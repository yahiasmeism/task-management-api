<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];
    protected $hidden = ['pivot'];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role_id', 'status'])
            ->join('roles', 'project_user.role_id', '=', 'roles.id')
            ->select('users.*', 'roles.name as role', 'project_user.status')
            ->withTimestamps();
    }

    public function owner()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role_id')
            ->wherePivot('role_id', '=', Role::where('name', 'owner')->first()->id);
    }
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
    public function comments()
    {
        return $this->hasMany(ProjectComment::class);
    }


    public function assignRole($userId, $role_name): void
    {
        $role =  Role::where('name', '=', $role_name)->first();
        $this->users()->updateExistingPivot($userId, ['role_id' => $role->id]);
    }


    public function hasRole($userId, $roleName): bool
    {
        $role = Role::where('name', '=', $roleName)->first();
        if (!$role) {
            return false;
        }

        return $this->users()
            ->where('user_id', $userId)
            ->where('role_id', $role->id)
            ->exists();
    }
}
