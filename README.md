# HeyBanco Client PHP

[![Latest Version](https://img.shields.io/packagist/v/ichavez/heybanco-client.svg?style=flat-square)](https://packagist.org/packages/ichavez/heybanco-client)
[![Total Downloads](https://img.shields.io/packagist/dt/ichavez/heybanco-client.svg?style=flat-square)](https://packagist.org/packages/ichavez/heybanco-client)
[![License](https://img.shields.io/packagist/l/ichavez/heybanco-client.svg?style=flat-square)](https://packagist.org/packages/ichavez/heybanco-client)

Cliente PHP para la API de HeyBanco que proporciona una interfaz simple y elegante para interactuar con los servicios CAAS (Customer as a Service) de HeyBanco.

## Características

- ✅ **Autenticación mTLS**: Soporte completo para autenticación mutua TLS
- ✅ **Firma digital**: Implementación de firmas JWE/JWS para seguridad
- ✅ **CAAS Services**: Soporte para Agreement, Collection y User services
- ✅ **PSR-4**: Autoloading compatible con PSR-4
- ✅ **PHP 8.1+**: Aprovecha las características modernas de PHP
- ✅ **Guzzle HTTP**: Cliente HTTP robusto y confiable

## Instalación

Puedes instalar el paquete vía Composer:

```bash
composer require ichavez/heybanco-client
```

## Requisitos

- PHP 8.1 o superior
- Extensión cURL
- Extensión JSON
- Certificados mTLS válidos de HeyBanco

## Uso Básico

### Configuración Inicial

```php
<?php

require_once 'vendor/autoload.php';

use Ichavez\HeyBancoClient\Client;
use Ichavez\HeyBancoClient\Auth;
use Ichavez\HeyBancoClient\Signature;
use Ichavez\HeyBancoClient\Caas;
use Ichavez\HeyBancoClient\HeyBancoClient;

// Configuración de conexión
$bApplication = "tu-b-application-id";
$mtlsKeystorePath = 'path/to/your/keystore.p12';
$mtlsKeystorePassword = 'tu-keystore-password';

// Crear cliente base
$client = new Client(
    host: 'https://sbox-api-tech.hey.inc',
    bApplication: $bApplication,
    mtlsKeystorePath: $mtlsKeystorePath,
    mtlsKeystorePassword: $mtlsKeystorePassword
);

// Configurar autenticación
$auth = new Auth($client);

// Configurar firma digital
$signature = new Signature(
    bApplication: $bApplication,
    mtlsCertificatePath: $mtlsKeystorePath,
    mtlsCertificatePassword: $mtlsKeystorePassword,
    privateKeyPath: 'path/to/private_key.pem',
    privateKeyPhrase: '',
    publicServerKeyPath: 'path/to/server_public_key.pem'
);

// Inicializar servicios CAAS
$caas = new Caas(
    new Caas\Agreement($client, $auth, $signature),
    new Caas\Collection($client, $auth),
    new Caas\User($client, $auth)
);

// Crear cliente principal
$heybanco = new HeyBancoClient($caas);
```

### Trabajar con Agreements

```php
// Obtener agreements
$agreements = $heybanco->caas->agreement->find(
    accountNumber: '220914510015',
    bTransaction: 'unique-transaction-id',
    clientId: 'your-client-id',
    clientSecret: 'your-client-secret'
);
```

### Trabajar con Collections

```php
// Operaciones con collections
$collections = $heybanco->caas->collection->find($agreementId);
```

### Trabajar con Users

```php
// Operaciones con usuarios
$users = $heybanco->caas->user->find($agreementId);
```

## Configuración de Entorno

### Variables de Entorno

Se recomienda usar variables de entorno para datos sensibles:

```bash
# .env
HEYBANCO_B_APPLICATION=tu-b-application-id
HEYBANCO_HOST=https://sbox-api-tech.hey.inc
HEYBANCO_MTLS_KEYSTORE_PATH=/path/to/keystore.p12
HEYBANCO_MTLS_KEYSTORE_PASSWORD=tu-password
HEYBANCO_PRIVATE_KEY_PATH=/path/to/private_key.pem
HEYBANCO_PUBLIC_SERVER_KEY_PATH=/path/to/server_public_key.pem
HEYBANCO_CLIENT_ID=tu-client-id
HEYBANCO_CLIENT_SECRET=tu-client-secret
```

### Ejemplo con Variables de Entorno

```php
$client = new Client(
    host: $_ENV['HEYBANCO_HOST'],
    bApplication: $_ENV['HEYBANCO_B_APPLICATION'],
    mtlsKeystorePath: $_ENV['HEYBANCO_MTLS_KEYSTORE_PATH'],
    mtlsKeystorePassword: $_ENV['HEYBANCO_MTLS_KEYSTORE_PASSWORD']
);
```

## Manejo de Errores

```php
try {
    $agreements = $heybanco->caas->agreement->getAgreements(
        accountNumber: '220914510015',
        bTransaction: 'unique-transaction-id',
        clientId: 'your-client-id',
        clientSecret: 'your-client-secret'
    );
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Testing

Ejecutar las pruebas:

```bash
# Ejecutar todas las pruebas
composer test

# Ejecutar pruebas con coverage
composer test-coverage

# Análisis estático con PHPStan
composer phpstan

# Verificar estilo de código
composer cs-check

# Corregir estilo de código automáticamente
composer cs-fix
```

## Desarrollo

### Estructura del Proyecto

```
src/
├── Auth.php              # Manejo de autenticación
├── Client.php            # Cliente HTTP base
├── HeyBancoClient.php     # Cliente principal
├── Signature.php         # Manejo de firmas digitales
├── Caas.php              # Contenedor de servicios CAAS
└── Caas/
    ├── Agreement.php     # Servicio de agreements
    ├── Collection.php    # Servicio de collections
    └── User.php          # Servicio de usuarios
```

### Contribuir

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agrega nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crea un Pull Request

## Seguridad

Si descubres alguna vulnerabilidad de seguridad, por favor envía un email a [ichavez@redgirasol.com](mailto:ichavez@redgirasol.com) en lugar de usar el issue tracker.

## Licencia

Este paquete es software de código abierto licenciado bajo la [Licencia MIT](LICENSE).

## Changelog

Por favor consulta [CHANGELOG](CHANGELOG.md) para más información sobre los cambios recientes.

## Créditos

- [Ivan Chavez](https://github.com/ichavezrg)
- [Todos los Contribuidores](../../contributors)

## Soporte

- [Documentación](https://github.com/ichavezrg/heybanco-client)
- [Issues](https://github.com/ichavezrg/heybanco-client/issues)
