<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Config;

class CarousellController extends Controller
{
    private $countryID = '1694008';
    private $carousell_url = 'https://www.carousell.ph/api-service/home/?countryID=1694008';
    private $carousell_search_url = 'https://www.carousell.ph/api-service/filter/search/3.3/products/';
    
    /**
     * method to display 5 post from Carousell.ph
     * 
     * @return Bool
     * @return $carousellfeed
     */
    public function getCarousell() {
        $function = new Functions();
        
        $carouselldata = $function->guzzleHttpCall($this->carousell_url.'&count=5');

        if($carouselldata == false) {
            return false;
        }

        // check if there is result in the body
        if(array_key_exists('results',$carouselldata['data'])) {   

            $carousellfeed = [];
            $deatail = [];
            $location = [];
            $count = 0;
            foreach($carouselldata['data']['results'] as $cfeed) {
                foreach($cfeed as $innercfeed) {
                    
                    $deatail = [];
                    foreach($innercfeed['belowFold'] as $belowFold) {
                        $deatail[] = [
                            'stringContent' => $belowFold['stringContent']
                        ];
                    }
                    
                    $carousellfeed[] = [
                        'no'            =>  $count,
                        'id'            =>  $innercfeed['id'],
                        'seller'        =>  $innercfeed['seller'],
                        'photoUrls'     =>  $innercfeed['photoUrls'],
                        'info'          =>  $deatail,
                        'source'        =>  'Carousell'
                    ];
                }
                $count++;
            }
            return $carousellfeed;
        } else {
            return false;
        }
    }

    /**
     * method to display 10 post per pagination
     * from carousell.ph
     * 
     * @param Request $request->page
     * @return JSON
     */
    public function feedCarousell($page) {
        
        $param = $this->createCarousellHeadandBody($page, '', '');  

        // do cURL
        $resultdata = $this->carousellcURLCall($param);

        // check if there is result in the body and create output
        if($resultdata !== false) {
            $gotdata = $this->createCarousellData($resultdata, $page);
            return $gotdata['data'];
        } else {
            return false;
        }
    }

    /**
     * method to do search from carousell.ph
     * display 10 post per pagination
     * 
     * @param Request $page, $search, $filter
     * @return JSON
     */
    public function doCarousellSearch($page, $search, $filter) {
        
        $param = $this->createCarousellHeadandBody($page, $search, $filter);  

        // do cURL
        $resultdata = $this->carousellcURLCall($param);

        // check if there is result in the body and create output
        if($resultdata !== false) {
            $gotdata = $this->createCarousellData($resultdata, $page);
            return array(
                'data'      => $gotdata['data'],
                'total'     => $gotdata['total'],
                'result'    => $gotdata['result'],
            );
        } else {
            return array(
                'data'      => 'Something went wrong on our side!',
                'result'    => false
            );
        }
    }

    /**
     * method to do search from carousell.ph
     * display 10 post per pagination
     * 
     * @param Request $request->page
     * @param Request $request->filter
     * @return JSON
     */
    public function filterCarousell($page, $search, $filter) {
                
        // lets check the id first before we mess around
        $checkid = $this->checkCategoryID($filter[0]);
        if(!$checkid) {
            return response()->json([
                'message'   => 'Category not found!',
                'result'    => false
            ]);
        }

        // ok then lets mess around
        $param = $this->createCarousellHeadandBody($page, $search, $filter);  

        // do cURL
        $resultdata = $this->carousellcURLCall($param);


        // check if there is result in the body and create output

        if(!$resultdata) {
            return false;
        } else {
            $gotdata = $this->createCarousellData($resultdata, $page);
            return $gotdata['data'];           
        }
    }

    /**
     * method to display all main carousell category only
     * 
     * @return JSON
     */
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

    /**
     * method to request POST method from carousell.ph using cURL
     * 
     * @param Array $param
     * $param['url']
     * $param['header']
     * $param['data']
     * 
     * @return Mix Bool/$jsonlist
     */
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

        if($jsonlist == null || array_key_exists('results', $jsonlist)) {
            return false;
        }

        return $jsonlist;
    }

    /**
     * method to create header and body param
     * 
     * @param $page, $search, $filter
     * @return Array
     */
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

    /**
     * method to create JSON data
     * 
     * @param $resultdata, $page
     * @return Array
     */
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
       $count=0;
       for($i = $startfrom; $i < count($resultdata['data']['results']); $i++){
           foreach($resultdata['data']['results'][$i] as $sfeed) {
               // for image "photos": []
               foreach($sfeed['photos'] as $imageurl) {
                   $image          = $imageurl['thumbnailUrl'];
                   $thumbnailimage = array_key_exists('thumbnailProgressiveUrl', $imageurl) == false ? $image : $imageurl['thumbnailProgressiveUrl'];
               }
               
               // for title
               $counttitle=0;
               foreach($sfeed['belowFold'] as $titledesc) {
                   if($counttitle == 0) {
                       $title          = $titledesc['stringContent'];
                       $titlenotrans   = $titledesc['stringContent'];
                       break;
                   }                    
                   $counttitle++;
               }

               // for description
               $still=0;
               $snippet = [];
               foreach($sfeed['belowFold'] as $snippetdesc) {
                  
                   $snippet[]          = $snippetdesc['stringContent'];                                          
                   $still++;
               }

               $carouselljsonfeed[] = [
                    'no'                =>  $count,
                    'id'                =>  $sfeed['id'],
                    'title'             =>  $title,
                    'snippet'           =>  $snippet,
                    'link'              =>  'https://www.carousell.ph/p/'.$this->treatTitle($titlenotrans).'-'.$sfeed['id'],
                    'image'             =>  $image,
                    'thumbnailimage'    =>  $thumbnailimage,
                    'source'            =>  'carousell'
               ];
           }
           $count++;
       }
       return array(
            'data'      => $carouselljsonfeed,
            'total'     => $totalquery,
            'result'    => true
        );
    }

    /**
     * method to create title name to be use as link
     * 
     * @param $title
     * @return $treatTitle
     */
    protected function treatTitle($title) {
        if($title == null) {
            return $title;
        }
        // then replace blank space with +
        $treatTitle = str_replace(' ', '-', $title);

        return strtolower($treatTitle);
    }

    /**
     * method to create pagination
     * actually there is no pagination
     * we just trick the count body param as our pagination
     * 
     * @param $page
     * @return $page
     */
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

    /**
     * method to get JWT from carousell.ph
     * 
     * @return $session
     */
    protected function getSession() {
        $function = new Functions();
        $session = $function->guzzleHttpCall($this->carousell_url);
        return $session['data']['session'];
    }

    /**
     * method to check if the filter(categoryid) is on the list
     * 
     * @param $id
     * @return Boolean
     */
    protected function checkCategoryID($id) {
        $catlist = in_array($id, config('corousell_category'), true);

        if($catlist) {
            return true;
        }
        return false;
    }
}
