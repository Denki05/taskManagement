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

            $header = TaskHeader::firstOrCreate(
                [
                    'tanggal' => \Carbon\Carbon::today(),
                    'pic' => "Erick",
                ],
                [
                    'status' => 'active',
                ]
            );

            TaskList::create([
                'task_header_id' => $header->id,
                'keterangan_task' => $request->keterangan_task,
                'status' => 'active',
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            dd($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, TaskList $taskList)
    {
        $taskList->update($request->all());
        return back()->with('success', 'Task berhasil diupdate');
    }

    public function destroy(TaskList $taskList)
    {
        $taskList->update(['status' => 'hide']);
        return back()->with('success', 'Task disembunyikan');
    }

    // Tambahan fitur

    public function move(Request $request, TaskList $taskList)
    {
        // simpan tanggal tujuan di field move_to_date
        $taskList->update(['move_to_date' => $request->agenda_date]);

        // cari atau buat header baru
        $header = TaskHeader::firstOrCreate([
            'tanggal' => $request->agenda_date,
            'pic' => $taskList->header->pic,
        ], [
            'status' => 'active',
        ]);

        // buat task baru di tanggal tujuan
        TaskList::create([
            'task_header_id' => $header->id,
            'keterangan_task' => $taskList->keterangan_task,
            'ref_cust' => $taskList->ref_cust,
            'status' => 'active',
        ]);

        return back()->with('success', 'Task berhasil dipindah');
    }

    public function favorite(TaskList $taskList)
    {
        $taskList->update(['is_favorite' => !$taskList->is_favorite]);
        return back()->with('success', 'Task diupdate sebagai favorit');
    }

    public function toggleFavorite(TaskList $task)
    {
        $task->favorite_rank = $task->favorite_rank ? null : 1; // contoh toggle sederhana
        $task->save();

        return response()->json(['success' => true]);
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
}
