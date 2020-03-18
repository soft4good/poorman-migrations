# Poorman Migrations

A lightweight, standalone migrations manager for MySQL databases with support for multiple environments. Featuring base schemas and seeds.

### Installation

```bash
$ composer require soft4good/poorman-migrations
$ vendor/bin/poorman-migrations setup
```

This will add a `.env` file and a `db/` directory to your project root.

If you have multiple environments you can create env files for each like: `.env.development`, `.env.staging`, `.env.production`, etc...

**IMPORTANT:** Make sure to _.gitignore_ your `.env` files so you don't leak sensitive info to the repository.

#### The `db/` directory contains three subdirs:

#### `db/migrations/` 
  
Migrations are SQL scripts with instructions to modify the database structure.

Migration filename syntax: `<timestamp>_<name>.sql`

Auto-generate migrations using the `gen:migration` task (see usage below)

**NOTE:** You may be tempted to add INSERT queries (seed data) on the migration scripts. PLEASE DON'T DO THIS, seed data for the specific environments should be placed in the SQL scripts found in the `seeds/` directory.
On reset operations, the migration scripts are ran immediately after the database structure is reset and there is no data, placing seed data in these files may fail foreign key checks rendering the migration invalid.

#### `db/seeds/` 

Seeds are SQL scripts with instructions to populate your database with seed data.

Here you can add seed data by environment, the default is local (`local.sql`).

#### `db/schemas`

Schemas are the base/initial structure of your database, these scripts are supposed to contain structure-only code.

Here you can add schemas by environment, the default is local (`local.sql`).

### Usage

Poorman Migrations will expose `vendor/bin/poorman-migrations`. Usage is as follows:

Syntax:
```
$ ./poorman <task> [<environment>|<artifact_name>]
```
Where:

**\<task\>:** setup | migrate | reset | init | seed | gen:migration

**\<environment\>:** development | staging | production | etc...

**\<artifact_name\>:** Required for `<task=gen:migration>`, the name of the migration (e.g. 'new_user_fields')

#### setup

You should run this task as part of the installation step. It will interactively create a `.env` file with the credentials to the database provided by you and the base `db` directory.

#### init

Loads the DB schema by executing the sql script on `db/schemas` specific to the specified environment, default is `local`.

So for example: 

```bash
$ vendor/bin/poorman-migrations init
```

will run `db/schemas/local.sql` 

while:
```bash
$ vendor/bin/poorman-migrations development
```

will run `db/schemas/development.sql`.

#### seed

Loads database seeds from the `db/seeds` directory for the specified environment, default is `local`.

So for example: 

```bash
$ vendor/bin/poorman-migrations seed
```

will run `db/seeds/local.sql` 

while:
```bash
$ vendor/bin/poorman-migrations seed development
```

will run `db/seeds/development.sql`.

#### migrate

Will execute database migrations found in the `db/migrations` directory. Migrations are ran in order taking into account the timestamp in the filename. 

We keep track of already migrated scripts by looking at the `schema_migrations` table in the database, this table is created by the `migrate` task if it doesnt exist.

e.g.
```bash
$ vendor/bin/poorman-migrations migrate
```

You can also specify an environment to migrate as with the other tasks.


#### gen:migration

This task will create an empty migration file with the `artifact_name` specified.

e.g.
```bash
$ vendor/bin/poorman-migrations gen:migration create_users_table
```

This example will create a file `db/migrations/<timestamp>_create_users_table.sql`.

You can now add your sql statements to this SQL file and it will be executed the next time you run the `migrate` task.

#### reset

Doing a reset will run these tasks in the following order:

* `init`
* `migrate`
* `seed`

