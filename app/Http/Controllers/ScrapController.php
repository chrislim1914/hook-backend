<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
use Pilipinews\Website\Gma\Scraper;
use Pilipinews\Website\Bulletin\Scraper as MBScraper;
use App\Http\Controllers\NewsArticle;
use App\Http\Controllers\Functions;
use App\Http\Controllers\CarousellController;

class ScrapController extends Controller
{
    

    /**
     * scrap carousell.ph using item id
     * 
     * @param $id
     * @return JSON
     */
    public function scrapCarousell($id) {

        $carousell_url = 'https://www.carousell.ph/p/'.$id;

        $client = new Client();

        $description = [];
        $seller = [];
        $newcarousell_item = [];

        $scrapcarousells = $client->request('GET', $carousell_url);

        $carousellcat = $scrapcarousells->filter('.styles__breadcrumbItem___3KK_l a')->each(function ($node) {
            return $node->eq(0)->attr('href');
        });

        $sellerusername = $scrapcarousells->filter('.styles__sellerWrapper___3YRXI p')->each(function ($node) {
            return $node->text();
        });

        $sellerphoto = $scrapcarousells->filter('.styles__avatar___1p0El img')->eq(0)->attr('src');
        
        $media = $scrapcarousells->filter('.styles__carouselVerticalTrack___Z4Gdv .styles__slide___1-pzx img')->each(function ($node) {
            return $node->eq(0)->attr('src');
        });

        $price = $scrapcarousells->filter('.styles__price___K6Kjb')->each(function ($node) {
            return $node->text();
        });

        $itemname = $scrapcarousells->filter('.styles__titleWrapper___3jSxG h1')->each(function ($node) {
            return $node->text();
        });

        $desc = $scrapcarousells->filter('.styles__textTruncate___2Mx1R .styles__overflowBreakWord___2rtT6')->each(function ($node) {
            return $node->text();
        });

        $shipping = $scrapcarousells->filter('.styles__textWithLeftLabel___20RQO .styles__text___1gJzw')->each(function ($node) {
            return $node->text();
        });

        // lets get the Main category ID, not the sub ID we dont use that
        $catID = $this->getCarousellCategoryIDfromScrap($carousellcat[1]);

        // lets use the filterCarousell() method on CarousellController to get similar items
        $carousell = new CarousellController();
        $similar = $carousell->filterCarousell(1,'', $catID);

        $seller = [
            'id'            => '',
            'username'      => $sellerusername[0],
            'profile_photo' => $sellerphoto
        ];
        
        // check if there is a content
        // TODO

        $newcarousell_item = [
            'url'               => $carousell_url,
            'category'          => $catID,
            'seller'            => $seller,
            'media'             => $media,
            'itemname'          => $itemname[0],
            'price'             => $price[0],
            'description'       => $desc[0],
            'source'            => 'carousell'
        ];

        return $newcarousell_item;
    }

