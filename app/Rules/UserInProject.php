<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UserInProject implements Rule
{
    protected $projectId;

    public function __construct($projectId)
    {
        $this->projectId = $projectId;
    }

    public function passes($attribute, $value)
    {
        return DB::table('project_user')
            ->where('project_id', $this->projectId)
            ->where('user_id', $value)
            ->exists();
    }

    public function message()
    {
        return 'User is not part of this project.';
    }
}
