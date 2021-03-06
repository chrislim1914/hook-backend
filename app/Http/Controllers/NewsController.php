<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\ScrapController;
use Goutte\Client;

class NewsController extends Controller
{
    private $url            = 'https://newsapi.org/v2/top-headlines?country=ph&pageSize=10';
    private $everything_url = 'https://newsapi.org/v2/everything?domains=abs-cbn.com,rappler.com,gmanetwork.com&sortBy=popularity&pageSize=10';
    private $world_url      = 'https://newsapi.org/v2/everything?domains=bbc.com,cnn.com,aljazeera.com&sortBy=popularity&pageSize=10';
    private $function;
    private $apikey;

    /**
     * __contruct()
     * instantiate Functions class
     * 
     * @param Functions $function
     */
    public function __construct(Functions $function) {
        $this->function = $function;
        $this->apikey   = $this->getKey();
    }

    /**
     * method to display the scraped news
     * 
     * @param $request
     * @return JSON
     */
    public function viewNewsArticle(Request $request) {
        $newsscrapper = new ScrapController();
        $countrycode = $this->function->isThereCountryCode($request);

        switch ($request->agency) {
            case 'Businessmirror.com.ph':
                $viewnews = $newsscrapper->scrapBusinessMirror($request->url, $countrycode);                
                return response()->json([
                    'data'      => $viewnews['body'],
                    'result'    => $viewnews['result']
                ]);
            case 'Rappler.com':
                $viewnews = $newsscrapper->scrapRapplerNews($request->url, $countrycode);                
                return response()->json([
                    'data'      => $viewnews['body'],
                    'result'    => $viewnews['result']
                ]);
            case 'Abs-cbn.com':
                $viewnews = $newsscrapper->scrapAbsCbnNews($request->url, $countrycode);                
                return response()->json([
                    'data'      => $viewnews['body'],
                    'result'    => $viewnews['result']
                ]);
            case 'Cnnphilippines.com':
                $viewnews = $newsscrapper->scrapCnnPhilNews($request->url, $countrycode);        
                return response()->json([
                    'data'      => $viewnews['body'],
                    'result'    => $viewnews['result']
                ]);
            case 'Mb.com.ph':
                $viewnews = $newsscrapper->scrapMBNews($request->url, $countrycode);                
                return response()->json([
                    'data'      => $viewnews['body'],
                    'result'    => $viewnews['result']
                ]);
            case 'Gmanetwork.com':
                $viewnews = $newsscrapper->scrapGmaNews($request->url, $countrycode);                
                return response()->json([
                    'data'      => $viewnews['body'],
                    'result'    => $viewnews['result']
                ]);
            case 'Bworldonline.com':
                $viewnews = $newsscrapper->scrapBWNews($request->url, $countrycode);                
                return response()->json([
                    'data'      => $viewnews['body'],
                    'result'    => $viewnews['result']
                ]);
            case 'CNN':
                $viewnews = $newsscrapper->scrapCnnInt($request->url, $countrycode);                
                return response()->json([
                    'data'      => $viewnews['body'],
                    'result'    => $viewnews['result']
                ]);
            case 'Bbc.com':
                $viewnews = $newsscrapper->scrapBbc($request->url, $countrycode);                
                return response()->json([
                    'data'      => $viewnews['body'],
                    'result'    => $viewnews['result']
                ]);
            case 'Al Jazeera English':
                $viewnews = $newsscrapper->scrapAljazeera($request->url, $countrycode);                
                return response()->json([
                    'data'      => $viewnews['body'],
                    'result'    => $viewnews['result']
                ]);
            default:
                return array(
                    'body'      => "Something went wrong on our side!",
                    'result'    => false
                );
        }
    }

