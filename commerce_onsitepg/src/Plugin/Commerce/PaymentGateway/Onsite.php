<?php

namespace Drupal\commerce_onsitepg\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the On-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "example_onsite",
 *   label = "Example (On-site)",
 *   display_label = "Example",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_onsitepg\PluginForm\Onsite\PaymentMethodAddForm",
 *     "edit-payment-method" = "Drupal\commerce_payment\PluginForm\PaymentMethodEditForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class Onsite extends OnsitePaymentGatewayBase implements OnsiteInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'api_key' => ''
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

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
    $this->configuration['api_key'] = $values['api_key'];
  }



  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    kint(['createPayment', $payment, $capture]);
    die();
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);
    $amount = $payment->getAmount();

    // Perform verifications related to billing address, payment currency, etc.
    // Throw exceptions as needed.
    // See \Drupal\commerce_payment\Exception for the available exceptions.

    // Perform the create payment request here, throw an exception if it fails.
    // Remember to take into account $capture when performing the request.
    $payment_method_token = $payment_method->getRemoteId();
    // The remote ID returned by the request.
    $remote_id = '123456';

    $next_state = $capture ? 'completed' : 'authorization';
    $payment->setState($next_state);
    $payment->setRemoteId($remote_id);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    kint(['createPaymentMethod', $payment_method, $payment_details]);
    die();
    $required_keys = [
      // The expected keys are payment gateway specific and usually match
      // the PaymentMethodAddForm form elements. They are expected to be valid.
      'type', 'number', 'expiration',
    ];
    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new \InvalidArgumentException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }

    // If the remote API needs a remote customer to be created.
    $owner = $payment_method->getOwner();
    if ($owner && $owner->isAuthenticated()) {
      $customer_id = $this->getRemoteCustomerId($owner);
      // If $customer_id is empty, create the customer remotely and then do
      // $this->setRemoteCustomerId($owner, $customer_id);
      // $owner->save();
    }

    // Perform the create request here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    // You might need to do different API requests based on whether the
    // payment method is reusable: $payment_method->isReusable().
    // Non-reusable payment methods usually have an expiration timestamp.
    $payment_method->card_type = $payment_details['type'];
    // Only the last 4 numbers are safe to store.
    $payment_method->card_number = substr($payment_details['number'], -4);
    $payment_method->card_exp_month = $payment_details['expiration']['month'];
    $payment_method->card_exp_year = $payment_details['expiration']['year'];
    $expires = CreditCard::calculateExpirationTimestamp($payment_details['expiration']['month'], $payment_details['expiration']['year']);
    // The remote ID returned by the request.
    $remote_id = '789';

    $payment_method->setRemoteId($remote_id);
    $payment_method->setExpiresTime($expires);
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    kint(['deletePaymentMethod', $payment_method]);
    die();
    // Delete the remote record here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.

    // Delete the local entity.
    $payment_method->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    kint(['capturePayment', $payment, $amount]);
    die();
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();

    // Perform the capture request here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    $remote_id = $payment->getRemoteId();

    $payment->setState('completed');
    $payment->setAmount($amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    kint(['voidPayment', $payment]);
    die();
    $this->assertPaymentState($payment, ['authorization']);
    // Perform the void request here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    $remote_id = $payment->getRemoteId();

    $payment->setState('authorization_voided');
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    kint(['refundPayment', $payment, $amount]);
    die();
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    // Perform the refund request here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    $remote_id = $payment->getRemoteId();

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->setState('partially_refunded');
    }
    else {
      $payment->setState('refunded');
    }

    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function updatePaymentMethod(PaymentMethodInterface $payment_method) {
    kint(['updatePaymentMethod', $payment_method]);
    die();
    $billing_profile = $payment_method->getBillingProfile();

    // Perform the update request here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
  }

}