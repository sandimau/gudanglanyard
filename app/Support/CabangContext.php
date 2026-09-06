<?php

namespace App\Support;

class CabangContext
{
    protected static ?int $override = null;

    public static function set(?int $cabangId): void
    {
        self::$override = $cabangId ? (int) $cabangId : null;
    }

    public static function get(): ?int
    {
        return self::$override;
    }

    public static function clear(): void
    {
        self::$override = null;
    }

    public static function run(?int $cabangId, callable $callback)
    {
        $previous = self::$override;
        self::set($cabangId);

        try {
            return $callback();
        } finally {
            self::$override = $previous;
        }
    }
}
