<?php

namespace pardnchiu;

class MySQLPool
{
  private static $client;
  private static $table;
  private static $wheres;
  private static $bindings;
  private static $orders;
  private static $limit;
  private static $offset;
  private static $joins;
  private static $selects;
  private static $with_total;

  private function __construct() {}

  private function __clone() {}

  private static function initConnection($target)
  {
    if (isset(self::$client)) {
      return;
    }

    $host = (string) $_ENV["DB_{$target}_HOST"] ?? "localhost";
    $port = (int) $_ENV["DB_{$target}_PORT"] ?? 3306;
    $user = (string) $_ENV["DB_{$target}_USER"] ?? "root";
    $password = (string) $_ENV["DB_{$target}_PASSWORD"] ?? "";
    $database = (string) $_ENV["DB_{$target}_DATABASE"] ?? "database";
    $charset = (string) $_ENV["DB_{$target}_CHARSET"] ?? "utf8mb4";

    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=$charset";

    try {
      self::$client = new \PDO($dsn, $user, $password, [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
      ]);

      $timeout = 600;
      self::$client->query("SET SESSION wait_timeout = $timeout");
      self::$client->query("SET SESSION interactive_timeout = $timeout");
    } catch (\Exception $e) {
      throw $e;
    };
  }

  public static function table($table, $target = "READ")
  {
    self::initConnection($target);
    self::$table = $table;
    self::$wheres = [];
    self::$bindings = [];
    self::$orders = [];
    self::$limit = null;
    self::$offset = null;
    self::$joins = [];
    self::$selects = ["*"];
    self::$with_total = false;

    return new static();
  }

  public static function total()
  {
    self::$with_total = true;
    return new static();
  }

  public static function select($fields)
  {
    if (is_string($fields)) {
      $fields = func_get_args();
    };

    self::$selects = $fields;

    return new static();
  }

  public static function join($table, $first, $operator, $second = null)
  {
    return self::set_join("INNER", $table, $first, $operator, $second);
  }

  public static function left_join($table, $first, $operator, $second = null)
  {
    return self::set_join("LEFT", $table, $first, $operator, $second);
  }

  public static function right_join($table, $first, $operator, $second = null)
  {
    return self::set_join("RIGHT", $table, $first, $operator, $second);
  }

  private static function set_join($type, $table, $first, $operator, $second)
  {
    if ($second === null) {
      $second = $operator;
      $operator = "=";
    };

    $first = strpos($first, ".") === false ? "`$first`" : $first;
    $second = strpos($second, ".") === false ? "`$second`" : $second;

    self::$joins[] = "$type JOIN `$table` ON $first $operator $second";

    return new static();
  }

  public static function where($column, $operator, $value = null)
  {
    if ($operator === "LIKE" && is_string($value)) {
      $value = "%$value%";
    } else if ($value === null) {
      $value = $operator;
      $operator = "=";
    };

    $column = strpos($column, "(") === false && strpos($column, ".") === false ? "`$column`" : $column;

    self::$wheres[] = "$column $operator ?";
    self::$bindings[] = $value;

    return new static();
  }

  public static function order_by($column, $direction = "DESC")
  {
    $direction = strtoupper($direction);
    if (!in_array($direction, ["ASC", "DESC"])) {
      throw new \InvalidArgumentException("Invalid: Direction ($direction).");
    };

    $column = strpos($column, ".") === false ? "`$column`" : $column;
    self::$orders[] = "$column $direction";

    return new static();
  }

  public static function limit($limit)
  {
    self::$limit = (int) $limit;

    return new static();
  }

  public static function offset($offset)
  {
    self::$offset = (int) $offset;

    return new static();
  }

