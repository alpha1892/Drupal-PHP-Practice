# xml_migration 
* This module consists of migration of article contents, taxonomy, images and users. Even having the custom process plugins.
* Migrating contents from xml file.
* Migration divides into 3 steps : 
   - source : consists source file details
   - process : Mapping of system fields with source file fields
   - destination : Your target entity.

# File Structure
 * config
   - optional
     - All Config Files (if needed)
 * migrations
   - YML Files needed for migration.
 * sources (custom name) Not required to store inside your custom module. Files can be placed in private or public folders too or you can directly pull the files from remote host.
   - XML Files 
 * src
   * Plugin
     * migrate
       * Process
         - Files of your custom process plugins.
 - custom_modules.info.yml
 
 # Requirements
 * Migrate
 * Migrate Tools (migrate_tools)
 * Migrate Plus (migrate_plus)

# Composer Commands to install migrate contrib modules.
* composer require drupal/migrate_tools
* composer require drupal/migrate_plus

# Drush Commands
* To Check status of all migration id - drush ms 
* Only one migration id - drush ms migration_id
* For migration contents - drush mim migration_id
* For rollback migration - drush mr migration_id
* For reset migration to idle - drush mrs migration_id
