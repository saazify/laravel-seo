<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Saazify\LaravelSeo\Local\OpeningHours;

class Location extends Model
{
    protected $table = 'seo_locations';

    protected $guarded = [];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'opening_hours' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Location $location): void {
            if (! $location->slug) {
                $location->slug = Str::slug((string) $location->name) ?: 'location';
            }
        });
    }

    /**
     * @param  array<string, mixed>  $fallback
     * @return array<string, mixed>
     */
    public function toOrganizationArray(array $fallback = []): array
    {
        return [
            'name' => $this->name ?: ($fallback['name'] ?? null),
            'type' => $this->business_type ?: ($fallback['type'] ?? 'LocalBusiness'),
            'email' => $this->email,
            'phone' => $this->phone,
            'url' => $this->url ?: ($fallback['url'] ?? null),
            'logo' => $this->logo ?: ($fallback['logo'] ?? null),
            'image' => $this->image ?: ($fallback['image'] ?? null),
            'price_range' => $this->price_range ?: ($fallback['price_range'] ?? null),
            'vat_id' => $this->vat_id ?: ($fallback['vat_id'] ?? null),
            'description' => $this->description ?: ($fallback['description'] ?? null),
            'address' => [
                'street' => $this->street,
                'city' => $this->city,
                'region' => $this->region,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
            ],
            'geo' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'opening_hours' => $this->opening_hours ?? [],
            'same_as' => $fallback['same_as'] ?? [],
        ];
    }

    public function formattedAddress(): string
    {
        return OpeningHours::formatAddress([
            $this->street,
            $this->city,
            $this->region,
            $this->postal_code,
            $this->country,
        ]);
    }

    public function isOpenAt(?\DateTimeInterface $moment = null): bool
    {
        return OpeningHours::isOpen($this->opening_hours ?? [], $moment ?? new \DateTimeImmutable);
    }

    public function mapUrl(): ?string
    {
        if (is_numeric($this->latitude) && is_numeric($this->longitude)) {
            return 'https://www.google.com/maps?q='.$this->latitude.','.$this->longitude;
        }

        $address = $this->formattedAddress();

        return $address !== '' ? 'https://www.google.com/maps?q='.rawurlencode($address) : null;
    }
}
