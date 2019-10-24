<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->options('/{any:.*}', ['middleware' => 'cors', function() {
    return response(['status' => 'success']);
  }]);

/**
 * user
 */
$router->group(['prefix' => 'api/user'], function($router)
{
    $router->post('register', ['middleware' => 'cors', 'uses' => 'UserController@registerUser']);
    $router->post('uploadphoto', ['middleware' => 'cors', 'uses' => 'UserController@uploadProfilePhoto']);
    $router->post('sns', ['middleware' => 'cors', 'uses' => 'UserController@snsSignupSignin']);

    $router->post('login', ['middleware' => 'cors', 'uses' => 'UserController@loginUser']);
    $router->post('logout', ['middleware' => 'cors', 'uses' => 'UserController@logoutUser']);
    $router->post('refresh', ['middleware' => 'cors', 'uses' => 'UserController@refresh']);
    $router->get('getUserData', ['middleware' => 'cors', 'uses' => 'UserController@getUserData']);
});

/**
 * weather
 */
$router->group(['prefix' => 'api'], function($router)
{
    $router->get('weather', ['middleware' => 'cors', 'uses' => 'WeatherController@getCCandFC']);
});

/**
 * buy and sell
 */
$router->group(['prefix' => 'api'], function($router)
{
    $router->get('buyandsell', ['middleware' => 'cors', 'uses' => 'BuyAndSellController@getCarousell']);
    $router->get('buyandsellview', ['middleware' => 'cors', 'uses' => 'ScrapController@scrapCarousell']);
    $router->get('buyandsellfeed', ['middleware' => 'cors', 'uses' => 'BuyAndSellController@feedCarousell']);
    $router->get('buyandsellfilter', ['middleware' => 'cors', 'uses' => 'BuyAndSellController@filterCarousell']);
    $router->get('carousellcategory', ['middleware' => 'cors', 'uses' => 'BuyAndSellController@loadCarousellCategory']);
});

/**
 * news
 */
$router->group(['prefix' => 'api'], function($router)
{
    $router->get('news', ['middleware' => 'cors', 'uses' => 'NewsController@feedNews']);
    $router->get('newscategory', ['middleware' => 'cors', 'uses' => 'NewsController@feedNewsByCategory']);
    $router->get('viewnews', ['middleware' => 'cors', 'uses' => 'NewsController@viewNewsArticle']);
});

/**
 * search
 */
$router->group(['prefix' => 'api'], function($router)
{
    $router->get('searchGoogle', ['middleware' => 'cors', 'uses' => 'SearchEngineController@doGoogleSearch']);
    $router->get('searchCarousell', ['middleware' => 'cors', 'uses' => 'BuyAndSellController@doCarousellSearch']);
});

/**
 * to test something
 */
$router->group(['prefix' => 'api'], function($router)
{
    $router->post('test', ['middleware' => 'cors', 'uses' => 'ScrapController@scrapCarousell']);
});