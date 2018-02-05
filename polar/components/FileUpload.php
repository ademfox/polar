<?php

# Polar\Component\FileUpload V1.0

namespace Polar\Component;

class FileUpload {

  public $resource  = '';
  public $index     = '';
  public $file      = array();
  public $errors    = array();
  public $validates = array();
  public $name      = '';

  public function __construct( $resource ) {
    $this->resource = $resource;
  }

  public static function render( $filePath, $contentType ) {
    $file = fopen( $filePath, 'rb' );
    header( 'Content-Type: '   . $contentType );
    header( 'Content-Length: ' . filesize( $filePath ) );
    fpassthru( $file );
  }

  # Check to see if file has been uploaded.
  public function isUploaded( $index ) {
    if (
       isset( $_FILES[ $this->resource ][ 'tmp_name' ][ $index ] ) and
      !empty( $_FILES[ $this->resource ][ 'tmp_name' ][ $index ] )
    ) {
      $this->file = $_FILES[ $this->resource ];
      return true;
    }
    return false;
  }

  # 1000bytes = 1kilobytes
  # 1000000bytes = 1megabytes
  private $validationFilters = array( 'size', 'type', 'match' );

  public function validateSize( $index, $size ) {
    if ( is_array( $size ) ) {
      if ( isset( $size[ 'min' ] ) and is_numeric( $size[ 'min' ] ) ) {
        if ( $this->file[ 'size' ][ $index ] < ( int ) $size[ 'min' ] ) {
          return false;
        }
      }
      if ( isset( $size[ 'max' ] ) and is_numeric( $size[ 'max' ] ) ) {
        if ( $this->file[ 'size' ][ $index ] > ( int ) $size[ 'max' ] ) {
          return false;
        }
      }
      return true;
    }
    return false;
  }

  public function validateType( $index, $types ) {
    if ( !is_array( $types ) ) {
      $types = array( $types );
    }
    foreach( $types as $type ) {
      if ( $this->file[ 'type' ][ $index ] == $type ) {
        return true;
      }
    }
    return false;
  }

  public function validateMatch( $index, $match ) {
    if ( is_string( $match ) ) {
      return preg_match( '/' . $match . '/', $this->file[ 'name' ][ $index ] ) ? true : false;
    }
  }

  public function validate( $index, $schema = null ) {
    $context   = $this;
    $schema    = is_array( $schema ) ? $schema : $this->validates;
    $_validate = function( $index, $v ) use( $context ) {
      foreach( $context->validationFilters as $filter ) {
        if ( isset( $v[ $filter ] ) ) {
          if ( !$context->{ 'validate' . ucfirst( $filter ) }( $index, $v[ $filter ] ) ) {
            $context->errors[ $index ][] = $v[ 'message' ];
            return false;
          }
        }
      }
      return true;
    };
    if ( is_array( $schema ) ) {
      if ( isset( $schema[ 0 ] ) ) {
        foreach( $schema as $validation ) {
          if ( !$_validate( $index, $validation ) ) {
            return false;
          }
        }
      } else {
        if ( !$_validate( $index, $schema ) ) {
          return false;
        }
      }
      return true;
    }
    return false;
  }

  public function move( $index, $to ) {
    $name      = $this->file[ 'name' ][ $index ];
    $extension = pathinfo( $name, PATHINFO_EXTENSION );
    $path      = $to . '.' . $extension;
    move_uploaded_file( $this->file[ 'tmp_name' ][ $index ], FILES_PATH . $path );
  }

}
