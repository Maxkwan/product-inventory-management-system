<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class InventoryCache
{
    public const TTL_SECONDS = 300;

    public static function key(string $resource, array $parameters = []): string
    {
        ksort($parameters);

        return sprintf('inventory:%s:v%d:%s', $resource, self::version(), sha1(serialize($parameters)));
    }

    public static function invalidate(): void
    {
        Cache::forever('inventory:version', self::version() + 1);
    }

    private static function version(): int
    {
        return (int) Cache::get('inventory:version', 1);
    }
}
