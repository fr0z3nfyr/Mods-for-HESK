<?php

namespace DataAccess\Calendar;


use BusinessLogic\Calendar\AbstractEvent;
use BusinessLogic\Calendar\CalendarEvent;
use BusinessLogic\Calendar\ReminderUnit;
use BusinessLogic\Calendar\SearchEventsFilter;
use BusinessLogic\Calendar\TicketEvent;
use Core\Constants\Priority;
use DataAccess\CommonDao;

class CalendarGateway extends CommonDao {
    /**
     * @param $startTime int
     * @param $endTime int
     * @param $searchEventsFilter SearchEventsFilter
     * @param $heskSettings array
     * @return AbstractEvent[]
     */
    public function getEventsForStaff($startTime, $endTime, $searchEventsFilter, $heskSettings) {
        $this->init();

        $events = array();

        $startTimeSql = "CONVERT_TZ(FROM_UNIXTIME(" . hesk_dbEscape($startTime) . " / 1000), @@session.time_zone, '+00:00')";
        $endTimeSql = "CONVERT_TZ(FROM_UNIXTIME(" . hesk_dbEscape($endTime) . " / 1000), @@session.time_zone, '+00:00')";

        // EVENTS
        $sql = "SELECT `events`.*, `categories`.`name` AS `category_name`, `categories`.`background_color` AS `background_color`,
                    `categories`.`foreground_color` AS `foreground_color`, `categories`.`display_border_outline` AS `display_border`,
                    `reminders`.`amount` AS `reminder_value`, `reminders`.`unit` AS `reminder_unit`
                FROM `" . hesk_dbEscape($heskSettings['db_pfix']) . "calendar_event` AS `events`
                INNER JOIN `" . hesk_dbEscape($heskSettings['db_pfix']) . "categories` AS `categories`
                    ON `events`.`category` = `categories`.`id`
                LEFT JOIN `" . hesk_dbEscape($heskSettings['db_pfix']) . "calendar_event_reminder` AS `reminders`
                    ON `reminders`.`user_id` = " . intval($searchEventsFilter->reminderUserId) . "
                    AND `reminders`.`event_id` = `events`.`id`
                WHERE NOT (`end` < {$startTimeSql} OR `start` > {$endTimeSql})
                    AND `categories`.`usage` <> 1
                    AND `categories`.`type` = '0'";

        if (!empty($searchEventsFilter->categories)) {
            $categoriesAsString = implode(',', $searchEventsFilter->categories);
            $sql .= " AND `events`.`category` IN (" . $categoriesAsString . ")";
        }

        $rs = hesk_dbQuery($sql);
        while ($row = hesk_dbFetchAssoc($rs)) {
            $event = new CalendarEvent();
            $event->id = intval($row['id']);
            $event->startTime = $row['start'];
            $event->endTime = $row['end'];
            $event->allDay = $row['all_day'] ? true : false;
            $event->title = $row['name'];
            $event->location = $row['location'];
            $event->comments = $row['comments'];
            $event->categoryId = intval($row['category']);
            $event->categoryName = $row['category_name'];
            $event->backgroundColor = $row['background_color'];
            $event->foregroundColor = $row['foreground_color'];
            $event->displayBorder = $row['display_border'] === '1';
            $event->reminderValue = $row['reminder_value'] === null ? null : floatval($row['reminder_value']);
            $event->reminderUnits = $row['reminder_unit'] === null ? null : ReminderUnit::getByValue($row['reminder_unit']);

            $events[] = $event;
        }

        // TICKETS
        if ($searchEventsFilter->includeTickets) {
            $oldTimeSetting = $heskSettings['timeformat'];
            $heskSettings['timeformat'] = 'Y-m-d';
            $currentDate = hesk_date();
            $heskSettings['timeformat'] = $oldTimeSetting;

            $sql = "SELECT `trackid`, `subject`, `due_date`, `category`, `categories`.`name` AS `category_name`, `categories`.`background_color` AS `background_color`, 
                `categories`.`foreground_color` AS `foreground_color`, `categories`.`display_border_outline` AS `display_border`,
                  CASE WHEN `due_date` < '{$currentDate}' THEN 1 ELSE 0 END AS `overdue`, `owner`.`name` AS `owner_name`, `tickets`.`owner` AS `owner_id`,
                   `tickets`.`priority` AS `priority`
                FROM `" . hesk_dbEscape($heskSettings['db_pfix']) . "tickets` AS `tickets`
                INNER JOIN `" . hesk_dbEscape($heskSettings['db_pfix']) . "categories` AS `categories`
                    ON `categories`.`id` = `tickets`.`category`
                    AND `categories`.`usage` <> 2
                LEFT JOIN `" . hesk_dbEscape($heskSettings['db_pfix']) . "users` AS `owner`
                    ON `tickets`.`owner` = `owner`.`id`
                WHERE `due_date` >= {$startTimeSql}
                AND `due_date` <= {$endTimeSql}
                AND `status` IN (SELECT `id` FROM `" . hesk_dbEscape($heskSettings['db_pfix']) . "statuses` WHERE `IsClosed` = 0) 
                AND (`owner` = " . $searchEventsFilter->reminderUserId;

            if ($searchEventsFilter->includeUnassignedTickets) {
                $sql .= " OR `owner` = 0 ";
            }

            if ($searchEventsFilter->includeTicketsAssignedToOthers) {
                $sql .= " OR `owner` NOT IN (0, " . $searchEventsFilter->reminderUserId . ") ";
            }

            $sql .= ")";

            if (!empty($searchEventsFilter->categories)) {
                $categoriesAsString = implode(',', $searchEventsFilter->categories);
                $sql .= " AND `tickets`.`category` IN (" . $categoriesAsString . ")";
            }

            $rs = hesk_dbQuery($sql);
            while ($row = hesk_dbFetchAssoc($rs)) {
                $event = new TicketEvent();
                $event->trackingId = $row['trackid'];
                $event->subject = $row['subject'];
                $event->title = $row['subject'];
                $event->startTime = $row['due_date'];
                $event->url = $heskSettings['hesk_url'] . '/' . $heskSettings['admin_dir'] . '/admin_ticket.php?track=' . $event->trackingId;
                $event->categoryId = intval($row['category']);
                $event->categoryName = $row['category_name'];
                $event->backgroundColor = $row['background_color'];
                $event->foregroundColor = $row['foreground_color'];
                $event->displayBorder = $row['display_border'] === '0';
                $event->owner = $row['owner_name'];
                $event->priority = Priority::getByValue($row['priority']);

                $events[] = $event;
            }
        }

        $this->close();

        return $events;
    }
}