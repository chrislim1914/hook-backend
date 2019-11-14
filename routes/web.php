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
    $router->post('sns', ['middleware' => 'cors', 'uses' => 'UserController@snsSignupSignin']);
    $router->post('register', ['middleware' => 'cors', 'uses' => 'UserController@registerUser']);

    $router->post('uploadphoto', ['middleware' => 'cors', 'uses' => 'UserController@uploadProfilePhoto']);
    $router->post('update', ['middleware' => 'cors', 'uses' => 'UserController@updateProfile']);
    

    $router->post('login', ['middleware' => 'cors', 'uses' => 'UserController@loginUser']);
    $router->post('logout', ['middleware' => 'cors', 'uses' => 'UserController@logoutUser']);
    $router->post('refresh', ['middleware' => 'cors', 'uses' => 'UserController@refresh']);
    $router->get('getUserData', ['middleware' => 'cors', 'uses' => 'UserController@getUserData']);

    // user product post
    $router->get('getPost', ['middleware' => 'cors', 'uses' => 'UserController@userPostProduct']);

    // send reset password link
    $router->post('sendEmailLink', ['middleware' => 'cors', 'uses' => 'UserController@verifyEmailUrl']);

    // send verify email link
    $router->post('resetEmailLink', ['middleware' => 'cors', 'uses' => 'UserController@forgetPasswordEmail']);
    // change the password after resetEmailLink
    $router->post('resetPassword', ['middleware' => 'cors', 'uses' => 'UserController@resetPassword']);

    // change the password
    $router->post('changePassword', ['middleware' => 'cors', 'uses' => 'UserController@changePassword']);


    // verify email link
    $router->get('verifyUrl/{payload}', ['middleware' => 'cors', 'uses' => 'UserController@verifyUrl']);

    // get sellet data
    $router->get('seller', ['middleware' => 'cors', 'uses' => 'UserController@getSellerData']);
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
    // carousell
    $router->get('buyandsell', ['middleware' => 'cors', 'uses' => 'BuyAndSellController@mergeFrontDisplay']);
    $router->get('buyandsellview', ['middleware' => 'cors', 'uses' => 'BuyAndSellController@viewSingleContent']);
    $router->get('buyandsellfeed', ['middleware' => 'cors', 'uses' => 'BuyAndSellController@feedBuyandSell']);
    $router->get('buyandsellfilter', ['middleware' => 'cors', 'uses' => 'BuyAndSellController@buyAndSellFilter']);
    $router->get('carousellcategory', ['middleware' => 'cors', 'uses' => 'CarousellController@loadCarousellCategory']);

    // our own
    $router->post('product/add', ['middleware' => 'cors', 'uses' => 'ProductController@postProduct']);
    $router->post('product/update', ['middleware' => 'cors', 'uses' => 'ProductController@updatePost']);
    $router->post('product/changeStatus', ['middleware' => 'cors', 'uses' => 'ProductController@changeStatus']);
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
    $router->get('searchProduct', ['middleware' => 'cors', 'uses' => 'BuyAndSellController@buyAndSellSearch']);
});

/**
 * to test something
 */
$router->group(['prefix' => 'api'], function($router)
{
    $router->get('test/{page}', ['middleware' => 'cors', 'uses' => 'CarousellController@feedCarousell']);
});