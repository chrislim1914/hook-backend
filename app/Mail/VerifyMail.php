<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerifyMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var string the address to send the email */
    protected $to_address;

    /** @var string the context of mail */
    public $url;

    /** @var string username of user */
    public $username;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($to_address, $username, $url)
    {
        $this->to_address = $to_address;
        $this->username = $username;
        $this->url = $url;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->to($this->to_address)
            ->subject('Verify your email')
            ->view('verify')
            ->with(
                [
                    'verify'    => $this->url,
                    'username'  => $this->username
                ]
            );
    }
}
