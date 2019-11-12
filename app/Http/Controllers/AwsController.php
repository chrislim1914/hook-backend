<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Aws\Translate\TranslateClient; 
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Config;

class AwsController extends Controller
{
    public function awsTRanlate($item, $countrycode) {
        $client = new TranslateClient(config('aws'));

        $currentLanguage = 'en';

        // If the TargetLanguageCode is not "en", the SourceLanguageCode must be "en".
        $targetLanguage= $countrycode;

        try {
            $result = $client->translateText([
                'SourceLanguageCode' => $currentLanguage,
                'TargetLanguageCode' => $targetLanguage, 
                'Text' => $item, 
            ]);

            return $result['TranslatedText'];

        }catch (AwsException $e) {
            return false;
        }
    }
}
