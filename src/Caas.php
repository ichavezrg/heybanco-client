<?php

namespace Ichavezrg\HeyBancoClient;

use Ichavez\HeyBancoClient\Caas\Agreement;
use Ichavez\HeyBancoClient\Caas\Collection;
use Ichavez\HeyBancoClient\Caas\User;

class Caas
{
    public function __construct(
        public readonly Agreement $agreement,
        public readonly Collection $collection,
        public readonly User $user,
    ) {}
}
