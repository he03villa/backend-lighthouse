<?php

namespace App\Enums;

enum ActivityType: string
{
    case Upload = 'upload';
    case Reflection = 'reflection';
    case Completion = 'completion';
    case Quiz = 'quiz';
}
