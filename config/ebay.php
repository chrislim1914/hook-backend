<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Modes
    |--------------------------------------------------------------------------
    |
    | This package supports sandbox and production modes.
    | You may specify which one you're using throughout
    | your application here.
    |
    | Supported: "sandbox", "production"
    |
    */

    'mode' => env('EBAY_MODE'),


    /*
    |--------------------------------------------------------------------------
    | Site Id
    |--------------------------------------------------------------------------
    |
    | The unique numerical identifier for the eBay site your API requests are to be sent to.
    | For example, you would pass the value 3 to specify the eBay UK site.
    | A complete list of eBay site IDs is available
    |(http://developer.ebay.com/devzone/finding/Concepts/SiteIDToGlobalID.html).
    |
    |
    */

    'siteId' => env('EBAY_SITE_ID','0'),

    /*
    |--------------------------------------------------------------------------
    | Marketplace Id
    |--------------------------------------------------------------------------
    |
    | The unique numerical identifier for the eBay site your API requests are to be sent to.
    | For example, you would pass the value 3 to specify the eBay UK site.
    | A complete list of eBay site IDs is available
    |(http://developer.ebay.com/devzone/finding/Concepts/SiteIDToGlobalID.html).
    |
    |
    */

    'marketplaceId' => env('EBAY_MARKETPLACE_ID'),

    /*
    |--------------------------------------------------------------------------
    | KEYS
    |--------------------------------------------------------------------------
    |
    | Get keys from EBAY. Create an app and generate keys.
    | (https://developer.ebay.com)
    | You can create keys for both sandbox and production also
    | User token can generated here.
    |
    */
    
    'sandbox' => [
        'credentials' => [
            'devId'     => '7b96c1fb-4dcd-43d4-bf94-e53d7c789580',
            'appId'     => 'UTH702In-hook-SBX-f92e94856-81adb492',
            'certId'    => 'SBX-92e94856f9fc-b634-4001-9ca5-0c1a',
        ],
        'authToken'         => 'https://signin.sandbox.ebay.com/ws/eBayISAPI.dll?SignIn&runame=UTH702_Inc-UTH702In-hook-S-vnptes&SessID=',
        'oauthUserToken'    => 'https://auth.sandbox.ebay.com/oauth2/authorize?client_id=UTH702In-hook-SBX-f92e94856-81adb492&response_type=code&redirect_uri=UTH702_Inc-UTH702In-hook-S-vnptes&scope=https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/buy.order.readonly https://api.ebay.com/oauth/api_scope/buy.guest.order https://api.ebay.com/oauth/api_scope/sell.marketing.readonly https://api.ebay.com/oauth/api_scope/sell.marketing https://api.ebay.com/oauth/api_scope/sell.inventory.readonly https://api.ebay.com/oauth/api_scope/sell.inventory https://api.ebay.com/oauth/api_scope/sell.account.readonly https://api.ebay.com/oauth/api_scope/sell.account https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly https://api.ebay.com/oauth/api_scope/sell.fulfillment https://api.ebay.com/oauth/api_scope/sell.analytics.readonly https://api.ebay.com/oauth/api_scope/sell.marketplace.insights.readonly https://api.ebay.com/oauth/api_scope/commerce.catalog.readonly https://api.ebay.com/oauth/api_scope/buy.shopping.cart https://api.ebay.com/oauth/api_scope/buy.offer.auction https://api.ebay.com/oauth/api_scope/commerce.identity.readonly https://api.ebay.com/oauth/api_scope/commerce.identity.email.readonly https://api.ebay.com/oauth/api_scope/commerce.identity.phone.readonly https://api.ebay.com/oauth/api_scope/commerce.identity.address.readonly https://api.ebay.com/oauth/api_scope/commerce.identity.name.readonly https://api.ebay.com/oauth/api_scope/sell.finances https://api.ebay.com/oauth/api_scope/sell.item.draft https://api.ebay.com/oauth/api_scope/sell.payment.dispute https://api.ebay.com/oauth/api_scope/sell.item',
        'ruName'            => 'UTH702_Inc-UTH702In-hook-S-vnptes'
    ],
    'production' => [
        'credentials'   => [
            'devId'     => '7b96c1fb-4dcd-43d4-bf94-e53d7c789580',
            'appId'     => 'UTH702In-hook-PRD-892db9319-5940c295',
            'certId'    => 'PRD-92db9319af26-40ed-4839-bf46-0190',
        ],
        'authToken'         => 'https://signin.ebay.com/ws/eBayISAPI.dll?SignIn&runame=UTH702_Inc-UTH702In-hook-P-mkduis&SessID=>',
        'oauthUserToken'    => 'https://auth.ebay.com/oauth2/authorize?client_id=UTH702In-hook-PRD-892db9319-5940c295&response_type=code&redirect_uri=UTH702_Inc-UTH702In-hook-P-mkduis&scope=https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/sell.marketing.readonly https://api.ebay.com/oauth/api_scope/sell.marketing https://api.ebay.com/oauth/api_scope/sell.inventory.readonly https://api.ebay.com/oauth/api_scope/sell.inventory https://api.ebay.com/oauth/api_scope/sell.account.readonly https://api.ebay.com/oauth/api_scope/sell.account https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly https://api.ebay.com/oauth/api_scope/sell.fulfillment https://api.ebay.com/oauth/api_scope/sell.analytics.readonly https://api.ebay.com/oauth/api_scope/sell.finances https://api.ebay.com/oauth/api_scope/sell.payment.dispute',
        'ruName'            => 'UTH702_Inc-UTH702In-hook-P-mkduis'
    ]
];