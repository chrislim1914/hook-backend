<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable;
    use Notifiable;

    public $timestamps = false;

    protected $primaryKey = 'iduser';
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'iduser', 'email', 'username', 'password', 'birthdate', 'profile_photo',
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
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
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
}
