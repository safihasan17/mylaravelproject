<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('id')->get();

        $totalRoles = $roles->count();
        $assignedRoles = $roles->where('users_count', '>', 0)->count();
        $unusedRoles = $totalRoles - $assignedRoles;

        return view('admin.pages.role.index', compact('roles', 'totalRoles', 'assignedRoles', 'unusedRoles'));
    }

    public function create()
    {
        return view('admin.pages.role.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:100|unique:roles,name',
        ]);

        Role::create($data);

        return redirect()->route('roles.index')->with('success', 'Role created successfully');
    }

    public function show(Role $role)
    {
        $role->load('users');

        return view('admin.pages.role.show', compact('role'));
    }

    public function edit(Role $role)
    {
        return view('admin.pages.role.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
        ]);

        $role->update($data);

        return redirect()->route('roles.index')->with('success', 'Role updated successfully');
    }

    public function destroy(Role $role)
    {
        if ($role->isProtected()) {
            return redirect()->route('roles.index')
                ->with('error', "\"{$role->name}\" is a core role used by the system and can't be deleted.");
        }

        if ($role->users()->exists()) {
            return redirect()->route('roles.index')
                ->with('error', "\"{$role->name}\" is assigned to users. Move those users to another role first.");
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully');
    }
}
