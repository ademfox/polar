<?php

# \Polar\Core\MySQLRecord V1.2
# Require Polar

namespace Polar\Core;

class MySQLRecord {

  public $config = array(
    'host'     => 'localhost',
    'port'     => 22,
    'database' => 'db',
    'user'     => 'root',
    'password' => 'root',
  );

  public $connection = null;

  public function __construct( $config = null ) {
    $this->connect( $config );
  }

  public function connect( $config = null ) {
    if (
      !is_null( $config ) and
      is_array( $config )
    ) {
      $this->config = $config;
    }
    if ( is_null( $this->connection ) ) {
      $database = 'mysql:host=' . $this->config[ 'host' ] .
                  ';port=' .      $this->config[ 'port' ] .
                  ';dbname=' .    $this->config[ 'database' ];
      $this->connection = new \PDO( $database, $this->config[ 'user' ], $this->config[ 'password' ] );
    }
    if ( \Polar\Polar::$enableErrorReporting ) {
      $this->connection->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
      $this->connection->setAttribute( \PDO::ATTR_EMULATE_PREPARES, false );
    }
  }

  public function disconnect() {
    if ( !is_null( $this->connection ) ) {
      $this->connection = null;
    }
  }

  # Query

  public function query( $SQL, $params = array() ) {
    $statement = $this->connection->prepare( $SQL );
    $index = 1;
    foreach( $params as $value ) {
      if ( $value === '' ) {
        $value = NULL;
      }
      $statement->bindValue( $index, $value, $this->getPdoConstantType( $value ) );
      $index++;
    }
    $statement->execute();
    if ( $statement->errorCode() == 0000 ) {
      $result = $statement->fetchAll( \PDO::FETCH_ASSOC );
      return $result;
    }
    return false;
  }

  public function getColumnNamesFromTable( $table ) {
    $SQL = "SELECT DISTINCT {$table} FROM INFORMATION_SCHEMA.COLUMNS";
    $statement = $this->connection->prepare( $SQL );
    $statement->execute();
    if ( $statement->errorCode() == 0000 ) {
      $result = $statement->fetchAll( \PDO::FETCH_ASSOC );
      return $result;
    }
    return false;
  }

  public function count( $record ) {
    $require = array( 'table' );
    if ( !$this->_recordContains( $record, $require ) ) {
      return false;
    }
    $SQL = "SELECT COUNT(*) FROM {$record[ 'table' ]}";
    $VALUES = array();
    if ( isset( $record[ 'where' ] ) ) {
      $this->_appendWhere( $record[ 'where'], $SQL, $VALUES );
    }
    $statement = $this->connection->prepare( $SQL );
    $index     = 1;
    foreach( $VALUES as $value ) {
      if ( $value === '' ) {
        $value = NULL;
      }
      $statement->bindValue( $index, $value, $this->getPdoConstantType( $value ) );
      $index++;
    }
    $statement->execute();
    if ( $statement->errorCode() == 0000 ) {
      $count = $statement->fetch( \PDO::FETCH_ASSOC );
      $count = ( int ) $count[ 'COUNT(*)' ];
      return $count;
    }
    return false;
  }

  /*
    array(
      'data' => array(
        'Name' => 'Andrew',
        'Age'  => 22,
      ),
      'encrypt' => array(
        'Name' => null,
        'Age'  => 'abcde'
      ),
      'password' => 'abc',
      'table' => 'Users'
    );
  */
  public function insert( $record ) {
    $require = array( 'data', 'table' );
    if ( !$this->_recordContains( $record, $require ) ) {
      return false;
    }
    $data   = $record[ 'data' ];
    $keys   = array_keys( $data );
    $fields = implode( ', ', $keys );
    $bounds = array();
    $binds  = array();
    $stringFn = function( $column, $password ) use ( &$binds, &$bounds, &$data ) {
      if ( is_null( $password ) ) {
        $bounds[] = ':' . $column;
      } else {
        $bounds[] = 'AES_ENCRYPT(:' . $column . ', \'' . $password . '\')';
      }
      $binds[ $column ] = $data[ $column ];
    };
    $this->_processCrypt( 'encrypt', $record, $keys, $stringFn );
    $bounds    = implode( ', ', $bounds );
    $SQL       = "INSERT INTO {$record['table']} ({$fields}) VALUES ({$bounds})";
    $statement = $this->connection->prepare( $SQL );
    foreach( $binds as $key => $value ) {
      $statement->bindValue( ':' . $key, $value, $this->getPdoConstantType( $value ) );
    }
    $statement->execute();
    if ( $statement->errorCode() == 0000 ) {
      $result = $this->connection->lastInsertId();
      return $result ? $result : true;
    }
    return false;
  }

