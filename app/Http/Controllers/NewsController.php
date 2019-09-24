<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;

class NewsController extends Controller
{
    private $url = 'https://newsapi.org/v2/top-headlines?';

    public function feedNews(Request $request) {

        // get country code, news apikey
        $country = $this->newsapi_countrycode($request->countrycode);
        $apikey = $this->getKey();

        // lets create the newsapi url
        $newsapi_url = $this->url.'country='.$country.'&apiKey='.$apikey;
        
        // if($request->query != null) {
        //     $newsapi_url = $this->url.'country='.$country.'&q='.$request->query.'&apiKey='.$apikey;
        // }
        
        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->request('GET', $newsapi_url,['http_errors' => false]);
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

    protected function newsapi_countrycode($countrycode) {

        $c_code = 'ph'; 

        $array_code =  array(
           'au',  'ca', 'cn', 'hk', 'id', 'jp', 'kr', 'my', 'nz', 'ph', 'sg',
        );

        if($countrycode == null) {
            return $c_code;
        }

        $search = array_search($countrycode, $array_code);

        if($search != false) {
            return $countrycode;
        } else {
            return $c_code;
        }
    }

    protected function newsapi_category() {
        return array(
            'business', 'entertainment', 'general', 'health', 'science', 'sports', 'technology'
        );
    }
}
