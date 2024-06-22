<?php
// app/Http/Controllers/TaskCommentController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TaskComment;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TaskCommentController extends Controller
{
    public function index($taskId)
    {
        $task = Task::findOrFail($taskId);
        $comments = $task->comments()->with('user')->get();
        return response()->json($comments);
    }

    public function store(Request $request, $taskId)
    {
        $validate = Validator::make($request->all(), [
            "comment" => "required|string",
        ]);
        if ($validate->fails()) {
            return response()->json(['error' => $validate->errors()], 400);
        }


        $task = Task::findOrFail($taskId);
        $comment = $task->comments()->create([
            'project_id'=> $task->project->id,
            'comment'=> $request->comment,
            'user_id'=> Auth::id()
        ]);
        return response()->json($comment, 201);
    }

    public function update(Request $request, $taskId, $commentId)
    {
        $comment = TaskComment::findOrFail($commentId);
        $validate = Validator::make($request->all(), [
            "comment" => "required|string",
        ]);
        if ($validate->fails()) {
            return response()->json(['error' => $validate->errors()], 400);
        }

        $task = Task::findOrFail($taskId);
        $project = $task->project; // Assumes Task belongs to a Project

        // Check if user has permission to update this comment
        if ($comment->user_id !== Auth::id() && !$project->hasRole(Auth::id(), 'owner') && !$project->hasRole(Auth::id(), 'admin')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        
        $comment->comment = $request->comment;
        $comment->save();

        return response()->json($comment);
    }

    public function destroy($taskId, $commentId)
    {
        $comment = TaskComment::findOrFail($commentId);
        $task = Task::findOrFail($taskId);
        $project = $task->project; // Assumes Task belongs to a Project

        // Check if user has permission to delete this comment
        if ($comment->user_id !== Auth::id() && !$project->hasRole(Auth::id(), 'owner') && !$project->hasRole(Auth::id(), 'admin')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }
}
