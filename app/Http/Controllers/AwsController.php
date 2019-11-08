<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Aws\Translate\TranslateClient; 
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Config;

class AwsController extends Controller
{
    public function awsTRanlate() {
        $client = new TranslateClient(config('aws'));

        $currentLanguage = 'en';

        // If the TargetLanguageCode is not "en", the SourceLanguageCode must be "en".
        $targetLanguage= 'ko';


        $textToTranslate = 'PH resumes stamping Chinese passports with disputed 9-dash line';

        try {
            $result = $client->translateText([
                'SourceLanguageCode' => $currentLanguage,
                'TargetLanguageCode' => $targetLanguage, 
                'Text' => $textToTranslate, 
            ]);
            var_dump($result);
        }catch (AwsException $e) {
            // output error message if fails
            echo $e->getMessage();
            echo "\n";
        }
    }
}
