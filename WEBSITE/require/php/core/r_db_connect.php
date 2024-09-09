<?php

///////////////////
/////////////////// file: r_db_connect.php
/////////////////// owner: DESAPHY Jeremy
/////////////////// creation date: 11/26/18
/////////////////// purpose: Connect to the database
/////////////////// LOG:
///////////////////  1/14/19: Add dbconn.txt to hide connexion data from plain sight


if (!defined("BIORELS")) header("Location:/"); /// BIORELS defined in index.php. Not existing? Go to index.php


try {

    if (isset($_SESSION['LOWER_ACCESS']))
	{
		if (!$USER['ADMIN']) throw new Exception("Trying to change access when not admin");
		
		for ($I=0;$I<count($USER['Access']);++$I)
		if ($I==0)$USER['Access'][$I]=true;
		else $USER['Access'][$I]=false;
	}
	if (isset($_SESSION['HIGHER_ACCESS']))
	{
		if (!$USER['ADMIN']) throw new Exception("Trying to change access when not admin");
		$HEADERS = getallheaders();
   		$STRHTTPD = $HEADERS[$GLB_CONFIG['GLOBAL']['HTTPD_ACCESS']];
		defineAccess($STRHTTPD);
	}


    $db_host = getenv('DB_HOST');
    $db_port = getenv('DB_PORT');
    $db_name = getenv('DB_NAME');


    if ($USER['Access'][1])
    {
        if ($GLB_CONFIG['GLOBAL']['PRIVATE_DB_USER']=='[DB_USER]') throw new Exception("PRIVATE_DB_USER not set in config file");
        if ($GLB_CONFIG['GLOBAL']['PRIVATE_DB_PASS']=='[DB_PASS]') throw new Exception("PRIVATE_DB_PASS not set in config file");
       
       $db_user=getenv($GLB_CONFIG['GLOBAL']['PRIVATE_DB_USER']);
       $db_p=getenv($GLB_CONFIG['GLOBAL']['PRIVATE_DB_PASS']);
       if ($db_user===false) throw new Exception("PRIVATE_DB_USER not set in environment");
         if ($db_p===false) throw new Exception("PRIVATE_DB_PASS not set in environment");
       
    }
   else
   {
    if ($GLB_CONFIG['GLOBAL']['PUBLIC_DB_USER']=='[DB_USER]') throw new Exception("PUBLIC_DB_USER not set in config file");
    if ($GLB_CONFIG['GLOBAL']['PUBLIC_DB_PASS']=='[DB_PASS]') throw new Exception("PUBLIC_DB_PASS not set in config file");
   
       $db_user=getenv($GLB_CONFIG['GLOBAL']['PUBLIC_DB_USER']);
       $db_p=getenv($GLB_CONFIG['GLOBAL']['PUBLIC_DB_PASS']);
       if ($db_user===false) throw new Exception("PUBLIC_DB_USER not set in environment");
         if ($db_p===false) throw new Exception("PUBLIC_DB_PASS not set in environment");
       
   }
   
    // $db_user = getenv('DB_USER');
    // $db_p = getenv('DB_PASSWORD');


    $DB_CONN = new PDO(
        'pgsql:host=' . $db_host . ';port=' . $db_port . ';dbname=' . $db_name,
        $db_user,
        $db_p,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false)
    );
} catch (PDOException $e) {
    throw new Exception("Unable to connect to the database\n" . $e->getMessage(), ERR_TGT_SYS);
}

// Set DB_SCHEMA from the global settings as the session search_path, so we don't have to specify the schema name in every query
$DB_SCHEMA = getenv($GLB_CONFIG['GLOBAL']['DB_SCHEMA']);
$DB_SCHEMA_PRIVATE = getenv($GLB_CONFIG['GLOBAL']['DB_SCHEMA_PRIVATE']);

try {
    runQuery('SET SESSION search_path TO ' . $DB_SCHEMA . ';');
} catch (PDOException $e) {
    throw new Exception("Unable to set search_path\n" . $e->getMessage(), ERR_TGT_SYS);
}


