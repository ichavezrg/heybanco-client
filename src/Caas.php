<?php

namespace Ichavezrg\HeyBancoClient;

use Ichavezrg\HeyBancoClient\Caas\Agreement;
use Ichavezrg\HeyBancoClient\Caas\Collection;
use Ichavezrg\HeyBancoClient\Caas\User;

class Caas
{
    public function __construct(
        public readonly Agreement $agreement,
        public readonly Collection $collection,
        public readonly User $user,
    ) {}
}
