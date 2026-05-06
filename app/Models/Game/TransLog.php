<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class TransLog extends Model
{
    protected $connection = 'game';
    protected $table = 'trans_log';
    public $timestamps = false;

    protected $fillable = [
        'username', 'seri', 'pin', 'type', 'amount', 'trans_id', 'status',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'user_id');
    }
}
