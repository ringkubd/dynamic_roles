#!/bin/bash

# Dynamic Roles Package Installation Script
# This script helps integrate the Dynamic Roles package into your Laravel application

echo "🚀 Dynamic Roles Package Installation Script"
echo "============================================="

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    echo "❌ Error: This script must be run from the root of a Laravel project"
    exit 1
fi

echo "✅ Laravel project detected"

# Check if Spatie Laravel Permission is installed
if ! composer show spatie/laravel-permission > /dev/null 2>&1; then
    echo "📦 Installing Spatie Laravel Permission..."
    composer require spatie/laravel-permission
    
    echo "📄 Publishing Spatie permission migrations..."
    php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
    
    echo "🗃️ Running Spatie permission migrations..."
    php artisan migrate --path=database/migrations/*_create_permission_tables.php
else
    echo "✅ Spatie Laravel Permission already installed"
fi

# Add the package to composer.json if not already added
if ! grep -q "gunma/dynamic-roles" composer.json; then
    echo "📝 Adding package repository to composer.json..."
    
    # Create backup
    cp composer.json composer.json.backup
    
    # Add repository
    php -r "
    \$composer = json_decode(file_get_contents('composer.json'), true);
    if (!isset(\$composer['repositories'])) {
        \$composer['repositories'] = [];
    }
    \$composer['repositories'][] = [
        'type' => 'path',
        'url' => './packages/dynamic-roles'
    ];
    \$composer['require']['gunma/dynamic-roles'] = '*';
    file_put_contents('composer.json', json_encode(\$composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    "
    
    echo "🔄 Running composer install..."
    composer install
else
    echo "✅ Package already added to composer.json"
fi

# Publish configuration
echo "📄 Publishing Dynamic Roles configuration..."
php artisan vendor:publish --tag=dynamic-roles-config --force

# Publish migrations
echo "📄 Publishing Dynamic Roles migrations..."
php artisan vendor:publish --tag=dynamic-roles-migrations --force

# Run migrations
echo "🗃️ Running Dynamic Roles migrations..."
php artisan migrate

# Add HasRoles trait to User model if not already added
USER_MODEL="app/Models/User.php"
if [ -f "$USER_MODEL" ]; then
    if ! grep -q "use HasRoles" "$USER_MODEL"; then
        echo "🔧 Adding HasRoles trait to User model..."
        
        # Create backup
        cp "$USER_MODEL" "$USER_MODEL.backup"
        
        # Add the trait
        php -r "
        \$content = file_get_contents('$USER_MODEL');
        
        // Add use statement
        if (!str_contains(\$content, 'use Spatie\Permission\Traits\HasRoles;')) {
            \$content = str_replace(
                'use Illuminate\Foundation\Auth\User as Authenticatable;',
                \"use Illuminate\Foundation\Auth\User as Authenticatable;\nuse Spatie\Permission\Traits\HasRoles;\",
                \$content
            );
        }
        
        // Add trait usage
        if (!str_contains(\$content, 'use HasRoles;')) {
            \$content = preg_replace(
                '/class User extends Authenticatable\s*{/',
                \"class User extends Authenticatable\n{\n    use HasRoles;\",
                \$content
            );
        }
        
        file_put_contents('$USER_MODEL', \$content);
        "
        
        echo "✅ HasRoles trait added to User model"
    else
        echo "✅ HasRoles trait already exists in User model"
    fi
else
    echo "⚠️  Warning: User model not found at $USER_MODEL"
    echo "   Please manually add 'use HasRoles;' trait to your User model"
fi

# Create environment variables
ENV_FILE=".env"
if [ -f "$ENV_FILE" ]; then
    echo "🔧 Adding environment variables..."
    
    # Check and add each variable if not exists
    declare -A env_vars=(
        ["DYNAMIC_ROLES_CACHE_ENABLED"]="true"
        ["DYNAMIC_ROLES_CACHE_DRIVER"]="redis"
        ["DYNAMIC_ROLES_CACHE_PREFIX"]="dynamic_roles"
        ["DYNAMIC_ROLES_CACHE_TTL"]="3600"
        ["DYNAMIC_ROLES_ENABLE_API"]="true"
        ["DYNAMIC_ROLES_API_PREFIX"]="api/dynamic-roles"
        ["DYNAMIC_ROLES_AUTO_DISCOVERY"]="true"
        ["DYNAMIC_ROLES_AUTO_REGISTER_URLS"]="true"
        ["DYNAMIC_ROLES_SUPER_ADMIN"]="super-admin"
        ["DYNAMIC_ROLES_BYPASS_PERMISSIONS"]="false"
        ["DYNAMIC_ROLES_LOG_CHECKS"]="false"
    )
    
    for var in "${!env_vars[@]}"; do
        if ! grep -q "^$var=" "$ENV_FILE"; then
            echo "$var=${env_vars[$var]}" >> "$ENV_FILE"
            echo "   Added: $var=${env_vars[$var]}"
        fi
    done
else
    echo "⚠️  Warning: .env file not found"
fi

# Create a super admin user (optional)
echo ""
read -p "🔐 Would you like to create a super admin user? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "📝 Creating super admin user..."
    
    php artisan tinker --execute="
    \$user = App\Models\User::firstOrCreate(
        ['email' => 'admin@example.com'],
        [
            'name' => 'Super Admin',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]
    );
    
    if (!\Spatie\Permission\Models\Role::where('name', 'super-admin')->exists()) {
        \Spatie\Permission\Models\Role::create(['name' => 'super-admin']);
    }
    
    \$user->assignRole('super-admin');
    
    echo \"Super admin created:\n\";
    echo \"Email: admin@example.com\n\";
    echo \"Password: password\n\";
    echo \"Role: super-admin\n\";
    "
fi

# Auto-discover routes
echo ""
read -p "🔍 Would you like to auto-discover existing routes? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "🔍 Auto-discovering routes..."
    php artisan dynamic-roles:sync-permissions --auto-discover
fi

# Clear application cache
echo "🧹 Clearing application cache..."
php artisan config:clear
php artisan route:clear
php artisan cache:clear

echo ""
echo "✅ Installation completed successfully!"
echo ""
echo "📋 Next Steps:"
echo "1. Review the configuration at config/dynamic-roles.php"
echo "2. Check your .env file for new variables"
echo "3. Test the middleware by adding it to your routes"
echo "4. Use the API endpoints for frontend integration"
echo ""
echo "📚 Documentation:"
echo "   - Package README: packages/dynamic-roles/README.md"
echo "   - API endpoints: /api/dynamic-roles/*"
echo "   - Artisan commands: php artisan dynamic-roles:*"
echo ""
echo "🎉 Happy coding!"
