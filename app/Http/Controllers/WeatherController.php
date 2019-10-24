<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use App\Http\Controllers\Functions;

class WeatherController extends Controller
{
    private $ipapi = 'http://ip-api.com/php/';
    private $function;
    private $currentcondition_url = 'https://api.openweathermap.org/data/2.5/weather';
    private $forecast_url = 'https://api.openweathermap.org/data/2.5/forecast';
    private $apikey;

    /**
     * __contruct()
     * instantiate Functions class
     * 
     * @param Functions $function
     */
    public function __construct(Functions $function) {
        $this->function = $function;
        $this->apikey   = $this->getCredential();
    }

    public function getCCandFC(Request $request) {
        // get api key
        $this->apikey = $this->getCredential();

        // get ip data
        $ipdata = $this->getipInfo($request->ipaddress);

        if($ipdata == false) {
            return response()->json([
                'message'   => 'Error in getting user location data!',
                'result'    => false
            ]);
        }

        // get city and countrycode
        $cityname = $ipdata['city'];
        $countrycode = strtolower($ipdata['countryCode']);

        // get language syupport
        // $gotlang = $this->function->getLanguageCode($request->languagecode);

        // $cc_url = $this->currentcondition_url.'?q='.$cityname.','.$countrycode.'&units=metric&lang='.$gotlang.'&APPID='.$this->apikey;
        // $fc_url = $this->forecast_url.'?q='.$cityname.','.$countrycode.'&units=metric&lang='.$gotlang.'&APPID='.$this->apikey;

        $cc_url = $this->currentcondition_url.'?q='.$cityname.','.$countrycode.'&units=metric&APPID='.$this->apikey;
        $fc_url = $this->forecast_url.'?q='.$cityname.','.$countrycode.'&units=metric&APPID='.$this->apikey;

        $cc_body = $this->function->guzzleHttpCall($cc_url);
        $fc_body = $this->function->guzzleHttpCall($fc_url);
        // check if success
        if($cc_body['cod'] != 200 || $cc_body == false) {
            return response()->json([
                'message'   => 'Something went wrong on our side!',
                'result'    => false
            ]); 
        }
        if($fc_body['cod'] != 200  || $fc_body == false) {
            return response()->json([
                'message'   => 'Something went wrong on our side!',
                'result'    => false
            ]); 
        }

        $currentfeed = [];

        foreach($cc_body['weather'] as $icondata) {
            $icon = $icondata['icon'];
            $description = $icondata['description'];
        }

        $currentfeed = [
            'Date'          => date("Y-m-d",$cc_body['dt']),
            'EpochDate'     => $cc_body['dt'],
            'Temp'          => $cc_body['main']['temp'],
            'Icon'          => 'http://openweathermap.org/img/wn/'.$icon.'@2x.png',
            'Description'   => $description
        ];

        $fcFeed = [];
        $count = 0;

        foreach($fc_body['list'] as $item) {
            foreach($item['weather'] as $innerdata) {
                $innericon = $innerdata['icon'];
                $innerdes = $innerdata['description'];
            }

            $thistime = strtotime(date("Y-m-d",$item['dt']).' 11:00');

            if($count == 0){
                $fcFeed[] = [
                    'Date'          => date("Y-m-d H:i",$item['dt']),
                    'EpochDate'     => $item['dt'],
                    'Min-Temp'      => $item['main']['temp_min'],
                    'Max-Temp'      => $item['main']['temp_max'],
                    'Icon'          => 'http://openweathermap.org/img/wn/'.$innericon.'@2x.png',
                    'Description'   => $innerdes
                ];
            }elseif($thistime == $item['dt']) {
                $fcFeed[] = [
                    'Date'          => date("Y-m-d H:i",$item['dt']),
                    'EpochDate'     => $item['dt'],
                    'Min-Temp'      => $item['main']['temp_min'],
                    'Max-Temp'      => $item['main']['temp_max'],
                    'Icon'          => 'http://openweathermap.org/img/wn/'.$innericon.'@2x.png',
                    'Description'   => $innerdes
                ];
            }            
            $count++;
        }

        $weatherdata = [
            'Current_Condition' => $currentfeed,
            'Forecast'  => $fcFeed
        ];
        
        return response()->json([
            'data'      => $weatherdata,
            'result'    => true
        ]);
    }

    /**
     * method to get info using client ip address
     * utilizing http://ip-api.com/php/
     * 
     * @param $ipaddress
     * @return Mix
     */
    protected function getipInfo($ipaddress) {
        $getipInfo = @unserialize(file_get_contents('http://ip-api.com/php/'.$ipaddress));

        if($getipInfo['status'] === 'success') {
            return $getipInfo;
        }

        return false;
    }

    /**
     * method to get openweather app key
     * 
     * @return $openweatherapikey
     */
    protected function getCredential() {        
        $openweatherapikey  = env('OPENWEATHERAPIKEY');
        return $openweatherapikey;
    }
}
