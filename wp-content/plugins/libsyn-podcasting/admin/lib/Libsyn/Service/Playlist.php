<?php
namespace Libsyn\Service;
/*
	This class is used to import 3rd party podcast feeds into the libsyn network.
	For other 3rd party integrations please see Integration class.
*/
class Playlist extends \Libsyn\Service {

    /**
     * Handles getting a feed from url.
     * 
     * @param <type> $api
     * 
     * @return <type>
     */
	public function setFeedRedirect(\Libsyn\Service $api) {
		
		
	}

    /**
     * Cleans a string
     * 
     * @param <string> $string 
     * 
     * @return <string>
     */
	function clean ($string) {
		return preg_replace('/[^\da-z ]/i', '', $string);
	}

    /**
     * Checks the enclosure type
     * 
     * @param <string> $enclosureType 
     * 
     * @return <bool>
     */
	function check_enclosure($enclosureType) {
		if(strpos($enclosureType, 'video') !== false) return false;
			else return true;
	}

    /**
     * Converts seconds to duration
     * 
     * @param <int> $seconds 
     * 
     * @return <string>
     */
	function seconds_to_duration($seconds) {
		// extract hours
		$hours = floor($seconds / (60 * 60));

		// extract minutes
		$divisor_for_minutes = $seconds % (60 * 60);
		$minutes = floor($divisor_for_minutes / 60);

		// extract the remaining seconds
		$divisor_for_seconds = $divisor_for_minutes % 60;
		$seconds = ceil($divisor_for_seconds);

		// return the final array
		$obj = array(
			'h' => (int) $hours,
			'm' => (int) $minutes,
			's' => (int) $seconds,
		);
		
		foreach($obj as $key=>$val)if($val===0)unset($obj[$key]);
		if(isset($obj['s'])&&$obj['s']<=9) $obj['s'] = '0'.$obj['s'];
		$return = implode(':', $obj);
		if(strpos($return, ':')===false)return ':'.$return; else return $return;
	}
}

?>