    /**
     * businessmirror.com.ph news Scrapping
     * 
     * @param $url
     */
    public function scrapBusinessMirror($url, $countrycode) {
        // prepare the news filter
        $businessmirrorfilter = array(
            'url'       => $url,
            'title'     => '.td-post-title .entry-title',
            'subtitle'  => '',
            'publish'   => '.entry-date',
            'editor'    => '.td-post-author-name',
            'body'      => '.has-content-area',
            'media'     => '.td-post-featured-image img',
            'img-link'  => 'src',
        );

        $businessmirror = $this->getNewsData($businessmirrorfilter, $countrycode);

        if($businessmirror == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $businessmirror_data = array(
            'title'     => $businessmirror->title(),
            'subtitle'  => $businessmirror->subtitle(),
            'publish'   => $businessmirror->publish(),
            'editor'    => $businessmirror->editor(),
            'image'     => $businessmirror->media(),
            'body'      => $businessmirror->body(),
            'media'     => '/img/news-img/BM-logo.png',
        );

        return array(
            'body'      => $businessmirror_data,
            'result'    => true
        );
    }

    /**
     * Rappler.com news Scrapping
     * 
     * @param $url
     */
    public function scrapRapplerNews($url, $countrycode) {
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

        $rappler = $this->getNewsData($rapplerfilter, $countrycode);

        if($rappler == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $rappler_data = array(
            'title'     => $rappler->title(),
            'subtitle'  => $rappler->subtitle(),
            'publish'   => $rappler->publish(),
            'editor'    => $rappler->editor(),
            'image'     => $rappler->media(),
            'body'      => $rappler->body(),
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
    public function scrapAbsCbnNews($url, $countrycode) {
        $is_sports = $this->isAbscbnSports($url);
        // prepare the news filter
        switch ($is_sports) {
            case 'news':
                $abscbnfilter = array(
                    'url'       => $url,
                    'title'     => '.news-title',
                    'subtitle'  => '',
                    'publish'   => '.timestamp-entry',
                    'editor'    => '.author-block .author-details .editor',
                    'body'      => '.article-content',
                    'media'     => '.article-content .embed-wrap img',
                    'img-link'  => 'src',
                );
                break;
            case 'business':
                $abscbnfilter = array(
                    'url'       => $url,
                    'title'     => '.news-title',
                    'subtitle'  => '',
                    'publish'   => '.timestamp-entry',
                    'editor'    => '.author-block .author-details .editor',
                    'body'      => '.article-content',
                    'media'     => '.article-content .embed-wrap img',
                    'img-link'  => 'src',
                );
                break;
            case 'entertainment':
                $abscbnfilter = array(
                    'url'       => $url,
                    'title'     => '.news-title',
                    'subtitle'  => '',
                    'publish'   => '.timestamp-entry',
                    'editor'    => '.author-block .author-details .editor',
                    'body'      => '.article-content',
                    'media'     => '.article-content .embed-wrap img',
                    'img-link'  => 'src',
                );
                break;
            case 'life':
                $abscbnfilter = array(
                    'url'       => $url,
                    'title'     => '.news-title',
                    'subtitle'  => '',
                    'publish'   => '.timestamp-entry',
                    'editor'    => '.author-block .author-details .editor',
                    'body'      => '.article-content',
                    'media'     => '.article-content .embed-wrap img',
                    'img-link'  => 'src',
                );
                break;
            case 'sports':
                $abscbnfilter = array(
                    'url'       => $url,
                    'title'     => '.news-title',
                    'subtitle'  => '',
                    'publish'   => '.timestamp-entry',
                    'editor'    => '.author-block .author-details .editor',
                    'body'      => '.article-content',
                    'media'     => '.article-content .embed-wrap img',
                    'img-link'  => 'src',
                );
                break;
            case 'overseas':
                $abscbnfilter = array(
                    'url'       => $url,
                    'title'     => '.news-title',
                    'subtitle'  => '',
                    'publish'   => '.timestamp-entry',
                    'editor'    => '.author-block .author-details .editor',
                    'body'      => '.article-content',
                    'media'     => '.article-content .embed-wrap img',
                    'img-link'  => 'src',
                );
                break;
            case 'spotlight':
                $abscbnfilter = array(
                    'url'       => $url,
                    'title'     => '.news-title',
                    'subtitle'  => '',
                    'publish'   => '.timestamp-entry',
                    'editor'    => '.author-block .author-details .editor',
                    'body'      => '.article-content',
                    'media'     => '.article-content .embed-wrap img',
                    'img-link'  => 'src',
                );
                break;
            default:
                return array(
                    'body'      => "Something went wrong on our side!",
                    'result'    => false
                );
        }

        $abscbn = $this->getNewsData($abscbnfilter, $countrycode);

        if($abscbn == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $abscbn_data = array(
            'title'     => $abscbn->title(),
            'subtitle'  => $abscbn->subtitle(),
            'publish'   => $abscbn->publish(),
            'editor'    => $abscbn->editor(),
            'image'     => $abscbn->media(),
            'body'      => $abscbn->body(),
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
    public function scrapCnnPhilNews($url, $countrycode) {
        $function = new Functions();
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
        $cnnphil = $this->getNewsData($cnnphilfilter, $countrycode);

        if($cnnphil == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }
        // get youtube video ID
        $ytid = $this->getYTid($cnnphil->media());

        $cnnphil_data = array(
            'title'     => $cnnphil->title(),
            'subtitle'  => $cnnphil->subtitle(),
            'publish'   => $cnnphil->publish(),
            'editor'    => $cnnphil->editor(),
            'image'     => $is_video === true ? 'https://i.ytimg.com/vi/'.$ytid[4].'/sddefault.jpg' : str_replace($function->getThatAnnoyingChar(),"",'http://cnnphilippines.com'.$cnnphil->media()),
            'body'      => $cnnphil->body(),
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
    public function scrapMBNews($url, $countrycode) {
        $mblink = $this->checkMBnewsLink($url);
        // prepare the news filter
        switch ($mblink) {
            case 'news':
                $mbfilter = array(
                    'url'       => $url,
                    'title'     => '#tm-content .uk-article-title',
                    'subtitle'  => '',
                    'publish'   => '.uk-article .published_date',
                    'updated'   => '.uk-article .updated_date',
                    'editor'    => '.uk-article em',
                    'body'      => '.uk-article',
                    'media'     => '.uk-article img',
                    'img-link'  => 'src',
                );
                break;
            case 'business':
                $mbfilter = array(
                    'url'       => $url,
                    'title'     => '#tm-content .uk-article-title',
                    'subtitle'  => '',
                    'publish'   => '.uk-article .published_date',
                    'updated'   => '.uk-article .updated_date',
                    'editor'    => '.uk-article em',
                    'body'      => '.uk-article',
                    'media'     => '.uk-article img',
                    'img-link'  => 'src',
                );
                break;
            case 'entertainment':
                $mbfilter = array(
                    'url'       => $url,
                    'title'     => '#tm-content .uk-article-title',
                    'subtitle'  => '',
                    'publish'   => '.uk-article .published_date',
                    'updated'   => '.uk-article .updated_date',
                    'editor'    => '.uk-h5 a',
                    'body'      => '.uk-article',
                    'media'     => '.uk-article img',
                    'img-link'  => 'src',
                );
                break;
            case 'sports':
                $mbfilter = array(
                    'url'       => $url,
                    'title'     => '#tm-content .uk-article-title',
                    'subtitle'  => '',
                    'publish'   => '.uk-article .published_date',
                    'updated'   => '.uk-article .updated_date',
                    'editor'    => '.uk-article p em',
                    'body'      => '.uk-article',
                    'media'     => '.uk-article img',
                    'img-link'  => 'src',
                );
                break;
            case 'lifestyle':
                $mbfilter = array(
                    'url'       => $url,
                    'title'     => '#tm-content .uk-article-title',
                    'subtitle'  => '',
                    'publish'   => '.uk-article .published_date',
                    'updated'   => '.uk-article .updated_date',
                    'editor'    => '.uk-article p em',
                    'body'      => '.uk-article',
                    'media'     => '.uk-article img',
                    'img-link'  => 'src',
                );
                break;
            case 'technology':
                    $mbfilter = array(
                        'url'       => $url,
                        'title'     => '#tm-content .uk-article-title',
                        'subtitle'  => '',
                        'publish'   => '.uk-article .published_date',
                        'updated'   => '.uk-article .updated_date',
                        'editor'    => '',
                        'body'      => '.uk-article',
                        'media'     => '.uk-article img',
                        'img-link'  => 'src',
                    );
                    break;
            default:
                return array(
                    'body'      => "Something went wrong on our side!",
                    'result'    => false
                );
        }
        

        $mb = $this->getNewsData($mbfilter, $countrycode);

        if($mb == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $mb_data = array(
            'title'     => $mb->title(),
            'subtitle'  => $mb->subtitle(),
            'publish'   => $mb->publish(),
            'editor'    => $mb->editor(),
            'image'     => $mb->media(),
            'body'      => $mb->body(),
            'media'     => '/img/news-img/mb.png',
        );

        return array(
            'body'      => $mb_data,
            'result'    => true
        );
    }

    /**
     *Bworldonline.com news Scrapping
     * 
     * @param $url
     */
    public function scrapBWNews($url, $countrycode) {
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

        $bw = $this->getNewsData($bwfilter, $countrycode);

        if($bw == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $bw_data = array(
            'title'     => $bw->title(),
            'subtitle'  => $bw->subtitle(),
            'publish'   => $bw->subtitle(),
            'editor'    => $bw->editor(),
            'image'     => $bw->media(),
            'body'      => $bw->body(),
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
    public function scrapCnnInt($url, $countrycode) {
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
        
        

        $cnnInt = $this->getNewsData($cnnfilter, $countrycode);

        if($cnnInt == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $cnn_data = array(
            'title'     => $cnnInt->title(),
            'subtitle'  => $cnnInt->subtitle(),
            'publish'   => $cnnInt->publish(),
            'editor'    => $cnnInt->editor(),
            'image'     => $cnnInt->media(),
            'body'      => $cnnInt->body(),
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
    public function scrapBbc($url, $countrycode) {
        
        $check_bbc_link = $this->checkBbcLink($url);

        // prepare the news filter
        switch ($check_bbc_link) {
            case 'news':
                $bbcfilter = array(
                    'url'       => $url,
                    'title'     => '.story-body__h1',
                    'subtitle'  => '',
                    'publish'   => '.mini-info-list__item .date',
                    'editor'    => '',
                    'body'      => '.story-body__inner p',
                    'media'     => '.image-and-copyright-container img',
                    'img-link'  => 'src',
                );
                break;
            case 'sport':
                $bbcfilter = array(
                    'url'       => $url,
                    'title'     => '.story-headline',
                    'subtitle'  => '',
                    'publish'   => '',
                    'editor'    => '.gel-flag__body .gel-long-primer',
                    'body'      => '.story-body',
                    'media'     => '.sp-media-asset__image img',
                    'img-link'  => 'src',
                );
                break;
            case 'reel':
                // TODO
                // $bbcfilter = array(
                //     'url'       => $url,
                //     'title'     => '.evzinhz4',
                //     'subtitle'  => '',
                //     'publish'   => '.evzinhz2',
                //     'editor'    => '',
                //     'body'      => '',
                //     'media'     => '.smphtml5iframebbcMediaPlayer0wrp #smphtml5iframebbcMediaPlayer0 iframe .mediaContainer img',
                //     'img-link'  => 'src',
                // );
                return array(
                    'body'      => "Something went wrong on our side!",
                    'result'    => false
                );
                break;
            case 'worklife':
                $bbcfilter = array(
                    'url'       => $url,
                    'title'     => '.hero-header__header',
                    'subtitle'  => '.simple-header',
                    'publish'   => '.author-unit__date',
                    'editor'    => '.author-name',
                    'body'      => '.body-text-card__text',
                    'media'     => '.article-title-card__image img',
                    'img-link'  => 'src',
                );
                break;
            case 'future':
                    $bbcfilter = array(
                        'url'       => $url,
                        'title'     => '.hero-header__header',
                        'subtitle'  => '.simple-header',
                        'publish'   => '.author-unit__date',
                        'editor'    => '.author-name',
                        'body'      => '.body-text-card__text',
                        'media'     => '.article-title-card__image img',
                        'img-link'  => 'src',
                    );
                    break;
            case 'travel':
                $bbcfilter = array(
                    'url'       => $url,
                    'title'     => '.hero-unit .hero-unit-lining h1',
                    'subtitle'  => '.introduction-wrapper .introduction',
                    'publish'   => '.source-attribution-detail .publication-date',
                    'editor'    => '.source-attribution-detail .seperated-list-item span',
                    'body'      => '.body-content',
                    'media'     => '.responsive-image-wrapper img',
                    'img-link'  => 'src',
                );
                break;
            case 'culture':
                $bbcfilter = array(
                    'url'       => $url,
                    'title'     => '.hero-unit .hero-unit-lining h1',
                    'subtitle'  => '.introduction-wrapper .introduction',
                    'publish'   => '.source-attribution-detail .publication-date',
                    'editor'    => '.source-attribution-detail .seperated-list-item span',
                    'body'      => '.body-content',
                    'media'     => '.responsive-image-wrapper img',
                    'img-link'  => 'src',
                );
                break;
            default:
                return array(
                    'body'      => "Something went wrong on our side!",
                    'result'    => false
                );
        }
        
        

        $bbc = $this->getNewsData($bbcfilter, $countrycode);

        if($bbc == false) {            
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $bbc_data = array(
            'title'     => $bbc->title(),
            'subtitle'  => $bbc->subtitle(),
            'publish'   => $check_bbc_link === 'reel' ? $this->explodebbcReelPublishDate($bbc->publish()) : $bbc->publish(),
            'editor'    => $bbc->editor(),
            'image'     => $bbc->media(),
            'body'      => $bbc->body(),
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
    public function scrapAljazeera($url, $countrycode) {

        $link = $this->checkaljezeeraLink($url);

        // prepare the news filter
        switch ($link) {
            case 'news':
                $aljazeerafilter = array(
                    'url'       => $url,
                    'title'     => '.post-title',
                    'subtitle'  => '.article-heading-des',
                    'publish'   => '.article-duration',
                    'editor'    => '.article-body-artSource span',
                    'body'      => '.article-p-wrapper',
                    'media'     => '.margin-bottom-15 .img-container img',
                    'img-link'  => 'src',
                );
                break;
            case 'programmes':
                $aljazeerafilter = array(
                    'url'       => $url,
                    'title'     => '.heading-story',
                    'subtitle'  => '.standfirst',
                    'publish'   => '.meta time',
                    'editor'    => '',
                    'body'      => '.article-body',
                    'media'     => '.#vjs_video_3 video',
                    'img-link'  => 'poster',
                );
                break;
            case 'indepth':
                $aljazeerafilter = array(
                    'url'       => $url,
                    'title'     => '.post-title',
                    'subtitle'  => '.article-heading-des',
                    'publish'   => '.article-duration',
                    'editor'    => '',
                    'body'      => '.main-article-body',
                    'media'     => '.main-article-media img',
                    'img-link'  => 'src',
                );
                break;
            case 'ajimpact':
                $aljazeerafilter = array(
                    'url'       => $url,
                    'title'     => '.post-title',
                    'subtitle'  => '.article-heading-des',
                    'publish'   => '.article-duration',
                    'editor'    => '.article-heading-author-name',
                    'body'      => '.article-p-wrapper',
                    'media'     => '.main-article-media img',
                    'img-link'  => 'src',
                );
                break;
            default:
                return array(
                    'body'      => "Something went wrong on our side!",
                    'result'    => false
                );
        }

        $aljazeera = $this->getNewsData($aljazeerafilter, $countrycode);

        if($aljazeera == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        $aljazeera_data = array(
            'title'     => $aljazeera->title(),
            'subtitle'  => $aljazeera->subtitle(),
            'publish'   => $aljazeera->publish(),
            'editor'    => $aljazeera->editor(),
            'image'     => $link === 'indepth' || $link === 'ajimpact'? 'https://www.aljazeera.com'.$aljazeera->media()  : $aljazeera->media(),
            'body'      => $aljazeera->body(),
            'media'     => '/img/news-img/aljazeera.jpg',
        );

        return array(
            'body'      => $aljazeera_data,
            'result'    => false
        );
    }

    /**
     * method to scrap supported news agency
     * except GMA news and MB. it use package to extract data
     * 
     * @param array $newsdata
     * @return NewsArticle
     */
    protected function getNewsData(array $newsdata, $countrycode) {
        $function = new Functions();
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
        if($newsdata['editor'] !== '') {
            $count = $scrapnews->filter($newsdata['editor'])->count();
            if($count >= 1){
                $editor = $scrapnews->filter($newsdata['editor'])->each(function ($node) {
                    return $node->text();
                });
            } else {
                $editor = null;
            }
        } else {
            $editor = null;
        }

        // get news body
        $body = $scrapnews->filter($newsdata['body'])->each(function ($node) {
            return $node->html();
        });

        // get news publish date
        if($newsdata['publish'] !== '') {
            // check if publish is empty: this is for MB news
            $count = $scrapnews->filter($newsdata['publish'])->count();

            if($count >= 1){
                $publish = $scrapnews->filter($newsdata['publish'])->each(function ($node) {
                    return $node->text();
                });
            } else {
                $publish = $scrapnews->filter($newsdata['updated'])->each(function ($node) {
                    return $node->text();
                });
            }
            
        } else {
            $publish = null;
        }

        // get news media
       if(array_key_exists('video', $newsdata)) {
            try {
                $media = $scrapnews->filter($newsdata['video'])->eq(0)->attr($newsdata['video-link']);
            } catch (\Exception $e) {
                $media = '';
            }
        }else{
            if($newsdata['media'] == '') {
                $media = '';
            }
            try {
                $media = $scrapnews->filter($newsdata['media'])->eq(0)->attr($newsdata['img-link']);
            } catch (\Exception $e) {
                $media = '';
            }
        }

        if(count($title) == 0 || count($body) == 0) {
            return false;
        }

        $newtitle       = str_replace($function->getThatAnnoyingChar(), "", array_key_exists('sport', $newsdata) ? $title[1] : $title[0]);
        $newsubtitle    = str_replace($function->getThatAnnoyingChar(), "", $subtitle[0]);
        $neweditor      = str_replace($function->getThatAnnoyingChar(), "", $editor[0]);        
        $newbody        = str_replace($function->getThatAnnoyingChar(), "", preg_replace("/<img[^>]+\>/i", "", $body[0]));
        $newmedia       = str_replace($function->getThatAnnoyingChar(), "", $media);
        $newpublish     = str_replace($function->getThatAnnoyingChar(), "", array_key_exists('sport', $newsdata) ? $publish : $publish[0]);

        if(!$countrycode || $countrycode == 'en'){
            return new NewsArticle($newtitle, $newsubtitle, $neweditor, $newbody, $newmedia, $newpublish);
        }

        $explode = explode("\n", str_replace($function->getThatAnnoyingChar(), "", preg_replace("/<img[^>]+\>/i", "", $body[0])));
        $trans_body = [];
        for($i=0;$i<count($explode);$i++) {
            array_push($trans_body, (empty($explode[$i]) || $explode[$i] === " ")  ? $explode[$i] : $function->translator($explode[$i], $countrycode));
        }
       

        return new NewsArticle(
            $function->translator($newtitle, $countrycode),  
            $newsubtitle == '' || $newsubtitle == ' ' ? $newsubtitle : $function->translator($newsubtitle, $countrycode), 
            $neweditor, 
            implode("", $trans_body), 
            $newmedia,
            $newpublish == '' || $newpublish == ' ' ? $newsubtitle : $function->translator($newpublish, $countrycode)
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

    protected function checkBbcLink($url) {
        $bbc_url = explode("/", $url);
        return $bbc_url[3];
    }

    protected function checkaljezeeraLink($url) {
        $bbc_url = explode("/", $url);
        return $bbc_url[3];
    }

    protected function explodebbcReelPublishDate($publish) {
        $pub = explode(".", $publish);
        return $pub[0];
    }

    protected function getYTid($yt_url) {
       return $yt =   explode("/", $yt_url);       
    }

    protected function isAbscbnSports($url) {
        $partsabscbn = explode('/', $url);
        $smallabscbn = explode('.', $partsabscbn[2]);
        return $smallabscbn[0] ;
    }

    protected function checkMBnewsLink($url) {
        $partsMBnewsLink = explode('/', $url);
        $smallMBnewsLink = explode('.', $partsMBnewsLink[2]);
        return $smallMBnewsLink[0] ;
    }

    /**
     * method to get carousell category ID
     */
    protected function getCarousellCategoryIDfromScrap($string) {
        $function = new Functions();
        $delimiters = array("/","-");
        $getcat = $function->multiexplode($delimiters,$string);
        $thisisid = 0;
        foreach($getcat as $idontknow) {
            for($i=0;$i<count($idontknow);$i++) {
                if(is_numeric($idontknow[$i])) {
                    $thisisid = $idontknow[$i];
                }
            }            
        }

        return $thisisid;
    }
}
