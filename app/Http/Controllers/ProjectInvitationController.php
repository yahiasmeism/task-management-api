<?php

namespace App\Http\Controllers;

use App\Mail\ProjectInvitation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ProjectInvitationController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:owner,admin')->only(['inviteUser']);
    }
    public function inviteUser(Request $request, $projectId)
    {
        $validated = Validator::make($request->all(), [
            'email' => 'required|email|exists:users',
            'role' => 'required|in:admin,member'
        ]);

        if ($validated->fails()) {
            return response()->json(['errors' => $validated->errors()], 400);
        }

        try {
            $project = Project::findOrFail($projectId);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'project not found'], 404);
        }

        $userInviter = auth()->user();
        $userInvitee = User::where('email', $request->email)->first();

        //check if user already exist in project
        $invitationStatus = $project->users()->where('user_id', $userInvitee->id)->pluck('status')->first();
        if ($invitationStatus == 'accepted') {

            return response()->json(['message' => 'user already exist in project']);
        } else if ($invitationStatus == 'invited') {


            // resend invitation message if no accepted
            Mail::to($userInvitee->email)->send(new ProjectInvitation($project, $userInviter, $userInvitee));
            return response()->json(['message' => 'Invitation sent.']);
        } else {


            // send email invitation
            Mail::to($userInvitee->email)->send(new ProjectInvitation($project, $userInviter, $userInvitee));
            $project->users()->attach($userInvitee->id, ['status' => 'invited']);
            $project->assignRole($userInvitee->id, $request->role);
            return response()->json(['message' => 'Invitation sent.']);
        }
    }



    public function acceptInvite($projectId, $inviteeId)
    {
        $project = Project::findOrFail($projectId);
        $invitee = User::findOrFail($inviteeId);
        $project->users()->updateExistingPivot($invitee->id, ['status' => 'accepted']);
        return view('invitation.accepted', ['message' => 'Invitation accepted successfully']);
    }
}
