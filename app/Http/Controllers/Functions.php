<?php
/**
 * author: Christopher M. Lim
 * email: lm.chrstphr.m@gmail.com
 * 2018
 */

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;

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
        $current->timezone = new \DateTimeZone(getenv('APP_TIMEZONE'));
        
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
}