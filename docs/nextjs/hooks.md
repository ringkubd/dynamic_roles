# React Hooks

Custom React hooks for seamless integration with the Dynamic Roles Package.

## Core Hooks

### useDynamicRoles

Main hook for accessing Dynamic Roles functionality.

```typescript
import { useDynamicRoles } from '@/contexts/DynamicRolesContext';

function MyComponent() {
  const { 
    menuTree, 
    userPermissions, 
    checkPermission, 
    loading 
  } = useDynamicRoles();

  return (
    <div>
      {loading ? 'Loading...' : 'Ready'}
    </div>
  );
}
```

### usePermissionCheck

Hook for checking specific permissions.

```typescript
// hooks/usePermissionCheck.ts
import { useState, useEffect } from 'react';
import { useDynamicRoles } from '@/contexts/DynamicRolesContext';

export function usePermissionCheck(url: string, method: string = 'GET') {
  const { checkPermission } = useDynamicRoles();
  const [hasPermission, setHasPermission] = useState<boolean | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let mounted = true;

    const check = async () => {
      try {
        const result = await checkPermission(url, method);
        if (mounted) {
          setHasPermission(result);
        }
      } catch (error) {
        if (mounted) {
          setHasPermission(false);
        }
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    };

    check();

    return () => {
      mounted = false;
    };
  }, [url, method, checkPermission]);

  return { hasPermission, loading };
}
```

**Usage:**

```tsx
function ProtectedComponent() {
  const { hasPermission, loading } = usePermissionCheck('/api/users', 'GET');

  if (loading) return <div>Checking permissions...</div>;
  if (!hasPermission) return <div>Access denied</div>;

  return <div>Protected content</div>;
}
```

### useMenuTree

Hook for accessing and filtering menu tree.

```typescript
// hooks/useMenuTree.ts
import { useMemo } from 'react';
import { useDynamicRoles } from '@/contexts/DynamicRolesContext';

interface UseMenuTreeOptions {
  maxDepth?: number;
  filterEmpty?: boolean;
}

export function useMenuTree(options: UseMenuTreeOptions = {}) {
  const { menuTree, loading } = useDynamicRoles();
  const { maxDepth = Infinity, filterEmpty = true } = options;

  const filteredTree = useMemo(() => {
    const filterTree = (items: any[], currentDepth = 0): any[] => {
      if (currentDepth >= maxDepth) return [];

      return items
        .map(item => ({
          ...item,
          children: filterTree(item.children || [], currentDepth + 1)
        }))
        .filter(item => !filterEmpty || item.children.length > 0 || item.url !== '#');
    };

    return filterTree(menuTree);
  }, [menuTree, maxDepth, filterEmpty]);

  return { menuTree: filteredTree, loading };
}
```

**Usage:**

```tsx
function Navigation() {
  const { menuTree, loading } = useMenuTree({ 
    maxDepth: 3, 
    filterEmpty: true 
  });

  if (loading) return <div>Loading menu...</div>;

  return (
    <nav>
      {menuTree.map(item => (
        <MenuItem key={item.id} item={item} />
      ))}
    </nav>
  );
}
```

### useApi

Hook for making API calls to Dynamic Roles endpoints.

```typescript
// hooks/useApi.ts
import { useState, useCallback } from 'react';
import { api } from '@/lib/api';

export function useApi() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const execute = useCallback(async (apiCall: () => Promise<any>) => {
    setLoading(true);
    setError(null);
    
    try {
      const result = await apiCall();
      return result;
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'An error occurred';
      setError(errorMessage);
      throw err;
    } finally {
      setLoading(false);
    }
  }, []);

  const createUrl = useCallback(async (data: any) => {
    return execute(() => api.createUrl(data));
  }, [execute]);

  const getUrls = useCallback(async () => {
    return execute(() => api.getUrls());
  }, [execute]);

  const checkPermission = useCallback(async (url: string, method: string = 'GET') => {
    return execute(() => api.checkPermission(url, method));
  }, [execute]);

  return {
    loading,
    error,
    createUrl,
    getUrls,
    checkPermission,
    execute
  };
}
```

**Usage:**

```tsx
function UrlManager() {
  const { createUrl, getUrls, loading, error } = useApi();
  const [urls, setUrls] = useState([]);

  const handleCreateUrl = async (data: any) => {
    try {
      await createUrl(data);
      const updatedUrls = await getUrls();
      setUrls(updatedUrls.data);
    } catch (error) {
      console.error('Failed to create URL:', error);
    }
  };

  return (
    <div>
      {loading && <div>Loading...</div>}
      {error && <div>Error: {error}</div>}
      {/* Your UI here */}
    </div>
  );
}
```

