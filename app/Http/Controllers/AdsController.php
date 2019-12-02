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

    public function __construct(Ads $ads, Functions $function) {
        $this->ads = $ads;
        $this->function = $function;
        $this->baseURL = Functions::getAppURL();
    }

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

    protected function todayIs() {
        $todayis = $this->function->setDatetime();
        return $todayis->toDateString();
    }

    public function hookAds() {
        // leaderboard
        $leaderboard = $this->randomSelectLeaderboard('leaderboard', 1);

        // rectangle
        $rectangle = $this->randomSelectLeaderboard('rectangle', 1);

        // skyscaper
        $skyscaper = $this->randomSelectLeaderboard('skyscaper', 2);

        // mobile
        $mobile = $this->randomSelectLeaderboard('mobile', 1);

        // return all
        $hookads = [
            'leaderboard'   => $leaderboard,
            'rectangle'     => $rectangle,
            'skyscaper'     => $skyscaper,
            'mobile'        => $mobile,
        ];

        return response()->json([
            'data'      =>  $hookads,
            'result'    =>  true
        ]);
    }

    protected function randomSelectLeaderboard($adsspaces, $num) {
        $newadsdata = [];
        $currentdate = $this->todayIs();

        $adslist = $this->ads::where('adsspaces', $adsspaces)->whereDate('adsend', '>', $currentdate)->get();
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
        $thisisskyscaper = [];
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
    
}
