<?php

namespace App\Http\Controllers;

use App\Models\TaskList;
use App\Models\TaskHeader;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TaskListController extends Controller
{
    public function store(Request $request)
    {
        try {
            $request->validate([
                'keterangan_task' => 'required|string',
            ]);
    
            // Ambil user yang sedang aktif dari session
            $userPic = $request->session()->get('user_link');
    
            if (!$userPic) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak dikenali.'
                ], 403);
            }
    
            // Cari atau buat TaskHeader sesuai tanggal hari ini dan user aktif
            $header = TaskHeader::firstOrCreate(
                [
                    'tanggal' => \Carbon\Carbon::today(),
                    'pic' => $userPic,
                ],
                [
                    'status' => 1,
                ]
            );
    
            // Buat task baru di bawah header yang sesuai
            TaskList::create([
                'task_header_id' => $header->id,
                'keterangan_task' => $request->keterangan_task,
                'status' => 1,
            ]);
    
            return response()->json(['success' => true]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function hideTask(TaskList $taskList)
    {
        $taskList->update(['status' => TaskList::STATUS['HIDE']]);
        
        if (request()->ajax()) {
            return response()->json(['success' => 'Task hidden successfully.']);
        }

        return back()->with('success', 'Task disembunyikan');
    }

    public function move(Request $request, TaskList $taskList)
    {
        // 1. Validasi request
        $request->validate([
            'move_date' => 'required|date',
        ]);

        $tanggalTujuan = $request->input('move_date');

        // 2. Pastikan task memiliki header sebelum melanjutkan
        if (!$taskList->header) {
            return response()->json(['success' => false, 'message' => 'Agenda tidak ditemukan.'], 404);
        }

        // 3. Cari atau buat TaskHeader baru untuk tanggal tujuan
        $taskHeaderTujuan = TaskHeader::firstOrCreate(
            ['tanggal' => $tanggalTujuan, 'pic' => $taskList->header->pic],
            ['status' => 'active']
        );

        // 4. Perbarui task lama dengan task_header_id yang baru.
        //    Ini hanya memindahkan satu entri di database.
        $taskList->update([
            'task_header_id' => $taskHeaderTujuan->id,
            'status' => TaskList::STATUS['ACTIVE'],
        ]);

        return response()->json(['success' => true, 'message' => 'Task berhasil dipindah.']);
    }

    public function favorite(TaskList $taskList)
    {
        DB::beginTransaction();
        try {
            if ($taskList->is_favorite) {
                $oldRank = $taskList->favorite_rank;
                $taskList->update([
                    'is_favorite' => false,
                    'favorite_rank' => null,
                ]);
                TaskList::where('is_favorite', true)
                    ->where('favorite_rank', '>', $oldRank)
                    ->decrement('favorite_rank');
            } else {
                TaskList::where('is_favorite', true)->increment('favorite_rank');
                $taskList->update([
                    'is_favorite' => true,
                    'favorite_rank' => 1,
                ]);
            }

            // Ambil task favorit terbaru urut
            $favorites = TaskList::where('is_favorite', true)
                ->orderBy('favorite_rank')
                ->get(['id', 'favorite_rank']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task berhasil diupdate sebagai favorit.',
                'task_id' => $taskList->id,
                'is_favorite' => $taskList->is_favorite,
                'favorite_rank' => $taskList->favorite_rank,
                'favorites' => $favorites,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status favorit: ' . $e->getMessage()
            ], 500);
        }
    }

    public function moveTo(Request $request, TaskHeader $header)
    {
        $request->validate([
            'move_date' => 'required|date'
        ]);

        $header->tanggal = $request->move_date;
        $header->save();

        return response()->json(['success' => true]);
    }

    public function hide(TaskList $taskList)
    {
        $taskList->update(['status' => 'hide']);
        return back()->with('success', 'Task berhasil disembunyikan');
    }

    public function reorder(Request $request)
    {
        $order = $request->input('order', []);

        if (!is_array($order) || count($order) === 0) {
            return response()->json(['success' => false, 'message' => 'Invalid order payload'], 400);
        }

        DB::beginTransaction();

        try {
            foreach ($order as $item) {
                if (!isset($item['id'])) {
                    continue; // skip jika tidak ada id
                }

                $id   = (int) $item['id'];
                $rank = isset($item['rank']) ? (int) $item['rank'] : null;

                if ($rank !== null) {
                    // Geser semua favorit lain yang punya rank >= target rank
                    TaskList::where('is_favorite', 1)
                        ->where('favorite_rank', '>=', $rank)
                        ->where('id', '!=', $id) // jangan geser dirinya sendiri
                        ->increment('favorite_rank');

                    // Update task yang dipindahkan
                    TaskList::where('id', $id)->update([
                        'is_favorite'   => 1,
                        'favorite_rank' => $rank,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function markDone(TaskList $taskList)
    {
        try {
            $taskList->update(['status' => TaskList::STATUS['DONE']]);
    
            return response()->json([
                'success' => true,
                'task_id' => $taskList->id,
                'status' => $taskList->status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status task: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function sync(Request $request)
    {
        try {
            // Pastikan user terautentikasi
            $userPic = $request->session()->get('user_link');
            if (!$userPic) {
                return response()->json(['success' => false, 'message' => 'User tidak dikenali.'], 403);
            }

            $changes = $request->input('changes', []);
            DB::beginTransaction();

            foreach ($changes as $change) {
                switch ($change['action']) {
                    case 'favorite':
                        $taskList = TaskList::find($change['id']);
                        if ($taskList) {
                            $taskList->update(['is_favorite' => $change['is_favorite']]);
                        }
                        break;
                    case 'done':
                        $taskList = TaskList::find($change['id']);
                        if ($taskList) {
                            $taskList->update(['status' => 2]);
                        }
                        break;
                    case 'hide':
                        $taskList = TaskList::find($change['id']);
                        if ($taskList) {
                            $taskList->update(['status' => 3]);
                        }
                        break;
                    case 'move':
                        $taskList = TaskList::find($change['id']);
                        if ($taskList) {
                            $targetDate = $change['move_date'];
                            $targetHeader = TaskHeader::firstOrCreate(
                                ['tanggal' => $targetDate, 'pic' => $userPic],
                                ['status' => 1]
                            );
                            $taskList->update(['task_header_id' => $targetHeader->id]);
                        }
                        break;
                    case 'reorder':
                        foreach ($change['order'] as $item) {
                            $taskList = TaskList::find($item['id']);
                            if ($taskList) {
                                $taskList->update(['rank' => $item['rank']]);
                            }
                        }
                        break;
                    case 'moveAll':
                        // Pindahkan semua task dari satu tanggal ke tanggal lain
                        $sourceHeader = TaskHeader::where('tanggal', $change['source_date'])
                            ->where('pic', $userPic)
                            ->first();

                        if ($sourceHeader) {
                            $targetHeader = TaskHeader::firstOrCreate(
                                ['tanggal' => $change['target_date'], 'pic' => $userPic],
                                ['status' => 1]
                            );
                            // Pindahkan semua task dari header sumber ke header target
                            TaskList::where('task_header_id', $sourceHeader->id)
                                ->update(['task_header_id' => $targetHeader->id]);

                            $sourceHeader->delete(); // Hapus header lama
                        }
                        break;
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Perubahan berhasil disinkronkan.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyinkronkan perubahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
