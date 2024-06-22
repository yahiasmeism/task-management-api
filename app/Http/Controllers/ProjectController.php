<?php

namespace App\Http\Controllers;

use App\Mail\ProjectInvitation;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Rules\UserInProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:owner')->only(['update', 'destroy']);
        $this->middleware('role:owner,admin')->only(['removeUser', 'updateUserRole']);
    }

    /**
     * Get a list of all projects with their owners.
     */
    public function index()
    {
        $projects = Project::all();
        $projectsWithOwners = $projects->map(function ($project) {
            $project['owner'] = $project->owner()->first();
            return $project;
        });
        return response()->json($projectsWithOwners);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request)
    {
        // Validate request data
        $validated = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        // Return validation errors if any
        if ($validated->fails()) {
            return response()->json(['errors' => $validated->errors()], 400);
        }

        // Create a new project
        $project = Project::create($request->all());

        // Get authenticated user
        $user = auth()->user();

        // Attach user to project with 'accepted' status
        $project->users()->attach($user->id, ['status' => 'accepted']);

        // Assign 'owner' role to user within the project
        $project->assignRole($user->id, 'owner');

        return response()->json($project, 201);
    }

    /**
     * Show a specific project by ID.
     */
    public function show($projectId)
    {
        $project = $this->findProjectOrFail($projectId);
        if ($project instanceof JsonResponse) {
            return $project;
        }

        $project['owner'] = $project->owner()->first();
        return response()->json($project);
    }

    /**
     * Update a specific project by ID.
     */
    public function update(Request $request, $projectId)
    {
        $project = $this->findProjectOrFail($projectId);
        if ($project instanceof JsonResponse) {
            return $project;
        }

        // Validate request data
        $validated = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        // Return validation errors if any
        if ($validated->fails()) {
            return response()->json(['errors' => $validated->errors()], 400);
        }

        // Update project with validated data
        $project->update($validated->validated());

        return response()->json($project);
    }



    /**
     * Destroy (delete) a specific project by ID.
     */
    public function destroy($projectId)
    {
        $project = $this->findProjectOrFail($projectId);
        if ($project instanceof JsonResponse) {
            return $project;
        }

        $project->delete();

        return response()->json(['message' => 'Deleted successfully'], 200);
    }



    /**
     * Get a list of users in a specific project.
     */
    public function usersInProject($projectId)
    {
        $project = $this->findProjectOrFail($projectId);
        if ($project instanceof JsonResponse) {
            return $project;
        }

        return response()->json($project->users);
    }



    // Function to update user role in a project
    public function updateUserRole(Request $request, $projectId, $userId)
    {
        $validate = Validator::make(array_merge($request->all(), ['project_id' => $projectId, 'user_id' => $userId]), [
            'role' => 'required|in:admin,member',
            'project_id' => 'required|exists:projects,id',
            'user_id' => ['required', 'exists:users,id', new UserInProject($projectId)],
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors());
        }

        $currentUser = Auth::user();
        $currentUserRole = DB::table('project_user')
            ->where('user_id', $currentUser->id)
            ->where('project_id', $projectId)
            ->first()
            ->role_id;

        $targetUserRole = DB::table('project_user')
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->first()
            ->role_id;

        $ownerRoleId = Role::where('name', 'owner')->first()->id;
        $adminRoleId = Role::where('name', 'admin')->first()->id;

        // Check permissions
        if ($currentUserRole == $adminRoleId && ($targetUserRole == $ownerRoleId || $request->role == 'owner')) {
            return response()->json(['error' => 'cannot change'], 403);
        }

        if ($currentUserRole == $adminRoleId && $targetUserRole == $adminRoleId && $request->role == 'member') {
            return response()->json(['error' => 'cannot change'], 403);
        }
        $project = Project::find($projectId);
        $project->assignRole($userId, $request->role);

        return response()->json(['message' => 'User role updated successfully']);
    }

    // Function to remove a user from a project
    public function removeUser($projectId, $userId)
    {
        $validate = Validator::make(array_merge(['project_id' => $projectId, 'user_id' => $userId]), [
            'project_id' => 'required|exists:projects,id',
            'user_id' => ['required', 'exists:users,id', new UserInProject($projectId)],
        ]);

        if ($validate->fails()) {
            return response()->json($validate->errors());
        }


        $currentUser = Auth::user();
        $currentUserRole = DB::table('project_user')
            ->where('user_id', $currentUser->id)
            ->where('project_id', $projectId)
            ->first()
            ->role_id;

        $targetUserRole = DB::table('project_user')
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->first()
            ->role_id;

        $ownerRoleId = Role::where('name', 'owner')->first()->id;
        $adminRoleId = Role::where('name', 'admin')->first()->id;

        // Check permissions
        if ($targetUserRole == $ownerRoleId) {
            return response()->json(['error' => 'Cannot remove owner from project'], 403);
        }

        if ($currentUserRole == $adminRoleId && $targetUserRole == $adminRoleId) {
            return response()->json(['error' => 'Admin cannot remove another admin'], 403);
        }
        $project = Project::find($projectId);
        $project->users()->detach($userId);

        return response()->json(['message' => 'User removed from project successfully']);
    }

    /**
     * Find a project by ID or return a JSON error response.
     */
    private function findProjectOrFail($projectId)
    {
        try {
            return Project::findOrFail($projectId);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Project not found'], 404);
        }
    }
}
