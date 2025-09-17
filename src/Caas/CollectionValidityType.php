<?php

namespace Ichavezrg\HeyBancoClient\Caas;

enum CollectionValidityType: string
{
    case DATE = 'DATE';
    case CHARGES = 'CHARGES';
    case UNLIMITED = 'UNLIMITED';
}