  /*
    array(
      'data' => array(
        'Name' => 'Andrew',
        'Age'  => 22,
      ),
      'encrypt' => array(
        'Name' => null,
        'Age'  => 'abcde'
      ),
      'password' => 'abc',
      'table' => 'Users',
      'where'
    );
  */
  public function update( $record ) {
    $require = array( 'table', 'data' );
    if ( !$this->_recordContains( $record, $require ) ) {
      return false;
    }
    $data   = $record[ 'data' ];
    $keys   = array_keys( $data );
    $VALUES = array();
    $set    = array();
    $stringFn = function( $column, $password ) use( &$data, &$set, &$VALUES ) {
      if ( !is_null( $password ) ) {
        $set[]    = $column . '=AES_ENCRYPT(?, \'' . $password . '\')';
        $VALUES[] = $data[ $column ];
      } else {
        $set[]    = $column . '=?';
        $VALUES[] = $data[ $column ];
      }
    };
    $this->_processCrypt( 'encrypt', $record, $keys, $stringFn );
    $set = implode( ', ', $set );
    $SQL = "UPDATE {$record[ 'table' ]} SET $set";
    if ( isset( $record[ 'where' ] ) ) {
      $this->_appendWhere( $record[ 'where' ] , $SQL, $VALUES );
    }
    $statement = $this->connection->prepare( $SQL );
    $index = 1;
    foreach( $VALUES as $value ) {
      if ( $value === '' ) {
        $value = NULL;
      }
      $statement->bindValue( $index, $value, $this->getPdoConstantType( $value ) );
      $index++;
    }
    $statement->execute();
    return ( $statement->errorCode() == 0000 ) ? true : false;
  }

  public function find( $record ) {
    $require = array( 'table' );
    if ( !$this->_recordContains( $record, $require ) ) {
      return false;
    }
    $columns = $this->_processColumns( $record );
    $SQL     = "SELECT {$columns} FROM {$record[ 'table' ]}";
    $VALUES  = array();
    if ( isset( $record[ 'where' ] ) ) {
      $this->_appendWhere( $record[ 'where' ], $SQL, $VALUES );
    }
    $SQL      .= ' LIMIT 1';
    $statement = $this->connection->prepare( $SQL );
    $index     = 1;
    foreach( $VALUES as $value ) {
      if ( $value === '' ) {
        $value = NULL;
      }
      $statement->bindValue( $index, $value, $this->getPdoConstantType( $value ) );
      $index++;
    }
    $statement->execute();
    if ( $statement->errorCode() == 0000 ) {
      $result = $statement->fetch();
      return is_array( $result ) ? $result : [];
    }
    return false;
  }

  /*
    array(
      'table' => 'article',
      'column' => '',
      'where' => array('id = ?' => 2),
      'order' => 'name DESC'
      'limit' => 0,
      'offset' => 2,
    );
  */
  public function findAll( $record ) {
    $require = array( 'table' );
    if ( !$this->_recordContains( $record, $require ) ) {
      return false;
    }
    $columns  = $this->_processColumns( $record );
    $distinct = ( isset( $record[ 'distinct' ] ) and $record[ 'distinct' ] == true ) ? 'DISTINCT' : '';
    $SQL      = "SELECT {$distinct} {$columns} FROM {$record[ 'table' ]}";
    $VALUES   = array();
    if ( isset( $record[ 'where'] ) ) {
      $this->_appendWhere( $record['where'], $SQL, $VALUES );
    }
    if ( isset( $record[ 'order' ] ) ) {
      $this->_appendOrder( $record['order'], $SQL, $VALUES );
    }
    if ( isset( $record[ 'limit'] ) ) {
      $this->_appendLimit( $record['limit'], $SQL, $VALUES );
    }
    if ( isset( $record[ 'offset'] ) ) {
      $this->_appendOffset( $record['offset'], $SQL, $VALUES );
    }
    $statement = $this->connection->prepare( $SQL);
    $i = 1;
    foreach( $VALUES as $value ) {
      $statement->bindValue( $i, $value, $this->getPdoConstantType( $value ) );
      $i++;
    }
    $statement->execute();
    if ( $statement->errorCode() == 0000 ) {
      $result = $statement->fetchAll( \PDO::FETCH_ASSOC );
      return is_array( $result ) ? $result : [];
    } else {
      return false;
    }
  }

