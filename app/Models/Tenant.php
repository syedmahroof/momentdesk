<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'logo_light_path',
        'logo_dark_path',
        'status',
        'settings',
    ];

    protected $appends = [
        'logo_light_url',
        'logo_dark_url',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    protected function logoLightUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->logo_light_path ? Storage::disk('public')->url($this->logo_light_path) : null);
    }

    protected function logoDarkUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->logo_dark_path ? Storage::disk('public')->url($this->logo_dark_path) : null);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    public function flyerTemplates(): HasMany
    {
        return $this->hasMany(FlyerTemplate::class);
    }

    public function flyers(): HasMany
    {
        return $this->hasMany(Flyer::class);
    }
}
