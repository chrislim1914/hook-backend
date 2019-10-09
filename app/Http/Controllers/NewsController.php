<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;
use App\Http\Controllers\Functions;

class NewsController extends Controller
{
    private $url = 'https://newsapi.org/v2/top-headlines?country=ph&pageSize=5';
    private $function;

    public function __construct(Functions $function) {
        $this->function = $function;
    }

    /**
     * method to display top headlines for hook front page
     * 
     * @param Request $request
     * @return JSON
     */
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
        
        $headlines = $this->createNewsJsonBody($httpcall, $langcode);

        return response()->json([
            'data'      => $headlines,
            'result'    => true
        ]);
    }

    /**
     * method to display news with category
     * 
     * @param Request $request
     * @return JSON
     */
    public function feedNewsByCategory(Request $request) {
        // get country code, news apikey
        $langcode =  $this->function->getLanguageCode($request->languagecode);
        $apikey =   $this->getKey();

        // check input category and page
        $cat = $this->newsapi_category($request->category);
        $page = $this->newsapi_page($request->page);
        if($cat == false) {
            return response()->json([
                'message'   => "Category not found!",
                'result'    => false
            ]);
        }

        // lets create the newsapi url
        if($cat === 'top_stories') {
            $newsapi_url = $this->url.'&apiKey='.$apikey.'&page='.$page;
        } else {
            $newsapi_url = $this->url.'&apiKey='.$apikey.'&category='.$request->category.'&page='.$page;
        }

        $httpcall = $this->function->guzzleHttpCall($newsapi_url);
        //get status
        if($httpcall['status'] !== 'ok' || !is_array($httpcall)) {
            return response()->json([
                'message'      => "Something went wrong on our side!",
                'result'    => false
            ]);
        }

        $newsjson = $this->createNewsJsonBody($httpcall, $langcode);

        return response()->json([
            'data'          => $newsjson,
            'totalResults'  => $httpcall['totalResults'],
            'result'        => true
        ]);
    }

    protected function createNewsJsonBody($newsbody, $langcode) {
        
        // lets build the json data and even translate if neccesary
        $newsfeed = [];
        
        foreach($newsbody['articles'] as $source) {
            $newsource      = $source['source']['name'];
            $author         = $source['author'];
            $title          = $langcode == 'en' ? $source['title'] : $this->function->translator($source['title'], $langcode);
            $description    = $langcode == 'en' ? $source['description'] : $this->function->translator($source['description'], $langcode);
            $url            = $source['url'];
            $image          = $source['urlToImage'];
            $publishedAt    = $source['publishedAt'];
            $content        = $source['content'];
            
            $newsfeed[] = [
                'Source'            =>  $newsource,
                'author'            =>  $author,
                'title'             =>  $title,
                'description'       =>  $description,
                'url'               =>  $url,
                'image'             =>  $image,
                'publishedAt'       =>  $this->function->timeLapse($publishedAt)
            ];
        }

        return $newsfeed;
    }

    /**
     * method to retrieved news api key
     * 
     * @return $key
     */
    private function getKey() {
        $key = env('NEWSAPIKEY');
        return $key;
    }

    /**
     * method to check input category if in the list
     * 
     * @param $category
     * @return $cat
     */
    public function newsapi_category($category) {
        $categories =  array(
            'business', 'entertainment', 'local', 'health', 'science', 'sports', 'technology', 'top_stories'
        );

        $value = gettype(array_search($category, $categories));
        if($value == 'integer') {
            return true;
        } else {
            return false;
        }        
    }

    /**
     * method to check pagination number
     * 
     * @param $page
     * @return $page
     */
    private function newsapi_page($page) {
        if($page == null || $page == 1 || $page == 0 || $page == '' ) {
            return $page = '1';
        }
        
        return $page;
    }
}
