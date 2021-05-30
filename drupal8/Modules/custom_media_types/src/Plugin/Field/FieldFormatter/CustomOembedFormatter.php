<?php

namespace Drupal\custom_media_types\Plugin\Field\FieldFormatter;

use Drupal\Core\Url;
use Drupal\media\OEmbed\Resource;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\OEmbed\ResourceException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\media\Plugin\Field\FieldFormatter\OEmbedFormatter;

/**
 * Plugin implementation of the 'alshaya oembed' formatter.
 *
 * @FieldFormatter(
 *   id = "custom_oembed",
 *   label = @Translation("Custom oEmbed"),
 *   field_types = {
 *     "link",
 *     "string",
 *     "string_long",
 *   },
 * )
 */
class CustomOembedFormatter extends OEmbedFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'responsive' => TRUE,
      'width' => '854',
      'height' => '480',
      'autoplay' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $width = $this->getSetting('width');
    $height = $this->getSetting('height');

    foreach ($items as $delta => $item) {
      $main_property = $item->getFieldDefinition()->getFieldStorageDefinition()->getMainPropertyName();
      $value = $item->{$main_property};

      if (empty($value)) {
        continue;
      }
      try {
        $resource_url = $this->urlResolver->getResourceUrl($value, $width, $height);
        $resource = $this->resourceFetcher->fetchResource($resource_url);
      }
      catch (ResourceException $exception) {
        $this->logger->error("Could not retrieve the remote URL (@url).", ['@url' => $value]);
        continue;
      }

      if ($resource->getType() === Resource::TYPE_VIDEO ||
      $resource->getType() === Resource::TYPE_RICH) {
        $url = Url::fromRoute('media.oembed_iframe', [], [
          'query' => [
            'url' => $value,
            'max_width' => $width,
            'max_height' => $height,
            'hash' => $this->iFrameUrlHelper->getHash($value, $width, $height),
          ],
        ]);

        $domain = $this->config->get('iframe_domain');
        if ($domain) {
          $url->setOption('base_url', $domain);
        }
        // Render videos and rich content in an iframe for security reasons.
        // @see: https://oembed.com/#section3
        $element[$delta] = [
          '#type' => 'html_tag',
          '#tag' => 'iframe',
          '#attributes' => [
            'src' => $url->toString(),
            'frameborder' => 0,
            'scrolling' => FALSE,
            'allowtransparency' => TRUE,
            'allowfullscreen' => 'allowfullscreen',
            'width' => $width ?: $resource->getWidth(),
            'height' => $height ?: $resource->getHeight(),
            'class' => ['custom-media-oembed-content'],
          ],
        ];

        // An empty title attribute will disable title inheritance, so only
        // add it if the resource has a title.
        $title = $resource->getTitle();
        if ($title) {
          $element[$delta]['#attributes']['title'] = $title;
        }
        $element[$delta] = [
          '#type' => 'container',
          '#attributes' => ['class' => [Html::cleanCssIdentifier(sprintf('custom-oembed-field-provider'))]],
          'children' => $element[$delta],
        ];
        // For responsive videos, wrap each field item in it's own container.
        if ($this->getSetting('responsive')) {
          $element[$delta]['#attached']['library'][] = 'custom_media_types/responsive-video';
          $element[$delta]['#attributes']['class'][] = 'custom-oembed-responsive-video';
        }

        CacheableMetadata::createFromObject($resource)
          ->addCacheTags($this->config->getCacheTags())
          ->applyTo($element[$delta]);
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['autoplay'] = [
      '#title' => $this->t('Autoplay'),
      '#type' => 'checkbox',
      '#description' => $this->t('Autoplay the videos.'),
      '#default_value' => $this->getSetting('autoplay'),
    ];
    $elements['responsive'] = [
      '#title' => $this->t('Responsive Video'),
      '#type' => 'checkbox',
      '#description' => $this->t("Make the video fill the width of it's container, adjusting to the size of the user's screen."),
      '#default_value' => $this->getSetting('responsive'),
    ];
    // Loosely match the name attribute so forms which don't have a field
    // formatter structure (such as the WYSIWYG settings form) are also matched.
    $responsive_checked_state = [
      'visible' => [
        [
          ':input[name*="responsive"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $elements['width'] = [
      '#title' => $this->t('Width'),
      '#type' => 'number',
      '#field_suffix' => 'px',
      '#default_value' => $this->getSetting('width'),
      '#required' => TRUE,
      '#size' => 20,
      '#states' => $responsive_checked_state,
    ];
    $elements['height'] = [
      '#title' => $this->t('Height'),
      '#type' => 'number',
      '#field_suffix' => 'px',
      '#default_value' => $this->getSetting('height'),
      '#required' => TRUE,
      '#size' => 20,
      '#states' => $responsive_checked_state,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $dimensions = $this->getSetting('responsive') ?
    $this->t('Responsive') :
    $this->t('@widthx@height', [
      '@width' => $this->getSetting('width'),
      '@height' => $this->getSetting('height'),
    ]);
    $summary[] = $this->t('Embedded Video (@dimensions@autoplay).', [
      '@dimensions' => $dimensions,
      '@autoplay' => $this->getSetting('autoplay') ? $this->t(', autoplaying') : '',
    ]);

    return $summary;
  }

}
