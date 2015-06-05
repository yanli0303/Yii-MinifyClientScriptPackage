<?php

return array(
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
);
