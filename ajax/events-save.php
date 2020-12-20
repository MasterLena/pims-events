<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php';

$eventId = isset($_POST['eventId']) ? intval($_POST['eventId']) : null;

$saveEventInfo = new Milena\Pims\Events\PimsUserSaveEvents($eventId);

echo json_encode(array(
    'events' => $saveEventInfo->getEventSuccess(),
    'venue' => $saveEventInfo->getVenueSuccess(),
    'userEvents' => $saveEventInfo->getUserEventSuccess()
));

