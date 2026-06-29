<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $items = GalleryItem::when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Admin/Gallery/Index', [
            'items' => $items,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Gallery/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'      => ['nullable', 'string', 'max:255'],
            'url'        => ['required', 'string', 'max:2000'],
            'type'       => ['required', 'in:video,photo'],
            'format'     => ['required', 'in:landscape,portrait,square'],
            'platform'   => ['required', 'in:youtube,facebook,instagram,other'],
            'is_active'  => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        GalleryItem::create($validated);

        return redirect()->route('admin.gallery.index')->with('success', 'Elemento agregado a la galería.');
    }

    public function edit(GalleryItem $gallery)
    {
        return Inertia::render('Admin/Gallery/Edit', ['item' => $gallery]);
    }

    public function update(Request $request, GalleryItem $gallery)
    {
        $validated = $request->validate([
            'title'      => ['nullable', 'string', 'max:255'],
            'url'        => ['required', 'string', 'max:2000'],
            'type'       => ['required', 'in:video,photo'],
            'format'     => ['required', 'in:landscape,portrait,square'],
            'platform'   => ['required', 'in:youtube,facebook,instagram,other'],
            'is_active'  => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        $gallery->update($validated);

        return redirect()->route('admin.gallery.index')->with('success', 'Elemento actualizado.');
    }

    public function uploadThumbnail(Request $request, GalleryItem $gallery)
    {
        $request->validate(['thumbnail' => ['required', 'image', 'max:2048']]);

        if ($gallery->thumbnail && Storage::disk('public')->exists($gallery->thumbnail)) {
            Storage::disk('public')->delete($gallery->thumbnail);
        }

        $path = $request->file('thumbnail')->store('gallery', 'public');
        $gallery->update(['thumbnail' => $path]);

        return back()->with('success', 'Miniatura actualizada.');
    }

    public function deleteThumbnail(GalleryItem $gallery)
    {
        if ($gallery->thumbnail && Storage::disk('public')->exists($gallery->thumbnail)) {
            Storage::disk('public')->delete($gallery->thumbnail);
        }

        $gallery->update(['thumbnail' => null]);

        return back()->with('success', 'Miniatura eliminada.');
    }

    public function destroy(GalleryItem $gallery)
    {
        if ($gallery->thumbnail && Storage::disk('public')->exists($gallery->thumbnail)) {
            Storage::disk('public')->delete($gallery->thumbnail);
        }

        $gallery->delete();

        return redirect()->route('admin.gallery.index')->with('success', 'Elemento eliminado.');
    }
}
