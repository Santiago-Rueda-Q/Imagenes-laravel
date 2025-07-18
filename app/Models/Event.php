<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'description',
        'location',
        'event_date',
        'color',
        'image_small',
        'image_medium',
        'image_large',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'event_date' => 'date',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime', // Para soft deletes
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'image_small_url',
        'image_medium_url',
        'image_large_url',
    ];

    /**
     * Scope para eventos próximos
     */
    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', Carbon::today());
    }

    /**
     * Scope para eventos pasados
     */
    public function scopePast($query)
    {
        return $query->where('event_date', '<', Carbon::today());
    }

    /**
     * Scope para eventos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Accessor para URL de imagen pequeña
     */
    public function getImageSmallUrlAttribute()
    {
        if (!$this->image_small) {
            return null;
        }

        // Verificar si la imagen existe
        if (!Storage::disk('public')->exists($this->image_small)) {
            return null;
        }

        // CORRECCIÓN: Usar asset() en lugar de Storage::url()
        return asset('storage/' . $this->image_small);
    }

    /**
     * Accessor para URL de imagen mediana
     */
    public function getImageMediumUrlAttribute()
    {
        if (!$this->image_medium) {
            return null;
        }

        // Verificar si la imagen existe
        if (!Storage::disk('public')->exists($this->image_medium)) {
            return null;
        }

        // CORRECCIÓN: Usar asset() en lugar de Storage::url()
        return asset('storage/' . $this->image_medium);
    }

    /**
     * Accessor para URL de imagen grande
     */
    public function getImageLargeUrlAttribute()
    {
        if (!$this->image_large) {
            return null;
        }

        // Verificar si la imagen existe
        if (!Storage::disk('public')->exists($this->image_large)) {
            return null;
        }

        // CORRECCIÓN: Usar asset() en lugar de Storage::url()
        return asset('storage/' . $this->image_large);
    }

    /**
     * Eliminar imágenes del almacenamiento
     */
    public function deleteImages()
    {
        if ($this->image_small && Storage::disk('public')->exists($this->image_small)) {
            Storage::disk('public')->delete($this->image_small);
        }
        if ($this->image_medium && Storage::disk('public')->exists($this->image_medium)) {
            Storage::disk('public')->delete($this->image_medium);
        }
        if ($this->image_large && Storage::disk('public')->exists($this->image_large)) {
            Storage::disk('public')->delete($this->image_large);
        }
    }

    /**
     * Boot del modelo para manejar eventos
     */
    protected static function boot()
    {
        parent::boot();

        // Eliminar imágenes cuando se elimina el evento (soft delete)
        static::deleting(function ($event) {
            if ($event->isForceDeleting()) {
                $event->deleteImages();
            }
        });
    }
}
