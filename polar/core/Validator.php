<?php

namespace Polar\Core;

class Validator extends \Polar\ApplicationValidator {

  public $data          = array();
  public $errorCount    = 0;
  public $isValid       = true;
  public $errorMessages = array();
  public $validations   = array();

  public function __construct( $data = array() ) {
    $this->constructValidations();
    $this->setData( $data );
    return $this;
  }

  public function addErrorMessage( $column, $message ) {
    if ( isset( $this->errorMessages[ $column ] ) ) {
      if ( is_string( $message ) ) {
        $this->errorMessages[ $column ][] = $message;
      } else if ( is_array( $message ) ) {
        $this->errorMessages = array_merge( $this->errorMessages[ $column ], $message );
      }
    }
    else {
      if ( is_string( $message ) ) {
        $this->errorMessages[ $column ] = array( $message );
      } else if ( is_array( $message ) ) {
        $this->errorMessages[ $column ] = $message;
      }      
    }
    return $this;
  }

  public function containsError() {
    return $errorCount > 0 ? false : true;
  }

  private function incrementErrorCount() {
    $this->errorCount++;
    return $this;
  }

  public function reset() {
    $this->data          = array();
    $this->errorMessages = array();
    $this->errorCount    = 0;
    return $this;
  }

  public function setData( $data ) {
    if ( is_array( $data ) ) {
      foreach( $data as $key => $value ) {
        $this->data[ $key ] = $value;
      }
    }
    return $this;
  }

  # Validation Setup

  public function addValidation( $key, $validation ) {
    $this->validations[ $key ] = $validation;
    return $this;
  }

  public function applyValidation( $validation ) {
    foreach( $validation as $key => $value ) {
      if (
        is_integer( $key ) and
         is_string( $value )
      ) {
        if ( $value === 'stopIfInvalid' ) {
          if ( !$this->isValid ) {
            break;
          }
        } else {
          $this->callbackHandler( $value );
        }
      } else if ( is_string( $key ) ) {
        $this->validateColumn( $key, $value );
      }
    }
    return $this->isValid;
  }

  public function constructValidations() {
    foreach( $this->validations as $key => $value ) {
      if ( preg_match( '/^load\:/', $value ) ) {
        $path = preg_replace( array( '/^load\:/', '/[\s]*/' ), '', $value );
        $this->validations[ $key ] = $this->loadValidation( $path );
      }
    }
    return $this;
  }

  public function loadValidation( $path ) {
    return ( include \Polar\Polar::$paths[ 'validations' ] . $path . '.php' );
  }

  public function validate( $validationName = 'default' ) {
    foreach ( $this->validations as $key => $value ) {
      $validationNames = preg_replace( '/[\s]*/', '', $key );
      $validationNames = explode( ',', $validationNames );
      if ( in_array( $validationName, $validationNames ) ) {
        $this->applyValidation( $value );
      }
    }
    return $this->isValid;
  }

  # Handlers

  private function callbackHandler( $fn, $param = null ) {
    if ( is_callable( $fn ) ) {
      $fn( $this );
    } else if (
          is_string( $fn ) and
         preg_match( '/[a-z][a-zA-Z0-9]*/', $fn ) and
      method_exists( $this, $fn )
    ) {
      $this->$fn( $param );
    }
    return $this;
  }

  private function handleErrorResult( $column, $value ) {
    $result = array( 'break' => false );
    $this->incrementErrorCount();
    if ( isset( $value[ 'error' ] ) ) {
      $error = $value[ 'error' ];
      # Break
      if (
        isset( $error[ 'break' ] ) and
               $error[ 'break' ] === true
      ) {
        $result[ 'break' ] = true;
      }
      # Callback
      if ( isset( $error[ 'callback' ] ) ) {
        $this->callbackHandler( $error[ 'callback' ] );
      }
      # Ignore validity
      if (
        !isset( $error[ 'ignoreValidity' ] ) or
               !$error[ 'ignoreValidity' ]
      ) {
        $this->isValid = false;
      }
      # Error message
      if (
        isset( $error[ 'message' ] ) and
        (
          is_string( $error[ 'message' ] ) or
           is_array( $error[ 'message' ] )
        )
      ) {
        $this->addErrorMessage( $column, $error[ 'message' ] );
      }
    }
    return $result;
  }

  private function handleSuccessResult( $column, $value ) {
    $result = array( 'break' => false );
    if ( isset( $value[ 'success' ] ) ) {
      $success = $value[ 'success' ];
      # Break
      if (
        isset( $success[ 'break' ] ) and
        $success[ 'break' ] === true
      ) {
        $result[ 'break' ] = true;
      }
      # Callback
      if ( isset( $success[ 'callback' ] ) ) {
        $this->callbackHandler( $success[ 'callback' ] );
      }
    }
    return $result;
  }

  # Validators

  private function validateColumn( $column, $validation ) {
    # loop through column validation
    foreach( $validation as $key => $value ) {
      if (
        is_integer( $key ) and
         is_string( $value )
      ) {
        if (
          $value === 'stopIfInvalid' and
          !$this->isValid
        ) {
          break;
        } else {
          $this->callbackHandler( $this->data[ $column ] );
        }
      }
      else if ( is_string( $key ) ) {
        $validationMethodName = 'validate' . ucfirst( $key );
        if ( !method_exists( $this, $validationMethodName ) ) {
          # skip this column
          continue;
        }
        # handle validation result
        $result = $this->$validationMethodName( $this->data[ $column ], $value );
        if ( $result ) {
          $result = $this->handleSuccessResult( $column, $key, $value );
        } else {
          $result = $this->handleErrorResult( $column, $value );
        }
        # callback
        if ( isset( $value[ 'callback' ] ) ) {
          $this->callbackHandler( $value[ 'callback' ] );
        }
        # break
        if ( $result[ 'break' ] === true ) {
          break;
        }
      }
    }
  }

  public function validateEmail( $value ) {
    return ( filter_var( $value, FILTER_VALIDATE_EMAIL ) ) ? true : false;
  }

  public function validateFormat( $value, $validation ) {
    return ( boolean ) preg_match( '/' . $validation[ 'pattern' ] . '/', $value );
  }

  public function validateLength( $string, $validation ) {
    if (
       isset( $validation[ 'min' ] ) and
      is_int( $validation[ 'min' ] ) and
      strlen( $string ) < $validation[ 'min' ]
    ) {
      return false;
    }
    if (
       isset( $validation[ 'max' ] ) and
      is_int( $validation[ 'max' ] ) and
      strlen( $string ) > $validation[ 'max' ]
    ) {
      return false;
    }
    return true;
  }

  public function validatePresence( &$value ) {
    if (
      !isset( $value ) or
       empty( $value ) or
      preg_match( '/^[\s]*$/', $value )
    ) {
      return false;
    }
    return true;
  }

}