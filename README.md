# Описание проекта

Проект - для демонстрации раздельной, изолированной обработки статусов заказов и нотификаций через RabbitMQ.

Каждая из папок `inventory-service`, `notification-service`, `order-service` - это работа с заказом
из разных микросервисных приложений последовательно.  В `order-service` заказ создаётся. Заказ обрабатывается после создания `inventory-service`. Дальше обрабатывает нотификации `notification-service`.

## Папка "inventory-service"
### Команда `InventoryConsumerCommand.php`
Основная логика реализована в данной команде. Команда обрабатывает заказы по ключу маршрутизации `order_created`

## Папка "notification-service"
### Команда `InventoryConsumerCommand.php`
Основная логика реализована в данной команде. Команда обрабатывает нотификации по ключу маршрутизации `inventory_processed`

## Папка "order-service"
### Контроллер `OrderController.php`
Основная логика реализована в данном контроллер. Контроллер принимает запрос за создание заказа, создаёт его в Базе Данных. Затем по ключу маршрутизации `order_created` он добавляет в RabbitMQ сообщение для его дальнейшей обработки.
Создание сообщения происходит с помощью  `OrderCreated`, который конвертирует данные массив и передает
```PHP
    $event = new OrderCreated($orderDTO);
    //Публикуется сообщение в RabbitMQ о том что заказ создан для его обработки
    $this->rabbitMQService->publishMessage('order_created', $event->toArray());
```

## Папка `shared`
Имеются DTO-объекты и сервис `Ecommerce\Shared\Services\MessageQueue\RabbitMQService`.
### Описание сервиса `RabbitMQService`
Инициализация параметров RabbitMQ происходит в конструкторе.
По умолчанию настройки такие:
```PHP
  'host' => 'default_host',
            'port' => 5672,
            'user' => 'default_user',
            'password' => 'default_password',
            'exchange' => 'default_exchange',
            'exchange_type' => 'direct',
            'exchange_declare' => true,
            'queue_declare' => true,
            'queue_bind' => true,
            'vhost' => '/',
```
Тип `exchange_type = direct`. 

У данного сервиса имеются 2 метода - `function declareExchange` и `function publishMessage`.
Оба метода на входе получают   ключ маршрутизации, exchange(необязательный) и очередь (необязательный)
В начале этих методов происходят действия - привязка ключа маршрутизации к очередям и `exchange`.

#### Описание ``public function publishMessage``
Происходит создание объекта сообщения `AMQPMessage` и его публикация через вендорную библиотеку 
```PHP
$this->channel->basic_publish($msg, $exchange, $routingKey);
```

#### Описание ``public function consumeMessages``
Получает на входе коллбэк, который вызывает когда находится сообщение из RabbitMQ.
Происходит запрос к серверу ``$this->channel->basic_consume`` через вендорную библиотеку
```PHP
$this->channel->basic_publish($msg, $exchange, $routingKey);
```


