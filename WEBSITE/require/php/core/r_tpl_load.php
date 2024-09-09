<?php
///////////////////
/////////////////// file: r_tpl_load.php
/////////////////// owner: DESAPHY Jeremy
/////////////////// creation date: 1/14/19
/////////////////// purpose: Load and process modules
/////////////////// LOG:
if (!defined("BIORELS")) {
    header("Location:/");
} /// BIORELS defined in index.php. Not existing? Go to index.php


function replaceTag($block, $value, $str)
{
    return str_replace('${' . $block . '}', $value, $str);
}

function changeValue($html_name, $block, $value)
{
    global $HTML;
    if (!isset($HTML[$html_name])) {
        $HTML['ERROR'] .= '<div class="line">HTML NAME:' . $html_name . " ; BLOCK: " . $block . "  NOT FOUND</div>";
        return;
    }
    do {
        $prev = $HTML[$html_name];
        $HTML[$html_name] = replaceTag($block, $value, $HTML[$html_name]);
    } while ($prev != $HTML[$html_name]);
}

function removeBlock($html_name, $block)
{
    global $HTML;
    if (!isset($HTML[$html_name])) {
        $HTML['ERROR'] .= '<div class="line">HTML NAME:' . $html_name . " ; BLOCK: " . $block . "  NOT FOUND</div>";
        return;
    }
    do {
        $prev = $HTML[$html_name];
        $pos = strpos($HTML[$html_name], '$[' . $block . ']');
        $end_pos = strpos($HTML[$html_name], '$[/' . $block . ']', $pos);
        if ($pos !== false && $end_pos !== false) {
            $HTML[$html_name] = substr($HTML[$html_name], 0, $pos) . substr($HTML[$html_name], $end_pos + strlen($block) + 4);
        }
    } while ($prev != $HTML[$html_name]);
}

function preloadHTML($fname, $type = 'PAGE', $STORE_MODULE_DATA = false)
{

    global $DEBUG_TG;
    global $HTML;
    global $HTML_BLOCKS;
    global $HTML_RULES;
    global $HTML_ORDER;
    global $GLB_CONFIG;
    global $USER_INPUT;
    global $LATEST_MODULE_DATA;
    global $USER;
    $MAX_USER_ACCESS = 0;

    // echo getcwd();
    // echo "<br>";
    // print_r(scandir("/var/www/html/private"));
    // exit;


    $MODULE_DATA = array();

    if ($DEBUG_TG) {
        echo "LOADING " . $type . ": " . $fname . '<br/>';
    }
    if (!isset($GLB_CONFIG[$type][$fname])) {
        throw new Exception("Unable to find " . $fname . ' in pages', ERR_TGT_SYS);
    }
    $HTML_MOD_TAG = '';
    try {
        $CURRENT_MODULE = null;
        foreach ($GLB_CONFIG[$type][$fname] as $K_MOD => $TEST_MOD) {
            for ($I = 0; $I < strlen($TEST_MOD['LEVEL']); ++$I) {
                if (substr($TEST_MOD['LEVEL'], $I, 1) == 1 && $USER['Access'][$I] == 1) {
                    $CURRENT_MODULE = &$GLB_CONFIG[$type][$fname][$K_MOD];
                }
            }
        }

        if ($CURRENT_MODULE == null) {
            $CURRENT_MODULE = &$GLB_CONFIG['PAGE']['NO_ACCESS'][0];
        }

        $DIR = '/var/www/html/';


        if (isset($CURRENT_MODULE['IS_PRIVATE'])) {
            $DIR .= 'private/';
        }

        if ($DEBUG_TG) {
            print_r($CURRENT_MODULE);
        }
        if (!isset($CURRENT_MODULE['HTML_TAG'])) {
            throw new Exception("Unable to find HTML_TAG for " . $fname, ERR_TGT_SYS);
        }
        if (!isset($CURRENT_MODULE['FNAME'])) {
            throw new Exception("Unable to find FNAME for " . $fname, ERR_TGT_SYS);
        }
        if (!isset($CURRENT_MODULE['LOC'])) {
            throw new Exception("Unable to find LOC for " . $fname, ERR_TGT_SYS);
        }
        $DIR .= 'module/' . $CURRENT_MODULE['LOC'];
        if (!is_dir($DIR)) {
            throw new Exception("preloadHTML - Unable to find directory " . getcwd() . '/' . $DIR . " for " . $fname, ERR_TGT_SYS);
        }
        if ($DEBUG_TG) {
            echo $DIR . "\n";
        }
        // if(isset($CURRENT_MODULE['WITH_EXPORT']) && $CURRENT_MODULE['WITH_EXPORT']=='true'){

        // 	if ($DEBUG_TG)echo "WITH EXPORT LOADING\n";
        // 	$TPATH=$DIR.'/'.$CURRENT_MODULE['FNAME'].'_export.php';
        // 	require_once($TPATH);
        // 	return;
        // 	}

        $HTML_MOD_TAG = $CURRENT_MODULE['HTML_TAG'];
        $FNAME = $CURRENT_MODULE['FNAME'];

        $HTML_PATH = $DIR . '/' . $FNAME . '.html';

        if (!is_file($HTML_PATH)) {
            throw new Exception("Unable to find file " . $HTML_PATH . " for " . $fname, ERR_TGT_SYS);
        }

        $HTML[$HTML_MOD_TAG] = file_get_contents($HTML_PATH);
        if ($HTML_MOD_TAG != "HEADER") {

            $HTML_ORDER[$HTML_BLOCKS] = $HTML_MOD_TAG;
            $HTML_BLOCKS++;
        } else {
            $HTML_ORDER[0] = $HTML_MOD_TAG;
        }

        if ($CURRENT_MODULE['WITH_DATA'] == 'true') {
            $PHP_PATH = $DIR . '/' . $FNAME . '_data.php';
            if (!is_file($PHP_PATH)) {
                throw new Exception("Unable to find file " . $PHP_PATH . " for " . $fname, ERR_TGT_SYS);
            }
            require($PHP_PATH);
        }
        $PHP_PATH = $DIR . '/' . $FNAME . '.php';
        if (!is_file($PHP_PATH)) {
            throw new Exception("Unable to find file " . $PHP_PATH . " for " . $fname, ERR_TGT_SYS);
        }
        require($PHP_PATH);

        if (isset($HTML_ORDER[$HTML_BLOCKS])) {
            throw new Exception($HTML_BLOCKS . ' already in use', ERR_TGT_SYS);
        }
        if ($STORE_MODULE_DATA) {
            $LATEST_MODULE_DATA = $MODULE_DATA;
        }
    } catch (Exception $e) {
        //print_r($e);
        $HTML[$HTML_MOD_TAG] = '<div class="alert alert-danger" role="alert">Error: ' . $e->getMessage() . '</div>';
        $HTML_ORDER[$HTML_BLOCKS] = $HTML_MOD_TAG;
        $HTML_BLOCKS++;
        if ($e->getCode() == ERR_TGT_USR) {
        }
    }

    return array($HTML_BLOCKS - 1, $HTML_MOD_TAG);
}


