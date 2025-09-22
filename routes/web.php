<?php

use App\Controllers\TaskListController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Task Header (per tanggal)
Route::resource('task-headers', 'TaskHeaderController');

// Task List (detail per header)
Route::resource('task-lists', 'TaskListController');

// Aksi tambahan untuk task list
Route::post('/task-lists', 'TaskListController@store')->name('task-lists.store');
Route::post('task-lists/{taskList}/move', 'TaskListController@move')->name('task-lists.move');
Route::post('task-lists/{taskList}/favorite', 'TaskListController@favorite')->name('task-lists.favorite');
Route::post('/task-lists/reorder', 'TaskListController@reorder')->name('task-lists.reorder');
Route::post('/task-lists/{taskList}/hide', 'TaskListController@hideTask')->name('task-lists.hide');