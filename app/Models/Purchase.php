<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $primaryKey = 'purchase_id';

    protected $fillable = [
        'supplier_id',
        'user_id',
        'no_faktur',
        'tanggal',
        'total',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'datetime',
            'total' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    /** Nomor pembelian yang ditampilkan, mis. PB-000012. */
    public function kode(): string
    {
        return 'PB-'.str_pad((string) $this->purchase_id, 6, '0', STR_PAD_LEFT);
    }
}
