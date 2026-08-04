<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use HasFactory;

    protected $guarded = [];

    private ?array $cachedRolePermissions = null;
    private ?array $cachedRoleIds = null;

    function rolepermissions () {
        if ($this->cachedRolePermissions !== null) {
            return $this->cachedRolePermissions;
        }

        if (empty($this->permissions)) {
            return $this->cachedRolePermissions = [];
        }

        $roles = Role::where('status', 'active')
            ->whereIn('id', array_filter(explode(',', $this->permissions)))
            ->get(['id', 'permissions']);

        $this->cachedRoleIds = $roles->pluck('id')->all();
        $permissionIds = $roles->pluck('permissions')
            ->filter()
            ->flatMap(fn ($permissions) => explode(',', $permissions))
            ->filter()
            ->unique()
            ->values();

        if ($permissionIds->isEmpty()) {
            return $this->cachedRolePermissions = [];
        }

        return $this->cachedRolePermissions = RolePermission::whereIn('id', $permissionIds)
            ->pluck('route')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    function user () {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function roleIds(){
        if ($this->cachedRoleIds !== null) {
            return $this->cachedRoleIds;
        }

        if (empty($this->permissions)) {
            return $this->cachedRoleIds = [];
        }

        return $this->cachedRoleIds = Role::where('status', 'active')
            ->whereIn('id', array_filter(explode(',', $this->permissions)))
            ->pluck('id')
            ->all();
    }
    
}
