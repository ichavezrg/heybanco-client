<?php

namespace Ichavez\HeyBancoClient;

class HeyBancoClient
{
    public function __construct(public readonly Caas $caas)
    {
    }
}
