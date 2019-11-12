<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\CarousellController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ScrapController;

class BuyAndSellController extends Controller
{

    private $carousell;
    private $hook;

    public function __construct(CarousellController $carousell, ProductController $hook) {
        $this->carousell    = $carousell;
        $this->hook         = $hook;
    }

    /**
     * method to display cvarousell and our product in the front page
     * 
     * @return $buyandsell
     */
    public function mergeFrontDisplay() {

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

        return response()->json([
            'data'      => $buyandsell,
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
        $feedcarousell  = $this->carousell->feedCarousell($request->page);
        $feedhook       = $this->hook->feedHook($request->page);

        $buyandsell = [];  

        for($i=0;$i<5;$i++) {           

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

        return response()->json([
            'data'      => $buyandsell,
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
        $scrap = new ScrapController();
        $product = new ProductController();

        // if statement is temporarily
        if(!$request->has('source')) {
            $view_carousell = $scrap->scrapCarousell($request->id);
                return response()->json([
                    'data'          => $view_carousell,
                    'result'        => true
                ]);
        }

        switch ($request->source) {
            case 'carousell':
                $view_carousell = $scrap->scrapCarousell($request->id);
                return response()->json([
                    'data'          => $view_carousell,
                    'result'        => true
                ]);
            case 'hook':
                $view_product = $product->viewProduct($request->id);
                return response()->json([
                    'data'          => $view_product,
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

        $source = $request->source;
        $page = $request->page;
        $request->has('search') == true ? $search = $request->search : $search = '';
        $request->has('filter') == true ? $filter = $request->filter : $filter = '';

        switch ($source) {
            case 'carousell';
                $searchcar = $this->carousell->doCarousellSearch($page, $search, $filter);
                if(array_key_exists('total', $searchcar )) {
                    return response()->json([
                        'data'      => $searchcar['data'],
                        'total'     => $searchcar['total'],
                        'result'    => $searchcar['result']
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
                    return response()->json([
                        'data'      => $searchhook['data'],
                        'total'     => $searchhook['total'],
                        'result'    => $searchhook['result']
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

    public function buyAndSellFilter(Request $request) {
        $page = $request->page;
        $request->has('search') == true ? $search = $request->search : $search = '';
        $request->has('filter') == true ? $filter = $request->filter : $filter = '';
    
        $filtercarousell    = $this->carousell->filterCarousell($page, $search, $filter);
        $filterhook         = $this->hook->filterProduct($filter, $page);
    
        $buyandsellfilter = [];  
    
        for($i=0;$i<3;$i++) {           
    
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
    
        return response()->json([
            'data'      => $buyandsellfilter,
            'result'    => true
        ]);
    }
}
