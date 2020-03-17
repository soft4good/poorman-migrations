<?php
  error_reporting(E_ALL & ~E_NOTICE);

  // 3RD PARTY
  require_once __DIR__  . '/../../autoload.php';

  // LIB
  require_once __DIR__ . '/lib/helpers.php';
  require_once __DIR__ . '/lib/mysqldb.class.php';
  require_once __DIR__ . '/lib/logger.class.php';
  require_once __DIR__ . '/lib/taskmanager.class.php';
  
  // LOGS
  $logger = new Logger();
  $logger->toggleStdOut();

  // ENVIRONMENT
  try {
    $dotenv = new Dotenv\Dotenv('.');
    $dotenv->load();
  }
  catch ( Exception $e ) {
    $logger->log( "WARNING", "No .env found." );
  }
  
  // CONFIG
  require_once __DIR__ . '/config.php';
  
  // DB
  $db = null;
  try {
    $db = new MysqlDB([
      'dsn'      => DB_DSN,
      'host'     => DB_HOST,
      'name'     => DB_NAME,
      'username' => DB_USERNAME,
      'password' => DB_PASSWORD
    ]);
  }
  catch ( Exception $e ) {
    $logger->log( "WARNING", "Running with NO connection to the database. " . $e->getMessage() );
  }

  // TASKS
  $taskManager = new TaskManager( $logger, $db );