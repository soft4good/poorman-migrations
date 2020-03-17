<?php

// DB
define( 'DB_DSN', "mysql:host=$_ENV[DB_HOST];dbname=$_ENV[DB_NAME]" );
define( 'DB_HOST', $_ENV['DB_HOST'] );
define( 'DB_NAME', $_ENV['DB_NAME'] );
define( 'DB_USERNAME', $_ENV['DB_USERNAME'] );
define( 'DB_PASSWORD', $_ENV['DB_PASSWORD'] );

define( 'MIGRATIONS_DIR' , "db/migrations" );
define( 'SEEDS_DIR' , "db/seeds" );
define( 'SCHEMAS_DIR' , "db/schemas" );

define( 'SCHEMA_SCRIPT_PATH' , SCHEMAS_DIR . "/$_ENV[APP_ENV].sql" );
define( 'SEEDS_SCRIPT_PATH' , SEEDS_DIR . "/$_ENV[APP_ENV].sql" );