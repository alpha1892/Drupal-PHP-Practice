<?php

namespace Drupal\custom_media_types\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class YoutubeApiKeyConfigForm for youtube api settings.
 */
class YoutubeApiKeyConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'custom_media_types.youtube_api_key',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yt_api_key_config_form_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('custom_media_types.youtube_api_key');

    $form['yt_config']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Youtube API Key'),
      '#description' => $this->t('For more detail https://developers.google.com/youtube/v3/getting-started'),
      '#default_value' => !empty($config->get('yt_api_key')) ? $config->get('yt_api_key') : '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('custom_media_types.youtube_api_key');
    $config->set('yt_api_key', $form_state->getValue('api_key'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
