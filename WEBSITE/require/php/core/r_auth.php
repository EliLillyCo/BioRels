<?php

///////////////////
/////////////////// file: r_auth.php
/////////////////// owner: DESAPHY Jeremy
/////////////////// creation date: 1/14/19
/////////////////// purpose: Check user system id/get or create user entry in database

if (!defined("BIORELS")) header("Location:/"); /// Biorels defined in index.php. Not existing? Go to index.php

$HEADERS = getallheaders();

////// DEVELOPMENT MODE:
if ($GLB_CONFIG['GLOBAL']['PRD_STATUS'] == "F") {
    //print_r($_SERVER['REMOTE_ADDR']);exit;
    global $USER_INPUT;
    $todo = true;
    if (isset($USER_INPUT['PARAMS'])) {
        $tmp = array_search('NOMPAAMIDIP', $USER_INPUT['PARAMS']);
        if ($tmp !== false) {
            unset($USER_INPUT['PARAMS'][$tmp]);
            $todo = false;
        }
    }
    /// Only some IP adresses are allowed for the dev version as we don't have authentification set.
    if ($todo && !in_array($_SERVER['REMOTE_ADDR'], explode("|", $GLB_CONFIG['GLOBAL']['DEV_IP_ALLOWED']))) {
        /// So we redirect to the correct auth page
        //throw new Exception("You are not allowed to access this website<br/>IP Address:".$_SERVER['REMOTE_ADDR'], ERR_TGT_ACC);
    }
    
    ///For testing purpose
    if ($GLB_CONFIG['GLOBAL']['WITH_HTTPD_ACCESS'] == 'T') {


        
        $urlbase = explode(":", $_SERVER["HTTP_HOST"])[0];
        $is_localhost = $urlbase  == "localhost" || $urlbase == "127.0.0.1";
        if ($is_localhost) {
            // if yes, set headers manually
            $HEADERS['X-User-Name'] = 'local user';
            $HEADERS['X-Webauth-Email'] = 'ABC@EFG.com';
            $HEADERS['X-Webauth-Groups'] = 'Group1';
            $HEADERS['X-Webauth-User'] = 'User';
        }

        if (!isset($HEADERS[$GLB_CONFIG['GLOBAL']['HTTPD_ACCESS']])) throw new Exception("Unauthorized access", ERR_TGT_ACC);


        $STRHTTPD = $HEADERS[$GLB_CONFIG['GLOBAL']['HTTPD_ACCESS']];


        $HTTPD_RULES = array();
        $tmp = explode("|", $GLB_CONFIG['GLOBAL']['HTTPD_GRP_RULE']);

        foreach ($tmp as $t) {
            $tab = explode(":", $t);
            $HTTPD_RULES[$tab[0]] = $tab[1];
        }

        $tab = explode(",", $STRHTTPD);
        foreach ($tab as $v) {
            if (!isset($HTTPD_RULES[$v])) continue;
            $R = $HTTPD_RULES[$v];
            for ($I = 0; $I < strlen($R); ++$I) {
                if (substr($R, $I, 1) == '1') $USER['Access'][$I] = true;
            }
        }
        $USER['id'] = '';
        $USER['last_name'] = '';
        $USER['first_name'] = '';
        if ($GLB_CONFIG['GLOBAL']['HTTPD_USER_NAME'] != 'N/A') {
            $tab = explode(" ", $HEADERS[$GLB_CONFIG['GLOBAL']['HTTPD_USER_NAME']]);
            $USER['last_name'] = $tab[count($tab) - 1];
            unset($tab[count($tab) - 1]);
            $USER['first_name'] = implode(" ", $tab);
        }
        if ($GLB_CONFIG['GLOBAL']['HTTPD_USER_ID'] != 'N/A') {
            $USER['id'] = $HEADERS[$GLB_CONFIG['GLOBAL']['HTTPD_USER_ID']];
        }
        if ($GLB_CONFIG['GLOBAL']['HTTPD_USER_EMAIL'] != 'N/A') {
            $USER['email'] = $HEADERS[$GLB_CONFIG['GLOBAL']['HTTPD_USER_EMAIL']];
        }
        //($USER);
    }
}

