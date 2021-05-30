<?php

namespace Drupal\custom_media_types\Plugin\media\Source;

use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;

/**
 * External image entity media source.
 *
 * @see \Drupal\file\FileInterface
 *
 * @MediaSource(
 *   id = "callout",
 *   label = @Translation("Callout/Quotes"),
 *   description = @Translation("Use callout/quotes."),
 *   allowed_field_types = {"text_long"},
 *   thumbnail_alt_metadata_attribute = "alt",
 *   default_thumbnail_filename = "no-thumbnail.png",
 *   forms = {
 *     "media_library_add" = "\Drupal\custom_media_types\Form\CalloutMediaForm",
 *   }
 * )
 */
class Callout extends MediaSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'attribution' => $this->t('Attribution'),
      'description' => $this->t('Quote'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    $media_data = $this->getSourceFieldValue($media);
    switch ($attribute_name) {
      case 'default_name':
        return $media_data;

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
   *   The callout id if from the source_field if found. False otherwise.
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

}
