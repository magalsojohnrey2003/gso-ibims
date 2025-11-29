<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ManpowerRequest extends Model
{
    use HasFactory;

    protected $appends = [
        'formatted_request_id',
        'role_breakdown',
        'has_multiple_roles',
        'total_requested_quantity',
    ];

    protected $fillable = [
        'user_id',
        'quantity',
        'role',
        'purpose',
        'location',
        'office_agency',
        'start_at',
        'end_at',
        'letter_path',
        'status',
        'rejection_reason_subject',
        'rejection_reason_detail',
        'approved_quantity',
        'manpower_role_id',
        'public_token',
        'municipality',
        'barangay',
        'qr_verified_form_path',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function roleType()
    {
        return $this->belongsTo(ManpowerRole::class, 'manpower_role_id');
    }

    public function roles()
    {
        return $this->hasMany(ManpowerRequestRole::class);
    }

    public function getLetterUrlAttribute(): ?string
    {
        return $this->resolvePublicDiskUrl($this->letter_path);
    }

    public function getQrVerifiedFormUrlAttribute(): ?string
    {
        return $this->resolvePublicDiskUrl($this->qr_verified_form_path);
    }

    public function getPublicStatusUrlAttribute(): ?string
    {
        if (! $this->public_token) return null;
        try {
            return route('manpower.requests.public.show', $this->public_token);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getFormattedRequestIdAttribute(): ?string
    {
        $id = $this->getAttribute('id');
        if (!$id) {
            return null;
        }
        return sprintf('MP-%04d', (int) $id);
    }

    public function getRoleBreakdownAttribute(): array
    {
        $roles = $this->resolvedRoles();
        if ($roles->isEmpty()) {
            return [[
                'role_id' => $this->manpower_role_id,
                'role_name' => $this->role,
                'quantity' => (int) $this->quantity,
                'approved_quantity' => $this->approved_quantity,
            ]];
        }

        return $roles->map(static function (ManpowerRequestRole $role) {
            return [
                'role_id' => $role->manpower_role_id,
                'role_name' => $role->role_name,
                'quantity' => (int) $role->quantity,
                'approved_quantity' => $role->approved_quantity,
            ];
        })->values()->all();
    }

    public function getHasMultipleRolesAttribute(): bool
    {
        return $this->resolvedRoles()->count() > 1;
    }

    public function getTotalRequestedQuantityAttribute(): int
    {
        $roles = $this->resolvedRoles();
        if ($roles->isEmpty()) {
            return (int) $this->quantity;
        }

        return (int) $roles->sum('quantity');
    }

    public function buildRoleSummary(int $limit = 2): string
    {
        $roles = $this->resolvedRoles();
        if ($roles->isEmpty()) {
            return trim((string) ($this->role ?? ''));
        }

        $ordered = $roles->map(static function (ManpowerRequestRole $role) {
            return sprintf('%s (%d)', $role->role_name ?? 'Role', (int) $role->quantity);
        });

        if ($ordered->count() <= $limit) {
            return $ordered->implode(', ');
        }

        $leading = $ordered->take($limit)->implode(', ');
        $remaining = $ordered->count() - $limit;
        return sprintf('%s, +%d more', $leading, $remaining);
    }

    private function resolvedRoles(): Collection
    {
        $this->loadMissing('roles');
        $roles = $this->getRelation('roles');
        return $roles instanceof Collection ? $roles : collect();
    }

    private function resolvePublicDiskUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = null;
        try {
            $disk = Storage::disk('public');
        } catch (\Throwable $e) {
            $disk = null;
        }

        $candidate = null;
        if ($disk && $disk->exists($path)) {
            try {
                $candidate = $disk->url($path);
            } catch (\Throwable $e) {
                $candidate = null;
            }
        }

        if (! $candidate && filter_var($path, FILTER_VALIDATE_URL)) {
            $candidate = $path;
        }

        $request = null;
        try {
            $request = request();
        } catch (\Throwable $e) {
            $request = null;
        }

        if ($candidate && filter_var($candidate, FILTER_VALIDATE_URL)) {
            if ($request) {
                $parsed = parse_url($candidate) ?: [];
                $port = $request->getPort();
                $needsPort = empty($parsed['port']) && ! in_array($port, [null, 80, 443], true);
                if ($needsPort) {
                    $scheme = $request->getScheme();
                    $host = $request->getHost();
                    $pathPart = $parsed['path'] ?? '';
                    $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
                    return sprintf('%s://%s:%d%s%s', $scheme, $host, $port, $pathPart, $query);
                }
            }

            return $candidate;
        }

        $relative = $candidate ?: '/storage/' . ltrim($path, '/');
        if ($relative && $relative[0] !== '/') {
            $relative = '/' . ltrim($relative, '/');
        }

        if ($request) {
            return rtrim($request->getSchemeAndHttpHost(), '/') . $relative;
        }

        return $relative;
    }
}
