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

// payment type checkbox ids
var toggleElements = [
    "BEGATEWAY_ACTIVE_CREDIT_CARD",
    "BEGATEWAY_ACTIVE_CREDIT_CARD_HALVA",
    "BEGATEWAY_ACTIVE_ERIP"
];

// max additional options per payment type
var iMaxOptionFields = 9;

var iIndex1;
var iIndex2;

function begatewayAddListeners()
{
    for (iIndex1 = 0; iIndex1 < toggleElements.length; ++iIndex1) {
        begatewayHideUncheckedElementOptions(toggleElements[iIndex1]);
        begatewayAddInputListener(toggleElements[iIndex1]);
    }
}

function begatewayAddInputListener(sElemId)
{
    var sRadioOnId  = sElemId + '_on';
    var sRadioOffId = sElemId + '_off';
    var oElem;

    if (oElem = document.getElementById(sElemId)) {
        begatewayAddCheckboxListener(oElem);
    } else if (oElem = document.getElementById(sRadioOnId)) {
        begatewayAddRadioListener(oElem, true);
        oElem = document.getElementById(sRadioOffId);
        begatewayAddRadioListener(oElem, false);
    }
}

function begatewayAddCheckboxListener(oElem) {
    if (oElem.addEventListener) {
        oElem.addEventListener(
            "change",
            begatewayTogglePayments
        );
    } else if (oElem.attachEvent) {
        oElem.attachEvent(
            'onchange',
            begatewayTogglePayments
        );
    }
}

function begatewayAddRadioListener(oElem, blOn)
{
    var sFunction;
    if (blOn) {
        sFunction = begatewayTogglePayments;
    } else {
        sFunction = begatewayHidePayments;
    }

    if (oElem.addEventListener) {
        oElem.addEventListener(
            "change",
            sFunction
        );
    } else if (oElem.attachEvent) {
        oElem.attachEvent(
            'onchange',
            sFunction
        );
    }
}

function begatewayTogglePayments(event)
{
    var oElem = event.target;

    for (iIndex2 = iMaxOptionFields; iIndex2 > 0; iIndex2--) {
        begateway_togglepaymentoptions(
            oElem,
            iIndex2,
            false
        );
    }
}

function begatewayHidePayments(event)
{
    var oElem = event.target;

    for (iIndex2 = iMaxOptionFields; iIndex2 > 0; iIndex2--) {
        begateway_togglepaymentoptions(
            oElem,
            iIndex2,
            true
        );
    }
}

function begatewayHideUncheckedElementOptions(sElementId)
{
    for (iIndex2 = iMaxOptionFields; iIndex2 > 0; iIndex2--) {
        if (document.getElementById(sElementId)) {
            begateway_togglepaymentoptions(document.getElementById(sElementId), iIndex2);
        } else if (document.getElementById(sElementId + '_on')) {
            begateway_togglepaymentoptions(document.getElementById(sElementId + '_on'), iIndex2);
        }
    }
}

function begateway_togglepaymentoptions(oElement, iCount, blInverse)
{
    var oToggleElement = begatewayGetToggleElement(oElement.id, iCount);

    var sStyle1 = blInverse ? 'none' : 'block';
    var sStyle2 = blInverse ? 'block' : 'none';

    if (oToggleElement) {
        if (oElement.checked || oElement.selected) {
            oToggleElement.style.display = sStyle1;
        } else {
            oToggleElement.style.display = sStyle2;
        }
    }
}

function begatewayGetToggleElement(sSourceId, iCount)
{
    sSourceId = sSourceId.replace("_on", "").replace("_off", "");

    var sNewId = sSourceId + '_OPTION' + iCount;

    if (document.getElementById(sNewId)) {
        var oBaseElement = document.getElementById(sNewId);
        return oBaseElement.parentNode.parentNode;
    }

    return false;
}

document.addEventListener("DOMContentLoaded", function() {
    begatewayAddListeners();
});
