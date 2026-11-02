# Localzet PSR-7

[Русская документация](README.ru.md)

HTTP message objects, streams and URI utilities.

## Status and compatibility

Core types live under localzet\PSR7. Installation and autoload checks are not proof of PSR-7 conformance; message immutability, stream behavior, URI encoding and interoperability need a dedicated test suite.

The current branch targets Localzet Server 7.x; the published stable `v3.0.2` requires Server `^4.1`.

## Dependencies

- `php`: `^8.2`
- `localzet/server`: `^7.0`
- `psr/http-message`: `^2.0`

## Installation

```sh
composer require localzet/psr7:dev-main
```

Use the current `dev-main` branch for Server 7. It is not a stable release; do not pin it in production before a compatible tag is published.

## Development checks

```sh
composer validate --strict
composer install
composer dump-autoload --optimize --strict-psr
composer audit
```

Installation, lint and autoload checks do not establish end-to-end behavior or production readiness.

## Author and license

Ivan Zorin (`localzet`), <creator@localzet.com>, https://www.localzet.com.
Source: https://github.com/localzet/PSR-7. AGPL-3.0-or-later; [LICENSE](LICENSE). Original copyright and third-party licenses remain applicable.

[Authors](.github/AUTHORS.md) · [Contributing](.github/CONTRIBUTING.md) · [Security](.github/SECURITY.md)
