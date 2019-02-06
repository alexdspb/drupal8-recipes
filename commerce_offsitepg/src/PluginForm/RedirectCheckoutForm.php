<?php

namespace Drupal\commerce_offsitepg\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class RedirectCheckoutForm extends PaymentOffsiteForm {

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $configuration = $this->getConfiguration();

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    $data['version'] = 'v10';
    $data['private_key'] = $configuration['private_key'];
    $data['api_key'] = $configuration['api_key'];

    return $this->buildRedirectForm(
      $form,
      $form_state,
      'https://payment.quickpay.net',
      $data,
      PaymentOffsiteForm::REDIRECT_POST
    );
  }
}