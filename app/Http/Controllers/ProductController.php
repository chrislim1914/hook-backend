<?php

namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\Product;
use App\ProductPhoto;
use App\User;
use App\Http\Controllers\ProductPhotoController;
use Illuminate\Support\Facades\Config;

class ProductController extends Controller
{   
    /**
     * method to load post product in the front page
     * 
     * @return $hookfeed
     */
    public function loadOurProduct() {

        $product    = Product::Orderby('idproduct', 'desc')->get();

        $hookfeed = [];
        $count=0;

        foreach($product as $each) {
            $info = [];
            $seller = [];

            $user = User::where('iduser', $each['iduser'])->first();

            $seller = [
                'id'                => $user['iduser'],
                'profilePicture'    => 'http://api.geeknation.info/'.$user['profile_photo'],
                'username'          => $user['username'],
            ];

            $image = ProductPhoto::where('idproduct', $each['idproduct'])->first();

            $info1 = [
                'stringContent' => $each['title'],
            ];

            $info2 = [
                'stringContent' => $each['price'],
            ];

            $info3 = [
                'stringContent' => $each['description'],
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
                'photoUrls'     =>  ['http://api.geeknation.info/'.$image['image']],
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

        $product    = Product::Orderby('idproduct', 'desc')->skip($paginate['skip'])->take($paginate['page'])->get();

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
        $product->post        = 'yes';

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
               
            $save_image = new ProductPhotoController();
            // now move the image to its folder and save
            foreach($request->image as $image) {
                $newphoto = '';

                // create new name for the image
                $time = time();
                $name = md5($image->getClientOriginalName());                

                $newphoto = $name.$time.'.'.$image->getClientOriginalExtension();
                $image_name = $newpath.$newphoto;

                // lets save the image into the table
                $save_image->insertImage($id, $image_name);

                // next move the image
                $image->move($newpath,$newphoto);
            }
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
     * method to do search on our own product
     * 
     * @param $request
     * @return JSON 
     */
    public function searchProduct($search, $page) {
        
        $paginate = $this->paginateHook($page);

        if($search == null || $search == '') {
            return response()->json([
                'message'   => "Search string is empty!",
                'result'    => false
            ]);
        }

        $search_product = Product::where('title', 'LIKE', "%{$search}%")->skip($paginate['skip'])->take($paginate['page'])->Orderby('idproduct', 'desc')->get();

        // return noting if null
        if($search_product == null) {
            return response()->json([
                'data'      => [],
                'total'     => [],
                'result'    => true
            ]);
        }
        
        $searchproduct = $this->createProductJsonData($search_product);

        return response()->json([
            'data'      => $searchproduct,            
            'total'     => count($search_product),
            'result'    => true
        ]);
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

        $filter_product = Product::where('categoryid', $filter)->skip($paginate['skip'])->take($paginate['page'])->Orderby('idproduct', 'desc')->get();

        // return noting if null
        if($filter_product == null) {
            return response()->json([
                'data'      => [],
                'total'     => [],
                'result'    => true
            ]);
        }

        $filterproduct = $this->createProductJsonData($product);

        return response()->json([
            'data'      => $filterproduct,            
            'total'     => count($filter_product),
            'result'    => true
        ]);
    }

    /**
     * method to create product JSON data
     * 
     * @param Object $product
     * @return $hookfeed
     */
    protected function createProductJsonData($product) {

        $hookfeed = [];
        $count=0;
        foreach($product as $each) {
            $info = [];
            $seller = [];

            $user = User::where('iduser', $each['iduser'])->first();

            $seller = [
                'id'                => $user['iduser'],
                'profilePicture'    => 'http://api.geeknation.info/'.$user['profile_photo'],
                'username'          => $user['username'],
            ];

            $image = ProductPhoto::where('idproduct', $each['idproduct'])->first();

            $info = [
                $each['title'],
                $each['price'],
                $each['description'],
                $each['condition'],
            ];

            // same as search
            $hookfeed[] = [
                'no'                =>  $count,
                'id'                =>  $each['idproduct'],
                'title'             =>  $each['title'],
                'snippet'           =>  $info,
                'link'              =>  'https://hook.com/p/'.$each['idproduct'],
                'image'             =>  'http://api.geeknation.info/'.$image['image'],
                'thumbnailimage'    =>  'http://api.geeknation.info/'.$image['image'],
                'source'            =>  'Hook'
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
}
