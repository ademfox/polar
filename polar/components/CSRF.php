<?php

# \Polar\Component\CSRF V1.0

namespace Polar\Component;

class CSRF {

  # Generates token from key.
  public static function generateToken( $key ) {
    $token = base64_encode( openssl_random_pseudo_bytes( 16 ) );
    $_SESSION[ 'CSRF' . $key ] = $token;
    return $token;
  }

  # Verify key and token.
  public static function verifyToken( $key, $token ) {
    if (
      isset( $_SESSION[ 'CSRF' . $key ] ) and
             $_SESSION[ 'CSRF' . $key ] === $token
    ) {
      return true;
    }
    return false;
  }

}
