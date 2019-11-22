<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\CarousellController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ScrapController;
use App\Http\Controllers\Functions;

class BuyAndSellController extends Controller
{

    private $carousell;
    private $hook;
    private $function;

    /**
     * instantiate CarousellController, ProductController, Functions
     */
    public function __construct(CarousellController $carousell, ProductController $hook, Functions $function) {
        $this->carousell    = $carousell;
        $this->hook         = $hook;
        $this->function     = $function;
    }

    protected function translateBuyAndSellSearch($content, $countrycode, $source) {
        if($countrycode === 'en') {
            return $content;
        }
        $translatedData = [];
        foreach($content['data'] as $new) {
            $snippet = [];
            for($i=0;$i<4;$i++) {
                $snippet[] = $new['snippet'][$i] == "" ? '' : $this->function->translator($new['snippet'][$i], $countrycode);
            }
            array_push($translatedData, [
                'id'                =>  $new['id'],
                'title'             =>  $new['title'] == '' ? '' : $this->function->translator($new['title'], $countrycode),
                'snippet'           =>  $snippet,
                'link'              =>  $new['link'],
                'image'             =>  $new['image'],
                'thumbnailimage'    =>  $new['thumbnailimage'],
                'source'            =>  $new['source'],
            ]);
        }
        // array_push($translatedData['total'], $content['total']);
        // array_push($translatedData['result'], $content['result']);
        return array(
            'data'      => $translatedData,
            'total'     => $content['total'],
            'result'    => $content['result']
        );
    }

    /**
     * method to get $request->countrycode
     * 
     * @param $request
     * @return $viewitem 
     * @return Boolean 
     */
    protected function translateViewContent($content, $countrycode, $source) {
        if($countrycode === 'en') {
            return $content;
        }
        
        $viewitem = [];

        switch ($source) {
            case 'carousell':         
                $viewitem = [
                    'url'               => $content['url'],
                    'seller'            => $content['seller'],
                    'media'             => $content['media'],
                    'itemname'          => $this->function->translator($content['itemname'], $countrycode),
                    'price'             => $content['price'],
                    'description'       => $this->function->translator($content['description'], $countrycode),
                    'source'            => $content['source'],
                ];
                
                return $viewitem;

            case 'hook':
                $viewitem = [
                    'url'               => $content['url'],
                    'seller'            => $content['seller'],
                    'media'             => $content['media'],
                    'itemname'          => $this->function->translator($content['itemname'], $countrycode),
                    'price'             => $content['price'],                
                    'description'       => $this->function->translator($content['description'], $countrycode),          
                    'condition'         => $this->function->translator($content['condition'], $countrycode),   
                    'meetup'            => $content['meetup'] == '' ? '' : $this->function->translator($content['meetup'], $countrycode),  
                    'delivery'          => $content['delivery'] == '' ? '' : $this->function->translator($content['delivery'], $countrycode),
                    'source'            => $content['source'],
                ];
                return $viewitem;
        }
    }
    
    /**
     * method to translate mergeFrontDisplay() method
     * 
     * @param $buyandselldata, $countrycode
     * @return $translatedData
     */
    protected function translateFrontDIsplay($buyandselldata, $countrycode) {
        if($countrycode === 'en') {
            return $buyandselldata;
        }

        $translatedData = [];
        foreach($buyandselldata as $new) {
            $info = [];
            for($i=0;$i<4;$i++) {
                $info[] = [
                    'stringContent' => $new['info'][$i]['stringContent'] == "" ? '' : $this->function->translator($new['info'][$i]['stringContent'], $countrycode),
                ];
            }
            
            array_push($translatedData, [
                'id'            =>  $new['id'],
                'seller'        =>  $new['seller'],
                'photoUrls'     =>  $new['photoUrls'],
                'info'          =>  $info,
                'source'        =>  $new['source'],
            ]);
        }

        return $translatedData;        
    }

    /**
     * method to translate translateFeedBuyandSell() method
     * 
     * @param $buyandselldata, $countrycode
     * @return $translatedData
     */
    protected function translateFeedBuyandSell($buyandselldata, $countrycode) {
        if($countrycode === 'en') {
            return $buyandselldata;
        }

        $translatedData = [];
        foreach($buyandselldata as $new) {
            $snippet = [];
            for($i=0;$i<4;$i++) {
                $snippet[] = $new['snippet'][$i] == "" ? '' : $this->function->translator($new['snippet'][$i], $countrycode);
            }
            array_push($translatedData, [
                'id'                =>  $new['id'],
                'title'             =>  $new['title'] == '' ? '' : $this->function->translator($new['title'], $countrycode),
                'snippet'           =>  $snippet,
                'link'              =>  $new['link'],
                'image'             =>  $new['image'],
                'thumbnailimage'    =>  $new['thumbnailimage'],
                'source'            =>  $new['source'],
            ]);
        }

        return $translatedData;
    }

