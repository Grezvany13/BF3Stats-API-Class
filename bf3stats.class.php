<?php

// is required ;)
require_once('stats.class.php');

class BF3StatsAPI {

	private static $curl;
	private static $postUri;
	private static $postfields = array();
	
	private static $data;
	
	private static $response;
	private static $status;
	
	private static $errno;
	private static $error;
	
	private static $baseURI = 'http://api.bf3stats.com/%s/%s/'; // sprintf( self::$baseURI, 'platform', 'function' )
	
	private static $_platforms = array(
		'pc',
		'ps3',
		'360',
		//'global'
	);
	
	private static $_functions = array(
		'playerlist',
		'player',
		'onlinestats',
		'playerupdate',	// *(for registered apps)
		'playerlookup',	// *(for registered apps)
		'setupkey',		// *(for registered apps)
		'getkey'		// *(for registered apps)
	);
	
	private static $_options = array(
		'clear',				// sets all options to false
		'index',				// returns only index data, all other options are ignored
		'all',					// is the default option with nextranks:true and imgInfo:true
		'noinfo',				// sets all *Info options to false
		'nounlocks',			// sets all *Unlocks options to false
	
		'scores',				// default: true
		'global',				// default: true
		'nextranks',			// default: false
		'rank',					// default: true
		'imgInfo',				// default: false
		'urls',					// default: false
		'keys',					// default: false
		'raw',					// default: false
		'nozero',				// default: false
		'coop',					// default: true
		'coopInfo',				// default: true
		'coopMissions',			// default: true
		'gamemodes',			// default: true
		'gamemodesInfo',		// default: true
		'weapons',				// default: true
		'weaponsName',			// default: true
		'weaponsInfo',			// default: true
		'weaponsOnlyUsed',		// default: false
		'weaponsUnlocks',		// default: true
		'weaponsStars',			// default: true
		'equipment',			// default: true
		'equipmentName',		// default: true
		'equipmentInfo',		// default: true
		'equipmentOnlyUsed',	// default: false
		'specializations',		// default: true
		'specializationsName',	// default: true
		'specializationsInfo',	// default: true
		'teams',				// default: true
		'kits',					// default: true
		'kitsName',				// default: true
		'kitsInfo',				// default: true
		'kitsStars',			// default: true
		'kitsUnlocks',			// default: true
		'vehicles',				// default: true
		'vehiclesName',			// default: true
		'vehiclesInfo',			// default: true
		'vehiclesOnlyUsed',		// default: false
		'vehCats',				// default: true
		'vehCatsStars',			// default: true
		'vehCatsUnlocks',		// default: true
		'vehCatsInfo',			// default: true
		'awards',				// default: true
		'awardsName',			// default: true
		'awardsInfo',			// default: true
		'awardsAwarded'			// default: false
	);
	
	private static $players = array();
	private static $platform = '';
	private static $options = array();
	
	private static $_cache = array();
	private static $cache_time = 3600; // 1 hour
	
	private static $time_correction = 0;
	private static $api_ident;
	private static $api_key;
	
	private static $settings = array(
		'cache' => 'files',		// none (false), files, [database, auto (true)] // database and auto don't work yet
		'time_correction' => 0,
		'date_format' => '%c',
		'time_format' => 'battlelog',
		'img_path' => 'bf3',
		'cache_path' => '_cache'
	);
	private static $return_object = true;
	

	public function __construct() {
		// ugly hack to prevent timezone errors
		date_default_timezone_set(date_default_timezone_get());
	}
	
	public static function setApiIdent( $ident ) {
		self::$api_ident = $ident;
	}
	
	public static function setApiKey( $key ) {
		self::$api_key = $key;
	}

	// OPTIONS
	public static function setTimeCorrection( $int ) {
		self::$settings['time_correction'] = (int)$int;
	}
	public static function setCache( $string ) {
		if( in_array($string, array(true, false, 'none', 'files', 'database', 'auto')) ):
			if($string === true) $string = 'auto';
			if($string === false) $string = 'files'; // should become auto
			self::$settings['cache'] = $string;
		endif;
	}
	public static function setDateFormat( $format ) {
		if( strftime($format) !== false ):
			self::$settings['date_format'] = $format;
		endif;
	}
	public static function setTimeFormat( $format ) {
		if($format == 'battlelog'):
			self::$settings['time_format'] = 'H M';
		endif;
	}
	public static function setImagePath( $path ) {
		self::$settings['img_path'] = $path;
	}
	public static function setCachePath( $path ) {
		self::$settings['cache_path'] = $path;
	}
	
	/**
	 * setPlayer
	 *
	 * Set a single player to retrieve data from
	 * 
	 * @static
	 * @access	Public
	 * @param	string	$player_name	Name of the player (eg. Grezvany13)
	 * @param	string	$platform		Valid platform (pc, ps3, 360)
	 */
	public static function setPlayer( $player_name, $platform ) {
		if( in_array( $platform, self::$_platforms ) ):
			self::$players[ $platform ][] = $player_name;
		else:
			self::error('setPlayer', 0, 'Incorrect platform ['.$platform.']');
		endif;
	}

