<?php
/**
 * author: Christopher M. Lim
 * email: lm.chrstphr.m@gmail.com
 * 2018
 */

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Stichoza\GoogleTranslate\GoogleTranslate;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use App\Http\Controllers\IbmController;
use Illuminate\Support\Facades\File;

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
        $lang = $this->getLanguageCode($countrycode);
        $trans_text = $this->transIBM($item, $countrycode);
        if($trans_text == null) {
            $trans_text = $this->transGoogle($item, $countrycode);
            return $trans_text;
        }
        return $trans_text;
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
            return null;
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

        if($item == null) {
            $translated = $item;
        } elseif ($countrycode === 'en') {
            $translated = $item;
        } else {
            $translated = $tr->translate($item);
            $translated == null ? $translated = $item : $translated;
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
}