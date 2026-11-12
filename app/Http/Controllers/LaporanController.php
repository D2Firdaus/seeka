<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LaporanController extends Controller
{
    /**
     * Tampilkan daftar laporan milik pengguna yang sedang masuk.
     */
    public function index(Request $request): View
    {
        $types = array_values(array_intersect((array) $request->input('type'), ['lost', 'found']));

        $requestedPerPage = $request->integer('per_page', 9);
        $perPage = in_array($requestedPerPage, [9, 18, 36], true) ? $requestedPerPage : 9;

        $items = Item::query()
            ->with(['category', 'location', 'images'])
            ->where('user_id', $request->user()->user_id)
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->when($request->filled('location'), fn ($query) => $query->where('location_id', $request->integer('location')))
            ->when($request->filled('date_from') && $request->filled('date_to'), fn ($query) => $query->whereBetween('date_event', [$request->date('date_from'), $request->date('date_to')]))
            ->when($request->filled('date_from') && ! $request->filled('date_to'), fn ($query) => $query->whereDate('date_event', '>=', $request->date('date_from')))
            ->when(! $request->filled('date_from') && $request->filled('date_to'), fn ($query) => $query->whereDate('date_event', '<=', $request->date('date_to')))
            ->when($types !== [], fn ($query) => $query->whereIn('type', $types))
            ->when($request->filled('status'), fn ($query) => $query->whereIn('status', (array) $request->input('status')))
            ->latest('date_event')
            ->paginate($perPage)
            ->appends(array_filter($request->query(), fn ($value) => ! blank($value)));

        return view('laporan.index', [
            'items' => $items,
            'categories' => Category::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    /**
     * Tampilkan form edit laporan milik pengguna.
     */
    public function edit(Item $item): View
    {
        Gate::authorize('update', $item);

        return view('laporan.edit', [
            'item' => $item->load(['images', 'category', 'location']),
            'categories' => Category::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ]);
    }

    /**
     * Perbarui laporan milik pengguna beserta foto barangnya.
     */
    public function update(Request $request, Item $item): RedirectResponse
    {
        Gate::authorize('update', $item);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,category_id'],
            'location_id' => ['required', 'exists:locations,location_id'],
            'date_event' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string'],
            'hidden_detail' => [$item->type === 'found' ? 'required' : 'nullable', 'string', 'max:255'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer', 'exists:item_images,image_id'],
        ], [
            'title.required' => 'Nama barang wajib diisi.',
            'title.max' => 'Nama barang maksimal 255 karakter.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori tidak valid.',
            'location_id.required' => 'Lokasi wajib dipilih.',
            'location_id.exists' => 'Lokasi tidak valid.',
            'date_event.required' => 'Tanggal wajib diisi.',
            'date_event.date' => 'Tanggal tidak valid.',
            'date_event.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
            'description.required' => 'Deskripsi barang wajib diisi.',
            'hidden_detail.required' => 'Ciri khusus barang wajib diisi.',
            'hidden_detail.max' => 'Ciri khusus barang maksimal 255 karakter.',
            'storage_location.max' => 'Lokasi penyimpanan maksimal 255 karakter.',
            'photos.array' => 'Format foto tidak valid.',
            'photos.max' => 'Maksimal 3 foto.',
            'photos.*.image' => 'File harus berupa gambar.',
            'photos.*.mimes' => 'Foto harus berformat JPG atau PNG.',
            'photos.*.max' => 'Ukuran foto maksimal 5 MB per foto.',
            'photos.*.uploaded' => 'Foto gagal diunggah, coba dengan file yang lebih kecil.',
            'remove_images.array' => 'Format foto tidak valid.',
            'remove_images.*.exists' => 'Foto tidak ditemukan.',
        ]);

        $removeIds = array_values(array_intersect(
            array_map('intval', (array) $request->input('remove_images', [])),
            $item->images()->pluck('image_id')->all(),
        ));
        $keptImages = $item->images()->whereNotIn('image_id', $removeIds)->count();
        $newPhotos = $request->file('photos', []);

        if ($keptImages + count($newPhotos) > 3) {
            return back()
                ->withInput()
                ->withErrors(['photos' => 'Total foto tidak boleh melebihi 3 foto.']);
        }

        DB::transaction(function () use ($validated, $item, $removeIds, $newPhotos): void {
            $item->update([
                'category_id' => $validated['category_id'],
                'location_id' => $validated['location_id'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'hidden_detail' => $validated['hidden_detail'] ?? null,
                'storage_location' => $validated['storage_location'] ?? null,
                'date_event' => $validated['date_event'],
            ]);

            if ($removeIds !== []) {
                $item->images()->whereIn('image_id', $removeIds)->delete();
            }

            $nextSort = (int) $item->images()->max('sort_order') + 1;

            foreach ($newPhotos as $photo) {
                $item->images()->create([
                    'path' => $photo->store('items', 'public'),
                    'sort_order' => $nextSort++,
                ]);
            }
        });

        return redirect()->route('laporan.saya')->with('success', 'Laporan berhasil diperbarui.');
    }

    /**
     * Hapus laporan milik pengguna (soft delete).
     */
    public function destroy(Request $request, Item $item): RedirectResponse
    {
        Gate::authorize('delete', $item);

        $item->delete();

        return redirect()->route('laporan.saya')->with('success', 'Laporan berhasil dihapus.');
    }

    /**
     * Tutup laporan milik pengguna sehingga tidak lagi muncul di pencarian publik.
     */
    public function close(Request $request, Item $item): RedirectResponse
    {
        Gate::authorize('close', $item);

        $item->update(['status' => 'closed']);

        return redirect()->route('laporan.saya')->with('success', 'Laporan berhasil ditutup.');
    }

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
