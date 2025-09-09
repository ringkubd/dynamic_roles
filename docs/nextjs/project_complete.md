# Next.js Project Complete Integration Guide

This comprehensive guide covers complete integration of the Dynamic Roles Package with a Next.js application.

## Project Setup

### 1. Project Structure

```
nextjs-app/
├── app/
│   ├── admin/
│   │   ├── layout.tsx
│   │   ├── page.tsx
│   │   ├── users/
│   │   ├── roles/
│   │   ├── permissions/
│   │   └── menus/
│   ├── api/
│   │   └── auth/
│   ├── globals.css
│   └── layout.tsx
├── components/
│   ├── ui/
│   ├── Navigation.tsx
│   ├── Breadcrumbs.tsx
│   ├── ProtectedRoute.tsx
│   └── MenuManager.tsx
├── contexts/
│   └── DynamicRolesContext.tsx
├── hooks/
│   ├── useApi.ts
│   ├── usePermissionCheck.ts
│   ├── useMenuTree.ts
│   └── useCachedPermissions.ts
├── lib/
│   ├── api.ts
│   ├── auth.ts
│   └── dynamic-roles-server.ts
├── types/
│   └── dynamic-roles.ts
└── middleware.ts
```

### 2. Environment Configuration

```env
# .env.local
NEXTAUTH_URL=http://localhost:3000
NEXTAUTH_SECRET=your-secret-key

# Backend API
NEXT_PUBLIC_API_URL=http://localhost:8000
NEXT_PUBLIC_DYNAMIC_ROLES_API=http://localhost:8000/api/dynamic-roles

# Laravel Sanctum
NEXT_PUBLIC_SANCTUM_STATEFUL_DOMAINS=localhost:3000
```

### 3. Package Dependencies

```json
{
  "dependencies": {
    "next": "^14.0.0",
    "react": "^18.0.0",
    "react-dom": "^18.0.0",
    "next-auth": "^4.24.0",
    "axios": "^1.6.0",
    "react-beautiful-dnd": "^13.1.1",
    "react-hook-form": "^7.47.0",
    "@hookform/resolvers": "^3.3.0",
    "zod": "^3.22.0",
    "tailwindcss": "^3.3.0"
  },
  "devDependencies": {
    "@types/node": "^20.0.0",
    "@types/react": "^18.0.0",
    "@types/react-dom": "^18.0.0",
    "typescript": "^5.0.0"
  }
}
```

## Complete Implementation

### 1. TypeScript Definitions

```typescript
// types/dynamic-roles.ts
export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at?: string;
  roles: Role[];
  permissions: Permission[];
  created_at: string;
  updated_at: string;
}

export interface Role {
  id: number;
  name: string;
  guard_name: string;
  permissions: Permission[];
  created_at: string;
  updated_at: string;
}

export interface Permission {
  id: number;
  name: string;
  guard_name: string;
  created_at: string;
  updated_at: string;
}

export interface MenuItem {
  id: number;
  title: string;
  url: string;
  icon?: string;
  order: number;
  is_active: boolean;
  target: string;
  description?: string;
  parent_id?: number;
  children: MenuItem[];
  permissions: Permission[];
  roles: Role[];
  created_at: string;
  updated_at: string;
}

export interface UrlPermission {
  id: number;
  url: string;
  method: string;
  name?: string;
  category?: string;
  permissions: Permission[];
  created_at: string;
  updated_at: string;
}

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
  meta?: {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
    from: number;
    to: number;
  };
  errors?: Record<string, string[]>;
}

export interface PermissionCheckResponse {
  has_permission: boolean;
  permissions: string[];
  user_permissions: string[];
  matched_permissions: string[];
}
```

### 2. API Client

