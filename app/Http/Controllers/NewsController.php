<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;
use App\Http\Controllers\Functions;

class NewsController extends Controller
{
    private $url = 'https://newsapi.org/v2/top-headlines?country=ph';

    public function feedNews(Request $request) {

        // get country code, news apikey
        $country =  $this->newsapi_countrycode($request->countrycode);
        $apikey =   $this->getKey();

        // lets create the newsapi url
        $newsapi_url = $this->url.'&apiKey='.$apikey;

        // call the Functions Class
        $function = new Functions();
                
        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->request('GET', $newsapi_url,['http_errors' => false]);
            $newsbody = json_decode($response->getBody(), true);

            //get status
            if($newsbody['status'] !== 'ok') {
                return response()->json([
                    'data'      => "Something went wrong on our side!",
                    'result'    => false
                ]);
            }

            // lets build the json data and even translate if neccesary
            $newsfeed = [];
            $count = 0;
            foreach($newsbody['articles'] as $source) {
                if($count >=5){
                    break;
                } 
                $newsource      = $source['source']['name'];
                $author         = $source['author'];
                $title          = $source['title'];
                $description    = $source['description'];
                $url            = $source['url'];
                $image          = $source['urlToImage'];
                $publishedAt    = $source['publishedAt'];
                $content        = $source['content'];
                
                $newsfeed[] = [
                    'Source'            =>  $newsource,
                    'author'            =>  $author,
                    'title'             =>  $country === 'ph' ? $title : $function->translator($title, $country),
                    // 'title'             =>  $title,
                    'description'       =>  $country === 'ph' ? $description : $function->translator($description, $country),
                    // 'description'       =>  $description,
                    'url'               =>  $url,
                    'image'             =>  $image,
                    'publishedAt'       =>  $function->timeLapse($publishedAt),
                    // 'content'           =>  $this->checkifNull($content, $country),
                ];
                $count++;
            }

            return response()->json([
                'data'      => $newsfeed,
                'result'    => true
            ]);
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            return response()->json([
                'message'   => 'Something went wrong on our side!',
                'result'    => false
            ]);
        }
        catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return response()->json([
                'message'   => 'Something went wrong on our side!',
                'result'    => false
            ]);
        }
        catch (\GuzzleHttp\Exception\ConnectException $e) {
            return response()->json([
                'message'   => 'Something went wrong on our side!',
                'result'    => false
            ]);
        }
        catch (\GuzzleHttp\Exception\ServerException $e) {
            return response()->json([
                'message'   => 'Something went wrong on our side!',
                'result'    => false
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'message'   => 'Something went wrong on our side!',
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
           'jp' =>  'ja',
           'kr' =>  'ko',
           'cn' =>  'zh',
           'ph' =>  'en'
        );

        if($countrycode == null) {
            return $c_code;
        }
        
        foreach($array_code as $key => $value) {
            if($key === $countrycode) {
                $c_code = $value;
                return $c_code;
            }
        }

        return $c_code;
    }

    protected function newsapi_category() {
        return array(
            'business', 'entertainment', 'general', 'health', 'science', 'sports', 'technology'
        );
    }
}
