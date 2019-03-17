<?php

class TaskManager {

  private $logger;
  private $db;

  function __construct( $logger, $db ) {
    $this->logger = $logger;
    $this->db = $db;
  }

  public function generateMigration( $name ) {
    if ( !$name ) {
      $this->logger->log('ERROR', 'Migration name is required.' );
      return false;
    }

    $migrationPath = MIGRATIONS_DIR . "/" . time() . "_$name.sql";
    try {
      $fp = fopen( $migrationPath, 'a+' );
      fwrite( $fp, "START TRANSACTION;\n-- Migration queries -- \n\nCOMMIT;" );
      fclose( $fp );
    }
    catch( Exception $e ) {
      $this->logger->log('ERROR', 'Unable to create migration file - ' . $e->getMessage() );
      return false;
    }

    $this->logger->log('INFO', "Migration generated successfully at $migrationPath" );
    return true;
  }

  public function importSchema() {
    $this->logger->log( 'INFO', "Will import schema for [$_ENV[APP_ENV]]..." );

    if ( $_ENV['APP_ENV'] == 'production' ) { // hard-coded as it gets :)
      $this->logger->log( 'ERROR', "This action can't be performed on production." );
      return false;
    }

    try {
      $result = $this->db->execSQLScript( SCHEMA_SCRIPT_PATH );
      if ( $result['exitCode'] > 0 ) {
        $this->logger->log( 'DB', $result['output'] );
        $this->logger->log( 'ERROR', "A MYSQL error occurred importing the schema, check logs above." );
        return false;
      }
      else {
        $this->logger->log( 'INFO', "Schema imported successfully!" );
        return true;
      }
    }
    catch (Exception $e) {
      $this->logger->log( 'ERROR', $e->getMessage() );
      return false;
    }
  }

  public function importSeeds() {
    $this->logger->log( 'INFO', "Will import database seed data for [$_ENV[APP_ENV]]..." );

    if ( $_ENV['APP_ENV'] == 'production' ) { // hard-coded as it gets :)
      $this->logger->log( 'ERROR', "This action can't be performed on production." );
      return false;
    }

    try {
      $result = $this->db->execSQLScript( SEEDS_SCRIPT_PATH );
      if ( $result['exitCode'] > 0 ) {
        $this->logger->log( 'DB', $result['output'] );
        $this->logger->log( 'ERROR', "A MYSQL error occurred importing seed data. Check logs above." );
        return false;
      }
      else {
        $this->logger->log( 'INFO', "Seed data imported successfully!" );
        return true;
      }
    }
    catch (Exception $e) {
      $this->logger->log( 'ERROR', $e->getMessage() );
      return false;
    }
  }

  public function importMigrations() {
    $this->logger->log( 'INFO', 'Will import migrations...' );
    if ( !$this->db->tableExists('schema_migrations') ) {
      $this->logger->log( 'WARNING', 'schema_migrations table not found.' );
    }

    $migrationFiles = scandir( MIGRATIONS_DIR );
    $migrations = [];
    foreach( $migrationFiles as $migrationFile ) {
      if ( preg_match('/([0-9]+?)_(.+?)\.sql/', $migrationFile, $matches ) ) {
        $migrations[] = [
          'fileName'  => $migrationFile,
          'timestamp' => $matches[1],
          'name'      => $matches[2]
        ];
      }
    }
    usort( $migrations, [$this, 'sortMigrations'] );
    foreach( $migrations as $migration ) {
      if ( !$this->importMigration( $migration ) ) {
        return false;
      }
    }

    $this->logger->log( 'INFO', 'Migrations imported successfully!' );
    return true;
  }

  public function reset() {
    return $this->importSchema() && $this->importMigrations() && $this->importSeeds();
  }

  // PRIVATE

  private function importMigration( $migration ) {
    if ( $this->db->tableExists( 'schema_migrations' ) && $this->db->existsBy( 'schema_migrations', ['timestamp' => $migration['timestamp']] ) ) {
      return true;
    }
    else {
      $this->logger->log( 'INFO', "============ Will execute migration [$migration[name]] ============" );
      try {
        $result = $this->db->execSQLScript( MIGRATIONS_DIR . '/' . $migration['fileName'] );
        if ( $result['exitCode'] > 0 ) {
          $this->logger->log( 'DB', $result['output'] );
          $this->logger->log( 'ERROR', "A MYSQL error occurred importing the migration. Check logs above." );
          return false;
        }
        else {
          $this->db->insert( "schema_migrations", ['timestamp' => $migration['timestamp'], 'name' => $migration['name']] );
          $this->logger->log( 'INFO', "Migration imported successfully!" );
          return true;
        }
      }
      catch ( Exception $e ) {
        $this->logger->log( 'ERROR', $e->getMessage() );
        return false;
      }
    }
  }

  private function sortMigrations( $migration1, $migration2) {
    return $migration1['timestamp'] - $migration2['timestamp'];
  }

}