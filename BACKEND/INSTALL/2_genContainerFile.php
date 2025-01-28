<?php

/// Getting environment variables defined in setenv.sh. Here it's TG_DIR, the root directory for biorels.
$TG_DIR=getenv('TG_DIR');
if ($TG_DIR===false)die('TG_DIR not set in setenv.sh');/// TG_DIR is not set as environment variable
if (!is_dir($TG_DIR))die('Unable to find directory '.$TG_DIR);//// TG_DIR is not an existing directory
if (!is_file($TG_DIR.'/BACKEND/CONTAINER/env-file.txt'))die('No environment file found'."\n");
if (!is_file($TG_DIR.'/BACKEND/CONTAINER/biorels_container.sif'))echo ('Warning - No container file found'."\n");



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

?>
