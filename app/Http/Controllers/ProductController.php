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

    // this is for front page only
    public function loadOurProduct() {

        $product    = Product::Orderby('idproduct', 'desc')->get();

        $hookfeed = [];
        foreach($product as $each) {
            $info = [];
            $seller = [];

            $user = User::where('iduser', $each['iduser'])->first();

            $seller = [
                'id'                => $user['iduser'],
                'profilePicture'    => '/'.$user['profile_photo'],
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
                'id'            =>  $each['idproduct'],
                'seller'        =>  $seller,
                'photoUrls'     =>  ['/'.$image['image']],
                'info'          =>  $info,
                // 'location'      =>  $innercfeed['marketPlace']['name'],
                // 'coordinates'   =>  $innercfeed['marketPlace']['location'],
                'source'        =>  'Hook'
            ];
        }
        return response()->json([
            'data'      => $hookfeed,
            'result'    => true
        ]);
    }

    // feed hook post for buy and sell page
    public function feedHook() {
        
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
}
