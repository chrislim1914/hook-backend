<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Config;

class BuyAndSellController extends Controller
{
    private $client;
    private $function;
    private $countryID = '1694008';
    private $carousell_url = 'https://www.carousell.ph/api-service/home/?countryID=1694008';
    private $carousell_search_url = 'https://www.carousell.ph/api-service/filter/search/3.3/products/';

    /**
     * __contruct()
     * instantiate Functions class
     * 
     * @param Client $client, 
     * @param Functions $function
     */
    public function __construct(Client $client, Functions $function) {
        $this->client   = $client;
        $this->function = $function;
    }

    public function getCarousell(Request $request) {
        
        $carouselldata = $this->function->guzzleHttpCall($this->carousell_url.'&count=5');

        if($carouselldata == false) {
            return response()->json([
                'message'   => "Something went wrong on our side!",
                'result'    => false
            ]);
        }

        // check if there is result in the body
        if(array_key_exists('results',$carouselldata['data'])) {            
            $carousellfeed = [];
            $deatail = [];
            $location = [];
            foreach($carouselldata['data']['results'] as $cfeed) {
                foreach($cfeed as $innercfeed) {
                    
                    $deatail = [];
                    foreach($innercfeed['belowFold'] as $belowFold) {
                        $deatail[] = [
                            'stringContent' => $belowFold['stringContent']
                        ];
                    }
                    
                    $carousellfeed[] = [
                        'id'            =>  $innercfeed['id'],
                        'seller'        =>  $innercfeed['seller'],
                        'photoUrls'     =>  $innercfeed['photoUrls'],
                        'info'          =>  $deatail,
                        'location'      =>  $innercfeed['marketPlace']['name'],
                        'coordinates'   =>  $innercfeed['marketPlace']['location'],
                        'source'        =>  'Carousell'
                    ];
                }
            }
            return response()->json([
                'data'      => $carousellfeed,
                'result'    => true
            ]);
        } else {
            return response()->json([
                'message'   => "Something went wrong on our side!",
                'result'    => false
            ]);
        }
    }

    public function feedCarousell(Request $request) {

        $page = $request->page;
        $request->has('search') == true ? $search = $request->search : $search = '';
        $request->has('filter') == true ? $filter = $request->filter : $filter = '';
        
        $param = $this->createCarousellHeadandBody($page, $search, $filter);  

        // do cURL
        $resultdata = $this->carousellcURLCall($param);

        // check if there is result in the body and create output
        if($resultdata !== false) {
            $gotdata = $this->createCarousellData($resultdata, $page);
            return response()->json([
                'data'      => $gotdata['data'],
                'total'     => $gotdata['total'],
                'result'    => $gotdata['result'],
            ]);
        } else {
            return response()->json([
                'message'   => 'Something went wrong on our side!',
                'result'    => false
            ]);
        }
    }

    public function doCarousellSearch(Request $request) {

        $page = $request->page;
        $request->has('search') == true ? $search = $request->search : $search = '';
        $request->has('filter') == true ? $filter = $request->filter : $filter = '';
        
        $param = $this->createCarousellHeadandBody($page, $search, $filter);  

        // do cURL
        $resultdata = $this->carousellcURLCall($param);

        // check if there is result in the body and create output
        if($resultdata !== false) {
            $gotdata = $this->createCarousellData($resultdata, $page);
            return response()->json([
                'data'      => $gotdata['data'],
                'total'     => $gotdata['total'],
                'result'    => $gotdata['result'],
            ]);
        } else {
            return response()->json([
                'message'   => 'Something went wrong on our side!',
                'result'    => false
            ]);
        }
    }

    public function filterCarousell(Request $request) {
        
        $page = $request->page;
        $request->has('search') == true ? $search = $request->search : $search = '';
        $request->has('filter') == true ? $filter = $request->filter : $filter = '';
        
        $param = $this->createCarousellHeadandBody($page, $search, $filter);  

        // do cURL
        $resultdata = $this->carousellcURLCall($param);


        // check if there is result in the body and create output
        if($resultdata !== false) {
            $gotdata = $this->createCarousellData($resultdata, $page);
            return response()->json([
                'data'      => $gotdata['data'],
                'total'     => $gotdata['total'],
                'result'    => $gotdata['result'],
            ]);
        } else {
            return response()->json([
                'message'   => 'Something went wrong on our side!',
                'result'    => false
            ]);
        }
    }

