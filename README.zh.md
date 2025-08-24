# MySQL CLI

> 輕量級 PHP MySQL 客戶端，支援鏈式語法、查詢建構器和讀寫分離。<br>
> 遵循無狀態架構原則，提供穩定可靠的資料庫操作體驗。

[![packagist](https://img.shields.io/packagist/v/pardnchiu/mysql-cli)](https://packagist.org/packages/pardnchiu/mysql-cli)
[![version](https://img.shields.io/github/v/tag/pardnchiu/php-mysql-cli?label=release)](https://github.com/pardnchiu/php-mysql-cli/releases)
[![license](https://img.shields.io/github/license/pardnchiu/php-mysql-cli)](LICENSE)<br>
[![readme](https://img.shields.io/badge/readme-EN-white)](README.md)
[![readme](https://img.shields.io/badge/readme-ZH-white)](README.zh.md)

- [三大核心特色](#三大核心特色)
  - [鏈式語法](#鏈式語法)
  - [讀寫分離](#讀寫分離)
  - [穩定連接](#穩定連接)
- [功能特性](#功能特性)
- [使用方法](#使用方法)
  - [安裝](#安裝)
  - [環境變數設定](#環境變數設定)
  - [基本使用](#基本使用)
- [API 參考](#api-參考)
  - [查詢建構器](#查詢建構器)
  - [JOIN 操作](#join-操作)
  - [資料操作](#資料操作)
- [錯誤處理](#錯誤處理)
- [授權協議](#授權協議)
- [作者](#作者)

## 三大核心特色

### 鏈式語法
直觀的查詢建構器語法，讓複雜的 SQL 查詢變得簡單易讀，低學習成本

### 讀寫分離
自動識別查詢類型並路由到對應的資料庫連接，支援讀寫分離架構，有效分散資料庫負載，提升系統整體性能

### 穩定連接
重試機制，自動處理網路抖動和暫時性連接失敗，確保在不穩定網路環境下的可靠性

## 功能特性

- **環境變數配置**: 靈活的環境變數設定，支援多環境部署
- **慢查詢監控**: 自動記錄超過 20ms 的查詢，協助性能優化
- **安全參數綁定**: 預處理語句防止 SQL 注入攻擊
- **完整 CRUD**: 支援增刪改查的完整資料庫操作
- **SQL 函數支援**: 內建常用 MySQL 函數識別和處理
- **無狀態設計**: 每次請求獨立清理

## 使用方法

### 安裝

```shell
composer require pardnchiu/mysql-cli
```

### 環境變數設定

#### 讀取資料庫（選填）
```env
DB_READ_HOST=localhost
DB_READ_PORT=3306
DB_READ_USER=read_user
DB_READ_PASSWORD=read_password
DB_READ_DATABASE=your_database
DB_READ_CHARSET=utf8mb4
```

#### 寫入資料庫（寫入操作必填）
```env
DB_WRITE_HOST=localhost
DB_WRITE_PORT=3306
DB_WRITE_USER=write_user
DB_WRITE_PASSWORD=write_password
DB_WRITE_DATABASE=your_database
DB_WRITE_CHARSET=utf8mb4
```

### 基本使用

```php
<?php

use pardnchiu\SQL;

// 基本查詢
$users = SQL::table("users")
  ->where("status", "active")
  ->where("age", ">", 18)
  ->get();

// 複雜查詢與聚合
$reports = SQL::table("orders")
  ->select("user_id", "COUNT(*) as order_count", "SUM(amount) as total")
  ->where("created_at", ">=", "2024-01-01")
  ->groupBy("user_id")
  ->orderBy("total", "DESC")
  ->limit(10)
  ->get();
```

## API 參考

### 查詢建構器

- `table($table, $target = "READ")` - 設定目標表格和連接類型
  ```php
  SQL::table("users")           // 讀取操作（預設）
  SQL::table("users", "WRITE")  // 寫入操作
  ```

- `select($fields)` - 指定查詢欄位
  ```php
  SQL::table("users")->select("id", "name", "email");
  SQL::table("products")->select("COUNT(*) as total");
  ```

- `where($column, $operator, $value)` - 添加條件
  ```php
  // 基本條件
  SQL::table("users")->where("status", "active");
  SQL::table("orders")->where("amount", ">", 100);
  
  // LIKE 搜尋（自動加上通配符）
  SQL::table("users")->where("name", "LIKE", "John");
  ```

- `orderBy($column, $direction)` - 排序
  ```php
  SQL::table("users")->orderBy("created_at", "DESC");
  SQL::table("products")->orderBy("price", "ASC");
  ```

- `limit($count)` / `offset($count)` - 分頁
  ```php
  SQL::table("users")->limit(20)->offset(40);
  ```

### JOIN 操作

```php
// 內連接
SQL::table("users")
  ->join("profiles", "users.id", "profiles.user_id")
  ->get();

// 左連接
SQL::table("users")
  ->leftJoin("orders", "users.id", "orders.user_id")
  ->select("users.name", "COUNT(orders.id) as order_count")
  ->get();

// 右連接
SQL::table("departments")
  ->rightJoin("employees", "departments.id", "employees.dept_id")
  ->get();
```

### 資料操作

```php
// 新增資料並取得 ID
$userId = SQL::table("users", "WRITE")
  ->insertGetId([
    "name"        => "張三",
    "email"       => "zhang@example.com", 
    "created_at"  => "NOW()"
  ]);

// 更新資料
$result = SQL::table("users", "WRITE")
  ->where("id", $userId)
  ->update([
    "last_login"  => "NOW()",
    "login_count" => "login_count + 1"
  ]);

// 原生查詢
$customData = SQL::read(
  "SELECT u.name, COUNT(o.id) as orders FROM users u LEFT JOIN orders o ON u.id = o.user_id WHERE u.created_at > ? GROUP BY u.id",
  ["2024-01-01"]
);
```

## 錯誤處理

```php
try {
  $result = SQL::table("users", "WRITE")
    ->where("id", 1)
    ->update([
      "status"      => "active", 
      "updated_at"  => "NOW()"
    ]);
  
  // 檢查慢查詢警告
  if (!empty($result["info"])) {
    error_log("慢查詢警告: " . $result["info"]);
  }
  
  echo "更新成功，影響行數: " . $result["affected_rows"];
    
} catch (\PDOException $e) {
  // 資料庫相關錯誤
  error_log("資料庫錯誤: " . $e->getMessage());
  
  // 根據錯誤碼進行處理
  $errorCode = $e->getCode();
  if ($errorCode === 2006 || $errorCode === 2013) {
    // 連接中斷，系統會自動重試
    echo "連接異常，請稍後再試";
  } else {
    echo "資料庫操作失敗";
  }
    
} catch (\InvalidArgumentException $e) {
  // 參數錯誤
  error_log("參數錯誤: " . $e->getMessage());
  echo "請求參數不正確";
    
} catch (\Exception $e) {
  // 其他錯誤
  error_log("系統錯誤: " . $e->getMessage());
  echo "系統暫時不可用，請聯繫管理員";
}
```

### 性能監控

```php
// 啟用詳細日誌記錄
error_reporting(E_ALL);

// 自動記錄慢查詢（超過 20ms）
$users = SQL::table("users")
  ->where("status", "active")
  ->get();

// 檢查系統日誌：
// [Info] PD\SQL: [Slow Query: 25.43ms] [SELECT * FROM users WHERE status = ?]
```

## 授權協議

本原始碼專案採用 [MIT](https://github.com/pardnchiu/mysql-cli/blob/main/LICENSE) 授權。

## 作者

<img src="https://avatars.githubusercontent.com/u/25631760" align="left" width="96" height="96" style="margin-right: 0.5rem;">

<h4 style="padding-top: 0">邱敬幃 Pardn Chiu</h4>

<a href="mailto:dev@pardn.io" target="_blank">
    <img src="https://pardn.io/image/email.svg" width="48" height="48">
</a> <a href="https://linkedin.com/in/pardnchiu" target="_blank">
    <img src="https://pardn.io/image/linkedin.svg" width="48" height="48">
</a>

***

©️ 2024 [邱敬幃 Pardn Chiu](https://pardn.io)