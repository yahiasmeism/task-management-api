<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Rules\UserInProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:owner,admin')->only(['store', 'update', 'destroy']);
        $this->middleware('role:owner,admin,member')->only(['show', 'index', 'getUserTasksInProject', 'updateStatus']);
    }
    public function index($projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'project not found'], 404);
        }
        return response()->json($project->tasks()->with('user')->get());
    }

    public function store(Request $request, $projectId)
    {

        $validate = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:in queue,in progress,done',
            'user_id' => ['required', 'exists:users,id', new UserInProject($projectId)],
            'due_date'=>'date'
        ]);
        if ($validate->fails()) {
            return response()->json([
                'error' => $validate->errors(),
            ], 400);
        }

        $task = Task::create(array_merge($validate->validated(), ['project_id' => $projectId]));

        return response()->json($task, 201);
    }


    public function show($projectId, $taskId)
    {
        $task = $this->findTaskOrFail($taskId);
        if ($task instanceof JsonResponse) {
            return $task;
        }
        return response()->json($task);
    }

    public function update(Request $request, $projectId, $taskId)
    {

        $task = $this->findTaskOrFail($taskId);
        if ($task instanceof JsonResponse) {
            return $task;
        }
        $validate = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:in queue,in progress,done',
            'due_date'=>'date'
        ]);

        if ($validate->fails()) {
            return response()->json([
                'error' => $validate->errors(),
            ], 400);
        }
        $task->update($validate->validated());
        return response()->json($task);
    }

    public function destroy(Request $request, $projectId)
    {

        $taskIds = $request->all();
    
        Task::whereIn('id', $taskIds)->where('project_id', $projectId)->delete();
    
        return response()->json(['message' => 'deleted successfully']);
    }


    public function getUserTasksInProject($projectId, $userId)
    {
        $validate = Validator::make(['project_id' => $projectId, 'user_id' => $userId], [
            'project_id' => 'required|exists:projects,id',
            'user_id' => ['required', 'exists:users,id', new UserInProject($projectId)],
        ]);

        if ($validate->fails()) {
            return response()->json([
                'error' => $validate->errors(),
            ], 400);
        }

        $tasks = Task::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->get();

        return response()->json($tasks);
    }



    public function updateStatus(Request $request, $projectId, $taskId)
    {
        $validated = Validator::make($request->all(), [
            'status' => 'required|string|in:in queue,in progress,done'
        ]);

        if ($validated->fails()) {
            return response()->json(['errors' => $validated->errors()], 400);
        }

        $user = Auth::user();
        $task = Task::findOrFail($taskId);

        if ($task->user_id !== $user->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $task->update(['status' => $validated->validated()['status']]);

        return response()->json($task);
    }
    public function findTaskOrFail($taskId)
    {
        try {
            $task = Task::findOrFail($taskId);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Task not found'], 404);
        }
        return $task;
    }
}
