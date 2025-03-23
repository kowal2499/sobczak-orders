<?php

namespace App\ValueObject\Authorization;

enum GrantType: string
{
    case Select = 'select';
    case Boolean = 'boolean';
}
