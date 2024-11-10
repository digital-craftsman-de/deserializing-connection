<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\Doctrine;

use DigitalCraftsman\DeserializingConnection\Doctrine\StringNormalizableType;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\SearchTerm;

final class SearchTermType extends StringNormalizableType
{
    #[\Override]
    public static function getTypeName(): string
    {
        return 'search_term';
    }

    #[\Override]
    public static function getClass(): string
    {
        return SearchTerm::class;
    }
}
