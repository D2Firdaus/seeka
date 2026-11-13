<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MetadataController extends Controller
{
    /**
     * Tampilkan daftar kategori beserta jumlah laporan yang memakainya.
     */
    public function kategoriIndex(): View
    {
        return view('admin.metadata.kategori.index', [
            'categories' => Category::query()
                ->withCount(['items' => fn ($query) => $query->withTrashed()])
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Tampilkan form tambah kategori.
     */
    public function kategoriCreate(): View
    {
        return view('admin.metadata.kategori.form', [
            'category' => null,
        ]);
    }

    /**
     * Simpan kategori baru beserta catatan audit.
     */
    public function kategoriStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.string' => 'Nama kategori tidak valid.',
            'name.max' => 'Nama kategori maksimal 100 karakter.',
            'name.unique' => 'Nama kategori sudah digunakan.',
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $category = Category::create([
                'name' => $validated['name'],
                'slug' => $this->buildSlug($validated['name'], Category::class),
            ]);

            $this->recordAudit($request, 'category', $category->category_id, 'create', null, [
                'name' => $category->name,
                'slug' => $category->slug,
            ]);
        });

        return redirect()->route('admin.metadata.kategori')->with('success', 'Kategori berhasil ditambahkan.');
    }

    /**
     * Tampilkan form ubah kategori.
     */
    public function kategoriEdit(Category $category): View
    {
        return view('admin.metadata.kategori.form', [
            'category' => $category,
        ]);
    }

    /**
     * Perbarui kategori beserta catatan audit.
     */
    public function kategoriUpdate(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->category_id, 'category_id')],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.string' => 'Nama kategori tidak valid.',
            'name.max' => 'Nama kategori maksimal 100 karakter.',
            'name.unique' => 'Nama kategori sudah digunakan.',
        ]);

        DB::transaction(function () use ($request, $category, $validated): void {
            $oldValues = [
                'name' => $category->name,
                'slug' => $category->slug,
            ];

            $category->update([
                'name' => $validated['name'],
                'slug' => $this->buildSlug($validated['name'], Category::class, $category->category_id),
            ]);

            $this->recordAudit($request, 'category', $category->category_id, 'update', $oldValues, [
                'name' => $category->name,
                'slug' => $category->slug,
            ]);
        });

        return redirect()->route('admin.metadata.kategori')->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Hapus kategori yang belum dipakai laporan mana pun beserta catatan audit.
     */
    public function kategoriDestroy(Request $request, Category $category): RedirectResponse
    {
        $usedBy = $category->items()->withTrashed()->count();

        if ($usedBy > 0) {
            return back()->withErrors([
                'name' => "Kategori masih digunakan oleh {$usedBy} laporan dan tidak dapat dihapus.",
            ]);
        }

        DB::transaction(function () use ($request, $category): void {
            $oldValues = [
                'name' => $category->name,
                'slug' => $category->slug,
            ];

            $category->delete();

            $this->recordAudit($request, 'category', $category->category_id, 'delete', $oldValues, null);
        });

        return redirect()->route('admin.metadata.kategori')->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * Tampilkan daftar lokasi beserta jumlah laporan yang memakainya.
     */
    public function lokasiIndex(): View
    {
        return view('admin.metadata.lokasi.index', [
            'locations' => Location::query()
                ->withCount(['items' => fn ($query) => $query->withTrashed()])
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Tampilkan form tambah lokasi.
     */
    public function lokasiCreate(): View
    {
        return view('admin.metadata.lokasi.form', [
            'location' => null,
        ]);
    }

    /**
     * Simpan lokasi baru beserta catatan audit.
     */
    public function lokasiStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('locations', 'name')],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Nama lokasi wajib diisi.',
            'name.string' => 'Nama lokasi tidak valid.',
            'name.max' => 'Nama lokasi maksimal 150 karakter.',
            'name.unique' => 'Nama lokasi sudah digunakan.',
            'description.string' => 'Deskripsi lokasi tidak valid.',
            'description.max' => 'Deskripsi lokasi maksimal 1000 karakter.',
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $location = Location::create([
                'name' => $validated['name'],
                'slug' => $this->buildSlug($validated['name'], Location::class),
                'description' => $validated['description'] ?? null,
            ]);

            $this->recordAudit($request, 'location', $location->location_id, 'create', null, [
                'name' => $location->name,
                'slug' => $location->slug,
                'description' => $location->description,
            ]);
        });

        return redirect()->route('admin.metadata.lokasi')->with('success', 'Lokasi berhasil ditambahkan.');
    }

    /**
     * Tampilkan form ubah lokasi.
     */
    public function lokasiEdit(Location $location): View
    {
        return view('admin.metadata.lokasi.form', [
            'location' => $location,
        ]);
    }

    /**
     * Perbarui lokasi beserta catatan audit.
     */
    public function lokasiUpdate(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('locations', 'name')->ignore($location->location_id, 'location_id')],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Nama lokasi wajib diisi.',
            'name.string' => 'Nama lokasi tidak valid.',
            'name.max' => 'Nama lokasi maksimal 150 karakter.',
            'name.unique' => 'Nama lokasi sudah digunakan.',
            'description.string' => 'Deskripsi lokasi tidak valid.',
            'description.max' => 'Deskripsi lokasi maksimal 1000 karakter.',
        ]);

        DB::transaction(function () use ($request, $location, $validated): void {
            $oldValues = [
                'name' => $location->name,
                'slug' => $location->slug,
                'description' => $location->description,
            ];

            $location->update([
                'name' => $validated['name'],
                'slug' => $this->buildSlug($validated['name'], Location::class, $location->location_id),
                'description' => $validated['description'] ?? null,
            ]);

            $this->recordAudit($request, 'location', $location->location_id, 'update', $oldValues, [
                'name' => $location->name,
                'slug' => $location->slug,
                'description' => $location->description,
            ]);
        });

        return redirect()->route('admin.metadata.lokasi')->with('success', 'Lokasi berhasil diperbarui.');
    }

    /**
     * Hapus lokasi yang belum dipakai laporan mana pun beserta catatan audit.
     */
    public function lokasiDestroy(Request $request, Location $location): RedirectResponse
    {
        $usedBy = $location->items()->withTrashed()->count();

        if ($usedBy > 0) {
            return back()->withErrors([
                'name' => "Lokasi masih digunakan oleh {$usedBy} laporan dan tidak dapat dihapus.",
            ]);
        }

        DB::transaction(function () use ($request, $location): void {
            $oldValues = [
                'name' => $location->name,
                'slug' => $location->slug,
                'description' => $location->description,
            ];

            $location->delete();

            $this->recordAudit($request, 'location', $location->location_id, 'delete', $oldValues, null);
        });

        return redirect()->route('admin.metadata.lokasi')->with('success', 'Lokasi berhasil dihapus.');
    }

    /**
     * Buat slug unik dari nama; tabrakan diselesaikan dengan akhiran angka.
     *
     * @param  class-string<Model>  $modelClass
     */
    private function buildSlug(string $name, string $modelClass, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while ($modelClass::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where((new $modelClass)->getKeyName(), '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * Catat perubahan data master ke audit trail.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function recordAudit(Request $request, string $entityType, int $entityId, string $action, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'user_id' => $request->user()->user_id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
        ]);
    }
}
