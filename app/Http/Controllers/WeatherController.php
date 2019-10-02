<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class WeatherController extends Controller
{
    private $ipapi = 'http://ip-api.com/php/';
    private $client;
    private $currentcondition_url = 'https://api.openweathermap.org/data/2.5/weather';
    private $forecast_url = 'https://api.openweathermap.org/data/2.5/forecast';

    // instantiate GuzzleHttp\Client
    public function __construct(Client $client) {
        $this->client = $client;
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
        try {
                            
            $cc_url = $this->currentcondition_url.'?q='.$cityname.','.$countrycode.'&units=metric&lang='.$gotlang.'&APPID='.$apikey;
            $fc_url = $this->forecast_url.'?q='.$cityname.','.$countrycode.'&units=metric&lang='.$gotlang.'&APPID='.$apikey;

            $response_cc = $this->client->request('GET', $cc_url,['http_errors' => false]);
            $cc_body = json_decode($response_cc->getBody(), true);

            $response_fc = $this->client->request('GET', $fc_url,['http_errors' => false]);
            $fc_body = json_decode($response_fc->getBody(), true);

            // check if success
            if($cc_body['cod'] != 200) {
                return false;
            }
            if($fc_body['cod'] != 200) {
                return false;
            }

            $currentfeed = [];

            foreach($cc_body['weather'] as $icondata) {
                $icon = $icondata['icon'];
                $description = $icondata['description'];
            }

            $currentfeed = [
                'Date'          => date("Y-m-d\TH:i:s\Z",$cc_body['dt']),
                'EpochDate'     => $cc_body['dt'],
                'Temp'          => $cc_body['main']['temp'],
                'Icon'          => 'http://openweathermap.org/img/wn/'.$icon.'@2x.png',
                'Description'   => $description
            ];

            $fcFeed = [];
            foreach($fc_body['list'] as $item) {
                foreach($item['weather'] as $innerdata) {
                    $innericon = $innerdata['icon'];
                    $innerdes = $innerdata['description'];
                }
                $fcFeed[] = [
                    'Date'          => date("Y-m-d\TH:i:s\Z",$item['dt']),
                    'EpochDate'     => $item['dt'],
                    'Min-Temp'      => $item['main']['temp_min'],
                    'Max-Temp'      => $item['main']['temp_max'],
                    'Icon'          => 'http://openweathermap.org/img/wn/'.$innericon.'@2x.png',
                    'Description'   => $innerdes
                ];
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
        catch (\GuzzleHttp\Exception\ClientException $e) {
            return $e;
        }
        catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return $e;
        }
        catch (\GuzzleHttp\Exception\ConnectException $e) {
            return $e;
        }
        catch (\GuzzleHttp\Exception\ServerException $e) {
            return $e;
        }
        catch (\Exception $e) {
            return $e;
        }
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
