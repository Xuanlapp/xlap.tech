<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions
     */
    public function index()
    {
        $permissions = Permission::all();
        return view('admin.permissions.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new permission
     */
    public function create()
    {
        return view('admin.permissions.create');
    }

    /**
     * Store a newly created permission
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:permissions,name',
        ]);

        Permission::create(['name' => $request->input('name')]);

        return redirect()->route('permissions.index')
            ->with('success', 'Permission created successfully');
    }

    /**
     * Display the specified permission
     */
    public function show($id)
    {
        $permission = Permission::find($id);
        $roles = $permission->roles;
        return view('admin.permissions.show', compact('permission', 'roles'));
    }

    /**
     * Show the form for editing the specified permission
     */
    public function edit($id)
    {
        $permission = Permission::find($id);
        return view('admin.permissions.edit', compact('permission'));
    }

    /**
     * Update the specified permission
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
        ]);

        $permission = Permission::find($id);
        $permission->name = $request->input('name');
        $permission->save();

        return redirect()->route('permissions.index')
            ->with('success', 'Permission updated successfully');
    }

    /**
     * Remove the specified permission
     */
    public function destroy($id)
    {
        Permission::find($id)->delete();
        return redirect()->route('permissions.index')
            ->with('success', 'Permission deleted successfully');
    }
}
