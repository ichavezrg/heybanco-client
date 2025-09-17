<?php

namespace Ichavezrg\HeyBancoClient\Caas;

use DateTimeImmutable;

class CollectionValidity
{
    private function __construct(
        public readonly CollectionValidityType $type,
        public readonly ?string $validUntil,
    ) {}

    public static function fromDate(DateTimeImmutable $validUntil): static
    {
        return new static(
            type: CollectionValidityType::DATE,
            validUntil: $validUntil->format('Y-m-d'),
        );
    }

    public static function fromCharge(int $numberCharges): static
    {
        return new static(
            type: CollectionValidityType::CHARGES,
            validUntil: $numberCharges,
        );
    }

    public static function unlimited(): static
    {
        return new static(
            type: CollectionValidityType::UNLIMITED,
            validUntil: null,
        );
    }

    public function toArray(): array
    {
        $response = [
            'type' => $this->type->value,
        ];

        if ($this->validUntil !== null) {
            $response['validUntil'] = $this->validUntil;
        }

        return $response;
    }
}
