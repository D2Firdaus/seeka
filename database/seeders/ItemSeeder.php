<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ItemSeeder extends Seeder
{
    /**
     * Seed kategori, lokasi, dan contoh laporan barang.
     */
    public function run(): void
    {
        $users = collect([
            ['name' => 'Dede', 'email' => 'demo@kampus.ac.id'],
            ['name' => 'Sari Wulandari', 'email' => 'sari@kampus.ac.id'],
            ['name' => 'Rizky Pratama', 'email' => 'rizky@kampus.ac.id'],
        ])->map(fn (array $attrs) => User::firstOrCreate(
            ['email' => $attrs['email']],
            $attrs + [
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        ));

        $categories = collect(['Elektronik', 'Dokumen', 'Tas', 'Aksesori', 'Alat Tulis', 'Kendaraan', 'Pakaian', 'Lainnya'])
            ->map(fn (string $name) => Category::firstOrCreate(
                ['slug' => str()->slug($name)],
                ['name' => $name],
            ));

        $locations = collect(['Perpustakaan', 'Gedung A', 'Gedung B', 'Kantin', 'Lapangan', 'Masjid', 'Parkir', 'Aula'])
            ->map(fn (string $name) => Location::firstOrCreate(
                ['slug' => str()->slug($name)],
                ['name' => $name, 'description' => 'Area '.$name.' kampus'],
            ));

        $samples = [
            ['Dompet Hitam Kulit', 'lost', 'Aksesori', 'Kantin', 'Dompet hitam berisi KTM dan kartu ATM.'],
            ['Kunci Motor Honda', 'found', 'Kendaraan', 'Parkir', 'Kunci motor dengan gantungan kunci biru.'],
            ['Laptop ASUS Silver', 'lost', 'Elektronik', 'Perpustakaan', 'Laptop silver dengan stiker fakultas.'],
            ['Tas Ransel Cokelat', 'found', 'Tas', 'Gedung A', 'Tas ransel cokelat berisi buku kuliah.'],
            ['KTM Hilang', 'lost', 'Dokumen', 'Gedung B', 'Kartu tanda mahasiswa atas nama pemilik.'],
            ['Payung Lipat Biru', 'found', 'Aksesori', 'Aula', 'Payung lipat warna biru dongker.'],
            ['Jam Tangan Digital Hitam', 'lost', 'Aksesori', 'Lapangan', 'Jam tangan digital hitam dengan tali karet.'],
            ['Botol Minum Stainless', 'found', 'Lainnya', 'Kantin', 'Botol minum stainless dengan stiker dosen.'],
            ['Kacamata Minus', 'lost', 'Aksesori', 'Masjid', 'Kacamata minus dengan kotak hitam.'],
            ['Flashdisk 32GB', 'found', 'Elektronik', 'Gedung B', 'Flashdisk hitam dengan label nama.'],
            ['Jas Laboratorium', 'lost', 'Pakaian', 'Gedung A', 'Jas lab putih ukuran M.'],
            ['Charger Laptop 65W', 'found', 'Elektronik', 'Perpustakaan', 'Charger laptop dengan kabel panjang.'],
            ['Kunci Motor Yamaha', 'lost', 'Kendaraan', 'Parkir', 'Kunci motor dengan gantungan charm kucing.'],
            ['Helm Hitam Full Face', 'found', 'Kendaraan', 'Parkir', 'Helm hitam ukuran L dengan visor buram.'],
            ['Tote Bag Kanvas', 'found', 'Tas', 'Kantin', 'Tote bag kanvas warna krem berisi buku catatan.'],
            ['Powerbank 10000mAh', 'lost', 'Elektronik', 'Aula', 'Powerbank putih dengan tali karet.'],
            ['Buku Catatan Statistika', 'lost', 'Alat Tulis', 'Gedung A', 'Buku catatan mata kuliah Statistika lengkap.'],
            ['Kartu Tanda Penduduk', 'found', 'Dokumen', 'Kantin', 'KTP atas nama mahasiswa semester lima.'],
            ['Sepeda Gunung Biru', 'lost', 'Kendaraan', 'Lapangan', 'Sepeda gunung biru dengan rak belakang.'],
            ['Headset Kabel Putih', 'found', 'Elektronik', 'Gedung B', 'Headset kabel putih terlilit di meja laboratorium.'],
            ['Tas Selempang Hitam', 'lost', 'Tas', 'Masjid', 'Tas selempang hitam berisi Al-Quran kecil.'],
            ['Set Stabilo Enam Warna', 'found', 'Alat Tulis', 'Perpustakaan', 'Set stabilo enam warna belum terpakai.'],
            ['Jaket Denim Biru', 'lost', 'Pakaian', 'Aula', 'Jaket denim biru ukuran L tertinggal di kursi.'],
            ['Mouse Wireless', 'found', 'Elektronik', 'Perpustakaan', 'Mouse wireless abu-abu beserta penerima USB.'],
            ['Ijazah SMA', 'lost', 'Dokumen', 'Gedung A', 'Map berisi ijazah dan rapor SMA.'],
            ['Topi Kampus', 'found', 'Aksesori', 'Lapangan', 'Topi kampus warna maroon ukuran adjustable.'],
            ['Pulpen Grafik 0.5', 'lost', 'Alat Tulis', 'Gedung B', 'Pulpen grafik hitam dengan isi biru.'],
            ['Tablet Samsung', 'lost', 'Elektronik', 'Perpustakaan', 'Tablet dengan soft case merah muda.'],
            ['Dompet Kulit Cokelat', 'found', 'Aksesori', 'Kantin', 'Dompet kulit cokelat berisi kartu members kantin.'],
            ['Helm Putih Open Face', 'lost', 'Kendaraan', 'Parkir', 'Helm putih dengan kaca bening tergores.'],
            ['Tas Laptop Abu-abu', 'found', 'Tas', 'Gedung B', 'Tas laptop abu-abu berisi charger dan kabel data.'],
            ['Sertifikat Pelatihan', 'lost', 'Dokumen', 'Aula', 'Sertifikat pelatihan soft skill dalam map plastik.'],
            ['Earbuds True Wireless', 'found', 'Elektronik', 'Kantin', 'Earbuds putih beserta casing pengecasnya.'],
            ['Kacamata Hitam', 'found', 'Aksesori', 'Lapangan', 'Kacamata hitam dengan bingkai tipis.'],
            ['Buku Agenda 2026', 'lost', 'Alat Tulis', 'Perpustakaan', 'Buku agenda tahunan bertuliskan nama di sampul.'],
            ['Jas Hujan Ponco', 'found', 'Pakaian', 'Parkir', 'Jas hujan ponco biru dilipat rapi di motor.'],
            ['Kartu Ujian Tengah Semester', 'lost', 'Dokumen', 'Gedung B', 'Kartu ujian beserta pulpen pilihan ganda.'],
            ['Keyboard Mekanikal', 'lost', 'Elektronik', 'Gedung A', 'Keyboard mekanikal RGB dengan kabel type-C.'],
            ['Syal Kotak-kotak', 'found', 'Pakaian', 'Masjid', 'Syal kotak-kotak warna hijau tua.'],
            ['Sepeda Lipat Merah', 'found', 'Kendaraan', 'Lapangan', 'Sepeda lipat warna merah dengan keranjang depan.'],
            ['Cincin Perak', 'lost', 'Aksesori', 'Masjid', 'Cincin perak bergaris tertinggal di tempat wudu.'],
            ['Kotak Pensil Plastik', 'found', 'Alat Tulis', 'Gedung A', 'Kotak pensil berisi pensil warna dan penghapus.'],
            ['Goodie Bag Seminar', 'found', 'Tas', 'Aula', 'Goodie bag seminar berisi alat tulis dan brosur.'],
            ['Kartu SIM', 'lost', 'Dokumen', 'Kantin', 'Kartu SIM beserta tempat kartu SIM ponsel.'],
            ['Mousepad XL', 'lost', 'Elektronik', 'Gedung B', 'Mousepad hitam ukuran XL dengan tepi jahit.'],
            ['Hoodie Abu-abu', 'lost', 'Pakaian', 'Aula', 'Hoodie abu-abu ukuran M tertinggal di aula.'],
            ['Gelang Tali Biru', 'found', 'Aksesori', 'Lapangan', 'Gelang tali biru dengan manik-manik kayu.'],
            ['Penggaris Baja 30cm', 'lost', 'Alat Tulis', 'Gedung A', 'Penggaris baja di saku jas laboratorium.'],
            ['Speaker Bluetooth', 'found', 'Elektronik', 'Kantin', 'Speaker bluetooth kecil warna merah.'],
            ['Buku Panduan Skripsi', 'lost', 'Alat Tulis', 'Perpustakaan', 'Buku panduan penulisan skripsi dengan catatan tangan.'],
            ['Kunci Mobil', 'lost', 'Kendaraan', 'Parkir', 'Kunci mobil dengan remote dan gantungan kulit.'],
            ['Tas Ransel Merah', 'lost', 'Tas', 'Gedung B', 'Tas ransel merah berisi botol minum dan buku.'],
            ['Paspor', 'lost', 'Dokumen', 'Aula', 'Paspor mahasiswa program pertukaran pelajar.'],
            ['Payung Transparan', 'lost', 'Aksesori', 'Masjid', 'Payung transparan tertinggal setelah salat Jumat.'],
            ['Kabel Data USB-C', 'found', 'Elektronik', 'Gedung A', 'Kabel data USB-C putih sepanjang satu meter.'],
            ['Kaus Olahraga Kampus', 'lost', 'Pakaian', 'Lapangan', 'Kaus olahraga kampus ukuran L bekas latihan.'],
            ['Kalkulator Ilmiah', 'lost', 'Alat Tulis', 'Gedung B', 'Kalkulator ilmiah merek Casio milik mahasiswa teknik.'],
            ['Tali Kunci Kampus', 'found', 'Aksesori', 'Kantin', 'Tali kunci kampus dengan gantungan angkatan.'],
            ['SSD Eksternal 256GB', 'found', 'Elektronik', 'Perpustakaan', 'SSD eksternal beserta kabel pendek.'],
            ['Buku Tabungan', 'lost', 'Dokumen', 'Kantin', 'Buku tabungan bank beserta bukti transfer.'],
        ];

        Item::query()->delete();

        foreach ($samples as [$title, $type, $category, $location, $description]) {
            Item::create([
                'user_id' => $users->random()->user_id,
                'category_id' => $categories->firstWhere('name', $category)->category_id,
                'location_id' => $locations->firstWhere('name', $location)->location_id,
                'type' => $type,
                'title' => $title,
                'description' => $description,
                'hidden_detail' => 'Ciri khusus: '.$description,
                'date_event' => now()->subDays(rand(1, 90)),
                'status' => fake()->randomElement(['open', 'open', 'open', 'open', 'open', 'open', 'open', 'claimed', 'claimed', 'returned', 'returned', 'closed']),
                'storage_location' => 'Lokasi penyimpanan petugas',
            ]);
        }
    }
}
