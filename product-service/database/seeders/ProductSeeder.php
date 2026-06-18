<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'id'          => 'b1111111-1111-1111-1111-111111111111',
                'code'        => 'PRD-001',
                'name'        => 'Kaos Polos Premium',
                'description' => 'Kaos polos bahan cotton combed 30s, nyaman dipakai harian.',
                'price'       => 75000,
                'stock'       => 100,
            ],
            [
                'id'          => 'b2222222-2222-2222-2222-222222222222',
                'code'        => 'PRD-002',
                'name'        => 'Sepatu Sneakers Casual',
                'description' => 'Sepatu sneakers ringan untuk aktivitas sehari-hari.',
                'price'       => 250000,
                'stock'       => 50,
            ],
            [
                'id'          => 'b3333333-3333-3333-3333-333333333333',
                'code'        => 'PRD-003',
                'name'        => 'Tas Selempang Kanvas',
                'description' => 'Tas selempang bahan kanvas tebal, cocok untuk kuliah.',
                'price'       => 120000,
                'stock'       => 75,
            ],
            [
                'id'          => 'b4444444-4444-4444-4444-444444444444',
                'code'        => 'PRD-004',
                'name'        => 'Topi Baseball',
                'description' => 'Topi baseball adjustable, bahan twill tebal.',
                'price'       => 45000,
                'stock'       => 150,
            ],
            [
                'id'          => 'b5555555-5555-5555-5555-555555555555',
                'code'        => 'PRD-005',
                'name'        => 'Jaket Hoodie Oversize',
                'description' => 'Hoodie oversize bahan fleece tebal, hangat dipakai.',
                'price'       => 180000,
                'stock'       => 60,
            ],
            [
                'id'          => 'b6666666-6666-6666-6666-666666666666',
                'code'        => 'PRD-006',
                'name'        => 'Celana Chino Slimfit',
                'description' => 'Celana chino bahan stretch, nyaman dan fleksibel.',
                'price'       => 165000,
                'stock'       => 40,
            ],
            [
                'id'          => 'b7777777-7777-7777-7777-777777777777',
                'code'        => 'PRD-007',
                'name'        => 'Dompet Kulit Pria',
                'description' => 'Dompet kulit asli dengan banyak slot kartu.',
                'price'       => 95000,
                'stock'       => 80,
            ],
            [
                'id'          => 'b8888888-8888-8888-8888-888888888888',
                'code'        => 'PRD-008',
                'name'        => 'Jam Tangan Digital',
                'description' => 'Jam tangan digital tahan air, cocok untuk olahraga.',
                'price'       => 220000,
                'stock'       => 35,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['id' => $product['id']], $product);
        }
    }
}