///// SPECIFIC AUTHENTIFICATION BLOCK
else if ($GLB_CONFIG['GLOBAL']['WITH_HTTPD_ACCESS'] == 'T') {
    /// If the user came from the authentification (which he/she is supposed to), the proxy used for auth will send in the headers

    $HEADERS = getallheaders();
    // get current url
    // check if current url is localhost
    $urlbase = explode(":", $_SERVER["HTTP_HOST"])[0];
    $is_localhost = $urlbase  == "localhost" || $urlbase == "127.0.0.1";
    if ($is_localhost) {
        // if yes, set headers manually
        $HEADERS['X-User-Name'] = 'local user';
        $HEADERS['X-Webauth-Email'] = 'local@test.com';
        $HEADERS['X-Webauth-Groups'] = 'Group1';
        $HEADERS['X-Webauth-User'] = 'User';
    }
    //print_r($HEADERS);
    //echo "<br/>";
    //echo $GLB_CONFIG['GLOBAL']['HTTPD_ACCESS'];

    if (!isset($HEADERS[$GLB_CONFIG['GLOBAL']['HTTPD_ACCESS']])) throw new Exception("Unauthorized access", ERR_TGT_ACC);


    $STRHTTPD = $HEADERS[$GLB_CONFIG['GLOBAL']['HTTPD_ACCESS']];


    defineAccess($STRHTTPD);
    $USER['id'] = '';
    $USER['last_name'] = '';
    $USER['first_name'] = '';
    $USER['full_name'] = '';
    if ($GLB_CONFIG['GLOBAL']['HTTPD_USER_NAME'] != 'N/A') {
        $tab = explode(" ", $HEADERS[$GLB_CONFIG['GLOBAL']['HTTPD_USER_NAME']]);
        $USER['full_name'] = $HEADERS[$GLB_CONFIG['GLOBAL']['HTTPD_USER_NAME']];
        $USER['last_name'] = $tab[count($tab) - 1];
        unset($tab[count($tab) - 1]);
        $USER['first_name'] = implode(" ", $tab);
    }
    if ($GLB_CONFIG['GLOBAL']['HTTPD_USER_ID'] != 'N/A') {
        $USER['id'] = $HEADERS[$GLB_CONFIG['GLOBAL']['HTTPD_USER_ID']];
    }
    if ($GLB_CONFIG['GLOBAL']['HTTPD_USER_EMAIL'] != 'N/A') {
        $USER['email'] = $HEADERS[$GLB_CONFIG['GLOBAL']['HTTPD_USER_EMAIL']];
    }
}
/////END  SPECIFIC AUTHENTIFICATION BLOCK


function defineAccess($STRHTTPD)
{
    global $GLB_CONFIG;
    global $USER;
    $HTTPD_RULES = array();
    $tmp = explode("|", $GLB_CONFIG['GLOBAL']['HTTPD_GRP_RULE']);

    foreach ($tmp as $t) {
        $tab = explode(":", $t);
        $HTTPD_RULES[$tab[0]] = $tab[1];
    }

    $tab = explode(",", $STRHTTPD);
    foreach ($tab as $v) {
        if (!isset($HTTPD_RULES[$v])) continue;
        $R = $HTTPD_RULES[$v];
        for ($I = 0; $I < strlen($R); ++$I) {
            if (substr($R, $I, 1) == '1') $USER['Access'][$I] = true;
        }
    }
}



function getIpAddress()
{
    $ipAddress = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        // to get shared ISP IP address
        $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // check for IPs passing through proxy servers
        // check if multiple IP addresses are set and take the first one
        $ipAddressList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        foreach ($ipAddressList as $ip) {
            if (!empty($ip)) {
                // if you prefer, you can check for valid IP address here
                $ipAddress = $ip;
                break;
            }
        }
    } else if (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
        $ipAddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    } else if (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (!empty($_SERVER['HTTP_FORWARDED'])) {
        $ipAddress = $_SERVER['HTTP_FORWARDED'];
    } else if (!empty($_SERVER['REMOTE_ADDR'])) {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
    }
    return $ipAddress;
}


function compareAccess($LEVEL)
{
    global $USER;
    $TEST_LEVEL = $USER['Access'];

    for ($I = 0; $I < count($TEST_LEVEL); ++$I) {
        if ($TEST_LEVEL[$I] == substr($LEVEL, $I, 1) && substr($LEVEL, $I, 1) == 1) return true;
    }
    return false;
}
