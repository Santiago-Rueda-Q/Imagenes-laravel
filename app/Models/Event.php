<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'location',
        'event_date',
        'color',
        'image_small',
        'image_medium',
        'image_large',
        'is_active'
    ];

    protected $casts = [
        'event_date' => 'date',
        'is_active' => 'boolean',
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

    
}
