<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskList extends Model
{
    protected $fillable = [
        'task_header_id', 'keterangan_task', 'ref_cust',
        'status', 'is_favorite', 'favorite_rank', 'move_to_date'
    ];
    protected $table = 'task_lists';

    const STATUS = [
        'HIDE' => 0,
        'ACTIVE' => 1,
        'DONE' => 2,
    ];

    public function header()
    {
        // 🔥 Tambahkan withDefault() di sini
        return $this->belongsTo(TaskHeader::class, 'task_header_id')->withDefault();
    }

    public function status()
    {
        return array_search($this->status, self::STATUS);
    }
}
