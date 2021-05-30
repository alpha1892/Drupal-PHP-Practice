<?php

namespace Drupal\custom_media_types\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\custom_media_types\Plugin\media\Source\KalturaEmbed;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'kaltura_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "kaltura_embed_format",
 *   label = @Translation("Kaltura Embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class KalturaEmbedFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Configuration data.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new CommentDefaultFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object.
   */
  public function __construct($plugin_id,
  $plugin_definition,
  FieldDefinitionInterface $field_definition,
  array $settings,
  $label,
  $view_mode,
  array $third_party_settings,
  ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->configFactory = $config_factory->get('custom_media_types.kaltura_config');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
  array $configuration,
  $plugin_id,
  $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public static function defaultSettings() {
    return [
      'width' => '720',
      'height' => '450',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritDoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Width of embedded player. Suggested value: 400'),
    ];

    $elements['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $this->getSetting('height'),
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Height of embedded player. Suggested value: 285'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [
      $this->t('Width: @width', [
        '@width' => $this->getSetting('width'),
      ]),
      $this->t('Height: @height', [
        '@height' => $this->getSetting('height'),
      ]),
    ];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\media_entity\MediaInterface $media_entity */
    $media = $items->getEntity();
    $partner_id = $this->configFactory->get('partner_id');
    $uiconf_id = $this->configFactory->get('uiconf_id');
    $flash_vars = [
      'controlBarContainer.plugin' => TRUE,
      'largePlayBtn.plugin' => TRUE,
      'loadingSpinner.plugin' => FALSE,
      'autoMute' => FALSE,
      'autoPlay' => FALSE,
      'loop' => FALSE,
      'disableOnScreenClick' => FALSE,
      'sourceSelector.plugin' => TRUE,
      'sourceSelector.switchOnResize' => FALSE,
      'sourceSelector.simpleFormat' => TRUE,
      'sourceSelector.displayMode' => 'sizebitrate',
      'playbackRateSelector.plugin' => TRUE,
      'playbackRateSelector.position' => 'after',
      'playbackRateSelector.loadingPolicy' => 'onDemand',
      'playbackRateSelector.defaultSpeed' => '1',
      'playbackRateSelector.speeds' => '.5,.75,1,1.5,2',
      'mediaProxy.preferedFlavorBR' => '1600',
      'infoScreen.plugin' => TRUE,
      'playPauseBtn.plugin' => TRUE,
      'closedCaptions.plugin' => TRUE,
      'streamerType' => 'auto',
      'inlineScript' => FALSE,
    ];

    $element = [];
    if (($source = $media->getSource()) && $source instanceof KalturaEmbed) {
      /** @var \Drupal\media\MediaTypeInterface $item */
      foreach ($items as $delta => $item) {
        if ($source_id = $source->getMetadata($media, 'source_id')) {
          $element[$delta] = [
            '#theme' => 'media_kaltura_embed',
            '#partnerId' => $partner_id,
            '#uiConfId' => $uiconf_id,
            '#entryId' => $source_id,
            '#flashVars' => $flash_vars,
            '#width' => $this->getSetting('width'),
            '#height' => $this->getSetting('height'),
          ];
        }
      }
    }

    return $element;
  }

}