function loadHTMLHeader()
{
    global $GLB_CONFIG;
    return file_get_contents("module/global/header/" . (($GLB_CONFIG['GLOBAL']['PRD_STATUS'] == "F") ? "dev" : "prd") . '_link.html');
}

function loadHTMLAndRemove($fname, $W_HEADER = false, $TYPE = 'PAGE')
{
    global $HTML_ORDER;
    global $HTML;

    $INFO = preloadHTML($fname, $TYPE, true);
    //echo "###############33".$fname;


    //	foreach ($HTML as $K=>$V)echo $K.' '.strlen($V)."<br/>";
    cleanRules($INFO[1]);
    //echo " ".strlen($HTML[$INFO[1]]);
    //echo $STR;

    unset($HTML_ORDER[$INFO[0]]);

    $STR = '';
    if ($W_HEADER) {
        $STR .= loadHTMLHeader();
    }
    $STR .= $HTML[$INFO[1]];
    
    unset($HTML[$INFO[1]]);

    return $STR;
}


function preloadData($fname, $type = 'PAGE')
{
    //echo "LOADING DATA ONLY ".$type.": ".$fname.'<br/>';
    global $HTML;
    global $HTML_BLOCKS;
    global $HTML_RULES;
    global $USER;
    global $HTML_ORDER;
    global $GLB_CONFIG;
    global $USER_INPUT;
    $MODULE_DATA = array();
    if (!isset($GLB_CONFIG[$type][$fname])) {
        throw new Exception("Unable to find " . $fname . ' in pages', ERR_TGT_SYS);
    }
    $CURRENT_MODULE = null;
    foreach ($GLB_CONFIG[$type][$fname] as $K_MOD => $TEST_MOD) {
        for ($I = 0; $I < strlen($TEST_MOD['LEVEL']); ++$I) {
            if (substr($TEST_MOD['LEVEL'], $I, 1) == 1 && $USER['Access'][$I] == 1) {
                $CURRENT_MODULE = &$GLB_CONFIG[$type][$fname][$K_MOD];
            }
        }
    }
    if ($CURRENT_MODULE == null) {
        $CURRENT_MODULE = &$GLB_CONFIG['PAGE']['NO_ACCESS'][0];
    }

    $DIR = '';
    if (isset($CURRENT_MODULE['IS_PRIVATE'])) {
        $DIR = 'private/';
    }
    try {
        if (!isset($CURRENT_MODULE['HTML_TAG'])) {
            throw new Exception("Unable to find HTML_TAG for " . $fname, ERR_TGT_SYS);
        }
        if (!isset($CURRENT_MODULE['FNAME'])) {
            throw new Exception("Unable to find FNAME for " . $fname, ERR_TGT_SYS);
        }
        if (!isset($CURRENT_MODULE['LOC'])) {
            throw new Exception("Unable to find LOC for " . $fname, ERR_TGT_SYS);
        }

        $DIR .= 'module/' . $CURRENT_MODULE['LOC'];
        if (!is_dir($DIR)) {
            throw new Exception("Unable to find directory " . getcwd() . $DIR . " for " . $fname, ERR_TGT_SYS);
        }
        $HTML_MOD_TAG = $CURRENT_MODULE['HTML_TAG'];
        $FNAME = $CURRENT_MODULE['FNAME'];


        if ($CURRENT_MODULE['WITH_DATA'] == 'true') {
            $PHP_PATH = $DIR . '/' . $FNAME . '_data.php';
            if (!is_file($PHP_PATH)) {
                throw new Exception("Unable to find file " . $PHP_PATH . " for " . $fname, ERR_TGT_SYS);
            }
            require($PHP_PATH);
            return $MODULE_DATA;
        } else {
            $PHP_PATH = $DIR . '/' . $FNAME . '.php';
            if (!is_file($PHP_PATH)) {
                throw new Exception("Unable to find file " . $PHP_PATH . " for " . $fname, ERR_TGT_SYS);
            }
            require($PHP_PATH);
            return $MODULE_DATA;
        }
    } catch (Exception $e) {
        global $HTML_ORDER;
        $HTML_ORDER[1000] = 'MSG_ERR';
        global $HTML;
        $HTML['MSG_ERR'] = '
	<div class="alert alert-info" role="alert">
 An issue happened during the preparation of this webpage
</div>';
        print_r($e);
    }
}

