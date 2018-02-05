<?php

# \Polar\Core\View V1.0
# Require Polar

namespace Polar\Core;

class View {

  public $data = array();
  public $directory;
  public $helper;
  public $path;
  public $variables;
  public $yield;

  public function __construct( $view, $yield = null, $data = array(), $variables = array() ) {
    $this->path      = $view;
    $this->directory = preg_replace( '/[\w-]+\.php$/', '', $this->path );
    $this->yield     = is_callable( $yield ) ? $yield : null;
    $this->data      = $data;
    $this->variables = $variables;
  }

  public function clearData() {
    $this->data = array();
    return $this;
  }

  public function collection( $partial, $collection, $as, $data = array(), $variables = array() ) {
    if (
         isset( $collection ) and
      is_array( $collection )
    ) {
      foreach( $collection as $value ) {
        $variables[ $as ] = $value;
        if ( !$this->partial( $partial, $data, $variables ) ) {
          return false;
        }
      }
      return true;
    }
    return false;
  }

  public function extendData( $data ) {
    $this->data = array_merge( $this->data, $data );
    return $this;
  }

  public function partial( $partial, $data = array(), $variables = array()) {
    $direcory  = '';
    $name      = '';
    $directory = preg_replace( '/[\w-]+$/', '', $partial );
    $name      = preg_replace( '/^\/?([\w-]+\/)*/', '', $partial );
    $name      = preg_replace( '/^_/', '', $name );
    if ( preg_match( '/^\/.*$/', $partial ) ) {
      $directory = \Polar\Polar::$paths[ 'view' ] . preg_replace( '/^[\/]+/', '', $directory );
      $path      = $directory . '_' . $name . '.php';
    }
    else {
      $path = $this->directory . $directory . '_' . $name . '.php';
    }
    if ( file_exists( $path ) ) {
      $view = new self( $path, null, $this->data, $variables );
      $view->extendData( $data );
      $view->render();
      return true;
    }
    return false;
  }

  public function render() {
    foreach ( $this->variables as $key => $value ) {
      $$key = $value;
    }
    $yield = $this->yield;
    include $this->path;
    return $this;
  }

}