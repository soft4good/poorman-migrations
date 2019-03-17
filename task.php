<?php
  require_once('bootstrap.php');

  if ( $argc < 2 ) {
    die("
      Syntax: task.php <task> [<environment>|<artifact_name>] \n

      <task> = migrate | reset | init | seed | gen:migration
      <environment> = development | staging | production
      <artifact_name> = Required for <task=create_migration>, the name of the migration (e.g. 'new_user_fields')
    ");
  }

  $logger->toggleStdOut();

  $task = $argv[1];
  $environment = '';
  if ( strpos( $task, 'gen:' ) !== false ) {
    $artifactName = isset($argv[2]) ? $argv[2] : '';
  }
  else {
    $environment = isset($argv[2]) ? $argv[2] : $environment;
  }

  if ( $environment ) { // overwrite environment
    try {
      $dotenv = new Dotenv\Dotenv(__DIR__, ".env.$environment" );
      $dotenv->overload();

      $db = new MysqlDB([
        'dsn'      => "mysql:host=$_ENV[DB_HOST];dbname=$_ENV[DB_NAME]",
        'host'     => $_ENV['DB_HOST'],
        'name'     => $_ENV['DB_NAME'],
        'username' => $_ENV['DB_USERNAME'],
        'password' => $_ENV['DB_PASSWORD']
      ]);

      $taskManager = new TaskManager( $logger, $db );
    }
    catch ( Exception $e ) {
      $logger->log( 'ERROR', $e->getMessage() );
      die();
    }
  }

  switch ($task) {
    case 'migrate':          $taskManager->importMigrations(); break;
    case 'reset':            $taskManager->reset(); break;
    case 'init':             $taskManager->importSchema(); break;
    case 'seed':             $taskManager->importSeeds(); break;
    case 'gen:migration':    $taskManager->generateMigration( $artifactName ); break;

    default: $logger->log( 'ERROR', "Unknown Task <$task>." ); break;
  }


