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
    private $function;

    public function __construct(Functions $function) {
        $this->function = $function;
    }

    public function feedNews(Request $request) {

        // get country code, news apikey
        $langcode =  $this->function->getLanguageCode($request->languagecode);
        $apikey =   $this->getKey();

        // lets create the newsapi url
        $newsapi_url = $this->url.'&apiKey='.$apikey;

        $httpcall = $this->function->guzzleHttpCall($newsapi_url);
        
        //get status
        if($httpcall['status'] !== 'ok' || !is_array($httpcall)) {
            return response()->json([
                'data'      => "Something went wrong on our side!",
                'result'    => false
            ]);
        }
        // lets build the json data and even translate if neccesary
        $newsfeed = [];
        $count = 0;

        foreach($httpcall['articles'] as $source) {
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
                'title'             =>  $langcode === 'ph' ? $title : $this->function->translator($title, $langcode),
                // 'title'             =>  $title,
                'description'       =>  $langcode === 'ph' ? $description : $this->function->translator($description, $langcode),
                // 'description'       =>  $description,
                'url'               =>  $url,
                'image'             =>  $image,
                'publishedAt'       =>  $this->function->timeLapse($publishedAt),
                // 'content'           =>  $this->checkifNull($content, $langcode),
            ];
            $count++;
        }

        return response()->json([
            'data'      => $newsfeed,
            'result'    => true
        ]);
    }

    private function getKey() {
        $key = env('NEWSAPIKEY');
        return $key;
    }

    protected function newsapi_category() {
        return array(
            'business', 'entertainment', 'general', 'health', 'science', 'sports', 'technology'
        );
    }
}
