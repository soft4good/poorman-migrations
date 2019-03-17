# Migrations

**Migrations are .sql scripts with instructions to modify the database structure.**

Migration filename syntax: `<timestamp>_<name>.sql`

AUto-Generate migrations using: php src/task.php gen:migration `<migration_name>`

NOTE: You may be tempted to add insert queries (seed data) on the migration scripts. PLEASE DON'T DO THIS, seed data for the specific environments should be placed in the .sql scripts found in ../seeds/.
On reset operations, the migration scripts are ran immediately after the database structure is reset and there is no data, placing seed data in these files may fail foreign key checks rendering the migration invalid.
