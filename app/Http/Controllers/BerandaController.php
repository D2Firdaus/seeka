<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BerandaController extends Controller
{
    /**
     * Tampilkan daftar laporan barang beserta pencarian dan filternya.
     */
    public function index(Request $request): View
    {
        $types = array_values(array_intersect((array) $request->input('type'), ['lost', 'found']));

        $requestedPerPage = $request->integer('per_page', 9);
        $perPage = in_array($requestedPerPage, [9, 18, 36], true) ? $requestedPerPage : 9;

        $items = Item::query()
            ->with(['category', 'location', 'images'])
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('description'), fn ($query) => $query->where('description', 'like', '%'.$request->string('description').'%'))
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

        return view('beranda.index', [
            'items' => $items,
            'categories' => Category::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
        ]);
    }
}
