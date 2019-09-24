<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;

class BuyAndSellController extends Controller
{
    private $client;
    private $carousell_url = 'https://www.carousell.ph/api-service/home/?count=20&countryID=';

    public function __construct(Client $client) {
        $this->client = $client;
    }

    public function getCarousell(Request $request) {
        
        $cid = $this->getCarousellCountryID($request->countryid);       
        
        try {
            $response = $this->client->request('GET', $cid,['http_errors' => false]);
            $body = json_decode($response->getBody(), true);
            
            //get status
            // $status = $response->getStatusCode();
            // var_dump($status);
            return response()->json([
                'data'   => $body['data']['results'],
                'result'    => true
            ]);
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

    public function getLazada() {

    }
}
