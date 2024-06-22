<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function user()
    {
        return response()->json(Auth::user());
    }
    public function projects()
    {
        $projects = Auth::user()->projects;
        $projectsWithOwners = $projects->map(function ($project) {
            $project['owner'] = $project->owner()->first();
            return $project;
        });
        return response()->json($projectsWithOwners);
    }
    public function tasks()
    {
        $tasks = Auth::user()->tasks;
        $tasksWithProjects = $tasks->map(function ($task) {
            $task['project'] = $task->project->first();
            return $task;
        });
        return response()->json($tasksWithProjects);
    }
}
