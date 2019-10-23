<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;
use App\Http\Controllers\Functions;

class BuyAndSellController extends Controller
{
    private $client;
    private $function;
    private $carousell_url = 'https://www.carousell.ph/api-service/home/?count=20&countryID=1694008';
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
        
        // get country code, news apikey
        $langcode =  $this->function->getLanguageCode($request->languagecode);

        $carouselldata = $this->function->guzzleHttpCall($this->carousell_url);

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
            $count = 0;
            foreach($carouselldata['data']['results'] as $cfeed) {
                if($count >= 5){
                    break;
                } 
                foreach($cfeed as $innercfeed) {
                    
                    $deatail = [];
                    foreach($innercfeed['belowFold'] as $belowFold) {
                        $deatail[] = [
                            // 'stringContent' => $belowFold['stringContent']
                            'stringContent' => $langcode === 'en' ? $belowFold['stringContent'] : $this->function->translator($belowFold['stringContent'], $langcode),
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
                $count++;
            }
            return response()->json([
                'data'      => $carousellfeed,
                'result'    => true
            ]);
        } else {
            return response()->json([
            'message'   => 'Error getting data!',
            'result'    => false
            ]);
        }
    }

    public function feedCarousell(Request $request) {
        // build URL
        $url = $this->carousell_search_url;

        // build page
        $page = $this->paginationTrick($request->page);

        // session
        $session = $this->getSession();

        // build body
        $data = json_encode(array(
            "count"         => $page,
            "countryId"     => "1694008",
            "session"       => $session
        ));       

        // build header
        $header = array(
            "content-type: application/json",
            "Content-Length: ".strlen($data),
            "Host: www.carousell.ph"
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);    
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);        
        $result=curl_exec ($ch);
        curl_close ($ch);

        $resultdata = json_decode($result, true);

        // check if there is result in the body
        if(array_key_exists('results',$resultdata['data'])) {

             // let's get the total result of search query
             $totalquery = $resultdata['data']['total']['value']['low'];

             // lets create virtual pagination
             $endat = $page > $totalquery ? $totalquery : $page;
             $startfrom = $page > 10 ? $page - 9 : $page - 10 ;
             
            // let see if there are still data to output
            if(count($resultdata['data']['results']) <= 0 ) {
                return response()->json([
                    'data'      => [],
                    'total'     => $totalquery,
                    'result'    => true
                    ]);
            }

            // json body to output
            $searchfeed = [];
            
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

                    $searchfeed[] = [
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
            return response()->json([
                'data'      => $searchfeed,
                'total'     => $totalquery,
                'result'    => true
            ]); 

        } else {
            return response()->json([
            'message'   => 'Error getting data!',
            'result'    => false
            ]);
        }
    }

    public function doCarousellSearch(Request $request) {        
        
        // get country code language localization
        $langcode =  $this->function->getLanguageCode($request->languagecode);

        // build URL
        $url = $this->carousell_search_url;

        // build page
        $page = $this->paginationTrick($request->page);

        // build body
        $data = json_encode(array(
            "count"         => $page,
            "countryId"     => "1694008",
            "filters"       => [],
            "locale"        => "en",
            "query"         => $request->search
        ));
        

        // build header
        $header = array(
            "content-type: application/json",
            "Content-Length: ".strlen($data)
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);    
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);        
        $result=curl_exec ($ch);
        curl_close ($ch);

        $resultdata = json_decode($result, true);

        // check if there is result in the body
        if(array_key_exists('results',$resultdata['data'])) {

            // let's get the total result of search query
            $totalquery = $resultdata['data']['total']['value']['low'];

            // lets create virtual pagination
            $endat = $page > $totalquery ? $totalquery : $page;
            $startfrom = $page > 10 ? $page - 9 : $page - 10 ;

            // let see if there are still data to output
            if(count($resultdata['data']['results']) <= 0 ) {
                return response()->json([
                    'data'      => [],
                    'total'     => $totalquery,
                    'result'    => true
                    ]);
            }

            // json body to output
            $searchfeed = [];
            
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
                            // $title          = $titledesc['stringContent'];
                            $title          = $langcode === 'en' ? $titledesc['stringContent'] : $this->function->translator($titledesc['stringContent'], $langcode);
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
                        // $snippet[]          = $langcode === 'en' ? $snippetdesc['stringContent'] : $this->function->translator($snippetdesc['stringContent'], $langcode);
                                          
                        $still++;
                    }
                    $searchfeed[] = [
                        'id'                =>  $sfeed['id'],
                        'title'             =>  $title,
                        'snippet'           =>  $snippet,
                        'link'              =>  'https://www.carousell.ph/p/'.$this->treatTitle($titlenotrans).'-'.$sfeed['id'],
                        'image'             =>  $image,
                        'thumbnailimage'    =>  $thumbnailimage,
                    ];
                }
            }
            return response()->json([
                'data'      => $searchfeed,
                'total'     => $totalquery,
                'result'    => true
            ]); 
        } else {
            return response()->json([
            'message'   => 'Error getting data!',
            'result'    => false
            ]);
        }
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
