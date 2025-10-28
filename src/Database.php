<?php

namespace fhj7438ht\Minesweeper;

use RedBeanPHP\R;
use RedBeanPHP\RedException\SQL;

/**
 * Класс для работы с базой данных SQLite через RedBeanPHP ORM
 */
class Database
{
    private string $dbPath;
    private bool $isConnected = false;

    public function __construct(string $dbPath = 'minesweeper.db')
    {
        $this->dbPath = $dbPath;
        $this->connect();
        $this->initializeTables();
    }

    /**
     * Подключение к базе данных SQLite через RedBeanPHP
     */
    private function connect(): void
    {
        try {
            // Настройка RedBeanPHP для работы с SQLite
            R::setup('sqlite:' . $this->dbPath);
            
            // Включаем режим "заморозки" для продакшена
            R::freeze(false);
            
            $this->isConnected = true;
        } catch (SQL $e) {
            throw new \RuntimeException("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }

    /**
     * Инициализация таблиц базы данных
     */
    private function initializeTables(): void
    {
        try {
            // Создаем таблицу games если её нет
            $gamesTable = R::dispense('games');
            $gamesTable->player_name = 'temp';
            $gamesTable->rows = 1;
            $gamesTable->cols = 1;
            $gamesTable->mines = 1;
            $gamesTable->board_state = 'temp';
            $gamesTable->visible_state = 'temp';
            $gamesTable->game_over = false;
            $gamesTable->game_won = false;
            $gamesTable->opened_cells = 0;
            $gamesTable->created_at = date('Y-m-d H:i:s');
            $gamesTable->updated_at = date('Y-m-d H:i:s');
            
            $gameId = R::store($gamesTable);
            
            // Удаляем тестовую запись
            R::trash($gamesTable);
            
            // Создаем таблицу moves если её нет
            $movesTable = R::dispense('moves');
            $movesTable->game_id = 1;
            $movesTable->move_number = 1;
            $movesTable->row = 0;
            $movesTable->col = 0;
            $movesTable->action = 'temp';
            $movesTable->result = 'temp';
            $movesTable->created_at = date('Y-m-d H:i:s');
            
            $moveId = R::store($movesTable);
            
            // Удаляем тестовую запись
            R::trash($movesTable);
            
        } catch (SQL $e) {
            throw new \RuntimeException("Ошибка создания таблиц: " . $e->getMessage());
        }
    }

    /**
     * Получение объекта RedBeanPHP для выполнения операций
     */
    public function getRedBean(): \RedBeanPHP\ToolBox
    {
        if (!$this->isConnected) {
            throw new \RuntimeException("База данных не подключена");
        }
        return R::getToolBox();
    }

    /**
     * Выполнение SQL запроса с параметрами (для совместимости)
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $pdo = R::getPDO();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (SQL $e) {
            throw new \RuntimeException("Ошибка выполнения запроса: " . $e->getMessage());
        }
    }

    /**
     * Получение последнего вставленного ID
     */
    public function getLastInsertId(): int
    {
        try {
            return (int)R::getInsertID();
        } catch (SQL $e) {
            throw new \RuntimeException("Ошибка получения ID: " . $e->getMessage());
        }
    }

    /**
     * Начало транзакции
     */
    public function beginTransaction(): bool
    {
        try {
            R::begin();
            return true;
        } catch (SQL $e) {
            return false;
        }
    }

    /**
     * Подтверждение транзакции
     */
    public function commit(): bool
    {
        try {
            R::commit();
            return true;
        } catch (SQL $e) {
            return false;
        }
    }

    /**
     * Откат транзакции
     */
    public function rollback(): bool
    {
        try {
            R::rollback();
            return true;
        } catch (SQL $e) {
            return false;
        }
    }

    /**
     * Закрытие соединения с базой данных
     */
    public function close(): void
    {
        if ($this->isConnected) {
            R::close();
            $this->isConnected = false;
        }
    }

    /**
     * Деструктор для автоматического закрытия соединения
     */
    public function __destruct()
    {
        $this->close();
    }
}
