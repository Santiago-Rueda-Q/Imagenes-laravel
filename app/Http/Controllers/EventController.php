<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    /**
     * Display a listing of the events.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Event::query();

            // Filtros opcionales
            if ($request->has('is_active') && $request->is_active !== null) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('upcoming') && $request->upcoming) {
                $query->upcoming();
            }

            if ($request->has('past') && $request->past) {
                $query->past();
            }

            // Paginación
            $perPage = $request->input('per_page', 10);
            $events = $query->orderBy('event_date', 'desc')->paginate($perPage);

            // Transformar los datos para incluir URLs de imágenes
            $transformedEvents = $events->through(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'location' => $event->location,
                    'event_date' => $event->event_date,
                    'color' => $event->color,
                    'is_active' => $event->is_active,
                    'image_small_url' => $event->image_small_url,
                    'image_medium_url' => $event->image_medium_url,
                    'image_large_url' => $event->image_large_url,
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedEvents,
                'message' => 'Eventos cargados exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error cargando eventos: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error cargando eventos: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
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

            // Procesar imagen
            if ($request->hasFile('image')) {
                $imagePaths = $this->processImage($request->file('image'));
                $event->image_small = $imagePaths['small'];
                $event->image_medium = $imagePaths['medium'];
                $event->image_large = $imagePaths['large'];
            }

            $event->save();

            // Preparar respuesta con URLs de imágenes
            $eventData = [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'location' => $event->location,
                'event_date' => $event->event_date,
                'color' => $event->color,
                'is_active' => $event->is_active,
                'image_small_url' => $event->image_small_url,
                'image_medium_url' => $event->image_medium_url,
                'image_large_url' => $event->image_large_url,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Evento creado exitosamente',
                'data' => $eventData
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creando evento: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creando evento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified event.
     */
    public function show(Event $event): JsonResponse
    {
        try {
            $eventData = [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'location' => $event->location,
                'event_date' => $event->event_date,
                'color' => $event->color,
                'is_active' => $event->is_active,
                'image_small_url' => $event->image_small_url,
                'image_medium_url' => $event->image_medium_url,
                'image_large_url' => $event->image_large_url,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $eventData
            ]);
        } catch (\Exception $e) {
            Log::error('Error showing event: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error loading event'
            ], 500);
        }
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
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
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

            // Preparar respuesta con URLs de imágenes
            $eventData = [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'location' => $event->location,
                'event_date' => $event->event_date,
                'color' => $event->color,
                'is_active' => $event->is_active,
                'image_small_url' => $event->image_small_url,
                'image_medium_url' => $event->image_medium_url,
                'image_large_url' => $event->image_large_url,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ];

            return response()->json([
                'success' => true,
                'message' => 'El evento se actualizó correctamente',
                'data' => $eventData
            ]);

        } catch (\Exception $e) {
            Log::error('Error de actualizar el evento: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error de actualizar el evento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified event from storage.
     */
    public function destroy(Event $event): JsonResponse
    {
        try {
            // Con soft deletes, esto solo marca como eliminado
            $event->delete();

            return response()->json([
                'success' => true,
                'message' => 'Evento eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error eliminado evento: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error eliminado evento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process uploaded image and create three sizes.
     */
    private function processImage($image): array
    {
        try {
            $fileName = time() . '_' . uniqid();
            $extension = $image->getClientOriginalExtension();
            $directory = 'events/images';

            // Usar el disco público para que las imágenes sean accesibles
            $originalPath = $directory . '/' . $fileName . '_original.' . $extension;

            // Guardar imagen usando el disco público
            $stored = Storage::disk('public')->putFileAs($directory, $image, $fileName . '_original.' . $extension);

            if (!$stored) {
                throw new \Exception('Failed to store image');
            }

            // Para simplificar, usar la misma imagen para todos los tamaños
            return [
                'small' => $stored,
                'medium' => $stored,
                'large' => $stored,
            ];

        } catch (\Exception $e) {
            Log::error('Error processing image: ' . $e->getMessage());
            throw new \Exception('Failed to process image: ' . $e->getMessage());
        }
    }

    // Métodos adicionales para filtros específicos
    public function filterActive(Request $request): JsonResponse
    {
        $request->merge(['is_active' => true]);
        return $this->index($request);
    }

    public function filterUpcoming(Request $request): JsonResponse
    {
        $request->merge(['upcoming' => true]);
        return $this->index($request);
    }

    public function filterPast(Request $request): JsonResponse
    {
        $request->merge(['past' => true]);
        return $this->index($request);
    }
}
