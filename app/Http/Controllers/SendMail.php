<?php

namespace App\Http\Controllers;

use App\Mail\VerifyMail;
use App\Mail\ResetMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SendMail extends Controller
{
    public function verifyEmail($to_address, $username, $url){

        Mail::send(
            new VerifyMail(
                $to_address,
                $username,
                $url
            )
        );
    }

    public function resetEmail($to_address, $username, $url){

        Mail::send(
            new ResetMail(
                $to_address,
                $username,
                $url
            )
        );
    }
}
