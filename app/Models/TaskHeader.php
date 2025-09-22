<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskHeader extends Model
{
    protected $fillable = [
        'pic', 'tanggal', 'status'
    ];

    public function tasks()
    {
        return $this->hasMany(TaskList::class, 'task_header_id', 'id');
    }
}