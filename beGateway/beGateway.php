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
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

require_once _PS_MODULE_DIR_ . 'begateway/lib/BeGateway/lib/BeGateway.php';

class Begateway extends PaymentModule
{

  /**
 * predefined test account
 *
 * @var array
 */
  protected $presets = array(
    'test' => array(
      'shop_id' => '361',
      'shop_key' => 'b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d',
      'domain_checkout' => 'checkout.begateway.com'
    )
  );

  public function __construct()
  {
    $this->name = 'begateway';
    $this->tab = 'payments_gateways';
    $this->version = '1.7.0';
    $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
    $this->author = 'eComCharge';
    $this->controllers = array('validation');
    $this->need_instance = 1;

    $this->currencies      = true;
    $this->currencies_mode = 'checkbox';
    $this->bootstrap       = true;
    $this->display         = true;

    parent::__construct();

    $this->displayName = $this->l('BeGateway');
    $this->description = $this->l('Accept online payments');
    $this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');

    if (!count(Currency::checkPaymentCurrencies($this->id))) {
      $this->warning = $this->l('No currency has been set for this module.');
    }
  }

  public function install()
  {
    if (Shop::isFeatureActive()) {
        Shop::setContext(Shop::CONTEXT_ALL);
    }

    if (extension_loaded('curl') == false) {
      $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
      return false;
    }

    $language_code = $this->context->language->iso_code;

    Module::updateTranslationsAfterInstall(false);

    Configuration::updateValue('BEGATEWAY_SHOP_ID', $this->presets['test']['shop_id']);
    Configuration::updateValue('BEGATEWAY_SHOP_PASS', $this->presets['test']['shop_key']);
    Configuration::updateValue('BEGATEWAY_TRANS_TYPE_CREDIT_CARD', 'payment');
    Configuration::updateValue('BEGATEWAY_ACTIVE_MODE', false);
    Configuration::updateValue('BEGATEWAY_DOMAIN_CHECKOUT', $this->presets['test']['domain_checkout']);
    Configuration::updateValue('BEGATEWAY_TEST_MODE', true);

    Configuration::updateValue('BEGATEWAY_ACTIVE_CREDIT_CARD', true);
    Configuration::updateValue('BEGATEWAY_ACTIVE_CREDIT_CARD_HALVA', false);
    Configuration::updateValue('BEGATEWAY_ACTIVE_ERIP', false);

    // payment titles
    foreach (Language::getLanguages() as $language) {
      if (Tools::strtolower($language['iso_code']) == 'ru') {
        Configuration::updateValue('BEGATEWAY_TITLE_CREDIT_CARD_'. $language['iso_code'], 'Оплатить онлайн банковской картой');
        Configuration::updateValue('BEGATEWAY_TITLE_CREDIT_CARD_HALVA_'. $language['iso_code'], 'Оплатить онлайн картой Халва');
        Configuration::updateValue('BEGATEWAY_TITLE_ERIP_'. $language['iso_code'], 'Оплатить через ЕРИП');
      } else {
        Configuration::updateValue('BEGATEWAY_TITLE_CREDIT_CARD_'. $language['iso_code'], 'Pay by credit card');
        Configuration::updateValue('BEGATEWAY_TITLE_CREDIT_CARD_HALVA_'. $language['iso_code'], 'Pay by Halva');
        Configuration::updateValue('BEGATEWAY_TITLE_ERIP_'. $language['iso_code'], 'Pay by ERIP');
      }
    }

    $ow_status = Configuration::get('BEGATEWAY_STATE_WAITING');
    if ($ow_status === false)
    {
      $orderState = new OrderState();
    }
    else {
      $orderState = new OrderState((int)$ow_status);
    }

    $orderState->name = array();

    foreach (Language::getLanguages() as $language) {
      if (Tools::strtolower($language['iso_code']) == 'ru') {
        $orderState->name[$language['id_lang']] = 'Ожидание завершения оплаты';
      } else {
        $orderState->name[$language['id_lang']] = 'Awaiting for payment';
      }
    }

    $orderState->send_email  = false;
    $orderState->color       = '#4169E1';
    $orderState->hidden      = false;
    $orderState->module_name = 'begateway';
    $orderState->delivery    = false;
    $orderState->logable     = false;
    $orderState->invoice     = false;
    $orderState->unremovable = true;
    $orderState->save();

    Configuration::updateValue('BEGATEWAY_STATE_WAITING', (int)$orderState->id);

    copy(_PS_MODULE_DIR_ . 'begateway/views/img/logo.gif', _PS_IMG_DIR_ .'os/'.(int)$orderState->id.'.gif');

    return parent::install() &&
      $this->registerHook('backOfficeHeader') &&
      $this->registerHook('payment') &&
      $this->registerHook('paymentOptions') &&
      $this->registerHook('paymentReturn') &&
      $this->installDb();
  }

