<?php
// DO NOT touch these functions!
function host ($protocol=null) {
	$host =(!empty($_SERVER['HTTP_HOST'])) ?  $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
	if ( empty($protocol) ) {
		$protocol = ( !empty($_SERVER['HTTPS'] ) && ($_SERVER['HTTPS'] != 'off')) ? 'https' : 'http';
	}
	return $protocol.'://'.$host;
}


function urlhost ($protocol=null) {
	return host($protocol).$_SERVER['SCRIPT_NAME'];
}

?>