<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;
use App\Http\Controllers\Functions;

class BuyAndSellController extends Controller
{
    private $client;
    private $function;
    private $carousell_url = 'https://www.carousell.ph/api-service/home/?count=20&countryID=1694008';

    public function __construct(Client $client, Functions $function) {
        $this->client   = $client;
        $this->function = $function;
    }

    public function getCarousell(Request $request) {
        
        // get country code, news apikey
        $langcode =  $this->function->getLanguageCode($request->languagecode);

        $carouselldata = $this->function->guzzleHttpCall($this->carousell_url);

        if($carouselldata == false) {
            return response()->json([
                'message'   => "Something went wrong on our side!",
                'result'    => false
            ]);
        }
        
        // check if there is result in the body
        if(array_key_exists('results',$carouselldata['data'])) {            
            $carousellfeed = [];
            $deatail = [];
            $location = [];
            $count = 0;
            foreach($carouselldata['data']['results'] as $cfeed) {
                if($count >= 10){
                    break;
                } 
                foreach($cfeed as $innercfeed) {
                    
                    $deatail = [];
                    foreach($innercfeed['belowFold'] as $belowFold) {
                        $deatail[] = [
                            // 'stringContent' => $belowFold['stringContent']
                            'stringContent' => $langcode === 'ph' ? $belowFold['stringContent'] : $this->function->translator($belowFold['stringContent'], $langcode),
                        ];
                    }
                    
                    $carousellfeed[] = [
                        'id'            =>  $innercfeed['id'],
                        'seller'        =>  $innercfeed['seller'],
                        'photoUrls'     =>  $innercfeed['photoUrls'],
                        'info'          =>  $deatail,
                        'location'      =>  $innercfeed['marketPlace']['name'],
                        'coordinates'   =>  $innercfeed['marketPlace']['location'],
                        'source'        =>  'Carousell'
                    ];
                }
                $count++;
            }
            return response()->json([
                'data'      => $carousellfeed,
                'result'    => true
            ]);
        } else {
            return response()->json([
            'message'   => 'Error getting data!',
            'result'    => false
            ]);
        }
    }

    public function getCarousellCountryID($countryid) {
        /**
         * malaysia     = 1733045
         * philippines  = 1694008
         * singapore    = 1880251
         * taiwan       = 1668284
         * new zealand  = 2186224
         * indonesia    = 1643084
         * hongkong     = 1819730
         * canada       = 6251999
         * australia    = 2077456
         */

        if($countryid == null) {
            $new_carousell_url = $this->carousell_url .'1694008';
            return $new_carousell_url;
        } else {
            $new_carousell_url = $this->carousell_url . $countryid;
            return $new_carousell_url;
        }
    }
}
