#!/usr/bin/env php
<?php
// Проверяем путь к автозагрузчику в зависимости от способа установки пакета:
// 1. Если пакет установлен как зависимость через Composer - используем локальный vendor
// 2. Если пакет разрабатывается локально - используем автозагрузчик родительского проекта
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    require __DIR__ . '/../../../autoload.php';
}

use function fhj7438ht\Minesweeper\Controller\startGame;

startGame();