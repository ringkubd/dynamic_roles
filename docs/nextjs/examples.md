# Next.js Example Usage

Practical examples of integrating the Dynamic Roles Package with Next.js applications.

## Complete Admin Dashboard Example

### 1. Layout with Dynamic Navigation

```tsx
// app/admin/layout.tsx
'use client';

import { Navigation } from '@/components/Navigation';
import { Breadcrumbs } from '@/components/Breadcrumbs';
import { useDynamicRoles } from '@/contexts/DynamicRolesContext';

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const { loading } = useDynamicRoles();

  if (loading) {
    return <div>Loading admin interface...</div>;
  }

  return (
    <div className="flex h-screen bg-gray-100">
      {/* Sidebar */}
      <div className="w-64 bg-white shadow-lg">
        <div className="p-4">
          <h1 className="text-xl font-bold">Admin Panel</h1>
        </div>
        <Navigation />
      </div>

      {/* Main Content */}
      <div className="flex-1 flex flex-col overflow-hidden">
        <header className="bg-white shadow p-4">
          <Breadcrumbs />
        </header>
        <main className="flex-1 overflow-auto p-6">
          {children}
        </main>
      </div>
    </div>
  );
}
```

### 2. Protected Pages

```tsx
// app/admin/users/page.tsx
'use client';

import { useState, useEffect } from 'react';
import { ProtectedRoute } from '@/components/ProtectedRoute';
import { useApi } from '@/hooks/useApi';
import { Button } from '@/components/ui/Button';
import { Modal } from '@/components/ui/Modal';

interface User {
  id: number;
  name: string;
  email: string;
  roles: string[];
}

export default function UsersPage() {
  const [users, setUsers] = useState<User[]>([]);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const { execute, loading, error } = useApi();

  useEffect(() => {
    loadUsers();
  }, []);

  const loadUsers = async () => {
    try {
      const response = await execute(() => 
        fetch('/api/admin/users').then(res => res.json())
      );
      setUsers(response.data);
    } catch (error) {
      console.error('Failed to load users:', error);
    }
  };

  return (
    <ProtectedRoute url="/admin/users" method="GET">
      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <h1 className="text-2xl font-bold">User Management</h1>
          
          <ProtectedRoute url="/admin/users" method="POST" fallback={null}>
            <Button onClick={() => setShowCreateModal(true)}>
              Create User
            </Button>
          </ProtectedRoute>
        </div>

        {loading && <div>Loading users...</div>}
        {error && <div className="text-red-500">Error: {error}</div>}

        <div className="bg-white shadow rounded-lg overflow-hidden">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Name
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Email
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Roles
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {users.map((user) => (
                <UserRow key={user.id} user={user} onUpdate={loadUsers} />
              ))}
            </tbody>
          </table>
        </div>

        {showCreateModal && (
          <CreateUserModal
            onClose={() => setShowCreateModal(false)}
            onSuccess={() => {
              setShowCreateModal(false);
              loadUsers();
            }}
          />
        )}
      </div>
    </ProtectedRoute>
  );
}

function UserRow({ user, onUpdate }: { user: User; onUpdate: () => void }) {
  const { execute } = useApi();

  const handleDelete = async () => {
    if (confirm('Are you sure?')) {
      try {
        await execute(() =>
          fetch(`/api/admin/users/${user.id}`, { method: 'DELETE' })
        );
        onUpdate();
      } catch (error) {
        console.error('Failed to delete user:', error);
      }
    }
  };

  return (
    <tr>
      <td className="px-6 py-4 whitespace-nowrap">
        <div className="text-sm font-medium text-gray-900">{user.name}</div>
      </td>
      <td className="px-6 py-4 whitespace-nowrap">
        <div className="text-sm text-gray-500">{user.email}</div>
      </td>
      <td className="px-6 py-4 whitespace-nowrap">
        <div className="flex space-x-1">
          {user.roles.map((role) => (
            <span
              key={role}
              className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
            >
              {role}
            </span>
          ))}
        </div>
      </td>
      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
        <ProtectedRoute url={`/admin/users/${user.id}`} method="PUT" fallback={null}>
          <Button variant="outline" size="sm">
            Edit
          </Button>
        </ProtectedRoute>
        
        <ProtectedRoute url={`/admin/users/${user.id}`} method="DELETE" fallback={null}>
          <Button variant="outline" size="sm" onClick={handleDelete}>
            Delete
          </Button>
        </ProtectedRoute>
      </td>
    </tr>
  );
}
```

### 3. Permission Management Interface

