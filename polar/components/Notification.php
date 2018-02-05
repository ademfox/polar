<?php

namespace Polar\Component;

class Notification {

  private static $sessionIndex = '___polarNotification';

  public static function available( $name = null ) {
    if ( is_string( $name ) ) {
      return isset( $_SESSION[ self::$sessionIndex ][ $name ] );
    }
    return isset( $_SESSION[ self::$sessionIndex ] );
  }

  public static function clear( $name = null ) {
    if (
      is_string( $name ) and
          isset( $_SESSION[ self::$sessionIndex ][ $name ] )
    ) {
      unset( $_SESSION[ self::$sessionIndex ][ $name ] );
    }
    else {
      $_SESSION[ self::$sessionIndex ] = array();
    }
  }

  public static function pull( $name = null ) {
    # If group is valid and there is a session with given group.
    if (
      is_string( $name ) and
          isset( $_SESSION[ self::$sessionIndex ] ) and
          isset( $_SESSION[ self::$sessionIndex ][ $name ] )
    ) {
      return $_SESSION[ self::$sessionIndex ][ $name ];
    }
    else if (
      is_null( $name ) and
        isset( $_SESSION[ self::$sessionIndex ] )
    ) {
      return $_SESSION[ self::$sessionIndex ];
    }
    return false;
  }

  public static function push( $name, $notification = null ) {
    if ( !isset( $_SESSION[ self::$sessionIndex ] ) ) {
      $_SESSION[ self::$sessionIndex ]          = array();
      $_SESSION[ self::$sessionIndex ][ $name ] = array();
    # If there is no session with given group, create an empty one.
    }
    else if ( !isset( $_SESSION[ self::$sessionIndex ][ $name ] ) ) {
      $_SESSION[ self::$sessionIndex ][ $name ] = array();
    }
    if ( is_string( $notification ) ) {
      $_SESSION[ self::$sessionIndex ][ $name ][] = $notification; 
      return true;
    }
    return false;
  }

}