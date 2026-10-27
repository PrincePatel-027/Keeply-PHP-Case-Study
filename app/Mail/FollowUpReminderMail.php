<?php

namespace App\Mail;

use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FollowUpReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reminder $reminder) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Follow up: '.$this->reminder->resolutionCase->title,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.follow-up-reminder');
    }
}
