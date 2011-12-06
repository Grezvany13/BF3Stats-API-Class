<?php

require_once('bf3stats.class.php');
require_once('stats.class.php');

$api = new BF3StatsAPI();

$data = $api->player('Grezvany13', 'pc', array());

$data = data2object($data);

print '<pre>';
var_export($data->stats->getScorePerMinute());
print '</pre>';
?>