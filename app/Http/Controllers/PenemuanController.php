<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\ItemImage;
use App\Models\ItemMatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PenemuanController extends Controller
{
    /**
     * Tampilkan formulir pengajuan penemuan untuk barang kehilangan.
     */
    public function create(Request $request, Item $item): View
    {
        Gate::authorize('create', [ItemMatch::class, $item]);

        $item->load(['category', 'location', 'images']);

        $userFoundItems = $request->user()->items()
            ->where('type', 'found')
            ->where('status', 'open')
            ->orderBy('title')
            ->get();

        return view('penemuan.create', [
            'item' => $item,
            'userFoundItems' => $userFoundItems,
        ]);
    }

    /**
     * Simpan pengajuan penemuan barang yang cocok dengan laporan kehilangan.
     */
    public function store(Request $request, Item $item): RedirectResponse
    {
        Gate::authorize('create', [ItemMatch::class, $item]);

        $validated = $request->validate([
            'finding_description' => ['required', 'string', 'min:10'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'proof_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'existing_found_item_id' => [
                'nullable',
                Rule::exists('items', 'item_id')
                    ->where('user_id', $request->user()->user_id)
                    ->where('type', 'found')
                    ->where('status', 'open'),
            ],
            'declaration' => ['accepted'],
        ], [
            'finding_description.required' => 'Deskripsi penemuan wajib diisi.',
            'finding_description.min' => 'Deskripsi penemuan minimal 10 karakter.',
            'storage_location.max' => 'Lokasi penyimpanan maksimal 255 karakter.',
            'proof_image.image' => 'Foto barang harus berupa gambar.',
            'proof_image.mimes' => 'Foto barang harus berformat JPG atau PNG.',
            'proof_image.max' => 'Ukuran foto barang maksimal 5 MB.',
            'proof_image.uploaded' => 'Foto barang gagal diunggah, coba dengan file yang lebih kecil.',
            'existing_found_item_id.exists' => 'Laporan penemuan yang dipilih tidak valid atau bukan milik Anda.',
            'declaration.accepted' => 'Anda harus menyatakan kebenaran informasi penemuan ini.',
        ]);

        DB::transaction(function () use ($request, $item, $validated): void {
            if (! empty($validated['existing_found_item_id'])) {
                $foundItem = Item::query()
                    ->where('item_id', $validated['existing_found_item_id'])
                    ->firstOrFail();
            } else {
                $foundItem = Item::create([
                    'user_id' => $request->user()->user_id,
                    'category_id' => $item->category_id,
                    'location_id' => $item->location_id,
                    'type' => 'found',
                    'title' => 'Temuan: '.$item->title,
                    'description' => $validated['finding_description'],
                    'hidden_detail' => null,
                    'storage_location' => $validated['storage_location'] ?? null,
                    'date_event' => now(),
                    'status' => 'open',
                ]);

                if ($request->hasFile('proof_image')) {
                    $imagePath = $request->file('proof_image')->store('items', 'public');
                    ItemImage::create([
                        'item_id' => $foundItem->item_id,
                        'path' => $imagePath,
                        'sort_order' => 0,
                    ]);
                }
            }

            $match = ItemMatch::updateOrCreate([
                'lost_item_id' => $item->item_id,
                'found_item_id' => $foundItem->item_id,
            ], [
                'pipeline_version' => 'manual',
                'status' => 'new',
                'final_score' => 1.0,
            ]);

            AuditLog::create([
                'user_id' => $request->user()->user_id,
                'entity_type' => 'item_match',
                'entity_id' => $match->match_id,
                'action' => 'create',
                'old_values' => null,
                'new_values' => [
                    'lost_item_id' => $item->item_id,
                    'found_item_id' => $foundItem->item_id,
                    'finding_description' => $validated['finding_description'],
                ],
                'ip_address' => $request->ip(),
            ]);
        });

        return redirect()->route('home')->with('success', 'Laporan penemuan berhasil dikirim dan dihubungkan dengan laporan kehilangan.');
    }
}
