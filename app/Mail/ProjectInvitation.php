<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProjectInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public $project;
    public $inviter;
    public $invitee;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($project, $inviter, $invitee)
    {
        $this->project = $project;
        $this->inviter = $inviter;
        $this->invitee = $invitee;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('invitation.project_invitation')
            ->subject('Invitation to join project ' . $this->project->name)
            ->with([
                'projectName' => $this->project->name,
                'inviterName' => $this->inviter->name,
                'inviteeName' => $this->invitee->name,
                'acceptLink' => route('projects.accept_invitation', ['project_id' => $this->project->id, 'invitee_id' => $this->invitee->id]),
            ]);
    }
}
