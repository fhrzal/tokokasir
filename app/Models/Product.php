<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $primaryKey = 'product_id';

    protected $fillable = [
        'category_id',
        'kode_produk',
        'nama_produk',
        'harga_beli',
        'harga_jual',
        'stok',
        'stok_minimum',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'harga_beli' => 'decimal:2',
            'harga_jual' => 'decimal:2',
            'stok' => 'integer',
            'stok_minimum' => 'integer',
            'status_aktif' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class, 'product_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status_aktif', true);
    }

    /** Stok masih ada tetapi sudah mencapai batas minimum. */
    public function scopeLowStock(Builder $query): void
    {
        $query->where('stok', '>', 0)->whereColumn('stok', '<=', 'stok_minimum');
    }

    public function scopeOutOfStock(Builder $query): void
    {
        $query->where('stok', '<=', 0);
    }

    /** Stok menipis atau habis. */
    public function scopeNeedsRestock(Builder $query): void
    {
        $query->whereColumn('stok', '<=', 'stok_minimum');
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (filled($term)) {
            $query->where(function (Builder $q) use ($term) {
                $q->where('nama_produk', 'like', "%{$term}%")
                    ->orWhere('kode_produk', 'like', "%{$term}%");
            });
        }
    }

    /** aman | menipis | habis */
    public function stockStatus(): string
    {
        if ($this->stok <= 0) {
            return 'habis';
        }

        return $this->stok <= $this->stok_minimum ? 'menipis' : 'aman';
    }
}