```typescript
// lib/api.ts
import axios, { AxiosInstance, AxiosRequestConfig } from 'axios';
import { getSession } from 'next-auth/react';

class DynamicRolesAPI {
  private client: AxiosInstance;

  constructor() {
    this.client = axios.create({
      baseURL: process.env.NEXT_PUBLIC_DYNAMIC_ROLES_API,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    // Request interceptor to add auth token
    this.client.interceptors.request.use(async (config) => {
      const session = await getSession();
      if (session?.accessToken) {
        config.headers.Authorization = `Bearer ${session.accessToken}`;
      }
      return config;
    });

    // Response interceptor for error handling
    this.client.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          // Handle unauthorized access
          window.location.href = '/auth/signin';
        }
        return Promise.reject(error);
      }
    );
  }

  // Menu endpoints
  async getMenuTree(): Promise<ApiResponse<MenuItem[]>> {
    const response = await this.client.get('/menus/tree');
    return response.data;
  }

  async getMenus(): Promise<ApiResponse<MenuItem[]>> {
    const response = await this.client.get('/menus');
    return response.data;
  }

  async createMenu(data: Partial<MenuItem>): Promise<ApiResponse<MenuItem>> {
    const response = await this.client.post('/menus', data);
    return response.data;
  }

  async updateMenu(id: number, data: Partial<MenuItem>): Promise<ApiResponse<MenuItem>> {
    const response = await this.client.put(`/menus/${id}`, data);
    return response.data;
  }

  async deleteMenu(id: number): Promise<ApiResponse<null>> {
    const response = await this.client.delete(`/menus/${id}`);
    return response.data;
  }

  async reorderMenus(items: Array<{ id: number; order: number; parent_id?: number }>): Promise<ApiResponse<null>> {
    const response = await this.client.post('/menus/reorder', { items });
    return response.data;
  }

  async getMenuBreadcrumbs(id: number): Promise<ApiResponse<MenuItem[]>> {
    const response = await this.client.get(`/menus/${id}/breadcrumbs`);
    return response.data;
  }

  // URL Permission endpoints
  async getUrls(): Promise<ApiResponse<UrlPermission[]>> {
    const response = await this.client.get('/urls');
    return response.data;
  }

  async createUrl(data: Partial<UrlPermission>): Promise<ApiResponse<UrlPermission>> {
    const response = await this.client.post('/urls', data);
    return response.data;
  }

  async updateUrl(id: number, data: Partial<UrlPermission>): Promise<ApiResponse<UrlPermission>> {
    const response = await this.client.put(`/urls/${id}`, data);
    return response.data;
  }

  async deleteUrl(id: number): Promise<ApiResponse<null>> {
    const response = await this.client.delete(`/urls/${id}`);
    return response.data;
  }

  async checkPermission(url: string, method: string = 'GET'): Promise<ApiResponse<PermissionCheckResponse>> {
    const response = await this.client.post('/urls/check-permission', { url, method });
    return response.data;
  }

  // Role and Permission endpoints
  async getRoles(): Promise<ApiResponse<Role[]>> {
    const response = await this.client.get('/roles');
    return response.data;
  }

  async getPermissions(): Promise<ApiResponse<Permission[]>> {
    const response = await this.client.get('/permissions');
    return response.data;
  }

  async assignRole(userId: number, roleId: number): Promise<ApiResponse<null>> {
    const response = await this.client.post('/users/assign-role', { user_id: userId, role_id: roleId });
    return response.data;
  }

  async removeRole(userId: number, roleId: number): Promise<ApiResponse<null>> {
    const response = await this.client.post('/users/remove-role', { user_id: userId, role_id: roleId });
    return response.data;
  }

  async getUserPermissions(userId: number): Promise<ApiResponse<Permission[]>> {
    const response = await this.client.get(`/users/${userId}/permissions`);
    return response.data;
  }

  // Bulk operations
  async syncPermissions(): Promise<ApiResponse<null>> {
    const response = await this.client.post('/sync-permissions');
    return response.data;
  }

  async clearCache(): Promise<ApiResponse<null>> {
    const response = await this.client.post('/clear-cache');
    return response.data;
  }
}

export const api = new DynamicRolesAPI();
```

### 3. Server-Side Permission Checking

```typescript
// lib/dynamic-roles-server.ts
import { getServerSession } from 'next-auth';
import { authOptions } from '@/lib/auth';

export async function checkServerPermission(
  req: any,
  res: any,
  url: string,
  method: string = 'GET'
): Promise<boolean> {
  const session = await getServerSession(req, res, authOptions);
  
  if (!session?.accessToken) {
    return false;
  }

  try {
    const response = await fetch(`${process.env.NEXT_PUBLIC_DYNAMIC_ROLES_API}/urls/check-permission`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${session.accessToken}`,
      },
      body: JSON.stringify({ url, method }),
    });

    if (!response.ok) {
      return false;
    }

    const data = await response.json();
    return data.data?.has_permission || false;
  } catch (error) {
    console.error('Server permission check failed:', error);
    return false;
  }
}

