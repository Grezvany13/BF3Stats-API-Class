<?php

define( 'DS', DIRECTORY_SEPARATOR );
/**
 * Create new objects when needed
 */
function __autoload( $class ) {
	if( substr($class, 0, 1) == '_' ):
		if(
			substr($class, 1, 2) == 'ar'		// Assault Rifles
			||	substr($class, 1, 2) == 'ca'	// Carbines
			||	substr($class, 1, 2) == 'mg'	// Machine Guns
			||	substr($class, 1, 1) == 'p'		// Pistols
			||	substr($class, 1, 2) == 'sg'	// Shotguns
			||	substr($class, 1, 2) == 'sm'	// Sub Machine Guns
			||	substr($class, 1, 2) == 'sr'	// Sniper Rifle
			||	substr($class, 1, 2) == 'wa'	// Weapons Special (Knife)
			||	substr($class, 1, 3) == 'wLA'	// Anti Air
			||	substr($class, 1, 3) == 'wLA'	// Anti Tank
		):
			eval('class '.$class.' extends __wap {}');
		elseif( substr($class, 1, 2) == 'us' || substr($class, 1, 2) == 'ru' ):
			eval('class '.$class.' extends __team {}');
		else:
			eval('class '.$class.' {}');
		endif;
	endif;
}

/**
 * takes the response from the API and creates one massive object
 * when the class already exists it can use the predefined methods (see class _stats)
 */
function data2object( $array, $class = 'data', $settings = array() ) {
	$class = '_'.strtolower($class);
	$object = new $class;
	if(method_exists($object, 'setSettings')):
		$object->setSettings( $settings );
	endif;
	if( !empty($array) && is_array($array) ):
		foreach($array as $key => $value):
			// if key is numeric, it's an array (eg. nextranks)
			if( is_numeric($key) ):
				if(!is_array($object)) $object = array();
				if( is_array($value) ):
					$object[$key] = data2object( $value, $key );
				else:
					$object[$key] = $value;
				endif;
			else:
				if( is_array($value) ):
					$object->{$key} = data2object( $value, $key, $settings );
				else:
					$object->{$key} = $value;
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
			$time = time();
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
			$time = (float)$data * 1000;
		else:
			$time = 0;
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
		if( isset($this->global->time) && isset($this->scores->score) ):
			$num = 0;
			if( $this->scores->score && $this->global->time ) $num = $this->scores->score / ( $this->global->time / 60 );
			$this->global->spm = (float)number_format( $num, 3 );
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
		 * Assault = 220000;
		 * Engineer = 145000;
		 * Support = 177000; == 170000
		 * Recon = 195000;
		 *
		 * thanks to TheHiram
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
			
			
		}
	}
class __wap {
	public function init() {
		if( isset($this->shots) && isset($this->hits) && $this->shots>0 && $this->hits>0 ):
			$this->accuracy = (float)number_format( ((100 / $this->shots ) * $this->hits), 3 );
		endif;
	}
}
class __team {
	public function init() {
		if( isset($this->shots) && isset($this->hits) && $this->shots>0 && $this->hits>0 ):
			$this->accuracy = (float)number_format( ((100 / $this->shots ) * $this->hits), 3 );
		endif;
	}
}


?>