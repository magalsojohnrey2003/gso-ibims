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
            return;
        }

        $service = app(PropertyNumberService::class);
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

        $this->attributes['serial'] = $components['serial'];
        $this->attributes['serial_int'] = $components['serial_int'];
        $this->attributes['office_code'] = $components['office'];
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
                if (count($parts) === 4) {
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