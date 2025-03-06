# E-Commerce Microservices
This project is a simplified E-Commerce system built using a microservices architecture in Laravel. It consists of three services and a shared folder for common utilities. Each service communicates asynchronously using RabbitMQ for event-driven processing.

## Architecture Overview
### Services
1. Order Service
    - Manages customer orders and processes payments.
    - Provides a REST API for creating orders.
    - Publishes events like `order_created` to RabbitMQ.
2. Inventory Service
    - Manages product inventory and updates stock levels.
    - Consumes events (e.g., `order_created`) and processes inventory updates.
    - Executed as a command-line consumer.
3. Notification Service
    - Handles customer notifications (e.g., order status updates).
    - Consumes events (e.g., `inventory_processed`) and sends notifications.
    - Executed as a command-line consumer.
    
### Shared Folder
- Contains utilities like `RabbitMQService` for consistent messaging across all services.

## Tech Stack
- **Backend:** Laravel (PHP)
- **Message Broker:** RabbitMQ
- **Database:** MYSQL

## Setup Guide
### Prerequisites
- PHP 8.1+
- Composer
- RabbitMQ
- MySQL

## Installation Steps
1. Clone the repository:
    ```
    git clone https://github.com/ndy-s/ecommerce-microservices.git  
    cd ecommerce-microservices  
    ```
2. Install dependencies for each service:
    ```
    composer install  
    ```
3. Configure the .env file for each service with the appropriate settings (RabbitMQ, database, etc.).
4. Run database migrations for each service:
    ```
    php artisan migrate  
    ```

## How to Use Each Service
### 1. Order Service
- This service provides a REST API to manage orders.
- Use the provided Postman collection to test the API:
    - Location: `order-service/postman/ecommerce-collection.json`

**Example API Call**
<br>To create an order:
```
POST /api/orders  
Content-Type: application/json

{
    "user_id": 1,
    "items": [
        { "product_id": 9, "quantity": 2 },
        { "product_id": 10, "quantity": 1 }
    ],
    "total": 115.77
}
```

**Running the Order Service**
<br>Start the Laravel development server:
```
php artisan serve --host=0.0.0.0 --port=8000
```

### 2. Inventory Service
- This service processes events from RabbitMQ to update inventory.

**Running the Inventory Service**
<br>Execute the command to start consuming messages:
```
php artisan inventory:consume  
```

### 3. Notification Service
- This service processes events from RabbitMQ to send notifications.

**Running the Notification Service**
<br>Execute the command to start consuming messages:
```
php artisan notification:consume
```

## Folder Structure
```
ecommerce-microservices/  
├── order-service/  
│   ├── postman/  
│   │   └── ecommerce-collection.json  
├── inventory-service/  
├── notification-service/  
└── shared/
```

## License
This project is open-source and available under the MIT License.
