<?php

/// Getting environment variables defined in setenv.sh. Here it's TG_DIR, the root directory for biorels.
$TG_DIR=getenv('TG_DIR');
if ($TG_DIR===false)die('TG_DIR not set in setenv.sh');/// TG_DIR is not set as environment variable
if (!is_dir($TG_DIR))die('Unable to find directory '.$TG_DIR);//// TG_DIR is not an existing directory
if (!is_file($TG_DIR.'/BACKEND/CONTAINER/env-file.txt'))die('No environment file found');
if (!is_file($TG_DIR.'/BACKEND/CONTAINER/biorels_container.sif'))echo ('Warning - No container file found');

echo "Root directory for biorels: ".$TG_DIR."\n";
$resSTDIN=fopen("php://stdin","r");
    echo("Do you confirm? Y/N. Then press return: ");
    $strChar = stream_get_contents($resSTDIN, 1);
    if ($strChar=='N') die('You did not agreed'."\n");
    if ($strChar!='Y') die('We didn\'t understood the answer'."\n");

/// Getting environment variables defined in setenv.sh. Here it's DB_SCHEMA, the name of the public schema
$DB_SCHEMA=getenv('DB_SCHEMA');	if ($DB_SCHEMA===false) die('No schema provided');
/// Getting environment variables defined in setenv.sh. Here it's PRIVATE_SCHEMA, the name of the private schema
$PRIVATE_SCHEMA=getenv('SCHEMA_PRIVATE');



echo "\n\n\nPublic schema:".$DB_SCHEMA."\n";
	$resSTDIN=fopen("php://stdin","r");
    echo("This will delete this schema if it exists. Do you confirm? Y/N. Then press return: ");
    $strChar = stream_get_contents($resSTDIN, 1);
    if ($strChar=='N') die('You did not agreed'."\n");
    if ($strChar!='Y') die('We didn\'t understood the answer'."\n");


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
	$resSTDIN=fopen("php://stdin","r");
    echo("This will delete this schema if it exists. Do you confirm? Y/N. Then press return: ");
    $strChar = stream_get_contents($resSTDIN, 1);
    if ($strChar=='N') die('You did not agreed'."\n");
    if ($strChar!='Y') die('We didn\'t understood the answer'."\n");
	$change=array('DB_SCHEMA_NAME'=>$DB_SCHEMA,'DB_PRIVATE_SCHEMA'=>$PRIVATE_SCHEMA);
//// Here we convert the template sql file by changing the template schema name to the private schema name
	convertFile($TG_DIR.'/BACKEND/INSTALL/biorels_private.sql',$TG_DIR.'/BACKEND/INSTALL/private_schema_ready.sql',$change);
	/// Execute the installation file for the private schema
	exec(' psql -h $DB_HOST -p $DB_PORT -U $PGUSER -d $DB_NAME -f '.$TG_DIR.'/BACKEND/INSTALL//private_schema_ready.sql',$res,$return_code);
	if ($return_code!=0) die("Unable to create private postgres schema");
}


echo "\n\n\n\nCreating CONTAINER shells\n";
 $files1 = scandir($TG_DIR.'/BACKEND/SCRIPT/SHELL/');
 if (!is_dir($TG_DIR.'/BACKEND/CONTAINER_SHELL') && !mkdir($TG_DIR.'/BACKEND/CONTAINER_SHELL')) die('Unable to create CONTAINER_SHELL directory');



$SING_COMMAND='biorels_exe ';



foreach ($files1 as $file)
{
	if ($file=='.'||$file=='..'||$file=='setenv.sh')continue;
	echo "\t=>".$file."\n";
	chmod($TG_DIR.'/BACKEND/SCRIPT/SHELL/'.$file,0755);
	$fpO=fopen($TG_DIR.'/BACKEND/CONTAINER_SHELL/'.$file,'w');
	$fp=fopen($TG_DIR.'/BACKEND/SCRIPT/SHELL/'.$file,'r');

	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if ($line=='')continue;
		if (substr($line,0,1)=='#'|| strpos($line,'source')!==false)fputs($fpO,$line."\n");
		else 
		{
			$IS_JOB=false;
			//echo $line."\n";
			
			$path=$TG_DIR.substr($line,strpos($line,'/',strpos($line,' ')));
			if (strpos($path,' $1')!==false)
			{
				$IS_JOB=true;
				$path=trim(str_replace("//","/",$path));
				$pos=strrpos($path,'/');
				$pos2=strpos($path,' ',$pos);
				$params=substr($path,$pos2);
				$path=substr($path,0,$pos2);
				
			}
			if (!is_file($path))
			{
				echo "WARNING: For ".$file.": The path in the script does not exist\nPath: ".$path."\n";
			}
			fputs($fpO,$SING_COMMAND.$line."\n");
		}

	}
	fclose($fp);
	fclose($fpO);
	
}




echo "\n\n\n\nCreating CONTAINER shells\n";
 $files1 = scandir($TG_DIR.'/BACKEND/PRIVATE_SCRIPT/SHELL/');

$SING_COMMAND='biorels_exe ';


foreach ($files1 as $file)
{
	if ($file=='.'||$file=='..'||$file=='setenv.sh')continue;
	echo "\t=>".$file."\n";
	chmod($TG_DIR.'/BACKEND/PRIVATE_SCRIPT/SHELL/'.$file,0755);
	$fpO=fopen($TG_DIR.'/BACKEND/CONTAINER_SHELL/'.$file,'w');
	$fp=fopen($TG_DIR.'/BACKEND/PRIVATE_SCRIPT/SHELL/'.$file,'r');

	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if ($line=='')continue;
		if (substr($line,0,1)=='#'|| strpos($line,'source')!==false)fputs($fpO,$line."\n");
		else 
		{
			$path=$TG_DIR.substr($line,strpos($line,'/'));
			if (!is_file($path))
			{
				echo "WARNING: For ".$file.": The path in the script does not exist\nPath: ".$path."\n";
			}
			fputs($fpO,$SING_COMMAND.$line."\n");
		}

	}
	fclose($fp);
	fclose($fpO);
	
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
