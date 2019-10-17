<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stichoza\GoogleTranslate\GoogleTranslate;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;

class ShopsmController extends Controller
{
    private $shopsm_url = 'https://graph1.smomni.com/graphql';

    public function feedSMPopular() {
        $getPopularSku = $this->getPopular();
        if($getPopularSku == false) {
            return response()->json([
                'message'      => "Something went wrong on our side!",
                'result'    => false
            ]);
        }

        $smfeed = [];
        $detail = [];
        
        foreach($getPopularSku['data']['getPopularSku']['skus'] as $skus ) {
            
            $deatail[] = [
                'name' => $skus['name']
            ];   $deatail[] = [
                'currentPrice' => $skus['currentPrice'],
            ];   $deatail[] = [
                'discount' => $skus['discount'],
            ];   $deatail[] = [
                'totalInventory' => $skus['totalInventory'],
            ];            
            $smfeed[] = [
                'id'            =>  $skus['_id'],
                'seller'        =>  '',
                'photoUrls'     =>  [$skus['primaryImage']],
                'info'          =>  $deatail,
                'location'      =>  '',
                'coordinates'   =>  '',
                'source'        =>  'shopsm.com'
            ];
            $detail = array();      
        }

        return response()->json([
            'data'      => $smfeed,
            'result'    => true
        ]);
    }

    public function getPopular() {
        $params = [
            'operationName' => 'getPopularSkuQuery',
            'variables'     => [
                                'key'           => 'undefined',
                                'offset'        => 0,
                                'limit'         => 10,
                                'minPrice'      => 0,
                                'maxPrice'      => 0,
                                'brands'        => [],
                                'categories'    => [],
                                'sortBy'        => 'publishedDate',
                                'sortOrder'     => '-1',
                                ],
            'query'         =>  'query getPopularSkuQuery($categories: [String], $brands: [String], $colorFilters: [String], $minPrice: Float, $maxPrice: Float, $sortBy: String, $sortOrder: String, $limit: Int, $offset: Int) {  getPopularSku(categories: $categories, brands: $brands, colorFilters: $colorFilters, minPrice: $minPrice, maxPrice: $maxPrice, sortBy: $sortBy, sortOrder: $sortOrder, limit: $limit, offset: $offset) {    totalCount    skus {      _id     primaryImage      referencePrice     currentPrice    salePrice      discount      name  totalInventory     __typename    }    minPrice    maxPrice   __typename  }}'
        ];

        $client = new Client([
            'headers' => [
                'Host'              => 'graph1.smomni.com',
                'User-Agent'        => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv=>69.0) Gecko/20100101 Firefox/69.0',
                'Accept'            => '*/*',
                'Accept-Language'   => 'en-US,en;q=0.5',
                'Accept-Encoding'   => 'gzip, deflate, br',
                'content-type'      => 'application/json',
                'appid'             => '5a72f72461763423531289af',
                'companyId'         => '5a72f6f861763423531289ae'
            ],
        ]);

        try {
                    
            $response = $client->request('POST','https://graph1.smomni.com/graphql',['json' => $params]);
        
            $body = json_decode($response->getBody(), true);

            if(!is_array($body)) {
                return false;
            }

            return $body;
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            return false;
        }
        catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return false;
        }
        catch (\GuzzleHttp\Exception\ConnectException $e) {
            return false;
        }
        catch (\GuzzleHttp\Exception\ServerException $e) {
            return false;
        }
        catch (\Exception $e) {
            return false;
        }

    }
}
