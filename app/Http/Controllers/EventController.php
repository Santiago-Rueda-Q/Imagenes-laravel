<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class EventController extends Controller
{
    /**
     * Display a listing of the events.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Event::query();

        // Filtros opcionales
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('upcoming')) {
            $query->upcoming();
        }

        if ($request->has('past')) {
            $query->past();
        }

        // Paginación
        $perPage = $request->input('per_page', 10);
        $events = $query->orderBy('event_date', 'desc')->paginate($perPage);

        // Agregar URLs de imágenes
        $events->getCollection()->transform(function ($event) {
            $event->image_small_url = $event->image_small_url;
            $event->image_medium_url = $event->image_medium_url;
            $event->image_large_url = $event->image_large_url;
            return $event;
        });

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    /**
     * Store a newly created event in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'event_date' => 'required|date',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $event = new Event();
            $event->title = $request->title;
            $event->description = $request->description;
            $event->location = $request->location;
            $event->event_date = $request->event_date;
            $event->color = $request->color;
            $event->is_active = $request->boolean('is_active', true);

            // Procesar imagen si se proporciona
            if ($request->hasFile('image')) {
                $imagePaths = $this->processImage($request->file('image'));
                $event->image_small = $imagePaths['small'];
                $event->image_medium = $imagePaths['medium'];
                $event->image_large = $imagePaths['large'];
            }

            $event->save();

            // Agregar URLs de imágenes para la respuesta
            $event->image_small_url = $event->image_small_url;
            $event->image_medium_url = $event->image_medium_url;
            $event->image_large_url = $event->image_large_url;

            return response()->json([
                'success' => true,
                'message' => 'Event created successfully',
                'data' => $event
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating event: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified event.
     */
    public function show(Event $event): JsonResponse
    {
        $event->image_small_url = $event->image_small_url;
        $event->image_medium_url = $event->image_medium_url;
        $event->image_large_url = $event->image_large_url;

        return response()->json([
            'success' => true,
            'data' => $event
        ]);
    }

    /**
     * Update the specified event in storage.
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'location' => 'sometimes|required|string|max:255',
            'event_date' => 'sometimes|required|date',
            'color' => 'sometimes|required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $event->fill($request->only([
                'title', 'description', 'location', 'event_date', 'color'
            ]));

            if ($request->has('is_active')) {
                $event->is_active = $request->boolean('is_active');
            }

            // Procesar nueva imagen si se proporciona
            if ($request->hasFile('image')) {
                // Eliminar imágenes anteriores
                $event->deleteImages();

                // Procesar nueva imagen
                $imagePaths = $this->processImage($request->file('image'));
                $event->image_small = $imagePaths['small'];
                $event->image_medium = $imagePaths['medium'];
                $event->image_large = $imagePaths['large'];
            }

            $event->save();

            // Agregar URLs de imágenes para la respuesta
            $event->image_small_url = $event->image_small_url;
            $event->image_medium_url = $event->image_medium_url;
            $event->image_large_url = $event->image_large_url;

            return response()->json([
                'success' => true,
                'message' => 'Event updated successfully',
                'data' => $event
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating event: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified event from storage.
     */
    public function destroy(Event $event): JsonResponse
    {
        try {
            // Eliminar imágenes del almacenamiento
            $event->deleteImages();

            // Eliminar el evento
            $event->delete();

            return response()->json([
                'success' => true,
                'message' => 'Event deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting event: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process uploaded image and create three sizes.
     */
    private function processImage($image): array
    {
        $fileName = time() . '_' . uniqid();
        $directory = 'events/images';

        // Crear directorio si no existe
        Storage::makeDirectory($directory);

        // Configurar Intervention Image con el driver GD
        $manager = new ImageManager(new Driver());

        // Procesar imagen pequeña (200x200)
        $smallPath = $directory . '/' . $fileName . '_small.webp';
        $img = $manager->read($image);
        $img->resize(200, 200, function ($constraint) {
            $constraint->aspectRatio();
        });
        Storage::put($smallPath, $img->toWebp(90));

        // Procesar imagen mediana (600x600)
        $mediumPath = $directory . '/' . $fileName . '_medium.webp';
        $img = $manager->read($image);
        $img->resize(600, 600, function ($constraint) {
            $constraint->aspectRatio();
        });
        Storage::put($mediumPath, $img->toWebp(90));

        // Procesar imagen grande (1200x1200)
        $largePath = $directory . '/' . $fileName . '_large.webp';
        $img = $manager->read($image);
        $img->resize(1200, 1200, function ($constraint) {
            $constraint->aspectRatio();
        });
        Storage::put($largePath, $img->toWebp(90));

        return [
            'small' => $smallPath,
            'medium' => $mediumPath,
            'large' => $largePath,
        ];
    }
}
