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
    public function insertImage($id, $image_name, $is_primary, $primary_image) {  
        $photo = new ProductPhoto(); 
       
        $photo->idproduct   = $id;
        $photo->primary     = $is_primary === 'primary' ? 1 : 0;
        $photo->image       = $image_name;

        $photo->save();

        /**
         * if we reach this line means, t
         * hat we have primary but its in the database 
         * and not in $request->image[]
         */
        if($is_primary == 'primary') {
            $this->getProductPhotos($id, $primary_image);
        }
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
    public function resetPrimaryImage($idproduct) {

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

    public function getProductPhotos($idproduct, $primary_image) {
        $dbphotos = ProductPhoto::where('idproduct', $idproduct)->get();

        // return false if we got nothing
        if($dbphotos->count() <= 0) {
            return false;
        }

        foreach($dbphotos as $photos) {
            $per = explode("/", $photos['image']);
            if($primary_image == $per['4']) {
                // let change the status = 1
                $updateprimary = ProductPhoto::where('idphoto', $photos['idphoto']);
                $updateprimary->update([
                    'primary' => 1
                ]);
            }
        }
    }
}
