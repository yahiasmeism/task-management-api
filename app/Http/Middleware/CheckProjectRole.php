<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;

class CheckProjectRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  mixed  ...$roles
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Check if the user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user(); // Get the authenticated user
        $projectId = $request->route('project_id'); // Get the project ID from the route
        $project = Project::findOrFail($projectId); // Find the project or fail if it doesn't exist

        // Check if the user has the required role and the status is accepted
        foreach ($roles as $role) {
            if ($project->hasRole($user->id, $role) && $project->users()->where('user_id', $user->id)->where('status', 'accepted')->exists()) {
                return $next($request);
            }
        }

        // If the user doesn't have the required role or status, return a forbidden response
        return response()->json(['error' => 'Forbidden'], 403);
    }
}
