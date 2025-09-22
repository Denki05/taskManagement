<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskHeader extends Model
{
    protected $fillable = [
        'pic', 'tanggal', 'status'
    ];
    protected $table = 'task_headers';

    public function tasks()
    {
        return $this->hasMany(TaskList::class, 'task_header_id');
    }

    // 🔥 Tambahkan metode bantu ini untuk mendapatkan task yang aktif
    public function activeTasks()
    {
        return $this->tasks()->where('status', TaskList::STATUS['ACTIVE']);
    }
}