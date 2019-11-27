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
    private $carousell_detail_url = 'https://www.carousell.ph/api-service/listing/3.1/listings/';
    private $carousell_related_url = 'https://www.carousell.ph/api-service/related-listing/';

    public function viewCarousell($id) {
        $param = array(
            'url'   => $this->carousell_detail_url.$id.'/detail/'
        );

        $resultdata = $this->carousellcURLCall($param);

        if(!$resultdata) {
            return false;
        }
        $newcarousell_item = [];

        foreach($resultdata as $car) {

            $seller = [];
            $media = [];
            foreach($car['screens'] as $find) {
                // get seller info
                $seller = [
                    'id'            => $find['meta']['default_value']['seller']['id'],
                    'username'      => $find['meta']['default_value']['seller']['username'],
                    'profile_photo' => $find['meta']['default_value']['seller']['profile']['image_url']
                ];

                // get media image
                for($i=0;$i<count($find['meta']['default_value']['photos']);$i++) {
                    $media[] = $find['meta']['default_value']['photos'][$i]['image_url'];
                }

                $category = $find['meta']['default_value']['collection']['id'];
                // get title
                $title = $find['meta']['default_value']['title'];
                // get formated price
                $price = $find['meta']['default_value']['price_formatted'];
                // get description
                $description = $find['meta']['default_value']['flattened_description'];
                $condition = $find['meta']['default_value']['condition'] == 1 ? 'Used' : 'New';
            }
            

            $newcarousell_item = [
                'url'               => 'https://www.carousell.ph/p/'.$id,                
                'seller'            => $seller,
                'category'          => $category,
                'media'             => $media,
                'itemname'          => $title,
                'price'             => 'PHP '.$price,
                'description'       => $description,
                'condition'         => $condition,
                'meetup'            => '',                
                'delivery'          => '',                
                'source'            => 'carousell'
            ];
        }        

        return $newcarousell_item; 
    }
    
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
                            'stringContent' => str_replace($function->getThatAnnoyingChar(), "", $belowFold['stringContent'])
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
        $param = array(
            'url'   => $this->carousell_url.'&count='. ($page * 10) .'&session='.$this->getSession($page)
        );
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

    public function viewRelatedListing($cc_id, $productid) {
        $param = array(
            'url'   => $this->carousell_related_url.'?collection_id='.$cc_id.'&country_id='.$this->countryID.'&locale=en&product_id='.$productid
        );

        $resultdata = $this->carousellcURLCall($param);

        if(!$resultdata) {
            return false;
        } 

        // let see if there are still data to output
        if(count($resultdata['data']['results']) <= 0 ) {
            return array(
                    'data'      => [],
                    'result'    => true
            );
        }

        $function = new Functions();
        // json body to output
        $carouselljsonfeed = [];
        $count=0;
        for($i = 0; $i < count($resultdata['data']['results']); $i++){
            // for image "photos": []
            foreach($resultdata['data']['results'][$i]['photoUrls'] as $imageurl) {
                $image          = $imageurl;
                $thumbnailimage = $imageurl;
            }
            
            
            // for title
            $counttitle=0;
            foreach($resultdata['data']['results'][$i]['belowFold'] as $titledesc) {
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
            foreach($resultdata['data']['results'][$i]['belowFold'] as $snippetdesc) {
                                    
                $snippet[]          = mb_convert_encoding(str_replace($function->getThatAnnoyingChar(), "", $snippetdesc['stringContent']), 'UTF-8', 'UTF-8');
                $still++;
            }

            $carouselljsonfeed[] = [
                    'id'                =>  $resultdata['data']['results'][$i]['id'],
                    'title'             =>  $title,
                    'snippet'           =>  $snippet,
                    'link'              =>  'https://www.carousell.ph/p/'.$this->treatTitle($titlenotrans).'-'.$resultdata['data']['results'][$i]['id'],
                    'image'             =>  $image,
                    'thumbnailimage'    =>  $thumbnailimage,
                    'source'            =>  'carousell'
            ];
            $count++;
        }
        return array(
                'data'      => $carouselljsonfeed,
                'result'    => true
            );
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

        if(array_key_exists('header', $param)) {
            curl_setopt($ch, CURLOPT_URL,$param['url']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_HTTPHEADER, $param['header']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param['data']);
        } else {
            curl_setopt($ch, CURLOPT_URL,$param['url']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        }
        

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

        // session
        $session = $this->getSession($page);

        // build body
        if($filter != '') {
            $data = json_encode(array(
                "count"         => $page * 10,
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
                "count"         => $page * 10,
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
        $function = new Functions();
        $paging = $this->paginationTrick($page);

        // let's get the total result of search query
        if(array_key_exists('total', $resultdata['data'])) {
            $totalquery = $resultdata['data']['total']['value']['low'];
        } else {
            $totalquery = 0;
        }        

        // lets create virtual pagination
        $endat = $paging['end'] > $totalquery ? $totalquery : $paging['end'];
        $startfrom = $paging['start'];

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

                if(array_key_exists('photos', $sfeed)) {
                    foreach($sfeed['photos'] as $imageurl) {
                        $image          = $imageurl['thumbnailUrl'];
                        $thumbnailimage = array_key_exists('thumbnailProgressiveUrl', $imageurl) == false ? $image : $imageurl['thumbnailProgressiveUrl'];
                    }
                }else{
                    foreach($sfeed['photoUrls'] as $imageurl) {
                        $image          = $imageurl[0];
                        $thumbnailimage = $imageurl[0];
                    }
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
                                        
                    $snippet[]          = mb_convert_encoding(str_replace($function->getThatAnnoyingChar(), "", $snippetdesc['stringContent']), 'UTF-8', 'UTF-8');
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
     * @return Array
     */
    protected function paginationTrick($page) {
        if($page == null || $page == 0 || $page == 1 ) {
            return array(
                'start' => 0,
                'end'   => 9
            );
        }

        $start  = ($page * 10) - 10;
        $end    = $start + 9;

        return array(
            'start' => $start,
            'end'   => $end
        );
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
    protected function getSession($page) {
        $function = new Functions();
        $session = $function->guzzleHttpCall($this->carousell_url.'&count='.$page*10);
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
