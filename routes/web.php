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

/**
 * user
 */
$router->group(['prefix' => 'api/user'], function($router)
{
    $router->post('register', ['middleware' => 'cors', 'uses' => 'UserController@registerUser']);
    $router->post('uploadphoto', ['middleware' => 'cors', 'uses' => 'UserController@uploadProfilePhoto']);

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
});

/**
 * news
 */
$router->group(['prefix' => 'api'], function($router)
{
    $router->get('news', ['middleware' => 'cors', 'uses' => 'NewsController@feedNews']);
});

/**
 * search
 */
$router->group(['prefix' => 'api'], function($router)
{
    $router->get('search', ['middleware' => 'cors', 'uses' => 'SearchEngineController@doSomeSearching']);
});