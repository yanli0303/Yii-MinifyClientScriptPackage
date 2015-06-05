# Yii-MinifyClientScriptPackage #

*By [Yan Li](https://github.com/yanli0303)* 

<!--
[![Latest Stable Version](http://img.shields.io/packagist/v/yanli0303/yii-minify-client-script-package.svg)](https://packagist.org/packages/yanli0303/yii-minify-client-script-package)
[![Total Downloads](https://img.shields.io/packagist/dt/yanli0303/yii-minify-client-script-package.svg)](https://packagist.org/packages/yanli0303/yii-minify-client-script-package)
-->
[![Build Status](https://travis-ci.org/yanli0303/Yii-MinifyClientScriptPackage.svg?branch=master)](https://travis-ci.org/yanli0303/Yii-MinifyClientScript)
[![Coverage Status](https://coveralls.io/repos/yanli0303/Yii-MinifyClientScriptPackage/badge.svg?branch=master)](https://coveralls.io/r/yanli0303/Yii-MinifyClientScriptPackage?branch=master)
[![License](https://img.shields.io/badge/License-MIT-brightgreen.svg)](https://packagist.org/packages/yanli0303/yii-minify-client-script-package)
[![PayPayl donate button](http://img.shields.io/badge/paypal-donate-orange.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=silentwait4u%40gmail%2ecom&lc=US&item_name=Yan%20Li&no_note=0&currency_code=USD&bn=PP%2dDonationsBF%3apaypal%2ddonate%2ejpg%3aNonHostedGuest)

A PHP console application for minifying JavaScript and CSS files of a PHP Yii web application.

## Usage ##
1. Minify JavaScript and CSS files with [Ant-MinifyJsCss](https://github.com/yanli0303/Ant-MinifyJsCss)
    > ant -Dsrc="path to Yii web application webroot" minify

2. Download *yiimin.phar* from [downloads](https://github.com/yanli0303/Yii-MinifyClientScriptPackage/tree/master/downloads)

3. Open a new command/terminal window, change current directory to the *Yii web app webroot*, and execute following command
    > php yiimin.phar minify -v

Take the [YiiWebApp](https://github.com/yanli0303/Yii-MinifyClientScriptPackage/tree/master/tests/YiiWebApp) in tests as an example, suppose you have 3 client script packages defined in Yii web app config file [protected/config/main.php](https://github.com/yanli0303/Yii-MinifyClientScriptPackage/blob/master/tests/YiiWebApp/protected/config/main.php):

```php
'clientScript' => array(
    'class'    => 'CClientScript',
    'packages' => array(
        'jquery'   => array(
            'baseUrl' => '//code.jquery.com/',
            'js'      => array('jquery-1.11.3.min.js')
        ),
        'layout'   => array(
            'baseUrl' => '',
            'js'      => array('css/layout/pageHeader.css', 'css/layout/pageFooter.css')
        ),
        'homePage' => array(
            'baseUrl' => '',
            'depends' => array('jquery'),
            'js'      => array('js/homePage/homePage.js'),
            'css'     => array('css/homePage/homePage.css')
        )
    )
)
```

Firstly you need to minify each local JavaScript and CSS files, after doing so, you'll have the following files:
- css/layout/pageHeader.min.css
- css/layout/pageFooter.min.css
- css/homePage/homePage.min.css
- js/homePage/homePage.min.js

Then, with this tool:

1. Open a new terminal window, change current directory to *YiiWebApp*
2. Run `php yiimin.phar minify -v`

After that, your config file will be changed to:

```php
'clientScript' => array(
    'class' => 'CClientScript',
    'packages' => array(
        'jquery' => array(
            'baseUrl' => '//code.jquery.com',
            'js' => array('jquery-1.11.3.min.js')
        ),
        'layout' => array(
            'baseUrl' => '',
            'js' => array('assets/layout_d7863c9225aabcb...cf618.min.js')
        ),
        'homePage' => array(
            'baseUrl' => '',
            'depends' => array('jquery'),
            'css' => array('assets/homePage_379c038778aa...620ae.min.css'),
            'js' => array('assets/homePage_8cc592b51a3ca...7c458.min.js')
        )
    )
)
```

And the *assets/homePage_379c038778aa...620ae.min.css* comprises the contents of following files:
- css/layout/pageHeader.min.css
- css/layout/pageFooter.min.css
- css/homePage/homePage.min.css

## Note ##

1. Extension of CSS files should be **.css**
    - Extension of minified CSS files will be **.min.css**
    - CSS files will be minified with [YUI Compressor](http://yui.github.io/yuicompressor/)
2. Extension of JavaScript files should be **.js**
    - Extension of minified JavaScript files will be **.min.js**
    - JavaScript files will be minified with [Google Closure Compiler](https://github.com/google/closure-compiler)
3. The minified files will be put in the same directory of unminified file
4. If the minified version already exists, it **won't be** overwritten
5. By default, ignore the JavaScript and CSS files in both **node_modules** and **bower_components**

## Limitations ##
- Better to only register one client script package on a page/view. If you registered several packages on a page, make sure the packages don't depend on a same package, otherwise the resources of the shared package will be loaded on the page multiple times;
-  Don't use [Yii::app()->getClientScript()>registerCssFile()](http://www.yiiframework.com/doc/api/1.1/CClientScript#registerCssFile-detail) or [Yii::app()->getClientScript()->registerScriptFile()](http://www.yiiframework.com/doc/api/1.1/CClientScript#registerScriptFile-detail) anymore; use [Yii::app()->getClientScript()->registerPackage()](http://www.yiiframework.com/doc/api/1.1/CClientScript#registerPackage-detail) instead.

## Run tests ##

1. Install [composer](https://getcomposer.org/) and run `composer install`
2. Install [PHPUnit](https://phpunit.de/) and run `phpunit`
