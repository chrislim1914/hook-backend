<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ProductPhoto;

class ProductPhotoController extends Controller
{
    public function insertImage($id, $image_name) {  
        $photo = new ProductPhoto(); 

        $photo->idproduct   = $id;
        $photo->image       = $image_name;

        $photo->save();
    }

    public function deleteImage($id) {
        
        $photo = new ProductPhoto(); 
        $photo::where('idproduct', $id)->delete();
    }
}
