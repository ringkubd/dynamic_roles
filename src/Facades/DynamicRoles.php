<?php

namespace Anwar\DynamicRoles\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool checkUrlPermission($user, string $url, string $method = 'GET')
 * @method static \Ringkubd\DynamicRoles\Models\DynamicUrl registerUrl(string $url, string $method = 'GET', array $permissions = [], array $options = [])
 * @method static array autoDiscoverRoutes()
 * @method static \Ringkubd\DynamicRoles\Models\DynamicUrl|null findMatchingUrl(string $url, string $method)
 * @method static void assignPermissionsToUrl(\Ringkubd\DynamicRoles\Models\DynamicUrl $url, array $permissions)
 * @method static void assignRolesToUrl(\Ringkubd\DynamicRoles\Models\DynamicUrl $url, array $roles)
 * @method static \Illuminate\Contracts\Pagination\LengthAwarePaginator getAllUrls(array $filters = [])
 * @method static bool deleteUrl(int $urlId)
 *
 * @see \Gunma\DynamicRoles\Services\UrlPermissionService
 */
class DynamicRoles extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'dynamic-roles.url';
    }
}
