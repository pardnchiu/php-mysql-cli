# MySQL CLI

> Lightweight PHP MySQL client with chainable syntax, query builder and read-write separation.<br>
> Following stateless architecture principles, providing stable and reliable database operation experience.

[![packagist](https://img.shields.io/packagist/v/pardnchiu/mysql-cli)](https://packagist.org/packages/pardnchiu/mysql-cli)
[![version](https://img.shields.io/github/v/tag/pardnchiu/php-mysql-cli?label=release)](https://github.com/pardnchiu/php-mysql-cli/releases)
[![license](https://img.shields.io/github/license/pardnchiu/php-mysql-cli)](LICENSE)<br>
[![readme](https://img.shields.io/badge/readme-EN-white)](README.md)
[![readme](https://img.shields.io/badge/readme-ZH-white)](README.zh.md)

- [Three Core Features](#three-core-features)
  - [Chainable Syntax](#chainable-syntax)
  - [Read-Write Separation](#read-write-separation)
  - [Stable Connection](#stable-connection)
- [Features](#features)
- [Usage](#usage)
  - [Installation](#installation)
  - [Environment Variables Setup](#environment-variables-setup)
  - [Basic Usage](#basic-usage)
- [API Reference](#api-reference)
  - [Query Builder](#query-builder)
  - [JOIN Operations](#join-operations)
  - [Data Operations](#data-operations)
- [Error Handling](#error-handling)
- [License](#license)
- [Author](#author)

## Three Core Features

### Chainable Syntax
Intuitive query builder syntax that makes complex SQL queries simple and readable with low learning curve

### Read-Write Separation
Automatically identifies query types and routes to corresponding database connections, supports read-write separation architecture, effectively distributes database load and improves overall system performance

### Stable Connection
Retry mechanism automatically handles network jitter and temporary connection failures, ensuring reliability in unstable network environments

## Features

- **Environment Variable Configuration**: Flexible environment variable settings, supports multi-environment deployment
- **Slow Query Monitoring**: Automatically logs queries over 20ms, assists with performance optimization
- **Secure Parameter Binding**: Prepared statements prevent SQL injection attacks
- **Complete CRUD**: Supports full database operations for create, read, update, delete
- **SQL Function Support**: Built-in common MySQL function recognition and processing
- **Stateless Design**: Independent cleanup for each request

## Usage

### Installation

```shell
composer require pardnchiu/mysql-cli
```

### Environment Variables Setup

#### Read Database (Optional)
```env
DB_READ_HOST=localhost
DB_READ_PORT=3306
DB_READ_USER=read_user
DB_READ_PASSWORD=read_password
DB_READ_DATABASE=your_database
DB_READ_CHARSET=utf8mb4
```

#### Write Database (Required for write operations)
```env
DB_WRITE_HOST=localhost
DB_WRITE_PORT=3306
DB_WRITE_USER=write_user
DB_WRITE_PASSWORD=write_password
DB_WRITE_DATABASE=your_database
DB_WRITE_CHARSET=utf8mb4
```

### Basic Usage

```php
<?php

use pardnchiu\SQL;

// Basic query
$users = SQL::table("users")
  ->where("status", "active")
  ->where("age", ">", 18)
  ->get();

// Complex query with aggregation
$reports = SQL::table("orders")
  ->select("user_id", "COUNT(*) as order_count", "SUM(amount) as total")
  ->where("created_at", ">=", "2024-01-01")
  ->groupBy("user_id")
  ->orderBy("total", "DESC")
  ->limit(10)
  ->get();
```

## API Reference

### Query Builder

- `table($table, $target = "READ")` - Set target table and connection type
  ```php
  SQL::table("users")           // Read operation (default)
  SQL::table("users", "WRITE")  // Write operation
  ```

- `select($fields)` - Specify query fields
  ```php
  SQL::table("users")->select("id", "name", "email");
  SQL::table("products")->select("COUNT(*) as total");
  ```

- `where($column, $operator, $value)` - Add conditions
  ```php
  // Basic conditions
  SQL::table("users")->where("status", "active");
  SQL::table("orders")->where("amount", ">", 100);
  
  // LIKE search (automatically adds wildcards)
  SQL::table("users")->where("name", "LIKE", "John");
  ```

- `orderBy($column, $direction)` - Sorting
  ```php
  SQL::table("users")->orderBy("created_at", "DESC");
  SQL::table("products")->orderBy("price", "ASC");
  ```

- `limit($count)` / `offset($count)` - Pagination
  ```php
  SQL::table("users")->limit(20)->offset(40);
  ```

### JOIN Operations

```php
// Inner join
SQL::table("users")
  ->join("profiles", "users.id", "profiles.user_id")
  ->get();

// Left join
SQL::table("users")
  ->leftJoin("orders", "users.id", "orders.user_id")
  ->select("users.name", "COUNT(orders.id) as order_count")
  ->get();

// Right join
SQL::table("departments")
  ->rightJoin("employees", "departments.id", "employees.dept_id")
  ->get();
```

### Data Operations

```php
// Insert data and get ID
$userId = SQL::table("users", "WRITE")
  ->insertGetId([
    "name"        => "John Doe",
    "email"       => "john@example.com", 
    "created_at"  => "NOW()"
  ]);

// Update data
$result = SQL::table("users", "WRITE")
  ->where("id", $userId)
  ->update([
    "last_login"  => "NOW()",
    "login_count" => "login_count + 1"
  ]);

// Raw query
$customData = SQL::read(
  "SELECT u.name, COUNT(o.id) as orders FROM users u LEFT JOIN orders o ON u.id = o.user_id WHERE u.created_at > ? GROUP BY u.id",
  ["2024-01-01"]
);
```

## Error Handling

```php
try {
  $result = SQL::table("users", "WRITE")
    ->where("id", 1)
    ->update([
      "status"      => "active", 
      "updated_at"  => "NOW()"
    ]);
  
  // Check slow query warnings
  if (!empty($result["info"])) {
    error_log("Slow query warning: " . $result["info"]);
  }
  
  echo "Update successful, affected rows: " . $result["affected_rows"];
    
} catch (\PDOException $e) {
  // Database related errors
  error_log("Database error: " . $e->getMessage());
  
  // Handle based on error code
  $errorCode = $e->getCode();
  if ($errorCode === 2006 || $errorCode === 2013) {
    // Connection interrupted, system will auto retry
    echo "Connection exception, please try again later";
  } else {
    echo "Database operation failed";
  }
    
} catch (\InvalidArgumentException $e) {
  // Parameter errors
  error_log("Parameter error: " . $e->getMessage());
  echo "Request parameters are incorrect";
    
} catch (\Exception $e) {
  // Other errors
  error_log("System error: " . $e->getMessage());
  echo "System temporarily unavailable, please contact administrator";
}
```

### Performance Monitoring

```php
// Enable detailed logging
error_reporting(E_ALL);

// Automatically log slow queries (over 20ms)
$users = SQL::table("users")
  ->where("status", "active")
  ->get();

// Check system logs:
// [Info] PD\SQL: [Slow Query: 25.43ms] [SELECT * FROM users WHERE status = ?]
```

## License

This project is licensed under [MIT](https://github.com/pardnchiu/mysql-cli/blob/main/LICENSE).

## Author

<img src="https://avatars.githubusercontent.com/u/25631760" align="left" width="96" height="96" style="margin-right: 0.5rem;">

<h4 style="padding-top: 0">邱敬幃 Pardn Chiu</h4>

<a href="mailto:dev@pardn.io" target="_blank">
    <img src="https://pardn.io/image/email.svg" width="48" height="48">
</a> <a href="https://linkedin.com/in/pardnchiu" target="_blank">
    <img src="https://pardn.io/image/linkedin.svg" width="48" height="48">
</a>

***

©️ 2024 [邱敬幃 Pardn Chiu](https://pardn.io)