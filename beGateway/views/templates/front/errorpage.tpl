{*
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
*}
{extends file='checkout/checkout.tpl'}
{block name="content"}
    <section id="content">
        <div class="row">
            <div class="col-md-12">
                <section id="checkout-payment-step" class="checkout-step -current -reachable js-current-step">
                    <h1 class="text-xs-center">{l s='Payment' mod='begateway'}</h1>
                    {include file="module:begateway/views/templates/front/error.tpl"}

                    <a href="{$link->getPageLink('order', true, NULL, "step=3")}"
                       class="btn btn-default">
                        {l s='Other payment methods' mod='begateway'}
                    </a>
                </section>
            </div>
        </div>
    </section>
{/block}
