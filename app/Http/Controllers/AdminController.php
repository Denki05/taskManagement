<?php

namespace App\Http\Controllers;

use App\Models\TaskHeader;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // Middleware khusus admin
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!$request->session()->has('is_admin')) {
                abort(403, 'Unauthorized access');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $selectedPic = $request->get('pic');
        $selectedDate = $request->get('tanggal');

        $allPics = TaskHeader::distinct()->pluck('pic');

        $taskHeaders = collect(); 

        if ($selectedDate) {
            $query = TaskHeader::where('tanggal', $selectedDate)
                       ->with(['tasks' => function($q){
                           $q->orderByDesc('is_favorite')
                             ->orderByRaw('favorite_rank IS NULL')
                             ->orderBy('favorite_rank', 'asc');
                       }]);
            
            if ($selectedPic) {
                $query->where('pic', $selectedPic);
            }
            
            $taskHeaders = $query->get();
        }

        return view('admin.index', compact('taskHeaders', 'allPics', 'selectedPic', 'selectedDate'));
    }
}