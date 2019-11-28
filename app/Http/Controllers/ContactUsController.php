<?php

namespace App\Http\Controllers;

use Validator;
use Mailgun\Mailgun;
use App\ContactUs;
use Illuminate\Http\Request;
use Mailgun\Exception\HttpClientException;
use Buzz\Exception\NetworkException;
use App\Http\Controllers\Functions;

class ContactUsController extends Controller
{
    /**
     * method to send email in  Contact Us page
     * 
     * @param $request
     * @return JSON
     */
    public function createNewContact(Request $request) {
       $function = new Functions();

        // First, instantiate the SDK with your API credentials
        $mailgundata = $this->getCredential();
        $mg = Mailgun::create($mailgundata['mailgunapikey']); // For US servers

        // Now, compose and send your message.
        // $mg->messages()->send($domain, $params);
        try {
            $mg->messages()->send($mailgundata['mailgundomain'], [
                'from'    => $request->email,
                'to'      => $mailgundata['mailgunforwardemail'],
                'subject' => 'HOOK INQUIRY',
                'text'    => $request->message
            ]);

            return response()->json([
                'message'   => '',
                'result'    => true
            ]);
        } catch (HttpClientException $e) {
            return response()->json([
                'message'   => str_replace($function->getThatAnnoyingChar(), "", $e->getMessage()),
                'result'    => true
            ]);
        } catch (NetworkException $e) {
            return response()->json([
                'message'   => str_replace($function->getThatAnnoyingChar(), "", $e->getMessage()),
                'result'    => true
            ]);
        }
        
        
    }

    /**
     * method to validate param in contactus
     * 
     * @param $request
     * @return Boolean
     */
    protected function validateNewContact($request) {        
        // lets validate
        $validator = Validator::make($request, [
            'email'         => 'required',
            'message'       => 'required',
        ]);

        if ($validator->fails()) {
            return false;       
        }else{
            return true;
        }
        
    }

    /**
     * method to get mailgun app key
     * 
     * @return $openweatherapikey
     */
    protected function getCredential() {  
        return array(
            'mailgunapikey'         => env('MAILGUN_APIKEY'),
            'mailgundomain'         => env('MAILGUN_DOMAIN'),
            'mailgunforwardemail'   => env('MAILGUN_FORWARD_EMAIL'),
        );
    }
}
