<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class PermissionController extends Controller
{


    public function index()
    {
        $permissions = Permission::paginate(10);
        $roles = Role::get();
        return view('permission.index', compact('permissions', 'roles'));
    }

    public function create()
    {

        $roles = Role::get();

        return view('permission.create')->with('roles', $roles);
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|max:40',
        ]);

        $name             = $request['name'];
        $permission       = new Permission();
        $permission->name = $name;

        $roles = $request['roles'];
        $permission->save();

        if(!empty($request['roles']))
        {
            foreach($roles as $role)
            {
                $r          = Role::where('id', '=', $role)->firstOrFail();
                $permission = Permission::where('name', '=', $name)->first();
                $r->givePermissionTo($permission);
            }
        }

        ToastMagic::success('Permission ' . $permission->name . ' added!');
        return redirect()->route('permissions.index');
    }


    public function edit(Permission $permission)
    {
        $roles = Role::where('created_by', '=', \Auth::user()->creatorId())->get();
        return view('permission.edit', compact('roles', 'permission'));
    }


    public function update(Request $request, Permission $permission)
    {

        $permission = Permission::findOrFail($permission['id']);
        $request->validate([
                'name' => 'required|max:40',
            ]
        );
        $input = $request->all();
        $permission->fill($input)->save();
        ToastMagic::success('Permission ' . $permission->name . ' updated!');
        return redirect()->route('permissions.index');


    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();
        ToastMagic::success('Permission successfully deleted.');
        return redirect()->route('permissions.index');
    }
}
