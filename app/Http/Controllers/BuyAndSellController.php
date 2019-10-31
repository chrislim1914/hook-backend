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
                            'source'        =>  'Carousell'
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

    public function viewSingleContent(Request $request) {
        $scrap = new ScrapController();
        $product = new ProductController();
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
}