export async function getServerMenuTree(req: any, res: any): Promise<MenuItem[]> {
  const session = await getServerSession(req, res, authOptions);
  
  if (!session?.accessToken) {
    return [];
  }

  try {
    const response = await fetch(`${process.env.NEXT_PUBLIC_DYNAMIC_ROLES_API}/menus/tree`, {
      headers: {
        'Authorization': `Bearer ${session.accessToken}`,
      },
    });

    if (!response.ok) {
      return [];
    }

    const data = await response.json();
    return data.data || [];
  } catch (error) {
    console.error('Server menu tree fetch failed:', error);
    return [];
  }
}
```

### 4. Middleware for Route Protection

```typescript
// middleware.ts
import { withAuth } from 'next-auth/middleware';
import { NextResponse } from 'next/server';

export default withAuth(
  async function middleware(req) {
    const token = req.nextauth.token;
    const pathname = req.nextUrl.pathname;

    // Check if accessing admin routes
    if (pathname.startsWith('/admin')) {
      if (!token) {
        const url = new URL('/auth/signin', req.url);
        url.searchParams.set('callbackUrl', pathname);
        return NextResponse.redirect(url);
      }

      // Additional permission checking can be done here
      // For now, we'll let the client-side components handle it
    }

    return NextResponse.next();
  },
  {
    callbacks: {
      authorized: ({ token, req }) => {
        const { pathname } = req.nextUrl;
        
        // Allow access to auth pages
        if (pathname.startsWith('/auth')) return true;
        
        // Require token for admin pages
        if (pathname.startsWith('/admin')) return !!token;
        
        // Allow access to public pages
        return true;
      },
    },
  }
);

export const config = {
  matcher: ['/admin/:path*', '/auth/:path*']
};
```

### 5. Complete Admin Dashboard

```tsx
// app/admin/layout.tsx
import { Metadata } from 'next';
import { AdminLayoutClient } from './AdminLayoutClient';

export const metadata: Metadata = {
  title: 'Admin Dashboard',
  description: 'Dynamic Roles Admin Dashboard',
};

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return <AdminLayoutClient>{children}</AdminLayoutClient>;
}
```

```tsx
// app/admin/AdminLayoutClient.tsx
'use client';

import { useState } from 'react';
import { Navigation } from '@/components/Navigation';
import { Breadcrumbs } from '@/components/Breadcrumbs';
import { DynamicRolesProvider } from '@/contexts/DynamicRolesContext';
import { useSession } from 'next-auth/react';

export function AdminLayoutClient({
  children,
}: {
  children: React.ReactNode;
}) {
  const { data: session } = useSession();
  const [sidebarOpen, setSidebarOpen] = useState(true);

  if (!session?.accessToken) {
    return <div>Loading...</div>;
  }

  return (
    <DynamicRolesProvider token={session.accessToken}>
      <div className="flex h-screen bg-gray-100">
        {/* Sidebar */}
        <div className={`${sidebarOpen ? 'w-64' : 'w-16'} bg-white shadow-lg transition-all duration-300`}>
          <div className="p-4 border-b">
            <div className="flex items-center justify-between">
              <h1 className={`text-xl font-bold ${!sidebarOpen && 'hidden'}`}>
                Admin Panel
              </h1>
              <button
                onClick={() => setSidebarOpen(!sidebarOpen)}
                className="p-1 rounded-md hover:bg-gray-100"
              >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                </svg>
              </button>
            </div>
          </div>
          
          <Navigation collapsed={!sidebarOpen} />
        </div>

        {/* Main Content */}
        <div className="flex-1 flex flex-col overflow-hidden">
          <header className="bg-white shadow-sm border-b px-6 py-4">
            <div className="flex items-center justify-between">
              <Breadcrumbs />
              
              <div className="flex items-center space-x-4">
                <button className="p-2 rounded-md hover:bg-gray-100">
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-5 5-5-5h5v-12h5v12z" />
                  </svg>
                </button>
                
                <div className="text-sm text-gray-600">
                  Welcome, {session.user?.name}
                </div>
              </div>
            </div>
          </header>
          
          <main className="flex-1 overflow-auto p-6">
            {children}
          </main>
        </div>
      </div>
    </DynamicRolesProvider>
  );
}
```

### 6. Performance Optimizations

```typescript
// hooks/useOptimizedApi.ts
import { useCallback, useRef } from 'react';
import { api } from '@/lib/api';

