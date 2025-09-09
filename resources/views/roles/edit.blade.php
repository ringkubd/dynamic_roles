@extends('dynamic-roles::layout')

@section('title', 'Role Permission Management')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Edit Role: {{ $role->name }}</h1>
            <p class="mt-2 text-gray-600">Manage permissions for this role</p>
        </div>
        <a href="{{ route('dynamic-roles.roles.show', $role) }}" 
           class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            Back to Role
        </a>
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('dynamic-roles.roles.update', $role) }}">
        @csrf
        @method('PUT')
        
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Role Information</h3>
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role Name</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $role->name }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Guard Name</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $role->guard_name }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Created</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $role->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Permissions</h3>
            <p class="text-sm text-gray-600 mb-4">Select the permissions you want to assign to this role.</p>
            
            @foreach($allPermissions as $category => $permissions)
                <div class="mb-6 bg-white border border-gray-200 rounded-lg">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">
                            {{ ucfirst($category) }} Permissions
                        </h4>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($permissions as $permission)
                                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" 
                                           name="permissions[]" 
                                           value="{{ $permission->id }}"
                                           @if($role->permissions->contains($permission->id)) checked @endif
                                           class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <div class="ml-3 flex-1">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $permission->name }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Guard: {{ $permission->guard_name }}
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
            <div class="text-sm text-gray-500">
                <span class="font-medium">{{ $role->permissions->count() }}</span> permissions currently assigned
            </div>
            <div class="space-x-3">
                <a href="{{ route('dynamic-roles.roles.show', $role) }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Update Permissions
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
// Select all/none functionality for each category
document.addEventListener('DOMContentLoaded', function() {
    // Add select all buttons for each category
    document.querySelectorAll('.bg-gray-50').forEach(function(header) {
        if (header.querySelector('h4')) {
            const selectAllBtn = document.createElement('button');
            selectAllBtn.type = 'button';
            selectAllBtn.className = 'text-xs text-blue-600 hover:text-blue-800 ml-2';
            selectAllBtn.textContent = 'Select All';
            
            selectAllBtn.addEventListener('click', function() {
                const categoryDiv = header.parentElement;
                const checkboxes = categoryDiv.querySelectorAll('input[type="checkbox"]');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = !allChecked;
                });
                
                selectAllBtn.textContent = allChecked ? 'Select All' : 'Select None';
            });
            
            header.querySelector('h4').appendChild(selectAllBtn);
        }
    });
});
</script>
@endpush
