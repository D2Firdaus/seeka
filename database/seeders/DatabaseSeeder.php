<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Dompet',
            'Kartu',
            'Kunci',
            'Alat Tulis & Buku',
            'Handphone',
            'Charger',
            'Pakaian & Aksesori',
            'Lainnya',
        ];
        foreach ($categories as $i => $name) {
            DB::table('categories')->updateOrInsert(
                ['slug' => Str::slug($name)],
                [
                    'category_id' => sprintf('CTG%03d', $i + 1),
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        // Ganti sesuai gedung/area di kampus kamu
        $locations = [
            'FASILKOM LAB LANJUT 1',
            'FASILKOM LAB LANJUT 2',
            'FASILKOM LAB DASAR 1',
            'FASILKOM LAB DASAR 2',
            'FASILKOM 4.75-1',
            'FASILKOM 4.76-2',
            'FASILKOM 4.77-3',
            'FASILKOM 4.79-5',
        ];
        foreach ($locations as $i => $name) {
            DB::table('locations')->updateOrInsert(
                ['name' => $name],
                [
                    'location_id' => sprintf('LOC%03d', $i + 1),
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        // Akun admin awal, GANTI password setelah login pertama
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@example.com'],
            [
                'user_id' => 'USR001',
                'name' => 'Administrator',
                'password' => Hash::make('ganti-password-ini'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
    }
}
