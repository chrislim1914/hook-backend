<?php

namespace App;

use Validator;
use Illuminate\Database\Eloquent\Model;

class Ads extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'idads', 'adstitle', 'adsimage', 'adsspaces', 'adslink', 'adsstart', 'adsend', 
    ];

    static function validateAdsCreate($request) {
        // lets validate
        $validator = Validator::make($request, [
            'adstitle'          => 'required',
            'adsimage'          => 'required',
            'adsspaces'         => 'required',
            'adslink'           => 'required',
            'adsstart'          => 'required',
            'adsend'            => 'required',
        ]);
        /**
         * me: if something went wrong on our validation then say something.
         * you: something.
         */
        if ($validator->fails()) {
            return false;       
        }else{
            return true;
        }
    }
}
