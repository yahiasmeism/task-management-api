<?php
// app/Http/Controllers/ProjectCommentController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectComment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProjectCommentController extends Controller
{
    public function index($projectId)
    {
        $project = Project::findOrFail($projectId);
        $comments = $project->comments()->with('user')->get();
        return response()->json($comments);
    }
    
    public function store(Request $request, $projectId)
    {

        $validate = Validator::make($request->all(), [
            "comment" => "required|string",
        ]);
        if ($validate->fails()) {
            return response()->json(['error' => $validate->errors()], 400);
        }
        $project = Project::findOrFail($projectId);
        $comment = $project->comments()->create([
            'project_id'=>$projectId,
            'comment'=> $request->comment,
            'user_id'=> Auth::id()
        ]);

        return response()->json($comment, 201);
    }

    public function update(Request $request, $projectId, $commentId)
    {
        $validate = Validator::make($request->all(), [
            "comment" => "required|string",
        ]);
        if ($validate->fails()) {
            return response()->json(['error' => $validate->errors()], 400);
        }
        $comment = ProjectComment::findOrFail($commentId);
        $project = Project::findOrFail($projectId);

        // Check if user has permission to update this comment
        if ($comment->user_id !== Auth::id() && !$project->hasRole(Auth::id(), 'owner') && !$project->hasRole(Auth::id(), 'admin')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $comment->comment = $request->comment;
        $comment->save();

        return response()->json($comment);
    }

    public function destroy($projectId, $commentId)
    {
        $comment = ProjectComment::findOrFail($commentId);
        $project = Project::findOrFail($projectId);

        // Check if user has permission to delete this comment
        if ($comment->user_id !== Auth::id() && !$project->hasRole(Auth::id(), 'owner') && !$project->hasRole(Auth::id(), 'admin')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }
}
