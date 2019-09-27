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
    private $carousell_url = 'https://www.carousell.ph/api-service/home/?count=20&countryID=1694008';

    public function __construct(Client $client) {
        $this->client = $client;
    }

    public function getCarousell(Request $request) {
        
        // $cid = $this->getCarousellCountryID($request->countryid);
        // call the Functions Class
        $function = new Functions();

        try {
            $response = $this->client->request('GET', $this->carousell_url,['http_errors' => false]);
            $body = json_decode($response->getBody(), true);

            // check if there is result in the body
            if(array_key_exists('results',$body['data'])) {
                
                $carousellfeed = [];
                $deatail = [];
                $location = [];
                $count = 0;
                foreach($body['data']['results'] as $cfeed) {
                    if($count >= 5){
                        break;
                    } 
                    foreach($cfeed as $innercfeed) {
                        
                        foreach($innercfeed['belowFold'] as $belowFold) {
                            $deatail[] = [
                                // 'stringContent' => $function->translator($belowFold['stringContent'], $request->countrycode)
                                'stringContent' => $belowFold['stringContent']
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
        catch (\GuzzleHttp\Exception\ClientException $e) {
            return response()->json([
                'message'   => $e,
                'result'    => false
            ]);
        }
        catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return response()->json([
                'message'   => $e,
                'result'    => false
            ]);
        }
        catch (\GuzzleHttp\Exception\ConnectException $e) {
            return response()->json([
                'message'   => $e,
                'result'    => false
            ]);
        }
        catch (\GuzzleHttp\Exception\ServerException $e) {
            return response()->json([
                'message'   => $e,
                'result'    => false
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'message'   => $e,
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
