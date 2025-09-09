# Changelog

All notable changes to the Dynamic Roles package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-09-09

### Added
- Initial release of the Dynamic Roles package
- Dynamic URL permission management system
- Role-based access control with Spatie Laravel Permission integration
- Comprehensive caching system with Redis/Memcached support
- Complete REST API for frontend integration
- Middleware for route protection (`DynamicPermissionMiddleware`, `DynamicRoleMiddleware`)
- Console commands for:
  - Syncing permissions (`sync:permissions`)
  - Clearing cache (`cache:clear-dynamic`)
  - Publishing configuration (`publish:dynamic-config`)
- Menu management system with hierarchical structure
- Breadcrumb generation for nested menus
- Tree structure support for unlimited menu depth
- Permission and role assignment for menus
- Auto-discovery of application routes
- Import/export functionality for permissions
- Bulk operations for efficient permission management
- Analytics and logging capabilities
- Highly configurable system with comprehensive config file
- Full test suite with PHPUnit
- Complete documentation with examples
- Installation script for easy setup

### Database Migrations
- `create_dynamic_urls_table` - Core URL storage
- `create_dynamic_url_permissions_table` - URL permission assignments
- `create_dynamic_role_urls_table` - Role-URL relationships
- `create_dynamic_permission_checks_table` - Permission check logging
- `create_dynamic_menus_table` - Menu system storage
- `create_dynamic_menu_permissions_table` - Menu permission assignments
- `create_dynamic_menu_roles_table` - Menu role assignments

### API Endpoints
- URL Permissions: Full CRUD operations (`/api/dynamic-roles/url-permissions`)
- Role Permissions: Assignment and management (`/api/dynamic-roles/role-permissions`)
- Menu Management: CRUD, tree, breadcrumbs, reordering (`/api/dynamic-roles/menus`)

### Services
- `UrlPermissionService` - Core permission management
- `RolePermissionService` - Role assignment and checking
- `MenuService` - Menu CRUD, tree operations, user-specific menus
- `PermissionCacheService` - Centralized caching with tagging

### Models
- `DynamicUrl` - URL storage with validation
- `DynamicPermissionCheck` - Permission check logging
- `DynamicMenu` - Hierarchical menu structure with relationships

### Configuration
- Comprehensive configuration file (`config/dynamic-roles.php`)
- Cache settings (driver, TTL, tags)
- Database table name customization
- API route prefix and middleware configuration
- Security settings and discovery options

### Documentation
- Complete README with installation and usage
- Installation summary with feature overview
- Detailed usage examples with code samples
- Integration examples for popular frameworks
- API documentation with endpoint details

### Package Structure
- PSR-4 autoloading with `Anwar\DynamicRoles` namespace
- Follows Laravel package development best practices
- Compatible with Laravel 9.x, 10.x, and 11.x
- PHP 8.0+ requirement
- Comprehensive test coverage

### Security
- URL validation and sanitization
- HTTP method validation
- Permission caching with security considerations
- Role-based access control integration
- Middleware protection for all endpoints
