<?php

namespace fhj7438ht\Minesweeper;

use RedBeanPHP\R;
use RedBeanPHP\RedException\SQL;

/**
 * Класс для управления данными игр в базе данных через RedBeanPHP ORM
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
        try {
            $gameBean = R::dispense('games');
            
            $gameBean->player_name = $playerName;
            $gameBean->rows = $game->getDimensions()['rows'];
            $gameBean->cols = $game->getDimensions()['cols'];
            $gameBean->mines = $game->getMinesCount();
            $gameBean->board_state = $this->serializeGameState($game);
            $gameBean->visible_state = $this->serializeVisibleState($game);
            $gameBean->game_over = $game->isGameOver();
            $gameBean->game_won = $game->isGameWon();
            $gameBean->opened_cells = $game->getOpenedCellsCount();
            $gameBean->created_at = date('Y-m-d H:i:s');
            $gameBean->updated_at = date('Y-m-d H:i:s');

            $gameId = R::store($gameBean);
            return (int)$gameId;
        } catch (SQL $e) {
            throw new \RuntimeException("Ошибка сохранения игры: " . $e->getMessage());
        }
    }

    /**
     * Обновление существующей игры
     */
    public function updateGame(int $gameId, Game $game): bool
    {
        try {
            $gameBean = R::load('games', $gameId);
            
            if (!$gameBean->id) {
                return false;
            }

            $gameBean->board_state = $this->serializeGameState($game);
            $gameBean->visible_state = $this->serializeVisibleState($game);
            $gameBean->game_over = $game->isGameOver();
            $gameBean->game_won = $game->isGameWon();
            $gameBean->opened_cells = $game->getOpenedCellsCount();
            $gameBean->updated_at = date('Y-m-d H:i:s');

            R::store($gameBean);
            return true;
        } catch (SQL $e) {
            throw new \RuntimeException("Ошибка обновления игры: " . $e->getMessage());
        }
    }

    /**
     * Загрузка игры по ID
     */
    public function loadGame(int $gameId): ?Game
    {
        try {
            $gameBean = R::load('games', $gameId);
            
            if (!$gameBean->id) {
                return null;
            }

            return $this->deserializeGame($gameBean);
        } catch (SQL $e) {
            throw new \RuntimeException("Ошибка загрузки игры: " . $e->getMessage());
        }
    }

    /**
     * Получение списка всех игр
     */
    public function getAllGames(): array
    {
        try {
            $games = R::findAll('games', 'ORDER BY created_at DESC');
            
            $result = [];
            foreach ($games as $gameBean) {
                $result[] = [
                    'id' => $gameBean->id,
                    'player_name' => $gameBean->player_name,
                    'rows' => $gameBean->rows,
                    'cols' => $gameBean->cols,
                    'mines' => $gameBean->mines,
                    'game_over' => $gameBean->game_over,
                    'game_won' => $gameBean->game_won,
                    'created_at' => $gameBean->created_at,
                    'updated_at' => $gameBean->updated_at
                ];
            }
            
            return $result;
        } catch (SQL $e) {
            throw new \RuntimeException("Ошибка получения списка игр: " . $e->getMessage());
        }
    }

    /**
     * Получение списка незавершенных игр
     */
    public function getActiveGames(): array
    {
        try {
            $games = R::find('games', 'game_over = ? AND game_won = ? ORDER BY updated_at DESC', [false, false]);
            
            $result = [];
            foreach ($games as $gameBean) {
                $result[] = [
                    'id' => $gameBean->id,
                    'player_name' => $gameBean->player_name,
                    'rows' => $gameBean->rows,
                    'cols' => $gameBean->cols,
                    'mines' => $gameBean->mines,
                    'created_at' => $gameBean->created_at,
                    'updated_at' => $gameBean->updated_at
                ];
            }
            
            return $result;
        } catch (SQL $e) {
            throw new \RuntimeException("Ошибка получения активных игр: " . $e->getMessage());
        }
    }

    /**
     * Удаление игры
     */
    public function deleteGame(int $gameId): bool
    {
        try {
            $gameBean = R::load('games', $gameId);
            
            if (!$gameBean->id) {
                return false;
            }

            R::trash($gameBean);
            return true;
        } catch (SQL $e) {
            throw new \RuntimeException("Ошибка удаления игры: " . $e->getMessage());
        }
    }

    /**
     * Сохранение хода
     */
    public function saveMove(int $gameId, int $moveNumber, int $row, int $col, string $action, string $result): void
    {
        try {
            $moveBean = R::dispense('moves');
            
            $moveBean->game_id = $gameId;
            $moveBean->move_number = $moveNumber;
            $moveBean->row = $row;
            $moveBean->col = $col;
            $moveBean->action = $action;
            $moveBean->result = $result;
            $moveBean->created_at = date('Y-m-d H:i:s');

            R::store($moveBean);
        } catch (SQL $e) {
            throw new \RuntimeException("Ошибка сохранения хода: " . $e->getMessage());
        }
    }

    /**
     * Получение всех ходов игры
     */
    public function getGameMoves(int $gameId): array
    {
        try {
            $moves = R::find('moves', 'game_id = ? ORDER BY move_number', [$gameId]);
            
            $result = [];
            foreach ($moves as $moveBean) {
                $result[] = [
                    'id' => $moveBean->id,
                    'game_id' => $moveBean->game_id,
                    'move_number' => $moveBean->move_number,
                    'row' => $moveBean->row,
                    'col' => $moveBean->col,
                    'action' => $moveBean->action,
                    'result' => $moveBean->result,
                    'created_at' => $moveBean->created_at
                ];
            }
            
            return $result;
        } catch (SQL $e) {
            throw new \RuntimeException("Ошибка получения ходов игры: " . $e->getMessage());
        }
    }

    /**
     * Получение следующего номера хода для игры
     */
    public function getNextMoveNumber(int $gameId): int
    {
        try {
            $maxMove = R::getCell('SELECT MAX(move_number) FROM moves WHERE game_id = ?', [$gameId]);
            return ($maxMove ?? 0) + 1;
        } catch (SQL $e) {
            throw new \RuntimeException("Ошибка получения номера хода: " . $e->getMessage());
        }
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
    private function deserializeGame($gameBean): Game
    {
        $game = new Game($gameBean->rows, $gameBean->cols, $gameBean->mines);
        
        // Восстанавливаем состояние игрового поля
        $boardState = unserialize(base64_decode($gameBean->board_state));
        $visibleState = unserialize(base64_decode($gameBean->visible_state));
        
        $game->restoreState($boardState, $visibleState, $gameBean->game_over, $gameBean->game_won, $gameBean->opened_cells);
        
        return $game;
    }
}
