<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;

class WeatherCpntroller extends Controller
{
    private $client;

    // accuweather url for forecast
    private $url = 'http://dataservice.accuweather.com/forecasts/v1/daily/5day';

    // accuweather url for current condition
    private $current_condition = 'http://dataservice.accuweather.com/currentconditions/v1';

    // accuweather to get location info
    private $locationAPI = 'http://dataservice.accuweather.com/locations/v1/cities/ipaddress';

    // instantiate GuzzleHttp\Client
    public function __construct(Client $client) {
        $this->client = $client;
    }

    public function getWeatherdata() {

        // location info
        $locationdatadata = $this->getlocationdata();

        if(!is_array($locationdatadata)) {
            return response()->json([
                'message'   => 'Error in getting user location data!',
                'result'    => false
            ]);
        }

        
        $locationkey = $locationdatadata['Key'];
        $localizedName = $locationdatadata['Country']['LocalizedName'];

        // forecast info
        $forecastdata = $this->get5dayForecast($locationkey, $localizedName);
        // var_dump($forecastdata); exit;
        // current condition info
        $currenconditiondata = $this->getCurrentCondition($locationkey);

        foreach($currenconditiondata as $newCCdata) {
            $currentfeed = [
                'Date'          => $newCCdata['LocalObservationDateTime'],
                'EpochDate'     => $newCCdata['EpochTime'],
                'Temp'          => $newCCdata['Temperature']['Metric']['Value'],
                'Icon'          => 'img/accuweather-img/'.$newCCdata['WeatherIcon'].'-s.png',
                'Description'   => $newCCdata['WeatherText']
            ];
        }

        $weatherFeed = [];

        foreach($forecastdata['DailyForecasts'] as $item) {
            $weatherFeed[] = [
                'Date'          => $item['Date'],
                'EpochDate'     => $item['EpochDate'],
                'Min-Temp'      => $this->convertFtoC($item['Temperature']['Minimum']['Value']),
                'Max-Temp'      => $this->convertFtoC($item['Temperature']['Maximum']['Value']),
                'Icon'          => 'img/accuweather-img/'.$item['Day']['Icon'].'-s.png',
                'Description'   => $item['Day']['IconPhrase']
            ];
        }

        $weatherdata = [
            'Current_Condition' => $currentfeed,
            'Forecast'  => $weatherFeed
        ];

        return response()->json([
            'data'      => $weatherdata,
            'result'    => false
        ]);

    }

