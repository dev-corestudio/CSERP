<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class OrderImageController extends Controller
{
    /**
     * Pobierz wszystkie zdjęcia zamówienia
     */
    public function index(Order $order)
    {
        $images = $order->images()
            ->orderBy('sort_order')
            ->get();
            
        return response()->json($images);
    }

    /**
     * Prześlij nowe zdjęcia
     */
    public function store(Request $request, Order $order)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'required|image|max:5120', // max 5MB
            'description' => 'nullable|string'
        ]);

        $uploadedImages = [];
        $maxSortOrder = $order->images()->max('sort_order') ?? 0;

        foreach ($request->file('images') as $index => $file) {
            // Generuj unikalną nazwę
            $filename = time() . '_' . $index . '.' . $file->getClientOriginalExtension();
            
            // Zapisz główne zdjęcie
            $path = $file->storeAs('orders/' . $order->id . '/images', $filename, 'public');
            
            // Wygeneruj thumbnail (200x200)
            $thumbnailPath = 'orders/' . $order->id . '/thumbnails/' . $filename;
            $thumbnail = Image::make($file)
                ->fit(200, 200)
                ->encode();
            Storage::disk('public')->put($thumbnailPath, $thumbnail);

            // Utwórz rekord w bazie
            $image = $order->images()->create([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'thumbnail_path' => $thumbnailPath,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'description' => $request->input('description'),
                'sort_order' => $maxSortOrder + $index + 1
            ]);

            $uploadedImages[] = $image;
        }

        return response()->json([
            'message' => 'Zdjęcia przesłane pomyślnie',
            'images' => $uploadedImages
        ], 201);
    }

    /**
     * Usuń zdjęcie
     */
    public function destroy(Order $order, OrderImage $image)
    {
        // Sprawdź czy zdjęcie należy do zamówienia
        if ($image->order_id !== $order->id) {
            return response()->json([
                'message' => 'Zdjęcie nie należy do tego zamówienia'
            ], 403);
        }

        // Usuń pliki z dysku
        Storage::disk('public')->delete($image->path);
        if ($image->thumbnail_path) {
            Storage::disk('public')->delete($image->thumbnail_path);
        }

        // Usuń rekord z bazy
        $image->delete();

        return response()->json([
            'message' => 'Zdjęcie usunięte pomyślnie'
        ]);
    }

    /**
     * Zaktualizuj kolejność zdjęć
     */
    public function updateOrder(Request $request, Order $order)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*.id' => 'required|exists:order_images,id',
            'images.*.sort_order' => 'required|integer'
        ]);

        foreach ($request->input('images') as $imageData) {
            OrderImage::where('id', $imageData['id'])
                ->where('order_id', $order->id)
                ->update(['sort_order' => $imageData['sort_order']]);
        }

        return response()->json([
            'message' => 'Kolejność zaktualizowana'
        ]);
    }
}
