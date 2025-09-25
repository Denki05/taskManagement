<?php

namespace App\Http\Controllers;

use App\Models\TaskHeader;
use Illuminate\Http\Request;
use DB;

class TaskHeaderController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!$request->session()->has('user_link')) {
                abort(403, 'Unauthorized access');
            }
            return $next($request);
        });
    }
    
    public function index(Request $request)
    {
        $userPic = $request->session()->get('user_link');
    
        if ($request->ajax()) {
            $date = $request->get('date');
    
            $query = TaskHeader::with(['tasks' => function ($q) {
                $q->whereIn('status', [\App\Models\TaskList::STATUS['ACTIVE'], \App\Models\TaskList::STATUS['DONE']])
                  ->orderByDesc('is_favorite')
                  ->orderByRaw('favorite_rank IS NULL')
                  ->orderBy('favorite_rank', 'asc');
            }]);
    
            if ($date) {
                $query->whereDate('tanggal', $date);
            }
    
            $query->where('pic', $userPic);
    
            $taskHeaders = $query->get();
    
            return response()->json([
                'success' => true,
                'taskHeaders' => $taskHeaders
            ]);
        }
    
        $data['taskHeaders'] = TaskHeader::with(['tasks' => function ($q) {
                $q->whereIn('status', [\App\Models\TaskList::STATUS['ACTIVE'], \App\Models\TaskList::STATUS['DONE']])
                  ->orderByDesc('is_favorite')
                  ->orderByRaw('favorite_rank IS NULL')
                  ->orderBy('favorite_rank', 'asc');
            }])
            ->where('pic', $userPic)
            ->get();
    
        return view('task_headers.index', $data);
    }
    
    public function moveAll(Request $request)
    {
        $request->validate([
            'source_date' => 'required|date',
            'target_date' => 'required|date|after_or_equal:source_date',
        ]);

        $userPic = $request->session()->get('user_link');
        $sourceDate = $request->input('source_date');
        $targetDate = $request->input('target_date');

        DB::beginTransaction();
        try {
            // Ambil TaskHeader beserta semua tasks-nya dari tanggal sumber
            $sourceHeader = TaskHeader::with('tasks')
                                      ->where('tanggal', $sourceDate)
                                      ->where('pic', $userPic)
                                      ->first();

            if (!$sourceHeader) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Tidak ada agenda yang ditemukan pada tanggal tersebut.'
                ], 404);
            }

            // Periksa apakah sudah ada header di tanggal tujuan
            $targetHeader = TaskHeader::where('tanggal', $targetDate)
                                      ->where('pic', $userPic)
                                      ->first();

            if ($targetHeader) {
                // Jika header di tanggal tujuan sudah ada, gabungkan task-nya
                foreach ($sourceHeader->tasks as $task) {
                    $task->task_header_id = $targetHeader->id;
                    $task->save();
                }
                // Hapus header lama setelah semua list dipindahkan
                $sourceHeader->delete();
            } else {
                // Jika header di tanggal tujuan tidak ada, cukup ubah tanggal header lama
                $sourceHeader->tanggal = $targetDate;
                $sourceHeader->save();
            }

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Semua agenda berhasil dipindahkan.'
            ]);

        } catch (\Exception $e) {
            dd($e);
            DB::rollBack();
            return response()->json([
                'success' => false, 
                'message' => 'Terjadi kesalahan saat memindahkan agenda: ' . $e->getMessage()
            ], 500);
        }
    }
}