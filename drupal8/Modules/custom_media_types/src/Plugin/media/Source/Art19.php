<?php

namespace Drupal\custom_media_types\Plugin\media\Source;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * External image entity media source.
 *
 * @see \Drupal\file\FileInterface
 *
 * @MediaSource(
 *   id = "art19",
 *   label = @Translation("Art19"),
 *   description = @Translation("Add Art19 resource."),
 *   allowed_field_types = {"string"},
 *   default_thumbnail_filename = "art19.png",
 *   forms = {
 *     "media_library_add" = "\Drupal\custom_media_types\Form\Art19MediaLibraryAddForm",
 *   }
 * )
 */
class Art19 extends MediaSourceBase {

  /**
   * Base url for art19 rss.
   */
  const ART19_RSS_BASE_URL = 'http://rss.art19.com/episodes';

  /**
   * Guzzle Http client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * File System service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannel|\Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              EntityFieldManagerInterface $entity_field_manager,
                              FieldTypePluginManagerInterface $field_type_manager,
                              ConfigFactoryInterface $config_factory,
                              Client $http_client,
                              FileSystem $file_system,
                              LoggerChannelFactory $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $this->httpClient = $http_client;
    $this->fileSystem = $file_system;
    $this->logger = $logger_factory->get('art19_media');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_field' => 'episode_id',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('file_system'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'episode_id' => $this->t('Episode Id'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    switch ($attribute_name) {
      case 'source_id':
        return $this->getSourceId($media);

      case 'thumbnail_uri':
        if (!empty($thumbnail_uri = $this->getArt19ThumbnailUri($media))) {
          return $thumbnail_uri;
        }
        else {
          $default_thumbnail_filename = $this->pluginDefinition['default_thumbnail_filename'];
          return $this->configFactory->get('media.settings')->get('icon_base_uri') . '/' . $default_thumbnail_filename;
        }

      case 'default_name':
        return TRUE;

      default:
        return parent::getMetadata($media, $attribute_name);

    }
  }

  /**
   * Returns the source field value for Media.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return string|bool
   *   The art19 id if from the source_field if found. False otherwise.
   */
  protected function getSourceId(MediaInterface $media) {
    $source_field = $this->getSourceFieldDefinition($media->bundle->entity);
    $field_name = $source_field->getName();

    if ($media->hasField($field_name)) {
      $property_name = $source_field->getFieldStorageDefinition()->getMainPropertyName();
      return $media->{$field_name}->{$property_name};
    }
    return FALSE;
  }

  /**
   * Helper function to fetch art19 Thumbnail uri.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return string
   *   Uri to cover image for Art19.
   */
  protected function getArt19ThumbnailUri(MediaInterface $media) {
    if ($source_id = $this->getSourceId($media)) {
      try {
        $response = $this->httpClient->get(self::ART19_RSS_BASE_URL . '/' . $source_id, [
          'headers' => [
            'accept' => 'application/json',
          ],
        ]);
      }
      catch (RequestException $e) {
        $this->logger->warning($e->getMessage());
        return NULL;
      }

      if ($response->getStatusCode() === 200) {
        $response = Json::decode($response->getBody()->getContents());
        $remote_thumbnail_url = $response['content']['cover_image'];
        $directory = "public://art19_thumbnails";
        $local_thumbnail_uri = "$directory/" . Crypt::hashBase64($remote_thumbnail_url) . '.' . pathinfo($remote_thumbnail_url, PATHINFO_EXTENSION);

        // If the local thumbnail already exists, return its URI.
        if (file_exists($local_thumbnail_uri)) {
          return $local_thumbnail_uri;
        }

        if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
          $this->logger->warning('Could not prepare thumbnail destination directory @dir for oEmbed media.', [
            '@dir' => $directory,
          ]);
          return NULL;
        }

        try {
          $response = $this->httpClient->get($remote_thumbnail_url);
          if ($response->getStatusCode() === 200) {
            $this->fileSystem->saveData((string) $response->getBody(), $local_thumbnail_uri, FileSystemInterface::EXISTS_REPLACE);
            return $local_thumbnail_uri;
          }
        }
        catch (RequestException $e) {
          $this->logger->warning($e->getMessage());
        }
        catch (FileException $e) {
          $this->logger->warning('Could not download remote thumbnail from {url}.', [
            'url' => $remote_thumbnail_url,
          ]);
        }
        return NULL;
      }
      else {
        return NULL;
      }
    }

    return NULL;
  }

}
