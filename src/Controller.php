<?php

namespace fhj7438ht\Minesweeper;

use fhj7438ht\Minesweeper\View;
use fhj7438ht\Minesweeper\Game;
use fhj7438ht\Minesweeper\Database;
use fhj7438ht\Minesweeper\GameRepository;

/**
 * Контроллер для обработки командной строки и управления игрой
 */
class Controller
{
    private static ?Database $database = null;
    private static ?GameRepository $gameRepository = null;

    /**
     * Инициализация базы данных
     */
    private static function initializeDatabase(): void
    {
        if (self::$database === null) {
            self::$database = new Database();
            self::$gameRepository = new GameRepository(self::$database);
        }
    }

    /**
     * Обработка аргументов командной строки и запуск игры
     */
    public static function startGame(): void
    {
        self::initializeDatabase();
        
        $args = getopt('nl:r:h', ['new', 'list', 'replay:', 'help']);

        if (isset($args['h']) || isset($args['help'])) {
            View::showHelp();
            return;
        }

        if (isset($args['l']) || isset($args['list'])) {
            self::showGameList();
            return;
        }

        if (isset($args['r']) || isset($args['replay'])) {
            $gameId = $args['r'] ?? $args['replay'] ?? null;
            if ($gameId === null) {
                View::showError('Необходимо указать ID игры для режима --replay');
                return;
            }
            self::replayGame((int)$gameId);
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

        // Получение имени игрока
        $playerName = View::promptForInput('Введите ваше имя (только английские буквы): ');
        $playerName = trim($playerName);
        
        if (empty($playerName)) {
            $playerName = 'Player';
        }
        
        // Валидация имени (только английские буквы)
        if (!preg_match('/^[a-zA-Z\s]+$/', $playerName)) {
            View::showError('Имя должно содержать только английские буквы и пробелы!');
            return;
        }

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
        
        // Сохраняем игру в базу данных
        $gameId = self::$gameRepository->saveGame($game, $playerName);
        View::showMessage("Игра сохранена с ID: {$gameId}");
        
        self::playGame($game, $gameId, $playerName);
    }

    /**
     * Основной игровой цикл
     */
    private static function playGame(Game $game, int $gameId, string $playerName): void
    {
        $dimensions = $game->getDimensions();
        $rows = $dimensions['rows'];
        $cols = $dimensions['cols'];

        while (!$game->isGameOver() && !$game->isGameWon()) {
            View::displayBoard($game);

            $input = View::promptForInput('Введите координаты (строка столбец), M строка столбец для отметки, или q для выхода: ');

            if (empty($input)) {
                continue;
            }

            $input = trim($input);
            
            // Проверка на выход из игры
            if (strtolower($input) === 'q' || strtolower($input) === 'quit') {
                View::showMessage("Игра сохранена. Вы можете продолжить позже с помощью команды:--replay {$gameId}");
                return;
            }

            $parts = explode(' ', $input);

            if (count($parts) === 3 && strtoupper($parts[0]) === 'M') {
                // Отметка ячейки
                $row = (int)$parts[1] - 1;
                $col = (int)$parts[2] - 1;

                if ($game->flagCell($row, $col)) {
                    View::showMessage('Ячейка отмечена/снята отметка');
                    
                    // Логируем ход
                    $moveNumber = self::$gameRepository->getNextMoveNumber($gameId);
                    self::$gameRepository->saveMove($gameId, $moveNumber, $row, $col, 'flag', 'отмечена');
                    
                    // Обновляем игру в базе данных
                    self::$gameRepository->updateGame($gameId, $game);
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
                    
                    // Определяем результат хода
                    $result = 'мины нет';
                    if ($game->isGameOver()) {
                        $result = 'взорвался';
                    } elseif ($game->isGameWon()) {
                        $result = 'выиграл';
                    }
                    
                    // Логируем ход
                    $moveNumber = self::$gameRepository->getNextMoveNumber($gameId);
                    self::$gameRepository->saveMove($gameId, $moveNumber, $row, $col, 'open', $result);
                    
                    // Обновляем игру в базе данных
                    self::$gameRepository->updateGame($gameId, $game);
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
        
        // Финальное обновление игры в базе данных
        self::$gameRepository->updateGame($gameId, $game);
    }

    /**
     * Показать список сохраненных игр
     */
    private static function showGameList(): void
    {
        $games = self::$gameRepository->getAllGames();
        View::showGameList($games);
    }

    /**
     * Воспроизведение игры по ID
     */
    private static function replayGame(int $gameId): void
    {
        $game = self::$gameRepository->loadGame($gameId);
        
        if ($game === null) {
            View::showError("Игра с ID {$gameId} не найдена!");
            return;
        }

        $moves = self::$gameRepository->getGameMoves($gameId);
        
        View::showReplayMessage($gameId);
        
        if (empty($moves)) {
            View::showMessage("В этой игре не было сделано ходов.");
            View::displayBoard($game);
            return;
        }

        View::showMessage("Воспроизведение всех ходов игры:");
        View::showMessage("");
        
        // Показываем финальное состояние игры
        View::displayBoard($game);
        View::showMessage("");
        
        // Показываем все ходы
        View::showMessage("История ходов:");
        foreach ($moves as $move) {
            $row = $move['row'] + 1; // Показываем координаты в пользовательском формате
            $col = $move['col'] + 1;
            $action = $move['action'] === 'open' ? 'открыть' : 'отметить';
            $result = $move['result'];
            
            View::showMessage("Ход {$move['move_number']}: ({$row}, {$col}) - {$action} - {$result}");
        }
        
        View::showMessage("");
        
        // Если игра не завершена, предлагаем продолжить
        if (!$game->isGameOver() && !$game->isGameWon()) {
            $continue = View::promptForInput("Игра не завершена. Продолжить играть? (y/n): ");
            if (strtolower($continue) === 'y' || strtolower($continue) === 'yes') {
                View::showMessage("Продолжение игры...");
                self::playGame($game, $gameId, 'Unknown');
            } else {
                View::showMessage("Воспроизведение завершено.");
            }
        } else {
            View::showMessage("Воспроизведение завершено.");
        }
    }
}
