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
            'declaration' => ['accepted'],
            'photos' => ['required', 'array', 'min:1', 'max:3'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ], [
            'title.required' => 'Nama barang wajib diisi.',
            'title.max' => 'Nama barang maksimal 255 karakter.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori tidak valid.',
            'location_id.required' => 'Lokasi kehilangan wajib dipilih.',
            'location_id.exists' => 'Lokasi kehilangan tidak valid.',
            'date_event.required' => 'Tanggal kehilangan wajib diisi.',
            'date_event.date' => 'Tanggal kehilangan tidak valid.',
            'date_event.before_or_equal' => 'Tanggal kehilangan tidak boleh di masa depan.',
            'description.required' => 'Deskripsi barang wajib diisi.',
            'declaration.accepted' => 'Anda harus menyatakan informasi dalam laporan ini benar adanya.',
            'photos.required' => 'Foto barang wajib diunggah.',
            'photos.min' => 'Minimal 1 foto.',
            'photos.array' => 'Format foto tidak valid.',
            'photos.max' => 'Maksimal 3 foto.',
            'photos.*.image' => 'File harus berupa gambar.',
            'photos.*.mimes' => 'Foto harus berformat JPG atau PNG.',
            'photos.*.max' => 'Ukuran foto maksimal 5 MB per foto.',
            'photos.*.uploaded' => 'Foto gagal diunggah, coba dengan file yang lebih kecil.',
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

    /**
     * Tampilkan form laporan penemuan.
     */
    public function createFound(): View
    {
        return view('laporan.penemuan', [
            'categories' => Category::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    /**
     * Simpan laporan penemuan beserta foto barang dan detail verifikasi klaim.
     */
    public function storeFound(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,category_id'],
            'location_id' => ['required', 'exists:locations,location_id'],
            'date_event' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string'],
            'hidden_detail' => ['required', 'string', 'max:255'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'declaration' => ['accepted'],
            'photos' => ['required', 'array', 'min:1', 'max:3'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ], [
            'title.required' => 'Nama barang wajib diisi.',
            'title.max' => 'Nama barang maksimal 255 karakter.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori tidak valid.',
            'location_id.required' => 'Lokasi penemuan wajib dipilih.',
            'location_id.exists' => 'Lokasi penemuan tidak valid.',
            'date_event.required' => 'Tanggal penemuan wajib diisi.',
            'date_event.date' => 'Tanggal penemuan tidak valid.',
            'date_event.before_or_equal' => 'Tanggal penemuan tidak boleh di masa depan.',
            'description.required' => 'Deskripsi barang wajib diisi.',
            'hidden_detail.required' => 'Ciri khusus barang wajib diisi.',
            'hidden_detail.max' => 'Ciri khusus barang maksimal 255 karakter.',
            'storage_location.max' => 'Lokasi penyimpanan maksimal 255 karakter.',
            'declaration.accepted' => 'Anda harus menyatakan informasi dalam laporan ini benar adanya.',
            'photos.required' => 'Foto barang wajib diunggah.',
            'photos.min' => 'Minimal 1 foto.',
            'photos.array' => 'Format foto tidak valid.',
            'photos.max' => 'Maksimal 3 foto.',
            'photos.*.image' => 'File harus berupa gambar.',
            'photos.*.mimes' => 'Foto harus berformat JPG atau PNG.',
            'photos.*.max' => 'Ukuran foto maksimal 5 MB per foto.',
            'photos.*.uploaded' => 'Foto gagal diunggah, coba dengan file yang lebih kecil.',
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $item = Item::create([
                'user_id' => $request->user()->user_id,
                'category_id' => $validated['category_id'],
                'location_id' => $validated['location_id'],
                'type' => 'found',
                'title' => $validated['title'],
                'description' => $validated['description'],
                'hidden_detail' => $validated['hidden_detail'],
                'storage_location' => $validated['storage_location'] ?? null,
                'date_event' => $validated['date_event'],
            ]);

            foreach ($request->file('photos', []) as $sortOrder => $photo) {
                $item->images()->create([
                    'path' => $photo->store('items', 'public'),
                    'sort_order' => $sortOrder,
                ]);
            }
        });

        return redirect()->route('home')->with('success', 'Laporan penemuan berhasil dibuat.');
    }
}
