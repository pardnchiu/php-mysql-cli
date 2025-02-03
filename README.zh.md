# PD\SQL

> PD\SQL 是一個基於 PDO 的 SQL 查詢建構器，為 PHP 提供優雅且安全的方式來建立和執行資料庫查詢。

![tag](https://img.shields.io/badge/tag-PHP%20Library-bb4444)
![size](https://img.shields.io/github/size/pardnchiu/PHP-SQL/src/SQL.php)<br>
![version](https://img.shields.io/packagist/v/pardnchiu/sql)
![download](https://img.shields.io/packagist/dm/pardnchiu/sql)

## 功能特點

- 流暢的介面用於建立 SQL 查詢
- 安全的參數綁定以防止 SQL 注入
- 支援複雜的 JOIN 操作（INNER, LEFT, RIGHT）
- 動態 WHERE 子句建構
- 排序和分頁支援
- 交易處理
- 查詢執行時間監控
- 基於環境的配置
- 自動連接管理

## 可用函式

- 使用 `table()` 進行表格選擇
- 使用 `select()` 進行自定義欄位選擇
- 使用 `where()` 進行條件過濾
- 使用 `join()`、`left_join()`、`right_join()` 進行連接操作
- 使用 `order_by()` 進行結果排序
- 使用 `limit()` 和 `offset()` 進行分頁
- 使用 `insertGetId()` 進行記錄創建
- 使用 `update()` 進行記錄更新
- 使用 `total()` 獲取總行數
- 使用 `query()` 執行複雜的自定義查詢

## 使用方式

### 安裝

```shell
composer require pardnchiu/sql
```

```php
<?php

use PD\SQL;

$result_user_0 = SQL::table('users')
   ->where("status", "active")
   ->where("age", ">", 18)
   ->get();

$result_order = SQL::table("orders")
   ->select("orders.*", "users.name")
   ->join("users", "orders.user_id", "users.id")
   ->where("orders.status", "pending")
   ->get();

$result_product = SQL::table("products")
   ->total()
   ->limit(10)
   ->offset(0)
   ->order_by("created_at", "DESC")
   ->get();

$result_user_1 = SQL::query(
    "SELECT * FROM users WHERE status = ? AND role = ?",
    ['active', 'admin']
);

try {
    $result = SQL::table("users")
        ->where("id", 1)
        ->update([
            "status" => "active",
            "updated_at" => "NOW()"
        ]);

    if (!empty($result["info"])) {
        echo "效能警告: " . $result["info"];
    };
} catch (\PDOException $e) {
    echo "資料庫錯誤: " . $e->getMessage();
} catch (\Exception $e) {
    echo "一般錯誤: " . $e->getMessage();
};
```

## 授權條款
此原始碼專案採用 [MIT](https://github.com/pardnchiu/PHP-SQL/blob/main/LICENSE) 授權。

## 創作者
<img src="https://avatars.githubusercontent.com/u/25631760" align="left" width="96" height="96" style="margin-right: 0.5rem;">
<h4 style="padding-top: 0">邱敬幃 Pardn Chiu</h4>
<a href="mailto:dev@pardn.io" target="_blank">
 <img src="https://pardn.io/image/email.svg" width="48" height="48">
</a> <a href="https://linkedin.com/in/pardnchiu" target="_blank">
 <img src="https://pardn.io/image/linkedin.svg" width="48" height="48">
</a>

---
©️ 2024 [邱敬幃 Pardn Chiu](https://pardn.io)