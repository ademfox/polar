<?php

# Polar\Component\Form V1.0

namespace Polar\Component;

class Form {

  public $action        = '';
  public $data          = array();
  public $dataName      = '';
  public $containsFile  = false;
  public $errorMessages = array();
  # Maximum file size,
  # in bytes (Default at 5MB, 5 * 1024 * 1024).
  public $maxFileSize    = 5242880;
  public $method         = 'POST';
  public $isCustomMethod = false;
  public $passedData     = array();

  # action  : URL
  # method  : [GET, POST, PUT, DELETE]
  # dataname: string

  public function __construct( $options = array() ) {
    $this->action   = $options[ 'action' ]   ?? '';
    $this->method   = $options[ 'method' ]   ?? 'POST';
    $this->dataName = $options[ 'dataName' ] ?? \Polar\Core\Request::$params[ 'controller' ];
    return $this;
  }

  public function setData( $data ) {
    foreach ( $data as $key => $value ) {
      $this->data[ $key ] = $value;
    }
    return $this;
  }

  public function isSubmitted() {
    if (
      !isset( \Polar\Core\Request::$params[ $this->dataName ] ) or
      !isset( \Polar\Core\Request::$params[ $this->dataName ][ 'isSubmitted' ] ) or
              \Polar\Core\Request::$params[ $this->dataName ][ 'isSubmitted' ] != 'true'
    ) {
      return false;
    }
    if ( isset( \Polar\Core\Request::$params[ 'authenticityToken' ] ) ) {
      $token = \Polar\Core\Request::$params[ 'authenticityToken' ];
      if ( \Polar\Component\CSRF::verifyToken( 'authenticityToken' . ucfirst( $this->dataName ), $token ) ) {
        $this->data = \Polar\Core\Request::$params[ $this->dataName ] ?? array();
        return true;
      }
    }
    return false;
  }

  public function reset() {
    $this->data          = array();
    $this->errorMessages = array();
    return $this;
  }

  # Tags

  public function formTagStart( $attributes = array(), $options = array() ) {
    if ( $this->containsFile === true ) {
      $attributes[ 'enctype' ] = 'multipart/form-data';
    }
    $attributes = \Polar\Component\HTML::arrayToAttributes( $attributes );
    $action     = \Polar\Polar::$config[ 'baseURL' ] . $this->action;
    $method     = strtoupper( $this->method );
    if ( in_array( $method, [ 'PUT', 'DELETE' ] ) ) {
      $this->isCustomMethod = true;
      $_method = 'POST';
    }
    else {
      $_method = $method;
    }
    echo '<form accept-charset="UTF-8" action="' . $action . '" method="' . $_method . '" ' . $attributes . '>' . PHP_EOL;
    if (
      $this->containsFile === true and
      is_int( $this->maxFileSize )
    ) {
      echo '<input name="MAX_FILE_SIZE" type="hidden" value="' . $this->maxFileSize . '">';
    }
    $authenticityToken = \Polar\Component\CSRF::generateToken( 'authenticityToken' . ucfirst( $this->dataName ) );
    echo '<input name="authenticityToken" type="hidden" value="' . $authenticityToken . '">' . PHP_EOL;
    echo '<input name="' . $this->dataName . '[isSubmitted]" type="hidden" value="true">' . PHP_EOL;
    $method = strtoupper( $this->method );
    if ( $this->isCustomMethod ) {
      echo '<input name="___polar-request-method" type="hidden" value="' . $method . '">' . PHP_EOL;
    }
    return $this;
  }

  public function formTagEnd() {
    echo '</form>' . PHP_EOL;
    return $this;
  }

  public function checkboxTag( $item, $checked = false, $attributes = array() ) {
    $dataValue = $this->data[ $item ] ?? null;
    if (
      isset( $this->errorMessages[ $item ] ) and
      isset( \Polar\Core\Request::$params[ $this->dataName ][ $item ] )
    ) {
      $dataValue = \Polar\Core\Request::$params[ $this->dataName ][ $item ];
    }
    $attributeValue = $attributes[ 'value' ] ?? 1;
    if ( is_array( $dataValue ) ) {
      foreach( $dataValue as $value ) {
        if ( $value == $attributeValue ) {
          $checked = true;
        }
      }
    }
    else {
      if ( $dataValue == $attributeValue ) {
        $checked = true;
      }
    }
    $checked = $checked ? 'checked' : '';
    $name    = $this->dataName . '[' . $item . ']';
    if ( isset( $attributes ) ) {
      $attributes = array( 'value' => 1 );
    }
    $attributes = \Polar\Component\HTML::arrayToAttributes( $attributes );
    echo '<input name="' . $name . '" type="checkbox" ' . $attributes . ' ' . $checked . '>' . PHP_EOL;
    return $this;
  }

  public function fileTag( $item, $attributes = array() ) {
    $attributes[ 'type' ] = 'file';
    $this->inputTag( $item, $attributes );
    return $this;
  }

