# Menu Management

The Dynamic Roles Package includes a comprehensive menu management system that allows you to create hierarchical menu structures with role and permission-based access control.

## Overview

The menu system provides:

- **Hierarchical Structure**: Create nested menus with unlimited depth
- **Permission Control**: Restrict menu visibility based on user permissions
- **Role-Based Access**: Control access using user roles
- **Icon Support**: Multiple icon libraries (FontAwesome, Feather, Heroicons, Material)
- **Breadcrumb Generation**: Automatic breadcrumb trails
- **Caching**: High-performance caching for menu structures
- **API Integration**: Full REST API for frontend frameworks

## Menu Configuration

Configure menu behavior in `config/dynamic-roles.php`:

```php
'menu' => [
    'enabled' => true,
    'cache_enabled' => true,
    'cache_ttl' => 1800, // 30 minutes
    'max_depth' => 5,
    'auto_permissions' => true, // Auto create permissions for menu items
    'icons' => [
        'supported_libraries' => ['fontawesome', 'feather', 'heroicons', 'material'],
        'default_library' => 'fontawesome',
    ],
],
```

## Creating Menus

### Via API

Create a menu item using the REST API:

```bash
POST /api/dynamic-roles/menus
```

**Request Body:**
```json
{
    "title": "User Management",
    "url": "/admin/users",
    "icon": "fa-users",
    "parent_id": null,
    "order": 1,
    "permissions": ["users.view"],
    "roles": ["admin", "manager"],
    "is_active": true,
    "target": "_self",
    "description": "Manage system users"
}
```

### Via Code

Create menus programmatically:

```php
use Anwar\DynamicRoles\Models\DynamicMenu;

$menu = DynamicMenu::create([
    'title' => 'Dashboard',
    'url' => '/admin/dashboard',
    'icon' => 'fa-dashboard',
    'order' => 1,
    'is_active' => true,
]);

// Assign permissions
$menu->permissions()->attach(['dashboard.view']);

// Assign roles
$menu->roles()->attach(['admin', 'manager']);
```

## Menu Structure

### Parent-Child Relationships

Create nested menu structures:

```php
// Parent menu
$adminMenu = DynamicMenu::create([
    'title' => 'Administration',
    'url' => '#',
    'icon' => 'fa-cog',
    'order' => 1,
]);

// Child menus
$userMenu = DynamicMenu::create([
    'title' => 'Users',
    'url' => '/admin/users',
    'icon' => 'fa-users',
    'parent_id' => $adminMenu->id,
    'order' => 1,
]);

$roleMenu = DynamicMenu::create([
    'title' => 'Roles',
    'url' => '/admin/roles',
    'icon' => 'fa-shield',
    'parent_id' => $adminMenu->id,
    'order' => 2,
]);
```

### Menu Tree Structure

Get the complete menu tree:

```php
$menuTree = app(\Anwar\DynamicRoles\Services\MenuService::class)->getMenuTree();
```

Example tree structure:
```json
[
    {
        "id": 1,
        "title": "Administration",
        "url": "#",
        "icon": "fa-cog",
        "children": [
            {
                "id": 2,
                "title": "Users",
                "url": "/admin/users",
                "icon": "fa-users",
                "children": []
            },
            {
                "id": 3,
                "title": "Roles",
                "url": "/admin/roles",
                "icon": "fa-shield",
                "children": []
            }
        ]
    }
]
```

## Icon Libraries

### FontAwesome (Default)

```php
'icon' => 'fa-users'          // FontAwesome 4
'icon' => 'fas fa-users'      // FontAwesome 5+
```

### Feather Icons

```php
'icon' => 'feather-users'
```

### Heroicons

```php
'icon' => 'heroicon-users'
'icon' => 'heroicon-outline-users'
'icon' => 'heroicon-solid-users'
```

### Material Icons

```php
'icon' => 'material-people'
'icon' => 'material-outline-people'
```

## Permission Control

### Menu Visibility

Menus are automatically filtered based on user permissions:

