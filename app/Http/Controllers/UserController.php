<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Functions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Support\Facades\File;
use Jdenticon\Identicon;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserController extends Controller
{
    public $user;
    protected $jwt;

    public function __construct(JWTAuth $jwt, User $user) {
        $this->user = $user;
        $this->jwt = $jwt;
    }

    /**
     * method to sign-up/sign-in user using google/facebook account
     * 
     * @param $request $request
     * @return JSON
     */
    public function snsSignupSignin(Request $request) {

        // lets get everything we need
        $email          = $request->email;
        $profile_photo  = $request->profile_photo;
        $snsproviderid  = $request->snsproviderid;

        // lets check if email exist
        $emailexist = $this->user->isemailExist($email);

        // lets check if snsprovider exist
        $snsexist = $this->user->issnsprovideridExist($snsproviderid);

        if($emailexist == true || $snsexist == true) {

            // if already exist then auth the user and send jwt
            $authuser = $this->authSnsUser($snsproviderid);

            return response()->json([
                'token'     => $authuser['message'],
                'result'    => $authuser['result']
            ]);
        }

        // lets create username by imploding email address
        $username = explode("@", $email);
        $username = preg_replace("/[^a-zA-Z0-9]/", "", $username[0]);

        // lets see if there is userphoto
        if($profile_photo != null || $profile_photo != '') {
            $profile_photo = array(
                'url'       =>  'yes',
                'folder'    =>  '',
                'filename'  =>  $profile_photo,
                'identicon' =>  ''
            );
        }else{
            $profile_photo = $this->createIdenticon($username);
        }

        // if not then insert the data and auth the new user also send jwt
        $newuser = array(
            'email'             => $email,
            'username'          => $username,
            'password'          => '',
            'birthdate'         => '',
            'snsproviderid'     => $snsproviderid,
        );

        $issave = $this->insertUser($newuser, $profile_photo);

        if($issave == false) {
            return response()->json([
                'message'   => 'Failed to create new user!',
                'result'    => false
            ]);
        }

        $authuser = $this->authSnsUser($snsproviderid);

        return response()->json([
            'token'   => $authuser['message'],
            'result'    => $authuser['result']
        ]);

    }

    /**
     * method to authenticate SNS user
     * 
     * @param $credential
     * @return array
     */
    protected function authSnsUser($credential) {
        $currentuser = $this->user::where('snsproviderid', $credential)->first();
        if($currentuser == null) {
            return array(
                'message'   => 'User not found!',
                'result'    => false
            );
        }
        try {

            if (! $token = $this->jwt->fromUser($currentuser)) {
                return array(
                    'message'   => 'Error Authenticating SNS user!',
                    'result'    => false
                );
            }

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return array(
                'message'   => 'Error Authenticating SNS user!',
                'result'    => false
            );

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return array(
                'message'   => 'Error Authenticating SNS user!',
                'result'    => false
            );

        }
        return array(
            'message'   => $token,
            'result'    => true
        );
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

        $identicon = $this->createIdenticon($request->username);

        // lets create new user
        $data = array(
            'email'             => $request->email,
            'username'          => $request->username,
            'password'          => $this->hashPassword($request->password),
            'birthdate'         => $request->birthdate,
            'snsproviderid'     => $request->has('snsproviderid') ? $request->snsproviderid : ''
        );

        $saveuser = $this->insertUser($data, $identicon);

        if($saveuser == false) {
            return response()->json([
                'message'   => 'Failed to create new user!',
                'result'    => false
            ]);
        }

        return response()->json([
            'message'   => '',
            'result'    => true
        ]);
    }

    /**
     * method to insert new user
     * 
     * @param array $userdata
     * @param array $userimage
     * 
     * @return Boolean
     */
    protected function insertUser($userdata, $userimage) {
        $email          = $userdata['email'];
        $username       = $userdata['username'];
        $password       = $userdata['password'];
        $birthdate      = $userdata['birthdate'];
        $profile_photo  = $userimage['url'] === 'yes' ? $userimage['filename'] : $userimage['folder'].$userimage['filename'];
        $snsproviderid  = $userdata['snsproviderid'];

        // ok let save the new user
        $this->user->email          = $email;
        $this->user->username       = $username;
        $this->user->password       = $password;
        $this->user->birthdate      = $birthdate;
        $this->user->profile_photo  = $profile_photo;
        $this->user->snsproviderid  = $snsproviderid;

        if($this->user->save()) {
            if($userimage['url'] === 'no') {
                file_put_contents($userimage['folder'].$userimage['filename'], $userimage['identicon']);
            }            
            return  true;
        } else {
            return false;
        }
    }

    /**
     * method to create Identicon
     * 
     * @param $username
     * @return array
     */
    protected function createIdenticon($username) {
        // lets create Identicon

        // lets create time for name purpose
        $name = time();

        $img = new \Jdenticon\Identicon();
        $img->setValue($username);
        $img->setSize(150);
        
        $folderdir = 'img/user/'.$username.'_'.$name.'/profile/';
        File::makeDirectory($folderdir, 0777, true);

        /**
         * we need to Turn on output buffering coz there's no way we can get the image
         * then Clean (erase) the output buffer and turn off output buffering
         */
        ob_start();
            $photo  = $img->displayImage('png');
            $binary = $img->getImageData('png');
            $identicon = ob_get_contents();
        ob_end_clean();

        // name of the temporary profile image
        $filename = $username.'_'.$name.'.png';

        return array(
            'url'       => 'no',
            'folder'    => $folderdir,
            'filename'  => $filename,
            'identicon' => $identicon
        );
    }

    /**
     * method to upload user profile photo
     * TODO i change the path folder
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
                'data'      => 'failed to get user info',
                'result'    => false
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
