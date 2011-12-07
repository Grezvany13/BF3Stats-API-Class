<!DOCTYPE html>
<html>
	<head>
		<meta enctype="utf-8">
		<title>BF3Stats API Class - Examples</title>
	</head>
	<body>
<?php

require_once('bf3stats.class.php');
require_once('stats.class.php');

$api = new BF3StatsAPI();

$data = $api->player('Grezvany13', 'pc', array('all'));

$data = data2object($data);

?>
<pre>
<?php var_dump(($data->stats)); ?>
</pre>


	</body>
</html>