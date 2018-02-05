<?php

namespace Polar\Component;

class Crypt {

  public static function hash( $string, $cost = 12 ) {
    $options = array( 'cost' => $cost );
    return password_hash( $string, PASSWORD_BCRYPT, $options );
  }

  public static function verify( $string, $hash ) {
    return password_verify( $string, $hash );
  }

  public static function generateRandom() {
    return mcrypt_create_iv( 22, MCRYPT_DEV_URANDOM );
  }

  public static function generateUUID() {
    $data = random_bytes( 16 );
    assert( strlen( $data ) == 16 );
    $data[6] = chr( ord( $data[ 6 ] ) & 0x0f | 0x40 ); # set version to 0100
    $data[8] = chr( ord( $data[ 8 ] ) & 0x3f | 0x80 ); # set bits 6-7 to 10
    return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
  }

}
