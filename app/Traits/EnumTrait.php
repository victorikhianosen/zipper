<?php

namespace App\Traits;

trait EnumTrait
{
    /**
     * @return array<int, string>
     */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, string>
     */
    public static function getKeyValues()
    {
        return array_column(self::cases(), 'value', 'value');
    }
}
