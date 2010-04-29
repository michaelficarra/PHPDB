<?php

extract($CONF['db']);

$db_handle = mysql_connect($host,$user,$pass);
if($db_handle===false) {
	echo 'ERROR: Could not connect to mysql database at "'.$host.'".<br />';
	if($CONF['debug']) { die( pp($CONF['db']) ); }
}

mysql_select_db($db) or die('ERROR: Could not use database "'.$db.'"');

# core function that does all of the formatting, escaping, querying, and error handling
function db_raw_query(){
	$args = func_get_args();
	if(count($args) < 1) { return false; }
	$query = array_shift($args);
	if(func_num_args()==2 && is_array(func_get_arg(1))) { $args=func_get_arg(1); }
	if(count($args) > 0) {	
		$args = array_map('mysql_real_escape_string', $args);
		$query = vsprintf($query, $args);
	}
	global $CONF;
	if($CONF['debug']) { error_log('SQL: '.preg_replace('/\\s+/',' ',$query)); }
	$result = mysql_query($query);
	if($CONF['debug'] && mysql_errno()>0) { error_log('SQL Error '.mysql_errno().': '.mysql_error()); }
	return $result;
}

# pass-through functions for now
function db_insert(){ $args = func_get_args(); return call_user_func_array('db_raw_query',$args); }
function db_update(){ $args = func_get_args(); return call_user_func_array('db_raw_query',$args); }
function db_delete(){ $args = func_get_args(); return call_user_func_array('db_raw_query',$args); }

# fetches all rows of the result, returning them as a numerically and associatively indexed array inside a numerically indexed array of rows
function db_fetch_rows(){
	$args = func_get_args();
	$result = call_user_func_array('db_raw_query',$args);
	if(!$result) return array();
	$ret = array();
	while($row = mysql_fetch_array($result)) {
		$ret[] = $row;
	}
	return $ret;
}

# fetches the first row of the result as a numerically and associatively indexed array
function db_fetch_row(){
	$args = func_get_args();
	$result = call_user_func_array('db_raw_query',$args);
	if(!$result || !mysql_num_rows($result)) return null;
	return mysql_fetch_array($result);
}

# fetches the value of the first field in the first row of the result
function db_fetch_field(){ $args = func_get_args(); return call_user_func_array('db_fetch_value',$args); }
function db_fetch_value(){
	$args = func_get_args();
	$result = call_user_func_array('db_raw_query',$args);
	if(!$result || !mysql_num_rows($result)) return null;
	return current(mysql_fetch_row($result));
}

# fetches a single column (the first one) as a numerically indexed array of values
function db_fetch_column(){ $args = func_get_args(); return call_user_func_array('db_fetch_values',$args); }
function db_fetch_values(){
	$args = func_get_args();
	$result = call_user_func_array('db_raw_query',$args);
	if(!$result) return array();
	$ret = array();
	while($row = mysql_fetch_array($result)) {
		$ret[] = $row[0];
	}
	return $ret;
}

# starts a transaction
function db_transaction_begin($autocommit=false){
	//db_raw_query('SET autocommit=%d',$autocommit);
	return ($autocommit ?  db_raw_query('BEGIN') : db_raw_query('START TRANSACTION'));
}
function db_transaction_start(){ return db_transaction_begin(); }

# commits a transaction
function db_transaction_commit(){ return db_raw_query('COMMIT'); }
function db_transaction_finish(){ return db_transaction_commit(); }

# rolls back a transaction
function db_transaction_rollback(){ return db_raw_query('ROLLBACK'); }
function db_transaction_stop(){ return db_transaction_rollback(); }

?>
