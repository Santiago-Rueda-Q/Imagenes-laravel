<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory;

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

    protected $casts = [
        'event_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Accessor para obtener las URLs completas de las imágenes
    public function getImageSmallUrlAttribute()
    {
        return $this->image_small ? Storage::url($this->image_small) : null;
    }

    public function getImageMediumUrlAttribute()
    {
        return $this->image_medium ? Storage::url($this->image_medium) : null;
    }

    public function getImageLargeUrlAttribute()
    {
        return $this->image_large ? Storage::url($this->image_large) : null;
    }

    // Método para eliminar imágenes del almacenamiento
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

    // Scopes para filtrar eventos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now());
    }

    public function scopePast($query)
    {
        return $query->where('event_date', '<', now());
    }
}
