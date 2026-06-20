<?php

namespace App\Enums;

enum LeadContactedStatus: string
{
    case PendingInvite = 'pending_invite';
    case EmailSent = 'email_sent';
}