<?php

namespace App\Http\Controllers;

use App\Models\TaskHeader;
use Illuminate\Http\Request;

class TaskHeaderController extends Controller
{
    public function index(Request $request)
    {
        // jika request AJAX dengan parameter date
        if ($request->ajax()) {
            $date = $request->get('date');

            $query = TaskHeader::with(['tasks' => function ($q) {
                $q->orderByDesc('is_favorite')
                ->orderByRaw('favorite_rank IS NULL')
                ->orderBy('favorite_rank', 'asc');
            }]);

            if ($date) {
                $query->whereDate('created_at', $date); // atau pakai field task_date kalau ada
            }

            $taskHeaders = $query->get();

            return response()->json([
                'success' => true,
                'taskHeaders' => $taskHeaders
            ]);
        }

        // default load view
        $data['taskHeaders'] = TaskHeader::with(['tasks' => function ($q) {
            $q->orderByDesc('is_favorite')
            ->orderByRaw('favorite_rank IS NULL')
            ->orderBy('favorite_rank', 'asc');
        }])->get();

        return view('task_headers.index', $data);
    }
}