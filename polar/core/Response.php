<?php

# Polar\Core\Response V1.0
# Require Polar

namespace Polar\Core;

class Response {

  public static function file( $filePath, $contentType ) {
    $file = fopen( $filePath, 'rb' );
    header( 'Content-Type: '   . $contentType );
    header( 'Content-Length: ' . filesize( $filePath ) );
    fpassthru( $file );
  }

  # https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
  public static function HTTPCode( $code ) {
    http_response_code( $code );
  }

  public static function json( $array ) {
    header( 'Content-Type: application/json; charset=utf-8;' );
    $json = json_encode( $array );
    $json = utf8_encode( $json );
    echo $json;
    return $json;
  }

  public static function jsonp( $array ) {
    header( 'content-type: application/json; charset=utf-8' );
    $return = $_GET[ 'callback' ] . '(' . json_encode( $array ) . ')';
    echo $return;
    return $return;
  }

  public static function redirect( $path ) {
    if ( !preg_match( '/^(http|https):\/\//', $path ) ) {
      $path = \Polar\Polar::$config[ 'baseURL' ] . $path;
    }
    header( 'location: ' . $path );
    exit();
  }

}
