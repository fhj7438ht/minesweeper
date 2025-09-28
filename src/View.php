<?php

namespace fhj7438ht\Minesweeper;

use fhj7438ht\Minesweeper\Game;

/**
 * Класс для отображения интерфейса игры
 */
class View
{
    /**
     * Отображение приветственного экрана
     */
    public static function showWelcome(): void
    {
        \cli\line("=== Добро пожаловать в игру Сапёр! ===");
        \cli\line("");
    }

    /**
     * Отображение справки
     */
    public static function showHelp(): void
    {
        \cli\line("=== Справка по игре Сапёр ===");
        \cli\line("");
        \cli\line("Параметры командной строки:");
        \cli\line("  --new, -n     Новая игра (режим по умолчанию)");
        \cli\line("  --list, -l    Список сохраненных игр");
        \cli\line("  --replay, -r  Повтор игры с указанным ID");
        \cli\line("  --help, -h    Показать эту справку");
        \cli\line("");
        \cli\line("Формат ввода координат:");
        \cli\line("  строка столбец     - открыть ячейку");
        \cli\line("  M строка столбец   - отметить/снять отметку с ячейки");
        \cli\line("");
        \cli\line("Примеры:");
        \cli\line("  1 1               - открыть ячейку в строке 1, столбце 1");
        \cli\line("  M 2 3             - отметить ячейку в строке 2, столбце 3");
        \cli\line("");
        \cli\line("Символы на поле:");
        \cli\line("  .  - закрытая ячейка");
        \cli\line("  M  - отмеченная ячейка");
        \cli\line("  *  - мина");
        \cli\line("  число - количество мин в соседних ячейках");
        \cli\line("  пробел - пустая ячейка");
    }

    /**
     * Отображение игрового поля
     */
    public static function displayBoard(Game $game): void
    {
        $board = $game->getBoardDisplay();
        $dimensions = $game->getDimensions();
        $rows = $dimensions['rows'];
        $cols = $dimensions['cols'];

        \cli\line("");
        \cli\line("=== Игровое поле ===");
        \cli\line("");

        // Заголовок с номерами столбцов
        echo "   ";
        for ($col = 1; $col <= $cols; $col++) {
            echo sprintf("%2d ", $col);
        }
        \cli\line("");

        // Отображение строк
        for ($row = 0; $row < $rows; $row++) {
            echo sprintf("%2d ", $row + 1);
            for ($col = 0; $col < $cols; $col++) {
                echo sprintf(" %s ", $board[$row][$col]);
            }
            \cli\line("");
        }

        \cli\line("");
    }

    /**
     * Запрос ввода от пользователя
     */
    public static function promptForInput(string $message): string
    {
        return \cli\prompt($message);
    }

    /**
     * Запрос числа от пользователя
     */
    public static function promptForNumber(string $message, int $default = null): int
    {
        $input = \cli\prompt($message);

        if (empty($input) && $default !== null) {
            return $default;
        }

        $number = (int)$input;
        return $number > 0 ? $number : $default;
    }

    /**
     * Отображение сообщения
     */
    public static function showMessage(string $message): void
    {
        \cli\line($message);
    }

    /**
     * Отображение ошибки
     */
    public static function showError(string $message): void
    {
        \cli\err("Ошибка: " . $message);
    }

    /**
     * Отображение сообщения о победе
     */
    public static function showWinMessage(): void
    {
        \cli\line("");
        \cli\line("Поздравляем! Вы выиграли!");
        \cli\line("");
    }

    /**
     * Отображение сообщения о проигрыше
     */
    public static function showGameOverMessage(): void
    {
        \cli\line("");
        \cli\line("Игра окончена! Вы проиграли!");
        \cli\line("");
    }

    /**
     * Отображение списка игр
     */
    public static function showGameList(): void
    {
        \cli\line("=== Список сохраненных игр ===");
        \cli\line("");
        \cli\line("Игра пока не сохраняется в базе данных.");
        \cli\line("Эта функция будет доступна в следующих версиях.");
        \cli\line("");
    }

    /**
     * Отображение сообщения о повторении игры
     */
    public static function showReplayMessage(string $gameId): void
    {
        \cli\line("=== Повтор игры #{$gameId} ===");
        \cli\line("");
        \cli\line("Игра пока не сохраняется в базе данных.");
        \cli\line("Эта функция будет доступна в следующих версиях.");
        \cli\line("");
    }
}
