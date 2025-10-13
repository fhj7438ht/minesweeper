<?php

namespace fhj7438ht\Minesweeper;

use PDO;
use PDOException;

/**
 * Класс для работы с базой данных SQLite
 */
class Database
{
    private PDO $pdo;
    private string $dbPath;

    public function __construct(string $dbPath = 'minesweeper.db')
    {
        $this->dbPath = $dbPath;
        $this->connect();
        $this->initializeTables();
    }

    /**
     * Подключение к базе данных SQLite
     */
    private function connect(): void
    {
        try {
            $this->pdo = new PDO("sqlite:{$this->dbPath}");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }

    /**
     * Инициализация таблиц базы данных
     */
    private function initializeTables(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS games (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                player_name TEXT NOT NULL,
                rows INTEGER NOT NULL,
                cols INTEGER NOT NULL,
                mines INTEGER NOT NULL,
                board_state TEXT NOT NULL,
                visible_state TEXT NOT NULL,
                game_over BOOLEAN NOT NULL DEFAULT 0,
                game_won BOOLEAN NOT NULL DEFAULT 0,
                opened_cells INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ";

        $sql2 = "
            CREATE TABLE IF NOT EXISTS moves (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                game_id INTEGER NOT NULL,
                move_number INTEGER NOT NULL,
                row INTEGER NOT NULL,
                col INTEGER NOT NULL,
                action TEXT NOT NULL,
                result TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (game_id) REFERENCES games (id) ON DELETE CASCADE
            )
        ";

        try {
            $this->pdo->exec($sql);
            $this->pdo->exec($sql2);
        } catch (PDOException $e) {
            throw new \RuntimeException("Ошибка создания таблиц: " . $e->getMessage());
        }
    }

    /**
     * Получение объекта PDO для выполнения запросов
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Выполнение SQL запроса с параметрами
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new \RuntimeException("Ошибка выполнения запроса: " . $e->getMessage());
        }
    }

    /**
     * Получение последнего вставленного ID
     */
    public function getLastInsertId(): int
    {
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Начало транзакции
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Подтверждение транзакции
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Откат транзакции
     */
    public function rollback(): bool
    {
        return $this->pdo->rollback();
    }
}