    public function loadCarousellCategory() {
        $dog = config('corousell_category');

        $category = [];
        foreach ($dog as $cat => $mouse) {
            $category[] = [
                'name'  => $cat,
                'id'    => $mouse
            ];
        }
        return response()->json([
            'data'      => $category,
            'result'    => true
        ]); 
    }

    protected function carousellcURLCall(array $param) {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$param['url']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $param['header']);    
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param['data']);   

        $result = curl_exec ($ch);

        curl_close ($ch);

        $jsonlist = json_decode($result, true);

        if(array_key_exists('results', $jsonlist)) {
            return false;
        }

        return $jsonlist;
    }

    protected function createCarousellHeadandBody($page, $search, $filter) {
        // build URL
        $url = $this->carousell_search_url;

        // build page
        $page = $this->paginationTrick($page);

        // session
        $session = $this->getSession();

        // build body
        if($filter != '') {
            $data = json_encode(array(
                "count"         => $page,
                "countryId"     => $this->countryID,
                "session"       => $session,
                "query"         => $search,
                "filters"       => [ array(
                    "fieldName"     => "collections", 
                        "idsOrKeywords" => array(
                            "value"     => $filter
                        )
                    )                    
                ]
            ));
        } else {
            $data = json_encode(array(
                "count"         => $page,
                "countryId"     => $this->countryID,
                "session"       => $session,
                "query"         => $search
            ));
        }
        
        // build header
        $header = array(
            "content-type: application/json",
            "Content-Length: ".strlen($data),
            "Host: www.carousell.ph"
        );

        return array(
            'url'       => $url,
            'data'      => $data,
            'header'    => $header
        );

    }

    protected function createCarousellData($resultdata, $page) {

        $page = $this->paginationTrick($page);

        // let's get the total result of search query
        $totalquery = $resultdata['data']['total']['value']['low'];
        // lets create virtual pagination
        $endat = $page > $totalquery ? $totalquery : $page;
        $startfrom = $page > 10 ? $page - 9 : $page - 10 ;
        
       // let see if there are still data to output
       if(count($resultdata['data']['results']) <= 0 ) {
           return array(
                'data'      => [],
                'total'     => $totalquery,
                'result'    => true
           );
       }

       // json body to output
       $carouselljsonfeed = [];
       
       for($i = $startfrom; $i < count($resultdata['data']['results']); $i++){
           foreach($resultdata['data']['results'][$i] as $sfeed) {
               // for image "photos": []
               foreach($sfeed['photos'] as $imageurl) {
                   $image          = $imageurl['thumbnailUrl'];
                   $thumbnailimage = array_key_exists('thumbnailProgressiveUrl', $imageurl) == false ? $image : $imageurl['thumbnailProgressiveUrl'];
               }
               
               // for title
               $count=0;
               foreach($sfeed['belowFold'] as $titledesc) {
                   if($count == 0) {
                       $title          = $titledesc['stringContent'];
                       $titlenotrans   = $titledesc['stringContent'];
                       break;
                   }                    
                   $count++;
               }

               // for description
               $still=0;
               $snippet = [];
               foreach($sfeed['belowFold'] as $snippetdesc) {
                  
                   $snippet[]          = $snippetdesc['stringContent'];                                          
                   $still++;
               }

               $carouselljsonfeed[] = [
                   'id'                =>  $sfeed['id'],
                   'title'             =>  $title,
                   'snippet'           =>  $snippet,
                   'link'              =>  'https://www.carousell.ph/p/'.$this->treatTitle($titlenotrans).'-'.$sfeed['id'],
                   'image'             =>  $image,
                   'thumbnailimage'    =>  $thumbnailimage,
                   'source'        =>  'Carousell'
               ];
           }
       }
       return array(
            'data'      => $carouselljsonfeed,
            'total'     => $totalquery,
            'result'    => true
        );
    }

    protected function treatTitle($title) {
        if($title == null) {
            return $title;
        }
        // then replace blank space with +
        $treatTitle = str_replace(' ', '-', $title);

        return strtolower($treatTitle);
    }

    protected function paginationTrick($page) {
        if($page == null || $page == 0 || $page == 1 ) {
            return $page = 10;
        }

        return $page = $page * 10;
    }

    // no use as of now
    protected function getCarousellCountryID($countryid) {
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

    protected function getSession() {
        $carousell = $this->function->guzzleHttpCall($this->carousell_url);
        return $carousell['data']['session'];
    }
}
