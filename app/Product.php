<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'idproduct', 'title', 'price', 'description', 'categoryid', 'iduser', 'condition', 'meetup', 'delivery', 'post'
    ];

    /**
     * method to get product data
     * 
     * @param $idproduct
     * @return $product
     */
    public function getProductData($idproduct) {
        $product = Product::where('idproduct', $idproduct)->first();

        return $product;
    }

    /**
     * method to check if product ID exist
     * 
     * @param $idproduct
     * @return Bool
     */
    public function isProductIDExist($idproduct) {
        $id_exist = Product::where('idproduct', $idproduct)->first();
        if($id_exist != null){
            return true;
        }else{
            return false;
        }
    }

    public function getTotalProductCount($iduser, $viewtype) {
        if($viewtype === 'public') {
            $product = Product::where('iduser', $iduser)->having('status', 'available')->orderBy('idproduct', 'desc')->get();
        } elseif($viewtype === 'private') {
            $product = Product::where('iduser', $iduser)->orderBy('idproduct', 'desc')->get();
        }

        if($product == null) {
            return false;
        }

        return  count($product);
    }
        
}
