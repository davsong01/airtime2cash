<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RolePermission;

class RolePermissionController extends Controller
{
    public function index(Request $request)
    {
        $permissions = RolePermission::orderBy('created_at', 'DESC')->get();
        return view('admin.permission.index', ['permissions' => $permissions]);
    }

    public function edit(RolePermission $permission)
    {
        return view('admin.permission.edit', compact('permission'));
    }

    public function update(Request $request, RolePermission $permission)
    {
        $data = $this->validate($request, [
            'name' => 'required|unique:role_permissions,name,' . $permission->id,
            'route' => 'required|unique:role_permissions,route,' . $permission->id,
            'status' => 'required',
            'type' => 'required',
        ]);

        $permission->update([
            'name' => $data['name'],
            'status' => $data['status'],
            'type' => $data['type'],
            'route' => $data['route']
        ]);

        return redirect(route('permission.index'))->with('message', 'Permission updated succesfully');
    }

    public function destroy(RolePermission $permission)
    {
        $permission->delete();

        return redirect(route('permission.index'))->with('message', 'Delete succesful');
    }

    function create()
    {
        return view('admin.permission.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'name' => 'required|unique:role_permissions,name',
            'route' => 'required|unique:role_permissions,route',
            'status' => 'required',
        ]);

        RolePermission::updateOrCreate(['name' => $data['name'], 'route' => $data['route']],[
            'name' => $data['name'],
            'route' => $data['route'],
            'status' => $data['status'],
        ]);

        return redirect(route('permission.index'))->with('message', 'Permission added succesfully');
    }
}
