<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InviteCreated extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The invitation instance.
     *
     * @var \App\Models\Invitation
     */
    public $invitation;

    /**
     * Create a new message instance.
     */
    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've been invited to FlowKosmo",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invite',
            with: [
                'code' => $this->invitation->code,
                // Note: Admin app needs to point to the TENANT app URL, or strict app.url
                // If config(app.url) in Admin is admin.flowkosmo.xyz, we want it to point to app.flowkosmo.xyz
                // We'll hardcode or deduce it for now to match the Tenant App logic, 
                // assuming the user wants to register on the Tenant App.
                // The original code used config('app.url').
                'url' => 'https://app.flowkosmo.xyz/register-business?code=' . $this->invitation->code,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
