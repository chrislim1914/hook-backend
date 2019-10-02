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
        // set the translator
        $tr = new GoogleTranslate(); // Translates to 'en' from auto-detected language by default
        $tr->setSource('en'); // Translate from English
        $tr->setTarget($countrycode); // Translate to based on countrycode localization

        if($item == null) {
            $translated = null;
        } elseif ($countrycode === 'ph') {
            $translated = $item;
        } else {
            $translated = $tr->translate($item);
        }

        return $translated;
    }

    /**
     * method for getting support language using Stichoza\GoogleTranslate\GoogleTranslate
     * 
     * @param $countrycode
     * @return $c_code
     */
    public function countrycodeforlanguage($countrycode) {

        $c_code = 'ph'; 

        $array_code =  array(
           'jp' =>  'ja',
           'kr' =>  'ko',
           'cn' =>  'zh',
           'ph' =>  'en'
        );

        if($countrycode == null) {
            return $c_code;
        }
        
        foreach($array_code as $key => $value) {
            if($key === $countrycode) {
                $c_code = $value;
                return $c_code;
            }
        }

        return $c_code;
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