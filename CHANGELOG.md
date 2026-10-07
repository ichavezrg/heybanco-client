# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere al [Versionado Semántico](https://semver.org/lang/es/).

## [Unreleased]

## [0.0.17] - 2026-10-07

### Added

-   Misc Webhooks: soporte de la API v1.1 (`/misc/v1.1/webhooks`) mediante `WebhookApiVersion` y `Webhook::withVersion()`. Por defecto sigue usando v1.0.
-   Misc Webhooks: `find`, `update` (PATCH), `notifications` y `resend` (estos dos últimos solo v1.1).
-   Misc Webhooks: paginación opcional en `findAll` y `showEvents`.
-   Misc Webhooks: `create` devuelve `webhookId` tomado del header `Location`.

### Changed

-   `WebhookTest` ahora usa respuestas simuladas de Guzzle en lugar de pegarle al sandbox.

## [0.0.2] - 2025-09-10

### Added

-   Agreement Healthcheck
-   Agreement Transactions
-   Add Docker enviroment for local test

## [0.0.1] - 2025-09-10

### Added

-   Configuración inicial del paquete de Composer
-   Documentación completa en README.md
-   Scripts de desarrollo en composer.json

## [0.0.1] - 2025-09-10

### Added

-   Cliente PHP para la API de HeyBanco
-   Soporte para autenticación mTLS
-   Implementación de firmas digitales JWE/JWS
-   Servicios CAAS (Customer as a Service):
    -   Agreement service para manejo de acuerdos
    -   Collection service para manejo de colecciones
    -   User service para manejo de usuarios
-   Cliente HTTP basado en Guzzle
-   Autoloading PSR-4
-   Soporte para PHP 8.1+
-   Tests unitarios con PHPUnit
-   Configuración para análisis estático con PHPStan
-   Configuración para verificación de estilo de código con PHP_CodeSniffer

### Security

-   Implementación de autenticación mutua TLS (mTLS)
-   Firma digital de requests con claves privadas
-   Validación de certificados del servidor

---

## Tipos de Cambios

-   `Added` para nuevas funcionalidades.
-   `Changed` para cambios en funcionalidades existentes.
-   `Deprecated` para funcionalidades que serán eliminadas pronto.
-   `Removed` para funcionalidades eliminadas.
-   `Fixed` para correcciones de bugs.
-   `Security` para vulnerabilidades.

[Unreleased]: https://github.com/ichavezrg/heybanco-client/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/ichavezrg/heybanco-client/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/ichavezrg/heybanco-client/releases/tag/v1.0.0
