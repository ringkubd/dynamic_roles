# Next.js Quick Start

Get up and running with Dynamic Roles Package in your Next.js application.

## Prerequisites

- Next.js 13+ project
- Laravel backend with Dynamic Roles Package installed
- API authentication set up (Sanctum recommended)

## Installation

No additional installation is required for the frontend - you'll interact with the package through API calls.

## Basic Setup

### 1. Configure API Base URL

Create an environment variable for your API base URL:

```env
# .env.local
NEXT_PUBLIC_API_URL=http://localhost:8000/api/dynamic-roles
```

### 2. Create API Client

```typescript
// lib/api.ts
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL;

export class DynamicRolesAPI {
  private token: string | null = null;

  setToken(token: string) {
    this.token = token;
  }

  private async request(endpoint: string, options: RequestInit = {}) {
    const url = `${API_BASE_URL}${endpoint}`;
    const headers = {
      'Content-Type': 'application/json',
      ...(this.token && { Authorization: `Bearer ${this.token}` }),
      ...options.headers,
    };

    const response = await fetch(url, { ...options, headers });
    
    if (!response.ok) {
      throw new Error(`API Error: ${response.statusText}`);
    }
    
    return response.json();
  }

  // Menu methods
  async getMenuTree() {
    return this.request('/menus/tree');
  }

  async getMenus() {
    return this.request('/menus');
  }

  // Permission methods
  async checkPermission(url: string, method: string = 'GET') {
    return this.request('/urls/check-permission', {
      method: 'POST',
      body: JSON.stringify({ url, method }),
    });
  }

  // URL methods
  async getUrls() {
    return this.request('/urls');
  }

  async createUrl(data: any) {
    return this.request('/urls', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }
}

export const api = new DynamicRolesAPI();
```

### 3. Create React Context

```typescript
// contexts/DynamicRolesContext.tsx
'use client';

import { createContext, useContext, useEffect, useState } from 'react';
import { api } from '@/lib/api';

interface DynamicRolesContextType {
  menuTree: any[];
  userPermissions: string[];
  checkPermission: (url: string, method?: string) => Promise<boolean>;
  loading: boolean;
}

const DynamicRolesContext = createContext<DynamicRolesContextType | null>(null);

export function DynamicRolesProvider({ 
  children, 
  token 
}: { 
  children: React.ReactNode;
  token: string;
}) {
  const [menuTree, setMenuTree] = useState([]);
  const [userPermissions, setUserPermissions] = useState<string[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (token) {
      api.setToken(token);
      loadData();
    }
  }, [token]);

  const loadData = async () => {
    try {
      const [menuResponse] = await Promise.all([
        api.getMenuTree(),
      ]);
      
      setMenuTree(menuResponse.data || []);
    } catch (error) {
      console.error('Failed to load dynamic roles data:', error);
    } finally {
      setLoading(false);
    }
  };

  const checkPermission = async (url: string, method: string = 'GET') => {
    try {
      const response = await api.checkPermission(url, method);
      return response.data?.has_permission || false;
    } catch (error) {
      console.error('Permission check failed:', error);
      return false;
    }
  };

  return (
    <DynamicRolesContext.Provider value={{
      menuTree,
      userPermissions,
      checkPermission,
      loading,
    }}>
      {children}
    </DynamicRolesContext.Provider>
  );
}

export function useDynamicRoles() {
  const context = useContext(DynamicRolesContext);
  if (!context) {
    throw new Error('useDynamicRoles must be used within DynamicRolesProvider');
  }
  return context;
}
```

### 4. Create Navigation Component

```tsx
// components/Navigation.tsx
'use client';

import Link from 'next/link';
import { useDynamicRoles } from '@/contexts/DynamicRolesContext';

interface MenuItem {
  id: number;
  title: string;
  url: string;
  icon?: string;
  children: MenuItem[];
}

function NavigationItem({ item }: { item: MenuItem }) {
  return (
    <li>
      <Link href={item.url} className="flex items-center p-2 hover:bg-gray-100">
        {item.icon && <i className={`${item.icon} mr-2`} />}
        {item.title}
      </Link>
      {item.children.length > 0 && (
        <ul className="ml-4">
          {item.children.map((child) => (
            <NavigationItem key={child.id} item={child} />
          ))}
        </ul>
      )}
    </li>
  );
}

export function Navigation() {
  const { menuTree, loading } = useDynamicRoles();

  if (loading) {
    return <div>Loading navigation...</div>;
  }

  return (
    <nav className="bg-white shadow">
      <ul>
        {menuTree.map((item) => (
          <NavigationItem key={item.id} item={item} />
        ))}
      </ul>
    </nav>
  );
}
```

### 5. Use in Your App

```tsx
// app/layout.tsx
import { DynamicRolesProvider } from '@/contexts/DynamicRolesContext';
import { getServerSession } from 'next-auth';

export default async function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const session = await getServerSession();
  
  return (
    <html lang="en">
      <body>
        {session?.accessToken && (
          <DynamicRolesProvider token={session.accessToken}>
            {children}
          </DynamicRolesProvider>
        )}
        {!session?.accessToken && children}
      </body>
    </html>
  );
}
```

### 6. Protected Route Component

```tsx
// components/ProtectedRoute.tsx
'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useDynamicRoles } from '@/contexts/DynamicRolesContext';

interface ProtectedRouteProps {
  children: React.ReactNode;
  url: string;
  method?: string;
  fallback?: React.ReactNode;
}

export function ProtectedRoute({ 
  children, 
  url, 
  method = 'GET', 
  fallback = <div>Access denied</div> 
}: ProtectedRouteProps) {
  const { checkPermission } = useDynamicRoles();
  const [hasPermission, setHasPermission] = useState<boolean | null>(null);

  useEffect(() => {
    checkPermission(url, method).then(setHasPermission);
  }, [url, method, checkPermission]);

  if (hasPermission === null) {
    return <div>Checking permissions...</div>;
  }

  return hasPermission ? <>{children}</> : <>{fallback}</>;
}
```

## Usage Example

```tsx
// app/admin/users/page.tsx
import { ProtectedRoute } from '@/components/ProtectedRoute';

export default function UsersPage() {
  return (
    <ProtectedRoute url="/admin/users" method="GET">
      <div>
        <h1>User Management</h1>
        {/* Your user management content */}
      </div>
    </ProtectedRoute>
  );
}
```

## Next Steps

- [React Hooks](hooks.md) - Learn about custom hooks for common operations
- [Example Usage](examples.md) - See more practical examples
- [Project Complete](project_complete.md) - Complete integration guide