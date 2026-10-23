<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const JENIS = [
        'awal' => 'Stok Awal',
        'masuk' => 'Barang Masuk',
        'penjualan' => 'Penjualan',
        'penyesuaian' => 'Penyesuaian',
    ];

    protected $primaryKey = 'movement_id';

    protected $fillable = [
        'product_id',
        'user_id',
        'jenis',
        'jumlah',
        'stok_sebelum',
        'stok_sesudah',
        'referensi',
        'keterangan',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'stok_sebelum' => 'integer',
            'stok_sesudah' => 'integer',
            'waktu' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jenisLabel(): string
    {
        return self::JENIS[$this->jenis] ?? ucfirst($this->jenis);
    }
}