## Advanced Hooks

### useRoleCheck

Hook for checking user roles.

```typescript
// hooks/useRoleCheck.ts
import { useState, useEffect } from 'react';
import { api } from '@/lib/api';

export function useRoleCheck(userId: number, roles: string[]) {
  const [hasRole, setHasRole] = useState<boolean | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const checkRoles = async () => {
      try {
        // Implement role checking logic
        // This would depend on your specific API endpoints
        const userRoles = await api.getUserRoles(userId);
        const hasAnyRole = roles.some(role => userRoles.includes(role));
        setHasRole(hasAnyRole);
      } catch (error) {
        setHasRole(false);
      } finally {
        setLoading(false);
      }
    };

    checkRoles();
  }, [userId, roles]);

  return { hasRole, loading };
}
```

### useCachedPermissions

Hook for caching permission checks to improve performance.

```typescript
// hooks/useCachedPermissions.ts
import { useState, useCallback, useRef } from 'react';
import { useDynamicRoles } from '@/contexts/DynamicRolesContext';

export function useCachedPermissions() {
  const { checkPermission } = useDynamicRoles();
  const cacheRef = useRef<Map<string, boolean>>(new Map());
  const [loading, setLoading] = useState(false);

  const getCachedPermission = useCallback(async (url: string, method: string = 'GET') => {
    const key = `${method}:${url}`;
    
    if (cacheRef.current.has(key)) {
      return cacheRef.current.get(key)!;
    }

    setLoading(true);
    try {
      const result = await checkPermission(url, method);
      cacheRef.current.set(key, result);
      return result;
    } finally {
      setLoading(false);
    }
  }, [checkPermission]);

  const clearCache = useCallback(() => {
    cacheRef.current.clear();
  }, []);

  return {
    checkPermission: getCachedPermission,
    clearCache,
    loading
  };
}
```

### useMenuBreadcrumbs

Hook for generating breadcrumbs from menu structure.

```typescript
// hooks/useMenuBreadcrumbs.ts
import { useMemo } from 'react';
import { useDynamicRoles } from '@/contexts/DynamicRolesContext';

export function useMenuBreadcrumbs(currentPath: string) {
  const { menuTree } = useDynamicRoles();

  const breadcrumbs = useMemo(() => {
    const findPath = (items: any[], path: string, ancestors: any[] = []): any[] => {
      for (const item of items) {
        const currentAncestors = [...ancestors, item];
        
        if (item.url === path) {
          return currentAncestors;
        }
        
        if (item.children?.length > 0) {
          const result = findPath(item.children, path, currentAncestors);
          if (result.length > 0) {
            return result;
          }
        }
      }
      return [];
    };

    return findPath(menuTree, currentPath);
  }, [menuTree, currentPath]);

  return breadcrumbs;
}
```

**Usage:**

```tsx
import { usePathname } from 'next/navigation';

function Breadcrumbs() {
  const pathname = usePathname();
  const breadcrumbs = useMenuBreadcrumbs(pathname);

  return (
    <nav aria-label="Breadcrumb">
      <ol className="flex space-x-2">
        {breadcrumbs.map((crumb, index) => (
          <li key={crumb.id} className="flex items-center">
            {index > 0 && <span className="mx-2">/</span>}
            <Link href={crumb.url}>{crumb.title}</Link>
          </li>
        ))}
      </ol>
    </nav>
  );
}
```

## TypeScript Types

Define types for better type safety:

```typescript
// types/dynamic-roles.ts
export interface MenuItem {
  id: number;
  title: string;
  url: string;
  icon?: string;
  order: number;
  is_active: boolean;
  parent_id?: number;
  children: MenuItem[];
  permissions: string[];
  roles: string[];
}

export interface UrlPermission {
  id: number;
  url: string;
  method: string;
  name?: string;
  permissions: string[];
  category?: string;
}

export interface PermissionCheck {
  has_permission: boolean;
  permissions: string[];
  user_permissions: string[];
}

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
  meta?: {
    total: number;
    per_page: number;
    current_page: number;
  };
}
```

These hooks provide a comprehensive foundation for integrating the Dynamic Roles Package with your Next.js application, offering both simplicity for basic use cases and flexibility for advanced scenarios.