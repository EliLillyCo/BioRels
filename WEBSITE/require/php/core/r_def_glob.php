<?php

if (!defined("BIORELS")) header("Location:/");/// BIORELS defined in index.php. Not existing? Go to index.php


///////////////////
/////////////////// file: r_def_glob.php
/////////////////// owner: DESAPHY Jeremy
/////////////////// creation date: 11/26/18
/////////////////// purpose: Define all global variables for the website

////// All of the website content will be stored here
////// The content works as blocks. Each block has a name being the key in HTML variable
////// The content of each block is stored as value
$HTML=array('JAVASCRIPT'=>"",
	    'ERROR'=>'<div id="error">',
	    'ADMIN'=>''
	   );


///// HTML_ORDER defines which block to show first when printing the page:
$HTML_ORDER=array( 9999=>'JAVASCRIPT',
		  10000=>'ERROR',
		  10001=>'ADMIN');

/// This is the number of blocks already loaded. This is used to define the order by default
/// Starts at 1 because headers is 0
$HTML_BLOCKS=1;

/// All error types:
define("ERR_TGT_USR",101);/// User error
define("ERR_TGT_SYS",102);/// Error to run the website properly
define("ERR_TGT_ACC",103);/// Unauthorized access

/////
///// All of the user information will be stored here:
/////
$USER = array('id' =>'XXXXXX',
	      'last_name' =>'John',
	      'first_name'=>'Doe',
	      'DB_ID'	  =>'',
		  'ADMIN'=>false,
	      'Access'	  =>array(0=>1,1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0,8=>0,9=>0,10=>0));
		  
/////
///// Timing functions
/////
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
$TIMING=array('GLOBAL'=>array(microtime_float()));

////
//// DEBUG
////
$DEBUG=array();
$DEBUG_TG=false;
////
//// Database connection
////
$DB_CONN=NULL;
$DB_XRAY=NULL;

/////
///// User parameters:
/////
$USER_INPUT=array();

/////
///// Configuration file
/////
$GLB_CONFIG=array();


/////
///// TARGET info
/////
$TARGET=array();


$LATEST_MODULE_DATA=array();

/////
///// Session information
/////
@session_start();



$COMMON_WORDS=array("word","time","number","way","people",
"water","day","part","sound","work",
"place","year","back","thing","name",
"sentence","man","line","boy","farm",
"end","men","land","home","hand",
"picture","air","animal","house","page",
"letter","point","mother","answer","America",
"world","food","country","plant","school",
"father","tree","city","earth","eye",
"head","story","example","life","paper",
"group","children","side","feet","car",
"mile","night","sea","river","state",
"book","idea","face","Indian","girl",
"mountain","list","song","family",
"he","a","one","all","an",
"each","other","many","some","two",
"more","long","new","little","most",
"good","great","right","mean","old",
"any","same","three","small","another",
"large","big","even","such","different",
"kind","still","high","every","own",
"light","left","few","next","hard",
"both","important","white","four","second",
"enough","above","young",
"not","when","there","how","up",
"out","then","so","no","first",
"now","only","very","just","where",
"much","before","too","also","around",
"well","here","why","again","off",
"away","near","below","last","never",
"always","together","often","once","later",
"far","really","almost","sometimes","soon",
"of","to","in","for","on",
"with","at","from","by","about",
"into","down","over","after","through",
"between","under","along","until","without",
"you","that","it","he","his",
"they","I","this","what","we",
"your","which","she","their","them",
"these","her","him","my","who",
"its","me","our","us","something",
"those",
"and","as","or","but",
"if","than","because","while",
"it’s","don’t");


?>
