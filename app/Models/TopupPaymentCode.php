<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopupPaymentCode extends Model
{
    protected $fillable = [
        'nro_account_id',
        'code',
    ];

    protected function casts(): array
    {
        return [
            'nro_account_id' => 'integer',
        ];
    }
}
