<?php

if (!defined("BIORELS")) {
    header("Location:/");
}
$date1 = new DateTime(date('Y').'-01-01');
$date2 = new DateTime(date('Y-m-d'));
$interval = $date1->diff($date2);
changeValue("monitor_admin",'YEAR', $interval->days);

$date1 = new DateTime(date('Y-m').'-01');
$date2 = new DateTime(date('Y-m-d'));
$interval = $date1->diff($date2);
changeValue("monitor_admin",'MONTH', $interval->days);


?>