/*
* @return array Containing all primary keys in the database.
*/
function getAllPrimaryKeys(): array
{
    global $GLB_VAR;
    $query = "
        SELECT kcu.table_schema, kcu.table_name, tco.constraint_name, 
            kcu.ordinal_position as position,
            kcu.column_name as key_column
        FROM information_schema.table_constraints tco
        JOIN information_schema.key_column_usage kcu
        ON kcu.constraint_name = tco.constraint_name
        AND kcu.constraint_schema = tco.constraint_schema
        AND kcu.constraint_name = tco.constraint_name
        AND kcu.table_schema='".$GLB_VAR['DB_SCHEMA']."'  
        WHERE tco.constraint_type = 'PRIMARY KEY'
        ORDER BY kcu.table_schema, kcu.table_name, position;
    ";
    $res = runQuery($query);

    // get key_column from  array
    $out = array_map(function ($x) {
        return $x["KEY_COLUMN"];
    }, $res);

    return $out;
}

/**
 * Modified version of array_walk_recursive that passes in the array to the callback
 * The callback can modify the array or value by specifying a reference for the parameter.
 *
 * @param array The input array.
 * @param callable $callback($value, $key, $array)
 */
function array_walk_recursive_array(array &$array, callable $callback)
{
    foreach ($array as $k => &$v) {
        if (is_array($v)) {
            array_walk_recursive_array($v, $callback);
        } else {
            $callback($v, $k, $array);
        }
    }
}

/*
* Queries db for the primary keys in all tables, then removes those keys from
* $MODULE_DATA.
* 
* @param array $MODULE_DATA Is an array containing SQL results and is
* typically populated by requiring *_data.php files.
* @return array Returns a copy of $MODULE_DATA after recursively removing all columns whose
* name matches a primary key in the database.
*/
function removePrimaryKeys(array $MODULE_DATA): array
{

    $primary_keys = array_change_key_case_recursive(getAllPrimaryKeys());
    print_r($primary_keys);
    array_walk_recursive_array(
        $MODULE_DATA,
        function ($value, $key, &$arr) use ($primary_keys) {
            if (in_array(strtolower($key), $primary_keys)) {
                unset($arr[$key]);
            }
        }
    );
    return $MODULE_DATA;
}


