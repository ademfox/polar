<?php

# Polar V 1.2

namespace Polar;

class Polar {

  public static $enableErrorReporting = true;
  public static $config               = array();
  public static $paths                = array();

  public static function buildApp() {
    include_once( self::$paths[ 'config'      ] . 'init.php'                  );
    require_once( self::$paths[ 'models'      ] . 'ApplicationModel.php'      );
    require_once( self::$paths[ 'controllers' ] . 'ApplicationController.php' );
    require_once( self::$paths[ 'filters'     ] . 'ApplicationFilter.php'     );
    require_once( self::$paths[ 'validators'  ] . 'ApplicationValidator.php'  );
    require_once( self::$paths[ 'vendor'      ] . 'vendor.php'                );
    foreach( ( include 'core.php' ) as $core ) {
      require_once 'polar/core/' . $core . '.php';
    }
    foreach( ( include 'components.php' ) as $component ) {
      require_once 'polar/components/' . $component . '.php';
    }
    foreach ( glob( self::$paths[ 'filters' ] . '*.php' ) as $filter ) {
      require_once $filter;
    }
    foreach ( glob( self::$paths[ 'validations' ] . '*.php' ) as $validation ) {
      require_once $validation;
    }
    foreach ( glob( self::$paths[ 'validators' ] . '*.php' ) as $validator ) {
      require_once $validator;
    }
    foreach ( glob( self::$paths[ 'models' ] . '*.php' ) as $model ) {
      require_once $model;
    }
    foreach ( glob( self::$paths[ 'controllers' ] . '*Controller.php' ) as $controller ) {
      require_once $controller;
    }
  }

  public static function getPaths() {
    self::$paths = ( include 'polar/paths.php' );
  }

  public static function loadConfigurations() {
    # Get configurations.
    self::$config = ( require_once self::$paths[ 'config' ] . 'config.php' );
    # If environment config is set.
    if (
          isset( self::$config[ 'environment' ] ) and
      is_string( self::$config[ 'environment' ] )
    ) {
      # Get environment name.
      $environment       = self::$config[ 'environment' ];
      # Get environment config.
      $environmentConfig = ( require_once self::$paths['configEnvironment'] . $environment . '.php' );
      # Merge config with environment config.
      self::setConfig( $environmentConfig );
    }
  }

  public static function loadFunctions() {
    require_once 'polar/functions.php';
  }

  public static function setConfig( $configuration ) {
    foreach( $configuration as $key => $value ) {
      self::$config[ $key ] = $value;
    }
  }

  public static function setupPHPConfigurations() {
    if (
      isset( self::$config[ 'enableErrorReporting' ] ) and
             self::$config[ 'enableErrorReporting' ] === true
    ) {
      self::$enableErrorReporting = true;
    } else {
      self::$enableErrorReporting = false;
    }
    if ( self::$enableErrorReporting ) {
      error_reporting( E_ALL );
      ini_set( 'display_errors', 1 );
    }
    date_default_timezone_set( self::$config[ 'timeZone' ] );
    session_start();
  }

  public static function start() {
    self::getPaths();
    self::loadConfigurations();
    self::setupPHPConfigurations();
    self::loadFunctions();
    self::buildApp();
    \Polar\Core\Request::initialize();
    require_once self::$paths[ 'config' ] . 'routes.php';
    \Polar\Core\Router::route();
  }

}
