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
    ];

    return $form;
  }

}
