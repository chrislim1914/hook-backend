<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use Pilipinews\Website\Gma\Scraper;
use Pilipinews\Website\Bulletin\Scraper as MBScraper;
use App\Http\Controllers\NewsArticle;

class ScrapController extends Controller
{
    

    public function scrapCarousell(Request $request) {

        $carousell_url = 'https://www.carousell.ph/p/'.$request->id;

        $client = new Client();

        $description = [];
        $newcarousell_item = [];

        $scrapnews = $client->request('GET', $carousell_url);
        $media = $scrapnews->filter('.styles__container___2zBd_ img')->eq(0)->attr('src');
        $price = $scrapnews->filter('.styles__price___K6Kjb')->each(function ($node) {
            return $node->text();
        });
        $itemname = $scrapnews->filter('.styles__titleWrapper___3jSxG h1')->each(function ($node) {
            return $node->text();
        });

        $details = $scrapnews->filter('.styles__body___VSdV5 p')->each(function ($node) {
            return $node->text();
        });

        $desc = $scrapnews->filter('.styles__textTruncate___2Mx1R .styles__overflowBreakWord___2rtT6')->each(function ($node) {
            return $node->text();
        });

        $shipping = $scrapnews->filter('.styles__textWithLeftLabel___20RQO .styles__text___1gJzw')->each(function ($node) {
            return $node->text();
        });

        $locationicon = 'https://sl3-cdn.karousell.com/components/location_v3.svg';
        $listingicon  = 'https://sl3-cdn.karousell.com/components/caroupay_listing_details_v7.svg';
        $conditionicon = 'https://sl3-cdn.karousell.com/components/condition_v3.svg';

        $description = [
            'meetupicon'    => $locationicon,
            'meetup'        => $details[0],
            'listingicon'   => $listingicon,
            'listing'       => $details[1],
            'conditionicon' => $conditionicon,
            'condition'     => $details[2],
            'description'   => $desc[0],
        ];

        $newcarousell_item = [
            'url'               => $carousell_url,
            'media'             => $media,
            'price'             => $price[0],
            'itemname'          => $itemname[0],
            'description'       => $description,
            'Mailing&Delivery'  => $shipping 
        ];

        return response()->json([
            'data'          => $newcarousell_item,
            'result'        => true
        ]);
    }

