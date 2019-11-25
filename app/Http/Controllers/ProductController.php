<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\Product;
use App\ProductPhoto;
use App\User;
use App\Http\Controllers\ProductPhotoController;
use Illuminate\Support\Facades\Config;
use App\Http\Controllers\Functions;

class ProductController extends Controller
{   
    /**
     * method to view product from hook and carousell
     * 
     * @param $id
     * @return JSON
     */
    public function viewProduct($id) {
        $function   = new Functions();
        $product    = Product::where('idproduct', $id)->get();
        $baseURL    = $function::getAppURL();
        if(!$product) {
            return false;
        }
        
        
        $viewitem = [];
        
        foreach($product as $new) {
            $photo          = ProductPhoto::where('idproduct', $id)->having('primary', 0)->get();
            $primaryphoto   = ProductPhoto::where('idproduct', $id)->having('primary', 1)->first();
            $media = [];
            $user = User::where('iduser', $new['iduser'])->first();
            $image = $user->getUserFolder($new['iduser']);
            
            if($primaryphoto['image'] !== '') {
                $media = [
                    $baseURL.$primaryphoto['image']
                ];
            }
            

            foreach($photo as $newphoto) {
                $media[] = $baseURL.$newphoto['image'];
            }

            $seller = [
                'id'            => $user['iduser'],
                'username'      => $user['username'],
                'contactno'     => $user['contactno'],
                'profile_photo' => $image == false ? $user['profile_photo'] : $baseURL.$user['profile_photo']
            ];
            $utf_convert = mb_convert_encoding(str_replace($function->getThatAnnoyingChar(), "", $new['description']), 'UTF-8', 'UTF-8');
            $viewitem = [
                'url'               => '',
                'seller'            => $seller,
                'category'          => $new['categoryid'],
                'media'             => $media,
                'itemname'          => $new['title'],
                'price'             => 'PHP '.$new['price'],                
                'description'       => $utf_convert,          
                'condition'         => $new['condition'],                
                'meetup'            => $new['meetup'],                
                'delivery'          => $new['delivery'],                
                'status'            => $new['status'],
                'source'            => 'hook'
            ];
        }

        return $viewitem;
    }
    /**
     * method to load post product in the front page
     * 
     * @return $hookfeed
     */
    public function loadOurProduct() {
        $function = new Functions();
        $user = new User();
        $product    = Product::where('status', 'available')->Orderby('idproduct', 'desc')->get();
        $baseURL    = $function::getAppURL();

        $hookfeed = [];
        $count=0;

        foreach($product as $each) {
            $info = [];
            $seller = [];

            $c_user = $user->where('iduser', $each['iduser'])->first();

            $seller = [
                'id'                => $c_user['iduser'],
                'profilePicture'    => $user->profilePath($c_user['profile_photo']),
                'username'          => $c_user['username'],
            ];

            $image = ProductPhoto::where('idproduct', $each['idproduct'])->having('primary', 1)->first();

            $info1 = [
                'stringContent' => $each['title'],
            ];

            $info2 = [
                'stringContent' => 'PHP '.$each['price'],
            ];
            $utf_convert = mb_convert_encoding($each['description'], 'UTF-8', 'UTF-8');
            $info3 = [
                'stringContent' => str_replace($function->getThatAnnoyingChar(), "", $utf_convert),
            ];

            $info4 = [
                'stringContent' => $each['condition'],
            ];

            $info = [
                $info1,
                $info2,
                $info3,
                $info4,
            ];

            $hookfeed[] = [
                'no'            =>  $count,
                'id'            =>  $each['idproduct'],
                'seller'        =>  $seller,
                'photoUrls'     =>  [$baseURL.$image['image']],
                'info'          =>  $info,
                'source'        =>  'Hook'
            ];
            $count++;
        }
        return $hookfeed;
    }    

    /**
     * method to load product for buy and sell page
     * 
     * @param $page
     * @return $feedhook
     */
    public function feedHook($page) {

        $paginate = $this->paginateHook($page);

        $product    = Product::where('status', 'available')->Orderby('idproduct', 'desc')->skip($paginate['skip'])->take($paginate['page'])->get();

        // return noting if null
        if($product == null) {
            return false;
        }

        $feedhook = $this->createProductJsonData($product);
        
        return $feedhook;
    }

