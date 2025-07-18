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
        return $this->image_small ? Storage::url($this->image_small) : null;
    }

    /**
     * Accessor para URL de imagen mediana
     */
    public function getImageMediumUrlAttribute()
    {
        return $this->image_medium ? Storage::url($this->image_medium) : null;
    }

    /**
     * Accessor para URL de imagen grande
     */
    public function getImageLargeUrlAttribute()
    {
        return $this->image_large ? Storage::url($this->image_large) : null;
    }

    /**
     * Eliminar imágenes del almacenamiento
     */
    public function deleteImages()
    {
        if ($this->image_small) {
            Storage::delete($this->image_small);
        }
        if ($this->image_medium) {
            Storage::delete($this->image_medium);
        }
        if ($this->image_large) {
            Storage::delete($this->image_large);
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
