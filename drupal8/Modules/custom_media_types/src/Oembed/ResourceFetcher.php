<?php

namespace Drupal\custom_media_types\Oembed;

use Drupal\media\OEmbed\ResourceFetcher as MediaResourceFetcher;
use Drupal\media\OEmbed\Resource;

/**
 * Overrides the oEmbed resources.
 *
 * This class override the media.oembed.resource_fetcher service from the core
 * media module.
 *
 * @see \Drupal\media\OEmbed\ResourceFetcher
 */
class ResourceFetcher extends MediaResourceFetcher {

  /**
   * {@inheritDoc}
   */
  protected function createResource(array $data, $url) {
    if ($data['type'] == Resource::TYPE_VIDEO && $data['provider_name'] == 'YouTube') {
      // Replace the default youtube domain with the no-cookie domain.
      $data['html'] = str_replace('youtube.com/', 'youtube-nocookie.com/', $data['html']);
      // Load the high-res thumbnail.
      $data['thumbnail_url'] = str_replace('/hqdefault.jpg', '/mqdefault.jpg', $data['thumbnail_url']);
    }
    // Create the resource.
    return parent::createResource($data, $url);
  }

}
