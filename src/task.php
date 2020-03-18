<?php
  require_once __DIR__ . '/bootstrap.php';

  if ( $argc < 2 ) {
    die("Syntax: task.php <task> [<environment>|<artifact_name>] \n

<task> = setup | migrate | reset | init | seed | gen:migration
<environment> = development | staging | production
<artifact_name> = Required for <task=gen:migration>, the name of the migration (e.g. 'new_user_fields')
    ");
  }

  $task = $argv[1];
  $environment = '';
  $isSetup = false;
  if ( strpos( $task, 'gen:' ) !== false ) {
    $artifactName = isset($argv[2]) ? $argv[2] : '';
  }
  elseif( $task === 'setup' ) {
    $environment = isset($argv[2]) ? $argv[2] : 'local';
    $isSetup = true;
  }
  else {
    $environment = isset($argv[2]) ? $argv[2] : $environment;
  }

  if ( $environment && !$isSetup ) { // overwrite environment
    try {
      $dotenv = new Dotenv\Dotenv(".env.$environment" );
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
    case 'setup':            $taskManager->setup( $environment ); break;
    case 'migrate':          $taskManager->importMigrations(); break;
    case 'reset':            $taskManager->reset(); break;
    case 'init':             $taskManager->importSchema(); break;
    case 'seed':             $taskManager->importSeeds(); break;
    case 'gen:migration':    $taskManager->generateMigration( $artifactName ); break;

    default: $logger->log( 'ERROR', "Unknown Task <$task>." ); break;
  }