function array_change_key_case_recursive($arr)
{
    return array_map(function ($item) {
        if (is_array($item))
            $item = array_change_key_case_recursive($item);
        return $item;
    }, array_change_key_case($arr, CASE_UPPER));
}

function runQuery($query)
{
    
    try {
        global $DB_CONN;
        $stmt = $DB_CONN->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (defined("NO_CASE_CHANGE")) return $results;
        return array_change_key_case_recursive($results);
    } catch (PDOException $e) {
        throw new Exception("Error while running query\n" . $e->getMessage() . "\n\n" . $query, ERR_TGT_SYS);
    }
}

function runQueryNoRes($query)
{

    try {
        //echo $query."<br/>";
        global $DB_CONN;
        $stmt = $DB_CONN->prepare($query);
        $res = $stmt->execute();
        $stmt->closeCursor();
        unset($stmt);
        $stmt = null;
        return $res;
    } catch (PDOException $e) {
        echo "Error while running query\n" . $e->getMessage() . "\n\n" . $query . "\n";
        return false;
    }
}



//// Now that we have the user info, we check them against the database user entries:

$query = "SELECT * FROM biorels.WEB_USER WHERE GLOBAL_ID = '" . $USER['id'] . "'";
// $query = "SELECT * FROM WEB_USER WHERE GLOBAL_ID = '" . $USER['id'] . "'";
$RES = runQuery($query);

//// Not found -> Create
if (count($RES) == 0) {
    $tab = explode("|", $GLB_CONFIG['GLOBAL']['EMAIL']);

    //Create new user if it doesn't exist:
    $query = "INSERT INTO WEB_USER (WEB_USER_ID,GLOBAL_ID,LAST_NAME,FIRST_NAME,EMAIL) " .
        "VALUES (nextval('web_user_seq'), " .
        "'" . $USER['id'] . "'," .
        "'" . $USER['last_name'] . "'," .
        "'" . $USER['first_name'] . "'," .
        "'" . $USER['email'] . "')";

    $RES = runQuery($query);

    /// Get newly created user ID
    $query = "SELECT WEB_USER_ID FROM WEB_USER WHERE GLOBAL_ID = '" . $USER['id'] . "'";
    $RES = runQuery($query);
    $USER['DB_ID'] = $RES[0]['WEB_USER_ID'];

    $tab = explode("|", $GLB_CONFIG['GLOBAL']['EMAIL']);

    foreach ($tab as $EM) {
        if (!mail(
            $EM,
            "BIORELS USER",
            $USER['last_name'] . " - " .
                $USER['first_name'] . ' - DBID:' .
                $USER['DB_ID'] . ' - Global ID: ' . $USER['id']
        )) echo "FAIL TO SEND MAIL";
    }
} else {

    $USER['DB_ID'] = $RES[0]['WEB_USER_ID'];
    if ($RES[0]['EMAIL'] == '') {
        // runQuery('UPDATE WEB_USER SET EMAIL = \''.$USER['email'].'\' WHERE web_user_Id = '.$USER['DB_ID']);
        // comment out for testing will want to comment back in -- make a note about update priviledges
    }
}

insertToStat($QUERY_STR);


