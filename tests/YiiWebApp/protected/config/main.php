<?php

return array(
    'basePath'          => __DIR__.'/..',
    'name'              => 'Yii Web App',
    'import'            => array('application.models.*', 'application.components.*', 'application.extensions.*'),
    'defaultController' => 'welcome',
    'components'        => array(
        'session'      => array('class' => 'CDbHttpSession', 'timeout' => YII_DEBUG ? 864000 : 1800),
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
    )
);