export function useOptimizedApi() {
  const requestCacheRef = useRef<Map<string, Promise<any>>>(new Map());
  const dataCacheRef = useRef<Map<string, { data: any; timestamp: number }>>(new Map());
  
  const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

  const cachedRequest = useCallback(async (key: string, requestFn: () => Promise<any>) => {
    // Check if request is already in flight
    if (requestCacheRef.current.has(key)) {
      return requestCacheRef.current.get(key);
    }

    // Check if data is cached and still fresh
    const cached = dataCacheRef.current.get(key);
    if (cached && Date.now() - cached.timestamp < CACHE_DURATION) {
      return cached.data;
    }

    // Make request
    const requestPromise = requestFn();
    requestCacheRef.current.set(key, requestPromise);

    try {
      const result = await requestPromise;
      dataCacheRef.current.set(key, { data: result, timestamp: Date.now() });
      return result;
    } finally {
      requestCacheRef.current.delete(key);
    }
  }, []);

  const getMenuTree = useCallback(() => {
    return cachedRequest('menuTree', () => api.getMenuTree());
  }, [cachedRequest]);

  const getUrls = useCallback(() => {
    return cachedRequest('urls', () => api.getUrls());
  }, [cachedRequest]);

  const clearCache = useCallback(() => {
    requestCacheRef.current.clear();
    dataCacheRef.current.clear();
  }, []);

  return {
    getMenuTree,
    getUrls,
    clearCache,
  };
}
```

### 7. Testing Setup

```typescript
// __tests__/components/ProtectedRoute.test.tsx
import { render, screen } from '@testing-library/react';
import { ProtectedRoute } from '@/components/ProtectedRoute';
import { DynamicRolesProvider } from '@/contexts/DynamicRolesContext';

const mockSession = {
  accessToken: 'test-token',
  user: { id: 1, name: 'Test User' }
};

const MockProvider = ({ children }: { children: React.ReactNode }) => (
  <DynamicRolesProvider token={mockSession.accessToken}>
    {children}
  </DynamicRolesProvider>
);

describe('ProtectedRoute', () => {
  it('renders children when user has permission', async () => {
    // Mock API response
    global.fetch = jest.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({
        success: true,
        data: { has_permission: true }
      })
    });

    render(
      <MockProvider>
        <ProtectedRoute url="/test" method="GET">
          <div>Protected Content</div>
        </ProtectedRoute>
      </MockProvider>
    );

    expect(await screen.findByText('Protected Content')).toBeInTheDocument();
  });

  it('renders fallback when user lacks permission', async () => {
    global.fetch = jest.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({
        success: true,
        data: { has_permission: false }
      })
    });

    render(
      <MockProvider>
        <ProtectedRoute url="/test" method="GET" fallback={<div>Access Denied</div>}>
          <div>Protected Content</div>
        </ProtectedRoute>
      </MockProvider>
    );

    expect(await screen.findByText('Access Denied')).toBeInTheDocument();
  });
});
```

## Deployment Considerations

### 1. Environment Variables

```env
# Production environment
NODE_ENV=production
NEXTAUTH_URL=https://your-domain.com
NEXTAUTH_SECRET=your-production-secret

# Backend API
NEXT_PUBLIC_API_URL=https://api.your-domain.com
NEXT_PUBLIC_DYNAMIC_ROLES_API=https://api.your-domain.com/api/dynamic-roles

# Additional security
NEXT_PUBLIC_SANCTUM_STATEFUL_DOMAINS=your-domain.com
```

### 2. Security Headers

```typescript
// next.config.js
/** @type {import('next').NextConfig} */
const nextConfig = {
  async headers() {
    return [
      {
        source: '/(.*)',
        headers: [
          {
            key: 'X-Frame-Options',
            value: 'DENY',
          },
          {
            key: 'X-Content-Type-Options',
            value: 'nosniff',
          },
          {
            key: 'Referrer-Policy',
            value: 'origin-when-cross-origin',
          },
        ],
      },
    ];
  },
};

module.exports = nextConfig;
```

This complete integration provides a production-ready Next.js application with full Dynamic Roles Package integration, including security best practices, performance optimizations, and comprehensive testing setup.