    /**
     * method to display cvarousell and our product in the front page
     * 
     * @return $buyandsell
     */
    public function mergeFrontDisplay(Request $request) {
        $countrycode = $this->function->isThereCountryCode($request);

        $front_carousell    = $this->carousell->getCarousell();
        $front_hook         = $this->hook->loadOurProduct();
        $buyandsell = [];  

        for($i=0;$i<3;$i++) {           

            if($front_hook) {
                foreach($front_hook as $hook) {
                    if($i == $hook['no']) {
                        array_push($buyandsell, [
                            'id'            =>  $hook['id'],
                            'seller'        =>  $hook['seller'],
                            'photoUrls'     =>  $hook['photoUrls'],
                            'info'          =>  $hook['info'],
                            'source'        =>  'hook'
                        ]);
                    }
                }
            }
            
            if($front_carousell) {
                foreach($front_carousell as $carousell) {
                    if($i == $carousell['no']) {
                        array_push($buyandsell, [
                            'id'            =>  $carousell['id'],
                            'seller'        =>  $carousell['seller'],
                            'photoUrls'     =>  $carousell['photoUrls'],
                            'info'          =>  $carousell['info'],
                            'source'        =>  'carousell'
                        ]);
                    }              
                }
            }
        }

        if(!$countrycode){
            return response()->json([
                'data'      => $buyandsell,
                'result'    => true
            ]);
        }

        $trans = $this->translateFrontDIsplay($buyandsell, $countrycode);
        
        return response()->json([
            'data'      => $trans,
            'result'    => true
        ]);
    }

    /**
     * method to display cvarousell and our product in the buy and sell page
     * 
     * @param Request $request
     * @return $buyandsell
     */
    public function feedBuyandSell(Request $request) {

        $countrycode = $this->function->isThereCountryCode($request);

        $feedcarousell  = $this->carousell->feedCarousell($request->page);
        $feedhook       = $this->hook->feedHook($request->page);
        $buyandsell = [];
        for($i=0;$i<10;$i++) {

            if($feedhook) {
                foreach($feedhook as $hook) {
                    if($i == $hook['no']) {
                        array_push($buyandsell, [
                            'id'                =>  $hook['id'],
                            'title'             =>  $hook['title'],
                            'snippet'           =>  $hook['snippet'],
                            'link'              =>  $hook['link'],
                            'image'             =>  $hook['image'],
                            'thumbnailimage'    =>  $hook['thumbnailimage'],
                            'source'            =>  $hook['source'],
                        ]);
                    }
                }
            }
            
            if($feedcarousell) {
                foreach($feedcarousell as $carousell) {
                    if($i == $carousell['no']) {
                        array_push($buyandsell, [
                            'id'                =>  $carousell['id'],
                            'title'             =>  $carousell['title'],
                            'snippet'           =>  $carousell['snippet'],
                            'link'              =>  $carousell['link'],
                            'image'             =>  $carousell['image'],
                            'thumbnailimage'    =>  $carousell['thumbnailimage'],
                            'source'            =>  $carousell['source'],
                        ]);
                    }              
                }
            }
        }

        if(!$countrycode){
            return response()->json([
                'data'      => $buyandsell,
                'result'    => true
            ]);
        }

        $trans = $this->translateFeedBuyandSell($buyandsell, $countrycode);

        return response()->json([
            'data'      => $trans,
            'result'    => true
        ]);
    }

    /**
     * method to view single product from hook, and carousell
     * 
     * @param Request $request
     * @return JSON
     */
    public function viewSingleContent(Request $request) {
        $countrycode = $this->function->isThereCountryCode($request);
        $source = $request->source;

        $scrap = new ScrapController();
        $product = new ProductController();       

        switch ($source) {
            case 'carousell':
                $view_carousell = $scrap->scrapCarousell($request->id);
                $similaritem = $this->buyAndSellFilter($countrycode, 1, '', array($view_carousell['category']));
                $view_carousell['similar_item'] = $similaritem['data'];

                if(!$countrycode){
                    return response()->json([
                        'data'          => $view_carousell,                        
                        'result'        => true
                    ]);
                }

                $trans = $this->translateViewContent($view_carousell, $countrycode, $source);

                return response()->json([
                    'data'          => $trans,
                    'result'        => true
                ]);
                
            case 'hook':
                $view_product = $product->viewProduct($request->id);
                $similaritem = $this->buyAndSellFilter($countrycode, 1, '', array(strval($view_product['category'])), $request->id);
                $view_product['similar_item'] = $similaritem['data'];
                
                if(!$countrycode){
                    return response()->json([
                        'data'          => $view_product,
                        'result'        => false
                    ]);
                }

                $trans = $this->translateViewContent($view_product, $countrycode, $source);

                return response()->json([
                    'data'          => $trans,
                    'result'        => true
                ]);
        }
    }

