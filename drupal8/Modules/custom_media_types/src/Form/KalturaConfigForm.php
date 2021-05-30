<?php

namespace Drupal\custom_media_types\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class KalturaConfigForm for kaltura embeded settings.
 */
class KalturaConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'custom_media_types.kaltura_config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kaltura_config_form_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('custom_media_types.kaltura_config');

    $form['kaltura_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Kaltura Video Embed Configuration.'),
      '#description' => $this->t('For more detail https://knowledge.kaltura.com/embedding-kaltura-media-players-your-site'),
      '#tree' => TRUE,
    ];

    $form['kaltura_config']['partner_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Partner Id'),
      '#description' => $this->t('You can get Partner Id from your Kaltura account. Ex: 811441'),
      '#default_value' => !empty($config->get('partner_id')) ? $config->get('partner_id') : '',
    ];
    $form['kaltura_config']['uiconf_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Uiconf Id'),
      '#description' => $this->t('You can get Uiconf Id from your Kaltura account. Ex: 32783592'),
      '#default_value' => !empty($config->get('uiconf_id')) ? $config->get('uiconf_id') : '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('custom_media_types.kaltura_config');
    $config->set('partner_id', $form_state->getValue(['kaltura_config', 'partner_id']));
    $config->set('uiconf_id', $form_state->getValue(['kaltura_config', 'uiconf_id']));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
