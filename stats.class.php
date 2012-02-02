<?php

define( 'DS', DIRECTORY_SEPARATOR );

/**
 * Create new objects when needed
 */
function __load( $class, $extend = null) {
	if($extend):
		eval('class '.$class.' extends '.$extend.' {}');
	endif;
	return new $class;
}

function __autoload( $class ) {
	eval('class '.$class.' {}');
}
/**
 * check if the current class has a known parent and add the "special" class
 */
function hasExtend( $class, $parent = null ) {
	switch( $parent ):
		default: case null:	return false;			break;
		case 'weapons':		return '__weapon';		break;
		case 'ranking':		return '__ranking';		break;
		case 'teams':		return '__team';		break;
		case 'gamemodes':	return '__gamemode';	break;
		case 'kits':		return '__kit';			break;
	endswitch;
	return false;
}

/**
 * takes the response from the API and creates one massive object
 * when the class already exists it can use the predefined methods (see class _stats)
 */
function data2object( $array, $class = 'data', $settings = array(), $parent = null, $special = null ) {
	$nclass = '_'.strtolower($class);
	// check if the new class needs something "special"
	if( $extend = hasExtend($class, $parent) ):
		$object = __load($nclass, $extend);
	else:
		$object = __load($nclass);
	endif;
	// set some settings when needed
	if(method_exists($object, 'setSettings')):
		$object->setSettings( $settings );
	endif;
	if( !empty($array) && is_array($array) ):
		foreach($array as $key => $value):
			// if key is numeric, it's an array (eg. nextranks)
			if( is_numeric($key) || ($class === 'data' && $special === '@playerlist') ):
				if(!is_array($object)) $object = array();
				if($class === 'data' && $special === '@playerlist'):
					$object[$key] = data2object( $value, '_player', $settings, $class, $special );
				elseif( is_array($value) ):
					$object[$key] = data2object( $value, $key, $settings, $class, $special );
				else:
					$object[$key] = $value;
				endif;
			else:
				// if value is an array, loop over it again
				if( is_array($value) ):
					$object->{$key} = data2object( $value, $key, $settings, $class, $special );
				else:
					// if value is not an array, put it as a variable
					$object->{$key} = $value;
					// check for special values like date, time, images, etc
					if( strstr($key, 'img') == true && is_callable(array('_data', '_htmlImage')) ):
						$printkey = 'html_'.$key;
						$object->{$printkey} = _data::_htmlImage( $value );
					endif;
					if( strstr($key, 'date') == true && is_callable(array('_data', '_niceDate')) ):
						$printkey = 'nice_'.$key;
						$object->{$printkey} = _data::_niceDate( $value );
					endif;
					if( strstr($key, 'time') == true && is_callable(array('_data', '_niceTime')) ):
						$printkey = 'nice_'.$key;
						$object->{$printkey} = _data::_niceTime( $value );
					endif;
				endif;
			endif;
		endforeach;
	endif;
	// run method init() when available
	if(method_exists($object, 'init')):
		$object->init();
	endif;
	return $object;
}

/**
 * PREDEFINED OBJECTS
 */
 
class _data {
	private static $settings = array();

	public function _htmlImage( $uri, $alt = '' ) {
		return '<img src="'. sprintf(self::$settings['img_path'].DS.'%s', str_replace('/', DS, $uri)).'" alt="'.$alt.'" />';
	}
	
	public function setSettings( $settings = array() ) {
		self::$settings = $settings;
	}

	public function _niceDate( $date ) {
		if( is_numeric($date) ):
			$time = (float)$date;
		else:
			return;
		endif;
		
		$datetime = strftime( self::$settings['date_format'], ($time));
		
		return $datetime;
	}
	
