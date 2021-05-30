<?php

namespace Drupal\custom_media_types;

use DateInterval;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\Client;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Class VideoDuration.
 */
class VideoDuration {

  use LoggerChannelTrait;

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Configuration data.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Media Id.
   *
   * @var int
   */
  protected $mid;

  /**
   * Constructs VideoDuration Object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object.
   * @param \GuzzleHttp\Client $http_client
   *   The http client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
  ConfigFactoryInterface $config_factory,
  Client $http_client,
  CacheBackendInterface $cache,
  LanguageManagerInterface $language_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory->get('custom_media_types.youtube_api_key');
    $this->client = $http_client;
    $this->cache = $cache;
    $this->lang = $language_manager->getCurrentLanguage()->getId();
  }

  /**
   * Set the media id.
   *
   * @param int $mid
   *   Media id.
   */
  public function setMid($mid) {
    $this->mid = $mid;
    return $this;
  }

  /**
   * Get the media id.
   *
   * @return int
   *   Media ID.
   */
  public function getMid() {
    return $this->mid;
  }

  /**
   * Helper function to get remote video duration.
   *
   * @param string $remote_url
   *   Remote video url.
   *
   * @return string
   *   Call duration
   */
  public function getDuration($remote_url) {
    // YouTube.
    $yt_rx = '/^((?:https?:)?\/\/)?((?:www|m)\.)?((?:youtube\.com|youtu.be))(\/(?:[\w\-]+\?v=|embed\/|v\/)?)([\w\-]+)(\S+)?$/';
    $has_match_youtube = preg_match($yt_rx, $remote_url, $yt_matches);

    // Vimeo.
    $vm_rx = '/(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([‌​0-9]{6,11})[?]?.*/';
    $has_match_vimeo = preg_match($vm_rx, $remote_url, $vm_matches);
    // Check video type.
    if ($has_match_youtube) {
      $video_id = $yt_matches[5];
      preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $remote_url, $match);
      $video_id = $match[1];
      $duration = $this->processCache('youtube', $video_id);

      return $duration;
    }
    elseif ($has_match_vimeo) {
      $video_id = $vm_matches[5];
      $duration = $this->processCache('vimeo', $video_id);

      return $duration;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Get YouTube video duration.
   *
   * @param int $id
   *   YouTube ID.
   *
   * @return string
   *   YouTube Duration.
   */
  public function getYoutubeDuration($id) {
    try {
      $key = $this->configFactory->get('yt_api_key');
      $url = "https://www.googleapis.com/youtube/v3/videos?part=contentDetails&id={$id}&key={$key}";
      $response = $this->client->get($url);
      $code = $response->getStatusCode();
      if ($code == 200) {
        $data = $response->getBody();
        $result = json_decode($data, TRUE);
        if (empty($result['items'][0]['contentDetails'])) {
          return NULL;
        }
        $vinfo = $result['items'][0]['contentDetails'];
        $interval = new DateInterval($vinfo['duration']);
        $duration = $interval->h * 3600 + $interval->i * 60 + $interval->s;

        return gmdate("H:i:s", $duration);
      }
    }
    catch (\Exception $e) {
      $logger = $this->getLogger('custom-media-types');
      $logger->error($e->getMessage());
    }
  }

  /**
   * Get Vimeo video duration.
   *
   * @param int $id
   *   Vimeo ID.
   *
   * @return string
   *   Video Duration
   */
  public function getVimeoDuration($id) {
    try {
      $url = "http://vimeo.com/api/v2/video/$id/json";
      $response = $this->client->get($url);
      $code = $response->getStatusCode();
      if ($code == 200) {
        $data = $response->getBody();
        $result = json_decode($data, TRUE);
        if (empty($result[0]['duration'])) {
          return NULL;
        }
        $duration = $result[0]['duration'];

        return gmdate("H:i:s", $duration);
      }
    }
    catch (\Exception $e) {
      $logger = $this->getLogger('custom-media-types');
      $logger->error($e->getMessage());;
    }
  }

  /**
   * Function to process the cache for video duration.
   */
  public function processCache($type, $id) {
    $cid = $type . "_" . $id . "_" . $this->lang;
    $cached_data = " ";
    if ($cache = $this->cache->get($cid)) {
      $cached_data = $cache->data;
    }
    else {
      $cached_data = ($type == 'youtube') ? $this->getYoutubeDuration($id) : $this->getVimeoDuration($id);
      $tag = "media:$this->mid";
      $this->cache->set($cid, $cached_data, CacheBackendInterface::CACHE_PERMANENT, [$tag]);
    }

    return $cached_data;
  }

}
