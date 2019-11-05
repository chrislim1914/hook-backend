<?php

namespace App\Http\Controllers;

use App\Mail\VerifyMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SendMail extends Controller
{
    public function sendMail($to_address, $username, $url){

        Mail::send(
            new VerifyMail(
                $to_address,
                $username,
                $url
            )
        );
    }
}