	/**
	 * timer
	 *
	 * @static
	 * @access	Public
	 * @param	integer|array	$data		time in seconds or API result array (for default 'total time played')
	 * @param	string			$format		Which time formats should be shown d (day), h (hour), i (minute) and s (second)
	 * @param	boolean			$letters	TRUE/FALSE to add letter after the formatted value (eg. M after amount of minutes)
	 * @return	string						default: nH nnM
	 */
	public static function _niceTime( $data, $format = 'h i', $letters = true ) {
		if( is_numeric($data) ):
			$time = (float)($data * 1000)+1; // +1 is ugly fix
		else:
			return;
		endif;

		$d = 1000*24*60*60; // 1 day in ms
		$h = 1000*60*60; // 1 hour in ms
		$i = 1000*60; // 1 minute in ms
		$s = 1000; // 1 second in ms
		
		$format = str_split($format);
		$string = '';

		if( in_array( 'd', $format ) ):
			if( $time % $d !== 0 ):
				$tmp = floor( $time / $d );
				$days = $tmp;
				$time = $time - ( $tmp * $d );
			endif;
		endif;
		if( in_array( 'h', $format ) ):
			if( $time % $h !== 0 ):
				$tmp = floor( $time / $h );
				$hours = $tmp;
				$time = $time - ( $tmp * $h );
			endif;
		endif;
		if( in_array( 'i', $format ) ):
			if( $time % $i !== 0 ):
				$tmp = floor( $time / $i );
				$minutes = $tmp;
				$time = $time - ( $tmp * $i );
			endif;
		endif;
		if( in_array( 's', $format ) ):
			if( $time % $s !== 0 ):
				$tmp = floor( $time / $s );
				$seconds = $tmp;
				$time = $time - ( $tmp * $s );
			endif;
		endif;

		foreach( $format as $char ):
			switch( $char ):
				default:
					$string .= (string)$char;
				break;
				case ' ':
					$string .= ' ';
				break;
				case 'd':
					if(isset($days)):
						$string .= $days;
						if($letters) $string .= 'D';
					endif;
				break;
				case 'h':
					if(isset($hours)) {
						$string .= $hours;
						if($letters) $string .= 'H';
					/*} else {
						$hours = str_pad( '', 2, '0', STR_PAD_LEFT );
						$string .= $hours;
						if($letters) $string .= 'H';*/
					}
				break;
				case 'i':
					if(isset($minutes)):
						$string .= str_pad( $minutes, 2, '0', STR_PAD_LEFT );
						if($letters) $string .= 'M';
					endif;
				break;
				case 's':
					if(isset($seconds)):
						$string .= str_pad( $seconds, 2, '0', STR_PAD_LEFT );
						if($letters) $string .= 'S';
					endif;
				break;
			endswitch;
		endforeach;
		
		return $string;
	}
	
}

/**
 * _stats
 */
class _stats {
	public function init() {
		// not the best way to do this, but is needed since it requires 2 subclasses
		if( isset($this->global->time) && isset($this->scores->score) ):
			$num = 0;
			if( $this->scores->score && $this->global->time ) $num = $this->scores->score / ( $this->global->time / 60 );
			$this->global->spm = (float)number_format( $num, 3 );
		endif;
		
		
		if(
			isset($this->scores->score) &&
			isset($this->global->spm) &&
			isset($this->global->rounds) &&
			isset($this->global->rounds_finished) &&
			isset($this->global->elo) &&
			isset($this->global->kills) &&
			isset($this->global->kdr) &&
			isset($this->global->accuracy) &&
			isset($this->global->saviorkills) &&
			isset($this->global->killassists) &&
			isset($this->scores->award) &&
			isset($this->scores->objective) &&
			isset($this->scores->squad) &&
			isset($this->scores->team)
		):
			$SCR = $data->stats->scores->score;				//Score
			$SPM = $data->stats->global->spm;				//Score Per Minute
			$RDP = $data->stats->global->rounds;			//Rounds Played
			$FRD = $data->stats->global->rounds_finished;	//Finished Rounds
			$SKL = $data->stats->global->elo;				//Skill
			$KLS = $data->stats->global->kills;				//Kills
			$KDR = $data->stats->global->kdr;				//Kill/Death Ratio
			$ACC = $data->stats->global->accuracy;			//Accuracy
			$SVK = $data->stats->global->saviorkills;		//Savior Kills
			$KAS = $data->stats->global->killassists;		//Kill Assists
			$AWS = $data->stats->scores->award;				//Award Score
			$OBS = $data->stats->scores->objective;			//Objective Score
			$SQS = $data->stats->scores->squad;				//Squad Score
			$TSC = $data->stats->scores->team;				//Team Score
			//First part of the formula is to fine a players "Raw Score" (or RSC):
			$RSC = $SCR - $AWS;
			//Next is the actual formula:
			$BFR = number_format(($SPM + $KDR + (($SQS/$RSC)*100*$FRD) + (($TSC/$RSC)*100*$FRD) + $ACC + (($OBS/$RSC)*100*$FRD) + (($SVK / $KLS)*100*$FRD) + (($KAS/$KLS)*5*$FRD) + $SKL) / 100, 3 );
			
			$this->bfr = $BFR;
		endif;
	}
}
class _global {
	public function init() {
		if( isset($this->kills) && isset($this->deaths) ):
			$this->kdr = (float)number_format( ($this->kills / $this->deaths), 3 );
		endif;
		if( isset($this->wins) && isset($this->losses) ):
			$this->wlr = (float)number_format( ($this->wins / $this->losses), 3 );
		endif;
		if( isset($this->kills) && isset($this->headshots) ):
			$this->headshot_perc = (float)number_format( ((100 / $this->kills) * $this->headshots), 3 );
		endif;
		if( isset($this->rounds) && isset($this->wins) && isset($this->losses) ):
			$this->rounds_finished = (float)number_format( (( 100 / ($this->wins + $this->losses)) * $this->rounds), 3 );
		endif;
		if( isset($this->shots) && isset($this->hits) && $this->shots>0 && $this->hits>0 ):
			$this->accuracy = (float)number_format( ((100 / $this->shots ) * $this->hits), 3 );
		endif;
	}
}
class _scores {
	public function init() {
		/**
		 * Scores per Class (or Kit)
		 *
		 * Assault = 220000
		 * Engineer = 145000
		 * Support = 170000
		 * Recon = 195000
		 */
		if(isset($this->assault)):
			$this->assault_stars = (int)floor($this->assault / 220000);
		endif;
		if(isset($this->engineer)):
			$this->engineer_stars = (int)floor($this->engineer / 145000);
		endif;
		if(isset($this->support)):
			$this->support_stars = (int)floor($this->support / 170000);
		endif;
		if(isset($this->recon)):
			$this->recon_stars = (int)floor($this->recon / 195000);
		endif;
		
		/**
		 * Scores per vehicle group
		 *
		 * Attack Helicopters = 60000
		 * Scout Hellicopters = 48000
		 * Jets = 35000
		 * Tanks = 100000               //83200
		 * Anti Air = 32000
		 * IFV = 90000
		 */
		if(isset($this->vehicleaa)):
			$this->aa_stars = (int)floor($this->vehicleaa / 32000);
		endif;
		if(isset($this->vehicleah)):
			$this->ah_stars = (int)floor($this->vehicleah / 60000);
		endif;
		if(isset($this->vehicleifv)):
			$this->ifv_stars = (int)floor($this->vehicleifv / 90000);
		endif;
		if(isset($this->vehiclejet)):
			$this->jet_stars = (int)floor($this->vehiclejet / 35000);
		endif;
		if(isset($this->vehiclembt)):
			$this->mbt_stars = (int)floor($this->vehiclembt / 100000);
		endif;
		if(isset($this->vehiclesh)):
			$this->sh_stars = (int)floor($this->vehiclesh / 48000);
		endif;
	}
}

