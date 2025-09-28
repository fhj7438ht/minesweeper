<?php

namespace fhj7438ht\Minesweeper;

use fhj7438ht\Minesweeper\View;
use fhj7438ht\Minesweeper\Game;

/**
 * Контроллер для обработки командной строки и управления игрой
 */
class Controller
{
    /**
     * Обработка аргументов командной строки и запуск игры
     */
    public static function startGame(): void
    {
        $args = getopt('nl:r:h', ['new', 'list', 'replay:', 'help']);

        if (isset($args['h']) || isset($args['help'])) {
            View::showHelp();
            return;
        }

        if (isset($args['l']) || isset($args['list'])) {
            View::showGameList();
            return;
        }

        if (isset($args['r']) || isset($args['replay'])) {
            $gameId = $args['r'] ?? $args['replay'] ?? null;
            if ($gameId === null) {
                View::showError('Необходимо указать ID игры для режима --replay');
                return;
            }
            View::showReplayMessage($gameId);
            return;
        }

        // Режим новой игры (по умолчанию)
        self::startNewGame();
    }

    /**
     * Запуск новой игры
     */
    private static function startNewGame(): void
    {
        View::showWelcome();

        // Получение параметров игры от пользователя
        $rows = (int)View::promptForNumber('Введите количество строк (по умолчанию 9): ', 9);
        $cols = (int)View::promptForNumber('Введите количество столбцов (по умолчанию 9): ', 9);
        $mines = (int)View::promptForNumber('Введите количество мин (по умолчанию 10): ', 10);

        // Валидация параметров
        if ($rows < 1 || $cols < 1 || $mines < 1 || $mines >= $rows * $cols) {
            View::showError('Некорректные параметры игры!');
            return;
        }

        $game = new Game($rows, $cols, $mines);
        self::playGame($game);
    }

    /**
     * Основной игровой цикл
     */
    private static function playGame(Game $game): void
    {
        $dimensions = $game->getDimensions();
        $rows = $dimensions['rows'];
        $cols = $dimensions['cols'];

        while (!$game->isGameOver() && !$game->isGameWon()) {
            View::displayBoard($game);

            $input = View::promptForInput('Введите координаты (строка столбец) или M строка столбец для отметки: ');

            if (empty($input)) {
                continue;
            }

            $parts = explode(' ', trim($input));

            if (count($parts) === 3 && strtoupper($parts[0]) === 'M') {
                // Отметка ячейки
                $row = (int)$parts[1] - 1;
                $col = (int)$parts[2] - 1;

                if ($game->flagCell($row, $col)) {
                    View::showMessage('Ячейка отмечена/снята отметка');
                } else {
                    View::showError('Невозможно отметить эту ячейку');
                }
            } elseif (count($parts) === 2) {
                // Открытие ячейки
                $row = (int)$parts[0] - 1;
                $col = (int)$parts[1] - 1;

                if ($row < 0 || $row >= $rows || $col < 0 || $col >= $cols) {
                    View::showError('Координаты вне игрового поля!');
                    continue;
                }

                if ($game->openCell($row, $col)) {
                    View::showMessage('Ячейка открыта');
                } else {
                    View::showError('Невозможно открыть эту ячейку');
                }
            } else {
                View::showError('Неверный формат ввода! Используйте: строка столбец или M строка столбец');
            }
        }

        // Игра окончена
        View::displayBoard($game);

        if ($game->isGameWon()) {
            View::showWinMessage();
        } else {
            View::showGameOverMessage();
        }
    }
}
