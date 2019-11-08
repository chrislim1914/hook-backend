<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Support\Facades\File;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable;
    use Notifiable;

    protected $primaryKey = 'iduser';
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'iduser', 'email', 'username', 'password', 'contactno', 'birthdate', 'profile_photo', 'snsproviderid', 'emailverify', 'emailverifytoken', 'resetpasswordtoken'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier() {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims() {
        return [];
    }

    /**
     * method to get user ID
     * 
     * @param $iduser
     * @return $user
     */
    public function isIDExist($iduser) {
        $iduser = User::where('iduser', $iduser)->first();
        if($iduser != null){
            return true;
        }else{
            return false;
        }
    }

    /**
     * method to get user data
     * 
     * @param $iduser
     * @return $user
     */
    public function getUserData($iduser) {
        $user = User::where('iduser', $iduser)->first();

        return $user;
    }

    /**
     * method to check if user email exist
     * 
     * @param $email
     * @return Bool
     */
    public function isemailExist($email) {
        $email_exist = User::where('email', $email)->first();
        if($email_exist != null){
            return true;
        }else{
            return false;
        }
    }

    public function issnsprovideridExist($snsproviderid) {
        $snsproviderid_exist = User::where('snsproviderid', $snsproviderid)->first();
        if($snsproviderid_exist != null){
            return true;
        }else{
            return false;
        }
    }

    /**
     * method to check if username exist
     * 
     * @param $username
     * @return Bool
     */
    public function isUsernameExist($username) {
        $username_exist = User::where('username', $username)->first();
        if($username_exist != null){
            return true;
        }else{
            return false;
        }
    }

    /**
     * method to create user folder
     * 
     * @param $username
     * @return $folderdir
     * @return false
     */
    public function createUserFolder($username) {
        // lets create time for name purpose
        $name = time();

        $folderdir = 'img/user/'.$username.'_'.$name.'/';

        if (file_exists($folderdir)) {
            return false;
        } else {
            File::makeDirectory($folderdir, 0777, true);
            return $folderdir;
        }        
    }

    /**
     * method to create user folder for product
     * 
     * @param $username
     * @return $folderdir
     * @return false
     */
    public function createUserFolderProduct($path, $id) {
        // lets create time for name purpose
        $name = time();

        $folderdir = $path.'product_'.$id.'/';

        if (file_exists($folderdir)) {
            return false;
        } else {
            File::makeDirectory($folderdir, 0777, true);
            return $folderdir;
        }        
    }

    /**
     * method to get user image folder
     * 
     * @param $iduser
     * 
     * @return $old_path
     * @return false
     */
    public function getUserFolder($iduser) {
        // first we get the user info
        $gotuser = $this->getUserData($iduser);

        // lets check if the profile_photo is path and not url
        $path = explode("/", $gotuser->profile_photo);
        if($path[0] === 'img') {
            return $old_path = $path[0].'/'.$path[1].'/'.$path[2].'/';
        } else {
           return false;
        }
    }

    public function profilePath($pathphoto) {
        $path = explode("/", $pathphoto);
        if($path[0] === 'img') {
            return 'http://api.allgamegeek.com/'.$pathphoto;
        } else {
           return $pathphoto;
        }
    }
}
