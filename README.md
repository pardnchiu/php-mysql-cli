# PD\SQL

> A fluent SQL query builder for PHP that provides an elegant and safe way to build and execute database queries. Built on top of PDO.

![tag](https://img.shields.io/badge/tag-PHP%20Library-bb4444) 
![size](https://img.shields.io/github/size/pardnchiu/PHP-SQL/src/SQL.php)<br>
![version](https://img.shields.io/packagist/v/pardnchiu/sql)
![download](https://img.shields.io/packagist/dm/pardnchiu/sql)

## Features
- Fluent interface for building SQL queries
- Safe parameter binding to prevent SQL injection
- Support for complex JOIN operations (INNER, LEFT, RIGHT)
- Dynamic WHERE clause construction
- Ordering and pagination support
- Transaction handling
- Query execution time monitoring
- Environment-based configuration
- Automatic connection management

## functions

- Table selection with `table()`
- Custom field selection with `select()`
- Conditional filtering with `where()`
- Join operations with `join()`, `left_join()`, `right_join()`
- Result ordering with `order_by()`
- Pagination with `limit()` and `offset()`
- Record creation with `insertGetId()`
- Record updates with `update()`
- Total row count with `total()`
- Raw query execution with `query()` for complex custom queries

## How to Use

### Install

```SHELL
composer require pardnchiu/sql
```

### Use

```PHP
<?php

use PD\SQL;

$result_user_0 = SQL::table('users')
   ->where('status', 'active')
   ->where('age', '>', 18)
   ->get();

$result_order = SQL::table('orders')
   ->select('orders.*', 'users.name')
   ->join('users', 'orders.user_id', 'users.id')
   ->where('orders.status', 'pending')
   ->get();

$result_product = SQL::table('products')
   ->total()
   ->limit(10)
   ->offset(0)
   ->order_by('created_at', 'DESC')
   ->get();

$result_user_1 = SQL::query(
    "SELECT * FROM users WHERE status = ? AND role = ?",
    ['active', 'admin']
);
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