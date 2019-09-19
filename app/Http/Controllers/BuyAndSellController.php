<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;

class BuyAndSellController extends Controller
{
    private $client;

    public function __construct(Client $client) {
        $this->client = $client;
    }

    public function getCarousell() {

        $url = 'https://www.carousell.ph/api-service/home/?count=20&countryID=1694008';

        try {
            $response = $this->client->request('GET', $url,['http_errors' => false]);
            $body = json_decode($response->getBody(), true);
            
            //get status
            $status = $response->getStatusCode();
            return response()->json([
                'data'   => $body['data']['results'],
                'result'    => true
            ]);
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            return response()->json([
                'message'   => '',
                'result'    => false
            ]);
        }
        catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return response()->json([
                'message'   => '',
                'result'    => false
            ]);
        }
        catch (\GuzzleHttp\Exception\ConnectException $e) {
            return response()->json([
                'message'   => '',
                'result'    => false
            ]);
        }
        catch (\GuzzleHttp\Exception\ServerException $e) {
            return response()->json([
                'message'   => '',
                'result'    => false
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'message'   => '',
                'result'    => false
            ]);
        }
    }

    public function getLazada() {
        
    }
}
