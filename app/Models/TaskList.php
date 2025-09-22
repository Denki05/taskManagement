<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskList extends Model
{
    protected $fillable = [
        'task_header_id', 'keterangan_task', 'ref_cust',
        'status', 'is_favorite', 'favorite_rank', 'move_to_date'
    ];

    public function header()
    {
        return $this->belongsTo(TaskHeader::class, 'task_header_id');
    }
}
