<?php

///////////////////
/////////////////// file: index.php
/////////////////// owner: DESAPHY Jeremy
/////////////////// creation date: 11/26/18
/////////////////// purpose: All website trafic goes through this page. Redirect to requested page
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 'stdout');
ini_set('memory_limit','500M');
$DEBUG_TG = false;
define("BIORELS", "1");
/// Define global parameters
require_once("require/php/core/r_def_glob.php");

try {
	/// Load configuration file
	require_once("require/php/core/r_load_conf.php");


	/// Load functions
	require_once("require/php/core/r_functions.php");

	/// Load template functions
	require_once("require/php/core/r_auth.php");

	/// Secure & Validate user POST/GET parameters
	require_once("require/php/core/r_secure_params.php");

	/// Connect to database
	require_once("require/php/core/r_db_connect.php");

	
	/// postgresql queries
	if ($USER_INPUT['PAGE']['NAME']!='API_HOME')	require_once("require/php/core/r_db_queries.php");


	/// Load template functions
	require_once("require/php/core/r_tpl_load.php");


	


	if (is_file('private/require/php/private_db_queries.php')) {
		require_once('private/require/php/private_db_queries.php');
	}
	/////////////////// Error handling
} catch (Exception $e) {

	$STR= file_get_contents("index_error.html");
	echo str_replace('${TXT}',$e->getMessage(),$STR);
	if (($e->getCode() == ERR_TGT_SYS || $e->getCode() == ERR_TGT_ACC) && isset($GLB_CONFIG['GLOBAL']['EMAIL'])) {
		$str = $e->getMessage() . "\n\n";
		$str .= var_export($GLB_CONFIG, true) . "\n\n";
		$str .= var_export($USER_INPUT, true) . "\n\n";
		$str .= var_export($DEBUG, true) . "\n\n";
		//$str.=phpinfo()."\n";
		$tab = explode("|", $GLB_CONFIG['GLOBAL']['EMAIL']);
		foreach ($tab as $EM) mail($EM, "Biorels issue", $str . "\n");
	}
	exit;
}

try {
	/// Define page
	require_once("require/php/core/r_def_page.php");


	if (isset($USER_INPUT['STATUS']) && $USER_INPUT['STATUS'][0] == 'ISSUE') {
		$USER_INPUT['PORTAL']['NAME'] = '';
		$USER_INPUT['PAGE']['NAME'] = 'WELCOME';
	}

	/// Execute page
	require_once("require/php/core/r_exec_page.php");
	printHTML();

	/////////////////// Error handling

} catch (Exception $e) {

	echo file_get_contents("index_error.html");
	echo $e->getMessage();
	if (($e->getCode() == ERR_TGT_SYS || $e->getCode() == ERR_TGT_ACC) && isset($GLB_CONFIG['GLOBAL']['EMAIL'])) {
		$str = $e->getMessage() . "\n\n";
		$str .= var_export($GLB_CONFIG, true) . "\n\n";
		$str .= var_export($USER_INPUT, true) . "\n\n";
		$str .= var_export($DEBUG, true) . "\n\n";
		$str .= phpinfo() . "\n";
		$tab = explode("|", $GLB_CONFIG['GLOBAL']['EMAIL']);
		foreach ($tab as $EM) mail($EM, "Biorels issue", $str . "\n");
	}
}