```php
// Only users with 'users.view' permission will see this menu
$menu = DynamicMenu::create([
    'title' => 'Users',
    'url' => '/users',
    'permissions' => ['users.view'],
]);
```

### Role-Based Access

Restrict menus by user roles:

```php
$menu = DynamicMenu::create([
    'title' => 'Admin Panel',
    'url' => '/admin',
    'roles' => ['admin', 'super-admin'],
]);
```

### Combined Permissions and Roles

Use both permissions and roles (OR logic):

```php
$menu = DynamicMenu::create([
    'title' => 'Reports',
    'url' => '/reports',
    'permissions' => ['reports.view'],
    'roles' => ['manager', 'admin'],
]);
// User needs EITHER the permission OR one of the roles
```

## API Endpoints

### List All Menus

```bash
GET /api/dynamic-roles/menus
```

### Get Menu Tree

```bash
GET /api/dynamic-roles/menus/tree
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Dashboard",
            "url": "/dashboard",
            "icon": "fa-dashboard",
            "children": []
        }
    ]
}
```

### Create Menu

```bash
POST /api/dynamic-roles/menus
```

### Update Menu

```bash
PUT /api/dynamic-roles/menus/{id}
```

### Delete Menu

```bash
DELETE /api/dynamic-roles/menus/{id}
```

### Get Breadcrumbs

```bash
GET /api/dynamic-roles/menus/{id}/breadcrumbs
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Administration",
            "url": "/admin"
        },
        {
            "id": 2,
            "title": "Users",
            "url": "/admin/users"
        }
    ]
}
```

### Reorder Menus

```bash
POST /api/dynamic-roles/menus/reorder
```

**Request Body:**
```json
{
    "items": [
        {"id": 1, "order": 1, "parent_id": null},
        {"id": 2, "order": 2, "parent_id": null},
        {"id": 3, "order": 1, "parent_id": 1}
    ]
}
```

## Frontend Integration

### Vue.js Example

```vue
<template>
  <nav>
    <menu-item 
      v-for="item in menuItems" 
      :key="item.id" 
      :item="item" 
    />
  </nav>
</template>

<script>
export default {
  data() {
    return {
      menuItems: []
    }
  },
  async mounted() {
    const response = await fetch('/api/dynamic-roles/menus/tree');
    this.menuItems = await response.json();
  }
}
</script>
```

### React Example

```jsx
import { useEffect, useState } from 'react';

function Navigation() {
  const [menuItems, setMenuItems] = useState([]);

  useEffect(() => {
    fetch('/api/dynamic-roles/menus/tree')
      .then(response => response.json())
      .then(data => setMenuItems(data.data));
  }, []);

  return (
    <nav>
      {menuItems.map(item => (
        <MenuItem key={item.id} item={item} />
      ))}
    </nav>
  );
}
```

## Caching

Menu data is automatically cached for performance:

- **Cache Key**: `dynamic_roles_menus_{user_id}`
- **TTL**: Configurable (default: 30 minutes)
- **Tags**: `dynamic_roles_menus`

Clear menu cache:

```bash
php artisan dynamic-roles:clear-cache
```

Or programmatically:

```php
use Anwar\DynamicRoles\Services\MenuService;

app(MenuService::class)->clearCache();
```

## Best Practices

### Menu Organization

1. **Use meaningful titles**: Clear, descriptive menu titles
2. **Logical grouping**: Group related functionality
3. **Consistent icons**: Use consistent icon styles
4. **Proper ordering**: Order menus logically

### Performance

1. **Enable caching**: Always enable menu caching in production
2. **Limit depth**: Avoid excessive nesting (max 3-4 levels)
3. **Batch operations**: Use bulk operations for large menu structures

### Security

1. **Principle of least privilege**: Only grant necessary permissions
2. **Regular audits**: Review menu permissions regularly
3. **Test access**: Verify menu visibility with different user roles

### Maintenance

1. **Document structure**: Document your menu hierarchy
2. **Version control**: Track menu changes
3. **Cleanup**: Remove unused menu items regularly