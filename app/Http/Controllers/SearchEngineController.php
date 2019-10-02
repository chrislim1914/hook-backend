<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;
use Illuminate\Config;
use App\Http\Controllers\Functions;

class SearchEngineController extends Controller
{
    private $client;
    private $search_url = 'https://www.googleapis.com/customsearch/v1';
    private $function;

    public function __construct(Client $client, Functions $function) {
        $this->client = $client;
        $this->function = $function;
    }

    public function doSomeSearching(Request $request) {

        $treat = $this->treatSearchParam($request->search);

        if($request->engine == null){
            return response()->json([
                'message'   => 'search engine require!',
                'result'    => false
            ]);
        }elseif(is_array($treat)) {
            return response()->json([
                'message'   => $treat['message'],
                'result'    => false
            ]);
        }
        
        // get country code language localization
        $country =  $this->function->countrycodeforlanguage($request->countrycode);
        
        $keys = config('engine');
        $count = 0;

        do {
            
            $googleapikey = $keys['googleapikey'][$count];
            $engineid = $request->engine === 'google' ? $keys['googleengine'][$count] : $keys['carousellengine'][$count];

            // build the search url
            $template_url = $this->search_url .'?key='. $googleapikey .'&cx='. $engineid .'&q='. $treat . ($request->page == null ? '' : ($request->page == 1 ? '' : '&start='. $this->startPage($request->page)));
        
            $searching = $this->function->guzzleHttpCall($template_url);
            // return response()->json([
            //     'message'   => $searching,
            //     'result'    => false
            // ]);
            // exit;
            //get status
            if(!is_array($searching) || $searching == false) {
                return response()->json([
                    'message'   => "Something went wrong on our side!",
                    'result'    => false
                ]);
            }
            if(array_key_exists('error', $searching)) {                
                $count++;
                if($count>=2) {
                    return response()->json([
                        'message'   => 'Something went wrong on our side!',
                        'result'    => false
                    ]); 
                }
            } else {
                // create new json data
                foreach($searching['items'] as $newitem) {

                    if(array_key_exists('cse_image', $newitem['pagemap'])) {
                        foreach($newitem['pagemap']['cse_image'] as $innerthumbimage) {
                            $thumbnailimage = $innerthumbimage['src'];
                        };
                    } else {
                        $thumbnailimage = null;
                    }                    

                    if(array_key_exists('cse_thumbnail', $newitem['pagemap'])) {
                        foreach($newitem['pagemap']['cse_thumbnail'] as $innerimage) {
                            $image = $innerimage['src'];
                        };
                    } else {
                        $image = null;
                    }
                                        
                    $searchdata[] = [
                        'title'             => $country === 'ph' ? $newitem['title'] : $this->function->translator($newitem['title'], $country),
                        'link'              => array_key_exists('og:url', $newitem['pagemap']['metatags'][0]) ? $newitem['pagemap']['metatags'][0]['og:url'] : $newitem['link'],
                        'snippet'           => $country === 'ph' ? $newitem['snippet'] : $this->function->translator($newitem['snippet'], $country),
                        'image'             => $image,
                        'thumbnailimage'    => $thumbnailimage,
                    ];
                }
                
                return response()->json([
                    'data'  => $searchdata,
                    'result'=> true
                ]);
            }
            
        } while ($count < 2);
    }

    protected function treatSearchParam($search) {

        $res = '';
        $len = strlen($search);

        if($len >= 1000) {
            $res =  array(
                'message'   => 'character exceed its limit!'
            );

            return $res; 
        }
        // lets check if the param is empty
        if($search == null) {
            $res = array(
                'message'   => 'search string is empty!'
            );

            return $res; 
        }
        if(gettype($search) != 'string') {

            $res = array(
                'message'   => 'data type not supprted!'
            );

            return $res;
        }

        // then replace blank space with +
        $newsearch_str = str_replace(' ', '+', $search);

        return $newsearch_str;
    }

    protected function startPage($pagenum) {
        $start = ($pagenum * 10) -10;

        return $start;
    }
}
