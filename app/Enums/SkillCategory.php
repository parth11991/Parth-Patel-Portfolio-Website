<?php

namespace App\Enums;

enum SkillCategory: string
{
    case Frontend = 'frontend';
    case Backend = 'backend';
    case Database = 'database';
    case Devops = 'devops';
    case Tool = 'tool';
    case Other = 'other';
}
