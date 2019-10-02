<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use App\Http\Controllers\Functions;

class WeatherController extends Controller
{
    private $ipapi = 'http://ip-api.com/php/';
    private $function;
    private $client;
    private $currentcondition_url = 'https://api.openweathermap.org/data/2.5/weather';
    private $forecast_url = 'https://api.openweathermap.org/data/2.5/forecast';

    // instantiate GuzzleHttp\Client
    public function __construct(Client $client, Functions $function) {
        $this->client = $client;
        $this->function = $function;
    }

    public function getCCandFC(Request $request) {
        // get api key
        $apikey = $this->getCredential();

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
        $gotlang = $this->getLocalizedLanguage($request->countrycode);

        $cc_url = $this->currentcondition_url.'?q='.$cityname.','.$countrycode.'&units=metric&lang='.$gotlang.'&APPID='.$apikey;
        $fc_url = $this->forecast_url.'?q='.$cityname.','.$countrycode.'&units=metric&lang='.$gotlang.'&APPID='.$apikey;

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

    protected function getipInfo($ipaddress) {
        $getipInfo = @unserialize(file_get_contents('http://ip-api.com/php/'.$ipaddress));

        if($getipInfo['status'] === 'success') {
            return $getipInfo;
        }

        return false;
    }
    
    /**
     * method to get user localize language
     * 
     * if not found the default language will be EN
     * 
     * @param $country
     * @return $lang
     */
    protected function getLocalizedLanguage($country) {
        // default language if country not supported
        $lang = 'en';
        
        foreach($this->supportedLanguage() as $key => $value) {
            if($key === $country) {
                $lang = $value;
                return $lang;
            }
        }

        return $lang;
    }

    /**
     * constant list of language supported by accuweather API
     * 
     * @return array
     */
    protected function supportedLanguage() {
        return array(
            'jp' =>  'ja',
            'kr' =>  'kr',
            'cn' =>  'zh_cn',
            'ph' =>  'en'
        );
    }

    protected function getCredential() {        
        $openweatherapikey  = env('OPENWEATHERAPIKEY');
        return $openweatherapikey;
    }
}
