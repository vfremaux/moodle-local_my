<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    local_my
 * @category   local
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$userid = required_param('id', PARAM_INT);

// Security.

require_login();

$cache = cache::make('local_my', 'heatmap');
$config = get_config('local_my');

$heatmapcachedtable = $cache->get('heatmapdata');
if (!$heatmapcachedtable) {

    $range = (!empty($config->heatmaprange)) ? $config->heatmaprange : 6;
    $start = time() - (DAYSECS * $range * 30);

    $logmanager = get_log_manager();
    $readers = $logmanager->get_readers('\core\log\sql_reader');
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
        foreach ($heatmap as $mapevent) {
            $maptable[$mapevent->daystamp] = (int)$mapevent->hits;
        }
    }
    $cache->set('heatmapdata', json_encode($maptable));
    echo json_encode($maptable);
    die;
}

echo $heatmapcachedtable;