    /**
     * merge search method for carousell and hook
     * 
     * @param $request
     * @return JSON
     */
    public function buyAndSellSearch(Request $request) {
        $countrycode = $this->function->isThereCountryCode($request);
        $source = $request->source;
        $page = $request->page;
        $request->has('search') == true ? $search = $request->search : $search = '';
        $request->has('filter') == true ? $filter = $request->filter : $filter = '';

        switch ($source) {
            case 'carousell';
                $searchcar = $this->carousell->doCarousellSearch($page, $search, $filter);
                if(array_key_exists('total', $searchcar )) {
                    if(!$countrycode){
                        return response()->json([
                            'data'      => $searchcar['data'],
                            'total'     => $searchcar['total'],
                            'result'    => $searchcar['result']
                        ]);
                    }
                    $searchdata = $this->translateBuyAndSellSearch($searchcar, $countrycode, $source);
                    return response()->json([
                        'data'      => $searchdata['data'],
                        'total'     => $searchdata['total'],
                        'result'    => $searchdata['result']
                    ]);
                } else {
                    return response()->json([
                        'message'   => $searchcar['data'],
                        'result'    => $searchcar['result']
                    ]);
                }
                break;                
            case 'hook':
                $searchhook = $this->hook->searchProduct($page, $search);
                if(array_key_exists('total', $searchhook )) {
                    if(!$countrycode){
                        return response()->json([
                            'data'      => $searchhook['data'],
                            'total'     => $searchhook['total'],
                            'result'    => $searchhook['result']
                        ]);
                    }
                    $searchdata = $this->translateBuyAndSellSearch($searchhook, $countrycode, $source);
                    return response()->json([
                        'data'      => $searchdata['data'],
                        'total'     => $searchdata['total'],
                        'result'    => $searchdata['result']
                    ]);
                } else {
                    return response()->json([
                        'message'   => $searchhook['data'],
                        'result'    => $searchhook['result']
                    ]);
                }
                break; 
            default:
                return response()->json([
                    'message'   => 'Source is empty!',
                    'result'    => false
                ]);
        }

    }

    public function buyAndSellFilterForPage(Request $request) {
        $page = $request->page;
        $request->has('countrycode') == true ? $countrycode = $request->countrycode : $countrycode = '';
        $request->has('search') == true ? $search = $request->search : $search = '';
        $request->has('filter') == true ? $filter = $request->filter : $filter = '';

        $got_data = $this->buyAndSellFilter($countrycode, $page, $search, $filter);

        return response()->json([
            'data'      => $got_data['data'],
            'result'    => $got_data['result']
        ]);
    }

    /**
     * filter method for carousell and hook
     * 
     * @param $request
     * @return JSON
     */
    public function buyAndSellFilter($countrycode, $page, $search, $filter, $idproduct = '') {    
        $filtercarousell    = $this->carousell->filterCarousell($page, $search, $filter);
        $filterhook         = $this->hook->filterProduct($filter, $page, $idproduct);

        $buyandsellfilter = [];
        for($i=0;$i<10;$i++) {
    
            if($filterhook) {
                foreach($filterhook as $hook) {
                    if($i == $hook['no']) {
                        array_push($buyandsellfilter, [
                            'id'                =>  $hook['id'],
                            'title'             =>  $hook['title'],
                            'snippet'           =>  $hook['snippet'],
                            'link'              =>  $hook['link'],
                            'image'             =>  $hook['image'],
                            'thumbnailimage'    =>  $hook['thumbnailimage'],
                            'source'            =>  $hook['source'],
                        ]);
                    }
                }
            }
            
            if($filtercarousell) {
                foreach($filtercarousell as $carousell) {
                    if($i == $carousell['no']) {
                        array_push($buyandsellfilter, [
                            'id'                =>  $carousell['id'],
                            'title'             =>  $carousell['title'],
                            'snippet'           =>  $carousell['snippet'],
                            'link'              =>  $carousell['link'],
                            'image'             =>  $carousell['image'],
                            'thumbnailimage'    =>  $carousell['thumbnailimage'],
                            'source'            =>  $carousell['source'],
                        ]);
                    }              
                }
            }
        }

        if($countrycode == ''){
            return array(
                'data'      => $buyandsellfilter,
                'result'    => true
            );
        }
    
        $trans = $this->translateFeedBuyandSell($buyandsellfilter, $countrycode);
        
        return array(
            'data'      => $trans,
            'result'    => true
        );
    }
}
