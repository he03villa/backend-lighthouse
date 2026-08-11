<?php

namespace App\Enums;

enum FieldNoteVisibility: string
{
    case Private = 'private';
    case SharedFamily = 'shared_family';
    case SharedParticipant = 'shared_participant';
    case Public = 'public';
}
