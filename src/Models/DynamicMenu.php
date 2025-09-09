<?php

namespace Anwar\DynamicRoles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DynamicMenu extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'url',
        'route_name',
        'description',
        'parent_id',
        'sort_order',
        'is_active',
        'is_visible',
        'target',
        'css_class',
        'menu_type',
        'metadata',
        'conditions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
        'conditions' => 'array',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the table name from config.
     */
    public function getTable(): string
    {
        return config('dynamic-roles.database.tables.dynamic_menus', 'dynamic_menus');
    }

    /**
     * Parent menu item.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Child menu items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * All descendants (recursive children).
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Permissions required for this menu.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('dynamic-roles.database.tables.dynamic_menu_permissions', 'dynamic_menu_permissions'),
            'dynamic_menu_id',
            'permission_id'
        )->withTimestamps();
    }

    /**
     * Roles that can access this menu.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.role'),
            config('dynamic-roles.database.tables.dynamic_menu_roles', 'dynamic_menu_roles'),
            'dynamic_menu_id',
            'role_id'
        )->withTimestamps();
    }

    /**
     * Scope for active menu items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for visible menu items.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope for top-level menu items.
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for menu items by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('menu_type', $type);
    }

    /**
     * Get menu tree structure.
     */
    public static function getMenuTree(string $type = 'main', bool $activeOnly = true): array
    {
        $query = self::with(['children.children.children']) // 3 levels deep
            ->topLevel()
            ->byType($type)
            ->orderBy('sort_order');

        if ($activeOnly) {
            $query->active()->visible();
        }

        return $query->get()->toArray();
    }

    /**
     * Check if user has access to this menu item.
     */
    public function userHasAccess($user): bool
    {
        if (!$user) {
            return false;
        }

        // Check if menu is active and visible
        if (!$this->is_active || !$this->is_visible) {
            return false;
        }

        // Check if user has super admin role
        $superAdminRole = config('dynamic-roles.security.super_admin_role', 'super-admin');
        if ($user->hasRole($superAdminRole)) {
            return true;
        }

        // Check custom conditions
        if (!empty($this->conditions) && !$this->evaluateConditions($user)) {
            return false;
        }

        // Check permissions
        $requiredPermissions = $this->permissions()->pluck('name')->toArray();
        if (!empty($requiredPermissions)) {
            foreach ($requiredPermissions as $permission) {
                if (!$user->hasPermissionTo($permission)) {
                    return false;
                }
            }
        }

        // Check role-based access
        $menuRoles = $this->roles()->pluck('id')->toArray();
        if (!empty($menuRoles)) {
            $userRoles = $user->roles()->pluck('id')->toArray();
            if (empty(array_intersect($userRoles, $menuRoles))) {
                return false;
            }
        }

        // If no specific permissions or roles are set, allow access
        if (empty($requiredPermissions) && empty($menuRoles)) {
            return true;
        }

        return true;
    }

    /**
     * Evaluate custom conditions for menu access.
     */
    protected function evaluateConditions($user): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            $type = $condition['type'] ?? '';
            $value = $condition['value'] ?? '';
            $operator = $condition['operator'] ?? '=';

            switch ($type) {
                case 'user_property':
                    $userValue = data_get($user, $value);
                    $expectedValue = $condition['expected'] ?? '';
                    
                    if (!$this->compareValues($userValue, $expectedValue, $operator)) {
                        return false;
                    }
                    break;

                case 'custom_callback':
                    if (is_callable($value)) {
                        if (!call_user_func($value, $user)) {
                            return false;
                        }
                    }
                    break;

                case 'date_range':
                    $start = $condition['start'] ?? null;
                    $end = $condition['end'] ?? null;
                    $now = now();
                    
                    if ($start && $now->lt($start)) {
                        return false;
                    }
                    if ($end && $now->gt($end)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Compare values based on operator.
     */
    protected function compareValues($actual, $expected, string $operator): bool
    {
        switch ($operator) {
            case '=':
            case '==':
                return $actual == $expected;
            case '!=':
                return $actual != $expected;
            case '>':
                return $actual > $expected;
            case '>=':
                return $actual >= $expected;
            case '<':
                return $actual < $expected;
            case '<=':
                return $actual <= $expected;
            case 'in':
                return in_array($actual, (array) $expected);
            case 'not_in':
                return !in_array($actual, (array) $expected);
            case 'contains':
                return str_contains((string) $actual, (string) $expected);
            case 'starts_with':
                return str_starts_with((string) $actual, (string) $expected);
            case 'ends_with':
                return str_ends_with((string) $actual, (string) $expected);
            default:
                return $actual == $expected;
        }
    }

    /**
     * Get breadcrumb trail for this menu item.
     */
    public function getBreadcrumb(): array
    {
        $breadcrumb = [];
        $current = $this;

        while ($current) {
            array_unshift($breadcrumb, [
                'id' => $current->id,
                'name' => $current->name,
                'url' => $current->url,
                'route_name' => $current->route_name,
            ]);
            $current = $current->parent;
        }

        return $breadcrumb;
    }

    /**
     * Generate menu HTML.
     */
    public function generateHtml(array $options = []): string
    {
        $tag = $options['tag'] ?? 'li';
        $linkTag = $options['link_tag'] ?? 'a';
        $cssClass = $this->css_class ? ' class="' . $this->css_class . '"' : '';
        $target = $this->target ? ' target="' . $this->target . '"' : '';
        $icon = $this->icon ? '<i class="' . $this->icon . '"></i> ' : '';

        $url = $this->route_name ? route($this->route_name) : $this->url;
        
        $html = "<{$tag}{$cssClass}>";
        $html .= "<{$linkTag} href=\"{$url}\"{$target}>";
        $html .= $icon . $this->name;
        $html .= "</{$linkTag}>";

        // Add children if any
        if ($this->children->isNotEmpty()) {
            $childrenHtml = '';
            foreach ($this->children as $child) {
                $childrenHtml .= $child->generateHtml($options);
            }
            $html .= "<ul>{$childrenHtml}</ul>";
        }

        $html .= "</{$tag}>";

        return $html;
    }

    /**
     * Get filtered menu for user.
     */
    public static function getFilteredMenuForUser($user, string $type = 'main'): array
    {
        $menus = self::getMenuTree($type, true);
        
        return self::filterMenuByAccess($menus, $user);
    }

    /**
     * Recursively filter menu items based on user access.
     */
    protected static function filterMenuByAccess(array $menus, $user): array
    {
        $filtered = [];

        foreach ($menus as $menu) {
            $menuItem = self::find($menu['id']);
            
            if ($menuItem && $menuItem->userHasAccess($user)) {
                $filteredMenu = $menu;
                
                // Filter children recursively
                if (!empty($menu['children'])) {
                    $filteredMenu['children'] = self::filterMenuByAccess($menu['children'], $user);
                }
                
                $filtered[] = $filteredMenu;
            }
        }

        return $filtered;
    }

    /**
     * Get menu cache key.
     */
    public function getCacheKey($userId = null): string
    {
        $key = sprintf(
            '%s:menu:%s:%s',
            config('dynamic-roles.cache.prefix', 'dynamic_roles'),
            $this->menu_type,
            $this->id
        );

        if ($userId) {
            $key .= ':user:' . $userId;
        }

        return $key;
    }
}
