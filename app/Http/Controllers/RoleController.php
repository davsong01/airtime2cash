<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    function index(Request $request)
    {
        $roles = Role::orderBy('created_at','DESC')->get();
        return view('admin.role.index', ['roles' => $roles]);
    }

    /**
     * Show the form for creating a new resource.
     */
    function create()
    {
        $menus = RolePermission::where('status', 'active')->where('type', 'menu')->get();
        $permissions = RolePermission::where('status','active')->where('type','link')->get();

        return view('admin.role.create', compact('menus', 'permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'name' => 'required|unique:roles',
            'menus' => 'required',
            'status' => 'required',
            'permissions' => 'required',
        ]);

        $permissions = array_merge($data['menus'], $data['permissions']);
        $permissions = implode(",",$permissions);

        Role::create([
            'name' => $data['name'],
            'status' => $data['status'],
            'permissions' => $permissions
        ]);

        return redirect(route('role.index'))->with('message','Role added succesfully');

    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $menus = RolePermission::where('status', 'active')->where('type', 'menu')->get();
        $permissions = RolePermission::where('status', 'active')->where('type', 'link')->orderBy('name','ASC')->get();
        $rolePermissions = explode(",",$role->permissions);
    
        return view('admin.role.edit', compact('menus', 'permissions', 'rolePermissions','role'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $data = $this->validate($request, [
            'name' => 'required|unique:roles,name,' . $role->id,
            'menus' => 'required',
            'status' => 'required',
            'permissions' => 'required',
        ]);
        
        $permissions = array_merge($data['menus'], $data['permissions']);
        $permissions = implode(",", $permissions);
    
        $role->update([
            'name' => $data['name'],
            'status' => $data['status'],
            'permissions' => $permissions
        ]);

        return redirect(route('role.index'))->with('message', 'Role updated succesfully');

    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        //
    }
}
