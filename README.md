# MySQLPool

> A MySQL connection pool solution designed specifically for PHP, featuring intuitive chaining syntax that integrates query builder capabilities with read-write separation.

[![packagist](https://img.shields.io/packagist/v/pardnchiu/mysql-pool)](https://packagist.org/packages/pardnchiu/mysql-pool)

## Features

- **Dual Connection Pools**: Separate read and write database connections
- **Query Builder**: Fluent interface for building SQL queries
- **Environment Configuration**: Easy setup using environment variables
- **Connection Management**: Automatic connection pooling and cleanup
- **Slow Query Detection**: Automatic logging of queries taking over 20ms
- **JOIN Operations**: Support for INNER, LEFT, and RIGHT joins
- **CRUD Operations**: Complete Create, Read, Update, Delete functionality
- **UPSERT Support**: Insert or update on duplicate key

## Installation

```shell
composer require pardnchiu/mysql-pool
```

## Environment Configuration

Set up your database connections using environment variables:

### Read Database (Optional)
```env
DB_READ_HOST=localhost
DB_READ_PORT=3306
DB_READ_USER=read_user
DB_READ_PASSWORD=read_password
DB_READ_DATABASE=your_database
DB_READ_CHARSET=utf8mb4
DB_READ_CONNECTION=8
```

### Write Database (Required for write operations)
```env
DB_WRITE_HOST=localhost
DB_WRITE_PORT=3306
DB_WRITE_USER=write_user
DB_WRITE_PASSWORD=write_password
DB_WRITE_DATABASE=your_database
DB_WRITE_CHARSET=utf8mb4
DB_WRITE_CONNECTION=4
```

## Quick Start

```php
<?php

use pardnchiu\MySQLPool as SQL;

// simple query
$result_user = SQL::table("users")
   ->where("status", "active")
   ->get();
```

## API Reference

### Query Builder Methods

#### `table($table, $target = "READ" | "WRITE")`
Set the target table and target pool

```php
<?php

SQL::table("users")           // use read pool
SQL::table("users", "WRITE")  // use write pool
```

#### `select($fields)`
Specify columns to select.

```php
<?php

SQL::table("users")
  ->select("id", "name", "email");
SQL::table("users")
  ->select("COUNT(*) as total");
```

### `where($column, $operator, $value)`
Add WHERE conditions.

```php
<?php

// Basic where
SQL::table("users")
  ->where("id", 1);
SQL::table("users")
  ->where("age", ">", 18);

// LIKE operator (automatically adds % wildcards)
SQL::table("users")
  ->where("name", "LIKE", "John");
```

### JOIN Operations

```php
<?php

// INNER JOIN
SQL::table("users")
  ->join("profiles", "users.id", "profiles.user_id");

// LEFT JOIN
SQL::table("users")
  ->left_join("orders", "users.id", "orders.user_id");

// RIGHT JOIN with custom operator
SQL::table("users")
  ->right_join("posts", "users.id", "!=", "posts.author_id");
```

### Error Handling
```php
<?php

try {
    $result = SQL::table("users")
        ->where("id", 1)
        ->update([
            "status" => "active",
            "updated_at" => "NOW()"
        ]);

    if (!empty($result["info"])) {
        echo "Performance: " . $result["info"];
    };
} catch (\PDOException $e) {
    echo "Database Error: " . $e->getMessage();
} catch (\Exception $e) {
    echo "General Error: " . $e->getMessage();
};
```

## License

This source code project is licensed under the [MIT](https://github.com/pardnchiu/PHP-SQL/blob/main/LICENSE) license.

## Creator

<img src="https://avatars.githubusercontent.com/u/25631760" align="left" width="96" height="96" style="margin-right: 0.5rem;">

<h4 style="padding-top: 0">邱敬幃 Pardn Chiu</h4>

<a href="mailto:dev@pardn.io" target="_blank">
    <img src="https://pardn.io/image/email.svg" width="48" height="48">
</a> <a href="https://linkedin.com/in/pardnchiu" target="_blank">
    <img src="https://pardn.io/image/linkedin.svg" width="48" height="48">
</a>

***

©️ 2024 [邱敬幃 Pardn Chiu](https://pardn.io)