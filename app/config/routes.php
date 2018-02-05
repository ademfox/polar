<?php

use Polar\Core\Router;

$rules = array(
  'date'  => '^([0-9]{4})-(1[0-2]|0[1-9])-(3[0-1]|[1-2][0-9]|0[1-9])$',
  'ID'    => '^[0-9]+$',
  'token' => '^[a-zA-Z0-9]+'
);

$methods = array( 'GET', 'POST' );

Router::root(    'Home@index' );
Router::noMatch( 'App@broken'    );

Router::group( 'pages/', function( $r ) {

  $methods = array( 'GET', 'POST' );

  $r( $methods, 'hello', 'Home@hello' );

}, $rules );