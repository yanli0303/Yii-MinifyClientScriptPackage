# Yii-MinifyClientScriptPackage #

*By [Yan Li](https://github.com/yanli0303)* 

[![License](https://img.shields.io/badge/License-MIT-brightgreen.svg)](https://packagist.org/packages/yanli0303/yii-minify-client-script-package)
[![PayPayl donate button](http://img.shields.io/badge/paypal-donate-orange.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=silentwait4u%40gmail%2ecom&lc=US&item_name=Yan%20Li&no_note=0&currency_code=USD&bn=PP%2dDonationsBF%3apaypal%2ddonate%2ejpg%3aNonHostedGuest)

A PHP console application for minifying JavaScript and CSS files of a PHP Yii web application.

## Usage ##
1. Minify JavaScript and CSS files with [Ant-MinifyJsCss](https://github.com/yanli0303/Ant-MinifyJsCss)
	> ant -Dsrc="path to Yii web application webroot" minify

2. Execute command 
	> php src/console "path to Yii web application webroot"

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

## Run tests ##

1. Install [composer](https://getcomposer.org/) and run `composer install`
2. Install [PHPUnit](https://phpunit.de/) and run `phpunit`

## TODO ##
1. Known limitations
2. Array pretty printer
