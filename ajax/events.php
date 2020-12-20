<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-config.php';

$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
$dateTo = isset($_POST['dateTo']) ? $_POST['dateTo'] : '';
$dateFrom = isset($_POST['dateFrom']) ? $_POST['dateFrom'] : '';
$sort = isset($_POST['sort']) ? $_POST['sort'] : '';
$pageSize = ( isset($_POST['pagesize']) && !empty($_POST['pagesize']) ) ? intval($_POST['pagesize']) : 12;

$events = new Milena\Pims\Events\PimsEvents($pageSize,$dateFrom,$dateTo,$sort,$page);

echo ( !empty($events->getEvents()) ? json_encode(array("events" => $events->getEvents(), "next_page" => $events->getNextPage(), "prev_page" => $events->getPreviousPage())) : false );