function cleanRules($BLOCK_ID)
{
    global $HTML; /// In case of errors, we need to be able to track them
    global $HTML_RULES;
    global $GLB_CONFIG;
    //print_r($HTML);
    $str = &$HTML[$BLOCK_ID];
    //echo "CLEAN RULE ".$BLOCK_ID.' '.strlen($str)."\n";
    $pos_ini = 0;
    $open = false;
    $HTML_MOD_TAGS = array(array('${', '}'), array('$[', ']'));
    global $HTML_BLOCKS;

    foreach ($HTML_MOD_TAGS as $HTML_MOD_TAG) {
        $pos_ini = 0;
        do {

            $pos = strpos($str, $HTML_MOD_TAG[0], $pos_ini);
            if ($pos === false) {
                break;
            }
            $pos2 = strpos($str, $HTML_MOD_TAG[1], $pos + 2);
            if ($pos2 === false) {
                throw new Exception("No } found as position " . $pos_ini . " for string:<br/>" . $str);
            }

            $NAME_TAG = substr($str, $pos + 2, $pos2 - $pos - 2);

            if ($GLB_CONFIG['GLOBAL']['PRD_STATUS'] == "F") {
                $HTML['ERROR'] .= '<br/>Issue in block ' . $BLOCK_ID . ' - ${' . $NAME_TAG . '} still on';
            }
            $pos_ini = $pos + 1;
            $str = substr($str, 0, $pos) . substr($str, $pos2 + 1);
            if ($pos_ini >= strlen($str)) {
                break;
            }
        } while ($pos <= strlen($str));
    }

    $str=str_replace('$|{','${',$str);
        $str=str_replace('$|[','$[',$str);


    //echo "CLEAN RULE ".$BLOCK_ID.' '.strlen($str)."\n";
    return $str;
}

function preloadPortalMenu($fname)
{
    //echo "LOADING PORTAL MENU: ".$fname.'<br/>';
    $type = 'PORTAL';
    global $HTML;
    global $HTML_BLOCKS;
    global $HTML_RULES;
    global $HTML_ORDER;
    global $USER;
    global $GLB_CONFIG;
    if (!isset($GLB_CONFIG[$type][$fname])) {
        throw new Exception("Unable to find " . $fname . ' in pages', ERR_TGT_SYS);
    }
    $CURRENT_MODULE = null;
    foreach ($GLB_CONFIG[$type][$fname] as $K_MOD => $TEST_MOD) {
        for ($I = 0; $I < strlen($TEST_MOD['LEVEL']); ++$I) {
            if (substr($TEST_MOD['LEVEL'], $I, 1) == 1 && $USER['Access'][$I] == 1) {
                $CURRENT_MODULE = &$GLB_CONFIG[$type][$fname][$K_MOD];
            }
        }
    }


    if ($CURRENT_MODULE == null) {
        $CURRENT_MODULE = &$GLB_CONFIG['PAGE']['NO_ACCESS'][0];
    }

    if (!isset($CURRENT_MODULE['HTML_TAG'])) {
        throw new Exception("Unable to find HTML_TAG for " . $fname, ERR_TGT_SYS);
    }
    if (!isset($CURRENT_MODULE['FNAME'])) {
        throw new Exception("Unable to find FNAME for " . $fname, ERR_TGT_SYS);
    }
    if (!isset($CURRENT_MODULE['LOC'])) {
        throw new Exception("Unable to find LOC for " . $fname, ERR_TGT_SYS);
    }
    $DIR = '';
    if (isset($CURRENT_MODULE['IS_PRIVATE'])) {
        $DIR = 'private/';
    }
    $DIR .= 'module/' . $CURRENT_MODULE['LOC'] . '_menu';
    // echo "IN\n".$DIR."\n";
    if (!is_dir($DIR)) {
        throw new Exception("Unable to find directory " . getcwd() . $DIR . " for " . $fname, ERR_TGT_SYS);
    }
    $HTML_MOD_TAG = $CURRENT_MODULE['HTML_TAG'] . '_menu';
    $FNAME = $CURRENT_MODULE['FNAME'];
    //echo "IN\n".$DIR."\n";
    $HTML_PATH = $DIR . '/' . $FNAME . '_menu.html';

    if (!is_file($HTML_PATH)) {
        throw new Exception("Unable to find file " . $HTML_PATH . " for " . $fname, ERR_TGT_SYS);
    }
    //echo "IN\n".$DIR."\n";
    $HTML[$HTML_MOD_TAG] = file_get_contents($HTML_PATH);
    if ($HTML_MOD_TAG != "HEADER") {
        $HTML_ORDER[$HTML_BLOCKS] = $HTML_MOD_TAG;
        $HTML_BLOCKS++;
    } else {
        $HTML_ORDER[0] = $HTML_MOD_TAG;
    }
    $PHP_PATH = $DIR . '/' . $FNAME . '_menu.php';


    if (!is_file($PHP_PATH)) {
        throw new Exception("Unable to find file " . $PHP_PATH . " for " . $fname, ERR_TGT_SYS);
    }
    require($PHP_PATH);

    if (isset($HTML_ORDER[$HTML_BLOCKS])) {
        throw new Exception($HTML_BLOCKS . ' already in use', ERR_TGT_SYS);
    }
}

