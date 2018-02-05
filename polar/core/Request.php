<?php

# \Polar\Core\Request V1.0
# Require Polar

namespace Polar\Core;

class Request {

  public static $accept;
  public static $IPAddress;
  public static $host;
  public static $languages;
  public static $path;
  public static $port;
  public static $requestMethod;
  public static $schema;
  public static $userAgent;
  public static $URL;

  public static $_polarRequestMethodOverrideIndex = '___polarRequestMethodOverride';
  public static $_polarPathIndex                  = '___polarPath';

  public static $input  = array();
  public static $params = array();
  public static $query  = array();

  private static $supportedRequestMethods = array( 'POST', 'GET', 'PUT', 'DELETE' );

  # https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept
  # Accept request HTTP header 
  public static function accepts( $accepts ) {
    $match = false;
    foreach( self::$accept as $_accept ) {
      if ( $accepts == $_accept ) {
        $match = true;
      }
    }
    return $match;
  }

  public static function getAccept() {
    $accept = $_SERVER[ 'HTTP_ACCEPT' ];
    $accept = explode( ',', $accept );
    if ( !is_array( $accept ) ) {
      $accept = array( $accept );
    }
    self::$accept = $accept;
    return self::$accept;
  }

  public static function getIPAddress() {
    self::$IPAddress = $_SERVER[ 'REMOTE_ADDR' ];
    return self::$IPAddress;
  }

  public static function getHost() {
    self::$host = $_SERVER[ 'HTTP_HOST' ];
    return self::$host;
  }

  public static function getLanguages() {
    $languages = $_SERVER[ 'HTTP_ACCEPT_LANGUAGE' ];
    $languages = explode( ',', $languages );
    if ( !is_array( $languages ) ) {
      $languages = array( $languages );
    }
    self::$languages = $languages;
    return self::$languages;
  }

  private static function getRequestMethod() {
    $requestMethod = '';
    if ( isset( $_GET[ self::$_polarRequestMethodOverrideIndex ] ) ) {
      $requestMethod = $_GET[ self::$_polarRequestMethodOverrideIndex ];
                unset( $_GET[ self::$_polarRequestMethodOverrideIndex ] );
    }
    else if ( isset( $_POST[ self::$_polarRequestMethodOverrideIndex ] ) ) {
      $requestMethod = $_POST[ self::$_polarRequestMethodOverrideIndex ];
                unset( $_POST[ self::$_polarRequestMethodOverrideIndex ] );
    }
    if ( !in_array( strtoupper( $requestMethod ), self::$supportedRequestMethods ) ) {
      $requestMethod = $_SERVER[ 'REQUEST_METHOD' ];
    }
    self::$requestMethod = $requestMethod;
    return self::$requestMethod;
  }

  public static function getPath() {
    if ( isset( $_GET[ self::$_polarPathIndex ] ) ) {
      self::$path = $_GET[ self::$_polarPathIndex ];
      unset( $_GET[ self::$_polarPathIndex ] );
    }
    else {
      self::$path = $_SERVER[ 'PHP_SELF' ];
    }
    return self::$path;
  }

  public static function getPort() {
    self::$port = $_SERVER[ 'SERVER_PORT' ];
    return self::$port;
  }

  public static function getSchema() {
    if (
      empty( $_SERVER[ 'HTTPS' ] ) or
      $_SERVER[ 'HTTPS' ] === 'off'
    ) {
      self::$schema = 'http';
    }
    else {
      self::$schema = 'https';
    }
    return self::$schema;
  }

  public static function getUserAgent() {
    self::$userAgent = $_SERVER[ 'HTTP_USER_AGENT' ];
    return self::$userAgent;
  }

  public static function getURL() {
    self::$URL = self::$schema . '://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];
    return self::$URL;
  }

  public static function initialize() {
    self::getAccept();
    self::getIPAddress();
    self::getHost();
    self::getLanguages();
    self::getRequestMethod();
    self::getPath();
    self::getPort();
    self::getSchema();
    self::getURL();
    self::getUserAgent();
    self::$query = $_GET;
    self::$input = $_POST;
    $jsonData = json_decode( file_get_contents( 'php://input' ), true );
    if ( is_array( $jsonData ) ) {
      self::$params = array_merge( self::$query, self::$input, $jsonData );
    }
    else {
      self::$params = array_merge( self::$query, self::$input );
    }
    return true;
  }

}
