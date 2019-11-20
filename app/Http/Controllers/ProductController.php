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
        $function = new Functions();
        $product    = Product::where('idproduct', $id)->get();

        if(!$product) {
            return false;
        }
        
        
        $viewitem = [];
        
        foreach($product as $new) {
            $photo = ProductPhoto::where('idproduct', $id)->get();
            $media = [];
            $user = User::where('iduser', $new['iduser'])->first();
            $image = $user->getUserFolder($new['iduser']);

            foreach($photo as $newphoto) {
                $media[] = 'https://api.allgamegeek.com/'.$newphoto['image'];
            }

            $seller = [
                'id'            => $user['iduser'],
                'username'      => $user['username'],
                'contactno'     => $user['contactno'],
                'profile_photo' => $image == false ? $user['profile_photo'] : 'https://api.allgamegeek.com/'.$user['profile_photo']
            ];
            $utf_convert = mb_convert_encoding(str_replace($function->getThatAnnoyingChar(), "", $new['description']), 'UTF-8', 'UTF-8');
            $viewitem = [
                'url'               => '',
                'seller'            => $seller,
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
                'photoUrls'     =>  ['https://api.allgamegeek.com/'.$image['image']],
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
            
            // lets save the primary image
            $this->savePostImages($request->primary_image, $newpath, $id, 'primary');
            // lets save the gallery image
            $this->savePostImages($request->image, $newpath, $id, 'gallery');

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
     * method to update hook post product
     * 
     * @param $request
     * @return JSON
     */
    public function updatePost(Request $request) {
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

            if(!$request->hasFile('image')) {
                return response()->json([
                    'message'   => '',
                    'result'    => true
                ]);
            }
            // image full path and name
            $newpath = $path.'product_'.$request->idproduct.'/';
            $photo = new ProductPhotoController();
            $photo->deleteImage($request->idproduct);

            // save the image
            $this->savePostImages($request->image, $newpath, $request->idproduct);

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
    public function filterProduct($filter, $page) {

        $paginate = $this->paginateHook($page);

        if($filter == null || $filter == '') {
            return response()->json([
                'message'   => "Search string is empty!",
                'result'    => false
            ]);
        }

        $filter_product = Product::where('categoryid', $filter)->where('status', 'available')->skip($paginate['skip'])->take($paginate['page'])->Orderby('idproduct', 'desc')->get();

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
        $hookfeed = [];
        $count=0;
        foreach($product as $each) {
            $info = [];
            $seller = [];

            $user = User::where('iduser', $each['iduser'])->first();

            $seller = [
                'id'                => $user['iduser'],
                'profilePicture'    => 'https://api.allgamegeek.com/'.$user['profile_photo'],
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
                'image'             =>  'https://api.allgamegeek.com/'.$image['image'],
                'thumbnailimage'    =>  'https://api.allgamegeek.com/'.$image['image'],
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
    protected function savePostImages($imageObj, $path, $id, $for) {
        $save_image = new ProductPhotoController();

        /**
         * check if they give us image as an array
         * if not then convert it to array so that
         * we will not make anymore method
         */
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
            $save_image->insertImage($id, $image_name, $for);

            // next move the image
            $image->move($path,$newphoto);
            echo "photo saved";
        }
    }
}
