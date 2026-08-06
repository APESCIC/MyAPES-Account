<?php

namespace App\Modules;

enum ModuleCodeStatus: string
{
    case Shipped = 'shipped';
    case CodeNotShipped = 'code_not_shipped';
    case Incompatible = 'incompatible';

    public function label(): string
    {
        return match ($this) {
            self::Shipped => 'Shipped',
            self::CodeNotShipped => 'Code not shipped',
            self::Incompatible => 'Incompatible',
        };
    }
}
