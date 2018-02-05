<?php

# \Polar\Core\Controller V1.0
# Require Response

namespace Polar\Core;

class Controller extends \Polar\ApplicationController {

  public $defaultLayout = 'application';
  public $layout        = 'application';
  public $params;

  public $afterAction  = array();
  public $beforeAction = array();

  public function redirect( $path ) {
    \Polar\Core\Response::redirect( $path );
    return $this;
  }

  public function renderView( $render = '', $options = array() ) {
    if (
                         is_string( $render ) and
      self::isControllerActionCall( $render )
    ) {
      $call = self::controllerActionCallToArray( $render );
      if ( self::isControllerActionCallable( $call[ 'controller' ], $call[ 'action' ] ) ) {
        self::call( $call[ 'controller' ], $call[ 'action' ] );
      }
    }
    else {
      if ( is_array( $render ) ) {
        $options = $render;
      }
      $data         = array();
      $variableData = array();
      # Layout
      if (
            isset( $options[ 'layout' ] ) and
        is_string( $options[ 'layout' ] )
      ) {
        $layout = $options[ 'layout' ];
      }
      else {
        $layout = $this->getCurrentLayout();
      }
      # Data
      if (
           isset( $options[ 'data' ] ) and
        is_array( $options[ 'data' ] )
      ) {
        $data = $options[ 'data' ];
      }
      # Variable data
      if (
           isset( $options[ 'variableData' ] ) and
        is_array( $options[ 'variableData' ] )
      ) {
        $variableData = $options[ 'variableData' ];
      }
      # Render
      if (
            isset( $render ) and
        is_string( $render ) and
           !empty( $render )
      ) {
        if ( !preg_match( '/^\//', $render ) ) {
          $render = \Polar\Core\Request::$params[ 'controller' ] . '/' . $render;
        }
      }
      if (
        !is_string( $render ) or
             empty( $render )
      ) {
        $viewPath = lcfirst( \Polar\Core\Request::$params[ 'controller' ] ) . '/' . \Polar\Core\Request::$params[ 'action' ];
      }
      else {
        $viewPath = $render;
      }
      $layoutPath      = \Polar\Polar::$paths[ 'viewLayout' ] . $layout   . '.php';
      $viewPath        = \Polar\Polar::$paths[ 'views'      ] . $viewPath . '.php';
      $viewTemplateObj = new \Polar\Core\View( $viewPath, null, $data, $variableData );
      $yield           = function() use( &$viewTemplateObj ) {
        $viewTemplateObj->render();
      };
      $layoutTemplateObj = new \Polar\Core\View( $layoutPath, $yield, $data, $variableData );
      $layoutTemplateObj->render();
    }
  }

  private function getCurrentLayout() {
    $layout = self::_extractCurrentActionCallbacks( $this->layout );
    if ( count( $layout ) > 0 ) {
      $layout = count( $layout ) > 1 ? $layout[ count( $layout ) - 1 ] : $layout[ 0 ];
    }
    else {
      $layout = null;
    }
    if (
           is_null( $layout ) or
      !file_exists( \Polar\Polar::$paths[ 'viewLayout' ] . $layout . '.php' )
    ) {
      $layout = $this->defaultLayout;
    }
    return $layout;
  }

  # Callbacks

  private static function _extractCurrentActionCallbacks( $callbacks ) {
    $results = array();
    if ( self::_isActionSpecificCallback( $callbacks ) ) {
      self::__matchCurrentActionCallback( $callbacks, $results );
    } else if ( is_array( $callbacks ) ) {
      foreach( $callbacks as $callback ) {
        if ( self::_isActionSpecificCallback( $callback ) ) {
          self::__matchCurrentActionCallback( $callback, $results );
        }
      }
    }
    return $results;
  }

  private static function _isActionSpecificCallback( $callback ) {
    if ( is_string( $callback ) ) {
      return true;
    }
    else if ( is_array( $callback ) ) {
      if ( count( $callback) != 2 ) {
        return false;
      }
      if (
        is_string( $callback[ 0 ] ) and
        is_string( $callback[ 1 ] ) and
        (
          preg_match( '/^exclude\:/', $callback[ 1 ] ) or
          preg_match( '/^include\:/', $callback[ 1 ] )
        )
      ) {
        return true;
      }
    }
    return false;
  }

