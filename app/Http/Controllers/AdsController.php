<?php

namespace App\Http\Controllers;

use App\Ads;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;

class AdsController extends Controller
{
    private $ads;
    private $function;
    public $baseURL;
    private $leaderboard    = 'img/advertisement/default-leaderboard970x90.jpg';
    private $skyscraper     = 'img/advertisement/default-skyscraper120x600.jpg';
    private $rectangle      = 'img/advertisement/default-rectangle300x250.jpg';
    private $mobile         = 'img/advertisement/default-mobile300x250.jpg';
    private $default_url    = 'https://allgamegeek.com';

    /**
     * instantiate Ads, Functions
     */
    public function __construct(Ads $ads, Functions $function) {
        $this->ads = $ads;
        $this->function = $function;
        $this->baseURL = Functions::getAppURL();
    }

    /**
     * method to insert new ads
     * 
     * @param $request
     * @return JSON
     */
    public function createNewAds(Request $request) {
        // validate input
        $checkinput = $this->ads::validateAdsCreate($request->all());

        if(!$checkinput) {
            return response()->json([
                'message'   => 'Input parameter is incorrect!',
                'result'    => false
            ]);
        }
        $adsimage = $request->file('adsimage');
        $folderdir = 'img/advertisement/';
        $time = time();
        $name = md5($adsimage->getClientOriginalName());
        $newphoto = $name.$time.'.'.$adsimage->getClientOriginalExtension();
        $adsimage_fullname = $folderdir.$newphoto;

        // lets save new ads
        $this->ads->adstitle    = $request->adstitle;
        $this->ads->adsimage    = $adsimage_fullname;
        $this->ads->adsspaces   = $request->adsspaces;
        $this->ads->adslink     = $request->adslink;
        $this->ads->adsstart    = $request->adsstart;
        $this->ads->adsend      = $request->adsend;

        if($this->ads->save()) {
            // lets also save the image
            $adsimage->move($folderdir,$newphoto);
            return response()->json([
                'message'   => '',
                'result'    => true
            ]);
        }
    }

    /**
     * method to display hook ads
     * 
     * @return JSON
     */
    public function hookAds() {
        // leaderboard
        $leaderboard = $this->randomSelectads('leaderboard', 1);

        // rectangle
        $rectangle = $this->randomSelectads('rectangle', 1);

        // skyscaper
        $skyscaper = $this->randomSelectads('skyscraper', 2);

        // mobile
        $mobile = $this->randomSelectads('mobile', 1);

        // return all
        $hookads = [
            'leaderboard'   => $leaderboard,
            'rectangle'     => $rectangle,
            'skyscraper'    => $skyscaper,
            'mobile'        => $mobile,
        ];

        return response()->json([
            'data'      =>  $hookads,
            'result'    =>  true
        ]);
    }

    /**
     * method to get current date
     * format yyyy-mm-dd
     * 
     * @return $todayis
     */
    protected function todayIs() {
        $todayis = $this->function->setDatetime();
        return $todayis->toDateString();
    }    

    /**
     * method to select random ads based in adsspaces
     * adsspaces: leaderboard, rectangle, skyscraper, mobile
     * 
     * @param $adsspaces, $num
     * @return $newadsdata
     * @return $thisisskyscaper
     */
    protected function randomSelectads($adsspaces, $num) {        
        $newadsdata = [];
        $currentdate = $this->todayIs();
        $thisisskyscaper = [];

        $adslist = $this->ads::where('adsspaces', $adsspaces)->whereDate('adsend', '>', $currentdate)->get();
        // if got nothing then we get the default image
        if($adslist->count() <= 0) {
            return $this->defaultAds($adsspaces);
        }

        // this will catch if the skyscraper got one one active ads
        if($adslist->count() == 1 && $adsspaces == 'skyscraper') {
            // so get that one active skyscraper ads
            // then add a default skyscraper ad
            $adsdata = array_rand(json_decode(json_encode($adslist), true), 1);
            $thisisskyscaper[] = [
                'idads'         =>  $adslist[$adsdata]['idads'],
                'adstitle'      =>  $adslist[$adsdata]['adstitle'],
                'adsimage'      =>  $this->baseURL.$adslist[$adsdata]['adsimage'],
                'adslink'       =>  $adslist[$adsdata]['adslink'],
            ];

            array_push($thisisskyscaper, [
                        'idads'         =>  0,
                        'adstitle'      =>  'hook default ads',
                        'adsimage'      =>  $this->baseURL.$this->skyscraper,
                        'adslink'       =>  $this->default_url,
            ]);

            return $thisisskyscaper;
        }

        $adsdata = array_rand(json_decode(json_encode($adslist), true), $num);
        
        if(!is_array($adsdata)) {
            $newadsdata = [
                'idads'         =>  $adslist[$adsdata]['idads'],
                'adstitle'      =>  $adslist[$adsdata]['adstitle'],
                'adsimage'      =>  $this->baseURL.$adslist[$adsdata]['adsimage'],
                'adslink'       =>  $adslist[$adsdata]['adslink'],
            ];
            // return $adslist[$adsdata];
            return $newadsdata;
        }
        
        for($i=0;$i<count($adsdata);$i++) {
            // $thisisskyscaper[] = $adslist[$adsdata[$i]];
            $thisisskyscaper[] = [
                'idads'         =>  $adslist[$adsdata[$i]]['idads'],
                'adstitle'      =>  $adslist[$adsdata[$i]]['adstitle'],
                'adsimage'      =>  $this->baseURL.$adslist[$adsdata[$i]]['adsimage'],
                'adslink'       =>  $adslist[$adsdata[$i]]['adslink'],
            ];
        }
        
        return $thisisskyscaper;
    }

    /**
     * method to get default hook ads template if randomSelectads is null
     * 
     * @param $adsspaces
     * @return $defaultadsdata
     */
    protected function defaultAds($adsspaces) {
        $defaultadsdata = [];

        switch ($adsspaces) {
            case 'leaderboard':
                $defaultadsdata = [
                    'idads'         =>  0,
                    'adstitle'      =>  'hook default ads',
                    'adsimage'      =>  $this->baseURL.$this->leaderboard,
                    'adslink'       =>  $this->default_url,
                ];

                return $defaultadsdata;
            case 'rectangle':
                $defaultadsdata = [
                    'idads'         =>  0,
                    'adstitle'      =>  'hook default ads',
                    'adsimage'      =>  $this->baseURL.$this->rectangle,
                    'adslink'       =>  $this->default_url,
                ];

                return $defaultadsdata;
            case 'skyscraper':
                for($i=0;$i<2;$i++){
                    $defaultadsdata[] = [
                        'idads'         =>  $i,
                        'adstitle'      =>  'hook default ads',
                        'adsimage'      =>  $this->baseURL.$this->skyscraper,
                        'adslink'       =>  $this->default_url,
                    ];
                }
                return $defaultadsdata;
            case 'mobile':
                $defaultadsdata = [
                    'idads'         =>  0,
                    'adstitle'      =>  'hook default ads',
                    'adsimage'      =>  $this->baseURL.$this->mobile,
                    'adslink'       =>  $this->default_url,
                ];

                return $defaultadsdata;
        }
    }
    
}
