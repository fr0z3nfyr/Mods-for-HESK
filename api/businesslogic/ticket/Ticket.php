<?php

namespace BusinessLogic\Tickets;


class Ticket {
    static function fromDatabaseRow($row, $linkedTicketsRs, $heskSettings) {
        $ticket = new Ticket();
        $ticket->id = $row['id'];
        $ticket->trackingId = $row['trackid'];
        $ticket->name = $row['name'];
        $ticket->email = $row['email'];
        $ticket->categoryId = $row['category'];
        $ticket->priorityId = $row['priority'];
        $ticket->subject = $row['subject'];
        $ticket->message = $row['message'];
        $ticket->dateCreated = $row['dt'];
        $ticket->lastChanged = $row['lastchange'];
        $ticket->firstReplyDate = $row['firstreply'];
        $ticket->closedDate = $row['closedat'];
        $ticket->suggestedArticles = explode(',', $row['articles']);
        $ticket->ipAddress = $row['ip'];
        $ticket->language = $row['language'];
        $ticket->statusId = $row['status'];
        $ticket->openedBy = $row['openedby'];
        $ticket->firstReplyByUserId = $row['firstreplyby'];
        $ticket->closedByUserId = $row['closedby'];
        $ticket->numberOfReplies = $row['replies'];
        $ticket->numberOfStaffReplies = $row['staffreplies'];
        $ticket->ownerId = $row['owner'];
        $ticket->timeWorked = $row['time_worked'];
        $ticket->lastReplyBy = $row['lastreplier'];
        $ticket->lastReplier = $row['replierid'];
        $ticket->archived = intval($row['archive']) === 1;
        $ticket->locked = intval($row['locked']) === 1;

        if (trim($row['attachments']) !== '') {
            $attachments = explode(',', $row['attachments']);
            $attachmentArray = array();
            foreach ($attachments as $attachment) {
                $attachmentRow = explode('#', $attachment);
                $attachmentModel = new Attachment();

                $attachmentModel->id = $attachmentRow[0];
                $attachmentModel->fileName = $attachmentRow[1];
                $attachmentModel->savedName = $attachmentRow[2];

                $attachmentArray[] = $attachmentModel;
            }
            $ticket->attachments = $attachmentArray;
        }

        if (trim($row['merged']) !== '') {
            $ticket->mergedTicketIds = explode(',', $row['merged']);
        }

        $ticket->auditTrailHtml = $row['history'];

        $ticket->customFields = array();
        foreach ($heskSettings['custom_fields'] as $key => $value) {
            if ($value['use'] == 1 && hesk_is_custom_field_in_category($key, intval($ticket->categoryId))) {
                $ticket->customFields[str_replace('custom', '', $key)] = $row[$key];
            }
        }

        $ticket->linkedTicketIds = array();
        while ($linkedTicketsRow = hesk_dbFetchAssoc($linkedTicketsRs)) {
            $ticket->linkedTicketIds[] = $linkedTicketsRow['id'];
        }

        $ticket->location = array();
        $ticket->location[0] = $row['latitude'];
        $ticket->location[1] = $row['longitude'];

        $ticket->usesHtml = intval($row['html']) === 1;
        $ticket->userAgent = $row['user_agent'];
        $ticket->screenResolution = array();
        $ticket->screenResolution[0] = $row['screen_resolution_width'];
        $ticket->screenResolution[1] = $row['screen_resolution_height'];

        $ticket->dueDate = $row['due_date'];
        $ticket->dueDateOverdueEmailSent = $row['overdue_email_sent'] !== null && intval($row['overdue_email_sent']) === 1;

        return $ticket;
    }

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $trackingId;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $email;

    /**
     * @var int
     */
    public $categoryId;

    /**
     * @var int
     */
    public $priorityId;

    /**
     * @var string
     */
    public $subject;

    /**
     * @var string
     */
    public $message;

    /**
     * @var string
     */
    public $dateCreated;

    /**
     * @var string
     */
    public $lastChanged;

    /**
     * @var string|null
     */
    public $firstReplyDate;

    /**
     * @var string|null
     */
    public $closedDate;

    /**
     * @var string[]|null
     */
    public $suggestedArticles;

    /**
     * @var string
     */
    public $ipAddress;

    /**
     * @var string|null
     */
    public $language;

    /**
     * @var int
     */
    public $statusId;

    /**
     * @var int
     */
    public $openedBy;

    /**
     * @var int|null
     */
    public $firstReplyByUserId;

    /**
     * @var int|null
     */
    public $closedByUserId;

    /**
     * @var int
     */
    public $numberOfReplies;

    /**
     * @var int
     */
    public $numberOfStaffReplies;

    /**
     * @var int
     */
    public $ownerId;

    /**
     * @var string
     */
    public $timeWorked;

    /**
     * @var int
     */
    public $lastReplyBy;

    /**
     * @var int|null
     */
    public $lastReplier;

    /**
     * @var bool
     */
    public $archived;

    /**
     * @var bool
     */
    public $locked;

    /**
     * @var Attachment[]|null
     */
    public $attachments;

    /**
     * @var int[]|null
     */
    public $mergedTicketIds;

    /**
     * @var string
     */
    public $auditTrailHtml;

    /**
     * @var string[]
     */
    public $customFields;

    /**
     * @var int[]
     */
    public $linkedTicketIds;

    /**
     * @var float[]|null
     */
    public $location;

    /**
     * @var bool
     */
    public $usesHtml;

    /**
     * @var string|null
     */
    public $userAgent;

    /**
     * @var int[]|null
     */
    public $screenResolution;

    /**
     * @var string|null
     */
    public $dueDate;

    /**
     * @var bool|null
     */
    public $dueDateOverdueEmailSent;
}