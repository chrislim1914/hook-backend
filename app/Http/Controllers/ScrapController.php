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
            'media'     => $media,
            'price'     => $price[0],
            'itemname'  => $itemname[0],
            'description'  => $description,
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
        // prepare the news filter
        $abscbnfilter = array(
            'url'       => $url,
            'title'     => '.news-title',
            'subtitle'  => '',
            'editor'    => '',
            'body'      => '.article-content',
            // 'media'     => '.article-content .embed-wrap img',
            // 'img-link'  => 'src',
        );

        $abscbn = $this->getNewsData($abscbnfilter);

        if($abscbn == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $abscbn_data = array(
            'title'     => str_replace($this->getThatAnnoyingChar(),"",$abscbn->title()),
            'subtitle'  => str_replace($this->getThatAnnoyingChar(),"",$abscbn->subtitle()),
            'editor'    => str_replace($this->getThatAnnoyingChar(),"",$abscbn->editor()),
            'body'      => str_replace($this->getThatAnnoyingChar(),"",$abscbn->body()),
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
        // prepare the news filter
        $cnnphilfilter = array(
            'url'       => $url,
            'title'     => '.title',
            'subtitle'  => '',
            'editor'    => '.author-byline',
            'body'      => '.article-maincontent-p #content-body-244757-498257',
            'media'     => '.margin-bottom-15 .img-container img',
            'img-link'  => 'src',
        );

        $cnnphil = $this->getNewsData($cnnphilfilter);

        if($cnnphil == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $cnnphil_data = array(
            'title'     => str_replace($this->getThatAnnoyingChar(),"",$cnnphil->title()),
            'subtitle'  => str_replace($this->getThatAnnoyingChar(),"",$cnnphil->subtitle()),
            'editor'    => str_replace($this->getThatAnnoyingChar(),"",$cnnphil->editor()),
            'image'     => str_replace($this->getThatAnnoyingChar(),"",'http://cnnphilippines.com'.$cnnphil->media()),
            'body'      => str_replace($this->getThatAnnoyingChar(),"",$cnnphil->body()),
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
        $mb = new MBScraper();
        $mbnews = $mb->scrape($url);
        $mb_data = array(
            'title'     => str_replace($this->getThatAnnoyingChar(),"",$mbnews->title()),
            'subtitle'  => '',
            'editor'    => '',
            'body'   => str_replace($this->getThatAnnoyingChar(),"",$mbnews->body()),
            'media'     => '/img/news-img/mb.png',
        );

        return response()->json([
            'data'      => $mb_data,
            'result'    => true
        ]);
    }

    /**
     * Gmanetwork.com news Scrapping
     * 
     * @param $url
     */
    public function scrapGmaNews($url) {
        $gma = new Scraper();

        $gmanews = $gma->scrape($url);
        
        $gma_data = array(
            'title'     => str_replace($this->getThatAnnoyingChar(),"",$gmanews->title()),
            'subtitle'  => '',
            'editor'    => '',
            'body'      => str_replace($this->getThatAnnoyingChar(),"",$gmanews->body()),
            'media'     => '/img/news-img/gma.png',
        );
       
        return response()->json([
            'data'      => $gma_data,
            'result'    => true
        ]);
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
        // prepare the news filter
        $cnnfilter = array(
            'url'       => $url,
            'title'     => '.pg-headline',
            'subtitle'  => '',
            'editor'    => '.metadata__byline__author',
            'body'      => '.pg-rail-tall__body',
            // 'media'     => '.margin-bottom-15 .img-container img',
            // 'img-link'  => 'src',
        );

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
            'editor'    => str_replace($this->getThatAnnoyingChar(),"",$cnnInt->editor()),
            'body'      => str_replace($this->getThatAnnoyingChar(),"",$cnnInt->body()),
            'media'     => '/img/news-img/cnn.png',
        );

        return response()->json([
            'data'      => $cnn_data,
            'result'    => true
        ]);
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
            'editor'    => '',
            'body'      => '.story-body__inner p',
            // 'media'     => '.margin-bottom-15 .img-container img',
            // 'img-link'  => 'src',
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
            'editor'    => str_replace($this->getThatAnnoyingChar(),"",$bbc->editor()),
            'body'      => str_replace($this->getThatAnnoyingChar(),"",$bbc->body()),
            'media'     => '/img/news-img/bbc-news.jpg',
        );

        return response()->json([
            'data'      => $bbc_data,
            'result'    => true
        ]);
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
            'editor'    => str_replace($this->getThatAnnoyingChar(),"",$aljazeera->editor()),
            'body'      => str_replace($this->getThatAnnoyingChar(),"",$aljazeera->body()),
            'media'     => '/img/news-img/aljazeera.jpg',
        );

        return response()->json([
            'data'      => $aljazeera_data,
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

        $title = $scrapnews->filter($newsdata['title'])->each(function ($node) {
            return $node->text();
        });

        $newsdata['subtitle'] !== '' ? 
            ($subtitle = $scrapnews->filter($newsdata['subtitle'])->each(function ($node) {
                return $node->text();
            }))
        : $subtitle = null ;        

        $newsdata['editor'] !== '' ? 
            ($editor = $scrapnews->filter($newsdata['editor'])->each(function ($node) {
                return $node->text();
            }))
        : $editor = null ;  

        $body = $scrapnews->filter($newsdata['body'])->each(function ($node) {
            return $node->html();
        });

        $publish = $scrapnews->filter($newsdata['publish'])->each(function ($node) {
            return $node->text();
        });
        
        $media = $scrapnews->filter($newsdata['media'])->eq(0)->attr($newsdata['img-link']);

        if(count($title) == 0 || count($body) == 0) {
            return false;
        }
        return new NewsArticle($title[0], $subtitle[0], $editor[0], implode("','",$body), $media, $publish[0]);
    }
}
