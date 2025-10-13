<?php

namespace fhj7438ht\Minesweeper;

/**
 * Класс для управления игровой логикой Сапёра
 */
class Game
{
    private const MINE = -1;
    private const UNOPENED = -2;
    private const FLAGGED = -3;

    private int $rows;
    private int $cols;
    private int $mines;
    private array $board;
    private array $visible;
    private bool $gameOver;
    private bool $gameWon;
    private int $openedCells;

    public function __construct(int $rows = 9, int $cols = 9, int $mines = 10)
    {
        $this->rows = $rows;
        $this->cols = $cols;
        $this->mines = $mines;
        $this->gameOver = false;
        $this->gameWon = false;
        $this->openedCells = 0;
        $this->initializeBoard();
    }

    /**
     * Инициализация игрового поля
     */
    private function initializeBoard(): void
    {
        $this->board = array_fill(0, $this->rows, array_fill(0, $this->cols, 0));
        $this->visible = array_fill(0, $this->rows, array_fill(0, $this->cols, self::UNOPENED));
        $this->placeMines();
        $this->calculateNumbers();
    }

    /**
     * Размещение мин на поле
     */
    private function placeMines(): void
    {
        $minesPlaced = 0;
        while ($minesPlaced < $this->mines) {
            $row = rand(0, $this->rows - 1);
            $col = rand(0, $this->cols - 1);

            if ($this->board[$row][$col] !== self::MINE) {
                $this->board[$row][$col] = self::MINE;
                $minesPlaced++;
            }
        }
    }

    /**
     * Подсчет чисел для каждой ячейки
     */
    private function calculateNumbers(): void
    {
        for ($row = 0; $row < $this->rows; $row++) {
            for ($col = 0; $col < $this->cols; $col++) {
                if ($this->board[$row][$col] !== self::MINE) {
                    $this->board[$row][$col] = $this->countAdjacentMines($row, $col);
                }
            }
        }
    }

