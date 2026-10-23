<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_product_with_initial_stock_movement(): void
    {
        $category = Category::create(['nama_kategori' => 'Minuman']);

        $this->actingAs($this->user(User::ROLE_ADMIN))->post(route('admin.produk.store'), [
            'kode_produk' => '8886008101053',
            'nama_produk' => 'Aqua 600ml',
            'category_id' => $category->category_id,
            'harga_beli' => 2600,
            'harga_jual' => 3500,
            'stok' => 24,
            'stok_minimum' => 12,
            'status_aktif' => '1',
        ])->assertRedirect(route('admin.produk.index'));

        $product = Product::where('kode_produk', '8886008101053')->firstOrFail();
        $this->assertSame(24, $product->stok);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->product_id, 'jenis' => 'awal', 'jumlah' => 24]);
    }

    public function test_product_code_must_be_unique(): void
    {
        $existing = $this->product();

        $this->actingAs($this->user(User::ROLE_ADMIN))->post(route('admin.produk.store'), [
            'kode_produk' => $existing->kode_produk,
            'nama_produk' => 'Duplikat',
            'category_id' => $existing->category_id,
            'harga_beli' => 1,
            'harga_jual' => 2,
            'stok_minimum' => 0,
        ])->assertSessionHasErrors('kode_produk');
    }

    public function test_purchase_increases_stock_and_updates_cost(): void
    {
        $supplier = Supplier::create(['nama_supplier' => 'CV Sumber Rejeki']);
        $product = $this->product(['stok' => 5, 'harga_beli' => 2000]);

        $this->actingAs($this->user(User::ROLE_ADMIN))->post(route('admin.barang-masuk.store'), [
            'supplier_id' => $supplier->supplier_id,
            'tanggal' => now()->subHour()->format('Y-m-d H:i'),
            'no_faktur' => 'INV-001',
            'update_harga_beli' => '1',
            'items' => [['product_id' => $product->product_id, 'qty' => 24, 'harga_beli' => 2200]],
        ])->assertRedirect();

        $product->refresh();
        $this->assertSame(29, $product->stok);
        $this->assertEquals(2200, $product->harga_beli);
        $this->assertDatabaseHas('purchases', ['supplier_id' => $supplier->supplier_id, 'total' => 52800, 'no_faktur' => 'INV-001']);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->product_id, 'jenis' => 'masuk', 'jumlah' => 24, 'stok_sesudah' => 29]);
    }

    public function test_stock_adjustment_by_physical_count(): void
    {
        $product = $this->product(['stok' => 20]);

        $this->actingAs($this->user(User::ROLE_ADMIN))->post(route('admin.stok.adjust'), [
            'product_id' => $product->product_id,
            'mode' => 'fisik',
            'nilai' => 17,
            'alasan' => 'Stok opname',
        ])->assertSessionHasNoErrors();

        $this->assertSame(17, $product->fresh()->stok);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->product_id, 'jenis' => 'penyesuaian', 'jumlah' => -3, 'keterangan' => 'Stok opname']);
    }

    public function test_stock_adjustment_cannot_make_stock_negative(): void
    {
        $product = $this->product(['stok' => 2]);

        $this->actingAs($this->user(User::ROLE_ADMIN))->post(route('admin.stok.adjust'), [
            'product_id' => $product->product_id,
            'mode' => 'selisih',
            'nilai' => -5,
            'alasan' => 'Barang rusak',
        ])->assertSessionHasErrors('nilai');

        $this->assertSame(2, $product->fresh()->stok);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)->patch(route('admin.pengguna.toggle', $admin))->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->status);
    }

    public function test_admin_creates_kasir_account_with_hashed_password(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN))->post(route('admin.pengguna.store'), [
            'nama' => 'Kasir Baru',
            'email' => 'baru@tokokasir.test',
            'role' => 'kasir',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'status' => '1',
        ])->assertRedirect(route('admin.pengguna.index'));

        $user = User::where('email', 'baru@tokokasir.test')->firstOrFail();
        $this->assertNotSame('rahasia123', $user->password);
        $this->assertTrue(password_verify('rahasia123', $user->password));
    }
}
