<?php

# Polar\Core\Helper V1.0
# Require Polar

namespace Polar\Core;

class Helper {

  public static function initialize() {
    self::loadApplicationHelper();
    self::loadControllerHelper();
  }

  public static function loadApplicationHelper() {
    $applicationHelperPath = \Polar\Polar::$paths[ 'helpers' ] . 'ApplicationHelper.php';
    if ( file_exists( $applicationHelperPath ) ) {
      require_once $applicationHelperPath;
      return true;
    }
    return false;
  }

  public static function loadControllerHelper() {
    if ( !isset( Request::$params[ 'controller' ] ) ) {
      return false;
    }
    $helperPath = \Polar\Polar::$paths[ 'helpers' ] . ucfirst( \Polar\Core\Request::$params[ 'controller' ] ) . 'Helper.php';
    if ( file_exists( $helperPath ) ) {
      require_once $helperPath;
      return true;
    }
    return false;
  }

}
