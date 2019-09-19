<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Functions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Support\Facades\File;

class UserController extends Controller
{
    public $user;
    protected $jwt;

    public function __construct(JWTAuth $jwt, User $user) {
        $this->user = $user;
        $this->jwt = $jwt;
    }

    /**
     * method to register new user
     * 
     * @param Request $request
     * @return $response JSON
     */
    public function registerUser(Request $request) {

        // check email and username
        $checkemail = $this->user->isemailExist($request->email);
        $checkusername = $this->user->isUsernameExist($request->username);

        if($checkemail == true) {
            return response()->json([
                'message'   => 'Email already been use!',
                'result'    => false
            ]); 
        }

        if($checkusername == true) {
            return response()->json([
                'message'   => 'Username already been use!',
                'result'    => false
            ]); 
        }

        // ok let save the new user
        $this->user->email      = $request->email;
        $this->user->username   = $request->username;
        $this->user->password   = $this->hashPassword($request->password);
        $this->user->birthdate  = $request->birthdate;

        if($this->user->save()) {
            return response()->json([
                'message'   => '',
                'result'    => true
            ]);
        } else {
            return response()->json([
                'message'   => 'Failed to create new user!',
                'result'    => false
            ]);
        }
    }

    /**
     * method to upload user profile photo
     * 
     * @param Request $request
     * @return response JSON
     */
    public function uploadProfilePhoto(Request $request) {
        //use for renaming photo
        $name = time();

        // check if user already have folder img
        $current_user = $this->user->getUserData($request->iduser);

        // if no folder then we create new folder
        if($current_user->profile_photo == null) {
            $folderdir = 'img/profile/'.$request->iduser.'_'.$name.'/';
            File::makeDirectory($folderdir, 0777, true);
        } else {
            // we just get the old folder path and
            // explode the string
            $path = explode("/", $current_user->profile_photo);
            $oldPath = $path[0].'/'.$path[1].'/'.$path[2].'/';

            $folderdir = $oldPath;
        }        

        $newprofilephoto = '';

        if ($request->hasFile('profile_photo')){
            $profilephoto = $request->file('profile_photo');
            $newprofilephoto = $name.'.'.$profilephoto->getClientOriginalExtension();
        }

        $profile_photo  = $folderdir.$newprofilephoto;

        $user_photo = $this->user::where('iduser', $request->iduser);
        if($user_photo->update([
            'profile_photo' => $profile_photo
            ])) {
            //move the image to its location
            if ($request->hasFile('profile_photo')){
                $profilephoto->move($folderdir,$newprofilephoto);
            }            
            return response()->json([
                'message'   => '',
                'result'    => true
            ]);
        } else {
            return response()->json([
                'message'   => 'Failed to create new employee!',
                'result'    => false
            ]);
        }
    }

    /**
     * method to login user
     * 
     * @param Request $request
     * @return Mix
     */
    public function loginUser(Request $request) {
        try {

            if (!$token = $this->jwt->attempt($request->only('username', 'password'))) {
                return response()->json(['message' => 'username, password not correct','result'=> false], 200);
            }

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            return response()->json(['message'=>'token_invalid','result'=>false], 500);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json(['message' => $e->getMessage(),'result'=>false], 500);

        }

        return response()->json([
            'message' => '',
            'token' => $token,
            'result'  => true
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutUser() {

        Auth::logout(true);

        return response()->json([
            'message'   => '',
            'result'    => true
        ]);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(){
        try {
        $token = Auth::refresh();
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'message'=>'token_invalid',
                'result'=>false
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'result'=>false
            ], 500);
        }

        return response()->json([
            'message'   => '',
            'result'    => true,
            'token'     => $token
        ]);
    }

    public function getUserData(Request $request) {
        $header = $request->header('Authorization');

        try {
            if(! $decoded = Auth::getPayload($header)->toArray()){
                return response()->json(['message' => 'access denied','result'=> false], 200);
            }

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            return response()->json(['message'=>'Invalid Token','result'=>false], 500);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json(['message' => $e->getMessage(),'result'=>false], 401);

        }

        $thisuser = $this->user->getUserData($decoded['sub']);

        if($thisuser != null) {
            return response()->json([
                'data'      => $thisuser,
                'result'    => true
            ]);
        } else {
            return response()->json([
                'data'      => $thisuser,
                'result'    => true
            ]);
        }
    }

    /**
     * method to hash user password
     * 
     * @param $password
     * @return $hash_password
     */
    protected function hashPassword($password) {
        // instantiate Functions and get hash method
        $hash_password = new Functions();

        return $hash_password->hash($password);
    }
}
