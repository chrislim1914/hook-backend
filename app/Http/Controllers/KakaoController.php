<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KakaoController extends Controller
{

    private $kakao_url = 'https://kapi.kakao.com/v1/translation/translate';

    public function kakaoTranslation($item, $countrycode) {

        $lang = $this->kakaoSupportLanguage($countrycode);

        // build the Header
        $header = array(
            "Content-Type: application/x-www-form-urlencoded",
            "Host: kapi.kakao.com",
            "Authorization: KakaoAK ".$this->getCredential()
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->kakao_url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            "src_lang=en&target_lang=$lang&query=$item"
        );

        $result = curl_exec ($ch);

        curl_close ($ch);

        $jsonlist = json_decode($result, true);

        if(is_int($jsonlist) == true || !array_key_exists('translated_text', $jsonlist)) {
            return false;
        }

        return $jsonlist;

    }

    /**
     * method to list kakao API support language for kakao translate
     * 
     * @return $codelist
     */
    protected function kakaoSupportLanguage($countrycode) {

        $codelist = array(
            'en'        => 'en',
            'ko'        => 'kr',
            'ja'        => 'jp',
            'zh_CN'     => 'cn',
        );

        if(array_key_exists($countrycode, $codelist)) {
            return $codelist[$countrycode];
        }

        return $codelist[0];
        
    }
    /**
     * method to get openweather app key
     * 
     * @return $openweatherapikey
     */
    protected function getCredential() {        
        $kakao_api_key  = env('KAKAO_HOOK');
        return $kakao_api_key;
    }
}
