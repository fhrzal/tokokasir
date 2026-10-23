<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $primaryKey = 'transaction_id';

    protected $fillable = [
        'kode_transaksi',
        'client_ref',
        'shift_id',
        'user_id',
        'subtotal',
        'diskon',
        'total',
        'metode_bayar',
        'status_bayar',
        'uang_diterima',
        'kembalian',
        'referensi_bayar',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'diskon' => 'decimal:2',
            'total' => 'decimal:2',
            'uang_diterima' => 'decimal:2',
            'kembalian' => 'decimal:2',
            'waktu' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function metodeLabel(): string
    {
        return config('tokokasir.metode_bayar')[$this->metode_bayar] ?? ucfirst($this->metode_bayar);
    }
}
