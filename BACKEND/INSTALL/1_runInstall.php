<?php

/// Getting environment variables defined in setenv.sh. Here it's TG_DIR, the root directory for biorels.
$TG_DIR=getenv('TG_DIR');
if ($TG_DIR===false)die('TG_DIR not set in setenv.sh');/// TG_DIR is not set as environment variable
if (!is_dir($TG_DIR))die('Unable to find directory '.$TG_DIR);//// TG_DIR is not an existing directory
if (!is_file($TG_DIR.'/BACKEND/CONTAINER/env-file.txt'))die('No environment file found'."\n");
if (!is_file($TG_DIR.'/BACKEND/CONTAINER/biorels_container.sif'))echo ('Warning - No container file found'."\n");

$AGREE_ALL=false;
if (isset($argv[1]) && $argv[1]=='--agree') $AGREE_ALL=true;


echo "Root directory for biorels: ".$TG_DIR."\n";
if (!$AGREE_ALL)
{
	$resSTDIN=fopen("php://stdin","r");
    echo("Do you confirm? Y/N. Then press return: ");
    $strChar = stream_get_contents($resSTDIN, 1);
    if ($strChar=='N') die('You did not agreed'."\n");
    if ($strChar!='Y') die('We didn\'t understood the answer'."\n");
}
/// Getting environment variables defined in setenv.sh. Here it's DB_SCHEMA, the name of the public schema
$DB_SCHEMA=getenv('DB_SCHEMA');	if ($DB_SCHEMA===false) die('No schema provided');
/// Getting environment variables defined in setenv.sh. Here it's PRIVATE_SCHEMA, the name of the private schema
$PRIVATE_SCHEMA=getenv('SCHEMA_PRIVATE');



echo "\n\n\nPublic schema:".$DB_SCHEMA."\n";
if (!$AGREE_ALL)
{
	$resSTDIN=fopen("php://stdin","r");
    echo("This will delete this schema if it exists. Do you confirm? Y/N. Then press return: ");
    $strChar = stream_get_contents($resSTDIN, 1);
    if ($strChar=='N') die('You did not agreed'."\n");
    if ($strChar!='Y') die('We didn\'t understood the answer'."\n");
}

echo "\n\nCreating tables for ".$DB_SCHEMA."\n";
/// Here we are going to convert the sql template file into the installation file, which includes the correct schema name.
$change=array('DB_SCHEMA_NAME'=>$DB_SCHEMA);
$blocks=array();
if ($PRIVATE_SCHEMA!==false)$blocks=array('NO_PRIVATE');
convertFile($TG_DIR.'/BACKEND/INSTALL/biorels_public.sql',$TG_DIR.'/BACKEND/INSTALL/schema_ready.sql',$change);

//// We execute the installation file that will create all the database tables.
exec(' psql -h $DB_HOST -p $DB_PORT -U $PGUSER -d $DB_NAME -f '.$TG_DIR.'/BACKEND/INSTALL/schema_ready.sql',$res,$return_code);
 if ($return_code!=0) die("Unable to create postgres schema");
else "\n\n Tables created successfully\n";




if ($PRIVATE_SCHEMA!==false)
{
	echo "\n\n\n\nPrivate schema:".$PRIVATE_SCHEMA."\n";
	if (!$AGREE_ALL)
	{
	$resSTDIN=fopen("php://stdin","r");
    echo("This will delete this schema if it exists. Do you confirm? Y/N. Then press return: ");
    $strChar = stream_get_contents($resSTDIN, 1);
    if ($strChar=='N') die('You did not agreed'."\n");
    if ($strChar!='Y') die('We didn\'t understood the answer'."\n");
	}
	$change=array('DB_SCHEMA_NAME'=>$DB_SCHEMA,'DB_PRIVATE_SCHEMA'=>$PRIVATE_SCHEMA);
//// Here we convert the template sql file by changing the template schema name to the private schema name
	convertFile($TG_DIR.'/BACKEND/INSTALL/biorels_private.sql',$TG_DIR.'/BACKEND/INSTALL/private_schema_ready.sql',$change);
	/// Execute the installation file for the private schema
	exec(' psql -h $DB_HOST -p $DB_PORT -U $PGUSER -d $DB_NAME -f '.$TG_DIR.'/BACKEND/INSTALL//private_schema_ready.sql',$res,$return_code);
	if ($return_code!=0) die("Unable to create private postgres schema");
}


echo "INSTALL COMPLETE\n";

function convertFile($path,$newpath,$changes,$blocks=array())
{
	
	$STR=file_get_contents($path);
	foreach ($changes as $change=>$to)$STR=str_replace($change,$to,$STR);
	if ($blocks!=array())
	foreach ($blocks as $block)$STR=removeBlock($STR,$block);

	$fpO=fopen($newpath,'w');if (!$fpO) die("Unable to open path ".$path);
	fputs($fpO,$STR);
	fclose($fpO);
}

function removeBlock($STR, $block)
{
    
    do {
        $prev = $STR;
        $pos = strpos($STR, '$[' . $block . ']');
        $end_pos = strpos($STR, '$[/' . $block . ']', $pos);
        if ($pos !== false && $end_pos !== false) {
            $STR = substr($STR, 0, $pos) . substr($STR, $end_pos + strlen($block) + 4);
        }
    } while ($prev != $STR);
	return $STR;
}

?>
