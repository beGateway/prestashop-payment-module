<?php
/*
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author BeGateway <techsupport@ecomcharge.com>
*  @copyright  2018 eComCharge
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

require_once(_PS_MODULE_DIR_ . 'begateway/controllers/front/begateway.php'); // Base Controller

class BegatewayWebhookModuleFrontController extends ModuleFrontController {

  public $contentOnly = true;
  public $ssl = true;
  public $display_column_left = false;
  public $display_column_right = false;

  /**
  * Prevent displaying the maintenance page
  *
  * @return void
  */
  protected function displayMaintenancePage()
  {
  }

  public function initContent() {
    die($this->executeWebhook());
  }

  public function executeWebhook() {
    $webhook = new \BeGateway\Webhook;
    $this->module->init_begateway();

    $cart = new Cart((int)$webhook->getTrackingId());

    PrestaShopLogger::addLog(
      'BeGateway::initContent::Webhook data: ' . var_export($webhook->getResponseArray(), true),
      1,
      null,
      'BeGateway Module',
      (int)$cart->id,
      true
    );

    if (!Validate::isLoadedObject($cart))
      return 'Error to load cart';

		$orderId = (int)Order::getIdByCartId((int)$cart->id);
		$order    = new Order($orderId);
    $currency = new Currency((int)($cart->id_currency));

    if (!Validate::isLoadedObject($order))
      return 'Error to load order';

    if (!$webhook->isAuthorized())
      return 'Not authorized';

    if (!$webhook->isSuccess() && !$webhook->isFailed())
      return 'Not final status';

    $amount = new \BeGateway\Money;

    $currency_code = trim($currency->iso_code);

    $amount->setCurrency($currency_code);
    $amount->setAmount($cart->getOrderTotal(true, 3));

    if ($webhook->getResponse()->transaction->currency != $currency_code ||
        $webhook->getResponse()->transaction->amount != $amount->getCents()) {
      return 'Incorrect paid amount';
    }

    $status = $webhook->isSuccess() ? Configuration::get('PS_OS_PAYMENT') : Configuration::get('PS_OS_ERROR');
    $order->setCurrentState($status);

    $this->saveOrderTransactionData($webhook->getUid(), $webhook->getPaymentMethod(), $orderId);

    return 'OK';
  }

  /**
     * Retrieves the OrderPayment object, created at validateOrder. And add transaction data.
     *
     * @param string $transactionId
     * @param string $paymentMethod
     * @param int    $orderId
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function saveOrderTransactionData($transactionId, $paymentMethod, $orderId)
    {
        // retrieve ALL payments of order.
        // if no OrderPayment objects is retrieved in the collection, do nothing.
        $order = new Order((int) $orderId);
        $collection = OrderPayment::getByOrderReference($order->reference);
        if (count($collection) > 0) {
            $orderPayment = $collection[0];
            // for older versions (1.5) , we check if it hasn't been filled yet.
            if (!$orderPayment->transaction_id) {
                $orderPayment->transaction_id = $transactionId;
                $orderPayment->payment_method = $paymentMethod;
                $orderPayment->update();
            }
        }
    }
}
