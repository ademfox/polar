<?php

function cleanPushTagsString( $tags ) {
  if ( $tags ) {
    # Make everything lowercase.
    $tags = strtolower( $tags );
    # Remove whitespace and unwanted characters.
    $patterns = array(
      '/[\s]+/',
      '/[^a-z0-9\-\,]+/'
    );
    $tags = preg_replace( $patterns, '', $tags );
    # Turn into array, splitting by comma.
    $tags = explode( ',', $tags );
    # Remove duplicates.
    $tags = array_unique( $tags );
    # Sort array.
    sort( $tags );
    # Wrap keywords with underscore.
    foreach ( $tags as $index => $tag ) {
      $tag            = preg_replace( '/^[-]+/', '', $tag );
      $tag            = preg_replace( '/[-]+$/', '', $tag );
      $tags[ $index ] = '_' . $tag . '_';
    }
    # Implode back into string.
    $tags = implode( ', ', $tags );
  }
  return $tags;
}

function cleanPathString( $path ) {
  # Make everything lowercase.
  $path = strtolower( $path );
  # Replace underscore and dash with space.
  $path = preg_replace( '/[\_\-]+/', ' ', $path );
  # Remove leading and trailing whitespaces and unwanted characters.
  $patterns = array(
    '/^[\s]+/', '/[\s]+$/',
    '/[^a-z0-9\s]+/'
  );
  $path = preg_replace( $patterns, '', $path );
  # Make sure there is no double spaces.
  $path = preg_replace( '/[\s]+/', ' ', $path );
  # Convert spaces unto dash.
  return preg_replace( '/[\s]+/', '-', $path );
}

function cleanPullTagsString( $tags ) {
  return preg_replace( '/[_]+/', '', $tags );
}

function cleanKeywordsString( $keywords ) {
  # Make everything lowercase.
  $keywords = strtolower( $keywords );
  # Remove whitespace and unwanted characters.
  $patterns = array(
    '/[\s]+/',
    '/[^a-z0-9\-\_\,]+/'
  );
  $keywords = preg_replace( $patterns, '', $keywords );
  # Turn into array, splitting by comma.
  $keywords = explode( ',', $keywords );
  # Remove duplicates.
  $keywords = array_unique( $keywords );
  # Sort array.
  sort( $keywords );
  # Implode back into string.
  return implode( ', ', $keywords );
}

function plainTextToHTML( $string ) {
  $matches = array();
  $pattern = '/((http[s]?):[^\s]+)/';
  $hasURL  = preg_match_all( $pattern, $string, $matches );
  if ( $hasURL ) {
    $matches = array_filter( $matches[ 0 ] );
    $matches = array_unique( $matches );
    foreach ( $matches as $match ) {
      $link   = '<a href="' . $match . '" target="_blank">' . $match . '</a>';
      $string = str_replace( $match, $link, $string );
    }
  }
  $string = preg_replace( '/\n/', '<br>'        , $string );
  $string = preg_replace( '/\t/', '&nbsp;&nbsp;', $string );
  return $string;
}

function randomChoiceArray( $array ) {
  $max = count( $array ) -1;
  return $array[ rand( 0, $max ) ];
}

function recaptchaForm() {
  $key = \Polar\Polar::$config[ 'recaptcha' ][ 'key' ];
  echo '<div class="g-recaptcha"data-sitekey="' . $key . '"></div>';
}