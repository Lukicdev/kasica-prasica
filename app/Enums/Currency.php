<?php

namespace App\Enums;

enum Currency: string
{
    case Rsd = 'RSD';
    case Eur = 'EUR';

    public static function base(): self
    {
        return self::Rsd;
    }

    public function isBase(): bool
    {
        return $this === self::base();
    }

    public function label(): string
    {
        return match ($this) {
            self::Rsd => 'Serbian dinar',
            self::Eur => 'Euro',
        };
    }
}
