<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class City extends Model
{
    use HasFactory, HasUuids;

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'country',
        'country_code',
        'region',
        'state_province',
        'latitude',
        'longitude',
        'population',
        'is_capital',
        'is_major',
        'timezone',
        'slug',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'population' => 'integer',
            'is_capital' => 'boolean',
            'is_major' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the events for the city.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Scope to get only active cities.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only major cities.
     */
    public function scopeMajor($query)
    {
        return $query->where('is_major', true);
    }

    /**
     * Scope to filter by country.
     */
    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope to filter by region.
     */
    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Get formatted display name with country.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name.', '.$this->country;
    }

    /**
     * Generate slug on creation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($city) {
            if (empty($city->slug)) {
                $city->slug = Str::slug($city->name.'-'.$city->country_code);
            }
        });
    }
}
