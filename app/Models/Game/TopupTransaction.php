<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class TopupTransaction extends Model
{
    protected $connection = 'game';
    protected $table = 'topup_transactions';
    public $timestamps = false;

    protected $fillable = [
        'trans_id', 'username', 'user_id', 'amount', 'currency', 'source',
        'note', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'user_id');
    }
}
