<?php

namespace Polar;

class ApplicationValidator {

  public function recaptchaHandler() {
    if ( \Polar\Polar::$config[ 'enableRecaptcha' ] === false ) {
      return true;
    }
    $secret    = \Polar\Polar::$config[ 'recaptcha' ][ 'secret' ];
    $response  = $_POST[ 'g-recaptcha-response' ];
    $IPAddress = \Polar\Core\Request::$IPAddress;
    $URL       = 'https://www.google.com/recaptcha/api/siteverify';
    $URL      .= '?secret='   . $secret;
    $URL      .= '&response=' . $response;
    $URL      .= '&remoteip=' . $IPAddress;
    $verify    = file_get_contents( $URL );
    $decoded   = json_decode( $verify, true );
    if ( !$decoded[ 'success' ] ) {
      $this->addErrorMessage( 'recaptcha', 'Invalid recaptcha input.' );
      $this->isValid = false;
      return false;
    }
    return true;
  }

  public function validateDateString(  $date ) {
    return \Polar\Component\DateUtil::isDateString( $date );
  }

  public function validateDateTimeString( $datetime ) {
    return \Polar\Component\DateUtil::isDateTimeString( $datetime );
  }

  public function validateDecimal( $decimal ) {
    return is_numeric( $decimal );
  }

  public function validateIsFutureDatetime( $datetime ) {
    return \Polar\Component\DateUtil::isFuture( $datetime );
  }

  public function validateTimeString( $time ) {
    return \Polar\Component\DateUtil::isTimeString( $time );
  }

  public function validateURL( $URL ) {
    return ( filter_var( $url, FILTER_VALIDATE_URL ) ) ? true : false;
  }

}
