<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Stall;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $students = [
            ['Galih Pratama', '20241001', 'XI MIPA 1', 'galih@bitego.test'],
            ['Sinta Dewi', '20241002', 'XI MIPA 2', 'sinta@bitego.test'],
            ['Rizky Aditya', '20241003', 'X IPS 1', 'rizky@bitego.test'],
            ['Ayu Lestari', '20241004', 'XII IPA 2', 'ayu@bitego.test'],
            ['Bima Sakti', '20241005', 'XI IPS 2', 'bima@bitego.test'],
        ];

        foreach ($students as [$name, $nis, $kelas, $email]) {
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => 'siswa',
                'nis' => $nis,
                'kelas' => $kelas,
            ]);
        }

        $daniel = User::create([
            'name' => 'Daniel',
            'email' => 'daniel@bitego.test',
            'password' => Hash::make('password'),
            'role' => 'vendor',
        ]);

        $acek = User::create([
            'name' => 'Acek',
            'email' => 'acek@bitego.test',
            'password' => Hash::make('password'),
            'role' => 'vendor',
        ]);

        $categories = [
            'Makanan Utama' => ['makanan-utama', 'fa-bowl-rice'],
            'Snack' => ['snack', 'fa-cookie-bite'],
            'Minuman' => ['minuman', 'fa-glass-water'],
            'Dessert' => ['dessert', 'fa-ice-cream'],
        ];

        foreach ($categories as $name => [$slug, $icon]) {
            Category::create(['name' => $name, 'slug' => $slug, 'icon' => $icon]);
        }

        $catMakanan = Category::where('slug', 'makanan-utama')->first();
        $catMinuman = Category::where('slug', 'minuman')->first();

        $stallsData = [
            [
                'Kantin Mama', 'kantin-mama', 'Masakan rumahan khas Mama — hangat, bersih, dan penuh rasa.', 'Lantai 1 - Area Timur', 'https://images.unsplash.com/photo-1547592180-85f173990554?w=600&auto=format&fit=crop', $daniel->id,
                [
                    ['Nasi Jeruk Cabe Garam', $catMakanan, 'Nasi hangat gurih dengan aroma jeruk, sambal cabe garam, dan ayam suwir goreng.', 15000, 'https://images.unsplash.com/photo-1626804475297-41608e074eb1?w=600&auto=format&fit=crop', 40, true],
                    ['Nasi Ayam Geprek', $catMakanan, 'Nasi hangat dengan ayam geprek renyah dan sambal bawang pedas.', 17000, 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=600&auto=format&fit=crop', 40, true],
                    ['Es Teh Manis', $catMinuman, 'Teh manis dingin yang menyegarkan, pas buat tengah hari.', 5000, 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=600&auto=format&fit=crop', 60, false],
                ],
            ],
            [
                'Kantin SMK', 'kantin-smk', 'Favorit anak sekolah! Aneka makanan dan minuman segar tiap hari.', 'Lantai 2 - Area Barat', 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=600&auto=format&fit=crop', $acek->id,
                [
                    ['Nasi Kuning', $catMakanan, 'Nasi kuning gurih komplit dengan ayam goreng, telur, dan kering tempe.', 12000, 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=600&auto=format&fit=crop', 45, true],
                    ['Nasi Goreng', $catMakanan, 'Nasi goreng bumbu spesial dengan telur ceplok dan timun.', 15000, 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=600&auto=format&fit=crop', 40, true],
                    ['Es Teh Tawar', $catMinuman, 'Es teh tawar dingin, segar tanpa gula.', 4000, 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=600&auto=format&fit=crop', 60, false],
                ],
            ],
        ];

        foreach ($stallsData as [$stallName, $slug, $desc, $loc, $stallImg, $userId, $menus]) {
            $stall = Stall::create([
                'user_id' => $userId,
                'name' => $stallName,
                'slug' => $slug,
                'description' => $desc,
                'image' => $stallImg,
                'location' => $loc,
                'is_active' => true,
            ]);

            foreach ($menus as [$mName, $category, $mDesc, $mPrice, $mImg, $stock, $popular]) {
                Menu::create([
                    'stall_id' => $stall->id,
                    'category_id' => $category->id,
                    'name' => $mName,
                    'slug' => Str::slug($mName).'-'.Str::random(4),
                    'description' => $mDesc,
                    'price' => $mPrice,
                    'image' => $mImg,
                    'stock' => $stock,
                    'is_available' => true,
                    'is_popular' => $popular,
                ]);
            }
        }

        // Sample orders for demonstrations
        $students = User::where('role', 'siswa')->get();
        $allStalls = Stall::all();
        $sampleStatuses = ['dimasak', 'siap_ambil', 'selesai', 'selesai', 'menunggu'];
        $usedCodes = [];

        foreach ($allStalls as $stall) {
            $count = mt_rand(2, 3);
            for ($i = 0; $i < $count; $i++) {
                $student = $students->random();
                $menus = $stall->menus()->get();

                if ($menus->isEmpty()) {
                    continue;
                }

                $randomMenus = $menus->random(min(2, $menus->count()));
                $qtyTotal = 0;
                $amount = 0;
                $itemsData = [];

                foreach ($randomMenus as $menu) {
                    $qty = mt_rand(1, 2);
                    $qtyTotal += $qty;
                    $amount += $menu->price * $qty;
                    $itemsData[] = [
                        'menu_id' => $menu->id,
                        'menu_name' => $menu->name,
                        'menu_image' => $menu->image,
                        'price' => $menu->price,
                        'quantity' => $qty,
                        'subtotal' => $menu->price * $qty,
                    ];
                }

                do {
                    $pickupCode = str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                } while (isset($usedCodes[$stall->id][$pickupCode]));

                $usedCodes[$stall->id][$pickupCode] = true;

                $order = Order::create([
                    'order_number' => 'BG-'.date('Ymd').'-'.strtoupper(Str::random(5)),
                    'user_id' => $student->id,
                    'stall_id' => $stall->id,
                    'pickup_slot' => $i % 2 === 0 ? 'istirahat_1' : 'istirahat_2',
                    'pickup_code' => $pickupCode,
                    'pickup_date' => now()->toDateString(),
                    'status' => $sampleStatuses[$i % count($sampleStatuses)],
                    'stock_deducted' => false,
                    'total_amount' => $amount,
                    'total_qty' => $qtyTotal,
                    'notes' => null,
                ]);

                foreach ($itemsData as $item) {
                    OrderItem::create(array_merge($item, ['order_id' => $order->id]));
                }
            }
        }
    }
}
