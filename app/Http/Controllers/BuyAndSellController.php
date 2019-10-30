<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\CarousellController;
use App\Http\Controllers\ProductController;

class BuyAndSellController extends Controller
{

    private $carousell;
    private $hook;

    public function __construct(CarousellController $carousell, ProductController $hook) {
        $this->carousell    = $carousell;
        $this->hook         = $hook;
    }

    public function mergeFrontDisplay() {

        $front_carousell    = $this->carousell->getCarousell();
        $front_hook         = $this->hook->loadOurProduct();
        $buyandsell = [];  

        for($i=0;$i<3;$i++) {           

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

        return response()->json([
            'data'      => $buyandsell,
            'result'    => true
        ]);
    }

}
