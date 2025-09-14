# Minesweeper

Консольная игра "Сапёр" для PHP.

## Установка

### Через Composer (рекомендуется)

```bash
composer require fhj7438ht/minesweeper
```

После установки запустите игру:
```bash
./vendor/bin/minesweeper
```

### Установка из исходного кода

Минимальные требования:
- PHP >= 7.4
- Composer (глобально доступен как команда `composer`)

Установка зависимостей:
```bash
composer install
```

Запуск игры:
```bash
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

## Использование в коде

После установки пакета через Composer, вы можете использовать его в своем PHP коде:

```php
<?php
require 'vendor/autoload.php';

use fhj7438ht\Minesweeper\Controller;
use fhj7438ht\Minesweeper\View;

// Запуск игры
Controller\startGame();

// Или использование отдельных компонентов
View\startScreen();
```

## Ссылки

- **Packagist**: https://packagist.org/packages/fhj7438ht/minesweeper
- **Исходный код**: [GitHub](https://github.com/fhj7438ht/minesweeper)