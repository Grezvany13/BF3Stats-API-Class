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
$data = $api->player('Grezvany13', 'pc', array('clear', 'assignments'));

print '<pre>';
var_dump($data->stats);
print '<pre>';
/**/
?>


<?php
/*
He loops over all the possible unlocks (or service stars or medals),
takes the number which needs te be acquired (eg. 100 kills),
 takes that number which you have and calculates the difference.
 This new number is divided by the number which is required
 and you've got the order (when sorting from low to high) which unlock will be first.

And now the best part: at the statistics page the order is defined by the amount of time!
 This is calculated by taking the the result from before
 and by calculating the same type per minute (eg. total kills per minute) for that item (eg. weapon).
* /

$tmp = array();

// example only shows 1 weapon, but you should loop over all!
$aek = $data->stats->weapons->arAEK;
foreach( $aek->unlocks as $unlock ):
	// unlock not yet taken
	if( $unlock->curr !== $unlock->needed ):
		// take number you needed (here: kills needed till recieving this unlock)
		$need = ($unlock->needed - $unlock->curr);
		
		// get the type (here: kills)
		$type = strtolower($unlock->nname);
		
		// check if this value exists, since the "DICE unlocks" don't have it ;)
		if( !isset($aek->{$type}) ):
			continue;
		endif;
		
		// take the number from this type (here: amount of kills)
		$num = $aek->{$type};
		
		// get the time you used this weapon
		$time = $aek->time;
		
		// calculate the number per time (here: kills per minute)
		$npt = ( $time / $num );
		
		// calculate the amount of time needed for this unlock (here: kills per minute multiplied by the amount of kills needed)
		$time_needed = $npt * $need;
		
		// make the time readable
		$real_time_needed = $data->_niceTime($time_needed);
		
		// put it in an array, where the time needed is the key
		$tmp[$time_needed] = array(
			'name' => $unlock->name,
			'time' => $real_time_needed
		);
	endif;
endforeach;

// sort all unlocks by the key (= time)
ksort($tmp);

// loop over the array and show the next unlocks
var_dump($tmp);
/**/
?>

<h2>$api->playerlist</h2>
<?php
/**/ // remove space after second * to activate

// get a list of players
$data = $api->playerlist(array('Grezvany13', 'ZA-Tony'), 'pc', array('clear','global'));

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