    /**
     * method to display top headlines for hook front page
     * 
     * @param Request $request
     * @return JSON
     */
    public function feedNews(Request $request) {
         // get the countrycode
        $countrycode = $this->function->isThereCountryCode($request);

        // lets create the newsapi url
        $newsapi_url = $this->url.'&apiKey='.$this->apikey;
        $newsapi_url_page2 = $this->url.'&apiKey='.$this->apikey.'&page=2';

        $httpcall = $this->function->guzzleHttpCall($newsapi_url);
        $httpcall_page2 = $this->function->guzzleHttpCall($newsapi_url_page2);
        
        //get status
        if($httpcall['status'] !== 'ok' || !is_array($httpcall)) {
            return response()->json([
                'data'      => "Something went wrong on our side!",
                'result'    => false
            ]);
        }
        
        $headlines = $this->createNewsJsonBody($httpcall);
        $headlines_page2 = $this->createNewsJsonBody($httpcall_page2);
        for($i=0;$i<count($headlines_page2); $i++) {
            array_push($headlines,  $headlines_page2[$i]);
        }
        
        // if(count($headlines) < 3) {
        //         $httpcall = $this->function->guzzleHttpCall($newsapi_url.'&page=2');
            
        //         //get status
        //         if($httpcall['status'] !== 'ok' || !is_array($httpcall)) {
        //             return response()->json([
        //                 'data'      => "Something went wrong on our side!",
        //                 'result'    => false
        //             ]);
        //     }
            
        //     $headlines = $this->createNewsJsonBody($httpcall);
        // }

        if(!$countrycode){
            return response()->json([
                'data'      => $headlines,
                'result'    => true
            ]);
        }

        $trans = $this->translateFeedNews($headlines, $countrycode);

        return response()->json([
            'data'      => $trans,
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
        // get the countrycode
        $countrycode = $this->function->isThereCountryCode($request);

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
        if($request->category === 'top_stories') {
            $newsapi_url = $this->url.'&apiKey='.$this->apikey.'&page='.$page;
        } elseif($request->category === 'local') {
            $newsapi_url = $this->everything_url.'&apiKey='.$this->apikey.'&page='.$page;
        } elseif($request->category === 'world') {
            $newsapi_url = $this->world_url.'&apiKey='.$this->apikey.'&page='.$page;
        } else {
            $newsapi_url = $this->url.'&apiKey='.$this->apikey.'&category='.$request->category.'&page='.$page;
        }

        $httpcall = $this->function->guzzleHttpCall($newsapi_url);

        //get status
        if($httpcall['status'] !== 'ok' || !is_array($httpcall)) {
            return response()->json([
                'message'   => "Something went wrong on our side!",
                'result'    => false
            ]);
        }

        $newsjson = $this->createNewsJsonBody($httpcall);

        if(!$countrycode){
            return response()->json([
                'data'          => $newsjson,
                'totalResults'  => $httpcall['totalResults'],
                'result'        => true
            ]);
        }

        $trans = $this->translateFeedNews($newsjson, $countrycode);

        return response()->json([
            'data'          => $trans,
            'totalResults'  => $httpcall['totalResults'],
            'result'        => true
        ]);
    }
    
    /**
     * method to translate feedNews()
     * 
     * @param $newsdata, $countrycode
     * @return $translateData
     */
    protected function translateFeedNews($newsdata, $countrycode) {

        if($countrycode === 'en') {
            return $newsdata;
        }

        $translateData = [];

        foreach($newsdata as $source) {            
            $translateData[] = [
                'Source'            =>  $source['Source'],
                'author'            =>  $source['author'],
                'title'             =>  $this->function->translator($source['title'], $countrycode),
                'description'       =>  $this->function->translator($source['description'], $countrycode),
                'url'               =>  $source['url'],
                'image'             =>  $source['image'],
                'publishedAt'       =>  $this->function->timeLapse($source['publishedAt'])
            ];
        }

        return $translateData;
    }

    /**
     * method to create json body for newsapi.org feed data
     * 
     * @param $newsbody
     * @return $newsfeed
     */
    protected function createNewsJsonBody($newsbody) {
        
        // lets build the json data and even translate if neccesary
        $newsfeed = [];
        // $feedcount = 0;
        foreach($newsbody['articles'] as $source) {

            // filter supported news agency
            // if($feedcount >= 10) {
            //     break;
            if($this->supportedNewsAgency($source['source']['name']) == true ) {
                $newsource      = $source['source']['name'];
                $author         = $source['author'];
                $title          = $source['title'];
                $description    = $source['description'];
                $url            = $source['url'];
                $image          = $source['urlToImage'];
                $publishedAt    = $source['publishedAt'];
                
                $newsfeed[] = [
                    'Source'            =>  $newsource,
                    'author'            =>  $author,
                    'title'             =>  $title,
                    'description'       =>  $description,
                    'url'               =>  $url,
                    'image'             =>  $image,
                    'publishedAt'       =>  $this->function->timeLapse($publishedAt)
                ];
            // $feedcount++;
            }            
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
     * @return Boolean
     */
    public function newsapi_category($category) {
        $categories =  array(
            'business', 'entertainment', 'local', 'health', 'science', 'sports', 'technology', 'top_stories', "world"
        );

        $value = gettype(array_search($category, $categories));
        if($value == 'integer') {
            return true;
        } else {
            return false;
        }        
    }

    /**
     * supported news agency to display
     * 
     * @param $agency
     * @return Boolean
     */
    protected function supportedNewsAgency($agency) {
        $newsagency = array(
            // local news
            'Rappler.com',
            'Cnnphilippines.com',
            'Abs-cbn.com',
            // 'Gmanetwork.com',
            'Mb.com.ph',
            'Bworldonline.com',
            'Businessmirror.com.ph',
            // international news
            'CNN',
            'Bbc.com',
            'Al Jazeera English'
        );
        $value = gettype(array_search($agency, $newsagency));
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
