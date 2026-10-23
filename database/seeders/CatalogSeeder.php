<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Kategori => [kode, nama, harga beli, harga jual, stok minimum, bobot popularitas].
     * Bobot dipakai DemoHistorySeeder untuk menentukan seberapa sering produk terjual.
     */
    public const PRODUCTS = [
        'Makanan Instan' => [
            ['8998866200301', 'Indomie Goreng 85g', 2700, 3500, 24, 10],
            ['8998866200318', 'Indomie Soto Mie 70g', 2600, 3300, 24, 6],
            ['8998866200325', 'Mie Sedaap Goreng 90g', 2650, 3400, 24, 7],
            ['8992388101015', 'Pop Mie Ayam Bawang 75g', 4300, 5500, 12, 3],
            ['8996001600146', 'Sarden ABC Saus Tomat 155g', 9200, 11500, 6, 2],
        ],
        'Minuman' => [
            ['8886008101053', 'Aqua Air Mineral 600ml', 2600, 3500, 24, 9],
            ['8886008101091', 'Aqua Air Mineral 1500ml', 4700, 6000, 12, 4],
            ['8992761111014', 'Teh Botol Sosro 450ml', 4200, 5500, 12, 6],
            ['8999999195649', 'Coca-Cola 390ml', 4800, 6000, 12, 4],
            ['8998009010231', 'Ultra Milk Coklat 250ml', 5300, 6500, 12, 5],
            ['8992696404441', 'Kopi Kapal Api Special Mix 25g', 1200, 1500, 30, 6],
            ['8997035563414', 'Pocari Sweat 500ml', 6200, 7500, 6, 3],
        ],
        'Sembako' => [
            ['8992936210115', 'Beras Ramos Premium 5kg', 64000, 72000, 5, 2],
            ['8993093665013', 'Gula Pasir Gulaku 1kg', 15500, 18000, 10, 4],
            ['8998866104005', 'Minyak Goreng Bimoli 2L', 34000, 39000, 6, 3],
            ['2000000000017', 'Telur Ayam Negeri 1kg', 26000, 30000, 5, 3],
            ['8992753101016', 'Tepung Terigu Segitiga Biru 1kg', 11000, 13500, 6, 2],
        ],
        'Makanan Ringan' => [
            ['8996001301012', 'Chitato Sapi Panggang 68g', 9800, 12000, 10, 3],
            ['8996001302019', 'Qtela Singkong Balado 60g', 6800, 8500, 10, 3],
            ['8992760221028', 'Oreo Vanilla 133g', 7900, 9500, 10, 4],
            ['8991102222016', 'Roma Kelapa 300g', 8800, 10500, 8, 2],
            ['8996001414019', 'Taro Net Seaweed 65g', 5800, 7000, 10, 2],
            ['8992696520011', 'SilverQueen Almond 58g', 13500, 16500, 6, 2],
        ],
        'Perawatan Diri' => [
            ['8999999059316', 'Lifebuoy Sabun Batang 110g', 3600, 4500, 12, 3],
            ['8999999036676', 'Pepsodent Pasta Gigi 190g', 11800, 14000, 8, 2],
            ['8999999706180', 'Sunsilk Shampo Hitam 170ml', 21000, 25000, 6, 1],
            ['8992694241017', 'Rexona Men Roll On 45ml', 17500, 21000, 4, 1],
        ],
        'Kebutuhan Rumah Tangga' => [
            ['8999999012342', 'Rinso Anti Noda 770g', 21500, 25500, 6, 2],
            ['8999999407155', 'Sunlight Jeruk Nipis 755ml', 14200, 17000, 6, 2],
            ['8993560025012', 'Baygon Aerosol 600ml', 36000, 42000, 3, 1],
            ['8886012140014', 'Tisu Paseo 250 Lembar', 12500, 15000, 6, 2],
            ['8999909001031', 'Baterai ABC Alkaline AA 2pcs', 7200, 9000, 6, 1],
        ],
        'Bumbu Dapur' => [
            ['8992770011014', 'Kecap Bango Manis 220ml', 9500, 11500, 8, 3],
            ['8992770024014', 'Royco Kaldu Ayam 94g', 4300, 5500, 10, 2],
            ['8999999407667', 'Masako Rasa Sapi 100g', 4500, 5500, 10, 2],
            ['8992388111014', 'Garam Refina 250g', 3200, 4000, 10, 1],
            ['8998866602020', 'Saus Sambal ABC 335ml', 10800, 13000, 6, 2],
        ],
    ];

    public function run(): void
    {
        foreach (self::PRODUCTS as $categoryName => $products) {
            $category = Category::firstOrCreate(['nama_kategori' => $categoryName]);

            foreach ($products as [$kode, $nama, $beli, $jual, $minimum]) {
                Product::updateOrCreate(['kode_produk' => $kode], [
                    'category_id' => $category->category_id,
                    'nama_produk' => $nama,
                    'harga_beli' => $beli,
                    'harga_jual' => $jual,
                    'stok_minimum' => $minimum,
                    'status_aktif' => true,
                ]);
            }
        }

        $suppliers = [
            ['nama_supplier' => 'CV Sumber Rejeki Kediri', 'telepon' => '0354-681234', 'alamat' => 'Jl. Hayam Wuruk No. 45, Kota Kediri'],
            ['nama_supplier' => 'PT Indogrosir Distribusi', 'telepon' => '0354-772210', 'alamat' => 'Jl. Soekarno Hatta No. 12, Kediri'],
            ['nama_supplier' => 'UD Makmur Jaya', 'telepon' => '0812-3300-4521', 'alamat' => 'Pasar Setono Betek Blok C-7, Kediri'],
            ['nama_supplier' => 'Toko Grosir Berkah', 'telepon' => '0857-4612-9087', 'alamat' => 'Jl. Dhoho No. 88, Kediri'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(['nama_supplier' => $supplier['nama_supplier']], $supplier);
        }
    }
}
