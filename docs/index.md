# Home

Welcome to the **Dynamic Roles Package** documentation - your comprehensive Laravel package for dynamic role and permission management.

## About Dynamic Roles Package

A comprehensive Laravel package for dynamic role and permission management, featuring caching, API support, and database-driven URL management. Perfect for applications with complex permission requirements that need to be managed without touching code.

## Key Features

- 🚀 **Dynamic permission management** - Create and manage permissions without touching code
- 🎯 **URL-based access control** - Control access to specific URLs and HTTP methods  
- ⚡ **High-performance caching** - Configurable caching with Redis, Memcached, or other drivers
- 🌐 **API endpoints** - Full REST API for frontend integration (perfect for Next.js)
- 🛡️ **Middleware protection** - Ready-to-use middleware for route protection
- 📊 **Analytics** - Track permission checks and access patterns
- 🎨 **Flexible configuration** - Highly configurable to fit any application structure
- 🍔 **Menu management** - Create hierarchical menu systems with role/permission-based access
- 💾 **Import/export** - Backup and restore permission configurations
- 🔄 **Bulk operations** - Efficiently manage permissions for multiple entities

## Quick Start Guide

### Installation

1. **Install the package via Composer:**
   ```bash
   composer require anwar/dynamic-roles
   ```

2. **Install dependencies (Spatie Laravel Permission):**
   ```bash
   composer require spatie/laravel-permission
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   php artisan migrate
   ```

3. **Publish configuration and migrations:**
   ```bash
   # Publish configuration
   php artisan vendor:publish --tag=dynamic-roles-config
   
   # Publish migrations
   php artisan vendor:publish --tag=dynamic-roles-migrations
   
   # Run migrations
   php artisan migrate
   ```

### Usage Overview

#### 1. Register URLs
Use the auto-discovery feature or register URLs manually:
```bash
# Auto-discover and sync permissions
php artisan dynamic-roles:sync-permissions --auto-discover
```

#### 2. Use Middleware
Protect your routes with dynamic permissions:
```php
// In your routes/api.php
Route::middleware(['dynamic.permission'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
});

// With specific permissions
Route::get('/admin/users', [UserController::class, 'adminIndex'])
    ->middleware('dynamic.permission:admin.users.view');
```

#### 3. API Usage
The package provides REST API endpoints:
- `GET /api/dynamic-roles/urls` - List all URLs
- `POST /api/dynamic-roles/urls` - Create new URL
- `POST /api/dynamic-roles/urls/check-permission` - Check permission
- `GET /api/dynamic-roles/roles` - List all roles
- `POST /api/dynamic-roles/users/assign-role` - Assign role to user

### Configuration
Configure the package by editing `config/dynamic-roles.php` for:
- Cache settings (Redis, Memcached, etc.)
- Database table names
- API route configuration
- Security settings
- Auto-discovery patterns

## Further Resources

### Documentation
- [**README**](https://github.com/ringkubd/dynamic_roles/blob/main/README.md) - Complete documentation with detailed examples
- [Installation Guide](installation.md) - Detailed installation instructions
- [Usage Examples](usage.md) - Practical usage examples
- [API Reference](api.md) - Complete API documentation
- [Configuration](configuration.md) - Configuration options
- [Menu Management](menu.md) - Menu system documentation

### Future Wiki Topics
We plan to expand this wiki with additional topics including:

- **Advanced Caching** - Deep dive into caching strategies and performance optimization
- **Menu API** - Complete guide to the dynamic menu system
- **Security Best Practices** - Security considerations and best practices
- **Integration Examples** - Real-world integration scenarios
- **Troubleshooting** - Common issues and solutions
- **Performance Tuning** - Optimization tips for large-scale applications

### Support
For support and questions:
1. Check this documentation
2. Review the [README](https://github.com/ringkubd/dynamic_roles/blob/main/README.md)
3. Check Laravel logs (`storage/logs/laravel.log`)
4. Review package configuration (`config/dynamic-roles.php`)
5. Examine database tables and relationships

### Contributing
We welcome contributions! Please see our [contribution guidelines](https://github.com/ringkubd/dynamic_roles) for more information.

---

*This documentation is for the Dynamic Roles Package v1.0.0. For the latest updates and changes, please refer to the [Changelog](changelog.md).*