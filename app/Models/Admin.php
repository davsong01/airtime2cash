<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use HasFactory;

    protected $guarded = [];

    function rolepermissions () {
        $permission = $this->permissions;
        $allPermissions = [];
        $routes = [];
        if(empty($permission)){
            return $allPermissions;
        }else{
            $allroles = explode(",",$permission);
            $rolePermission = Role::where('status','active')->whereIn('id',$allroles)->pluck('permissions')->toArray();
            
            foreach($rolePermission as $permissions){
                $explode = explode(",", $permissions);
                foreach ($explode as $key=>$permissions) {
                    $allPermissions[] = $permissions;
                }
            }
        }

        $allPermissions = array_unique($allPermissions);
        // Getting the slugs
        foreach($allPermissions as $key=>$value){
            $routes[] = RolePermission::where('id', $value)->value('route');
        }

        return $routes;
    }

    function user () {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function roleIds(){
        $permission = $this->permissions;
        $allPermissions = [];
        if (empty($permission)) {
            return $allPermissions;
        } else {
            $allroles = explode(",", $permission);
            $allPermissions= Role::where('status', 'active')->whereIn('id', $allroles)->pluck('id')->toArray();
            return $allPermissions;
        }
    }
    
}
