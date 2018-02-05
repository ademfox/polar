<?php

# \Polar\Core\Filter V1.0

namespace Polar\Core;

class Filter extends \Polar\ApplicationFilter {

  /*
    $filters = array(
      'filterName' => array(
        'filterMethod',
        'filterMethod' => 'include:Column1,Column2',
        'filterMethod' => 'Column1,Column2',
        'filterMethod' => 'exclude:Column2'
      )
    );
  */

  public $data             = array();
  public $filters          = array();
  public $ignorePrimaryKey = true;
  public $primaryKeyColumn = 'ID';

  public function __construct( $data = array() ) {
    $this->data = $data;
    return $this;
  }

  public function apply( $filterName ) {
    $hasFilter = false;
    $context   = $this;
    foreach ( $this->filters as $key => $value ) {
      $filterNames = preg_replace( '/[\s]*/', '', $key );
      $filterNames = explode( ',', $filterNames );
      if ( in_array( $filterName, $filterNames ) ) {
        $hasFilter = true;
        $context->applyFilters( $value );
      }
    }
    return ( $hasFilter ) ? $this->data : false;
  }

  public function applyFilters( $filters ) {
    foreach ( $filters as $key => $value ) {
      if ( is_integer( $key ) ) {
        if ( $this->_isFilterMethod( $value ) ) {
          $this->applyFilterMethodAll( $value );
        }
      }
      else if (
                     is_string( $key ) and
                     is_string( $value ) and
        $this->_isFilterMethod( $key )
      ) {
        $value = preg_replace( '/[^a-zA-Z0-9\-\_\:\,]*/', '', $value );
        if (
           preg_match( '/^include\:/', $value ) or
          !preg_match( '/^exclude\:/', $value )
        ) {
          $value   = preg_replace( '/^include\:/', '', $value );
          $include = explode( ',', $value );
          $this->applyFilterMethodIncluding( $key, $include );
        }
        else if ( preg_match( '/^exclude\:/', $value ) ) {
          $value   = preg_replace( '/^exclude\:/', '', $value );
          $exclude = explode( ',', $value );
          $this->applyFilterMethodExcluding( $key, $exclude );
        }
      }
    }
    return $this->data;
  }

  private function applyFilterMethodAll( $filterMethod ) {
    $filterMethod = 'filter' . ucfirst( $filterMethod );
    foreach ( $this->data as $column => $value ) {
      if (
        $this->ignorePrimaryKey and
        $this->primaryKeyColumn === $column
      ) {
        continue;
      }
      $this->data[ $column ] = $this->$filterMethod( $value );
    }
    return $this;
  }

  private function applyFilterMethodExcluding( $filterMethod, $exclude ) {
    $filterMethod = 'filter' . ucfirst( $filterMethod );
    if ( is_string( $exclude ) ) {
      $exclude = array( $exclude );
    }
    foreach ( $this->data as $column => $value ) {
      if (
        $this->ignorePrimaryKey and
        $column === $this->primaryKeyColumn
      ) {
        continue;
      }
      if ( !in_array( $column, $exclude ) ) {
        $this->data[ $column ] = $this->$filterMethod( $this->data[ $column ] );
      }
    }
    return $this;
  }

  private function applyFilterMethodIncluding( $filterMethod, $include ) {
    $filterMethod = 'filter' . ucfirst( $filterMethod );
    if ( is_string( $include ) ) {
      $include = array( $include );
    }
    foreach ( $include as $column ) {
      if (
        $this->ignorePrimaryKey and
        $column === $this->primaryKeyColumn
      ) {
        continue;
      }
      if ( isset( $this->data[ $column ] ) ) {
        $this->data[ $column ] = $this->$filterMethod( $this->data[ $column ] );
      }
    }
    return $this;
  }

  public function filterSanitize( $value ) {
    return filter_var( $value, FILTER_SANITIZE_STRING );
  }

  private function _isFilterMethod( $string ) {
    if (
          is_string( $string                          ) and
         preg_match( '/^[a-z][a-zA-Z0-9]*$/', $string ) and
      method_exists( $this, 'filter' . ucfirst( $string ) )
    ) {
      return true;
    }
    return false;
  }

  public function reset() {
    $this->data = array();
    return $this;
  }

  public function setData( $data ) {
    foreach ( $data as $key => $value ) {
      $this->data[ $key ] = $value;
    }
    return $this;
  }

}
