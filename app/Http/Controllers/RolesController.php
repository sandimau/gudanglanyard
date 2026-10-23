<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class RolesController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::with('permissions')->orderBy('id', 'asc')->get();

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::get();
        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:roles,name',
            'permission' => 'nullable|array',
            'permission.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create(['name' => $request->get('name')]);
        $role->syncPermissions($request->input('permission', []));

        return redirect()->route('roles.index')
                        ->with('success','Role created successfully');
    }

    public function show(Role $role)
    {
        $role = $role;
        $rolePermissions = $role->permissions;

        return view('roles.show', compact('role', 'rolePermissions'));
    }

    public function edit(Role $role)
    {
        $role = $role;
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $permissions = Permission::orderBy('name')->get();

        return view('roles.edit', compact('role', 'rolePermissions', 'permissions'));
    }

    public function update(Role $role, Request $request)
    {
        $isKonten = $role->name === 'konten';
        $this->validate($request, [
            'name' => ['required', $isKonten ? Rule::in(['konten']) : Rule::unique('roles', 'name')->ignore($role->id)],
            'permission' => 'nullable|array',
            'permission.*' => 'string|exists:permissions,name',
        ]);

        $role->update($request->only('name'));

        $role->syncPermissions($request->input('permission', []));

        return redirect()->route('roles.index')
                        ->with('success','Role updated successfully');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()->route('roles.index')
                        ->with('success','Role deleted successfully');
    }
}