//if (!
updateGroups($HEADERS['X-Webauth-Groups'],$USER['DB_ID'])
//)
// throw new Exception("Unable to update groups")
;
function updateGroups($GROUPS,$DB_ID)
{
//echo '<pre>';
    //print_r(getallheaders());
   // print_r($GROUPS);
    try{
        $query="SELECT * FROM org_group_map ogm, org_group og where og.org_Group_id = ogm.org_Group_id AND ogm.web_user_id=".$DB_ID;
        //echo $query."\n";
        $res=runQuery($query);
      //  echo count($res)."\n";
     // print_R($res);
        $DATA=array();
        foreach ($res as $line)
        {
            $line['STATUS']=false;
            $DATA[$line['ORG_GROUP_NAME']]=$line;
        }

        //echo "\n".'DATA:';
       // print_r($DATA);
        $tab=explode(",",$GROUPS);
        // echo "\n\nGROUPS:";
        // print_R($tab);
     
        foreach ($tab as $N)
        {
           // echo "TESTING: ".$N."\n";
            if (!isset($DATA[$N]))
            {
             //   echo "NOT FOUND\n";
                $ORG_GROUP_ID=-1;
                $res=runQuery("SELECT org_group_id FROM org_group where org_group_name = '".str_replace("'","''",$N)."'");
              //  print_R($res);
                if (count($res)==0)
                {
                  //  echo "INSERT INTO org_group VALUES (nextval('org_group_sq'),'".str_replace("'","''",$N)."') RETURNING org_Group_id"."\n";
                    $res=runQuery("INSERT INTO org_group VALUES (nextval('org_group_sq'),'".str_replace("'","''",$N)."') RETURNING org_Group_id");
//print_r($res);
                    $ORG_GROUP_ID=$res[0]['ORG_GROUP_ID'];

                }else
                {
//echo "EXISTING GROUP\n";
                $ORG_GROUP_ID=$res[0]['ORG_GROUP_ID'];
                }
                $query="INSERT INTO org_group_map (org_group_map_id, org_Group_id,web_user_id ) VALUES (nextval('org_Group_map_sq'),".$ORG_GROUP_ID.",".$DB_ID.')';
              //echo $query."\n";;
                if (!runQueryNoRes($query)) return false;
               // echo "SUCCESS\n";
            }
            else
            {
               // echo "EXISITNG\n";
                $DATA[$N]['STATUS']=true;
            }
        }
        $TO_DEL=array();
        foreach ($DATA as &$S)if (!$S['STATUS'])$TO_DEL[]=$S['ORG_GROUP_MAP_ID'];
        if ($TO_DEL!=array())
        {
        $query= "DELETE FROM org_group_map where org_group_map_id IN (".implode(',',$TO_DEL).')';
       // echo $query."\n"; 
        if (!runQueryNoRes($query)) return false;
        
        }
    }catch(Exception $e)
    {
        print_R($e);
        return false;
    }
    return true;
}




function getUserGroups($USER_ID)
{
	$res=runQuery("SELECT org_group_name FROM org_group og, org_group_map ogm, web_user wu
     where wu.web_user_Id = ogm.web_user_id AND  og.org_group_id = ogm.org_group_id AND global_id= '".$USER_ID."'");
    $DATA=array();
	foreach ($res as $line)$DATA[]=$line['ORG_GROUP_NAME'];
	return $DATA;
}

function insertToStat($PAGE)
{

    global $GLB_CONFIG;
    global $USER;
    global $USER_INPUT;

    if ($PAGE == '') $PAGE = 'WELCOME';

    $PRD_STATUS = (($GLB_CONFIG['GLOBAL']['PRD_STATUS'] == "F") ? "'D'" : "'P'");
    $VALUE = $USER_INPUT['PORTAL']['VALUE'];
    if ($USER_INPUT['PORTAL']['VALUE'] == array()) $VALUE = '';
    //print_r($_GET);
    $query = "INSERT INTO WEB_USER_STAT (WEB_USER_STAT_ID,
					     DATE_ACCESSED,
					     WEB_USER_ID,
					     WEBSITE,
						 PORTAL,
					     PORTAL_VALUE,
					     PAGE,
						 IP_ADDR) 
		 VALUES (nextval('web_stat_seq'),
			 CURRENT_TIMESTAMP,"
        . $USER['DB_ID'] . ","
        . $PRD_STATUS . ",'"
        . $USER_INPUT['PORTAL']['NAME'] . "','"
        . $VALUE . "','"
        . $PAGE . "','"
        . getIpAddress() . "')";

    runQuery($query);
}




    ?>