	/**
	 * setPlayers
	 *
	 * Set multiple players to retrieve data from
	 * You can either give the platform for each player (eg. array('Grezvany13' => 'pc') )
	 *     or use a single platform for all players (second parameter)
	 * 
	 * @static
	 * @access	Public
	 * @param	array	$players		Array which contains all the players.
	 * @param	string	$platform		Valid platform (pc, ps3, 360)
	 */
	public static function setPlayers( $players, $platform = null ) {
		foreach( $players as $player_name => $player_platform ):
			if( in_array( $player_platform, self::$_platforms ) ):
				self::$players[ $player_platform ][] = $player_name;
			elseif( in_array( $platform, self::$_platforms ) ):
				self::$players[ $platform ][] = $player_platform;
			else:
				self::error('setPlayers', 0, 'Incorrect platform for player ['.$player_name.']');
			endif;
		endforeach;
	}
	
	/**
	 * setOptions
	 *
	 * Set the options to send to the API
	 *
	 * @static
	 * @access	Public
	 * @param	string|array	List of options. (eg. array('clear' => true, 'global' => true) )
	 */
	public static function setOptions( $options ) {
		if( is_array($options) ):
			foreach( $options as $option => $value ):
				if( is_string($option) && (is_bool($value) || is_int($value)) ):
					self::$options[ $option ] = (bool)$value;
				else:
					self::$options[ $value ] = true;
				endif;
			endforeach;
		elseif( is_string($options) ):
			self::error('setOptions', 0, 'Sorry, this is not yet available');
		endif;
	}
	
	/**
	 *
	 */
	public static function setPlatform( $platform ) {
		if( in_array( $platform, self::$_platforms ) ):
			self::$platform = $platform;
		else:
			self::error('setPlatform', 0, 'Sorry, platform ['.$platform.'] is not valid');
		endif;
	}
	
	/**
	 *
	 */
	public static function playerlist( $players, $platform = null, $options = array() ) {
		self::setPlayers( $players, $platform );
		self::setPlatform( $platform );
		if(isset($options) && !empty($options)):
			self::setOptions( $options );
		endif;
		
		$data = array();
		foreach( self::$players as $platform => $players ):
			self::$postUri = sprintf( self::$baseURI, self::$platform, 'playerlist' ); //'http://api.bf3stats.com/pc/playerlist/';
			self::$postfields = array(
				'players' => json_encode( $players ),
				'opt' => json_encode( self::$options )
			);
			if(self::request()):
				if( self::$data['status'] === 'ok' ):
					if(self::$return_object && function_exists('data2object')):
						$data[$platform] = data2object(self::$data['list'], 'data', self::$settings);
					else:
						$data[$platform] = self::$data['list'];
					endif;
				elseif( self::$data['status'] === 'error' ):
					self::error( 'playerlist', self::$data['error'], print_r(self::$data['failed'], true) );
				endif;
			endif;
		endforeach;
		return $data;		
	}
	
	/**
	 *
	 */
	public static function player( $player, $platform, $options = array() ) {
		//self::setPlayer( $player, $platform );
		self::setPlatform( $platform );
		if(isset($options) && !empty($options)):
			self::setOptions( $options );
		endif;
		
		$data = array();
		
		self::$postUri = sprintf( self::$baseURI, self::$platform, 'player' ); //'http://api.bf3stats.com/pc/player/';
		self::$postfields = array(
			'player' => $player,
			'opt' => json_encode( self::$options )
		);
		if(self::request()):
			if( self::$data['status'] === 'error' ):
				self::error( 'player', self::$data['error'], 'Name: '.$player );
			else:
				$data = array_merge( $data, self::$data);
			endif;
		endif;
		if(self::$return_object && function_exists('data2object')):
			return data2object($data, 'data', self::$settings);
		endif;
		return $data;
	}
	
	/**
	 *
	 */
	public static function onlinestats( $platform = null ) {
		if($platform != null && !in_array($platform, self::$_platforms)):
			self::error('onlinestats', 0, 'Incorrect platform ['.$platform.']');
			return;
		endif;
		
		self::$postUri = sprintf( self::$baseURI, 'global', 'onlinestats' ); //'http://api.bf3stats.com/global/onlinestats/';
		self::$postfields = array();
		
		if(self::request()):
			if( self::$data['status'] === 'ok' ):
				if($platform):
					return self::$data[$platform];
				endif;
				return self::$data;
			else:
				self::error('onlinestats', self::$data['status'], self::$data['error']);
			endif;
		endif;
		return;
	}

