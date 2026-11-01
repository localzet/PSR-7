# Localzet PSR-7

[English documentation](README.md)

HTTP-сообщения, потоки и инструменты URI.

## Состояние и совместимость

Основные типы находятся в localzet\PSR7. Установка и проверка автозагрузки не доказывают соответствие PSR-7; нужны проверки неизменяемости сообщений, потоков, кодирования URI и совместимости.

Эта версия рассчитана на Localzet Server 7.x.

## Зависимости

- `php`: `^8.2`
- `localzet/server`: `^7.0`
- `psr/http-message`: `^2.0`

## Установка

```sh
composer require localzet/psr7
```

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