```tsx
// app/admin/permissions/page.tsx
'use client';

import { useState, useEffect } from 'react';
import { ProtectedRoute } from '@/components/ProtectedRoute';
import { useApi } from '@/hooks/useApi';

interface UrlPermission {
  id: number;
  url: string;
  method: string;
  permissions: string[];
  name?: string;
  category?: string;
}

export default function PermissionsPage() {
  const [urls, setUrls] = useState<UrlPermission[]>([]);
  const [newUrl, setNewUrl] = useState({
    url: '',
    method: 'GET',
    permissions: [''],
    name: '',
    category: '',
  });
  const { execute, loading, error } = useApi();

  useEffect(() => {
    loadUrls();
  }, []);

  const loadUrls = async () => {
    try {
      const response = await execute(() => api.getUrls());
      setUrls(response.data);
    } catch (error) {
      console.error('Failed to load URLs:', error);
    }
  };

  const handleCreateUrl = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await execute(() => api.createUrl({
        ...newUrl,
        permissions: newUrl.permissions.filter(p => p.trim())
      }));
      setNewUrl({
        url: '',
        method: 'GET',
        permissions: [''],
        name: '',
        category: '',
      });
      loadUrls();
    } catch (error) {
      console.error('Failed to create URL:', error);
    }
  };

  return (
    <ProtectedRoute url="/admin/permissions" method="GET">
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">URL Permissions</h1>

        {/* Create URL Form */}
        <ProtectedRoute url="/admin/permissions" method="POST" fallback={null}>
          <form onSubmit={handleCreateUrl} className="bg-white p-6 rounded-lg shadow space-y-4">
            <h2 className="text-lg font-medium">Add New URL Permission</h2>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700">URL</label>
                <input
                  type="text"
                  value={newUrl.url}
                  onChange={(e) => setNewUrl({ ...newUrl, url: e.target.value })}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                  placeholder="/api/users"
                  required
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700">Method</label>
                <select
                  value={newUrl.method}
                  onChange={(e) => setNewUrl({ ...newUrl, method: e.target.value })}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                >
                  <option value="GET">GET</option>
                  <option value="POST">POST</option>
                  <option value="PUT">PUT</option>
                  <option value="DELETE">DELETE</option>
                  <option value="PATCH">PATCH</option>
                </select>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700">Name</label>
                <input
                  type="text"
                  value={newUrl.name}
                  onChange={(e) => setNewUrl({ ...newUrl, name: e.target.value })}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                  placeholder="users.index"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700">Category</label>
                <input
                  type="text"
                  value={newUrl.category}
                  onChange={(e) => setNewUrl({ ...newUrl, category: e.target.value })}
                  className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                  placeholder="users"
                />
              </div>
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700">Permissions</label>
              {newUrl.permissions.map((permission, index) => (
                <div key={index} className="flex mt-1 space-x-2">
                  <input
                    type="text"
                    value={permission}
                    onChange={(e) => {
                      const updated = [...newUrl.permissions];
                      updated[index] = e.target.value;
                      setNewUrl({ ...newUrl, permissions: updated });
                    }}
                    className="flex-1 border-gray-300 rounded-md shadow-sm"
                    placeholder="users.view"
                  />
                  <button
                    type="button"
                    onClick={() => {
                      const updated = newUrl.permissions.filter((_, i) => i !== index);
                      setNewUrl({ ...newUrl, permissions: updated });
                    }}
                    className="px-3 py-2 bg-red-500 text-white rounded-md hover:bg-red-600"
                  >
                    Remove
                  </button>
                </div>
              ))}
              <button
                type="button"
                onClick={() => setNewUrl({ ...newUrl, permissions: [...newUrl.permissions, ''] })}
                className="mt-2 px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
              >
                Add Permission
              </button>
            </div>
            
            <button
              type="submit"
              disabled={loading}
              className="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 disabled:opacity-50"
            >
              {loading ? 'Creating...' : 'Create URL Permission'}
            </button>
            
            {error && <div className="text-red-500 text-sm">{error}</div>}
          </form>
        </ProtectedRoute>

        {/* URLs List */}
        <div className="bg-white shadow rounded-lg overflow-hidden">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  URL
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Method
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Permissions
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Category
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {urls.map((url) => (
                <tr key={url.id}>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {url.url}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                      url.method === 'GET' ? 'bg-green-100 text-green-800' :
                      url.method === 'POST' ? 'bg-blue-100 text-blue-800' :
                      url.method === 'PUT' ? 'bg-yellow-100 text-yellow-800' :
                      url.method === 'DELETE' ? 'bg-red-100 text-red-800' :
                      'bg-gray-100 text-gray-800'
                    }`}>
                      {url.method}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {url.permissions.join(', ')}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {url.category || '-'}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </ProtectedRoute>
  );
}
```

### 4. Menu Management Interface

```tsx
// app/admin/menus/page.tsx
'use client';

import { useState, useEffect } from 'react';
import { ProtectedRoute } from '@/components/ProtectedRoute';
import { useApi } from '@/hooks/useApi';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';

