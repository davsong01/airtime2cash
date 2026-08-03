<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmailMessages extends Mailable
{
    use Queueable, SerializesModels;
    private $data;
    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $from_address = $this->data['provider']['MAIL_FROM_ADDRESS'] ?? env('MAIL_FROM_ADDRESS');
        $from_name = $this->data['provider']['MAIL_FROM_NAME'] ?? env('MAIL_FROM_NAME');
        $mail_to_address = $this->data['provider']['MAIL_REPLY_TO_ADDRESS'] ?? env('MAIL_REPLY_TO_ADDRESS');
        $mail_reply_to_name = $this->data['provider']['MAIL_REPLY_TO_NAME'] ?? env('MAIL_REPLY_TO_NAME');
        
        return new Envelope(
            from: new Address($from_address, $from_name),
            replyTo: [
                new Address($mail_to_address, $mail_reply_to_name),
            ],
            
            subject: $this->data['subject'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.empty_template',
            with: [
                'data' => $this->data,
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
        $files = [];

        if(!empty($this->data['attachments'])){
            foreach($this->data['attachments'] as $key=>$attachment){
                
                if (file_exists(base_path() . '/' . $attachment)) {
                    $files[] = Attachment::fromPath(base_path() . '/' . $attachment);
                }
            }
        }
     
        return $files;
    }
}