    /**
     * method to get user location
     * 
     * @return JSON $body
     */
    protected function getlocationdata() {
        $apikey = $this->getKey();

        $getlocationdata_url = $this->locationAPI.'?apikey='.$apikey;

        try {
            $response = $this->client->request('GET', $getlocationdata_url,['http_errors' => false]);
            $body = json_decode($response->getBody(), true);
            
            return $body;
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

    /**
     * method to get accuweather 5 days weather Forecast
     * 
     * @param $lkey
     * @return JSON $body
     */
    protected function get5dayForecast($lkey, $localizedName) {
        $apikey = $this->getKey();
        $gotlang = $this->getLocalizedLanguage($localizedName);
        // lets build the url
        $accuweather_url = $this->url.'/'.$lkey.'?language='.$gotlang.'&apikey='.$apikey;

        // do the GET method call
        try {
            $response = $this->client->request('GET', $accuweather_url,['http_errors' => false]);
            $bodyForecast = json_decode($response->getBody(), true);
            
            return $bodyForecast;
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

    /**
     * method to get accuweather current weather condition
     * 
     * @param $lkey
     * @return JSON $body
     */
    protected function getCurrentCondition($lkey) {
        $apikey = $this->getKey();

        $currentcondition_url = $this->current_condition. '/' .$lkey. '?apikey=' .$apikey;

        try {
            $response = $this->client->request('GET', $currentcondition_url,['http_errors' => false]);
            $bodyCurrentCondition = json_decode($response->getBody(), true);
            
            return $bodyCurrentCondition;
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

    /**
     * method to get user localize language
     * 
     * if not found the default language will be EN
     * 
     * @param $country
     * @return MIX
     */
    protected function getLocalizedLanguage($country) {
        if($isexist = isset($this->supportedLanguage['$country'])) {
            return $this->supportedLanguage['$country'];
        }

        return 'en';
    }

    /**
     * constant list of language supported by accuweather API
     * 
     * @return array
     */
    protected function supportedLanguage() {
        return  array(
            'Algeria'                       => 'ar-dz',
            'Bahrain'                       => 'ar-bh',
            'Egypt'                         => 'ar-eg',
            'Iraq'                          => 'ar-iq',
            'Jordan'                        => 'ar-jo',
            'Kuwait'                        => 'ar-kw',
            'Lebanon'                       => 'ar-lb',
            'Libya'                         => 'ar-ly',
            'Morocco'                       => 'ar-ma',
            'Oman'                          => 'ar-om',
            'Qatar'                         => 'ar-qa',
            'Saudi Arabia'                  => 'ar-sa',
            'Sudan'                         => 'ar-sd',
            'Syria'                         => 'ar-sy',
            'Tunisia'                       => 'ar-tn',
            'U.A.E.'                        => 'ar-ae',
            'Yemen'                         => 'ar-ye',
            'Azerbaijan'                    => 'az-latn-az',
            'Bangladesh'                    => 'bn-bd',
            'India'                         => 'bn-in',
            'Bosnia and Herzegovina'        => 'bs-ba',
            'Bulgaria'                      => 'bg-bg',
            'Spain'                         => 'ca-es',
            'Hong Kong'                     => 'zh-hans-hk',
            'China'                         => 'zh-hans-cn',
            'Singapore'                     => 'zh-hans-sg',
            'Taiwan'                        => 'zh-hant-tw',
            'Croatia'                       => 'hr-hr',
            'Czech Republic'                => 'cs-cz',
            'Denmark'                       => 'da-dk',
            'Aruba'                         => 'nl-aw',
            'Belgium'                       => 'nl-be',
            'Curacao'                       => 'nl-cw',
            'Netherlands'                   => 'nl-nl',
            'Sint Maarten'                  => 'nl-sx',
            'Estonia'                       => 'et-ee',
            'Philippines'                   => 'en',
            'Afghanistan'                   => 'fa-af',
            'Iran'                          => 'fa-ir',
            'Finland'                       => 'fi-fi',
            'Benin'                         => 'fr-bj',
            'Burkina Faso'                  => 'fr-bf',
            'Burundi'                       => 'fr-bi',
            'Cameroon'                      => 'fr-cm',
            'Canada'                        => 'en',
            'United States'                 => 'en',
            'Central African Republic'      => 'fr-cf',
            'Chad'                          => 'fr-td',
            'Comoros'                       => 'fr-km',
            'Germany'                       => 'de-de',
            'Cyprus'                        => 'el-cy',
            'Greece'                        => 'el-gr',
            'Israel'                        => 'he-il',
            'Hungary'                       => 'hu-hu',
            'Iceland'                       => 'is-is',
            'Indonesia'                     => 'id-id',
            'Italy'                         => 'it-it',
            'Japan'                         => 'ja-jp',
            'Kazakhstan'                    => 'kk-kz',
            'South Korea'                   => 'ko-kr',
            'Latvia'                        => 'lv-lv',
            'Lithuania'                     => 'lt-lt',
            'Macedonia'                     => 'mk-mk',
            'Brunei'                        => 'ms-bn',
            'Malaysia'                      => 'ms-my',
            'Poland'                        => 'pl-pl',
            'Angola'                        => 'pt-ao',
            'Brazil'                        => 'pt-br',
            'Cape Verde'                    => 'pt-cv',
            'Guinea-Bissau'                 => 'pt-gw',
            'Mozambique'                    => 'pt-mz',
            'Portugal'                      => 'pt-pt',
            'Sao Tome and Principe'         => 'pt-st',
            'Romania'                       => 'ro-ro',
            'Russia'                        => 'ru-ru',
            'Ukraine'                       => 'uk-ua',
            'Bosnia and Herzegovina'        => 'sr-latn-ba',
            'Montenegrin'                   => 'sr-me',
            'Serbia'                        => 'sr-rs',
            'Slovakia'                      => 'sk-sk',
            'Slovenia'                      => 'sl-sl',
            'Argentina'                     => 'es-ar',
            'Mexico'                        => 'es-mx',
            'Democratic Republic of the Congo'  => 'sw-cd',
            'Kenya'                         => 'sw-ke',
            'Tanzania'                      => 'sw-tz',
            'Uganda'                        => 'sw-ug',
            'Finland'                       => 'sv-fi',
            'Sweden'                        => 'sv-se',
            'Sri Lanka'                     => 'ta-lk',
            'Thailand'                      => 'th-th',
            'Turkey'                        => 'tr-tr',
            'Uzbekistan'                    => 'uz-latn-uz',
            'Vietnam'                       => 'vi-vn'
        );
    }

    /**
     * method to convert F to C
     * 
     * @param $temp (F)
     * @return $celsius
     */
    protected function convertFtoC($temp) {
        $celsius = (($temp - 32) * 5 ) / 9;

        return number_format((float)$celsius, 2, '.', '');
    }

    /**
     * method to get accuweather key on .env
     * 
     * @return $key
     */
    private function getKey() {
        $key = env('ACCUWEATHERKEY');
        return $key;
    }
}
