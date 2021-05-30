<?php

namespace Drupal\xml_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Passes the source value to a date_time_conversion.
 *
 * The date_time_conversion process plugin allows simple converting date to timestamp.
 *
 * Examples:
 *
 * @code
 * process:
 *   destination_field:
 *     plugin: date_time_conversion
 *     source: source_field
 * @endcode
 *
 * An example where is a date format convert into timestamp:
 *
 * @code
 * process:
 *   destination_field:
 *     plugin: date_time_conversion
 *     source: source_field
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "date_time_conversion"
 * )
 */
class DateTimeConversion extends ProcessPluginBase {


  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return strtotime(trim($value));
  }

}
