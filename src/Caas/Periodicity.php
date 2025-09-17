<?php

namespace Ichavezrg\HeyBancoClient\Caas;

enum Periodicity: string
{
    case DAILY = 'D';
    case WEEKLY = 'S';
    case SEMIMONTHLY = 'Q';
    case MONTHLY = 'M';
}
