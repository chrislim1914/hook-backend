<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChinaBRandController extends Controller
{
    private $cb_login           = 'https://gloapi.chinabrands.com/v2/user/login';
    private $cb_category_url    = 'https://gloapi.chinabrands.com/v2/category/index';
    private $cb_list_url        = 'https://gloapi.chinabrands.com/v2/user/inventory';

    public function getList() {
        // check if we got token on getToken() method
        $tokendata = $this->getToken();
        if(!is_array($tokendata)) {
            return response()->json([
                'message'   => $tokendata,
                'result'    => false
            ]);
        }

        $postdata = array(
            'token'         => $tokendata['msg']['token'],
            'type'          => 1,
            // 'date_start'    => '2019-10-05T00:00:00+08:00',
            // 'date_end'      => '2019-10-06T00:00:00+08:00',
            'per_page'      => 10,
            'page_number'   => 1
        );

        $curl = curl_init($this->cb_list_url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $result = curl_exec($curl); 
        var_dump($curl);
        curl_close($curl);

        $jsonlist = json_decode($result, true);

        // if($jsonlist['status'] == 0) {
        //     return $jsonlist['msg'];
        // }
        
        return response()->json([
            'message'   => $jsonlist,
            'result'    => false
        ]);
    }

    /**
     * method to get all category list of china brand
     * 
     * @return $jsoncategory
     */
    public function getCategory() {

        // check if we got token on getToken() method
        $tokendata = $this->getToken();
        if(!is_array($tokendata)) {
            return response()->json([
                'message'   => $tokendata,
                'result'    => false
            ]);
        }

        $post_data = array(
            'token' => $tokendata['msg']['token'],
        );

        $curl = curl_init($this->cb_category_url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        $result = curl_exec($curl); 
        curl_close($curl);

        $jsoncategory = json_decode($result, true);

        if($jsoncategory['status'] == 0) {
            return $jsoncategory['msg'];
        }

        return $jsoncategory;
    }

    /**
     * method to login onto china brand
     * and get token to utilize in other methods
     * 
     * @return $jsondata
     */
    public function getToken() {
        // get credentials
        $credentials = $this->getCBCredential();
       
        // build payload
        $client_secret = $credentials['secret'];
        $data = array(
            'email'     => $credentials['email'],
            'password'  => $credentials['password'],
            'client_id' => $credentials['key']
        );

        $json_data = json_encode($data);
        $signature_string = md5($json_data.$client_secret);
        $post_data = 'signature='.$signature_string.'&data='.urlencode($json_data);

        // use cURL to call
        $curl = curl_init($this->cb_login);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        $result = curl_exec($curl);
        
        // check if we successed
        $jsondata = json_decode($result, true);

        if($jsondata['status'] == 0) {
            return $jsondata['msg'];
        }
        
        return $jsondata;
    }
    /**
     * method to get china brand credentials
     * 
     * @return array
     */
    public function getCBCredential() {        
        return array(
            'key'       => env('CHINABRANDKEY'),
            'secret'    => env('CHINABRANDSECRET'),
            'email'     => env('CHINABRANDEMAIL'),
            'password'  => env('CHINABRANDPASSWORD'),
        );
    }
}
