# Minesweeper

Минимальные требования:
- PHP >= 7.4
- Composer (глобально доступен как команда `composer`)

Установка зависимостей:
```
composer install
```

Генерация автозагрузки (обязательно после изменения composer.json):
```
composer dump-autoload -o
```

Запуск игры:
```
php bin/minesweeper.php
```

Структура:
- `src/Controller.php` — пространство имён `fhj7438ht\Minesweeper\Controller`, функция `startGame()`.
- `src/View.php` — пространство имён `fhj7438ht\Minesweeper\View`, функции отображения.
- `bin/minesweeper.php` — исполняемый файл, загружает автозагрузчик и вызывает `startGame()`.

Composer:
- `require`: `wp-cli/php-cli-tools` — удобные функции для CLI (`cli\line`, `cli\prompt`).
- `autoload.files`: `src/Controller.php`, `src/View.php` — подключаются всегда при `require vendor/autoload.php`.
- `bin`: `bin/minesweeper.php` — исполняемый файл пакета.

