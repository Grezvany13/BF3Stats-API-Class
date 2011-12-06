<?php

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
	
	private static $time_correction = 0;
	private static $api_ident;
	private static $api_key;
	

	public function __construct() {}
	
	public static function setTimeCorrection( $int ) {
		self::$time_correction = (int)$int;
	}
	
	public static function setApiIdent( $ident ) {
		self::$api_ident = $ident;
	}
	
	public static function setApiKey( $key ) {
		self::$api_key = $key;
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
					self::$options[ $option ] = true;
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
					$data = array_merge( $data, self::$data['list']);
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
		self::call();
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
		curl_setopt( self::$curl, CURLOPT_HTTPHEADER, array('Expect:') );
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
}

/**
 * Take however many objects and allow accessing them as a single object
 *
 * First In First Out access to properties and methods
 *
 * @author joseph
 */
class MergeObject {
    private $objectsArray=array();
    public function __construct() {
        $objects = func_get_args();
        foreach($objects as $o){
            if(!is_object($o)){
                throw new Exception('Tried to add a non object to the MergeObject::objects array');
            }
            $class=get_class($o);
            if(isset($this->objectsArray[$class]) && $class != 'stdClass'){
                throw new Exception ('object of class ' . $class . 'already set in the MergeObject::objects array');
            }
            $this->objectsArray[$class]=$o;
        }
    }

    public function __get($attrib_name) {
        foreach($this->objectsArray as $o){
            if(isset($o->$attrib_name)){
                return $o->$attrib_name;
            }else if(method_exists($o, '__get')){
                try{
                    $return = $o->$attrib_name;
                    return $return;
                }catch(Exception $e){
                    // fail silently
                }
            }
        }
        throw new Exception('Tried to access $attrib_name ' . $attrib_name . ' but not found in any Merged Objects');
    }

    public function toArray(){
        foreach($this->objectsArray as $o){
            if(method_exists($o, 'toArray')){
                $ars[]=$o->toArray();
            }else{
                $ars[]=(array)$o;
            }
        }
        //flip the order of the $ars array so it respects our first in first out access
        $ars = array_reverse($ars);
        return call_user_func_array('array_merge', $ars);
    }

    public function __call($name, $arguments){
        foreach($this->objectsArray as $o){
            if(method_exists($o, $name)){
                return call_user_func_array(array($o,$name), $arguments);
            }
        }
        throw new Exception("Tried to access method $name but not found in any Merged Objects");
    }
}

?>