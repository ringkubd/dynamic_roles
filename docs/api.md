# API Reference

This document provides a comprehensive reference for all API endpoints provided by the Dynamic Roles Package.

## Base Configuration

All API routes are prefixed with the configured prefix (default: `/api/dynamic-roles`) and protected by authentication middleware.

**Default Middleware:** `['api', 'auth:sanctum']`
**Base URL:** `/api/dynamic-roles`

## URL Permission Management

### List URLs
**GET** `/urls`

List all registered URLs with their permissions.

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "url": "/api/users",
            "method": "GET",
            "name": "users.index",
            "permissions": ["users.view"],
            "category": "users"
        }
    ]
}
```

### Create URL
**POST** `/urls`

Register a new URL with permissions.

**Request Body:**
```json
{
    "url": "/api/users",
    "method": "GET",
    "permissions": ["users.view"],
    "name": "users.index",
    "category": "users"
}
```

### Check Permission
**POST** `/urls/check-permission`

Check if the current user has permission for a specific URL.

**Request Body:**
```json
{
    "url": "/api/users",
    "method": "GET"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "has_permission": true,
        "permissions": ["users.view"],
        "user_permissions": ["users.view", "users.create"]
    }
}
```

## Role Management

### List Roles
**GET** `/roles`

Get all available roles.

### Assign Role to User
**POST** `/users/assign-role`

Assign a role to a user.

**Request Body:**
```json
{
    "user_id": 1,
    "role": "admin"
}
```

### Get User Permissions
**GET** `/users/{id}/permissions`

Get all permissions for a specific user.

## Menu Management

### List Menus
**GET** `/menus`

Get all menu items.

### Create Menu
**POST** `/menus`

Create a new menu item.

**Request Body:**
```json
{
    "title": "Users",
    "url": "/users",
    "icon": "fa-users",
    "parent_id": null,
    "permissions": ["users.view"],
    "roles": ["admin", "manager"]
}
```

### Get Menu Tree
**GET** `/menus/tree`

Get hierarchical menu structure.

### Update Menu
**PUT** `/menus/{menu}`

Update an existing menu item.

### Delete Menu
**DELETE** `/menus/{menu}`

Delete a menu item.

### Get Breadcrumbs
**GET** `/menus/{menu}/breadcrumbs`

Get breadcrumb trail for a menu item.

### Reorder Menus
**POST** `/menus/reorder`

Reorder menu items.

## Public Endpoints

### URL Patterns
**GET** `/public/url-patterns`

Get URL patterns without authentication (useful for frontend route discovery).

## Response Format

All API responses follow this standard format:

### Success Response
```json
{
    "success": true,
    "data": { /* response data */ },
    "message": "Operation completed successfully",
    "meta": {
        "total": 100,
        "per_page": 15,
        "current_page": 1
    }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field": ["Validation error message"]
    },
    "error_code": "VALIDATION_FAILED"
}
```

## Authentication

All protected endpoints require authentication. The package supports:

- Laravel Sanctum (default)
- Laravel Passport
- Custom authentication drivers

Include the authentication token in the Authorization header:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

## Rate Limiting

API endpoints are subject to Laravel's default rate limiting. You can customize this in your application's route service provider.

## Error Codes

| Code | Description |
|------|-------------|
| `VALIDATION_FAILED` | Request validation failed |
| `UNAUTHORIZED` | Authentication required |
| `FORBIDDEN` | Insufficient permissions |
| `NOT_FOUND` | Resource not found |
| `SERVER_ERROR` | Internal server error |