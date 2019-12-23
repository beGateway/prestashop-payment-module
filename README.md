# PrestaShop 1.7 payment module

[Русская версия](#модуль-оплаты-prestashop)

## Install module

  * Backup your webstore and database
  * Download [begateway.zip](https://github.com/begateway/prestashop-payment-module/raw/master/begateway.zip)
  * Login to your PrestaShop admin area and select the _Modules_ menu
  * Click _Upload a module_
  * Upload the archive _begateway.zip_ via a module installer
  * Locate _BeGateway_ in available modules of _Selection_ tab and install it

## Module configuration

  * Click _Installed modules_ tab, locate _BeGateway_ module and click _Configure_ button
  * Make it active
  * Enter in fields _Shop Id_, _Shop secret key_  and _Checkout page domain_ values received from your payment processor
  * Activate payment methods you want accept payment with and setup titles of them visible to customers
  * Select a default transaction type: __Payment__ or __Authorization__ for credit card payments

**You are done!**

## Notes

Tested and developed with PrestaShop 1.7.6.2

## Testing

You can use the following information to adjust the payment method in test mode:

  * __Shop ID:__ 361
  * __Shop Key:__ b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d
  * __Checkout page domain:__ checkout.begateway.com

Use the following test card to make successful test payment:

  * Card number: 4200000000000000
  * Name on card: JOHN DOE
  * Card expiry date: 01/30
  * CVC: 123

Use the following test card to make failed test payment:

  * Card number: 4005550000000019
  * Name on card: JOHN DOE
  * Card expiry date: 01/30
  * CVC: 123

# Модуль оплаты для PrestaShop 1.7

[English version](#prestashop-payment-module)

## Установка модуля

  * Создайте резервную копию вашего магазина и базы данных
  * Скачайте архив плагина [begateway.zip](https://github.com/begateway/prestashop-payment-module/raw/master/begateway.zip)
  * Зайдите в зону администратора магазина и выберете меню _Модули_
  * Нажмите _Загрузить модуль_
  * Загрузите модуль _begateway.zip_
  * Найдите модуль _BeGateway_ в списке модулей и установите его

![Установить модуль](https://github.com/begateway/prestashop-payment-module/raw/master/doc/add-module-install.png)

## Настройка модуля

  * Выберите закладку _Установленные модули_
  * Найдите модуль _BeGateway_ в списке модулей и нажмите кнопку _Настроить_
  * Включите модуль
  * Введите в полях _Id магазина_, _Ключ магазина_, _Домен страницы оплаты_ значения, полученные от вашей платежной компании
  * Включите способы оплаты, через которые вы хотите принимать платежи
  * Выберете тип транзакции по умолчанию: __Payment__ or __Authorization__

**Готово!**

## Примечания

Разработанно и протестировано с PrestaShop 1.7.6.2

## Тестирование

Вы можете использовать следующие данные, чтобы настроить способ оплаты в тестовом режиме

  * __Идентификационный номер магазина:__ 361
  * __Секретный ключ магазина:__ b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d
  * __Домен платежной страницы:__ checkout.begateway.com
  * __Режим работы:__ Тестовый

Используйте следующие данные карты для успешного тестового платежа:

  * Номер карты: 4200000000000000
  * Имя на карте: JOHN DOE
  * Месяц срока действия карты: 01/30
  * CVC: 123

Используйте следующие данные карты для неуспешного тестового платежа:

  * Номер карты: 4005550000000019
  * Имя на карте: JOHN DOE
  * Месяц срока действия карты: 01/30
  * CVC: 123
