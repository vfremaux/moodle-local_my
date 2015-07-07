<?php

require('../../config.php');

$userid = required_param('id', PARAM_INT);

require_login();

$range = (!empty($CFG->localmyheatmaprange)) ? $CFG->localmyheatmaprange : 6;
$start = time() - (DAYSECS * $range * 30);

$logmanger = get_log_manager();
$readers = $logmanger->get_readers('\core\log\sql_select_reader');
$reader = reset($readers);

if (empty($reader)) {
    return false; // No log reader found.
}

if ($reader instanceof \logstore_standard\log\store) {
    $table = 'logstore_standard_log';
    $timefield = 'timecreated';
} elseif($reader instanceof \logstore_legacy\log\store) {
    $table = 'log';
    $timefield = 'time';
} else{
    return;
}

$sql = "
    SELECT
        UNIX_TIMESTAMP(DATE_FORMAT(FROM_UNIXTIME($timefield), \"%Y-%m-%d\")) as daystamp,
        COUNT(*) as hits
    FROM
        {{$table}}
    WHERE
        $timefield >= ? AND
        userid = ?
    GROUP BY
        DATE_FORMAT(FROM_UNIXTIME($timefield), \"%Y-%m-%d\")
";

$maptable = array();
if ($heatmap = $DB->get_records_sql($sql, array($start, $userid))) {
    // print_object($heatmap);
    foreach ($heatmap as $mapevent) {
        $maptable[$mapevent->daystamp] = (int)$mapevent->hits;
    }
}

echo json_encode($maptable);
