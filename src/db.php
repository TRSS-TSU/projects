<?php

function get_db(): PDO {
    static $db = null;
    if ($db !== null) return $db;

    $db_path = dirname(__DIR__) . '/data/tracker.db';
    $schema_path = dirname(__DIR__) . '/schema.sql';
    $needs_init = !file_exists($db_path);

    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');

    if ($needs_init && file_exists($schema_path)) {
        $db->exec(file_get_contents($schema_path));
    }

    return $db;
}

function db_query(string $sql, array $params = []): array {
    $stmt = get_db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_query_one(string $sql, array $params = []): ?array {
    $stmt = get_db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function db_execute(string $sql, array $params = []): int {
    $stmt = get_db()->prepare($sql);
    $stmt->execute($params);
    return (int) get_db()->lastInsertId();
}
