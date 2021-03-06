<?php

/**
 * General Configuration
 *
 * All of your system's general configuration settings go in here.
 * You can see a list of the default settings in craft/app/config/defaults/general.php
 */

 return array(

   '*' => array(
        'omitScriptNameInUrls' => true,
        'loginPath' => 'admin',
    ),

   'chris.web' => array(
     'devMode' => true,
     'siteUrl' => 'http://chris.web',
     'environmentVariables' => array(
           'basePath' => '',
           'baseUrl'  => 'http://chris.web',
      )
   ),

   // Use IP address of your droplet below
   '138.197.37.103' => array(
     'siteUrl' => 'https://chrishallahan.com/',
     'environmentVariables' => array(
       'basePath' => 'assets/',
       'baseUrl'  => 'https://chrishallahan.com',
     )
   )
 );
