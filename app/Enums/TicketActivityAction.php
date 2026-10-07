<?php

namespace App\Enums;

enum TicketActivityAction: string
{
    case Created = 'created';
    case Claimed = 'claimed';
    case Assigned = 'assigned';
    case StatusChanged = 'status_changed';
    case PriorityChanged = 'priority_changed';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Reopened = 'reopened';
    case CommentAdded = 'comment_added';
    case AttachmentAdded = 'attachment_added';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
