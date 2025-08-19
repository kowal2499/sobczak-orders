<?php

namespace App\Module\Authorization\ValueObject;

enum GrantType: string
{
    case Select = 'select';
    case Boolean = 'boolean';
}