  public static function get()
  {
    if (self::$table === null) {
      throw new \Exception("Invalid: Table (null).");
    };

    $fields = implode(", ", array_map(function ($e) {
      return $e === "*" ? $e : (strpos($e, ".") === false ? "`$e`" : $e);
    }, self::$selects));

    $query = "SELECT $fields FROM `" . self::$table . "`";

    if (!empty(self::$joins)) {
      $query .= " " . implode(" ", self::$joins);
    };

    if (!empty(self::$wheres)) {
      $query .= " WHERE " . implode(" AND ", self::$wheres);
    };

    if (self::$with_total) {
      $query = "SELECT COUNT(*) OVER() AS total, data.* FROM ($query) AS data";
    };

    if (!empty(self::$orders)) {
      $query .= " ORDER BY " . implode(", ", self::$orders);
    };

    if (self::$limit !== null) {
      $query .= " LIMIT " . self::$limit;
    };

    if (self::$offset !== null) {
      $query .= " OFFSET " . self::$offset;
    };

    return self::query($query, self::$bindings);
  }

  public static function update(array $data)
  {
    if (!self::$table) {
      throw new \Exception("Invalid: Table (null).");
    };

    $sets = [];
    $values = [];
    $sqlFunctions = [
      'NOW()',
      'CURRENT_TIMESTAMP',
      'UUID()',
      'RAND()',
      'CURDATE()',
      'CURTIME()',
      'UNIX_TIMESTAMP()',
      'UTC_TIMESTAMP()',
      'SYSDATE()',
      'LOCALTIME()',
      'LOCALTIMESTAMP()',
      'PI()',
      'DATABASE()',
      'USER()',
      'VERSION()'
    ];

    foreach ($data as $column => $value) {
      $column = strpos($column, ".") === false ? "`$column`" : $column;

      if (is_string($value) && in_array(strtoupper($value), $sqlFunctions)) {
        $sets[] = "$column = $value";
      } else {
        $sets[] = "$column = ?";
        $values[] = $value;
      };
    };

    $query = "UPDATE `" . self::$table . "` SET " . implode(", ", $sets);

    if (!empty(self::$wheres)) {
      $query .= " WHERE " . implode(" AND ", self::$wheres);
    };

    return self::query($query, array_merge($values, self::$bindings));
  }

  public static function insertGetId(array $data)
  {
    if (!self::$table) {
      throw new \Exception("Invalid: Table (null).");
    };

    $columns = array_keys($data);
    $values = array_values($data);
    $placeholders = array_fill(0, count($values), "?");

    $query = "INSERT INTO `" . self::$table . "` (`" . implode("`, `", $columns) .
      "`) VALUES (" . implode(", ", $placeholders) . ")";

    $result = self::query($query, $values);
    return $result['insert_id'] ?? null;
  }

  public static function query($query, $params = [])
  {
    if (!isset(self::$client)) {
      throw new \Exception("數據庫連接未初始化");
    }

    try {
      $stmt = self::$client->prepare($query);

      if ($params) {
        foreach ($params as $index => &$val) {
          switch (true) {
            case is_int($val):
              $stmt->bindValue($index + 1, $val, \PDO::PARAM_INT);
              break;
            case is_bool($val):
              $stmt->bindValue($index + 1, $val, \PDO::PARAM_BOOL);
              break;
            case is_null($val):
              $stmt->bindValue($index + 1, $val, \PDO::PARAM_NULL);
              break;
            default:
              $stmt->bindValue($index + 1, $val, \PDO::PARAM_STR);
          };
        };
      };

      $start = microtime(true) * 1000;
      $stmt->execute();
      $end = microtime(true) * 1000;
      $ms = number_format($end - $start, 2);

      if ($ms > 20) {
        $info = sprintf("[Info] PD\SQL: [Slow Query: %sms] [%s]", $ms, $query);
        error_log($info);
      };

      if (stripos($query, "UPDATE") === 0 || stripos($query, "INSERT") === 0) {
        return [
          "info" => $info ?? "",
          "insert_id" => self::$client->lastInsertId(),
          "affected_rows" => $stmt->rowCount()
        ];
      };

      return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
      $info = $e->errorInfo ?? null;
      $code = $info[1] ?? $e->getCode();
      $message = $info[2] ?? $e->getMessage();
      $result = sprintf("[Error] PD\SQL: [Code: %s] [Message: %s] [%s]", $code, $message);

      error_log($result);

      throw new \PDOException($result, (int) $code, $e);
    };
  }

  public static function close()
  {
    self::$client = null;
    self::$table = null;
    self::$wheres = [];
    self::$bindings = [];
  }
}
