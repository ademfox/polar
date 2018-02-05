<?php

# version 2017-01-27

namespace Polar\Component;

class Locale {

  public static $availableLanguages = array('en');
  public static $currentLanguage    = '';
  public static $defaultLanguage    = 'en';
  public static $localesPath        = '';
  public static $strings            = array();
  private static $sessionId         = '___polarLocale';

  private static function dismantleLangCode($langCode) {
    # Extract quality value.
    $quality = preg_replace('/^[a-z]{2}(-[a-z]{2})?;q=/', '', $langCode);
    $quality = is_numeric($quality) ? $quality : 1;
    # Extract langCode code.
    $code = preg_replace('/;q=(0\.[0-9]|1)$/', '', $langCode);
    # Extract variant and base language.
    if (preg_match('/^[a-z]{2}-[a-z]{2}$/', $code)) {
      $lang    = preg_replace('/-[a-z]{2}$/', '', $code);
      $variant = preg_replace('/^[a-z]{2}-$/', '', $code);
    } else {
      $lang    = $code;
      $variant = null;
    }
    return array(
      'code'     => $code,
      'language' => $lang,
      'quality'  => $quality,
      'variant'  => $variant
    );
  }

  private static function getServerAcceptLanguages() {
    # accept-language: da, en-gb;q=0.8, en;q=0.7
    $acceptLanguages = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    # Remove white space(s).
    $acceptLanguages = preg_replace('/[\s]+/', '', $acceptLanguages);
    # Lowercase everything.
    $acceptLanguages = strtolower($acceptLanguages);
    # Split on comma.
    $acceptLanguages = explode(',', $acceptLanguages);
    if (!is_array($acceptLanguages)) {
      $acceptLanguages = array($acceptLanguages);
    }
    return $acceptLanguages;
  }

  public static function isAvailable($lang) {
    return in_array($lang, self::$availableLanguages);
  }

  public static function getRequestedLanguages() {
    # From query string..
    if (
      isset($_GET['locale']) and
      is_string($_GET['locale']) and
      self::isAvailable($_GET['locale'])
    ) {
      return array(self::dismantleLangCode($_GET['locale']));
    # Session
    } else if (
      isset($_SESSION[self::$sessionId]) and
      isset($_SESSION[self::$sessionId]['language'])
    ) {
      $language = self::dismantleLangCode($_SESSION[self::$sessionId]['language']);
      return array($language);
    # Browser
    } else {
      $acceptLanguages = self::getServerAcceptLanguages();
      $languages = array();
      foreach ($acceptLanguages as $language) {
        $languages[] = self::dismantleLangCode($language);
      }
      # Sort array by quality.
      usort($languages, function ($a, $b) {
        return $b['quality'] - $a['quality'];
      });
      return $languages;
    }
  }

  public static function getMatchedLanguage($ignoreVariant = false) {
    $availableLanguages = self::$availableLanguages;
    $requestedLanguages = self::getRequestedLanguages();
    $isMatched = false;
    $matchedLanguage = '';
    # Check for exact code match.
    foreach ($availableLanguages as $availableLanguage) {
      $al = self::dismantleLangCode($availableLanguage);
      if ($isMatched) {
        break;
      }
      foreach ($requestedLanguages as $rl) {
        if ($ignoreVariant) {
          if ($rl['language'] == $al['language']) {
            $isMatched       = true;
            $matchedLanguage = $al['language'];
          }
        } else {
          if ($rl['code'] == $al['code']) {
            $isMatched       = true;
            $matchedLanguage = $al['code'];
          }
        }
      }
    }
    return ($isMatched) ? $matchedLanguage : false;
  }

  public static function getLanguage() {
    $language = self::getMatchedLanguage();
    if (!$language) {
      $language = self::getMatchedLanguage(true);
      if (!$language) {
        $language = self::$defaultLanguage;
      }
    }
    return $language;
  }

  public static function beginSession() {
    self::$currentLanguage = self::getLanguage();
    $_SESSION[self::$sessionId] = array(
      'availableLanguages' => self::$availableLanguages,
      'language'           => self::$currentLanguage
    );
    return $_SESSION[self::$sessionId];
  }

  public static function getSession() {
    return $_SESSION[self::$sessionId];
  }

  public static function updateLanguage($language = null): bool {
    if (isset($_SESSION[self::$sessionId])) {
      $language = self::getLanguage();
      if (is_string($language)) {
        $_SESSION[self::$sessionId] = array(
          'availableLanguages' => self::$availableLanguages,
          'language'           => $language
        );
        return true;
      }
    }
    return false;
  }

  public static function getStrings($path = '', $language = null) {
    if (!is_string($language)) {
      $language = $_SESSION[self::$sessionId]['language'];
    }
    $json = self::$localesPath.$path.$language.'.json';
    if (file_exists($json)) {
      $content = file_get_contents($json);
      $strings = json_decode($content, true);
      self::$strings = $strings;
      return $strings;
    }
    return false;
  }

}