/**
 * hacked classes for assignments
 */
class _xpma09 {
	public function init() {
		if( isset($this->count) && is_callable(array('_data', '_niceTime')) ):	// since parent has the same classname, but no values
			// only required for 3rd (so 2) objective
			$this->criteria[2]->nice_time_needed = _data::_niceTime( (int)$this->criteria[2]->needed );
			$this->criteria[2]->nice_time_current = _data::_niceTime( (int)$this->criteria[2]->curr );
			$this->criteria[2]->nice_time_left = _data::_niceTime( ((int)$this->criteria[2]->needed - (int)$this->criteria[2]->curr) );
		endif;
	}
}
class _xpma10 {
	public function init() {
		if( isset($this->count) && is_callable(array('_data', '_niceTime')) ):	// since parent has the same classname, but no values
			// required for 4th (so 3) objective
			$this->criteria[3]->nice_time_needed = _data::_niceTime( (int)$this->criteria[3]->needed );
			$this->criteria[3]->nice_time_current = _data::_niceTime( (int)$this->criteria[3]->curr );
			$this->criteria[3]->nice_time_left = _data::_niceTime( ((int)$this->criteria[3]->needed - (int)$this->criteria[3]->curr) );
			
			// required for 5th (so 4) objective
			$this->criteria[4]->nice_time_needed = _data::_niceTime( (int)$this->criteria[4]->needed );
			$this->criteria[4]->nice_time_current = _data::_niceTime( (int)$this->criteria[4]->curr );
			$this->criteria[4]->nice_time_left = _data::_niceTime( ((int)$this->criteria[4]->needed - (int)$this->criteria[4]->curr) );

		endif;
	}
}

/**
 * "special" classes
 * these are used to extend a general class (eg. all weapons)
 */
class __weapon {
	public function init() {
		if( isset($this->shots) && isset($this->hits) && $this->shots > 0 && $this->hits > 0 ):
			$this->accuracy = (float)number_format( ((100 / $this->shots ) * $this->hits), 3 );
		endif;
	}
}
class __team {
	public function init() {
		if( isset($this->shots) && isset($this->hits) && $this->shots > 0 && $this->hits > 0 ):
			$this->accuracy = (float)number_format( ((100 / $this->shots ) * $this->hits), 3 );
		endif;
		if( isset($this->kills) && isset($this->headshots) && $this->kills > 0 && $this->headshots > 0 ):
			$this->headshot_perc = (float)number_format( ((100 / $this->kills ) * $this->headshots), 3 );
		endif;
	}
}
class __ranking {
	public function init() {
		$this->rank = $this->r;
		$this->combined = $this->c;
		$this->value = is_float($this->v) ? (float)number_format($this->v, 3) : $this->v;
		$this->top_perc = (float)number_format( ( (100 / $this->c) * $this->r ), 3 );
	}
}

class __gamemode {
	public function init() {
		if( isset($this->wins) && isset($this->losses) && $this->wins > 0 && $this->losses > 0 ):
			$this->wlr = (float)number_format( ($this->wins / $this->losses), 3 );
		endif;
	}
}

class __kit {
	public function init() {
		if( isset($this->time) && isset($this->score) ):
			$this->spm = (float)number_format( ($this->score / ( $this->time / 60 )), 3 );
		endif;
	}
}

class __player {
}

?>