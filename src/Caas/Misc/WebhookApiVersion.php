<?php

namespace Ichavezrg\HeyBancoClient\Caas\Misc;

/**
 * Versión de la API Misc Webhooks de HeyBanco (segmento de la ruta:
 * /misc/{version}/webhooks). Las suscripciones de cada versión son
 * independientes: un webhook dado de alta en v1.0 no aparece en v1.1.
 */
enum WebhookApiVersion: string
{
    case V1_0 = 'v1.0';
    case V1_1 = 'v1.1';
}
