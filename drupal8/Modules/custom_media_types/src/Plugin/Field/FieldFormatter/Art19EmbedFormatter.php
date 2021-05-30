<?php

namespace Drupal\custom_media_types\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_media_types\Plugin\media\Source\Art19;

/**
 * Plugin implementation of the 'art19_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "art19_embed",
 *   label = @Translation("Art19 embed"),
 *   field_types = {
 *     "link", "string"
 *   }
 * )
 */
class Art19EmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'background_color' => '#1B2B32',
      'shadow' => 'false',
      'progress_color' => '#DAD9D6',
      'primary_color' => '#8c1515',
      'allow_share' => 'false',
      'allow_subscribe' => 'false',
      'allow_download' => 'false',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['background_color'] = [
      '#title' => $this->t('Background Color'),
      '#type' => 'textfield',
      '#description' => $this->t('Background color of the player.'),
      '#default_value' => $this->getSetting('background_color'),
    ];

    $elements['progress_color'] = [
      '#title' => $this->t('Progress Bar Color'),
      '#type' => 'textfield',
      '#description' => $this->t('Progress bar color of the player.'),
      '#default_value' => $this->getSetting('progress_color'),
    ];

    $elements['primary_color'] = [
      '#title' => $this->t('Primary Text Color'),
      '#type' => 'textfield',
      '#description' => $this->t('Primary text color of the player.'),
      '#default_value' => $this->getSetting('primary_color'),
    ];

    $elements['shadow'] = [
      '#title' => $this->t('Add Shadow to the player'),
      '#type' => 'radios',
      '#options' => [
        'true' => $this->t('Yes'),
        'false' => $this->t('No'),
      ],
      '#description' => $this->t('Display border shadow with the player.'),
      '#default_value' => $this->getSetting('shadow'),
    ];

    $elements['allow_share'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show share button on the player'),
      '#description' => $this->t('Allow users to share the Media.'),
      '#default_value' => $this->getSetting('allow_share'),
    ];

    $elements['allow_subscribe'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show subscribe button on the player'),
      '#description' => $this->t('Allow users to subscribe to the Media.'),
      '#default_value' => $this->getSetting('allow_subscribe'),
    ];

    $elements['allow_download'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show download button on the player'),
      '#description' => $this->t('Allow users to download the Media.'),
      '#default_value' => $this->getSetting('allow_download'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [
      $this->t('Background Color: @background_color', [
        '@background_color' => $this->getSetting('background_color'),
      ]),
      $this->t('Primary Text Color: @primary_color', [
        '@primary_color' => $this->getSetting('primary_color'),
      ]),
      $this->t('Progress Bar Color: @progress_color', [
        '@progress_color' => $this->getSetting('progress_color'),
      ]),
      $this->t('Add Shadow to the player: @shadow', [
        '@shadow' => $this->getSetting('shadow'),
      ]),
      $this->t('Show share button on the player: @allow_share', [
        '@allow_share' => $this->getSetting('allow_share'),
      ]),
      $this->t('Show subscribe button on the player: @allow_subscribe', [
        '@allow_subscribe' => $this->getSetting('allow_subscribe'),
      ]),
      $this->t('Show download button on the player: @allow_download', [
        '@allow_download' => $this->getSetting('allow_download'),
      ]),
    ];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\media\MediaInterface $media */
    $media = $items->getEntity();

    $element = [];
    if (($source = $media->getSource()) && $source instanceof Art19) {
      /** @var \Drupal\media\MediaTypeInterface $item */
      foreach ($items as $delta => $item) {
        if ($source_id = $source->getMetadata($media, 'source_id')) {
          $element[$delta] = [
            '#theme' => 'media_art19',
            '#source_id' => $source_id,
            '#background_color' => $this->getSetting('background_color'),
            '#shadow' => $this->getSetting('shadow'),
            '#progress_color' => $this->getSetting('progress_color'),
            '#primary_color' => $this->getSetting('primary_color'),
            '#allow_share' => $this->getSetting('allow_share'),
            '#allow_subscribe' => $this->getSetting('allow_subscribe'),
            '#allow_download' => $this->getSetting('allow_download'),
          ];
        }
      }
    }

    $element['#attached']['library'][] = 'custom_media_types/art19_webp';
    return $element;
  }

}