function preloadTopicMenu($fname, $is_welcome)
{
    //echo "LOADING PORTAL MENU: ".$fname.'<br/>';
    $type = 'TOPIC';
    global $HTML;
    global $HTML_BLOCKS;
    global $HTML_RULES;
    global $HTML_ORDER;
    global $GLB_CONFIG;
    if (!isset($GLB_CONFIG[$type][$fname])) {
        throw new Exception("Unable to find " . $fname . ' in pages', ERR_TGT_SYS);
    }
    $CURRENT_MODULE = &$GLB_CONFIG[$type][$fname];


    if (!isset($CURRENT_MODULE['HTML_TAG'])) {
        throw new Exception("Unable to find HTML_TAG for " . $fname, ERR_TGT_SYS);
    }
    if (!isset($CURRENT_MODULE['FNAME'])) {
        throw new Exception("Unable to find FNAME for " . $fname, ERR_TGT_SYS);
    }
    if (!isset($CURRENT_MODULE['LOC'])) {
        throw new Exception("Unable to find LOC for " . $fname, ERR_TGT_SYS);
    }
    $DIR = '';
    if (isset($CURRENT_MODULE['IS_PRIVATE'])) {
        $DIR = 'private/';
    }
    $DIR = 'module/' . $CURRENT_MODULE['LOC'];
    if (!is_dir($DIR)) {
        throw new Exception("Unable to find directory " . $DIR . " for " . $fname, ERR_TGT_SYS);
    }
    $HTML_MOD_TAG = $CURRENT_MODULE['HTML_TAG'] . '_menu';
    $FNAME = $CURRENT_MODULE['FNAME'];

    $HTML_PATH = $DIR . '/' . $FNAME . '_menu.html';

    if (!is_file($HTML_PATH)) {
        throw new Exception("Unable to find file " . $HTML_PATH . " for " . $fname, ERR_TGT_SYS);
    }
    $HTML[$HTML_MOD_TAG] = file_get_contents($HTML_PATH);
    if ($HTML_MOD_TAG != "HEADER") {
        $HTML_ORDER[$HTML_BLOCKS] = $HTML_MOD_TAG;
        $HTML_BLOCKS++;
    } else {
        $HTML_ORDER[0] = $HTML_MOD_TAG;
    }
    $PHP_PATH = $DIR . '/' . $FNAME . '_menu.php';
    if (!is_file($PHP_PATH)) {
        throw new Exception("Unable to find file " . $PHP_PATH . " for " . $fname, ERR_TGT_SYS);
    }
    require($PHP_PATH);

    if (isset($HTML_ORDER[$HTML_BLOCKS])) {
        throw new Exception($HTML_BLOCKS . ' already in use', ERR_TGT_SYS);
    }
}


function printHTML()
{


    global $HTML;
    global $HTML_ORDER;
    global $HTML_BLOCKS;
    global $HTML_RULES;

    /// We sort the order based on the keys (not the values).
    ksort($HTML_ORDER);

    $STR_FINAL = '';
    $STR_MSG_ERR = '';


    foreach ($HTML_ORDER as $BLOCK) {
        if ($BLOCK == "ERROR") {
            continue;
        }
        if ($BLOCK == 'MSG_ERR') {
            $STR_MSG_ERR = cleanRules($BLOCK);
        } else {
            $STR_FINAL .= cleanRules($BLOCK);
        }
    }

    $STR_DEBUG = ob_get_clean();

    echo $STR_FINAL;

    //echo $HTML['ERROR'].'</div>';
    echo "</div>";
    echo "<div id='debug' style='display:none;   ' onclick='$(this).css(\"display\",\"none\")'><pre>" . $STR_DEBUG . '</pre></div>';
    if ($STR_MSG_ERR != '') {
        echo '<div class="w3-main"><div class="alert alert-info" role="alert">
An issue happened during the preparation of this webpage<br/>
<span class="bold">Reason: </span>' . $STR_MSG_ERR . '
</div></div>';
    }
    echo "</body></html>";
}