  public function installDb()
  {
    return (
      Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'begateway_transaction` (
        `id_begateway_transaction` int(11) NOT NULL AUTO_INCREMENT,
        `type` enum(\'payment\',\'refund\',\'authorization\') NOT NULL,
        `id_begateway_customer` int(10) unsigned NOT NULL,
        `id_cart` int(10) unsigned NOT NULL,
        `id_order` int(10) unsigned NOT NULL,
        `uid` varchar(60) NOT NULL,
        `amount` decimal(10,2) NOT NULL,
        `status` enum(\'incomplete\',\'failed\',\'successful\',\'pending\') NOT NULL,
        `currency` varchar(3) NOT NULL,
        `id_refund` varchar(32) ,
        `refund_amount` decimal(10,2),
        `au_uid` varchar(60),
        `token` varchar(100),
        `date_add` datetime NOT NULL,
        PRIMARY KEY (`id_begateway_transaction`),
        KEY `idx_transaction` (`type`,`id_order`,`status`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1')
		);
  }

  public function uninstall()
  {
    Configuration::deleteByName('BEGATEWAY_ACTIVE_MODE');
    Configuration::deleteByName('BEGATEWAY_SHOP_ID');
    Configuration::deleteByName('BEGATEWAY_SHOP_PASS');
    Configuration::deleteByName('BEGATEWAY_TRANS_TYPE_CREDIT_CARD');
    Configuration::deleteByName('BEGATEWAY_DOMAIN_CHECKOUT');
    Configuration::deleteByName('BEGATEWAY_TEST_MODE');
    Configuration::deleteByName('BEGATEWAY_ACTIVE_CREDIT_CARD');
    Configuration::deleteByName('BEGATEWAY_ACTIVE_CREDIT_CARD_HALVA');
    Configuration::deleteByName('BEGATEWAY_ACTIVE_ERIP');

    $orderStateId = Configuration::get('BEGATEWAY_STATE_WAITING');
    if ($orderStateId) {
        $orderState     = new OrderState();
        $orderState->id = $orderStateId;
        $orderState->delete();
        unlink(_PS_IMG_DIR_ .'os/'.(int)$orderState->id.'.gif');
    }

    Configuration::deleteByName('BEGATEWAY_STATE_WAITING');

    // payment titles
    foreach (Language::getLanguages() as $language) {
      Configuration::deleteByName('BEGATEWAY_TITLE_CREDIT_CARD_'. $language['iso_code']);
      Configuration::deleteByName('BEGATEWAY_TITLE_CREDIT_CARD_HALVA_'. $language['iso_code']);
      Configuration::deleteByName('BEGATEWAY_TITLE_ERIP_'. $language['iso_code']);
    }

    return Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'begateway_transaction`') &&
      $this->unregisterHook('backOfficeHeader') &&
      $this->unregisterHook('paymentOptions' ) &&
      $this->unregisterHook('paymentReturn') &&
      $this->unregisterHook('payment') &&
      parent::uninstall();
  }

  /**
   * Load the configuration form
   */
  public function getContent()
  {
      /**
       * If values have been submitted in the form, process.
       */
      if (((bool)Tools::isSubmit('submitBegatewayModule')) == true) {
          $this->postProcess();
      }

      $this->context->smarty->assign('module_dir', $this->_path);

      $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

      return $output . $this->renderForm();
  }

  /**
   * Save form data.
   */
  protected function postProcess()
  {
      $form_values = $this->getConfigFieldsValues();

      foreach (array_keys($form_values) as $key) {
          $value = Tools::getValue($key);
          Configuration::updateValue($key, trim($value));
      }
  }

  public function getConfigForm()
  {
    $id_lang = $this->context->language->iso_code;
    return array(
      'form' => array(
        'legend' => array(
          'title' => $this->l('Settings'),
          'icon'  => 'icon-cogs'
        ),
        'input'  => array(
          array(
            'type'    => 'switch',
            'label'   => $this->l('Active'),
            'name'    => 'BEGATEWAY_ACTIVE_MODE',
            'is_bool' => true,
            'values'  => array(
              array(
                  'id'    => 'active_on',
                  'value' => true,
                  'label' => $this->l('Enabled')
              ),
              array(
                  'id'    => 'active_off',
                  'value' => false,
                  'label' => $this->l('Disabled')
              )
            ),
          ),
          array(
            'type' => 'text',
            'label' => $this->l('Checkout page domain'),
            'name' => 'BEGATEWAY_DOMAIN_CHECKOUT',
            'required' => true
          ),
          array(
            'type' => 'switch',
            'label' => $this->l('Test mode'),
            'name' => 'BEGATEWAY_TEST_MODE',
            'values' => array(
              array(
                'id' => 'active_on',
                'value' => true,
                'label' => $this->l('Test')
              ),
              array(
                'id' => 'active_off',
                'value' => false,
                'label' => $this->l('Live')
              )
            )
          ),
          array(
            'type' => 'text',
            'label' => $this->l('Shop Id'),
            'name' => 'BEGATEWAY_SHOP_ID',
            'required' => true
          ),
          array(
            'type' => 'text',
            'label' => $this->l('Shop secret key'),
            'name' => 'BEGATEWAY_SHOP_PASS',
            'required' => true,
          ),
          array(
            'col'  => 8,
            'type' => 'html',
            'name' => '<hr>',
          ),
          array(
            'type' => 'switch',
            'label' => $this->l('Credit card active'),
            'name' => 'BEGATEWAY_ACTIVE_CREDIT_CARD',
            'values' => array(
              array(
                'id' => 'active_on',
                'value' => true,
                'label' => $this->l('Enabled')
              ),
              array(
                'id' => 'active_off',
                'value' => false,
                'label' => $this->l('Disabled')
              )
            )
          ),

          array(
            'type' => 'select',
            'label' => $this->l('Transaction Type'),
            'name' => 'BEGATEWAY_TRANS_TYPE_CREDIT_CARD',
            'id' => 'BEGATEWAY_ACTIVE_CREDIT_CARD_OPTION1',
            'options' => array(
              'query' => array(
                array('id' => 'payment', 'name' => $this->l('Payment')),
                array('id' => 'authorization', 'name' => $this->l('Authorization'))
              ),
              'name' => 'name',
              'id' => 'id'
            )
          ),
          array(
            'type' => 'text',
            'label' => $this->l('Title'),
            'name' => 'BEGATEWAY_TITLE_CREDIT_CARD_'. $id_lang,
            'id' => 'BEGATEWAY_ACTIVE_CREDIT_CARD_OPTION2',
            'required' => true,
          ),
          array(
              'col'  => 8,
              'type' => 'html',
              'name' => '<hr id="BEGATEWAY_ACTIVE_CREDIT_CARD_OPTION9">',
          ),

          array(
            'type' => 'switch',
            'label' => $this->l('Halva active'),
            'name' => 'BEGATEWAY_ACTIVE_CREDIT_CARD_HALVA',
            'values' => array(
              array(
                'id' => 'active_on',
                'value' => true,
                'label' => $this->l('Enabled')
              ),
              array(
                'id' => 'active_off',
                'value' => false,
                'label' => $this->l('Disabled')
              )
            )
          ),
          array(
            'type' => 'text',
            'label' => $this->l('Title'),
            'name' => 'BEGATEWAY_TITLE_CREDIT_CARD_HALVA_'. $id_lang,
            'id' => 'BEGATEWAY_ACTIVE_CREDIT_CARD_HALVA_OPTION2',
            'required' => true,
          ),
          array(
              'col'  => 8,
              'type' => 'html',
              'name' => '<hr id="BEGATEWAY_ACTIVE_CREDIT_CARD_HALVA_OPTION9">',
          ),
          array(
            'type' => 'switch',
            'label' => $this->l('ERIP active'),
            'name' => 'BEGATEWAY_ACTIVE_ERIP',
            'values' => array(
              array(
                'id' => 'active_on',
                'value' => true,
                'label' => $this->l('Enabled')
              ),
              array(
                'id' => 'active_off',
                'value' => false,
                'label' => $this->l('Disabled')
              )
            )
          ),
          array(
            'type' => 'text',
            'label' => $this->l('Title'),
            'name' => 'BEGATEWAY_TITLE_ERIP_'. $id_lang,
            'id' => 'BEGATEWAY_ACTIVE_ERIP_OPTION2',
            'required' => true,
          ),
          array(
              'col'  => 8,
              'type' => 'html',
              'name' => '<hr id="BEGATEWAY_ACTIVE_ERIP_OPTION9">',
          ),
        ),
        'submit' => array(
          'title' => $this->l('Save')
        )
      )
    );
  }

  public function renderForm()
  {

    $helper = new HelperForm();

    $helper->show_toolbar             = false;
		$helper->table                    = $this->table;
    $helper->module                   = $this;
    $helper->default_form_language    = $this->context->language->id;
    $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
		$helper->identifier               = $this->identifier;
		$helper->submit_action            = 'submitBegatewayModule';

    $helper->currentIndex  = $this->context->link->getAdminLink('AdminModules', false)
        . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
		$helper->token         = Tools::getAdminTokenLite('AdminModules');

		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages'    => $this->context->controller->getLanguages(),
			'id_language'  => $this->context->language->id
		);

		return $helper->generateForm(array($this->getConfigForm()));
  }

  public function getConfigFieldsValues()
	{
    $id_lang = $this->context->language->iso_code;
		return array(
			'BEGATEWAY_ACTIVE_MODE' => Tools::getValue('BEGATEWAY_ACTIVE_MODE', Configuration::get('BEGATEWAY_ACTIVE_MODE', false)),
			'BEGATEWAY_SHOP_ID' => Tools::getValue('BEGATEWAY_SHOP_ID', Configuration::get('BEGATEWAY_SHOP_ID', $this->presets['test']['shop_id'])),
			'BEGATEWAY_SHOP_PASS' => Tools::getValue('BEGATEWAY_SHOP_PASS', Configuration::get('BEGATEWAY_SHOP_PASS', $this->presets['test']['shop_key'])),
			'BEGATEWAY_DOMAIN_CHECKOUT' => Tools::getValue('BEGATEWAY_DOMAIN_CHECKOUT', Configuration::get('BEGATEWAY_DOMAIN_CHECKOUT', $this->presets['test']['domain_checkout'])),
			'BEGATEWAY_TEST_MODE' => Tools::getValue('BEGATEWAY_TEST_MODE', Configuration::get('BEGATEWAY_TEST_MODE', true)),

			'BEGATEWAY_ACTIVE_CREDIT_CARD' => Tools::getValue('BEGATEWAY_ACTIVE_CREDIT_CARD', Configuration::get('BEGATEWAY_ACTIVE_CREDIT_CARD', false)),
			'BEGATEWAY_TRANS_TYPE_CREDIT_CARD' => Tools::getValue('BEGATEWAY_TRANS_TYPE_CREDIT_CARD', Configuration::get('BEGATEWAY_TRANS_TYPE_CREDIT_CARD', 'payment')),

			'BEGATEWAY_ACTIVE_CREDIT_CARD_HALVA' => Tools::getValue('BEGATEWAY_ACTIVE_CREDIT_CARD_HALVA', Configuration::get('BEGATEWAY_ACTIVE_CREDIT_CARD_HALVA', false)),
			'BEGATEWAY_ACTIVE_ERIP' => Tools::getValue('BEGATEWAY_ACTIVE_ERIP', Configuration::get('BEGATEWAY_ACTIVE_ERIP', false)),
      'BEGATEWAY_TITLE_CREDIT_CARD_'. $id_lang => Tools::getValue('BEGATEWAY_TITLE_CREDIT_CARD_'. $id_lang, Configuration::get('BEGATEWAY_TITLE_CREDIT_CARD_'. $id_lang)),
      'BEGATEWAY_TITLE_CREDIT_CARD_HALVA_'. $id_lang => Tools::getValue('BEGATEWAY_TITLE_CREDIT_CARD_HALVA_'. $id_lang, Configuration::get('BEGATEWAY_TITLE_CREDIT_CARD_HALVA_'. $id_lang)),
      'BEGATEWAY_TITLE_ERIP_'. $id_lang => Tools::getValue('BEGATEWAY_TITLE_ERIP_'. $id_lang, Configuration::get('BEGATEWAY_TITLE_ERIP_'. $id_lang))
		);
	}

  public function hookPaymentOptions($params)
  {
    if (!$this->active) {
        return array();
    }

    if (false === Configuration::get('BEGATEWAY_ACTIVE_MODE', false)) {
        return array();
    }
    $this->smarty->assign('module_dir', $this->_path);

    $id_lang = $this->context->language->iso_code;

    $payments       = array(
      'credit_card' => array(
        'isActive' => Configuration::get('BEGATEWAY_ACTIVE_CREDIT_CARD', false),
        'title'    => Configuration::get('BEGATEWAY_TITLE_CREDIT_CARD_'. $id_lang),
        'id'       => 'creditcard',
      ),
      'credit_card_halva' => array(
        'isActive' => Configuration::get('BEGATEWAY_ACTIVE_CREDIT_CARD_HALVA', false),
        'title'    => Configuration::get('BEGATEWAY_TITLE_CREDIT_CARD_HALVA_'. $id_lang),
        'id'       => 'creditcardhalva',
      ),
      'erip' => array(
        'isActive' => Configuration::get('BEGATEWAY_ACTIVE_ERIP', false),
        'title'    => Configuration::get('BEGATEWAY_TITLE_ERIP_'. $id_lang),
        'id'       => 'erip',
      )
    );
    $activePayments = array();
    foreach ($payments as $payment => $paymentInfos) {
      if ($paymentInfos['isActive']) {
        $activePayments['begateway_' . $payment]             = array();
        $activePayments['begateway_' . $payment]['cta_text'] = $this->l(
          $paymentInfos['title'],
          $this->name . '_' . $payment
        );
        $activePayments['begateway_' . $payment]['logo']     = Media::getMediaPath(
          _PS_MODULE_DIR_ . $this->name . '/views/img/' . $this->name . '_' . $paymentInfos['id'] . '.png'
        );
        $activePayments['begateway_' . $payment]['action']   = $this->context->link->getModuleLink(
          $this->name,
          $paymentInfos['id'],
          array(),
          true
        );
      }
    }

    $newOptions = array();
    if ($activePayments) {
      foreach ($activePayments as $legacyOption) {
        if (false == $legacyOption) {
          continue;
        }

        foreach (PaymentOption::convertLegacyOption($legacyOption) as $option) {
          /** @var $option PaymentOption */
          $option->setModuleName($this->name);
          $newOptions[] = $option;
        }
      }

    return $newOptions;
    }

    return array();
  }

  public function hookBackOfficeHeader()
  {
    if (Tools::getValue('configure') == $this->name) {
      $this->context->controller->addJS($this->_path . 'views/js/back.js');
    }
  }

  public function hookPaymentReturn($params)
  {
    if ($this->active == false) {
        return false;
    }
    /** @var order $order */
    $order = $params['order'];
    $currency = new Currency($order->id_currency);

    if (strcasecmp($order->module, 'begateway') != 0) {
        return false;
    }

    if (Tools::getValue('status') != 'failed' && $order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
        $this->smarty->assign('status', 'ok');
    }

    $this->smarty->assign(
        array(
            'id_order'  => $order->id,
            'reference' => $order->reference,
            'params'    => $params,
            'total'     => Tools::displayPrice($order->getOrdersTotalPaid(), $currency, false),
        )
    );

    return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
  }

  public function init_begateway() {
    \BeGateway\Settings::$checkoutBase = 'https://' . trim(Configuration::get('BEGATEWAY_DOMAIN_CHECKOUT'));
    \BeGateway\Settings::$shopId  = trim(Configuration::get('BEGATEWAY_SHOP_ID'));
    \BeGateway\Settings::$shopKey = trim(Configuration::get('BEGATEWAY_SHOP_PASS'));
  }
}
