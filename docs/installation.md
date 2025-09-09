# Installation

This guide provides detailed installation instructions for the Dynamic Roles Package.

## Prerequisites

- PHP 8.2 or higher
- Laravel 10.x or higher
- Composer

## Step-by-Step Installation

### 1. Install the Package

```bash
composer require anwar/dynamic-roles
```

### 2. Install Required Dependencies

The package requires Spatie Laravel Permission package:

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

### 3. Publish Package Assets

```bash
# Publish configuration
php artisan vendor:publish --tag=dynamic-roles-config

# Publish migrations
php artisan vendor:publish --tag=dynamic-roles-migrations

# Run migrations
php artisan migrate
```

### 4. Add Traits to User Model

Update your User model to include the necessary traits:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    // ... rest of your model
}
```

### 5. Configure Environment Variables

Add these variables to your `.env` file:

```env
# Cache Configuration
DYNAMIC_ROLES_CACHE_ENABLED=true
DYNAMIC_ROLES_CACHE_DRIVER=redis
DYNAMIC_ROLES_CACHE_PREFIX=dynamic_roles
DYNAMIC_ROLES_CACHE_TTL=3600

# API Configuration
DYNAMIC_ROLES_ENABLE_API=true
DYNAMIC_ROLES_API_PREFIX=api/dynamic-roles

# Auto-Discovery
DYNAMIC_ROLES_AUTO_DISCOVERY=true
DYNAMIC_ROLES_AUTO_REGISTER_URLS=true

# Security
DYNAMIC_ROLES_SUPER_ADMIN=super-admin
DYNAMIC_ROLES_BYPASS_PERMISSIONS=false
DYNAMIC_ROLES_LOG_CHECKS=false
```

### 6. Initial Setup

Run the sync command to discover and register routes:

```bash
php artisan dynamic-roles:sync-permissions --auto-discover --clear-cache
```

## Next Steps

After installation, you can:

1. Configure permissions in `config/dynamic-roles.php`
2. Set up your middleware protection
3. Configure API endpoints
4. Start using the package features

See the [Usage](usage.md) section for detailed examples.