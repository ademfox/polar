<?php

namespace Polar;

class ApplicationFilter {

  public function filterEncodeUTF8( $content ) {
    return utf8_encode( $content );
  }

  public function filterLowerCase( $string ) {
    return strtolower( $string );
  }

  public function filterRemoveAllWhiteSpaces( $string ) {
    return preg_replace( '/[\s]+/', '', $string );
  }

  public function filterRemoveExtraWhiteSpaces( $string ) {
    return preg_replace( '/[\s]+/', ' ', $string );
  }

  public function filterTrim( $string ) {
    return preg_replace( array( '/^[\s]+/', '/[\s]+$/' ), '', $string );
  }

}
