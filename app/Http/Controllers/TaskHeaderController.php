<?php

namespace App\Http\Controllers;

use App\Models\TaskHeader;
use Illuminate\Http\Request;

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
        // Ambil user dari session
        $userPic = $request->session()->get('user_link');

        // Jika permintaan AJAX dengan parameter date
        if ($request->ajax()) {
            $date = $request->get('date');

            $query = TaskHeader::with(['tasks' => function ($q) {
                $q->where('status', \App\Models\TaskList::STATUS['ACTIVE'])
                  ->orderByDesc('is_favorite')
                  ->orderByRaw('favorite_rank IS NULL')
                  ->orderBy('favorite_rank', 'asc');
            }]);

            // Filter berdasarkan tanggal jika ada
            if ($date) {
                $query->whereDate('tanggal', $date);
            }

            // Filter berdasarkan user pic
            $query->where('pic', $userPic);

            $taskHeaders = $query->get();

            return response()->json([
                'success' => true,
                'taskHeaders' => $taskHeaders
            ]);
        }

        // Default load view
        $data['taskHeaders'] = TaskHeader::with(['tasks' => function ($q) {
                $q->where('status', \App\Models\TaskList::STATUS['ACTIVE'])
                  ->orderByDesc('is_favorite')
                  ->orderByRaw('favorite_rank IS NULL')
                  ->orderBy('favorite_rank', 'asc');
            }])
            ->where('pic', $userPic) // filter sesuai user
            ->get();

        return view('task_headers.index', $data);
        
    }
}