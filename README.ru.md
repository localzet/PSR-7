# Localzet PSR-7

[English documentation](README.md)

HTTP-сообщения, потоки и инструменты URI.

## Состояние и совместимость

Основные типы находятся в localzet\PSR7. Установка и проверка автозагрузки не доказывают соответствие PSR-7; нужны проверки неизменяемости сообщений, потоков, кодирования URI и совместимости.

Стабильный выпуск `v4.0.0` рассчитан на Localzet Server `^7.0`. Исторический выпуск `v3.0.2` требует Server `^4.1`.

## Зависимости

- `php`: `^8.2`
- `localzet/server`: `^7.0`
- `psr/http-message`: `^2.0`

## Установка

```sh
composer require localzet/psr7:^4.0
```

Используйте PSR-7 `4.x` с Server 7. Историческую линию `3.x` устанавливайте только в приложения на Server 4.1; не смешивайте эти основные версии.

## Проверки разработки

```sh
composer validate --strict
composer install
composer dump-autoload --optimize --strict-psr
composer audit
```

Установка, lint и автозагрузка не подтверждают сквозное поведение или готовность к эксплуатации.

## Автор и лицензия

Ivan Zorin (`localzet`), <creator@localzet.com>, https://www.localzet.com.
Source: https://github.com/localzet/PSR-7. AGPL-3.0-or-later; [LICENSE](LICENSE). Сохраняются исходные уведомления авторов и лицензии сторонних компонентов.

[Authors](.github/AUTHORS.md) · [Contributing](.github/CONTRIBUTING.md) · [Security](.github/SECURITY.md)
