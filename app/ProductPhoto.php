<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductPhoto extends Model
{
    protected $primaryKey = 'idphoto';
    protected $table = 'productphotos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'idphoto', 'idproduct', 'primary', 'image',
    ];

    /**
     * method to get product photo data
     * 
     * @param $idphoto
     * @return $productphoto
     */
    public function getProductPhotoData($idphoto) {
        $productphoto = ProductPhoto::where('idphoto', $idphoto)->first();

        return $productphoto;
    }
}
