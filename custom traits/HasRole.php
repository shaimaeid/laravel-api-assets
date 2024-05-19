<?php

namespace App\Traits;

use App\Models\Role;

trait HasRole
{
    /**
     * Check if the user has a role.
     *
     * @param  string $role
     * @return bool
     */
    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Assign a role to the user.
     *
     * @param  string $role
     * @return void
     */
    public function assignRole($role)
    {
        $role = Role::where('name', $role)->firstOrFail();
        $this->roles()->attach($role);
    }

    /**
     * Define the relationship with the roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
