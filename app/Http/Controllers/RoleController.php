<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class RoleController extends Controller
{

    public function index()
    {
        if(\Auth::user()->can('manage role'))
        {

            $user = \Auth::user();
            if($user->type == 'super admin')
            {
                $permissions = Permission::all()->pluck('name', 'id')->toArray();
            }
            else
            {
                $permissions = new Collection();
                foreach($user->roles as $role)
                {
                    $permissions = $permissions->merge($role->permissions);
                }
                $permissions = $permissions->pluck('name', 'id')->toArray();
            }
            $roles = Role::where('created_by', '=', \Auth::user()->creatorId())->where('created_by','=', \Auth::user()->creatorId())->paginate();
            $rolePermissions = $role->permissions()->pluck('id')->toArray();
            return view('role.index', compact('permissions', 'roles', 'rolePermissions'));
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

    }


    public function create()
    {
        if(\Auth::user()->can('create role'))
        {
            $user = \Auth::user();
            if($user->type == 'super admin')
            {
                $permissions = Permission::all()->pluck('name', 'id')->toArray();
            }
            else
            {
                $permissions = new Collection();
                foreach($user->roles as $role)
                {
                    $permissions = $permissions->merge($role->permissions);
                }
                $permissions = $permissions->pluck('name', 'id')->toArray();
            }

            return view('role.create', ['permissions' => $permissions]);
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

    }


    public function store(Request $request)
    {
        if(\Auth::user()->can('create role'))
        {
            $validator = \Validator::make(
                $request->all(), [
                    'name' => 'required|max:100|unique:roles,name,NULL,id,created_by,' . \Auth::user()->creatorId(),
                    'permissions' => 'required',
                ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            try {
                DB::beginTransaction();

                $name             = $request['name'];
                $role            = new Role();
                $role->name      = $name;
                $role->created_by = \Auth::user()->creatorId();
                $role->save();

                $permissions = $request['permissions'];
                foreach($permissions as $permission)
                {
                    $p = Permission::where('id', '=', $permission)->firstOrFail();
                    $role->givePermissionTo($p);
                }

                // Get all users with this role and sync their permissions
                $users = \App\Models\User::role($role->name)->get();
                foreach($users as $user) {
                    $user->syncPermissions($role->permissions);
                }

                DB::commit();
                ToastMagic::success('Role'. $role->name .'added Successifully!');
                return redirect()->route('roles.index');
            } catch (\Exception $e) {
                DB::rollback();
                ToastMagic::error('Role creation failed: ' . $e->getMessage());
                return redirect()->back();
            }
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function edit(Role $role)
    {
        if(\Auth::user()->can('edit role'))
        {

            $user = \Auth::user();
            if($user->type == 'super admin')
            {
                $permissions = Permission::all()->pluck('name', 'id')->toArray();
            }
            else
            {
                $permissions = new Collection();
                foreach($user->roles as $role1)
                {
                    $permissions = $permissions->merge($role1->permissions);
                }
                $permissions = $permissions->pluck('name', 'id')->toArray();
            }

            return view('role.edit', compact('role', 'permissions'));
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }


    }

    public function update(Request $request, Role $role)
    {
        if(\Auth::user()->can('edit role'))
        {
            $validator = \Validator::make(
                $request->all(), [
                    'name' => 'required|max:100|unique:roles,name,' . $role['id'] . ',id,created_by,' . \Auth::user()->creatorId(),
                    'permissions' => 'required',
                ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            try {
                DB::beginTransaction();

                $input = $request->except(['permissions']);
                $permissions = $request['permissions'];
                $role->fill($input)->save();

                // Remove all previous permissions
                $p_all = Permission::all();
                foreach($p_all as $p)
                {
                    $role->revokePermissionTo($p);
                }

                // Assign new permissions
                foreach($permissions as $permission)
                {
                    $p = Permission::where('id', '=', $permission)->firstOrFail();
                    $role->givePermissionTo($p);
                }

                // Get all users with this role and sync their permissions
                $users = \App\Models\User::role($role->name)->get();
                foreach($users as $user) {
                    $user->syncPermissions($role->permissions);
                }

                DB::commit();
                ToastMagic::success('Role ' . $role->name . ' updated!');
                return redirect()->route('roles.index');
            } catch (\Exception $e) {
                DB::rollback();
                ToastMagic::error('Role update failed: ' .  $e->getMessage());
                return redirect()->back();
            }
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }


    public function destroy(Role $role)
    {
        if(\Auth::user()->can('delete role'))
        {
            $role->delete();
            ToastMagic::success('Role successfully deleted.'. $role->name .'');
            return redirect()->route('roles.index');
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }
}
