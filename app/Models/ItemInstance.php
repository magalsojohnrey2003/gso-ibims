<?php

namespace App\Models;

use App\Services\PropertyNumberService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class ItemInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'property_number',
        'year_procured',
        'category_code',
        'gla',
        'category_id',
        'serial',
        'serial_int',
        'office_code',
        'status',
        'notes',
    ];

    protected $casts = [
        'year_procured' => 'integer',
        'serial_int' => 'integer',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function borrowRecords(): HasMany
    {
        return $this->hasMany(BorrowItemInstance::class, 'item_instance_id');
    }

    public function returnRecords(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'item_instance_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ItemInstanceEvent::class);
    }

    public function setPropertyNumberAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['property_number'] = null;
            $this->attributes['year_procured'] = null;
            $this->attributes['category_code'] = null;
            $this->attributes['gla'] = null;
            $this->attributes['serial'] = null;
            $this->attributes['serial_int'] = null;
            $this->attributes['office_code'] = null;
            return;
        }

        $service = app(PropertyNumberService::class);

        try {
            $components = $service->parse($value);

            $this->attributes['property_number'] = $components['property_number'];
            $this->attributes['year_procured'] = (int) $components['year'];

            // Store category-derived code (1-4 uppercase alnum)
            $category = isset($components['category']) ? strtoupper($components['category']) : (isset($components['category_code']) ? strtoupper($components['category_code']) : null);
            if ($category !== null) {
                $category = preg_replace('/[^A-Z0-9]/', '', $category);
                $this->attributes['category_code'] = $category !== '' ? substr($category, 0, 4) : null;
            } else {
                $this->attributes['category_code'] = null;
            }

            // Store GLA (digits 1-4)
            $this->attributes['gla'] = isset($components['gla']) ? (string) $components['gla'] : null;

            $this->attributes['serial'] = $components['serial'];
            $this->attributes['serial_int'] = $components['serial_int'];
            $this->attributes['office_code'] = $components['office'];
        } catch (InvalidArgumentException $e) {
            // Fallback: try to set as much as possible without blocking Eloquent hydration.
            // Avoid throwing here — keep raw value and best-effort splits.
            $raw = trim((string) $value);
            $this->attributes['property_number'] = $raw;
            // Attempt lightweight extraction using regex to avoid full parse failing
            if (preg_match('/(\d{4})-([A-Z0-9]{1,4})-([0-9]{1,4})-([A-Za-z0-9]+)-([A-Za-z0-9]{1,4})/', strtoupper($raw), $m)) {
                $this->attributes['year_procured'] = isset($m[1]) ? (int) $m[1] : null;
                $this->attributes['category_code'] = isset($m[2]) ? substr(preg_replace('/[^A-Z0-9]/', '', $m[2]), 0, 4) : null;
                $this->attributes['gla'] = isset($m[3]) ? $m[3] : null;
                $this->attributes['serial'] = isset($m[4]) ? $m[4] : null;
                $this->attributes['serial_int'] = isset($m[4]) && ctype_digit($m[4]) ? (int) ltrim($m[4], '0') : null;
                $this->attributes['office_code'] = isset($m[5]) ? $m[5] : null;
            } else {
                // best effort: don't populate segments
                $this->attributes['year_procured'] = $this->attributes['year_procured'] ?? null;
                $this->attributes['category_code'] = $this->attributes['category_code'] ?? null;
                $this->attributes['gla'] = $this->attributes['gla'] ?? null;
                $this->attributes['serial'] = $this->attributes['serial'] ?? null;
                $this->attributes['serial_int'] = $this->attributes['serial_int'] ?? null;
                $this->attributes['office_code'] = $this->attributes['office_code'] ?? null;
            }
        }
    }

    public function scopeSearchProperty(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $numeric = preg_replace('/\D+/', '', $term);
        $hasHyphen = str_contains($term, '-');

        return $query->where(function (Builder $inner) use ($term, $numeric, $hasHyphen) {
            if ($hasHyphen) {
                $normalized = preg_replace('/\s+/', '', $term);
                $parts = array_values(array_filter(explode('-', $normalized), fn ($part) => $part !== ''));
                if (count($parts) >= 4) {
                    $service = app(PropertyNumberService::class);
                    try {
                        $components = $service->parse($normalized);
                        $inner->orWhere('property_number', $components['property_number']);
                    } catch (InvalidArgumentException $e) {
                        // fall through to LIKE matching below
                    }
                }

                if (! empty($parts)) {
                    $pattern = '%' . implode('%', $parts) . '%';
                    $inner->orWhere('property_number', 'like', $pattern);
                }
            }

            if ($numeric !== '') {
                $inner->orWhere(function (Builder $sub) use ($numeric) {
                    $sub->where('serial', $numeric)
                        ->orWhere('serial_int', (int) $numeric)
                        ->orWhere('property_number', 'like', '%' . $numeric . '%');
                });
            }

            $inner->orWhere('property_number', 'like', '%' . $term . '%')
                  ->orWhere('office_code', 'like', '%' . $term . '%');
        });
    }
}