    /**
     * method to post a product
     * 
     * @param $request
     * @return JSON
     */
    public function postProduct(Request $request) {        
        $user       = new User();
        $product    = new Product();
        // first we get the user info
        $gotuser = $user->getUserData($request->iduser);

        // the validation
        $whatsup = $this->validatePostProduct($request->all());        

        if($gotuser == null) {
            return response()->json([
                'message'   => "User not found!",
                'result'    => false
            ]);
        }elseif(!$whatsup) {
            return response()->json([
                'message'   => "there is wrong with your data!",
                'result'    => false
            ]);
        }

        // lets check if the profile_photo is path and not url
        $path = $user->getUserFolder($request->iduser);
        
        $product->title       = $request->title;
        $product->description = $request->description;
        $product->price       = $request->price;
        $product->categoryid  = $request->categoryid;
        $product->iduser      = $request->iduser;
        $product->condition   = $request->condition;
        $product->meetup      = $request->meetup;
        $product->delivery    = $request->delivery;
        $product->status      = 'available';

        if($product->save()) {
            // set the id
            $id = $product->id;

            // image full path and name
            if(!$path) {
                $path = $user->createUserFolder($gotuser->username);
                $newpath = $user->createUserFolderProduct($path, $id);
            }else {
                $newpath = $user->createUserFolderProduct($path, $id);
            }
            
            // lets get the binary image for primary
            // $primary_image = $this->getBinaryImageForPrimary($request->image, $request->primary_image);
            
            // if(!$primary_image) {
            //     return response()->json([
            //         'message'   => "Failed to parse image!",
            //         'result'    => false
            //     ]);
            // }
            // lets save the primary image
            // $this->savePostImages($primary_image, $newpath, $id, 'primary');

            // lets save the gallery image
            $this->savePostImages($request->image, $newpath, $id, $request->primary_image);

            return response()->json([
                'message'   => '',
                'result'    => true
            ]);
        } else {
            return response()->json([
                'message'   => "Failed to add product!",
                'result'    => false
            ]);
        }
    }

    /**
     * method to soft delete hook post product
     * 
     * @param $request
     * @return JSON
     */
    public function changeStatus(Request $request) {
        $idproduct  = $request->idproduct;
        $status     = $request->status;
        $product    = new Product();

        // check if exist
        $checkid = $product->isProductIDExist($idproduct);

        if(!$product) {
            return response()->json([
                'message'   => 'Product not found!',
                'result'    => false
            ]);
        }

        $del_product = $product::where('idproduct', $idproduct);

        if($del_product->update([
            'status'  => $status
        ])) {
            return response()->json([
                'message'   => '',
                'result'    => true
            ]);
        } else {
            return response()->json([
                'message'   => 'Failed to delete product!',
                'result'    => false
            ]);
        }
    }

    /**
     * method to load detailed product information
     * 
     * @param $request
     * @return
     */
    public function loadProductforUpdate(Request $request) {
        $productinfo    = [];
        $productphoto   = [];

        $product    = Product::where('idproduct', $request->idproduct)->first();
        $photo      = ProductPhoto::where('idproduct', $request->idproduct)->get();

        $function   = new Functions();
        $baseURL    = $function::getAppURL();

        if($product == null) {
            return response()->json([
                'message'   => "Failed to load Product Data!",
                'result'    => false
            ]);
        }

        foreach($photo as $newphoto) {
            $imagenameonly = explode("/", $newphoto['image']);
            $productphoto[]   = [
                'idphoto'     => $newphoto['idphoto'],
                'idproduct'   => $newphoto['idproduct'],
                'image'       => $baseURL.$newphoto['image'],
                'name'        => $imagenameonly[4],
                'primary'     => $newphoto['primary'],
            ];
        }        

        $productinfo = [
            'idproduct'     => $product['idproduct'],
            'title'         => $product['title'],
            'description'   => $product['description'],
            'price'         => $product['price'],
            'categoryid'    => $product['categoryid'],
            'iduser'        => $product['iduser'],
            'condition'     => $product['condition'],
            'meetup'        => $product['meetup'],
            'delivery'      => $product['delivery'],
            'status'        => $product['status'],
            'gallery'       => $productphoto
        ];

        return response()->json([
            'data'   => $productinfo,
            'result'    => true
        ]);

    }

