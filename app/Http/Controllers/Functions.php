<?php
/**
 * author: Christopher M. Lim
 * email: lm.chrstphr.m@gmail.com
 * 2018
 */

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Stichoza\GoogleTranslate\GoogleTranslate;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use App\Http\Controllers\IbmController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Http\Controllers\AwsController;
use App\Http\Controllers\KakaoController;


class Functions extends Controller
{    
    public $hashPassword;
    
    //Password Encryption Function

    /**
     * method to hash password using bcrypt
     * note that bcrypt is design to encrypt but not to retrieved
     * the hashed password
     * 
     * @param $password
     * 
     * @return $hashPassword
     */
    public function hash($password) {
        $options = array(
            'cost' => 12,
          );
        $this->hashPassword = password_hash($password, PASSWORD_BCRYPT, $options);

        return trim($this->hashPassword);
    }

    /**
     * 
     * method to verify password using native php password_verify
     * 
     * @param $password $hashedPassword
     * 
     * @return Bool
     */
    public function verifyPassword($password, $hashedPassword) {
        if(password_verify($password, $hashedPassword)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
    * Return the path to public dir
    * @param null $path
    * @return string
    */
    public function public_path($path=null){
            return rtrim(app()->basePath('public/'.$path), '/');
    }

    // DateTime Function

    /**
     * set date and time with timezone
     * 
     * as of now the default time zone will be
     * manila, philippines
     */
    public function setDatetime(){

        //create current time using Carbon
        $current = Carbon::now();

        // Set the timezone via DateTimeZone instance or string
        $current->timezone = new \DateTimeZone('Asia/Manila');

        $current->toDateString(); 
        
        return $current;
    }

    /**
     * method to compute time lapse against createdate in contents table
     * 
     * @return $timelapse
     */
    public function timeLapse($timelapse){
        
        $timelapse = Carbon::parse($timelapse);        

        $current = $this->setDatetime();

        if($timelapse->diffInSeconds($current) <= 59) {
            return $timelapse =  'just now';
        } elseif($timelapse->diffInMinutes($current) <= 59) {
            $left = $timelapse->diffInMinutes($current);
            return ($left == 1 ? $left. ' minute ago' :  $left. ' minutes ago');
        } elseif($timelapse->diffInHours($current) <= 24) {
            $left = $timelapse->diffInHours($current);
            return ($left == 1 ? $left. ' hour ago' :  $left. ' hours ago');
        } elseif($timelapse->diffInDays($current) <= 6) {
            $left = $timelapse->diffInDays($current);
            return ($left == 1 ? $left. ' day ago' :  $left. ' days ago');
        } elseif($timelapse->diffInWeeks($current) <= 4){
            $left = $timelapse->diffInWeeks($current);
            return ($left == 1 ? $left. ' week ago' :  $left. ' weeks ago');
        } elseif($timelapse->diffInMonths($current) <= 12){
            $left = $timelapse->diffInMonths($current);
            return ($left == 1 ? $left. ' month ago' :  $left. ' months ago');
        } else {
            $left = $timelapse->diffInYears($current);
            return ($left == 1 ? $left. ' year ago' :  $left. ' years ago');
        }
    }

    /**
     * method to translate text
     * 
     * @param $item, $countrycode
     * @return $translated
     */
    public function translator($item, $countrycode) {
        /**
         * ok we have 4 translator
         * AWS
         * IBM
         * Google
         * Kakao
         * 
         * we use Kakao first, if failed 
         * we use Google, if failed 
         * we use AWS, if failed
         * we use IBM
         */
        if($countrycode === 'en') {
            return $item;            
        }
        
        $trans_kakao = $this->kakaoTranslator($item, $countrycode);
        if(!$trans_kakao) {
            $trans_google = $this->transGoogle($item, $countrycode);
            if(!$trans_google) {
                $trans_aws = $this->awsTranslator($item, $countrycode);
                if(!$trans_aws) {
                    $trans_ibm = $this->transIBM($item, $countrycode);
                    if(!$trans_ibm) {
                        // echo "nothing";
                        return $item;
                    }
                    // echo "ibm";
                    return $trans_ibm;
                }
                // echo "aws";
                return $trans_aws;
            }
            // echo "google";
            return $trans_google;
        }
        // echo "kakao";
        return $trans_kakao;
    }

    /**
     * method to translate text using AWS Translate API
     * 
     * @param $item, $countrycode
     * @return $translated
     */
    protected function kakaoTranslator($item, $countrycode) {
        $kakao_trans = new KakaoController();

        $translated = $kakao_trans->kakaoTranslation($item, $countrycode);

        if(!$translated) {
            return false;
        }
        // return response()->json($translated);
        // var_dump($translated);
        return $translated['translated_text'][0][0];
    }

    /**
     * method to translate text using AWS Translate API
     * 
     * @param $item, $countrycode
     * @return $translated
     */
    protected function awsTranslator($item, $countrycode) {
        $awstranslate = new AwsController();

        $translated = $awstranslate->awsTRanlate($item, $countrycode);

        if(!$translated) {
            return false;
        }

        return $translated;
    }

    /**
     * method to translate text using IBM Language Translation
     * 
     * @param $item, $countrycode
     * @return $translated
     */
    protected function transIBM($item, $countrycode) { 
        $ibmTranslator = new IbmController();
        $translated = $ibmTranslator->ibmTranslate($item, $countrycode);
        if($translated == null) {
            return false;
        }
        return $translated;  
    }

    /**
     * method to translate text using Google text Translation
     * 
     * @param $item, $countrycode
     * @return $translated
     */
    protected function transGoogle($item, $countrycode) {
        $tr = new GoogleTranslate(); // Translates to 'en' from auto-detected language by default
        $tr->setSource('en'); // Translate from English
        $tr->setTarget($countrycode); // Translate to based on countrycode localization

        $tr = new GoogleTranslate($countrycode, 'en', [
            'config' => [
                'curl' => [
                    'CURLOPT_PROXY' => '172.16.1.1/255',
                    'CURLOPT_PROXYPORT' => '3128',
                    'CURLOPT_PROXYUSERPWD' => ':',
                    'CURLOPT_HTTPPROXYTUNNEL' => 1
                ]
            ]
        ]);
       
        $translated = $tr->translate($item);

        if($translated == null) {
            return false;
        }

        return $translated;  
    }

    /**
     * method to check if the language 
     * inputed is supported in our language.php
     * 
     * @param $language
     * @return $langcode
     */
    public function getLanguageCode($inputlang) {
        // default language
        $langcode = '';

        $lang = config('language');

        // make sure that the input language code is in lower string        
        $inputlang = strtolower($inputlang);

        if(array_key_exists($inputlang, $lang)) {
            return $langcode = $inputlang;
        } else {
            return $langcode = 'en';
        }        
    }

    /**
     * method to check if the language 
     * inputed is supported in our language.php
     * 
     * @param $language
     * @return $langcode
     */
    public function getLanguageCodeForWeather($inputlang) {
        // default language
        $langcode = '';

        $lang = config('language');
        
        // make sure that the input language code is in lower string        
        $inputlang = strtolower($inputlang);

        if(array_key_exists($inputlang, $lang)) {
            return $langcode = $lang[$inputlang];
        } else {
            return $langcode = 'en';
        }        
    }

    /**
     * method to use GuzzleHttp\Client
     * 
     * @param $template_url
     * @return Mix
     */
    public function guzzleHttpCall($template_url) {
        
        $client = new Client();

        try {
                    
            $response = $client->request('GET', $template_url,['http_errors' => false]);
            $body = json_decode($response->getBody(), true);
            
            return $body;
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            return false;
        }
        catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return false;
        }
        catch (\GuzzleHttp\Exception\ConnectException $e) {
            return false;
        }
        catch (\GuzzleHttp\Exception\ServerException $e) {
            return false;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    /**
     * method to create verification email
     * 
     * @param $iduser
     * @return $verifyEmailUrl
     */
    public function createVerifyEmailLink($iduser, $for) {

        $now = $this->setDatetime();
        $expired = $now->addDay();
        $user = new User();
        $currentuser = $user->getUserData($iduser);

        // lets create the payload
        $payload = [
            'id'        => $currentuser['iduser'],
            'email'     => $currentuser['email'],
            'issueat'   => $now->toDateString(),
            'expired'   => $expired->toDateString(),
            'reason'    => $for
        ];

        $encrypt = Crypt::encrypt($payload);
        
        // ok lets save the token to the user table
        $usertoken = $user::where('iduser', $iduser);

        if($for === 'verify') {

            $verifyEmailUrl = 'https://allgamegeek.com/verify-email?t='.$encrypt;

            $usertoken->update([
                'emailverifytoken' => $encrypt
            ]);
        } elseif($for === 'reset') {

            $verifyEmailUrl = 'https://allgamegeek.com/reset-password?t='.$encrypt;

            $usertoken->update([
                'resetpasswordtoken' => $encrypt
            ]); 
        }

        return $verifyEmailUrl;
    }

    /**
     * method to decrypt token from createVerifyEmailLink()
     * 
     * @param $payload
     * @return $dismantle
     */
    public function dismantleVerifyLink($payload) {

        try {
            $dismantle = Crypt::decrypt($payload);
        } catch (DecryptException $e) {

            return false;  
        }

        return $dismantle;
    }

    
    /**
     * method to get $request->countrycode
     * 
     * @param $request
     * @return $countrycode 
     * @return Boolean 
     */
    public function isThereCountryCode($request) {
        if($request->has('countrycode')) {            
            return $request->countrycode;
        }
        return false;
    }

    /**
     * list of annoying character you get from scrapping
     */
    public function getThatAnnoyingChar() {
        return array("\n","\r","\\");
    }

    /**
     * method to explode string using multiple delimiter
     * 
     * @param array $delimiters
     * @param string $string
     * 
     * @return array $ary
     */
    public function multiexplode($delimiters,$string) {
        $ary = explode($delimiters[0],$string);
        array_shift($delimiters);
        if($delimiters != NULL) {
            foreach($ary as $key => $val) {
                 $ary[$key] = $this->multiexplode($delimiters, $val);
            }
        }
        return  $ary;
    }

    /**
     * method to get APP_URL from .env
     * 
     * @return $baseURL
     */
    public static function getAppURL() {
        $baseURL = env('APP_URL');
        return $baseURL;
    }
}
