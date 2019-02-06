<?php

namespace Drupal\commerce_offsitepg\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_payment\Exception\PaymentGatewayException;

/**
 * Provides offsite Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "offsitepg_redirect_checkout",
 *   label = @Translation("OffsitePG (Redirect to offsite)"),
 *   display_label = @Translation("OffsitePG"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_offsitepg\PluginForm\RedirectCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa",
 *   },
 * )
 */
class RedirectCheckout extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'private_key' => '',
        'api_key' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private key'),
      '#description' => $this->t('This is the private key from the Quickpay manager.'),
      '#default_value' => $this->configuration['private_key'],
      '#required' => TRUE,
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('The API key for the same user as used in Agreement ID.'),
      '#default_value' => $this->configuration['api_key'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['private_key'] = $values['private_key'];
    $this->configuration['api_key'] = $values['api_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    if ($request->something_that_marks_a_failure) {
      throw new PaymentGatewayException('Payment failed!');
    }

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'completed',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'remote_id' => $request->request->get('remote_id'),
      'remote_state' => $request->request->get('remote_state'),
    ]);

    $payment->save();
  }

}