    /**
     * method to update hook post product
     * 
     * @param $request
     * @return JSON
     */
    public function updatePost(Request $request) {
        $user       = new User();
        $product    = new Product();
        $photo      = new ProductPhotoController();

        // lets check if they try to delete the primary image and there is no new primary image is supplied
        if($request->has('delete_image')) {
            $checkifWhat = $this->checkIfThereIsNoPrimaryLeft($request->delete_image, $request->idproduct, $request->primary_image);
            if(!$checkifWhat) {
                return response()->json([
                    'message'   => "Cannot delete primary without new primary image!",
                    'result'    => false
                ]);
            }
        }        

        // first we get the user info
        $gotuser = $user->getUserData($request->iduser);

        // the validation
        $whatsup = $this->validateUpdateProduct($request->all());        

        if($gotuser == null) {
            return response()->json([
                'message'   => "User not found!",
                'result'    => false
            ]);
        }elseif(!$whatsup) {
            return response()->json([
                'message'   => "there is wrong with your data!",
                'result'    => false
            ]);
        }

        // lets check if the profile_photo is path and not url
        $path = $user->getUserFolder($request->iduser);

        $updatepost = Product::where('idproduct', $request->idproduct);

        // update the post
        if($updatepost->update([
            'title'         => $request->title,
            'description'   => $request->description,
            'price'         => $request->price,
            'categoryid'    => $request->categoryid,
            'iduser'        => $request->iduser,
            'condition'     => $request->condition,
            'meetup'        => $request->meetup,
            'delivery'      => $request->delivery,
        ])) {

            // image full path and name
            $newpath = $path.'product_'.$request->idproduct.'/';

            if($request->hasFile('image')) {
                // what to do if image[] is not empty
                // check if delete_image[] is not empty/empty
                if($request->has('delete_image')) {
                    // delete the images in $request->delete_image            
                    foreach($request->delete_image as $to_be_deleted_id) {
                        $photo->deleteImage($to_be_deleted_id);
                    }                    
                }

                // check if there is new primary_image to assign
                if($request->has('primary_image')) {
                    // reset status value if there is new primary_image
                    $change_primary = $photo->resetPrimaryImage($request->idproduct);
                    // mark the $request->primary_image as primary
                    $markprimary = $photo->getProductPhotos($request->idproduct, $request->primary_image);
                }
                
                // save the image
                $this->savePostImages($request->image, $newpath, $request->idproduct, $request->primary_image);
                
            }else{
                // what to do if image[] is empty
                if($request->has('delete_image')) {

                    // delete the images in $request->delete_image            
                    foreach($request->delete_image as $to_be_deleted_id) {
                        $photo->deleteImage($to_be_deleted_id);
                    }                    
                }

                // check if there is new primary_image to assign
                if($request->has('primary_image')) {
                    // reset status value if there is new primary_image
                    $change_primary = $photo->resetPrimaryImage($request->idproduct);
                    // mark the $request->primary_image as primary
                    $markprimary = $photo->getProductPhotos($request->idproduct, $request->primary_image);
                }
            }            

            return response()->json([
                'message'   => '',
                'result'    => true
            ]);
        } else {
            return response()->json([
                'message'   => "Failed to update product!",
                'result'    => false
            ]);
        }

    }

    /**
     * method to do search on our own product
     * 
     * @param $page, $search
     * @return JSON 
     */
    public function searchProduct($page, $search) {
        
        $paginate = $this->paginateHook($page);

        if($search == null || $search == '') {
            return array(
                'data'      => "Search string is empty!",
                'result'    => false
            );
        }

        $search_product = Product::where('title', 'LIKE', "%{$search}%")->where('status', 'available')->skip($paginate['skip'])->take($paginate['page'])->Orderby('idproduct', 'desc')->get();

        // return noting if null
        if($search_product == null) {
            return array(
                'data'      => [],
                'total'     => [],
                'result'    => true
            );
        }
        
        $searchproduct = $this->createProductJsonData($search_product);

        return array(
            'data'      => $searchproduct,            
            'total'     => count($search_product),
            'result'    => true
        );
    }

    /**
     * method to filter product by category ID
     * 
     * @param $request
     * @return JSON
     */
    public function filterProduct($filter, $page, $idproduct) {

        $paginate = $this->paginateHook($page);

        if($filter == null || $filter == '') {
            return response()->json([
                'message'   => "Search string is empty!",
                'result'    => false
            ]);
        }

        $filter_product = Product::where('categoryid', $filter)->where('status', 'available')->having('idproduct', '<>', $idproduct)->skip($paginate['skip'])->take($paginate['page'])->Orderby('idproduct', 'desc')->get();

        // return noting if null
        if($filter_product->count() <= 0) {
            return false;
        }

        $filterproduct = $this->createProductJsonData($filter_product);

        return $filterproduct;
    }

