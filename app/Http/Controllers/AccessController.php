<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccessController extends Controller
{
    public function handleUserLink($userLink)
    {
        $validLinks = explode(',', env('USER_LINKS', ''));
    
        if (!in_array($userLink, $validLinks)) {
            abort(403, 'Unauthorized access');
        }
    
        session(['user_link' => $userLink]);
    
        return redirect()->route('task-headers.index'); // session sudah ada
    }
}