  private static function _matchCurrentAction( $actions ) {
    $actions     = strtolower( $actions );
    $actions     = preg_replace( '/[\s]*/', '', $actions );
    $isInclusive = true;
    if (
      preg_match( '/^include\:/', $actions )
    ) {
      $actions = preg_replace( '/^include\:/', '', $actions );
    } else if ( preg_match( '/^exclude\:/', $actions ) ) {
      $actions     = preg_replace( '/^exclude\:/', '', $actions );
      $isInclusive = false;
    }
    $actions = explode( ',', $actions );
    foreach( $actions as $action ) {
      if ( \Polar\Core\Request::$params[ 'action' ] === $action ) {
        if ( $isInclusive ) {
          return true;
        }
        return false;
      }
    }
    return true;
  }

  private static function __matchCurrentActionCallback( $callback, &$results ) {
    if ( is_string( $callback ) ) {
      $results[] = $callback;
    } else if ( is_array( $callback ) ) {
      $actions = $callback[ 1 ];
      if ( self::_matchCurrentAction( $actions ) ) {
        $results[] = $callback[ 0 ];
      }
    }
  }

  public static function _invokeCallbacks() {
    $callbacks     = func_get_args();
    $controllerObj = $callbacks[ 0 ];
    array_shift( $callbacks );
    foreach( $callbacks as $callback ) {
      if ( !isset( $controllerObj->{$callback} ) ) {
        return false;
      }
      $methods = self::_extractCurrentActionCallbacks( $controllerObj->{$callback} );
      foreach( $methods as $method ) {
        if ( method_exists( $controllerObj, $method ) ) {
          $controllerObj->{$method}();
        }
      }
    }
  }

  # Controller Action Call

  public static function call( $controller = null, $action = null ) {
    if (
      !is_null( $action     ) and
      !is_null( $controller )
    ) {
      \Polar\Core\Request::$params[ 'action'     ] = $action;
      \Polar\Core\Request::$params[ 'controller' ] = $controller;
    } else {
      $params = \Polar\Core\Request::$params;
      if (
        isset( $params[ 'action'     ] ) and
        isset( $params[ 'controller' ] )
      ) {
        $action     = $params[ 'action'     ];
        $controller = $params[ 'controller' ];
      }
    }
    $action             = $action;
    $controller         = ucfirst( $controller ) . 'Controller';
    $Controller         = new $controller;
    $Controller->params = \Polar\Core\Request::$params;
    \Polar\Core\Helper::initialize();
    self::_invokeCallbacks( $Controller, 'beforeAction' );
    $Controller->{$action}();
    self::_invokeCallbacks( $Controller, 'afterAction' );
    return $Controller;
  }

  public static function controllerActionCallToArray( $call ) {
    $action     = preg_replace( '/^[a-zA-Z][a-zA-Z0-9]+@/', '', $call );
    $controller = preg_replace( '/@[a-zA-Z][a-zA-Z0-9]+$/', '', $call );
    return array(
      'action'     => $action,
      'controller' => $controller
    );
  }

  public static function isControllerActionCall( $call ) {
    return preg_match( '/^[a-zA-Z][a-zA-Z0-9]+\@[a-zA-Z][a-zA-Z0-9]+$/', $call ) ? true : false;
  }

  public static function isControllerActionCallable( $controller, $action ) {
    $regEx = '/^[a-zA-Z][a-zA-Z0-9]+$/';
    if (
      !preg_match( $regEx, $action     ) or
      !preg_match( $regEx, $controller )
    ) {
      return false;
    }
    $actionMethodName    = $action;
    $controllerClassName = ucfirst( $controller ) . 'Controller';
    if ( !class_exists( $controllerClassName ) ) {
      return false;
    }
    $controllerObj = new $controllerClassName();
    if (
           method_exists( $controllerObj, $actionMethodName ) and
      is_callable( array( $controllerObj, $actionMethodName ) )
    ) {
      return $controllerObj;
    }
    return false;
  }

}
