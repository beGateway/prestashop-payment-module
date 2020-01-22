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

require_once _PS_MODULE_DIR_ . 'begateway/lib/BeGateway/lib/BeGateway.php';

class BegatewayModuleFrontController extends ModuleFrontController
{
  public $ssl = true;
  public $display_column_left = false;
  public $display_column_right = false;
  public $currentTemplate = 'module:begateway/views/templates/front/errorpage.tpl';
  public $page_name = 'checkout';
  protected $_transaction;

  public function initHeader()
  {
    $this->context->smarty->assign(array('errors' => array()));
    parent::initHeader();

    $this->setTemplate($this->currentTemplate);
  }

  public function initContent()
  {
    parent::initContent();

    $cart = $this->context->cart;

    if ($cart->nbProducts() <= 0) {
      $this->setErrorTemplate($this->module->l('Your shopping cart is empty'));
    }

    if ($cart->id_customer == 0 || !$this->module->active || Configuration::get('BEGATEWAY_ACTIVE_MODE', false) === false) {
      Tools::redirect('index.php?controller=order&step=1');
    }

    // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
    $authorized = false;
    foreach (Module::getPaymentModules() as $module)
    if ($module['name'] == 'begateway')
    {
      $authorized = true;
      break;
    }

    if (!$authorized) {
      $this->setErrorTemplate($this->module->l('This payment method is not available.'));
      return;
    }

    $customer      = new Customer((int)($cart->id_customer));
    $address       = new Address(intval($cart->id_address_invoice));
    $country       = Country::getIsoById((int)$address->id_country);
    $lang_iso_code = $this->context->language->iso_code;;

    $currency      = new Currency((int)($cart->id_currency));
    $currency_code = trim($currency->iso_code);
    $amount        = $cart->getOrderTotal(true, 3);
    $total         = (float)$cart->getOrderTotal(true, Cart::BOTH);

    $callbackurl = $this->context->link->getModuleLink($this->module->name, 'webhook', array(), true);
    $callbackurl = str_replace('carts.local', 'webhook.begateway.com:8443', $callbackurl);
    $callbackurl = str_replace('app.docker.local:8080', 'webhook.begateway.com:8443', $callbackurl);

    $successurl = $this->context->link->getPageLink(
      'order-confirmation',
      true,
      null,
      array(
        'id_cart' => (int) $cart->id,
        'id_module' => (int) $this->module->id,
        'key' => $customer->secure_key
      )
    );

    $failurl = $this->context->link->getPageLink(
      'order-confirmation',
      true,
      null,
      array(
        'id_cart' => (int) $cart->id,
        'id_module' => (int) $this->module->id,
        'key' => $customer->secure_key
      )
    );

    $state_val = NULL;

    if (in_array($country, array('US','CA'))) {
      $state = new State((int)$address->id_state);
      if (Validate::isLoadedObject($state)) {
        $state_val = $state->iso_code;
      } else {
        $state_val = 'NA';
      }
    }

    $phone = ($address->phone) ? $address->phone : $address->phone_mobile;

    $this->_transaction = new \BeGateway\GetPaymentToken;

    $this->_transaction->money->setCurrency($currency_code);
    $this->_transaction->money->setAmount($amount);
    $this->_transaction->setDescription($this->l('Order No. ').$cart->id);
    $this->_transaction->setTrackingId($cart->id);
    $this->_transaction->setLanguage($lang_iso_code);
    $this->_transaction->setNotificationUrl($callbackurl);
    $this->_transaction->setSuccessUrl($successurl);
    $this->_transaction->setDeclineUrl($failurl);
    $this->_transaction->setFailUrl($failurl);

    $this->_transaction->customer->setFirstName($address->firstname);
    $this->_transaction->customer->setLastName($address->lastname);
    $this->_transaction->customer->setCountry($country);
    $this->_transaction->customer->setAddress($address->address1.' '.$address->address2);
    $this->_transaction->customer->setCity($address->city);
    $this->_transaction->customer->setZip($address->postcode);
    $this->_transaction->customer->setEmail($customer->email);
    $this->_transaction->customer->setPhone($phone);
    $this->_transaction->customer->setState($state_val);

    $this->setPaymentMethod();

    if (Configuration::get('BEGATEWAY_TEST_MODE')) {
      $this->_transaction->setTestMode();
    }

    PrestaShopLogger::addLog(
      'BeGateway::initContent::Token request data: ' . var_export($this->_transaction, true),
      1,
      null,
      'BeGateway Module',
      (int)$this->context->cart->id,
      true
    );

    try {
      $this->module->init_begateway();
      $response = $this->_transaction->submit();
      if (!$response->isSuccess()) {
        throw new \Exception($response->getMessage());
      }

      $this->module->validateOrder(
        $cart->id,
        Configuration::get('BEGATEWAY_STATE_WAITING'),
        $total,
        $this->module->displayName,
        NULL,
        array(),
        (int)$currency->id,
        false,
        $customer->secure_key
      );

      Tools::redirect($response->getRedirectUrl(), '');
    } catch (Exception $e) {
      $this->setErrorTemplate($this->module->l('Error to get a payment token.'));
      return;
    } finally {
      PrestaShopLogger::addLog(
        'BeGateway::initContent::Token response data: ' . var_export($response, true),
        1,
        null,
        'BeGateway Module',
        (int)$this->context->cart->id,
        true
      );
    }
  }

  /**
  * @param      $processingReturnCode
  * @param bool $setTemplate
  */
  protected function setErrorTemplate($processingReturnCode, $setTemplate = true)
  {
    if ($setTemplate) {
      $this->setTemplate("module:begateway/views/templates/front/errorpage.tpl");
    }
    $translation = $this->module->l($processingReturnCode);
    if ($translation === $processingReturnCode) {
      $translation = $this->module->l(
        'An error occurred while processing payment code: ' . $translation
      );
    }
    $this->context->smarty->assign(
      array(
        'errors' => array($translation)
      )
    );
  }
}
