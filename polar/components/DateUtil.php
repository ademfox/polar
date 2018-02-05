<?php

namespace Polar\Component;

class DateUtil {

  public static function currentDateString() {
    return date( 'Y-m-d' );
  }

  public static function currentDateTimeString() {
    return date( 'Y-m-d H:i:s' );
  }

  public static function hasExpired( $dateTimeString ) {
    $dateTimeNow = new \DateTime( 'now' );
    $dateTime    = new \DateTime( $dateTimeString );
    $difference  = ( int ) $dateTimeNow->getTimestamp() - ( int ) $dateTime->getTimestamp();
    return ( $difference > 0 ) ? true : false;
  }

  public static function isDateString( $dateString ) {
    $regex = '/^([0-9]{4})-(1[0-2]|0[1-9])-(3[0-1]|[1-2][0-9]|0[1-9])$/';
    return preg_match( $regex, $dateString ) ? true : false;
  }

  public static function isDateTimeString( $dateTimeString ) {
    $regex = '/^([0-9]{4})-(1[0-2]|0[1-9])-(3[0-1]|[1-2][0-9]|0[1-9]) (2[0-3]|[0-1][0-9]):(5[0-9]|[0-4][0-9]):(5[0-9]|[0-4][0-9])$/';
    return preg_match( $regex, $dateTimeString ) ? true : false;
  }

  public static function isFuture( $dateTimeString ) {
    $dateTimeNow = new \DateTime( 'now' );
    $dateTime    = new \DateTime( $dateTimeString );
    $difference  = ( int ) $dateTimeNow->getTimestamp() - ( int ) $dateTime->getTimestamp();
    return ( $difference < 0 ) ? true : false;
  }

  public static function isTimeString( $timeString ) {
    $regex = '/^(2[0-3]|[0-1][0-9]):(5[0-9]|[0-4][0-9]):(5[0-9]|[0-4][0-9])$/';
    return preg_match( $regex, $timeString ) ? true : false;
  }

}
