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
use App\Product;
use App\ProductPhoto;
use App\Http\Controllers\SendMail;

class UserController extends Controller
{
    public $user;
    protected $jwt;
    protected $function;

    public function __construct(JWTAuth $jwt, User $user, Functions $function) {
        $this->user = $user;
        $this->jwt = $jwt;
        $this->function = $function;
    }

    /**
     * method to create link with token to verify email address
     * 
     * @param $request
     * @return JSON
     */
    public function verifyEmailUrl(Request $request) {
        $send = $this->createEmailUrl($request->iduser, 'verify');
        if(!$send) {
            return response()->json([
                'message'   => 'Error sending email!',
                'result'    => false
            ]);
        }

        return response()->json([
            'message'   => '',
            'result'    => true
        ]);
    }

    /**
     * method to verify the link and update the user emailverify = 1
     * 
     * @param $request
     * @return JSON
     */
    public function verifyUrl($payload) {

        $token = $this->function->dismantleVerifyLink($payload);

        if(!$token) {
            return response()->json([
                'message'   => 'Payload error!',
                'result'    => false
            ]);
        }

        // checkmail
        $checkmail = $this->user->isemailExist($token['email']);

        if(!$checkmail) {
            return response()->json([
                'message'   => 'Failed to retrieved User Data!',
                'result'    => false
            ]);
        }

        $checkuser = $this->user::where('iduser', $token['id'])->first();

        $checkthis = $token['reason'] === 'verify' ? 'emailverifytoken' : 'resetpasswordtoken' ;

        // check if the user request the token
        if( $checkuser == null || $checkuser[$checkthis] == null || $checkuser[$checkthis] == '') {
            return response()->json([
                'message'   => 'you did not request for verification!',
                'result'    => false
            ]);
        }

        $current_user = $this->user::where('email', $token['email']);

        $updatethis = $token['reason'] === 'verify' ? [
            'emailverify'       => 1,
            'emailverifytoken'  => ''
            ]
        : [
            'resetpasswordtoken'  => ''
        ];

        $current_user->update($updatethis);

        return response()->json([
            'message'   => '',
            'result'    => true
        ]);
    }

    /**
     * method to reset password after the user verify the reset-password email
     * 
     * @param $request
     * @return JSON
     */
    public function resetPassword(Request $request) {

        // we neeed JWT token to authenticate that we have 
        $header = $request->header('Authorization');

        // remove the first 7 character
        $token = substr($header, 7);

        // get the original payload
        $clearuser = $this->function->dismantleVerifyLink($token);

        if(!$clearuser) {
            return array(
                'message'   => 'Token is invalid!',
                'result'    => false
            );
        }

        // get user data
        $thisuser = $this->user->getUserData($clearuser['id']);

        // if false then return false
        if($thisuser == null) {
            return array(
                'message'   => 'User not found!',
                'result'    => false
            );
        }

        // AUTH the user
        $changepass = $this->user::where('iduser', $thisuser['iduser']);

        $changepass->update([
            'password'  => $this->hashPassword($request->password)
        ]);

        $jwtFromUser = $this->createjwtFromUser($thisuser);

        if($jwtFromUser['result'] == false) {
            return response()->json([
                'message'   => $jwtFromUser['message'],
                'result'    => $jwtFromUser['result']
            ]);
        }

        return response()->json([
            'token'     => $jwtFromUser['data'],
            'result'    => $jwtFromUser['result']
        ]);
    }

    /**
     * method to change password traditionally
     * 
     * @param $request
     * @return JSON
     */
    public function changePassword(Request $request) {
        // set the var we need
        $iduser         = $request->iduser;
        $newpassword    = $request->newpassword;
        $oldpassword    = $request->oldpassword;

        // check user
        $usertochange = $this->user->getUserData($iduser);

        if($usertochange == null) {
            return array(
                'message'   => 'User not found!',
                'result'    => false
            );  
        }

        // check old password to the database
        if(!$this->function->verifyPassword($oldpassword, $usertochange['password'])) {
            return array(
                'message'   => 'Password is not same as store in database!',
                'result'    => false
            );  
        }

        // ok lets change that password
        $changepass = $this->user::where('iduser', $iduser);

        $changepass->update([
            'password'  => $this->hashPassword($newpassword)
        ]);

        return response()->json([
            'message'   => '',
            'result'    => true
        ]);

    }