    /**
     * Подсчет мин в соседних ячейках
     */
    private function countAdjacentMines(int $row, int $col): int
    {
        $count = 0;
        for ($i = -1; $i <= 1; $i++) {
            for ($j = -1; $j <= 1; $j++) {
                $newRow = $row + $i;
                $newCol = $col + $j;

                if (
                    $this->isValidCell($newRow, $newCol) &&
                    $this->board[$newRow][$newCol] === self::MINE
                ) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Проверка валидности координат ячейки
     */
    private function isValidCell(int $row, int $col): bool
    {
        return $row >= 0 && $row < $this->rows && $col >= 0 && $col < $this->cols;
    }

    /**
     * Открытие ячейки
     */
    public function openCell(int $row, int $col): bool
    {
        if (!$this->isValidCell($row, $col) || $this->gameOver || $this->gameWon) {
            return false;
        }

        if ($this->visible[$row][$col] !== self::UNOPENED && $this->visible[$row][$col] !== self::FLAGGED) {
            return false;
        }

        if ($this->visible[$row][$col] === self::FLAGGED) {
            return false; // Нельзя открыть отмеченную ячейку
        }

        $this->visible[$row][$col] = $this->board[$row][$col];
        $this->openedCells++;

        if ($this->board[$row][$col] === self::MINE) {
            $this->gameOver = true;
            $this->revealAllMines();
            return false;
        }

        if ($this->board[$row][$col] === 0) {
            $this->openAdjacentCells($row, $col);
        }

        $this->checkWinCondition();
        return true;
    }

    /**
     * Открытие соседних пустых ячеек
     */
    private function openAdjacentCells(int $row, int $col): void
    {
        for ($i = -1; $i <= 1; $i++) {
            for ($j = -1; $j <= 1; $j++) {
                $newRow = $row + $i;
                $newCol = $col + $j;

                if (
                    $this->isValidCell($newRow, $newCol) &&
                    $this->visible[$newRow][$newCol] === self::UNOPENED
                ) {
                    $this->visible[$newRow][$newCol] = $this->board[$newRow][$newCol];
                    $this->openedCells++;

                    if ($this->board[$newRow][$newCol] === 0) {
                        $this->openAdjacentCells($newRow, $newCol);
                    }
                }
            }
        }
    }

    /**
     * Отметка ячейки флагом
     */
    public function flagCell(int $row, int $col): bool
    {
        if (!$this->isValidCell($row, $col) || $this->gameOver || $this->gameWon) {
            return false;
        }

        if ($this->visible[$row][$col] === self::UNOPENED) {
            $this->visible[$row][$col] = self::FLAGGED;
            return true;
        } elseif ($this->visible[$row][$col] === self::FLAGGED) {
            $this->visible[$row][$col] = self::UNOPENED;
            return true;
        }

        return false;
    }

    /**
     * Показать все мины (при проигрыше)
     */
    private function revealAllMines(): void
    {
        for ($row = 0; $row < $this->rows; $row++) {
            for ($col = 0; $col < $this->cols; $col++) {
                if ($this->board[$row][$col] === self::MINE) {
                    $this->visible[$row][$col] = self::MINE;
                }
            }
        }
    }

    /**
     * Проверка условия победы
     */
    private function checkWinCondition(): void
    {
        $totalCells = $this->rows * $this->cols;
        if ($this->openedCells === $totalCells - $this->mines) {
            $this->gameWon = true;
        }
    }

    /**
     * Получение состояния ячейки для отображения
     */
    public function getCellDisplay(int $row, int $col): string
    {
        if (!$this->isValidCell($row, $col)) {
            return '?';
        }

        $cell = $this->visible[$row][$col];

        switch ($cell) {
            case self::UNOPENED:
                return '.';
            case self::FLAGGED:
                return 'M';
            case self::MINE:
                return '*';
            case 0:
                return ' ';
            default:
                return (string)$cell;
        }
    }

    /**
     * Получение полного состояния поля для отображения
     */
    public function getBoardDisplay(): array
    {
        $display = [];
        for ($row = 0; $row < $this->rows; $row++) {
            $display[$row] = [];
            for ($col = 0; $col < $this->cols; $col++) {
                $display[$row][$col] = $this->getCellDisplay($row, $col);
            }
        }
        return $display;
    }

    /**
     * Проверка окончания игры
     */
    public function isGameOver(): bool
    {
        return $this->gameOver;
    }

    /**
     * Проверка победы
     */
    public function isGameWon(): bool
    {
        return $this->gameWon;
    }

    /**
     * Получение размеров поля
     */
    public function getDimensions(): array
    {
        return ['rows' => $this->rows, 'cols' => $this->cols];
    }

    /**
     * Получение количества мин
     */
    public function getMinesCount(): int
    {
        return $this->mines;
    }

    /**
     * Получение количества открытых ячеек
     */
    public function getOpenedCellsCount(): int
    {
        return $this->openedCells;
    }

    /**
     * Получение состояния игрового поля
     */
    public function getBoardState(): array
    {
        return $this->board;
    }

    /**
     * Получение состояния видимых ячеек
     */
    public function getVisibleState(): array
    {
        return $this->visible;
    }

    /**
     * Восстановление состояния игры
     */
    public function restoreState(array $boardState, array $visibleState, bool $gameOver, bool $gameWon, int $openedCells): void
    {
        $this->board = $boardState;
        $this->visible = $visibleState;
        $this->gameOver = $gameOver;
        $this->gameWon = $gameWon;
        $this->openedCells = $openedCells;
    }

    /**
     * Создание игры с заданным состоянием (для воспроизведения)
     */
    public static function createFromState(int $rows, int $cols, int $mines, array $boardState, array $visibleState, bool $gameOver, bool $gameWon, int $openedCells): self
    {
        $game = new self($rows, $cols, $mines);
        $game->restoreState($boardState, $visibleState, $gameOver, $gameWon, $openedCells);
        return $game;
    }
}
