<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\TaskHeaderController;
use App\Http\Controllers\TaskListController;

// Halaman root langsung redirect ke task-headers
Route::get('/', function () {
    return redirect()->route('task-headers.index');
});

// Resource controller untuk task headers & task lists
Route::resource('task-headers', 'TaskHeaderController');
Route::resource('task-lists', 'TaskListController');

// Aksi tambahan untuk task list
Route::get('task-headers', 'TaskHeaderController@index')->name('task-headers.index');
Route::post('task-lists/{taskList}/move', 'TaskListController@move')->name('task-lists.move');
Route::post('task-lists/{taskList}/favorite', 'TaskListController@favorite')->name('task-lists.favorite');
Route::post('task-lists/reorder', 'TaskListController@reorder')->name('task-lists.reorder');
Route::post('task-lists/{taskList}/hide', 'TaskListController@hideTask')->name('task-lists.hide');
Route::post('task-lists', 'TaskListController@store')->name('task-lists.store');
Route::post('task-lists/{taskList}/done', 'TaskListController@markDone')->name('task-lists.done');
Route::post('/task-headers/move-all', [TaskHeaderController::class, 'moveAll'])->name('task-headers.moveAll');
Route::post('/tasks/sync', 'TaskListController@sync')->name('tasks.sync');

Route::get('/s/{userLink}', 'AccessController@handleUserLink')->name('user.link');

// Halaman admin untuk melihat semua task
Route::get('/admin', 'AdminController@index')->name('admin.index');

// Aksi tambahan jika ingin mengubah status, hapus, dsb.
Route::post('/admin/task-lists/{taskList}/update', 'AdminController@update')->name('admin.task-lists.update');

Route::get('/admin/s/{token}', function($token, Request $request){
    if ($token !== env('ADMIN_TOKEN')) {
        abort(403, 'Unauthorized access');
    }
    
    // Set session admin
    $request->session()->put('is_admin', true);

    return redirect()->route('admin.index');
})->name('admin.token');