<?php

  class MysqlDB
  {
    protected $db;
    protected $queryTemplates;

    /**
     * BaseModel constructor.
     *
     * @param $dbConfig array A hash with DB credentials, all hash fields are mandatory.
     * e.g.
     * [
     *   'dsn' => 'mysql:dbname=read_db;host=localhost',
     *   'username => 'user',
     *   'password => 'pass'
     * ],
     *
     */
    function __construct( $dbConfig  = null )
    {
      $this->queryTemplates = [
        'insert'           => 'INSERT INTO %1$s (%2$s) VALUES (%3$s)',
        'insertIgnore'     => 'INSERT IGNORE INTO %1$s (%2$s) VALUES (%3$s)',
        'dropTable'        => 'DROP TABLE IF EXISTS `%1$s`',
        'createTableCopy'  => 'CREATE TABLE `%1$s` LIKE `%2$s`',
        'copyTableData'    => 'INSERT `%2$s` SELECT * FROM `%1$s`',
        'wipeTable'        => 'DELETE FROM `%1$s`',
        'tableExists'      => 'SHOW TABLES LIKE \'%1$s\'',
        'countBy'          => 'SELECT COUNT(%1$s) AS `count` FROM %2$s WHERE %3$s',
        'totalCount'       => 'SELECT COUNT(*) AS `count` FROM %1$s',
        'getBy'            => 'SELECT * FROM %1$s WHERE %2$s',
        'getByLimit'       => 'SELECT * FROM %1$s WHERE %2$s LIMIT %3$d',
        'getSorted'        => 'SELECT * FROM %1$s ORDER BY %2$s LIMIT %3$d, %4$d',
        'deleteBy'         => 'DELETE   FROM %1$s WHERE %2$s',
        'updateBy'         => 'UPDATE        %1$s SET %2$s, updated_at = NOW() WHERE %3$s',
        'updateIgnoreBy'   => 'UPDATE IGNORE %1$s SET %2$s, updated_at = NOW() WHERE %3$s',
      ];

      $this->connect( $dbConfig );
    }

    private function connect( $dbConfig )
    {
      $this->dbConfig = $dbConfig;
      $this->db = null;

      $retries = defined( 'DB_CONNECT_RETRIES' ) ? DB_CONNECT_RETRIES : 1;

      $dsn      = $dbConfig['dsn'];
      $username = $dbConfig['username'];
      $password = $dbConfig['password'];

      $lastError = '';
      while ( $retries > 0 ) {
        try {
          $this->db = new PDO( $dsn, $username, $password );
          $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          $this->db->exec("SET NAMES 'utf8'");
          $retries = 0;
        } catch ( PDOException $exception ) {
          // TODO: log exception
          $lastError = $exception->getMessage();
          $retries--;
          usleep( 500 ); // Wait 0.5s between retries.
        }
      }

      if ( !$this->db ) {
        throw new Exception( "Unable to connect to database: $lastError" );
      }

      return $this->db;
    }

    protected function prepareAndExecute( $query, $queryValues = array() )
    {
      $pdoStatement = $this->db->prepare( $query );
      $pdoStatement->execute( $queryValues );

      $result = $pdoStatement->fetchAll( PDO::FETCH_ASSOC );

      return $result;
    }

    // for insert/update queries, returns the result of execute....
    protected function prepareAndExecuteUpdate( $query, $queryValues = array() )
    {
      $pdoStatement = $this->db->prepare( $query );

      if ( $pdoStatement->execute( $queryValues ) ) {
        $lastInsertId = $this->db->lastInsertId();
        if ( $lastInsertId ) {
          return $lastInsertId;
        }
        else {
          return true;
        }
      }

      return false;
    }

    public function readQuery( $query, $queryValues = array(), $modifiers = array() )
    {
      return $this->prepareAndExecute( vsprintf( $query, $modifiers ), $queryValues );
    }

    public function writeQuery( $query, $queryValues = array(), $modifiers = array() )
    {
      return $this->prepareAndExecuteUpdate( vsprintf( $query, $modifiers ), $queryValues );
    }

    // $queryType can be 'read' or 'write'
    public function query( $query, $queryValues, $modifiers, $queryType )
    {
      $queryResponse = null;

      switch( $queryType ) {
        case 'read':  $queryResponse = $this->readQuery( $query, $queryValues, $modifiers );  break;
        case 'write': $queryResponse = $this->writeQuery( $query, $queryValues, $modifiers ); break;
      }

      return $queryResponse;
    }

    public function createTableCopy( $orgTable, $copyData = false )
    {
      $tmpTable = "tmp_$orgTable";
      $this->writeQuery( $this->queryTemplates['dropTable'],       [], ['tableName' => $tmpTable] );
      $this->writeQuery( $this->queryTemplates['createTableCopy'], [], ['tmpTable'  => $tmpTable, 'orgTable' => $orgTable] );
      if ( $copyData ) {
        $this->copyTableData( $orgTable, $tmpTable );
      }

      return $tmpTable;
    }

    public function copyTableData( $fromTable, $toTable ) {
      $this->writeQuery( $this->queryTemplates['copyTableData'], [], ['fromTable' => $fromTable, 'toTable' => $toTable] );
    }

    public function dropTable( $tableName )
    {
      $this->writeQuery( $this->queryTemplates['dropTable'], [], ['tableName' => $tableName] );
    }

    public function wipeTable( $tableName )
    {
      $this->writeQuery( $this->queryTemplates['wipeTable'], [], ['tableName' => $tableName] );
    }

    public function tableExists( $tableName )
    {
      return count( $this->readQuery( $this->queryTemplates['tableExists'], [], ['tableName' => $tableName] ) ) ? true : false;
    }

    public function insert( $tableName, $data, $ignore = false )
    {
      $queryTemplate = $ignore ? 'insertIgnore' : 'insert';

      $fields    = [];
      $pdoFields = [];
      foreach( $data as $field => $value ) {
        $fields[]    = $field;
        $pdoFields[] = ":$field";
      }
      $fields    = implode( ',', $fields );
      $pdoFields = implode( ',', $pdoFields );

      return $this->writeQuery( $this->queryTemplates[$queryTemplate], $data, [$tableName, $fields, $pdoFields] );
    }

    public function insertMultiple( $tableName, $data, $ignore = false )
    {
      foreach( $data as $row ) {
        $this->insert( $tableName, $row, $ignore );
      }
    }

    public function countBy( $tableName, $fieldsMapping, $countExpression = '*' )
    {
      $whereConditions = [];
      foreach( $fieldsMapping as $name => $value ) {
        $whereConditions[] = "$name = :$name";
      }

      $result = $this->readQuery( $this->queryTemplates['countBy'], $fieldsMapping, [
        $countExpression, // TODO: SECURITY: sql injection ...
        $tableName,
        implode( ' AND ', $whereConditions ),
      ]);

      return $result[0]['count'];
    }

    public function totalCount( $tableName )
    {
      $result = $this->readQuery( $this->queryTemplates['totalCount'], [], [$tableName] );

      return $result[0]['count'];
    }

    public function existsBy( $tableName, $fieldsMapping )
    {
      return $this->countBy( $tableName, $fieldsMapping ) > 0;
    }

    public function getBy( $tableName, $fieldsMapping, $limit = null )
    {
      $whereConditions = ["1 = 1"];
      foreach( $fieldsMapping as $name => $value ) {
        $whereConditions[] = "$name = :$name";
      }

      $modifiers = [
        'tableName'  => $tableName,
        'whereQuery' => implode( ' AND ', $whereConditions )
      ];
      if ( $limit && $limit > 1 ) {
        $modifiers[] = $limit;
      }

      $queryTemplate = $limit && $limit > 1 ? 'getByLimit' : 'getBy';
      $result = $this->readQuery( $this->queryTemplates[$queryTemplate], $fieldsMapping, $modifiers );

      if ( $result && $limit && $limit == 1 ) {
        return $result[0];
      }
      else {
        return $result;
      }
    }

    public function getSorted( $tableName, $sortFields, $offset, $limit )
    {
      $sortConditions = [];
      foreach( $sortFields as $sortField => $sortOrder ) {
        $sortConditions[] = "$sortField $sortOrder";
      }

      $modifiers = [
        $tableName,
        implode( ',', $sortConditions ),
        $offset,
        $limit
      ];

      $result = $this->readQuery( $this->queryTemplates['getSorted'], [], $modifiers );

      if ( $result && $limit && $limit == 1 ) {
        return $result[0];
      }
      else {
        return $result;
      }
    }


    public function deleteBy( $tableName, $fieldsMapping )
    {
      $whereConditions = [];
      foreach( $fieldsMapping as $name => $value ) {
        $whereConditions[] = "$name = :$name";
      }

      $modifiers = [
        'tableName'  => $tableName,
        'whereQuery' => implode( ' AND ', $whereConditions )
      ];

      $queryTemplate = 'deleteBy';
      $result = $this->writeQuery( $this->queryTemplates[$queryTemplate], $fieldsMapping, $modifiers );

      return $result;
    }

    public function update( $tableName, $fieldsMapping, $conditions, $ignore = false )
    {
      $queryTemplate = $ignore ? 'updateIgnoreBy' : 'updateBy';

      $setValues = [];
      foreach( $fieldsMapping as $name => $value ) {
        $setValues[] = "$name = :$name";
      }

      $whereConditions = ["1 = 1"];
      foreach( $conditions as $name => $value ) {
        $whereConditions[] = "$name = :$name";
      }

      $modifiers = [$tableName, implode( ',', $setValues ), implode( ' AND ', $whereConditions )];

      return $this->writeQuery( $this->queryTemplates[$queryTemplate], $fieldsMapping, $modifiers );
    }

    public function execSQLScript($filePath)
    {
      $command = "mysql --verbose -u " . $this->dbConfig['username'] . " --password=" . $this->dbConfig['password'] . " -h " . $this->dbConfig['host'] . " " . $this->dbConfig['name'] . " < $filePath 2>&1";

      exec( $command, $output, $exitCode );
      return [
        'output' => $output,
        'exitCode' => $exitCode
      ];
    }
  }

?>