  public function inputTag( $item, $attributes = array(), $tagIsEmpty = false ) {
    $value = $this->data[ $item ] ?? '';
    if (
      isset( $this->errorMessages[ $item ] ) and
      isset( \Polar\Core\Request::$params[ $this->dataName ][ $item ] )
    ) {
      $value = \Polar\Core\Request::$params[ $this->dataName ][ $item ];
    }
    $attributes[ 'value' ] = $attributes[ 'value' ] ?? $value;
    if ( !isset( $this->errorMessages[ $item ] ) and $tagIsEmpty ) {
      $attributes[ 'value' ] = '';
    }
    $attributes[ 'type' ] = $attributes[ 'type' ] ?? 'text';
    $name                 = $this->dataName . '[' . $item . ']';
    $attributes           = \Polar\Component\HTML::arrayToAttributes( $attributes );
    echo '<input name="' . $name . '" ' . $attributes . '>' . PHP_EOL;
    return $this;
  }

  public function labelTag( $labelString, $attributes = array() ) {
    $attributes = \Polar\Component\HTML::arrayToAttributes( $attributes );
    echo '<label ' . $attributes . '>' . $labelString . '</label>' . PHP_EOL;
    return $this;
  }

  public function radioTag( $item, $value, $checked = false, $attributes = array() ) {
    $dataValue = $this->data[ $item ] ?? null;
    if (
      isset( $this->errorMessages[ $item ] ) and
      isset( \Polar\Core\Request::$params[ $this->dataName ][ $item ] )
    ) {
      $dataValue = \Polar\Core\Request::$params[ $this->dataName ][ $item ];
    }
    $attributeValue = $attributes[ 'value' ] ?? 1;
    if ( is_array( $dataValue ) ) {
      foreach( $dataValue as $value ) {
        if ( $value === $attributeValue ) {
          $checked = true;
        }
      }
    }
    else {
      if ( $dataValue === $attributeValue ) {
        $checked = true;
      }
    }
    $checked    = $checked ? 'checked' : '';
    $name       = $this->dataName . '[' . $item . ']';
    $attributes = \Polar\Component\HTML::arrayToAttributes( $attributes );
    echo '<input name="' . $name . '" type="radio" ' . $attributes . ' ' . $checked . '>' . PHP_EOL;
    return $this;
  }

  # Options:
  # [value, string]
  public function selectTag( $item, $options = array(), $selected  = false, $attributes = array() ) {
    $selectedValue = $this->data[ $item ] ?? '';
    if (
      isset( $this->errorMessages[ $item ] ) and
      isset( \Polar\Core\Request::$params[ $this->dataName ][ $item ] )
    ) {
      $selectedValue = \Polar\Core\Request::$params[ $this->dataName ][ $item ];
    }
    $selected   = $selected ?? $selectedValue;
    $name       = $this->dataName . '[' . $item . ']';
    $attributes = \Polar\Component\HTML::arrayToAttributes( $attributes );
    echo '<select name="' . $name . '" ' . $attributes . '>' . PHP_EOL;
    foreach ( $options as $option ) {
      if ( !is_array( $option ) ) {
        $string = $option;
        $value  = $option;
      }
      else {
        $string = $option[ 1 ];
        $value  = $option[ 0 ];
      }
      $active = ( $value == $selected ) ? 'selected' : '';
      echo '<option value="' . $value . '" ' . $active . '>' . $string . '</option>' . PHP_EOL;
    }
    echo '</select>' . PHP_EOL;
    return $this;
  }

  public function submitTag( $attributes = array() ) {
    $value                 = $this->data[ 'submit' ] ?? 'submit';
    $attributes[ 'value' ] = $attributes[ 'value' ] ?? $value;
    $attributes            = \Polar\Component\HTML::arrayToAttributes( $attributes );
    echo '<input type="submit" ' . $attributes . '>' . PHP_EOL;
    return $this;
  }

  public function textareaTag( $item, $text = null, $attributes = array() ) {
    $textareaValue = $this->data[ $item ] ?? '';
    if (
      isset( $this->errorMessages[ $item ] ) and
      isset( \Polar\Core\Request::$params[ $this->dataName ][ $item ] )
    ) {
      $textareaValue = \Polar\Core\Request::$params[ $this->dataName ][ $item ];
    }
    $textareaValue = !is_null( $text ) ? $text : $textareaValue;
    $name = $this->dataName . '[' . $item . ']';
    $attributes = \Polar\Component\HTML::arrayToAttributes( $attributes );
    echo '<textarea name="' . $name . '" ' . $attributes . '>' . $textareaValue . '</textarea>' . PHP_EOL;
    return $this;
  }

  # Error messages

  public function addErrorMessage( $item, $message ) {
    if ( isset( $this->errorMessages[ $item ] ) ) {
      if ( is_string( $message ) ) {
        $this->errorMessages[ $item ][] = $message;
      }
      else if ( is_array( $message ) ) {
        $this->errorMessages = array_merge( $this->errorMessages[ $item ], $message );
      }
    }
    else {
      if ( is_string( $message ) ) {
        $this->errorMessages[ $item ][] = array( $message );
      }
      else if ( is_array( $message ) ) {
        $this->errorMessages[ $item ] = $message;
      }      
    }
    return $this;
  }

  public function listErrorMessages( $item, $attributes = array()) {
    if (
      isset( $this->errorMessages[ $item ] ) and
      count( $this->errorMessages[ $item ] )
    ) {
      $attributes = \Polar\Component\HTML::arrayToAttributes( $attributes );
      echo '<ul ' . $attributes . '>' . PHP_EOL;
      foreach ( $this->errorMessages[ $item ] as $message ) {
        echo '<li>' . $message . '</li>' . PHP_EOL;
      }
      echo '</ul>' . PHP_EOL;
    }
    return $this;
  }

}
