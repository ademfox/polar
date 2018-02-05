<?php

# \Polar\Core\Model V1.0

namespace Polar\Core;

class Model extends \Polar\ApplicationModel {

  public $data          = array();
  public $errorMessages = array();

  public function __construct() {
    return $this;
  }

  public function addErrorMessage( $column, $message ) {
    if ( isset( $this->errorMessages[ $column ] ) ) {
      if ( is_string( $message ) ) {
        $this->errorMessages[ $column ][] = $message;
      }
      else if ( is_array( $message ) ) {
        $this->errorMessages = array_merge( $this->errorMessages[ $column ], $message );
      }
    }
    else {
      if ( is_string( $message ) ) {
        $this->errorMessages[ $column ][] = array( $message );
      }
      else if ( is_array( $message ) ) {
        $this->errorMessages[ $column ] = $message;
      }      
    }
    return $this;
  }

  public function setData( $data ) {
    foreach( $data as $key => $value ) {
      $this->data[ $key ] = $value;
    }
    return $this->data;
  }

}
