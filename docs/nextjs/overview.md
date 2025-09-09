# Next.js Client Overview

The Dynamic Roles Package provides excellent support for Next.js applications through its comprehensive REST API and TypeScript support.

## Features for Next.js

- **Full REST API**: Complete API endpoints for all package functionality
- **TypeScript Support**: Type definitions for better development experience
- **React Hooks**: Custom hooks for easy integration
- **SSR Compatible**: Works with Next.js server-side rendering
- **Authentication**: Seamless integration with NextAuth.js and other auth providers

## API Integration

The package exposes REST API endpoints that work perfectly with Next.js fetch, Axios, or any HTTP client:

```typescript
// Example API usage in Next.js
const response = await fetch('/api/dynamic-roles/menus/tree', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
});

const menuData = await response.json();
```

## Documentation Structure

- [Quick Start](quick_start.md) - Get started with Next.js integration
- [React Hooks](hooks.md) - Custom hooks for common operations
- [Example Usage](examples.md) - Practical implementation examples
- [Project Complete](project_complete.md) - Complete integration guide

## Benefits

### Type Safety
Full TypeScript support ensures type-safe API interactions.

### Performance
Built-in caching and optimized API endpoints provide excellent performance.

### Developer Experience
Custom React hooks make integration straightforward and intuitive.

### Flexibility
Works with any Next.js architecture - SSR, SSG, or client-side rendering.