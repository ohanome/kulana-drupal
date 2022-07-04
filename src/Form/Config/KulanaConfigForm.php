<?php

namespace Drupal\kulana\Form\Config;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class KulanaConfigForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return [
      'kulana.settings',
    ];
  }

  public function getFormId() {
    return 'kulana_config_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['kulana_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Kulana URL'),
      '#description' => $this->t('The URL of the Kulana instance to check.'),
      '#default_value' => $this->config('kulana.settings')->get('kulana_url') ?? 'https://kulana.ohano.me',
      '#required' => TRUE,
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $baseUrl = $form_state->getValue('kulana_url');
    if (
      empty($baseUrl) ||
      !filter_var($baseUrl, FILTER_VALIDATE_URL) ||
      !str_contains($baseUrl, 'https://')) {
      $form_state->setErrorByName('kulana_url', $this->t('The URL must be a valid HTTPS URL.'));
    }

    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('kulana.settings')
      ->set('kulana_url', $form_state->getValue('kulana_url'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
