<?php
  // 3RD PARTY
  require_once __DIR__  . '/../vendor/autoload.php';

  // ENVIRONMENT
  $dotenv = new Dotenv\Dotenv('.');
  $dotenv->load();

  // CONFIG
  require_once __DIR__ . '/config.php';
  
  // LIB
  require_once __DIR__ . '/lib/mysqldb.class.php';
  require_once __DIR__ . '/lib/logger.class.php';
  require_once __DIR__ . '/lib/taskmanager.class.php';

  // DB
  $db = new MysqlDB([
    'dsn'      => DB_DSN,
    'host'     => DB_HOST,
    'name'     => DB_NAME,
    'username' => DB_USERNAME,
    'password' => DB_PASSWORD
  ]);

  // LOGS
  $logger = new Logger();

  // TASKS
  $taskManager = new TaskManager( $logger, $db );