    /**
     * Rappler.com news Scrapping
     * 
     * @param $url
     */
    public function scrapRapplerNews($url) {
        // prepare the news filter
        $rapplerfilter = array(
            'url'       => $url,
            'title'     => '.select-headline',
            'subtitle'  => '.select-metadesc',
            'publish'   => '.published',
            'editor'    => '.byline',
            'body'      => '.cXenseParse',
            'media'     => '.cXenseParse img',
            'img-link'  => 'data-original',
        );

        $rappler = $this->getNewsData($rapplerfilter);

        if($rappler == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $rappler_data = array(
            'title'     => str_replace($this->getThatAnnoyingChar(),"",$rappler->title()),
            'subtitle'  => str_replace($this->getThatAnnoyingChar(),"",$rappler->subtitle()),
            'publish'   => str_replace($this->getThatAnnoyingChar(),"",$rappler->publish()),
            'editor'    => str_replace($this->getThatAnnoyingChar(),"",$rappler->editor()),
            'image'     => str_replace($this->getThatAnnoyingChar(),"",$rappler->media()),
            'body'      => str_replace($this->getThatAnnoyingChar(),"",preg_replace("/<img[^>]+\>/i", "", $rappler->body())),
            'media'     => '/img/news-img/rappler.jpg',
        );

        return array(
            'body'      => $rappler_data,
            'result'    => true
        );
    }

    /**
     * news.abs-cbn.com news Scrapping
     * 
     * @param $url
     */
    public function scrapAbsCbnNews($url) {
        $is_sports = $this->isAbscbnSports($url);

        // prepare the news filter
        if($is_sports == true) {
            $abscbnfilter = array(
                'url'       => $url,
                'title'     => '.mbr-container h2',
                'subtitle'  => '',
                'publish'   => '.timestamp-entry',
                'editor'    => '.mbr-title span',
                'body'      => '#content2-4 .container .mbr-text',
                'media'     => '.image-block amp-img',
                'img-link'  => 'src',
                'sport'     => 'yes'
            );
        }else {
            $abscbnfilter = array(
                'url'       => $url,
                'title'     => '.news-title',
                'subtitle'  => '',
                'publish'   => '.timestamp-entry',
                'editor'    => '.author-details .editor',
                'body'      => '.article-content',
                'media'     => '.article-content .embed-wrap img',
                'img-link'  => 'src',
            );
        }
        
        $abscbn = $this->getNewsData($abscbnfilter);

        if($abscbn == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $pub = explode('on', str_replace($this->getThatAnnoyingChar(),"",$abscbn->editor()));

        $abscbn_data = array(
            'title'     => str_replace($this->getThatAnnoyingChar(),"",$abscbn->title()),
            'subtitle'  => str_replace($this->getThatAnnoyingChar(),"",$abscbn->subtitle()),
            'publish'   => $is_sports == true ? $pub[1] : str_replace($this->getThatAnnoyingChar(),"",$abscbn->publish()),
            'editor'    => $is_sports == true ? $pub[0] : str_replace($this->getThatAnnoyingChar(),"",$abscbn->editor()),
            'body'      => str_replace($this->getThatAnnoyingChar(),"",preg_replace("/<img[^>]+\>/i", "", $abscbn->body())),
            'image'     => str_replace($this->getThatAnnoyingChar(),"",$abscbn->media()),
            'media'     => '/img/news-img/abscbn.png',
        );

        return array(
            'body'      => $abscbn_data,
            'result'    => true
        );
    }

    /**
     * Cnnphilippines.com CNN Philippines news Scrapping
     * 
     * @param $url
     */
    public function scrapCnnPhilNews($url) {
        // check the url if there is videos on address
        $is_video = $this->findVideoOnCnn($url);

        // prepare the news 
        if($is_video == true) {
            $cnnphilfilter = array(
                'url'       => $url,
                'title'     => '.title',
                'subtitle'  => '',
                'publish'   => '.dateString',
                'editor'    => '.author-byline',
                'body'      => '.article-maincontent-p #content-body-244757-498257',
                'video'     => '.video-container iframe',
                'video-link'=> 'src',
            );
        }else{
            $cnnphilfilter = array(
                'url'       => $url,
                'title'     => '.title',
                'subtitle'  => '',
                'publish'   => '.dateString',
                'editor'    => '.author-byline',
                'body'      => '.article-maincontent-p #content-body-244757-498257',
                'media'     => '.margin-bottom-15 .img-container img',
                'img-link'  => 'src',
            );
        }

        // scrap
        $cnnphil = $this->getNewsData($cnnphilfilter);

        if($cnnphil == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }
        // get youtube video ID
        $ytid = $this->getYTid($cnnphil->media());

        $cnnphil_data = array(
            'title'     => str_replace($this->getThatAnnoyingChar(),"",$cnnphil->title()),
            'subtitle'  => str_replace($this->getThatAnnoyingChar(),"",$cnnphil->subtitle()),
            'publish'   => str_replace($this->getThatAnnoyingChar(),"",$cnnphil->publish()),
            'editor'    => str_replace($this->getThatAnnoyingChar(),"",$cnnphil->editor()),
            'image'     => $is_video === true ? 'https://i.ytimg.com/vi/'.$ytid[4].'/sddefault.jpg' : str_replace($this->getThatAnnoyingChar(),"",'http://cnnphilippines.com'.$cnnphil->media()),
            'body'      => str_replace($this->getThatAnnoyingChar(),"",preg_replace("/<img[^>]+\>/i", "", $cnnphil->body())),
            'media'     => '/img/news-img/cnnphil.png',
        );
        
        return array(
            'body'      => $cnnphil_data,
            'result'    => true
        );
    }

    /**
     * Mb.com.ph news Scrapping
     * 
     * @param $url
     */
    public function scrapMBNews($url) {
        // prepare the news filter
        $mbfilter = array(
            'url'       => $url,
            'title'     => '#tm-content .uk-article-title',
            'subtitle'  => '',
            'publish'   => '.uk-article .published_date',
            'editor'    => '.uk-article em',
            'body'      => '.uk-article',
            'media'     => '.uk-article img',
            'img-link'  => 'src',
        );

        $mb = $this->getNewsData($mbfilter);

        if($mb == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $mb_data = array(
            'title'     => str_replace($this->getThatAnnoyingChar(),"",$mb->title()),
            'subtitle'  => str_replace($this->getThatAnnoyingChar(),"",$mb->subtitle()),
            'publish'   => str_replace($this->getThatAnnoyingChar(),"",$mb->publish()),
            'editor'    => str_replace($this->getThatAnnoyingChar(),"",$mb->editor()),
            'image'     => str_replace($this->getThatAnnoyingChar(),"",$mb->media()),
            'body'      => str_replace($this->getThatAnnoyingChar(),"",preg_replace("/<img[^>]+\>/i", "", $mb->body())),
            'media'     => '/img/news-img/mb.png',
        );

        return array(
            'body'      => $mb_data,
            'result'    => true
        );
    }

    /**
     * Gmanetwork.com news Scrapping
     * 
     * @param $url
     */
    public function scrapGmaNews() {
        // $url = 'https://www.gmanetwork.com/news/news/nation/712384/iranian-beauty-queen-barred-from-entering-phl-over-assault-charges-doj/story/';

        // $client = new Client();
        // $scrapnews = $client->request('GET', $url);

        // // get news title
        // $title = $scrapnews->filter('.storyContainer #story_container')->each(function ($node) {
        //     return $node->html();
        // });
        // var_dump($title);
        // $gma_data = array(
        //     'title'      => str_replace($this->getThatAnnoyingChar(),"",$title[0]),
        //     // 'subtitle'  => str_replace($this->getThatAnnoyingChar(),"",$title),
        //     // 'publish'   => str_replace($this->getThatAnnoyingChar(),"",$title),
        //     // 'editor'    => str_replace($this->getThatAnnoyingChar(),"",$title),
        //     // 'image'     => str_replace($this->getThatAnnoyingChar(),"",$title),
        //     // 'body'      => str_replace($this->getThatAnnoyingChar(),"",$title),
        //     'media'     => '/img/news-img/gma.png',
        // );

        // return array(
        //     'body'      => $gma_data,
        //     'result'    => true
        // );
        $gma = new Scraper();

        $gmanews = $gma->scrape($url);
        
        $gma_data = array(
            'title'     => str_replace($this->getThatAnnoyingChar(),"",$gmanews->title()),
            'subtitle'  => '',
            'editor'    => '',
            'body'      => str_replace($this->getThatAnnoyingChar(),"",$gmanews->body()),
            'media'     => '/img/news-img/gma.png',
        );
       
        return array(
            'body'      => $gma_data,
            'result'    => true
        );
    }

    /**
     *Bworldonline.com news Scrapping
     * 
     * @param $url
     */
    public function scrapBWNews($url) {
        // prepare the news filter
        $bwfilter = array(
            'url'       => $url,
            'title'     => '.article-title .entry-title',
            'subtitle'  => '',
            'publish'   => '.td-post-date .entry-date',
            'editor'    => '.td-post-content-area b',
            'body'      => '.td-post-content-area .td-post-content',
            'media'     => '',
            'img-link'  => '',
        );

        $bw = $this->getNewsData($bwfilter);

        if($bw == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $bw_data = array(
            'title'     => str_replace($this->getThatAnnoyingChar(),"",$bw->title()),
            'subtitle'  => str_replace($this->getThatAnnoyingChar(),"",$bw->subtitle()),
            'publish'   => str_replace($this->getThatAnnoyingChar(),"",$bw->publish()),
            'editor'    => str_replace($this->getThatAnnoyingChar(),"",$bw->editor()),
            'image'     => str_replace($this->getThatAnnoyingChar(),"",$bw->media()),
            'body'      => str_replace($this->getThatAnnoyingChar(),"",preg_replace("/<img[^>]+\>/i", "", $bw->body())),
            'media'     => '/img/news-img/BW.png',
        );

        return array(
            'body'      => $bw_data,
            'result'    => true
        );
    }

    /**                      **/
    /**  INTERNATIONAL NEWS  **/
    /**                      **/
    /**       CNN            **/
    /**       BBC            **/
    /**       aljazeera      **/
    /**                      **/

    /**
     * cnn.com news Scrapping
     * 
     * @param $url
     */
    public function scrapCnnInt($url) {
        // check what type of link is
        $whattype_url = $this->checkCnnIntLink($url);

        // prepare the news filter
        switch ($whattype_url){
            case 'videos':
                $cnnfilter = array(
                    'url'       => $url,
                    'title'     => '.media__video-headline',
                    'subtitle'  => '',
                    'publish'   => '',
                    'editor'    => '.el__video-collection__meta-wrapper .metadata--show__name',
                    'body'      => '.media__video-description',
                    'media'     => '.video__end-slate__top-wrapper .js-el__video__replayer-wrapper  img',
                    'img-link'  => 'src',
                );
                break;
            case 'travel':
                $cnnfilter = array(
                    'url'       => $url,
                    'title'     => '.Article__title',
                    'subtitle'  => '',
                    'publish'   => '',
                    'editor'    => '.Article__subtitle',
                    'body'      => '.Article__body',
                    'media'     => '',
                    'img-link'  => '',
                );
                break;
            case 'style':
                $cnnfilter = array(
                    'url'       => $url,
                    'title'     => '.PageHead__title',
                    'subtitle'  => '',
                    'publish'   => '.PageHead__published',
                    'editor'    => '.Authors__writers a',
                    'body'      => '.BasicArticle__body',
                    'media'     => '',
                    'img-link'  => '',
                );
                break;
            case false:
                $cnnfilter = array(
                    'url'       => $url,
                    'title'     => '.pg-headline',
                    'subtitle'  => '',
                    'publish'   => '.update-time',
                    'editor'    => '.metadata__byline__author',
                    'body'      => '.pg-rail-tall__body',
                    'media'     => '.margin-bottom-15 .img-container img',
                    'img-link'  => 'src',
                );
                break;
        }
        
        

        $cnnInt = $this->getNewsData($cnnfilter);

        if($cnnInt == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $cnn_data = array(
            'title'     => str_replace($this->getThatAnnoyingChar(),"",$cnnInt->title()),
            'subtitle'  => str_replace($this->getThatAnnoyingChar(),"",$cnnInt->subtitle()),
            'publish'   => str_replace($this->getThatAnnoyingChar(),"",$cnnInt->publish()),
            'editor'    => str_replace($this->getThatAnnoyingChar(),"",$cnnInt->editor()),
            'image'     => str_replace($this->getThatAnnoyingChar(),"",$cnnInt->media()),
            'body'      => str_replace($this->getThatAnnoyingChar(),"",preg_replace("/<img[^>]+\>/i", "", $cnnInt->body())),
            'media'     => '/img/news-img/cnn.png',
        );

        return array(
            'body'      => $cnn_data,
            'result'    => true
        );
    }

    /**
     * Bbc.com news Scrapping
     * 
     * @param $url
     */
    public function scrapBbc($url) {
        // prepare the news filter
        $bbcfilter = array(
            'url'       => $url,
            'title'     => '.story-body__h1',
            'subtitle'  => '',
            'publish'   => '',
            'editor'    => '',
            'body'      => '.story-body__inner p',
            'media'     => '.image-and-copyright-container img',
            'img-link'  => 'src',
        );

        $bbc = $this->getNewsData($bbcfilter);

        if($bbc == false) {            
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $bbc_data = array(
            'title'     => str_replace($this->getThatAnnoyingChar(),"",$bbc->title()),
            'subtitle'  => str_replace($this->getThatAnnoyingChar(),"",$bbc->subtitle()),
            'publish'   => str_replace($this->getThatAnnoyingChar(),"",$bbc->publish()),
            'editor'    => str_replace($this->getThatAnnoyingChar(),"",$bbc->editor()),
            'image'     => str_replace($this->getThatAnnoyingChar(),"",$bbc->media()),
            'body'      => str_replace($this->getThatAnnoyingChar(),"",$bbc->body()),
            'media'     => '/img/news-img/bbc-news.jpg',
        );

        return array(
            'body'      => $bbc_data,
            'result'    => true
        );
    }

    /**
     * aljazeera.com news Scrapping
     * 
     * @param $url
     */
    public function scrapAljazeera($url) {
       // prepare the news filter
        $aljazeerafilter = array(
            'url'       => $url,
            'title'     => '.post-title',
            'subtitle'  => '.article-heading-des',
            'publish'   => '',
            'editor'    => '.article-heading-author-name',
            'body'      => '.article-p-wrapper',
            // 'media'     => '.margin-bottom-15 .img-container img',
            // 'img-link'  => 'src',
        );

        $aljazeera = $this->getNewsData($aljazeerafilter);

        if($aljazeera == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $aljazeera_data = array(
            'title'     => str_replace($this->getThatAnnoyingChar(),"",$aljazeera->title()),
            'subtitle'  => str_replace($this->getThatAnnoyingChar(),"",$aljazeera->subtitle()),
            'publish'   => str_replace($this->getThatAnnoyingChar(),"",$aljazeera->publish()),
            'editor'    => str_replace($this->getThatAnnoyingChar(),"",$aljazeera->editor()),
            'body'      => str_replace($this->getThatAnnoyingChar(),"",$aljazeera->body()),
            'media'     => '/img/news-img/aljazeera.jpg',
        );

        return response()->json([
            'body'      => $aljazeera_data,
            'result'    => true
        ]);
    }

    protected function getThatAnnoyingChar() {
        return array("\n","\r","\\");
    }

    /**
     * method to scrap supported news agency
     * except GMA news and MB. it use package to extract data
     * 
     * @param array $newsdata
     * @return NewsArticle
     */
    protected function getNewsData(array $newsdata) {
        $client = new Client();
        $scrapnews = $client->request('GET', $newsdata['url']);

        // get news title
        $title = $scrapnews->filter($newsdata['title'])->each(function ($node) {
            return $node->text();
        });

        // get news sub title
        $newsdata['subtitle'] !== '' ? 
            ($subtitle = $scrapnews->filter($newsdata['subtitle'])->each(function ($node) {
                return $node->text();
            }))
        : $subtitle = null ;        

        // get news author
        $newsdata['editor'] !== '' ? 
            ($editor = $scrapnews->filter($newsdata['editor'])->each(function ($node) {
                return $node->text();
            }))
        : $editor = null ;  

        // get news body
        $body = $scrapnews->filter($newsdata['body'])->each(function ($node) {
            return $node->html();
        });

        // get news publish date
        $newsdata['publish'] !== '' ? 
            ($publish = $scrapnews->filter($newsdata['publish'])->each(function ($node) {
                return $node->text();
            }))
        : $publish = null ;  

        // get news media
        if($newsdata['media'] == '') {
            $media = '';
        } elseif(array_key_exists('video', $newsdata)) {
            try {
                $media = $scrapnews->filter($newsdata['video'])->eq(0)->attr($newsdata['video-link']);
            } catch (\Exception $e) {
                $media = '';
            }
        }else{
            try {
                $media = $scrapnews->filter($newsdata['media'])->eq(0)->attr($newsdata['img-link']);
            } catch (\Exception $e) {
                $media = '';
            }
        }

        if(count($title) == 0 || count($body) == 0) {
            return false;
        }
        
        return new NewsArticle(
            array_key_exists('sport', $newsdata) ? $title[1] : $title[0], 
            $subtitle[0], 
            $editor[0], 
            implode("','",$body), 
            $media, 
            array_key_exists('sport', $newsdata) ? $publish : $publish[0]
        );
    }

    protected function findVideoOnCnn($url){
        $parts = explode("/", $url);
        if(in_array("videos", $parts)) {
            return true;
        }else{
            return false;
        }
    }

    protected function checkCnnIntLink($url) {
        $cnnint = explode("/", $url);
        if(in_array("videos", $cnnint)) {
            return 'videos';
        }elseif(in_array("travel", $cnnint)){
            return 'travel';
        }elseif(in_array("style", $cnnint)){
            return 'style';
        }else{
            return false;
        }
    }

    protected function getYTid($yt_url) {
       return $yt =   explode("/", $yt_url);       
    }

    protected function isAbscbnSports($url) {
        $parts = explode('/', $url);
        $small = explode('.', $parts[2]);
        if(in_array("sports", $small)) {
            return true;
        }else{
            return false;
        }   
    }
}
