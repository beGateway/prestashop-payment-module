## PrestaShop 1.7 payment module

[Русская версия](#модуль-оплаты-prestashop)

### Install module

  * Backup your webstore and database
  * Download [begateway.zip](https://github.com/begateway/prestashop-payment-module/raw/master/begateway.zip)
  * Login to your PrestaShop admin area and select the _Modules_ menu
  * Click _Add a new module_

![Add a new module](https://github.com/begateway/prestashop-payment-module/raw/master/doc/add-module-button.png)

  * Upload the archive _begateway.zip_ via a module installer

![Upload a new module](https://github.com/begateway/prestashop-payment-module/raw/master/doc/add-module-file.png)

  * Locate _beGateway_ in available modules and install it

![Install module](https://github.com/begateway/prestashop-payment-module/raw/master/doc/add-module-install.png)

### Module configuration

At the payment module configuration page:

  * Enter in fields _Shop Id_, _Shop secret key_, _Payment gateway domain_ and _Payment page domain_ values received from your payment processor.
  * Select a default transaction type: __Payment__ or __Authorization__

![Configure module](https://github.com/begateway/prestashop-payment-module/raw/master/doc/config-module.png)

### You are done!

### Notes

Tested and developed with PrestaShop 1.7

### Demo credentials

You are free to use the settings to configure the module to process
payments with a demo gateway.

  * Shop Id __361__
  * Shop secret key __b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d__
  * Payment gateway domain __demo-gateway.begateway.com__
  * Payment page domain __checkout.begateway.com__

Use the test data to make a test payment:

  * card number __4200000000000000__
  * card name __John Doe__
  * card expiry month __01__ to get a success payment
  * card expiry month __10__ to get a failed payment
  * CVC __123__

### Contributing

Issue pull requests or send feature requests.

## Модуль оплаты для PrestaShop 1.7

[English version](#prestashop-payment-module)

### Установка плагина

  * Создайте резервную копию вашего магазина и базы данных
  * Скачайте архив плагина [begateway.zip](https://github.com/begateway/prestashop-payment-module/raw/master/begateway.zip)
  * Зайдите в зону администратора магазина и выберете меню _Модули_
  * Нажмите _Добавить модуль_

![Добавить модуль](https://github.com/begateway/prestashop-payment-module/raw/master/doc/add-module-button-ru.png)

  * Загрузите модуль _begateway.zip_ через _ДОБАВИТЬ МОДУЛЬ_

![Загрузить модуль](https://github.com/begateway/prestashop-payment-module/raw/master/doc/add-module-file-ru.png)

  * Найдите модуль _beGateway_ в списке модулей и установите его

![Установить модуль](https://github.com/begateway/prestashop-payment-module/raw/master/doc/add-module-install.png)

### Настройка магазина

На странице настройки модуля:

  * Введите в полях _Id магазина_, _Ключ магазина_, _Домен платежного шлюза_ и _Домен платежной страницы_ значения, полученные от вашей платежной компании
  * Выберете тип транзакции по умолчанию: __Payment__ or __Authorization__

![Настройка модуля](https://github.com/begateway/prestashop-payment-module/raw/master/doc/config-module.png)

### Готово!

### Примечания

Разработанно и протестированно с PrestaShop 1.7

### Тестовые данные

Вы можете использовать следующие данные, чтобы настроить способ оплаты в
тестовом режиме:

  * Идентификационный номер магазина __361__
  * Секретный ключ магазина __b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d__
  * Домен платежного шлюза __demo-gateway.begateway.com__
  * Домен платежной страницы __checkout.begateway.com__

Используйте следующий тестовый набор для тестового платежа:

  * номер карты __4200000000000000__
  * имя на карте __John Doe__
  * месяц срока действия карты __01__, чтобы получить успешный платеж
  * месяц срока действия карты __10__, чтобы получить неуспешный платеж
  * CVC __123__
