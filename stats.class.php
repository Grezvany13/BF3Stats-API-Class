<?php

/**
 * Create new objects when needed
 */
function __autoload( $class ) {
	if( substr($class, 0, 1) == '_' ):
		eval('class '.$class.' {}');
	endif;
}

/**
 * takes the response from the API and creates one massive object
 * when the class already exists it can use the predefined methods (see class _stats)
 */
function data2object( $array, $class = 'data' ) {
	$class = '_'.strtolower($class);
	$object = new $class;
	if( !empty($array) && is_array($array) ):
		foreach($array as $key => $value):
			if( is_array($value) ):
				$object->{$key} = data2object( $value, $key );
			else:
				$object->{$key} = $value;
			endif;
		endforeach;
	endif;
	return $object;
}

/**
 * PREDEFINED OBJECTS
 */

/**
 * _stats
 */
class _stats {
	public function getScorePerMinute() {
		if( isset($this->global->time) && isset($this->scores->score) ):
			$num = 0;
			if( $this->scores->score && $this->global->time ) $num = $this->scores->score / ( $this->global->time / 60 );
			return number_format( $num );
		endif;
		return false;
	}
}



?>