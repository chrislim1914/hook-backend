<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;

class IbmController extends Controller
{
    private $default_ibm_endpoint = 'https://gateway.watsonplatform.net/language-translator/api/v3/translate?version=';
   
    public function ibmTranslate($text, $languagecode) {

        $apikey = $this->getCredential();

        if($languagecode === 'ph' || $languagecode === 'en') {
            return $text;
        }

        // get model id
        $mID = $this->getModelID($languagecode);
        if($mID == false) {
            return null;
        }
        // build URL
        $url = $this->default_ibm_endpoint.$this->getCurrentVersion();

        // build header
        $header = array(
            "content-type: application/json",
        );

        // build body
        $data = json_encode(array(
            'text'      => $this->inputCleaner($text),
            'model_id'  => $mID
        ));

        $call = $this->curlCall($url, $header, $data, $apikey);

        if($call == false) {
            return null;
        } else {
            return $call;
        }        
    }

    protected function curlCall($url, $header, $data, $key) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, "apikey:$key");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);        
        $result=curl_exec ($ch);
        curl_close ($ch);

        /**
         * check if we got some error
         * https://cloud.ibm.com/apidocs/language-translator#error-handling
         */
        $jsonlist = json_decode($result, true);
        if(array_key_exists('code', $jsonlist)) {
            return false;
        }

        foreach($jsonlist['translations'] as $trans) {
            $translation = $trans['translation'];
        }
        return $translation;
    }

    protected function inputCleaner($text) {
        //this can be a list of char we want to remove
        $remove[] = '"';

        return $cleantext = str_replace($remove, "", $text);
    }

    /** 
     * method to create current version to use on IBM
     * url/?version={yyyy-mm-dd}
     * @return $version
     */
    protected function getCurrentVersion() {
        $function = new Functions();
        $version = $function->setDatetime();
        $version = $version->toDateString();

        return $version;
    }

    Protected function getModelID($languagecode) {
        $modellist = $this->getModelList();
        if(array_key_exists($languagecode, $modellist)) {
            return $modellist[$languagecode];
        }
        return false;
    }

    /**
     * model list based on IBM https://gateway.watsonplatform.net/language-translator/api/v3/models
     * 
     * @return array
     */
    protected function getModelList(){
        return array(
            'ja'    => 'en-ja',
            'ko'    => 'en-ko',
            'zh'    => 'en-zh'
        );
    }
    /**
     * method to get openweather app key
     * 
     * @return $openweatherapikey
     */
    protected function getCredential() {        
        $ibmkey  = env('IBMAPIKEY');
        return $ibmkey;
    }
}