     /**
     * method to create link with token to reset password
     * 
     * @param $request
     * @return JSON
     */
    public function forgetPasswordEmail(Request $request) {
        $thisuser = $this->user::where('email', $request->email)->first();

        if($thisuser == null) {
            return response()->json([
                'message'   => 'User not found!',
                'result'    => false
            ]);
        }

        $send = $this->createEmailUrl($thisuser['iduser'], 'reset');

        if(!$send) {
            return response()->json([
                'message'   => 'Error sending email!',
                'result'    => false
            ]);
        }

        return response()->json([
            'message'   => '',
            'result'    => true
        ]);
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
        $firstname      = !$request->has('firstname') || $request->firstname == '' ? '' : $request->firstname;
        $lastname       = !$request->has('lastname') || $request->lastname == '' ? '' : $request->lastname;
        $profile_photo  = $request->profile_photo;
        $snsproviderid  = $request->snsproviderid;

        // lets check if email exist
        $emailexist = $this->user->isemailExist($email);

        // lets check if snsprovider exist
        $snsexist = $this->user->issnsprovideridExist($snsproviderid);

        if($emailexist == true || $snsexist == true) {

            // if already exist then auth the user and send jwt
            $authuser = $this->authSnsUser($email, $snsproviderid);

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
            'firstname'         => $firstname,
            'lastname'          => $lastname,
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

        $authuser = $this->authSnsUser($email, $snsproviderid);

        return response()->json([
            'token'     => $authuser['message'],
            'result'    => $authuser['result']
        ]);

    }

    /**
     * method to authenticate SNS user
     * 
     * @param $credential
     * @return array
     */
    protected function authSnsUser($email, $snsproviderid) {
        $snscurrentuser = $this->user::where('snsproviderid', $snsproviderid)->first();
        $emailcurrentuser = $this->user::where('email', $email)->first();

        if($snscurrentuser == null && $emailcurrentuser == null) {
            return array(
                'message'   => 'User not found!',
                'result'    => false
            );
        }elseif($snscurrentuser == null) {
            $jwtFromUser = $this->createjwtFromUser($emailcurrentuser);
        }else {
            $jwtFromUser = $this->createjwtFromUser($snscurrentuser);
        }

        if($jwtFromUser['result'] == false) {
            return array(
                'message'   => $jwtFromUser['message'],
                'result'    => $jwtFromUser['result']
            );
        }

        return array(
            'message'   => $jwtFromUser['data'],
            'result'    => $jwtFromUser['result']
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
            'firstname'         => $request->firstname,
            'lastname'          => $request->lastname,
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

        if(!$current_user) {
            return response()->json([
                'message'   => 'User not found!',
                'result'    => false
            ]);
        }

        // if no folder then we create new folder
        if($current_user->profile_photo == null) {
            $folderdir = 'img/user/'.$current_user['username'].'_'.$name.'/profile/';
            File::makeDirectory($folderdir, 0777, true);
        } else {
            // lets see if the image is on local server or url
            $checkpath = $this->user->getUserFolder($request->iduser);

            if(!$checkpath) {
                // its false means URL image. lets create folder for user
                $folderdir = 'img/user/'.$current_user['username'].'_'.$name.'/profile/';
                File::makeDirectory($folderdir, 0777, true);
            }else{
                // we just get the old folder path and
                // explode the string
                $path = explode("/", $current_user->profile_photo);
                $oldPath = $path[0].'/'.$path[1].'/'.$path[2].'/';

                $folderdir = $oldPath.'profile/';
            }
            
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
                'message'   => 'Failed to update!',
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

    /**
     * method to get user data
     * 
     * @param $request
     * @return JSON $thisuser
     */
    public function getUserData(Request $request) {
        $header = $request->header('Authorization');

        try {
            if(! $decoded = Auth::getPayload($header)->toArray()){
                return response()->json([
                    'message'   => 'access denied',
                    'result'    => false
                ]);
            }

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'message'   => 'Invalid Token',
                'result'    => false
            ]);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'result'=>false
            ]);

        }

        $thisuser = $this->user->getUserData($decoded['sub']);

        // treat the user profile image if local or url 
        $image = $this->user->getUserFolder($thisuser['iduser']);

        if($thisuser != null) {
            return response()->json([
                'data'      => [
                    'iduser'        => $thisuser['iduser'],
                    'email'         => $thisuser['email'],
                    'firstname'     => $thisuser['firstname'],
                    'lastname'      => $thisuser['lastname'],
                    'username'      => $thisuser['username'],
                    'birthdate'     => $thisuser['birthdate'],
                    'contactno'     => $thisuser['contactno'],
                    'profile_photo' => $image == false ? $thisuser['profile_photo'] : 'https://api.allgamegeek.com/'.$thisuser['profile_photo'],
                    'snsproviderid' => $thisuser['snsproviderid'],
                    'emailverify'   => $thisuser['emailverify'],
                    'created_at'    => $thisuser['created_at']->toDateString(),
                    'updated_at'    => $thisuser['updated_at']->toDateString(),
                ],
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
     * method to retrieved seller info
     * 
     * @param $request
     * @return JSON
     */
    public function getSellerData(Request $request) {
        // check iduser first
        $getseller = $this->user::where('username', $request->username)->first();

        if($getseller == null) {
            return response()->json([
                'message'      => 'User not found!',
                'result'    => false
            ]);
        }

        // treat the user profile image if local or url 
        $image = $this->user->getUserFolder($getseller['iduser']);

        if($getseller != null) {
            return response()->json([
                'data'      => [
                    'username'      => $getseller['username'],
                    'firstname'     => $getseller['firstname'],
                    'lastname'      => $getseller['lastname'],
                    'birthdate'     => $getseller['birthdate'],
                    'contactno'     => $getseller['contactno'],
                    'profile_photo' => $image == false ? $getseller['profile_photo'] : 'https://api.allgamegeek.com/'.$getseller['profile_photo'],
                    'emailverify'   => $getseller['emailverify'],
                    'created_at'    => $getseller['created_at']->toDateString(),
                    'updated_at'    => $getseller['updated_at']->toDateString(),
                ],
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
     * method to display user posted product with pagination
     * 
     * @param Request $request
     * @return JSON
     */
    public function userPostProduct(Request $request) {
        // check iduser first
        $getusername = $this->user::where('username', $request->username)->first();
        $view = (!$request->has('view') || $request->view === '') ? '' : $request->view;

        if($getusername == null) {
            return response()->json([
                'message'      => 'User not found!',
                'result'    => false
            ]);
        }

        $paginate = $this->paginateHook($request->page);
        if($view === 'public') {
            $product = Product::where('iduser', $getusername['iduser'])->having('status', 'available')->orderBy('idproduct', 'desc')->skip($paginate['skip'])->take(10)->get();
        } elseif($view === 'private') {
            $product = Product::where('iduser', $getusername['iduser'])->orderBy('idproduct', 'desc')->skip($paginate['skip'])->take(10)->get();
        } else {
            return response()->json([
                'message'   => 'view method is empty!',
                'result'    => false
            ]);
        }
        

        if($product == null) {
            return response()->json([
                'data'      => [],
                'result'    => false
            ]);
        }

        $hookfeed = [];
        foreach($product as $each) {
            $info = [];

            $user = User::where('iduser', $each['iduser'])->first();

            $image = ProductPhoto::where('idproduct', $each['idproduct'])->first();

            $info = [
                $each['title'],
                $each['price'],
                $each['description'],
                $each['condition'],
            ];

            // same as search
            $hookfeed[] = [
                'id'                =>  $each['idproduct'],
                'title'             =>  $each['title'],
                'snippet'           =>  $info,
                'link'              =>  'https://hook.com/p/'.$each['idproduct'],
                'image'             =>  'http://api.allgamegeek.com/'.$image['image'],
                'thumbnailimage'    =>  'http://api.allgamegeek.com/'.$image['image'],
                'status'            =>  $each['status'],
                'source'            =>  'hook'
            ];
        }

        return response()->json([
            'data'      => $hookfeed,            
            'total'     => count($product),
            'result'    => true
        ]);
    }

    /**
     * method to update user profile
     * contact no, username, first name, last name, birthdate
     * 
     * @param $request
     * @return JSON
     */
    public function updateProfile(Request $request) {

        $iduser = $request->iduser;

        // lets check first if ID exist
        $checkid = $this->user->isIDExist($iduser);
        if(!$checkid) {
            return response()->json([
                'message'      => 'User not found!',
                'result'    => false
            ]);
        }

        // lets check username
        $checkusername = $this->user->isUsernameExist($request->username, $iduser);
        if(!$checkusername) {
            return response()->json([
                'message'      => 'Username already use!',
                'result'    => false
            ]);
        }

        // update the user info
        $updateuser = $this->user::where('iduser', $iduser);

        $updateuser->update([
            'username'  =>  $request->username,
            'firstname' =>  $request->firstname,
            'lastname'  =>  $request->lastname,
            'contactno' =>  $request->contactno,
            'birthdate' =>  $request->birthdate
        ]);

        return response()->json([
            'message'   => '',
            'result'    => true
        ]);

    }

    /**
     * method to create jwt token using fromUser() JWT method
     * 
     * @param Object $currentuser
     * @return Array
     */
    protected function createjwtFromUser($userdata) {
        try {
            if (! $token = $this->jwt->fromUser($userdata)) {
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
            'data'      => $token,
            'result'    => true
        );
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
     * method to send email to user
     * 
     * @param $iduser, $for
     * @return Boolean
     */
    protected function createEmailUrl($iduser, $for) {
        
        $currentuser = $this->user->getUserData($iduser);

        $url = $this->function->createVerifyEmailLink($iduser, $for);

        if($for === 'verify') {
            $send = new SendMail();
                try {
                    $sendit = $send->verifyEmail($currentuser['email'], $currentuser['username'], $url);
                    return true;
                } catch (ErrorException $e) {
                    return false;
                }
        }elseif($for === 'reset') {
            $send = new SendMail();
                try {
                    $sendit = $send->resetEmail($currentuser['email'], $currentuser['username'], $url);
                    return true;
                } catch (ErrorException $e) {
                    return false;
                }
        }        
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
        $firstname      = $userdata['firstname'];
        $lastname       = $userdata['lastname'];
        $username       = $userdata['username'];
        $password       = $userdata['password'];
        $birthdate      = $userdata['birthdate'];
        $profile_photo  = $userimage['url'] === 'yes' ? $userimage['filename'] : $userimage['folder'].$userimage['filename'];
        $snsproviderid  = $userdata['snsproviderid'];

        // ok let save the new user
        $this->user->email               = $email;
        $this->user->firstname           = $firstname;
        $this->user->lastname            = $lastname;
        $this->user->username            = $username;
        $this->user->password            = $password;
        $this->user->contactno           = '';
        $this->user->birthdate           = $birthdate;
        $this->user->profile_photo       = $profile_photo;
        $this->user->snsproviderid       = $snsproviderid;
        $this->user->emailverify         = 0;
        $this->user->emailverifytoken    = '';
        $this->user->resetpasswordtoken  = '';

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
}
