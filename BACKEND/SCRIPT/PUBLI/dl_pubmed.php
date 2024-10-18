<?php
ini_set('memory_limit','5000M');
/**
 SCRIPT NAME: dl_pubmed
 PURPOSE:     Download yearly pubmed files and process them
 
*/
$JOB_NAME='dl_pubmed';

/// Get root directories
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');

/// Get job id
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
/// Get job info
$JOB_INFO=$GLB_TREE[$JOB_ID];




addLog("Create directory");
	/// Set up directory
	$R_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'];
	if (!is_dir($R_DIR) && !mkdir($R_DIR)) 											failProcess($JOB_ID."001",'Unable to create new process dir '.$R_DIR);	
	$W_DIR=$R_DIR.'/'.getCurrDate();
	if (!is_dir($W_DIR) && !mkdir($W_DIR)) 											failProcess($JOB_ID."002",'Unable to create new process dir '.$W_DIR);	
	if (!chdir($W_DIR)) 															failProcess($JOB_ID."003",'Unable to access process dir '.$W_DIR);

	/// Setting up the process control directory to the current release so that the next job can pick it up
	$PROCESS_CONTROL['DIR']=getCurrDate();
	

	// Check if the WEB FTP link is set
	if (!isset($GLB_VAR['LINK']['FTP_NCBI']))										failProcess($JOB_ID."004",'FTP_NCBI path no set');

addLog("Working directory:".$W_DIR);

addLog("Get last refresh date");
	/// Get the last refresh date from db_pubmed_info job
	/// i.e. the last date the process was completely run
	$PRD_PUBLI=$GLB_TREE[getJobIDByName('db_pubmed_info')];
	$PUBLI_START_STATUS= ($PRD_PUBLI['TIME']['DEV_DIR']=='-1');
	
	$PREV_DIR=$R_DIR.'/'.$PRD_PUBLI['TIME']['DEV_DIR'];
	$PRD_DATE=$PRD_PUBLI['TIME']['DEV_DIR'];
	echo "PRD DATE:".$PRD_DATE."\n";


	/// Here we convert the PRD_DATE to a timestamp to compare to today's date
	$PRD_DATE_TIMESTAMP=0;
	$now = time(); 
	if (!$PUBLI_START_STATUS)	$PRD_DATE_TIMESTAMP = strtotime($PRD_DATE);
	echo "PRD TIMESTAMP:".$PRD_DATE_TIMESTAMP."\n";
	

addLog("Get Journal list");

	$URL=$GLB_VAR['LINK']['FTP_NCBI'].'/pubmed/J_Entrez.txt';
	if (dl_file($URL,3,'Journals.csv')===false)										failProcess($JOB_ID."005",'Unable to download ');

addLog("Getting Updated Files timestamp");
	/// Getting the baseline and updatefiles latest date
	/// Pubmed has a baseline which is the previous years
	/// And an updatefiles which is the current year
	/// So we need to find out the date for each and then compare it to the PRD_DATE

	if (checkFileExist('index.html') && !unlink('index.html')) 						failProcess($JOB_ID."006",'Unable to remove previous status file');
	if (!dl_file($GLB_VAR['LINK']['FTP_NCBI'].'/pubmed/',3,'index.html'))			failProcess($JOB_ID."007",'Unable to download status file');
	
	
	$fp=fopen('index.html','r');if (!$fp)											failProcess($JOB_ID."008",'Unable to open index.xml ');
	$TIME_BASELINE=null;
	$TIME_UPDATEFILES=null;
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");if ($line=='')continue;
		
		if (strpos($line,'updatefiles')===false) continue;
		
		$tab=array_values(array_filter(explode(" ",$line)));
		
		$TIME_UPDATEFILES= strtotime($tab[2]);
		if ($TIME_UPDATEFILES===false)												failProcess($JOB_ID."009", "Unable to understand date ".$tab[0].' '.$tab[1].' '.$tab[2]."\n");
		
	}
	fclose($fp);
	if (!unlink('index.html'))														failProcess($JOB_ID."010",'Unable to delete index.html');
	
	
	
