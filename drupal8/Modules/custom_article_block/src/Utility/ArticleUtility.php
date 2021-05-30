<?php

namespace Drupal\custom_article_block\Utility;

/**
 * ArticleUtility Class.
 */
class ArticleUtility {

  /**
   * Function to get node id by url path.
   *
   * @param mixed $current_path
   *   Request instance.
   *
   * @return int
   *   Id.
   */
  public static function getIdByPath($current_path) {
    $path = $current_path->getPathInfo();
    $url_alias = \Drupal::service('path.alias_manager')->getPathByAlias($path);
    if (preg_match('/node\/(\d+)/', $url_alias, $matches)) {
      $nid = $matches[1];
    }
    if (!empty($nid)) {
      return (int) $nid;
    }
  }

}
