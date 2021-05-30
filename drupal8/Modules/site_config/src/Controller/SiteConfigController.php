<?php

namespace Drupal\site_config\Controller;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\site_config\Utility\SiteConfigUtility;

/**
 * SiteConfigController class to handle the functionality json data of node.
 */
class SiteConfigController extends ControllerBase {

  /**
   * Site API key.
   *
   * @var mixed
   */
  protected $apiKey = NULL;

  /**
   * Constructor to store api-key object.
   */
  public function __construct() {
    $this->apiKey = \Drupal::config('system.site')->get('site_api_key');
  }

  /**
   * Returns response for the show json data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function retrieveJsonData() {
    $path = \Drupal::request()->getpathInfo();
    $arg  = explode('/', $path);
    // Conditional check of both api-key and node Id is valid.
    if (SiteConfigUtility::checkNodeExists($arg[3]) == TRUE
    && (!empty($this->apiKey && $this->apiKey == $arg[2]))) {
      $node_data = Node::load($arg[3]);
      // Return json data.
      return JsonResponse::create($node_data->toArray(), 200, ['Content-Type' => 'application/json']);
    }
    else {
      throw new AccessDeniedHttpException();
    }
  }

}
