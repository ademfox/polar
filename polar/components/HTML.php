<?php

# Polar\Component\HTML V1.0

namespace Polar\Component;

class HTML {

  # Convert nested array into HTML attributes string.
  public static function arrayToAttributes( $array ) {
    $attributes = array();
    $context    = array();
    $index      = 0;
    $insert = function( $value ) use( &$context, &$index, &$attributes ) {
      $result = '';
      for( $x = 0; $x < $index; $x++ ) {
        $divider = ( $x == 0 ) ? '' : '-';
        $result .= $divider.$context[ $x ];
      }
      $attributes[] = $result . '="' . $value . '"';
    };
    $inspect = function( $k, $v ) use( &$inspect, &$insert, &$context, &$index ) {
  	$context[ $index ] = $k;
      $index++;
      if ( is_array( $v ) ) {
        foreach( $v as $_k => $_v ) {
          $inspect( $_k, $_v, $context );
        }
        $index--;
      }
      else {
        $insert( $v );
        $index--;
      }
    };
    foreach( $array as $key => $value ) {
      $inspect( $key, $value );
    }
    return implode( ' ', $attributes );
  }

  # Convert an array into query string.
  public static function arrayToQueryStrings( $array ) {
    $query = http_build_query( $array );
    return $query;
  }

  public static function interpolate( $HTML, $array ) {
    foreach( $array as $key => $value ) {
      $regex = '/{' . preg_quote( $key ) . '}/';
      $HTML  = preg_replace( $regex, $value, $HTML );
    }
    return $HTML;
  }

}