<?php

/**
 * General Configuration
 *
 * All of your system's general configuration settings go in here.
 * You can see a list of the default settings in craft/app/config/defaults/general.php
 */

 return array(
   'localhost' => array(
     'devMode' => true,
     'siteUrl' => 'http://chris.web/',
     'environmentVariables' => array(
       'baseUrl'  => 'http://chris.web/',
     ),
     'testToEmailAddress' => 'dev@email.com',
   ),

   // Use IP address of your droplet below
   '138.197.37.103' => array(
     'siteUrl' => 'http://138.197.37.103/',
     'environmentVariables' => array(
       'basePath' => '/var/www/html/',
       'baseUrl'  => 'http://138.197.37.103/',
     )
   )
 );
