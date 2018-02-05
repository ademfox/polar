<?php

# Router V1.0
# Require Controller, Polar, Request, Response

namespace Polar\Core;

class Router {

	private static $rootRoute;
	private static $routeStack = array();
	private static $matchFound = false;
	private static $noMatchAction;
	private static $params = array();

	public static function group( $prefix, $fn, $rules = array() ) {
		$routes = array();
		$route  = function( $verb, $pattern, $action, $rules = array() ) use ( &$routes, &$prefix ) {
			$routes[] = array( $verb, $prefix.$pattern, $action, $rules );
		};
		$fn( $route );
		foreach ( $routes as $route ) {
			self::match( $route[ 0 ], $route[ 1 ], $route[ 2 ], array_merge( $rules, $route[ 3 ] ) );
		}
	}

	public static function match( $verbs, $pattern, $action, $rules = array() ) {
		self::addRoute( $verbs, $pattern, $action, $rules );
	}

	public static function noMatch( $action ) {
		self::$noMatchAction = $action;
	}

	public static function root( $action ) {
		self::$rootRoute = array(
			'action'  => $action,
			'pattern' => '/',
			'verb'    => 'GET'
		);
	}

	public static function route() {
		self::_matchRoute( self::$rootRoute );
		foreach( self::$routeStack as $route ) {
			if ( self::_matchRoute( $route ) ) {
				break;
			}
		}
		if ( !self::$matchFound ) {
			if ( !self::dispatch( self::$noMatchAction ) ) {
				http_response_code( 500 );
			}
		}
	}

  # Route Matching.

	private static $_regexMatchVariable = '\{[a-zA-Z]([\w-]+)?\}';

	private static function addRoute( $verb, $pattern, $action, $rules = array() ) {
		self::$routeStack[] = array(
			'action'  => $action,
			'pattern' => $pattern,
			'rules'   => $rules,
			'verb'    => $verb
		);
	}

	private static function _extractVariableName( $variable ) {
		return str_replace( array( '{', '}', '?'), '', $variable );
	}

	private static function _handleVariable( $patternSegment, $pathSegment, &$path, $route ) {
		$variableName = self::_extractVariableName( $patternSegment );
		if (
		   	 isset( $route[ 'rules' ] ) and
			is_array( $route[ 'rules' ] ) and
			   isset( $route[ 'rules' ][ $variableName ] )
		) {
			$regex = '/' . $route[ 'rules' ][ $variableName ] . '/';
			if ( !preg_match( $regex, $pathSegment ) ) {
				return false;
			}
		}
		self::$params[ $variableName ] = $pathSegment;
		return true;
	}

	private static function _handleWildcards( &$path, $route ) {
		if (
			   isset( $route[ 'rules' ] ) and
			is_array( $route[ 'rules' ] ) and
			   isset( $route[ 'rules' ][ '*' ] )
		) {
			$regex = '/' . $route[ 'rules' ][ '*' ] . '/';
			if ( !preg_match( $regex, $path ) ) {
				return false;
			}
		}
		self::$params[ '*' ] = $path;
		return true;
	}

	private static function _isAVariable( $string ) {
		return preg_match( '/^' . self::$_regexMatchVariable . '$/', $string ) ? true : false;
	}

	private static function _matchPattern( $pattern, $route ) {
		$path    = \Polar\Core\Request::$path;
		$path    = preg_replace( '/^[\/]+/', '', $path );
		$pattern = preg_replace( '/^[\/]+/', '', $pattern );
		if ( self::_patternContainsVariable( $pattern ) ) {
			$pattern      = explode( '/', $pattern );
			$patternCount = count( $pattern );
			for ( $i = 0; $i < $patternCount; $i++ ) {
				preg_match( '/^[^\/]*/', $path, $match );
				$pathSegment = $match[ 0 ];
				if ( self::_isAVariable( $pattern[$i] ) ) {
					if ( !self::_handleVariable( $pattern[ $i ], $pathSegment, $path, $route ) ) {
						return false;
					}
				}
				else if ( $pattern[ $i ] === '*' ) {
					self::_handleWildcards( $path, $route );
					if ( !self::_handleWildcards( $path, $route ) ) {
						return false;
					}
					break;
				}
				else if ( $pathSegment !== $pattern[ $i ] ) {
					return false;
				}
				self::_shiftPath( $pathSegment, $path );
			}
      if ( $path != '' ) {
        return false;
      }
		}
		else {
			$regex = '/^\/?' . preg_quote( $pattern, '/' ) . '[\/]*$/';
			if ( !preg_match( $regex, $path ) ) {
				return false;
			}
    }
		return true;
	}

	private static function _matchRoute( $route ) {
		if (
			!self::$matchFound and
			self::_matchVerb( $route[ 'verb' ] )
		) {
			self::$params = array();
			$patterns     = $route[ 'pattern' ];
			if ( !is_array( $patterns ) ) {
				$patterns = array( $patterns );
			}
			$match = false;
			foreach( $patterns as $pattern ) {
				if ( self::_matchPattern( $pattern, $route ) ) {
					$match = true;
				}
			}
			if (
				$match == true and
				self::dispatch( $route[ 'action' ], self::$params )
			) {
				self::$matchFound = true;
				return true;
			}
		}
		return false;
	}

	private static function _matchVerb( $verb ) {
		if ( $verb === 'ANY' ) {
			return true;
		}
		else if ( is_string( $verb ) ) {
			return strtoupper( $verb ) == \Polar\Core\Request::$requestMethod;
		}
		else if ( is_array( $verb ) ) {
			foreach( $verb as $_verb ) {
				if ( strtoupper( $_verb ) == \Polar\Core\Request::$requestMethod ) {
					return true;
				}
			}
		}
		return false;
	}

	private static function _patternContainsVariable( $pattern ) {
		if ( preg_match( '/' . self::$_regexMatchVariable . '/', $pattern ) ) {
			return true;
		}
		if ( preg_match( '/[\*]/', $pattern ) ) {
			return true;
		}
		return false;
	}

	private static function _shiftPath( $segment, &$path ) {
		$regex = '/^' . preg_quote( $segment, '/' ) . '/';
		$path  = preg_replace( $regex, '', $path );
		$path  = preg_replace( '/^[\/]*/', '', $path );
	}

	# Controller.

	private static function callControllerAction( $callString ) {
		if ( \Polar\Core\Controller::isControllerActionCall( $callString ) ) {
			$call = \Polar\Core\Controller::ControllerActionCallToArray( $callString );
			if ( \Polar\Core\Controller::isControllerActionCallable( $call[ 'controller' ], $call[ 'action' ] ) ) {
				\Polar\Core\Request::$params = array_merge( \Polar\Core\Request::$params, $call, self::$params );
				\Polar\Core\Controller::call();
				return true;
			}
		}
		return false;
	}

	private static function dispatch( $action ) {
		if ( is_callable( $action ) ) {
			\Polar\Core\Request::$params = array_merge( \Polar\Core\Request::$params, self::$params );
			call_user_func_array( $action, self::$params );
			return true;
		}
		else if ( preg_match( '/^(http|https):\/\//', $action ) ) {
			\Polar\Core\Response::redirect( $action );
			return true;
		}
		else {
			return self::callControllerAction( $action );
		}
	}

}
