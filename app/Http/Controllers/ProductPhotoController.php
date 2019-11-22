<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ProductPhoto;
use App\Http\Controllers\Functions;

class ProductPhotoController extends Controller
{
    /**
     * method to insert image to productphotos table
     * 
     * @param $id, $image_name, $is_primary
     */
    public function insertImage($id, $image_name, $is_primary) {  
        $photo = new ProductPhoto(); 
       
        $photo->idproduct   = $id;
        $photo->primary     = $is_primary === 'primary' ? 1 : 0;
        $photo->image       = $image_name;

        $photo->save();
    }

    /**
     * method to delete image in table and local storage
     * 
     * @param $id
     */
    public function deleteImage($id) {        
        $photo = new ProductPhoto();
        $path = new Functions();
        // lets get the image path to delete in database and local storage
        $image_path = $photo::where('idphoto', $id)->first();
        if(file_exists($image_path['image'])){
            unlink($path->public_path().'/'.$image_path['image']);
          }
        $photo::where('idphoto', $id)->delete();
    }

    /**
     * method to change primary photo in updatePost
     * 
     * @param $idproduct
     * @return Boolean
     */
    public function changePrimaryImage($idproduct) {

        $get_primary = $this->getPrimary($idproduct);
        
        if(!$get_primary) {
            return false;
        }
        // if there is then change primary = 0
        $change_primary = ProductPhoto::where('idproduct', $idproduct);

        $change_primary->update([
            'primary'   => 0
        ]);
        
        return true;
    }

    public function getPrimaryImage($idproduct) {
        $get_primary = $this->getPrimary($idproduct);
        
        if(!$get_primary) {
            return false;
        }

        return $get_primary['idphoto'];
    }

    protected function getPrimary($idproduct) {
        $photo = new ProductPhoto(); 

        // lets see is there is a current primary photo

        $is_there_primary = $photo::where('idproduct', $idproduct)->having('primary', 1)->first();

        if($is_there_primary == null) {
            return false;
        }

        return $is_there_primary;
    }
}
