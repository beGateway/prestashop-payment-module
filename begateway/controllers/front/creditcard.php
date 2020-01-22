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

class BegatewayCreditcardModuleFrontController extends BegatewayModuleFrontController
{
  public function initContent()
  {
    parent::initContent();
  }

  public function setPaymentMethod() {
    $this->setTransactionType();
    $this->_transaction->addPaymentMethod(new \BeGateway\PaymentMethod\CreditCard);
  }

  public function setTransactionType() {
    if (Configuration::get('BEGATEWAY_TRANS_TYPE_CREDIT_CARD') == 'authorization') {
      $this->_transaction->setAuthorizationTransactionType();
    } else {
      $this->_transaction->setPaymentTransactionType();
    }
  }
}