addLog("Getting Baseline Files timestamp");

	if (!dl_file($GLB_VAR['LINK']['FTP_NCBI'].'/pubmed/baseline/',3,'index.html'))	failProcess($JOB_ID."011",'Unable to download status file');
	
	$fp=fopen('index.html','r');if (!$fp)											failProcess($JOB_ID."012",'Unable to open index.xml ');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");if ($line=='')continue;
		
		if (strpos($line,'.gz')===false) continue;
			
		$tab=array_values(array_filter(explode(" ",$line)));
		
		$TIME_BASELINE= strtotime($tab[2]);
		if ($TIME_BASELINE===false)													failProcess($JOB_ID."013", "Unable to understand date ".$tab[0].' '.$tab[1].' '.$tab[2]."\n");
	
	}
	fclose($fp);
	if (!unlink('index.html'))														failProcess($JOB_ID."014",'Unable to delete index.html');
	
	
	
	/// Now we should have both - otherwise trouble
	if ($TIME_BASELINE===false || $TIME_UPDATEFILES===false)						failProcess($JOB_ID."014",'Unable to get timestamps');


	if (is_file('ENTRIES.xml') &&  !unlink('ENTRIES.xml'))							failProcess($JOB_ID."015",'Unable to delete previous ENTRIES file');
	
	
	
	echo "LAST DOWNLOAD DATE: ".$PRD_DATE_TIMESTAMP."\n";
	echo "BASELINE DATE ". $TIME_BASELINE."\n";


	/// PRD Date is before the baseline, so we need to download ALL the baseline
	if ($PRD_DATE_TIMESTAMP < $TIME_BASELINE)
	{

		echo "DOWNLOADING BASELINE\n";
		if (!is_dir($W_DIR.'/BASELINE') && !mkdir($W_DIR.'/BASELINE'))				failProcess($JOB_ID."016",'Unable to create baseline directory');
		if (!chdir($W_DIR.'/BASELINE') )					 						failProcess($JOB_ID."017",'Unable to access baseline directory');
		
		///Download whole directory
		if (!checkFileExist('baseline.html') &&
		!dl_file($GLB_VAR['LINK']['FTP_NCBI'].'/pubmed/baseline/',3,'baseline.html'))			failProcess($JOB_ID."018",'Unable to download baseline');

		$fpB=fopen('baseline.html','r');if (!$fpB) 												failProcess($JOB_ID."019",'Unable to open baseline.html');
		while(!feof($fpB))
		{
			$line=stream_get_line($fpB,10000,"\n");if ($line=='')continue;
			$tab=explode(">",$line);
			if (!isset($tab[1]))continue;
			$fname=substr($tab[1],0,-3);
			if (substr($fname,0,6)!='pubmed')continue;
			 if (!checkFileExist($fname) &&
                !dl_file($GLB_VAR['LINK']['FTP_NCBI'].'/pubmed/baseline/'.$fname,3))            failProcess($JOB_ID."020",'Unable to download '.$fname);
		}
		fclose($fpB);


		/// Now that downloaded the files, we need to merge them into ENTRIES.xml
		/// First we need to get the list of files
		/// Compare each file with their hash to confirm its properly downloaded
		/// Then ungzip and merge them into ENTRIES.xml
		$files=scandir('./');
		foreach ($files as $f)
		{
			if (substr($f,-3)!='.gz')continue;
			// We need to get the md5 hash
			if (is_file($f.'.md5'))
			{
				$tab=explode("=",file_get_contents($f.'.md5'));
				$md5=trim($tab[1]);
				/// And compare it with the file hash
				if (md5_file($f)!=$md5)												failProcess($JOB_ID."021",$f.' hash differs '.md5_file($f).' '.$md5."\n");
				if (!unlink($f.'.md5') )											failProcess($JOB_ID."022",'Unable to delete '.$f.'.md5');
			}
			/// Unzip and push the xml file to ENTRIES.xml
			if (!ungzip($f))														failProcess($JOB_ID."023",'Unable to ungzip '.$f."\n");
			exec('cat '.substr($f,0,-3).' >> ../ENTRIES.xml',$res,$return_code);
			if ($return_code!=0)													failProcess($JOB_ID."024",'Unable to merge '.$f." in ENTRIES.xml\n");
			if (!unlink(substr($f,0,-3)) )											failProcess($JOB_ID."025",'Unable to delete '.$f);	
		}

	}





	echo "UPDATEFILES DATE ". $TIME_UPDATEFILES."\n";

	/// PRD date is before the updatefiles timestamp, so we need to download some of the updatefiles
	/// That should pretty much be always true
	if ($PRD_DATE_TIMESTAMP > $TIME_UPDATEFILES) successProcess();
	
	echo "DONWLOAD UPDATEFILE\n";
	if (!is_dir($W_DIR.'/UPDATEFILES') && !mkdir($W_DIR.'/UPDATEFILES'))		failProcess($JOB_ID."026",'Unable to create UPDATEFILES directory');
	if (!chdir($W_DIR.'/UPDATEFILES') )					 						failProcess($JOB_ID."027",'Unable to access UPDATEFILES directory');
	
	///Download index.html
	if (!dl_file($GLB_VAR['LINK']['FTP_NCBI'].'/pubmed/updatefiles/',3))		failProcess($JOB_ID."028",'Unable to download updatefiles');

	$fp=fopen('index.html','r');
	if (!$fp)																	failProcess($JOB_ID."029",'Unable to open index.html');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if (!preg_match('/pubmed[0-9A-Za-z]{7}.xml.gz/',$line,$matches))continue;
	
		$tab=array_values(array_filter(explode(" ",$line)));
		
		/// We want to make sure that the file is newer than the PRD_DATE
		
		$TIME_FILE= strtotime($tab[2]);//.' '.$tab[1].' '.$tab[0]);
		if ($PRD_DATE_TIMESTAMP > $TIME_FILE)continue;
		echo $TIME_FILE."\n";
		$DL_PATH=$GLB_VAR['LINK']['FTP_NCBI'].'/pubmed/updatefiles/'.$matches[0];
		if (!dl_file($DL_PATH,3))												failProcess($JOB_ID."030",'Unable to download updatefiles/'.$matches[0]);
		if (!dl_file($DL_PATH.'.md5',3))										failProcess($JOB_ID."031",'Unable to download updatefiles/'.$matches[0].'.gz');
		
		/// Getting the
		$tab=explode("=",file_get_contents($matches[0].'.md5'));
		
		$md5=trim($tab[1]);
		if (md5_file($matches[0])!=$md5)										failProcess($JOB_ID."032",$matches[0].' hash differs '.md5_file($matches[0]).' '.$md5."\n");
		
		if (!unlink($matches[0].'.md5') )										failProcess($JOB_ID."033",'Unable to delete '.$matches[0].'.md5');
		
		if (!ungzip($matches[0]))												failProcess($JOB_ID."034",'Unable to ungzip '.$matches[0]."\n");
		
		exec('cat '.substr($matches[0],0,-3).' >> ../ENTRIES.xml',$res,$return_code);
		if ($return_code!=0)													failProcess($JOB_ID."035",'Unable to merge '.substr($matches[0],0,-3)." in ENTRIES.xml\n");
			
		if (!unlink(substr($matches[0],0,-3)) )									failProcess($JOB_ID."036",'Unable to delete '.substr($matches[0],0,-3));	
	}
	fclose($fp);
	if (!unlink('index.html'))													failProcess($JOB_ID."037",'Unable to delete index.html');



successProcess();


	

?>
