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

class BegatewayWebhookModuleFrontController extends BegatewayModuleFrontController {

  public $currentTemplate = 'module:begateway/views/templates/front/notify.tpl';
  public $contentOnly = true;

  public function initContent() {
    $webhook = new \BeGateway\Webhook;

    $cart = new Cart((int)$webhook->getTrackingId());
		$id_order = Order::getIdByCartId((int)$cart->id);
		$order = new Order((int)$id_order);

    if (!Validate::isLoadedObject($cart) || !Validate::isLoadedObject($order)) {
      PrestaShopLogger::addLog(
          'BeGateway::initContent::Webhook: Error to load cart data',
          1,
          null,
          'BeGateway Module',
          null,
          true
      );
      $this->setMessage('Critical error to load order cart data');
      return;
    }

    $module = new Begateway;
    $module->init_begateway();

    if ($webhook->isAuthorized() &&
        ($webhook->isSuccess() || $webhook->isFailed())
    ) {
      $status = $webhook->getStatus();
      $currency = $webhook->getResponse()->transaction->currency;
      $amount = new \BeGateway\Money;
      $amount->setCurrency($Currency);
      $amount->setCents($webhook->getResponse()->transaction->amount);
      $transId = $webhook->getUid();

      $customer = new Customer((int)$cart->id_customer);

      if (!Validate::isLoadedObject($customer)) {
        PrestaShopLogger::addLog(
            'BeGateway::initContent::Webhook: Error to load customer data',
            1,
            null,
            'BeGateway Module',
            (int)$cart->id,
            true
        );
        $this->setMessage('Critical error to load customer');
        return;
      }

      PrestaShopLogger::addLog(
        'BeGateway::initContent::Webhook data: ' . var_export($webhook, true),
        1,
        null,
        'BeGateway Module',
        (int)$cart->id,
        true
      );

      $payment_status = $webhook->isSuccess() ? Configuration::get('PS_OS_PAYMENT') : Configuration::get('PS_OS_ERROR');

      $module->validateOrder(
        (int)$id_order,
        $payment_status,
        $amount->getAmount(),
        $module->displayName,
        $webhook->getMessage(),
        array('transaction_id' => $transId),
        NULL,
        false,
        $customer->secure_key
      );

      $order_new = (empty($module->currentOrder)) ? $id_order : $module->currentOrder;

      Db::getInstance()->Execute('
        INSERT INTO '._DB_PREFIX_.'begateway_transaction (type, id_begateway_customer, id_cart, id_order,
          uid, amount, status, currency, date_add)
          VALUES ("'.$webhook->getResponse()->transaction->type.'", '.$cart->id_customer.', '.$id_order.', '.$order_new.', "'.$transId.'", '.$amount->getAmount().', "'.$status.'", "'.$currency.'", NOW())');

      $this->setMessage('OK');;
    }
  }

  public function setMessage($message, $statusCode = 200) {
    $response = new Response($message, $statuscode);
    $response->send();
    die;
  }
}
