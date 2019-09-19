<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;

class NewsController extends Controller
{
    private $url = 'https://newsapi.org/v2/top-headlines?';
    private $country = 'ph';

    public function feedNews() {
        $apikey = $this->getKey();
        $url = $this->url;
        $country = $this->country;

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->request('GET', $url.'country='.$country.'&apiKey='.$apikey,['http_errors' => false]);
            $body = json_decode($response->getBody(), true);

            //get status
            $status = $response->getStatusCode();
            return response()->json([
                'data'   => $body,
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

    private function getKey() {
        $key = env('NEWSAPIKEY');
        return $key;
    }
}