  public function delete( $record ) {
    $require = array( 'table' );
    if ( !$this->_recordContains( $record, $require ) ) {
      return false;
    }
    $SQL = "DELETE FROM {$record[ 'table' ]}";
    if ( isset( $record[ 'where' ] ) ) {
      $this->_appendWhere( $record[ 'where' ], $SQL, $VALUES );
    }
    $statement = $this->connection->prepare( $SQL );
    $i = 1;
    foreach( $VALUES as $value ) {
      $statement->bindValue( $i, $value, $this->getPdoConstantType( $value ) );
      $i++;
    }
    $statement->execute();
    return ( $statement->errorCode() == 0000 ) ? true : false;
  }

  # Utils

  private function getPdoConstantType( $variable ) {
    if ( is_int( $variable ) ) {
      return \PDO::PARAM_INT;
    }
    else if ( is_bool( $variable ) ) {
      return \PDO::PARAM_BOOL;
    }
    else if ( is_null( $variable ) ) {
      return \PDO::PARAM_NULL;
    }
    else if ( strlen( $variable ) > 255 ) {
      return \PDO::PARAM_LOB;
    }
    return \PDO::PARAM_STR;
  }

  private function _recordContains( &$record, $keys ) {
    foreach( $keys as $key ) {
      if ( !isset( $record[ $key ] ) ) {
        return false;
      }
    }
    return true;
  }

  private function _processColumns( &$record ) {
    $columns = $record[ 'columns' ] ?? null;
    if ( is_array( $columns ) ) {
      $columnsArray = array();
      $stringFn     = function( $column, $password ) use ( &$columnsArray ) {
        if ( !is_null( $password ) ) {
          $columnsArray[] = "AES_DECRYPT(" . $column . ", '" . $password . "') AS " . $column;
        } else {
          $columnsArray[] = $column;
        }
      };
      $this->_processCrypt( 'decrypt', $record, $columns, $stringFn );
      $columns = implode(', ', $columnsArray);
    } else {
      $columns = '*';
    }
    return $columns;
  }

  private function _processCrypt( $crypt, &$record, $columns, &$fn ) {
    $array = array();
    if (
          isset( $record[ $crypt ] ) and
      is_string( $record[ $crypt ] )
    ) {
      $record[ $crypt ] = array( $record[ $crypt ] );
    }
    foreach( $columns as $i => $column ) {
      if (
           isset( $record[ $crypt ] ) and
        is_array( $record[ $crypt ] ) and
           count( $record[ $crypt ] ) > 0
      ) {
        foreach( $record[ $crypt ] as $a => $b ) {
          if (
            is_integer( $a ) and
             is_string( $b )
          ) {
            $a = $b;
            $b = null;
          }
          $password = null;
          if ( $column == $a ) {
            if (
               !is_null( $b ) and
              is_string( $b )
            ) {
              $password = $b;
            } else if (
                  isset( $record[ 'password' ] ) and
              is_string( $record[ 'password' ] ) and
                 !empty( $record[ 'password' ] )
            ) {
              $password = $record[ 'password' ];
            }
          }
          $fn($column, $password);
        }
      } else {
        $fn($column, null);
      }
    } # end foreach
  }

  private function _appendOrder( $order, &$SQL ) {
    if ( is_string( $order ) ) {
      $SQL .= ' ORDER BY ' . $order;
    }
  }

  private function _appendLimit( $limit, &$SQL, &$VALUES ) {
    if ( is_integer( $limit ) ) {
      $SQL .= ' LIMIT ?';
      $VALUES[] = $limit;
    }
  }

  private function _appendOffset( $offset, &$SQL, &$VALUES ) {
    if ( is_integer( $offset ) ) {
      $SQL .= ' OFFSET ?';
      $VALUES[] = $offset;
    }
  }

  /*
    column_name BETWEEN value1 AND value2
    column_name IN (value1, value2, ...)
    AND OR.
  */
  private function _appendWhere( $where, &$SQL, &$VALUES ) {
    if (
      (
        is_string( $where ) and
           !empty( $where )
      ) or
      (
               $this->_isBindClause( $where ) or
        $this->_isAssociativeClause( $where )
      )
    ) {
      $where = array( $where );
    }
    if (
      is_array( $where ) and
         count( $where ) > 0 
    ) {
      $this->_processWhere( $where, $SQL, $VALUES );
    }
  }

