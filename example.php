<!DOCTYPE html>
<html>
	<head>
		<meta enctype="utf-8">
		<title>BF3Stats API Class - Examples</title>
	</head>
	<body>
		<h1>BF3Stats API Class - Examples</h1>
		<p>Please check the source of this file for more information</p>
		<p>&nbsp;</p>
<?php

require_once('bf3stats.class.php');

$api = new BF3StatsAPI();

// set a time correction in seconds in case your server time and the API server time are different (required for signed requests)
// $api->setTimeCorrection( 0 );

// enable, disable or specify the caching type. (not fully implemented yet)
// $api->setCache( 'files' );

// set the date format based on strftime <http://www.php.net/manual/en/function.strftime.php>
// $api->setDateFormat( '%d-%m-%Y' );

// set the time format. Currently only 'battlelog'
// $api->setTimeFormat( 'battlelog' );

// set the relative path to the images (rank images, ribbons, medals, etc). Images can be downloaded here <http://bf3stats.com/bf3stats.com_images.zip>
// $api->setImagePath( 'bf3' );

// set the relative path where the cache files should be placed. Make sure it has READ and WRITE rights (chmod 665 should be enough, chmod 777 will always work)
// $api->setCachePath( '_cache' );

?>

<h2>$api->player</h2>
<?php
/** / // remove space after second * to activate

// get a single player
$data = $api->player('Grezvany13', 'pc', array('clear','global'));

print '<pre>';
var_dump($data);
print '<pre>';
/**/
?>

<h2>$api->playerlist</h2>
<?php
/** / // remove space after second * to activate

// get a list of players
$data = $api->playerlist(array('Grezvany13'), 'pc', array('clear','global'));

print '<pre>';
var_dump($data);
print '<pre>';
/**/
?>

<h2>$api->onlinestats</h2>
<?php
/** / // remove space after second * to activate

// get a list of players
$data = $api->onlinestats();

print '<pre>';
var_dump($data);
print '<pre>';
/**/
?>


	</body>
</html>