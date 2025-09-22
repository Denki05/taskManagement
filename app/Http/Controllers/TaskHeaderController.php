<?php

namespace App\Http\Controllers;

use App\Models\TaskHeader;
use Illuminate\Http\Request;

class TaskHeaderController extends Controller
{
   public function index(Request $request)
    {
        // Jika permintaan AJAX dengan parameter date
        if ($request->ajax()) {
            $date = $request->get('date');

            $query = TaskHeader::with(['tasks' => function ($q) {
                // 🔥 Tambahkan kondisi ini untuk hanya mengambil task yang aktif (status = 1)
                $q->where('status', \App\Models\TaskList::STATUS['ACTIVE'])
                ->orderByDesc('is_favorite')
                ->orderByRaw('favorite_rank IS NULL')
                ->orderBy('favorite_rank', 'asc');
            }]);

            if ($date) {
                $query->whereDate('tanggal', $date);
            }

            $taskHeaders = $query->get();

            return response()->json([
                'success' => true,
                'taskHeaders' => $taskHeaders
            ]);
        }

        // Default load view
        $data['taskHeaders'] = TaskHeader::with(['tasks' => function ($q) {
            // 🔥 Tambahkan kondisi ini untuk hanya mengambil task yang aktif (status = 1)
            $q->where('status', \App\Models\TaskList::STATUS['ACTIVE'])
            ->orderByDesc('is_favorite')
            ->orderByRaw('favorite_rank IS NULL')
            ->orderBy('favorite_rank', 'asc');
        }])->get();

        return view('task_headers.index', $data);
    }
}