interface MenuItem {
  id: number;
  title: string;
  url: string;
  icon?: string;
  order: number;
  parent_id?: number;
  children: MenuItem[];
}

export default function MenusPage() {
  const [menus, setMenus] = useState<MenuItem[]>([]);
  const { execute, loading } = useApi();

  useEffect(() => {
    loadMenus();
  }, []);

  const loadMenus = async () => {
    try {
      const response = await execute(() => api.getMenuTree());
      setMenus(response.data);
    } catch (error) {
      console.error('Failed to load menus:', error);
    }
  };

  const handleReorder = async (result: any) => {
    if (!result.destination) return;

    const items = Array.from(menus);
    const [reorderedItem] = items.splice(result.source.index, 1);
    items.splice(result.destination.index, 0, reorderedItem);

    setMenus(items);

    try {
      await execute(() => api.reorderMenus(items.map((item, index) => ({
        id: item.id,
        order: index + 1,
        parent_id: item.parent_id
      }))));
    } catch (error) {
      console.error('Failed to reorder menus:', error);
      loadMenus(); // Reload on error
    }
  };

  return (
    <ProtectedRoute url="/admin/menus" method="GET">
      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <h1 className="text-2xl font-bold">Menu Management</h1>
          
          <ProtectedRoute url="/admin/menus" method="POST" fallback={null}>
            <button className="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
              Add Menu Item
            </button>
          </ProtectedRoute>
        </div>

        <div className="bg-white shadow rounded-lg p-6">
          <DragDropContext onDragEnd={handleReorder}>
            <Droppable droppableId="menus">
              {(provided) => (
                <div {...provided.droppableProps} ref={provided.innerRef}>
                  {menus.map((menu, index) => (
                    <MenuItemRow
                      key={menu.id}
                      menu={menu}
                      index={index}
                      onUpdate={loadMenus}
                    />
                  ))}
                  {provided.placeholder}
                </div>
              )}
            </Droppable>
          </DragDropContext>
        </div>
      </div>
    </ProtectedRoute>
  );
}

function MenuItemRow({ 
  menu, 
  index, 
  onUpdate, 
  depth = 0 
}: { 
  menu: MenuItem; 
  index: number; 
  onUpdate: () => void;
  depth?: number;
}) {
  const { execute } = useApi();

  const handleDelete = async () => {
    if (confirm('Are you sure?')) {
      try {
        await execute(() => api.deleteMenu(menu.id));
        onUpdate();
      } catch (error) {
        console.error('Failed to delete menu:', error);
      }
    }
  };

  return (
    <Draggable draggableId={menu.id.toString()} index={index}>
      {(provided) => (
        <div
          ref={provided.innerRef}
          {...provided.draggableProps}
          {...provided.dragHandleProps}
          className={`flex items-center justify-between p-3 border-b ${
            depth > 0 ? `ml-${depth * 6}` : ''
          }`}
        >
          <div className="flex items-center space-x-3">
            {menu.icon && <i className={menu.icon} />}
            <span className="font-medium">{menu.title}</span>
            <span className="text-gray-500">({menu.url})</span>
          </div>
          
          <div className="flex space-x-2">
            <ProtectedRoute url={`/admin/menus/${menu.id}`} method="PUT" fallback={null}>
              <button className="text-blue-600 hover:text-blue-800">Edit</button>
            </ProtectedRoute>
            
            <ProtectedRoute url={`/admin/menus/${menu.id}`} method="DELETE" fallback={null}>
              <button 
                onClick={handleDelete}
                className="text-red-600 hover:text-red-800"
              >
                Delete
              </button>
            </ProtectedRoute>
          </div>
        </div>
      )}
    </Draggable>
  );
}
```

## API Route Examples

### Next.js API Routes with Permission Checking

```typescript
// pages/api/admin/users/index.ts
import { NextApiRequest, NextApiResponse } from 'next';
import { getServerSession } from 'next-auth';
import { checkPermission } from '@/lib/dynamic-roles-server';

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  const session = await getServerSession(req, res, authOptions);
  
  if (!session) {
    return res.status(401).json({ error: 'Unauthorized' });
  }

  const hasPermission = await checkPermission(
    session.user.id,
    req.url!,
    req.method!
  );

  if (!hasPermission) {
    return res.status(403).json({ error: 'Forbidden' });
  }

  switch (req.method) {
    case 'GET':
      // Handle GET request
      const users = await getUsersFromDatabase();
      return res.json({ success: true, data: users });
      
    case 'POST':
      // Handle POST request
      const newUser = await createUser(req.body);
      return res.json({ success: true, data: newUser });
      
    default:
      return res.status(405).json({ error: 'Method not allowed' });
  }
}
```

These examples demonstrate comprehensive integration patterns for building admin interfaces with the Dynamic Roles Package in Next.js applications.