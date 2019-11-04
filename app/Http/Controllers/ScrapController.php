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

    $seller = [
        'id'            => '',
        'username'      => $sellerusername[0],
        'profile_photo' => $sellerphoto
    ];

    $newcarousell_item = [
        'url'               => $carousell_url,
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

        $abscbn = $this->getNewsData($abscbnfilter);

        if($abscbn == false) {
            return array(
                'body'      => "Something went wrong on our side!",
                'result'    => false
            );
        }

        // $pub = explode('on', str_replace($this->getThatAnnoyingChar(),"",$abscbn->editor()));

        $abscbn_data = array(
            'title'     => str_replace($this->getThatAnnoyingChar(),"",$abscbn->title()),
            'subtitle'  => str_replace($this->getThatAnnoyingChar(),"",$abscbn->subtitle()),
            // 'publish'   => $is_sports == true ? $pub[1] : str_replace($this->getThatAnnoyingChar(),"",$abscbn->publish()),
            // 'editor'    => $is_sports == true ? $pub[0] : str_replace($this->getThatAnnoyingChar(),"",$abscbn->editor()),
            'publish'   => str_replace($this->getThatAnnoyingChar(),"",$abscbn->publish()),
            'editor'    => str_replace($this->getThatAnnoyingChar(),"",$abscbn->editor()),
            'image'     => str_replace($this->getThatAnnoyingChar(),"",$abscbn->media()),
            'body'      => str_replace($this->getThatAnnoyingChar(),"",preg_replace("/<img[^>]+\>/i", "", $abscbn->body())),
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
            'publish'   => str_replace($this->getThatAnnoyingChar(),"",$bw->subtitle()),
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
            'publish'   => str_replace($this->getThatAnnoyingChar(),"",$check_bbc_link === 'reel' ? $this->explodebbcReelPublishDate($bbc->publish()) : $bbc->publish()),
            'editor'    => str_replace($this->getThatAnnoyingChar(),"",$bbc->editor()),
            'image'     => str_replace($this->getThatAnnoyingChar(),"",$bbc->media()),
            'body'      => str_replace($this->getThatAnnoyingChar(),"",preg_replace("/<img[^>]+\>/i", "", $bbc->body())),
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
            'image'     => str_replace($this->getThatAnnoyingChar(),"",$link === 'indepth' || $link === 'ajimpact'? 'https://www.aljazeera.com'.$aljazeera->media()  : $aljazeera->media()),
            'body'      => str_replace($this->getThatAnnoyingChar(),"",$aljazeera->body()),
            'media'     => '/img/news-img/aljazeera.jpg',
        );

        return array(
            'body'      => $aljazeera_data,
            'result'    => false
        );
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

        // $newsdata['editor'] !== '' ? 
        //     ($editor = $scrapnews->filter($newsdata['editor'])->each(function ($node) {
        //         if ($node->children()->last()->attr('class') == 'updated_date') {
        //             return $node->text();
        //         }else{
        //             return '';
        //         }
        //     }))
        // : $editor = null ;  

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
}

// public function scrapCarousell($id) {

//     $carousell_url = 'https://www.carousell.ph/p/'.$id;

//     $client = new Client();

//     $description = [];
//     $seller = [];
//     $newcarousell_item = [];

//     $scrapcarousells = $client->request('GET', $carousell_url);

//     $sellerusername = $scrapcarousells->filter('.styles__sellerWrapper___3YRXI p')->each(function ($node) {
//         return $node->text();
//     });

//     $sellerphoto = $scrapcarousells->filter('.styles__avatar___1p0El img')->eq(0)->attr('src');
    
//     $media = $scrapcarousells->filter('.styles__carouselVerticalTrack___Z4Gdv .styles__slide___1-pzx img')->each(function ($node) {
//         return $node->eq(0)->attr('src');
//     });

//     $price = $scrapcarousells->filter('.styles__price___K6Kjb')->each(function ($node) {
//         return $node->text();
//     });

//     $itemname = $scrapcarousells->filter('.styles__titleWrapper___3jSxG h1')->each(function ($node) {
//         return $node->text();
//     });

//     $desc = $scrapcarousells->filter('.styles__textTruncate___2Mx1R .styles__overflowBreakWord___2rtT6')->each(function ($node) {
//         return $node->text();
//     });

//     $condition = $scrapcarousells->filter('.styles__body___VSdV5 p')->each(function ($node) {
//         return $node->text();
//     });

//     $shipping = $scrapcarousells->filter('.styles__textWithLeftLabel___20RQO .styles__text___1gJzw')->each(function ($node) {
//         return $node->text();
//     });

//     $seller = [
//         'id'            => '',
//         'username'      => $sellerusername[0],
//         'profile_photo' => $sellerphoto
//     ];

//     $newcarousell_item = [
//         'url'               => $carousell_url,
//         'seller'            => $seller,
//         'media'             => $media,
//         'itemname'          => $itemname[0],
//         'price'             => $price[0],
//         'description'       => $desc[0],
//         'condition'         => $condition,
//         'source'            => 'carousell'
//     ];

//     return $newcarousell_item;
// }