    /**
     * method to create product JSON data
     * 
     * @param Object $product
     * @return $hookfeed
     */
    protected function createProductJsonData($product) {
        $function = new Functions();
        $baseURL    = $function::getAppURL();
        $hookfeed = [];
        $count=0;
        foreach($product as $each) {
            $info = [];
            $seller = [];

            $user = User::where('iduser', $each['iduser'])->first();

            $seller = [
                'id'                => $user['iduser'],
                'profilePicture'    => $baseURL.$user['profile_photo'],
                'username'          => $user['username'],
            ];

            $image = ProductPhoto::where('idproduct', $each['idproduct'])->having('primary', 1)->first();
            $utf_convert = mb_convert_encoding($each['description'], 'UTF-8', 'UTF-8');
            $info = [
                $each['title'],
                'PHP '.$each['price'],
                str_replace($function->getThatAnnoyingChar(), "",$utf_convert),
                $each['condition'],
            ];

            // same as search
            $hookfeed[] = [
                'no'                =>  $count,
                'id'                =>  $each['idproduct'],
                'title'             =>  $each['title'],
                'snippet'           =>  $info,
                'link'              =>  'https://allgamegeek.com/product/hook/'.$each['idproduct'],
                'image'             =>  $baseURL.$image['image'],
                'thumbnailimage'    =>  $baseURL.$image['image'],
                'source'            =>  'hook'
            ];
            $count++;
        }

        return $hookfeed;
    }

    /**
     * method to validate post product request
     * 
     * @param Object $validate
     * @return Boolean
     */
    protected function validatePostProduct($validate) {
        $user = new User();
        
        // lets validate
        $validator = Validator::make($validate, [
            'title'         => 'required',
            'description'   => 'required',
            'price'         => 'required',
            'categoryid'    => 'required|integer',
            'iduser'        => 'required|integer',
            'condition'     => 'required',
            'primary_image' => 'required',
        ]);
        
        // also to check categoryID and user ID
        $catlist = in_array($validate['categoryid'], config('corousell_category'), true);
        $user = $user->isIDExist($validate['iduser']);

        /**
         * me: if something went wrong on our validation then say something.
         * you: something.
         */
        if ($validator->fails()) {
            return false;
        }elseif(!$catlist){
            return false;
        }elseif(!$user){
            return false;
        }else{
            return true;
        }
        
    }

    /**
     * method to validate post product request
     * 
     * @param Object $validate
     * @return Boolean
     */
    protected function validateUpdateProduct($validate) {
        $user = new User();
        
        // lets validate
        $validator = Validator::make($validate, [
            'categoryid'    => 'required|integer',
            'iduser'        => 'required|integer',
            'idproduct'     => 'required',
        ]);
        
        // also to check categoryID and user ID
        $catlist = in_array($validate['categoryid'], config('corousell_category'), true);
        $user = $user->isIDExist($validate['iduser']);

        /**
         * me: if something went wrong on our validation then say something.
         * you: something.
         */
        if ($validator->fails()) {
            return false;
        }elseif(!$catlist){
            return false;
        }elseif(!$user){
            return false;
        }else{
            return true;
        }
        
    }

    /**
     * pagination trick for eloquent
     * using skip and take method
     * 
     * @param $page
     * @return array($skip, $page)
     */
    protected function paginateHook($page) {
        if($page == null || $page == 0 || $page == 1 ) {
            return array(
                'skip'  => 0,
                'page'  => 10
            );
        }

        $page = $page * 10;
        $skip = $page - 10;
        return array(
            'skip'  => $skip,
            'page'  => $page
        );
    }

    /**
     * method to insert hook product images
     * 
     * @param $imageObj, $path, $id
     */
    protected function savePostImages($imageObj, $path, $id, $primary_image) {
        $save_image = new ProductPhotoController();        

        if(!is_array($imageObj)) {
            $imageObj = array($imageObj);
        }

        foreach($imageObj as $image) {
            $newphoto = '';

            // create new name for the image
            $time = time();
            $name = md5($image->getClientOriginalName());                

            $newphoto = $name.$time.'.'.$image->getClientOriginalExtension();
            $image_name = $path.$newphoto;

            // lets save the image into the table
            if($image->getClientOriginalName() === $primary_image) {
                $save_image->insertImage($id, $image_name, 'primary', $primary_image);
            } else {
                $save_image->insertImage($id, $image_name, 'gallery', $primary_image);
            }            

            // next move the image
            $image->move($path,$newphoto);
        }
    }

    /**
     * method to check if they try to delete the priomary image
     * of the product. and not puppling a new primary image
     * 
     * @param Array $imageObj, $idproduct, $primary_image
     * @return Boolean
     */
    protected function checkIfThereIsNoPrimaryLeft(array $imageObj, $idproduct, $primary_image) {
        $photo = new ProductPhotoController();

        // lets check if they try to delete the primary image and there is no new primary image is supplied
        $this_is_primaryID = $photo->getPrimaryImage($idproduct);
        foreach($imageObj as $checkprimaryID) {
            if($this_is_primaryID == $checkprimaryID && $primary_image == "") {
                return false;
            }
            return true;
        }
    }
}
