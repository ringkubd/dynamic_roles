<?php

namespace Anwar\DynamicRoles\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PermissionCacheService
{
    protected string $cachePrefix;
    protected string $cacheDriver;
    protected int $cacheTtl;
    protected bool $cacheEnabled;
    protected array $cacheTags;

    public function __construct()
    {
        $this->cachePrefix = config('dynamic-roles.cache.prefix', 'dynamic_roles');
        $this->cacheDriver = config('dynamic-roles.cache.driver', 'redis');
        $this->cacheTtl = config('dynamic-roles.cache.ttl', 3600);
        $this->cacheEnabled = config('dynamic-roles.cache.enabled', true);
        $this->cacheTags = config('dynamic-roles.cache.tags', []);
    }

    /**
     * Get user permissions from cache.
     */
    public function getUserPermissions($userId): ?array
    {
        if (!$this->cacheEnabled) {
            return null;
        }

        $key = $this->getUserPermissionsCacheKey($userId);
        
        try {
            return $this->getCacheStore()->get($key);
        } catch (\Exception $e) {
            Log::warning('Failed to get user permissions from cache', [
                'user_id' => $userId,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache user permissions.
     */
    public function cacheUserPermissions($userId, array $permissions): bool
    {
        if (!$this->cacheEnabled) {
            return false;
        }

        $key = $this->getUserPermissionsCacheKey($userId);

        try {
            return $this->getCacheStore()->put($key, $permissions, $this->cacheTtl);
        } catch (\Exception $e) {
            Log::warning('Failed to cache user permissions', [
                'user_id' => $userId,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get URL permissions from cache.
     */
    public function getUrlPermissions(string $url, string $method): ?array
    {
        if (!$this->cacheEnabled) {
            return null;
        }

        $key = $this->getUrlPermissionsCacheKey($url, $method);

        try {
            return $this->getCacheStore()->get($key);
        } catch (\Exception $e) {
            Log::warning('Failed to get URL permissions from cache', [
                'url' => $url,
                'method' => $method,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache URL permissions.
     */
    public function cacheUrlPermissions(string $url, string $method, array $permissions): bool
    {
        if (!$this->cacheEnabled) {
            return false;
        }

        $key = $this->getUrlPermissionsCacheKey($url, $method);

        try {
            return $this->getCacheStore()->put($key, $permissions, $this->cacheTtl);
        } catch (\Exception $e) {
            Log::warning('Failed to cache URL permissions', [
                'url' => $url,
                'method' => $method,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get role permissions from cache.
     */
    public function getRolePermissions($roleId): ?array
    {
        if (!$this->cacheEnabled) {
            return null;
        }

        $key = $this->getRolePermissionsCacheKey($roleId);

        try {
            return $this->getCacheStore()->get($key);
        } catch (\Exception $e) {
            Log::warning('Failed to get role permissions from cache', [
                'role_id' => $roleId,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache role permissions.
     */
    public function cacheRolePermissions($roleId, array $permissions): bool
    {
        if (!$this->cacheEnabled) {
            return false;
        }

        $key = $this->getRolePermissionsCacheKey($roleId);

        try {
            return $this->getCacheStore()->put($key, $permissions, $this->cacheTtl);
        } catch (\Exception $e) {
            Log::warning('Failed to cache role permissions', [
                'role_id' => $roleId,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear all permission caches.
     */
    public function clearAll(): bool
    {
        if (!$this->cacheEnabled) {
            return true;
        }

        try {
            if ($this->supportsTagging()) {
                foreach ($this->cacheTags as $tag) {
                    $this->getCacheStore()->tags($tag)->flush();
                }
            } else {
                // Fallback: clear cache by pattern
                $this->clearByPattern($this->cachePrefix . ':*');
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear permission caches', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear user-specific caches.
     */
    public function clearUserCache($userId): bool
    {
        if (!$this->cacheEnabled) {
            return true;
        }

        try {
            $key = $this->getUserPermissionsCacheKey($userId);
            return $this->getCacheStore()->forget($key);
        } catch (\Exception $e) {
            Log::warning('Failed to clear user cache', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear role-specific caches.
     */
    public function clearRoleCache($roleId): bool
    {
        if (!$this->cacheEnabled) {
            return true;
        }

        try {
            $key = $this->getRolePermissionsCacheKey($roleId);
            return $this->getCacheStore()->forget($key);
        } catch (\Exception $e) {
            Log::warning('Failed to clear role cache', [
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear URL-specific caches.
     */
    public function clearUrlCache(string $url, string $method): bool
    {
        if (!$this->cacheEnabled) {
            return true;
        }

        try {
            $key = $this->getUrlPermissionsCacheKey($url, $method);
            return $this->getCacheStore()->forget($key);
        } catch (\Exception $e) {
            Log::warning('Failed to clear URL cache', [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generic cache remember method
     */
    public function remember(string $key, \Closure $callback, int $ttl = null): mixed
    {
        if (!$this->cacheEnabled) {
            return $callback();
        }

        $ttl = $ttl ?? $this->cacheTtl;
        $fullKey = $this->cachePrefix . ':' . $key;

        try {
            return $this->getCacheStore()->remember($fullKey, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Failed to remember cache', [
                'key' => $fullKey,
                'error' => $e->getMessage()
            ]);
            return $callback();
        }
    }

    /**
     * Clear cache by tag
     */
    public function clearByTag(string $tag): bool
    {
        if (!$this->cacheEnabled) {
            return true;
        }

        try {
            // Clear by pattern for now - in production you might want more sophisticated cache tagging
            $this->clearByPattern($this->cachePrefix . ':' . $tag . ':*');
            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to clear cache by tag', [
                'tag' => $tag,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get cache tag name
     */
    protected function getCacheTagName(string $tag): string
    {
        $tags = config('dynamic-roles.cache.tags', []);
        return $tags[$tag] ?? $this->cachePrefix . '_' . $tag;
    }

    /**
     * Get cache statistics.
     */
    public function getStats(): array
    {
        return [
            'enabled' => $this->cacheEnabled,
            'driver' => $this->cacheDriver,
            'prefix' => $this->cachePrefix,
            'ttl' => $this->cacheTtl,
            'tags_supported' => $this->supportsTagging(),
        ];
    }

    /**
     * Generate cache key for user permissions.
     */
    protected function getUserPermissionsCacheKey($userId): string
    {
        return "{$this->cachePrefix}:user:{$userId}:permissions";
    }

    /**
     * Generate cache key for URL permissions.
     */
    protected function getUrlPermissionsCacheKey(string $url, string $method): string
    {
        return "{$this->cachePrefix}:url:" . md5($url . ':' . $method) . ':permissions';
    }

    /**
     * Generate cache key for role permissions.
     */
    protected function getRolePermissionsCacheKey($roleId): string
    {
        return "{$this->cachePrefix}:role:{$roleId}:permissions";
    }

    /**
     * Get cache store instance.
     */
    protected function getCacheStore()
    {
        return Cache::store($this->cacheDriver);
    }

    /**
     * Check if the cache driver supports tagging.
     */
    protected function supportsTagging(): bool
    {
        return in_array($this->cacheDriver, ['redis', 'memcached']);
    }

    /**
     * Clear cache by pattern (fallback for drivers without tagging).
     */
    protected function clearByPattern(string $pattern): void
    {
        // This is a simplified implementation
        // In production, you might want to use Redis SCAN or similar
        if ($this->cacheDriver === 'redis' && function_exists('redis')) {
            $redis = app('redis')->connection();
            $keys = $redis->keys($pattern);
            if (!empty($keys)) {
                $redis->del($keys);
            }
        }
    }
}
