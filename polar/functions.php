<?php

# Polar Functions V1.1

function config( $key ) {
  return \Polar\Polar::$config[ $key ] ?? false;
}

function e( $string = '' ) {
  echo $string;
  return $string;
}

function eh( $string = '' ) {
  echo HTMLspecialchars( $string );
  return $string;
}

function eURL( $path = '' ) {
  $URL = url( $path );
  echo $URL;
  return $URL;
}

function ife( $condition, $string = '', $else = '' ) {
  echo ( $condition ) ? $string : $else;
}

function removeNumericKeys( $array ) {
  foreach( $array as $item ) {
    $key = key( $item );
    if ( is_numeric( key( $item ) ) ) {
      unset( $array[ $key ] );
    }
  }
  return $array;
}

function linkTo( $value, $URL, $attributes = array() ) {
  if ( !preg_match( '/^( http|https ):\/\//', $URL ) ) {
    $URL = \Polar\Polar::$config['baseURL'] . $URL;
    if ( isset( $attributes[ 'method' ] ) ) {
      $URL .= '?___polar-request-method=' . strtoupper( $attributes[ 'method' ] );
    }
  }
  $link = '<a href="' . $URL . '" ' . \Polar\Components\HTML::arrayToAttributes( $attributes ) . '>' . $value . '</a>';
  echo $link;
  return $link;
}

function loadScripts( $scripts, $path, $extension, $HTML ) {
  if ( !is_array( $scripts ) ) {
    $scripts = array( $scripts );
  }
  foreach( $scripts as $script ) {
    if ( !preg_match( '/^(http|https):\/\//', $script ) ) {
      $script = $path . $script . $extension;
      if ( !file_exists( 'app/assets/' . $script ) ) {
        continue;
      }
    }
    echo preg_replace( '/(\<yield\>)/', \Polar\Polar::$config[ 'baseURL' ] . $script, $HTML ) . PHP_EOL;
  }
}

function metaCSRF( $key = 'polar-csrf-token' ) {
  $token = Polar\Component\CSRF::generateToken( $key );
  echo '<meta content="' . $token . '" name="csrf-token">' . PHP_EOL;
}

function mySqlDateTime( $timestamp = null ) {
  $timestamp = is_null( $timestamp ) ? time() : $timestamp;
  return date( 'Y-m-d H:i:s', $timestamp );
}

function requireSSL( bool $withWWW = true ) {
  $redirect = false;
  # if no https
  if ( 
    empty( $_SERVER[ 'HTTPS' ] ) or
    $_SERVER[ 'HTTPS' ] === 'off'
   ) {
    $redirect = true;
  }
  # if no www.
  if ( 
    ( $withWWW == true and !preg_match( '/^(www\.)/', $_SERVER[ 'HTTP_HOST' ] ) ) or
    ( $withWWW == false and preg_match( '/^(www\.)/', $_SERVER[ 'HTTP_HOST' ] ) )
   ) {
    $redirect = true;
  }
  if ( $redirect ) {
    $www = ( $withWWW == true ) ? 'www.' : '';
    $URL = 'https://' . $www . preg_replace( '/^(www\.)/', '', $_SERVER[ 'HTTP_HOST' ] ) . $_SERVER[ 'REQUEST_URI' ];
    header( 'Location: ' . $URL );
    exit(  );
  }
}

function sa( string $string, array $values = array(  ) ) {
  if ( !is_array( $values ) ) {
    $values = array( $values );
  }
  $valueIndex = 0;
  $array   = array(  );
  $strings = explode( ',', $string );
  $parseVariable = function( $string ) use( $values, &$valueIndex ) {
    if ( $string === '?' ) {
      $valueIndex++;
      return $values[ $valueIndex-1 ];
    } else {
      return $string;
    }
  };
  $parse = function( string $string ) use( &$array, $parseVariable ) {
    $string = ts( $string );
    $match  = '';
    $regex  = '/^[\w]+:.*$/';
    if ( preg_match( $regex, $string, $match ) ) {
      $key         = preg_replace( '/:.*$/', '', $match[ 0 ] );
      $value       = preg_replace( '/^[\w]+:/', '', $match[ 0 ] );
      $array[ $key ] = $parseVariable( ts( $value ) );
    } else {
      $array[  ] = $parseVariable( ts( $string ) );
    }
  };
  foreach( $strings as $string ) {
    $parse( $string );
  }
  return $array;
}

function javascripts( $javascripts ) {
  loadScripts( 
    $javascripts,
    \Polar\Polar::$paths[ 'javascripts' ],
    '.js',
    '<script src="<yield>"></script>'
   );
}

function stylesheets( $stylesheets ) {
  loadScripts( 
    $stylesheets,
    \Polar\Polar::$paths[ 'stylesheets' ],
    '.css',
    '<link href="<yield>" rel="stylesheet">'
   );
}

function thisURL( $https = true ) {
  if ( $https === true ) {
    $https = 'https://';
  } else if ( is_null( $https ) ) {
    $https = '';
  } else {
    $https = 'http://';
  }
  return $https . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];
}

function URL( $path = '' ) {
  return \Polar\Polar::$config[ 'baseURL' ] . preg_replace( '/^[\/]+/', '', $path );
}

# Trim string.
function ts( $string ) {
  return preg_replace( array( '/^[\s]*/', '/[\s]*$/' ), '', $string );
}

function whitelist( $array, $whitelist ) {
  $result = array();
  foreach( $whitelist as $white ) {
    if ( isset( $array[ $white ] ) ) {
      $result[ $white ] = $array[ $white ];
    }
  }
  return $result;
}
