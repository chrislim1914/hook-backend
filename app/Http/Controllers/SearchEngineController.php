<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;
use Illuminate\Config;

class SearchEngineController extends Controller
{
    private $client;
    private $search_url = 'https://www.googleapis.com/customsearch/v1';

    public function __construct(Client $client) {
        $this->client = $client;
    }

    public function doSomeSearching(Request $request) {
        
        // lets check if the param is empty
        if($request->search == null) {
            return response()->json([
                'message'   => '',
                'result'    => false
            ]);
        }elseif($request->engine == null){
            return response()->json([
                'message'   => '',
                'result'    => false
            ]);
        }

        // get the credentials
        // $credential = $this->getCredential();
        // $googleapikey = $credential['googleapikey'];
        // $engineid = $request->engine === 'google' ? $credential['googlesearchengineid'] : $credential['carousellsearchengineid'];

        
        try {
            $keys = config('engine');
            $count = 0;
            do {
                if($count>=2) {
                    return response()->json([
                        'message'   => $engineid,
                        'result'    => false
                    ]); 
                }
                $googleapikey = $keys['googleapikey'][$count];
                $engineid = $request->engine === 'google' ? $keys['googleengine'][$count] : $keys['carousellengine'][$count];

                // build the search url
                $template_url = $this->search_url .'?key='. $googleapikey .'&cx='. $engineid .'&q='. $request->search . ($request->page == null ? '' : ($request->page == 1 ? '' : '&start='. $this->startPage($request->page)));
            
                $response = $this->client->request('GET', $template_url,['http_errors' => false]);
                $body = json_decode($response->getBody(), true);
                
                if(array_key_exists('error', $body)) {
                    $count++;
                } else {
                    return response()->json([
                        'data'  => $body,
                        'result'=> true
                    ]);
                }
                
            } while ($count < 2);
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

    protected function startPage($pagenum) {
        $start = ($pagenum * 10) -10;

        return $start;
    }

    protected function getCredential() {
        $gapikey            = env('GOOGLEAPIKEY');
        $gsearchengineid    = env('GOOGLESEARCHENGINEID');
        $csearchengineid    = env('CAROUSELLSEARCHENGINEID');

        return array(
            'googleapikey'              => $gapikey,
            'googlesearchengineid'      => $gsearchengineid,
            'carousellsearchengineid'   => $csearchengineid
        );
    }
}
