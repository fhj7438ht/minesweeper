<?php

namespace fhj7438ht\Minesweeper;

/**
 * Класс для управления данными игр в базе данных
 */
class GameRepository
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Сохранение игры в базу данных
     */
    public function saveGame(Game $game, string $playerName): int
    {
        $sql = "
            INSERT INTO games (player_name, rows, cols, mines, board_state, visible_state, game_over, game_won, opened_cells)
            VALUES (:player_name, :rows, :cols, :mines, :board_state, :visible_state, :game_over, :game_won, :opened_cells)
        ";

        $params = [
            'player_name' => $playerName,
            'rows' => $game->getDimensions()['rows'],
            'cols' => $game->getDimensions()['cols'],
            'mines' => $game->getMinesCount(),
            'board_state' => $this->serializeGameState($game),
            'visible_state' => $this->serializeVisibleState($game),
            'game_over' => $game->isGameOver() ? 1 : 0,
            'game_won' => $game->isGameWon() ? 1 : 0,
            'opened_cells' => $game->getOpenedCellsCount()
        ];

        $this->database->query($sql, $params);
        return $this->database->getLastInsertId();
    }

    /**
     * Обновление существующей игры
     */
    public function updateGame(int $gameId, Game $game): bool
    {
        $sql = "
            UPDATE games 
            SET board_state = :board_state, 
                visible_state = :visible_state, 
                game_over = :game_over, 
                game_won = :game_won, 
                opened_cells = :opened_cells,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $params = [
            'id' => $gameId,
            'board_state' => $this->serializeGameState($game),
            'visible_state' => $this->serializeVisibleState($game),
            'game_over' => $game->isGameOver() ? 1 : 0,
            'game_won' => $game->isGameWon() ? 1 : 0,
            'opened_cells' => $game->getOpenedCellsCount()
        ];

        $stmt = $this->database->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Загрузка игры по ID
     */
    public function loadGame(int $gameId): ?Game
    {
        $sql = "SELECT * FROM games WHERE id = :id";
        $stmt = $this->database->query($sql, ['id' => $gameId]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return $this->deserializeGame($data);
    }

    /**
     * Получение списка всех игр
     */
    public function getAllGames(): array
    {
        $sql = "SELECT id, player_name, rows, cols, mines, game_over, game_won, created_at, updated_at FROM games ORDER BY created_at DESC";
        $stmt = $this->database->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Получение списка незавершенных игр
     */
    public function getActiveGames(): array
    {
        $sql = "SELECT id, player_name, rows, cols, mines, created_at, updated_at FROM games WHERE game_over = 0 AND game_won = 0 ORDER BY updated_at DESC";
        $stmt = $this->database->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Удаление игры
     */
    public function deleteGame(int $gameId): bool
    {
        $sql = "DELETE FROM games WHERE id = :id";
        $stmt = $this->database->query($sql, ['id' => $gameId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Сохранение хода
     */
    public function saveMove(int $gameId, int $moveNumber, int $row, int $col, string $action, string $result): void
    {
        $sql = "
            INSERT INTO moves (game_id, move_number, row, col, action, result)
            VALUES (:game_id, :move_number, :row, :col, :action, :result)
        ";

        $params = [
            'game_id' => $gameId,
            'move_number' => $moveNumber,
            'row' => $row,
            'col' => $col,
            'action' => $action,
            'result' => $result
        ];

        $this->database->query($sql, $params);
    }

    /**
     * Получение всех ходов игры
     */
    public function getGameMoves(int $gameId): array
    {
        $sql = "SELECT * FROM moves WHERE game_id = :game_id ORDER BY move_number";
        $stmt = $this->database->query($sql, ['game_id' => $gameId]);
        return $stmt->fetchAll();
    }

    /**
     * Получение следующего номера хода для игры
     */
    public function getNextMoveNumber(int $gameId): int
    {
        $sql = "SELECT MAX(move_number) as max_move FROM moves WHERE game_id = :game_id";
        $stmt = $this->database->query($sql, ['game_id' => $gameId]);
        $result = $stmt->fetch();
        return ($result['max_move'] ?? 0) + 1;
    }

    /**
     * Сериализация состояния игрового поля
     */
    private function serializeGameState(Game $game): string
    {
        return base64_encode(serialize($game->getBoardState()));
    }

    /**
     * Сериализация состояния видимых ячеек
     */
    private function serializeVisibleState(Game $game): string
    {
        return base64_encode(serialize($game->getVisibleState()));
    }

    /**
     * Десериализация игры из данных базы
     */
    private function deserializeGame(array $data): Game
    {
        $game = new Game($data['rows'], $data['cols'], $data['mines']);
        
        // Восстанавливаем состояние игрового поля
        $boardState = unserialize(base64_decode($data['board_state']));
        $visibleState = unserialize(base64_decode($data['visible_state']));
        
        $game->restoreState($boardState, $visibleState, $data['game_over'], $data['game_won'], $data['opened_cells']);
        
        return $game;
    }
}
