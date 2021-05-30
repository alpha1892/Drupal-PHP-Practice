<?php

namespace Drupal\site_config\Utils;

/**
 * SiteConfigUtils class to handle static data.
 */
class SiteConfigUtils {

  /**
   * Function to check to Node exist.
   */
  public static function checkNodeExists($nid) {
    try {
      $query = \Drupal::database()->select('node_field_data', 'n');
      $query->fields('n', ['nid'])
        ->condition('n.nid', $nid, '=')
        ->range(0, 1);
      $result = $query->execute()->fetchAll();
      if (empty($result)) {
        return FALSE;
      }
      return TRUE;
    }
    catch (\Exception $e) {
      \Drupal::logger('site-config')->error($e->getMessage());
    }
  }

}
