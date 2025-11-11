<?php

namespace App\Support;

final class StatusRank
{
    /**
     * Returns the rank map for core borrow request statuses.
     * Higher means further in the lifecycle.
     */
    public static function map(): array
    {
        return [
            'pending' => 0,
            'validated' => 1,
            'approved' => 2,
            'return_pending' => 2.5,
            'returned' => 3,
            'rejected' => 3, // terminal
        ];
    }

    public static function rank(?string $status): float
    {
        $map = self::map();
        $key = is_string($status) ? strtolower($status) : '';
        return $map[$key] ?? -1;
    }

    public static function isDowngrade(?string $from, ?string $to): bool
    {
        $rFrom = self::rank($from);
        $rTo = self::rank($to);
        return $rTo >= 0 && $rFrom >= 0 && $rTo < $rFrom;
    }
}
