<?php

# \Polar\Component\PolarMySQL V1.0
# Require MySQLRecord, Polar

namespace Polar\Component;

class PolarMySQL {

  public static $primaryKeyColumn = 'ID';

  public static function getMySQLConfig( $name ) {
    return \Polar\Polar::$config[ 'mySQLRecordConfigs' ][ $name ];
  }

  public static function mergeData( &$data, $newData ) {
    foreach( $newData as $key => $value ) {
      $data[ $key ] = $value;
    }
    return $data;
  }

  public static function countWhere( $configName, $table, $where ) {
    $config         = self::getMySQLConfig( $configName );
    $mySQLRecordObj = new \Polar\Core\MySQLRecord( $config );
    $result         = $mySQLRecordObj->count( array(
      'table' => $table,
      'where' => $where
    ) );
    $mySQLRecordObj->disconnect();
    return $result;
  }

  public static function destroyByID( $configName, $table, $ID ) {
    if ( !is_numeric( $ID ) ) {
      return false;
    }
    $config         = self::getMySQLConfig( $configName );
    $mySQLRecordObj = new \Polar\Core\MySQLRecord( $config );
    $result         = $mySQLRecordObj->delete( array(
      'table' => $table,
      'where' => array( 'ID=?', ( int ) $ID )
    ) );
    $mySQLRecordObj->disconnect();
    return $result;
  }

  public static function destroyWhere( $configName, $table, $where ) {
    $config         = self::getMySQLConfig( $configName );
    $mySQLRecordObj = new \Polar\Core\MySQLRecord( $config );
    $result         = $mySQLRecordObj->delete( array(
      'table' => $table,
      'where' => $where
    ) );
    $mySQLRecordObj->disconnect();
    return $result;
  }

  public static function insert( $configName, $table, $data ) {
    if ( isset( $data[ self::$primaryKeyColumn ] ) ) {
      return false;
    }
    $config         = self::getMySQLConfig( $configName );
    $mySQLRecordObj = new \Polar\Core\MySQLRecord( $config );
    $result         = $mySQLRecordObj->insert( array(
      'data'  => $data,
      'table' => $table
    ) );
    $mySQLRecordObj->disconnect();
    return $result;
  }

  public static function findAll( $configName, $table, $record ) {
    $config            = self::getMySQLConfig( $configName );
    $mySQLRecordObj    = new \Polar\Core\MySQLRecord( $config );
    $record[ 'table' ] = $table;
    $results           = $mySQLRecordObj->findAll( $record );
    $mySQLRecordObj->disconnect();
    return $results;  
  }

  public static function findByID( $configName, $table, $ID ) {
    if ( !is_numeric( $ID ) ) {
      return false;
    }
    $config         = self::getMySQLConfig( $configName );
    $mySQLRecordObj = new \Polar\Core\MySQLRecord( $config );
    $result         = $mySQLRecordObj->find( array(
      'table' => $table,
      'where' => array( 'ID=?', ( int ) $ID )
    ) );
    $mySQLRecordObj->disconnect();
    return $result;  
  }

  public static function findWhere( $configName, $table, $where ) {
    $config         = self::getMySQLConfig( $configName );
    $mySQLRecordObj = new \Polar\Core\MySQLRecord( $config );
    $result         = $mySQLRecordObj->find( array(
      'table' => $table,
      'where' => $where
    ) );
    $mySQLRecordObj->disconnect();
    return $result;
  }

  public static function update( $configName, $table, $data, $where = array() ) {
    $config         = self::getMySQLConfig( $configName );
    $mySQLRecordObj = new \Polar\Core\MySQLRecord( $config );
    if (
           isset( $data[ 'ID' ] ) and
      is_numeric( $data[ 'ID' ] )
    ) {
      $where = array( 'ID=?', ( int ) $data[ 'ID' ] );
      unset( $data[ 'ID' ] );
    }
    $result = $mySQLRecordObj->update( array(
      'data'  => $data,
      'table' => $table,
      'where' => $where
    ) );
    $mySQLRecordObj->disconnect();
    return $result; 
  }

}