	/**
	 *
	 */
	public static function playerupdate( $player, $platform ) {		// *(for registered apps)
		self::setPlatform( $platform );
		
		$postdata = array(
			'time' => (time() + self::$time_correction),
			'ident' => self::$api_ident,
			'player' => $player
		);
		
		$data = array();
		self::$postUri = sprintf( self::$baseURI, self::$platform, 'playerupdate' ); //'http://api.bf3stats.com/pc/playerupdate/';
		self::$postfields = self::getSigned($postdata);
	
		if(self::request()):
			return self::$data; // need more checks for status updates
		endif;
		return;
	}
	
	/**
	 *
	 */
	public static function playerlookup() {	// *(for registered apps)
		self::setPlatform( $platform );
		
		$postdata = array(
			'time' => (time() + self::$time_correction),
			'ident' => self::$api_ident,
			'player' => $player
		);
		
		$data = array();
		self::$postUri = sprintf( self::$baseURI, self::$platform, 'playerlookup' ); //'http://api.bf3stats.com/pc/playerlookup/';
		self::$postfields = self::getSigned($postdata);
	
		if(self::request()):
			return self::$data; // need more checks for status updates
		endif;
		return;
	}
	public static function setupkey() {}		// *(for registered apps)
	public static function getkey() {}			// *(for registered apps)
	
	
	private static function getSigned( $data ) {
		if( is_array($data) ):
			$data = json_encode($data);
		endif;
		$_data = base64_encode( $data );
		$_sig = base64_encode( hash_hmac( 'sha256', $data, self::$api_key ) );
		
		return array(
			'data' => $_data,
			'sig' => $_sig
		);
	}
	
	private static function request() {
		if( self::$settings['cache'] != 'none' && self::_hasCache( self::$postUri, self::$postfields ) ):
			self::$status = 200;
			self::$response = self::_getCache( self::$postUri, self::$postfields );
		else:
			self::call();
			if( self::$settings['cache'] != 'none' ):
				self::_setCache( self::$postUri, self::$postfields, self::$response );
			endif;
		endif;
		if( self::$status == 200 ) {
			self::$data = json_decode( self::$response, true );
			return true;
		} else {
			self::error( 'HTTP CODE', self::$status, '' );
			return false;
		}
	}
	
	private static function call() {
		self::$curl = curl_init( self::$postUri );
		
		curl_setopt( self::$curl, CURLOPT_HEADER, false );
		curl_setopt( self::$curl, CURLOPT_POST, true );
		curl_setopt( self::$curl, CURLOPT_USERAGENT, 'BF3StatsAPI/0.1' );
		curl_setopt( self::$curl, CURLOPT_HTTPHEADER, array(
														'Expect:'														
													) );
		curl_setopt( self::$curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( self::$curl, CURLOPT_POSTFIELDS, self::$postfields );
		curl_setopt( self::$curl, CURLOPT_HEADER, false );
		
		self::$response = curl_exec( self::$curl );
		self::$status = curl_getinfo( self::$curl, CURLINFO_HTTP_CODE );
		
		self::$errno = curl_errno( self::$curl );
		self::$error = curl_error( self::$curl );
		if( self::$errno ):
			self::error( 'cURL', self::$errno, self::$error );
		endif;
		
		curl_close( self::$curl );
	}
	
	private static function error( $type, $errno, $error ) {
		print '<div style="color: #C00; border:2px solid; font-weight:bold;">';
		print '<h3>'.$type.'</h3>';
		print '<p>('.$errno.') '.$error.'</p>';
		print '</div>';
	}
	
	/**
	 * FILE CACHE
	 */
	private static function _setCache( $uri, $fields, $response ) {
		if(self::$settings['cache'] == 'file'):
			$file = self::_cacheMakeFile( $uri, $fields );
			if( is_writable( self::$settings['cache_path'].DS ) ): // buggy???
				file_put_contents( self::$settings['cache_path'].DS.$file.'.json', $response );
			else:
				self::error( 'file caching', 0, 'Either ['.self::$settings['cache_path'].DS .'] is not writeable or "is_writeable" is bugged. Disable caching and send a bug report.' );
			endif;
		endif;
	}
	private static function _getCache( $uri, $fields ) {
		if(self::$settings['cache'] == 'file'):
			$file = self::_cacheMakeFile( $uri, $fields );
			if( file_exists( self::$settings['cache_path'].DS.DS.$file.'.json' ) ):
				return file_get_contents( self::$settings['cache_path'].DS.DS.$file.'.json' );
			endif;
		endif;
	}
	private static function _hasCache( $uri, $fields ) {
		if(self::$settings['cache'] == 'file'):
			$file = self::_cacheMakeFile( $uri, $fields );
			if( file_exists( self::$settings['cache_path'].DS.$file.'.json' ) ):
				$filetime = filectime( self::$settings['cache_path'].DS.$file.'.json' );
				if( $filetime != false && $filetime < (time() + self::$cache_time) ):
					return true;
				else:
					unlink( self::$settings['cache_path'].DS.$file.'.json' );
				endif;
			endif;
		endif;
		return false;
	}
	private static function _cacheMakeFile( $uri, $fields ) {
		return md5(json_encode($uri)).'_'.md5(json_encode($fields));
	}
}

?>