<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LaporanController extends Controller
{
    /**
     * Tampilkan form laporan kehilangan.
     */
    public function create(): View
    {
        return view('laporan.kehilangan', [
            'categories' => Category::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    /**
     * Simpan laporan kehilangan beserta foto barang.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,category_id'],
            'location_id' => ['required', 'exists:locations,location_id'],
            'date_event' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'title.required' => 'Judul wajib diisi.',
            'title.max' => 'Judul maksimal 255 karakter.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori tidak valid.',
            'location_id.required' => 'Lokasi kejadian wajib dipilih.',
            'location_id.exists' => 'Lokasi kejadian tidak valid.',
            'date_event.required' => 'Tanggal kejadian wajib diisi.',
            'date_event.date' => 'Tanggal kejadian tidak valid.',
            'date_event.before_or_equal' => 'Tanggal kejadian tidak boleh di masa depan.',
            'description.required' => 'Deskripsi wajib diisi.',
            'photos.array' => 'Format foto tidak valid.',
            'photos.max' => 'Maksimal 5 foto.',
            'photos.*.image' => 'File harus berupa gambar.',
            'photos.*.mimes' => 'Foto harus berformat JPG atau PNG.',
            'photos.*.max' => 'Ukuran foto maksimal 2 MB per file.',
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $item = Item::create([
                'user_id' => $request->user()->user_id,
                'category_id' => $validated['category_id'],
                'location_id' => $validated['location_id'],
                'type' => 'lost',
                'title' => $validated['title'],
                'description' => $validated['description'],
                'date_event' => $validated['date_event'],
            ]);

            foreach ($request->file('photos', []) as $sortOrder => $photo) {
                $item->images()->create([
                    'path' => $photo->store('items', 'public'),
                    'sort_order' => $sortOrder,
                ]);
            }
        });

        return redirect()->route('home')->with('success', 'Laporan kehilangan berhasil dibuat.');
    }
}
