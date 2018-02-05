<?php

namespace Polar\Component;

class Util {

  public static function stringToCamelCase( $string, $isFirstLetterCapitalized = false ) {
    $matches = array();
    $result  = preg_match_all( '/_[a-z]/', $string, $matches );
    if ( $result ) {
      foreach( $matches[ 0 ] as $match ) {
        $string = str_replace( $match, strtoupper( $match ), $string );
      }
    }
    $string = str_replace( '_', '', $string );
    return $isFirstLetterCapitalized ? ucfirst( $string ) : $string;
  }

  public static function stringToUnderscore( $string ) {
    return ltrim( strtolower( preg_replace( '/[A-Z]/', '_$0', $string ) ), '_' );
  }

}