  private function _processWhere( $where, &$SQL, &$VALUES ) {
    $SQL       .= ' WHERE';
    $whereCount = count( $where );
    $i = 1;
    foreach( $where as $_where ) {
      $appendable = ( $whereCount > $i ) ? true : false;
      if ( is_string( $_where ) ) {
        $this->_appendStringClause( $_where, $SQL, $VALUES, $appendable );
      }
      else if ( is_array( $_where ) ) {
        if ( $this->_isBindClause( $_where ) ) {
          $this->_appendBindClause( $_where, $SQL, $VALUES, $appendable );
        }
        else if ( $this->_isAssociativeClause( $_where ) ) {
          return $this->_appendAssociativeClause( $_where, $SQL, $VALUES, $appendable );
        }
      }
      $i++;
    }
  }

  /*
    array(
      "column LIKE 'value'",
      array("(name = ? OR job = ?) AND", array('Andrew', 'Designer')),
      array("(name = ? OR job = ?) AND", 'Andrew', 'Designer'),
      'name' => 'Andrew',
    );

    This will produce (in SQL): "name = 'Andrew'"
  */

  private function _trimStringPadding( $string ) {
    # Remove spaces before and after.
    return preg_replace( array( '/^[\s]+/', '/[\s]+$/' ), '', $string );
  }

  private function _containsEndOperator( $string ) {
    return preg_match( '/(AND|OR)[\s]*$/', $string ) ? true : false;
  }

  private function _removeEndOperator( $string ) {
    return preg_replace( '/[\s]+(AND|OR)([\s]+)?$/', '', $string );
  }

  private function _isBindClause( $clause ) {
    if (
       !is_array( $clause ) or
          !isset( $clause[ 0 ] ) or
      !is_string( $clause[ 0 ] )
    ) {
      return false;
    }
    $binders = array();
    preg_match_all( '/[?]/', $clause[ 0 ], $binders );
    $binders = count( $binders[ 0 ] );
    if ( !$binders ) {
      return false;
    }
    if (
      is_array( $clause[ 1 ] ) and
         count( $clause[ 1 ] ) == $binders
    ) {
      return true;
    }
    else if ( ( count( $clause ) - 1 ) == $binders ) {
      return true;
    }
    return false;
  }

  private function _isAssociativeClause( $clause ) {
    if ( !is_array( $clause ) ) {
      return false;
    }
    if (
      is_string( key( $clause ) ) and
      preg_match( '/^[a-zA-Z_\$\#\@]+$/', key( $clause ) )
    ) {
      return true;
    }
    return false;
  }

  private function _appendStringClause( $clause, &$SQL, &$VALUES, $appendable = false ) {
    $clause = $this->_trimStringPadding( $clause );
    if (
      $appendable === true and
      !$this->_containsEndOperator( $clause )
    ) {
      $clause .= ' AND';
    }
    else if ( $this->_containsEndOperator( $clause ) ) {
      $clause = $this->_removeEndOperator( $clause );
    }
    $SQL .= ' '.$clause;
  }

  private function _appendBindClause( $clause, &$SQL, &$VALUES, $appendable = false ) {
    $clause[ 0 ] = $this->_trimStringPadding( $clause[ 0 ] );
    if (
      $appendable === true and
      !$this->_containsEndOperator( $clause[ 0 ] )
    ) {
      $clause[ 0 ] .= ' AND';
    }
    else if ( $this->_containsEndOperator( $clause[ 0 ] ) ) {
      $clause[ 0 ] = $this->_removeEndOperator( $clause[ 0 ] );
    }
    $SQL .= ' ' . $clause[ 0 ];
    $_values = array();
    if ( is_array( $clause[ 1 ] ) ) {
      $_values = $clause[ 1 ];
    }
    else {
      $_values = $clause;
      array_shift( $_values );
    }
    foreach( $_values as $_value ) {
      $VALUES[] = $_value;
    }
  }

  private function _appendAssociativeClause( $clause, &$SQL, &$VALUES, $appendable = false ) {
    $clause = $this->_trimStringPadding( $clause );
    $key    = key( $clause );
    $SQL   .= ' ' . $key . ' = ?';
    if ( $appendable === true ) {
      $SQL .= ' AND';
    }
    $VALUES[] = $clause[ $key ];
  }

}