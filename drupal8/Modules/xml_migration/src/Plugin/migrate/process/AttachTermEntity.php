<?php

namespace Drupal\xml_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Passes the source value to a attach_term_entity.
 *
 * The attach_term_entity process plugin allows to attach the term id.
 *
 * Examples:
 *
 * @code
 * process:
 *   destination_field:
 *     plugin: attach_term_entity
 *     migrated_type: migrated_type_field 
 *     source: source_field
 * @endcode
 *
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "attach_term_entity"
 * )
 */
class AttachTermEntity extends ProcessPluginBase implements ContainerFactoryPluginInterface {
  /**
   * The database connection instance.
   */
  protected $dbConnection;

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param mixed $migration
   *
   * @return static
   */
  public static function create(ContainerInterface $container,
  array $configuration,
  $plugin_id,
  $plugin_definition,
  MigrationInterface $migration = NULL) {
    $instance = new static ($configuration, $plugin_id, $plugin_definition, $migration);
    $instance->dbConnection = $container->get('database');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($this->configuration['migrated_type'])) {
      throw new MigrateException('Migrated type entity is missing.');
    }
    $tid = trim($value);
    $table_name = "migrate_map_" . $this->configuration['migrated_type'];
    if (!empty($value)) {
      $query = $this->dbConnection->select($table_name, 'migtb');
      $query->addField('migtb', 'destid1');
      $query->condition('migtb.sourceid1', $tid, '=');
      $result = $query->execute()->fetchCol();
     
      return [
        'target_id' => $result[0],
      ];
    }
  }

}
