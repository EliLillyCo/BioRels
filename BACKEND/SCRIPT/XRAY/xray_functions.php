<?php
$DISABLE_MOE=false;



function preloadTaxon($TG_DIR)
{
	$U_DIR=$TG_DIR.'/PRD_DATA/UNIPROT/';
	if (!is_dir($U_DIR)) return false;
	if (!checkFileExist($U_DIR.'/speclist.txt'))return false;
	$DATA=array();
	$fp=fopen($U_DIR.'/speclist.txt','r');if (!$fp)return false;
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if (substr($line,0,5)=="_____")break;
	}
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if ($line=='')break;
		if ($line[0]==' ')continue;
		
		$tab=array_values(array_filter(explode(" ",$line)));
		
		$DATA[$tab[0]]=array(substr($tab[2],0,-1),'EXACT');
	}
	$N=0;
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if (substr($line,0,5)=="=====")$N++;
		if ($N==2)break;
	}
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if ($line=='')continue;
		if ($line[0]==' ')continue;
		if (substr($line,0,5)=='-----')break;
		
		$tab=array_values(array_filter(explode(" ",$line)));
		$DATA[$tab[0]]=array(substr($tab[2],0,-1),'RANGE');
	}
	fclose($fp);
	return $DATA;
}


function cleanData(&$ENTRY,$JOB)
{
	/// 					0:GET_STRUCTE, , 2=>PDB_SEP, , 4=>BLASTP, , 	6=>SITEMAP, , 	8=>INTER_GEN, , 10=>SITE_GEN,
	///						1=>PDB_PREP	3=>VOLSITE	5=>CONVERT 	7=>CLUSTERING	9=>INTER_COMP	11=>SITE_COMP
	$RULE_DEL=array('GET_STRUCTURE'	=>array(1,	1,	 1,	1,	1,	1,	1,	1,	1,	1,	1,	1),
			'PDB_PREP'	=>array(0,	1,	 1,	1,	1,	1,	1,	1,	1,	1,	1,	1),
			'PDB_SEP'	=>array(0,	0,	 1,	1,	1,	1,	1,	1,	1,	1,	1,	1),
			'BLASTP'	=>array(0,	0,	 0,	1,	1,	0,	0,	1,	0,	0,	1,	1), 
			'VOLSITE'	=>array(0,	0,	 0,	1,	0,	0,	0,	1,	0,	0,	1,	1), 
			'CONVERT'	=>array(0,	0,	 0,	0,	0,	1,	1,	0,	0,	0,	0,	0),
			'SITEMAP'	=>array(0,	0,	 0,	0,	0,	0,	1,	0,	0,	0,	0,	0), 
			'CLUSTERING'	=>array(0,	0,	 0,	0,	0,	0,	0,	1,	1,	1,	0,	0),
			'INTER_GEN'	=>array(0,	0,	 0,	0,	0,	0,	0,	0,	1,	1,	0,	0), 
			'INTER_COMP'	=>array(0,	0,	 0,	0,	0,	0,	0,	0,	0,	1,	0,	0),
			'SITE_GEN'	=>array(0,	0,	 0,	0,	0,	0,	0,	0,	0,	0,	1,	1),
			'SITE_COMP'	=>array(0,	0,	 0,	0,	0,	0,	0,	0,	0,	0,	0,	1));
	$RULE_T=array_keys($RULE_DEL);
	if (!isset($RULE_DEL[$JOB])){echo "JON NAME NOT FOUND\n";return;}

	$SEL_RULE=$RULE_DEL[$JOB];
	foreach ($SEL_RULE as $POS=>$TODO)
	{


		//echo $POS.' '.$TODO.' '.$RULE_T[$POS]."\n";
		if ($TODO==0)continue;
		


	switch ($RULE_T[$POS])	
	{
		case 'GET_STRUCTURE':
			$ENTRY['PROCESS']['GET_STRUCTURE']="";
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|GET_STRUCTURE|CLEANING";
			break;
		case 'PDB_PREP':
			if (checkFileExist($ENTRY['FILES']['MPREP']))system("rm -f ".$ENTRY['FILES']['MPREP']);
			unset($ENTRY['FILES']['MPREP']);
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_PREP|CLEANING";
			$ENTRY['PROCESS']['PDB_PREP']="";
			break;
		case 'PDB_SEP':
			$ENTRY['FILES']['STRUCTURE']=array();
			$ENTRY['JOBS']=array();
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|CLEANING";
			$ENTRY['PROCESS']['PDB_SEP']="";
			if (is_dir($ENTRY['DIR'].'/STRUCTURE/'))system("rm -f ".$ENTRY['DIR'].'/STRUCTURE/*');
			break;
		
		case 'BLASTP':
		
			$ENTRY['PROCESS']['BLASTP']="";
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|CLEANING";
			if (is_dir($ENTRY['DIR'].'/BLASTP/'))system("rm -rf ".$ENTRY['DIR'].'/BLASTP/*');
			$ENTRY['BLASTP']=array();
			break;
		case 'VOLSITE':
			$ENTRY['PROCESS']['VOLSITE']="";
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|VOLSITE|CLEANING";
			if (is_dir($ENTRY['DIR'].'/VOLSITE/'))system("rm -rf ".$ENTRY['DIR'].'/VOLSITE/*');
			foreach ($ENTRY['CAVITIES'] as $K=>&$V){
				foreach ($V as $N=>$T) 	if (substr($N,-2)!="-s") unset($V[$N]);
			}

			foreach ($ENTRY['JOBS'] as $K=>&$V) { $V['VOLSITE']['DIR']="";$V['VOLSITE']['STATUS']="";}
			break;
		case 'SITE_CONV':
			$ENTRY['PROCESS']['SITE_CONV']="";
			$ENTRY['CONVERT']=array();
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|SITE_CONV|CLEANING";
			if (is_dir($ENTRY['DIR'].'/CONVERT/'))system("rm -rf ".$ENTRY['DIR'].'/CONVERT/*');
			break;
		case 'SITEMAP':
			$ENTRY['PROCESS']['SITEMAP']="";
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|SITEMAP|CLEANING";
			if (is_dir($ENTRY['DIR'].'/SITEMAP/'))system("rm -rf ".$ENTRY['DIR'].'/SITEMAP/*');
			foreach ($ENTRY['CAVITIES'] as $K=>&$V){
				foreach ($V as $N=>$T) 	if (substr($N,-2)=="-s") unset($V[$N]);
			}
			if (isset($ENTRY['JOBS']))
			foreach ($ENTRY['JOBS'] as $K=>&$V) { $V['SITEMAP']['DIR']="";$V['SITEMAP']['STATUS']="";}
						break;
		case 'CLUSTERING':

			$ENTRY['PROCESS']['CLUSTERING']='';
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|CLUSTERING|CLEANING";
			$ENTRY['CLUSTERING']=array();
			break;


	}
	}
	updateDataFile($ENTRY);

}

function createEntryDir(&$ENTRY,$ENTRIES_DIR,$JOB_ID)
{	

	$ENTRIES_DIR.='/';
	if (!is_dir($ENTRIES_DIR)) failProcess($JOB_ID."001",'Unable to find directory '.$ENTRIES_DIR); 
	$PDB=&$ENTRY['INIT']['ENTRY_NAME'];
	$UP_PDB=strtoupper($PDB);
	$PD_UP_DIR=$ENTRIES_DIR.substr($UP_PDB,1,2).'/';
	$PD_DIR=$PD_UP_DIR.$UP_PDB.'/';
	if (is_dir($PD_DIR) && is_file($PD_DIR.'_data'))
	{	
		
		$ENTRY=json_decode(file_get_contents($PD_DIR.'_data'),true);
		return "OK";
	}

	
	if (!is_dir($PD_UP_DIR) && !mkdir($PD_UP_DIR))
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|createEntryDir|Unable to create directory ".$PD_UP_DIR."\n";
		$ENTRY['PROCESS']['DIR']='FAIL';

		updateDataFile($ENTRY);
		return 'E1';
	}

	
	if (!is_dir($PD_DIR) && !mkdir($PD_DIR))
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|createEntryDir|Unable to create directory ".$PD_DIR."\n";
		$ENTRY['PROCESS']['DIR']='FAIL';
		updateDataFile($ENTRY);
		return 'E2';
	}

	$lf=array('STRUCTURE','CAVITIES','VOLSITE','INTERS','jobs','BLASTP');
	foreach ($lf as $D)
	{
		$D1=$PD_DIR.'/'.$D;
		if (!is_dir($D1) && !mkdir($D1))
		{
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|createEntryDir|Unable to create directory ".$D1."\n";
			$ENTRY['PROCESS']['DIR']='FAIL';
			updateDataFile($ENTRY);
			return false;
		}
	}
	$ENTRY['DIR']=$PD_DIR;
	$ENTRY['PROCESS']['DIR']='OK';

	updateDataFile($ENTRY);

	return 'OK';
}
function updateDataFile(&$DATA)
{

	$fp=fopen($DATA['DIR'].'/_data','w');
	if ($fp===null) {return false;}

	fputs($fp,json_encode($DATA)."\n");
	fclose($fp);
}

function loadEntry($PDB,$ENTRIES_DIR)
{
	$ENTRIES_DIR.='/';
	if (!is_dir($ENTRIES_DIR)) failProcess($JOB_ID."002",'Unable to find directory '.$ENTRIES_DIR); 
	$UP_PDB=strtoupper($PDB);
	$PD_UP_DIR=$ENTRIES_DIR.substr($UP_PDB,1,2).'/';
	$PD_DIR=$PD_UP_DIR.$UP_PDB.'/';
	if (is_dir($PD_DIR) && is_file($PD_DIR.'_data'))
	{	
		
		$ENTRY=json_decode(file_get_contents($PD_DIR.'_data'),true);
		return $ENTRY;
	}
	return null;
}


function getIniPDBEntry(&$ENTRY)
{

	global $IGNORE_TERM;
	$PDB=&$ENTRY['INIT']['ENTRY_NAME'];
	$PD_DIR=&$ENTRY['DIR'];

	if ($ENTRY['PROCESS']['GET_STRUCTURE']=="OK" && checkFileExist($PD_DIR.'/'.$PDB.'_start.pdb'))return 'OK';
	

	/// Check if file exist in previous run
	$done=false;
		
	for ($attempt=0;$attempt<=5;++$attempt)
	{
		$query="wget --no-check-certificate -O ".$PD_DIR.'/'.$PDB.'_start.pdb https://files.rcsb.org/download/'.$PDB.".pdb &> ".$PD_DIR."/jobs/dl.log";
		system($query,$retval);
		if ($retval==8)
		{
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|getIniPDBEntry|Return code - PDB is not available for this entry";
			$ENTRY['PROCESS']['GET_STRUCTURE']="TERM";
			updateDataFile($ENTRY);
			return 'T1';
		}
		if (!checkFileExist($PD_DIR.'/'.$PDB.'_start.pdb')) sleep(5);
		else break;
	}
	
	if (!checkFileExist($PD_DIR.'/'.$PDB.'_start.pdb')) 
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|getIniPDBEntry|Unable to download file: Query ".$query;
		$ENTRY['PROCESS']['GET_STRUCTURE']="FAIL";
		updateDataFile($ENTRY);
		return 'E1';
	}
	exec('tail -1 '.$PD_DIR.'/'.$PDB.'_start.pdb',$out);
	if ($out[0]!='END')
	{

		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|getIniPDBEntry|Unexpected end of file ".$out[0];
		$ENTRY['PROCESS']['GET_STRUCTURE']="FAIL";
		updateDataFile($ENTRY);
		return 'E2';
	}

	$res=array();	
	exec('egrep "^OBSLTE" '.$PD_DIR.'/'.$PDB.'_start.pdb',$res);
	if (count($res)>0)
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|getIniPDBEntry|Entry is obsolete ".$res[0];
		$ENTRY['PROCESS']['GET_STRUCTURE']="TERM";
		$ENTRY['PROCESS']['KILL']=true;
		updateDataFile($ENTRY);

		return 'T2';
	}
	$done=true;
	

	if (!$done)
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|getIniPDBEntry|Process not done";
		 $ENTRY['PROCESS']['GET_STRUCTURE']="FAIL";
		 updateDataFile($ENTRY);
		return 'E3';
	}
	if (!validatePDBFile($PD_DIR.'/'.$PDB.'_start.pdb'))
	{
 		 $ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|getIniPDBEntry|UNABLE TO FIND C/CA/CB\n";
		 $ENTRY['PROCESS']['GET_STRUCTURE']="TERM";
		 updateDataFile($ENTRY);
		return 'T3';
	}
	$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|getIniPDBEntry|SUCCESS";
	$ENTRY['PROCESS']['GET_STRUCTURE']="OK";
	updateDataFile($ENTRY);
	return 'OK';

	
}


function validatePDBFile($pfile)
{

	exec('egrep "^ATOM  " '.$pfile.' | tr -s " " | cut -d" " -f3,4,5,6,12 | sort | uniq | egrep "^C"| cut -d" " -f1 | sort | uniq -c',$res);

	$ATOM=array();
	foreach ($res as $l)
	{
		$tab=array_values(array_filter(explode(" ",$l)));

		$ATOM[]=$tab[1];
	}
	if (!in_array("C",$ATOM) ||
	    !in_array("CA",$ATOM)||
	    !in_array("CB",$ATOM)) return false;

	return true;

}




function prepPDBEntry(&$ENTRY)
{
global $TG_DIR;
global $GLB_VAR;
global $MAX_MOE_TIME;
	$PDB=&$ENTRY['INIT']['ENTRY_NAME'];
	$PD_DIR=&$ENTRY['DIR'];
	$PREP_FILE=$PD_DIR.'/'.$PDB.'_mprep.pdb';

	if ($ENTRY['PROCESS']['PDB_PREP']=="OK")
	{

		if (!checkFileExist($PREP_FILE))
		{
			 $ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PREP_FILE|STATUS OK BUT NO FILE FOUND AT ".$PREP_FILE;
			$ENTRY['PROCESS']['PDB_PREP']="ISSUE";
			return 'E1';
		}
		exec('wc -l '.$PREP_FILE,$r);
		if (explode(" ",$r[0])[0]==3)
		{
			 $ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PREP_FILE|FILE EXIST BUT HAS ONLY 3 LINES ".$PREP_FILE;
			$ENTRY['PROCESS']['PDB_PREP']="ISSUE";
			return 'E2';
		}
		unset($r);
		return "OK";
	}
	if ($ENTRY['PROCESS']['GET_STRUCTURE']!="OK")
	{
	 	$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PREP_FILE|CALL PREP ENTRY WHEN GET_STRUCTURE STATUS IS NOT OK";
		$ENTRY['PROCESS']['PDB_PREP']="ISSUE";
		return 'E3';
	}
	if ($ENTRY['PROCESS']['PDB_PREP']=="TERM"){
		if ($IGNORE_TERM)	cleanData($ENTRY,"PDB_PREP");
		else {echo "PREP PDB ENTRY WITH PDB_PREP TERM";exit;}

	}

	$done=false;

	if (!$done)
	{
		if ($GLOBALS['DISABLE_MOE']==true)
		{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PREP_FILE|MOE DISABLED";
		return 'E4';
		}
//$MAX_MOE_TIME
$MAX_MOE_TIME=12*60*60;
		chdir($PD_DIR);
	//	echo "timeout ".$MAX_MOE_TIME."s moebatch -licwait -load ".$TG_DIR.'/'.$GLB_VAR['TOOL']['MOE_PARAMS']." -exec \"batch_pdb_prep_lite['".$PDB."_start.pdb']\" &> ".$PD_DIR."/jobs/prep.log\n";
		exec("timeout ".$MAX_MOE_TIME."s /moe/bin/moebatch -licwait -load ".$TG_DIR.'/'.$GLB_VAR['TOOL']['MOE_PARAMS']." -exec \"batch_pdb_prep_lite['".$PDB."_start.pdb']\" &> ".$PD_DIR."/jobs/prep.log",$res);
//print_r($res);

		
		$OUT_PATH=$PD_DIR.'/'.$PDB.'_start_mprep.pdb';
		if (!checkFileExist($OUT_PATH))
		{
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PREP_FILE|NO FILE";
			$ENTRY['PROCESS']['PDB_PREP']="FAIL";
			return 'E5';
		}
		system("mv ".$OUT_PATH.' '.$PD_DIR.'/'.$PDB.'_mprep.pdb');
		if (!checkFileExist($PD_DIR.'/'.$PDB.'_mprep.pdb'))
		{
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PREP_FILE|UNABLE TO MOVE ".$PD_DIR.'/'.$PDB.'_mprep.pdb';
			$ENTRY['PROCESS']['PDB_PREP']="FAIL";
			return 'E6';
		}
		$done=true;

	}
	


	if (!$done)
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PREP_FILE|Process not done";
		$ENTRY['PROCESS']['PDB_PREP']="FAIL";
		return 'E7';
	}
	if (!validatePDBFile($PD_DIR.'/'.$PDB.'_start.pdb'))
	{
			 $ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PREP_FILE|UNABLE TO FIND C/CA/CB\n";
		 $ENTRY['PROCESS']['PDB_PREP']="TERM";
		return 'T1';
	}
	$ENTRY['FILES']['MPREP']=$PREP_FILE;
	$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PREP_FILE|SUCCESS";
	$ENTRY['PROCESS']['PDB_PREP']="OK";
	return 'OK';


}


function processEntry(&$ENTRY)
{
	$ORDER=array('DIR','GET_STRUCTURE','PDB_PREP','PDB_SEP','BLASTP');//,'VOLSITE','CLUSTERING');
	
	
	cleanData($ENTRY,'BLASTP');
	
	$ENTRY['PROCESS']['BLASTP']='';
	foreach ($ORDER as $JOB)
	{
		if ($ENTRY['PROCESS'][$JOB]=='OK')continue;
		if ($ENTRY['PROCESS'][$JOB]=='TERM')break;
		echo $JOB."\t".$ENTRY['PROCESS'][$JOB]."\n";
		switch ($JOB)
		{
			case 'PDB_PREP':
				$RET_CODE=prepPDBEntry($ENTRY);
				
				$ENTRY['CODE']['PDB_PREP']=$RET_CODE;
				updateDataFile($ENTRY);
					
				break;
			case 'PDB_SEP':
				$RET_CODE=splitEntry($ENTRY);
				
				$ENTRY['CODE']['PDB_SEP']=$RET_CODE;
				updateDataFile($ENTRY);
				
				break;
			case 'BLASTP':
			
				$RET_CODE=runBLASTP($ENTRY);
				
				$ENTRY['CODE']['BLASTP']=$RET_CODE;
				
				updateDataFile($ENTRY);
				
				break;
		}
	}

}



function splitEntry(&$ENTRY)
{
	global $TG_DIR;
	global $GLB_VAR;
	
global $IGNORE_TERM;

	$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|START";
	$PDB=&$ENTRY['INIT']['ENTRY_NAME'];
	$PD_DIR=&$ENTRY['DIR'];
	$PREP_FILE=$PD_DIR.'/'.$PDB.'_mprep.pdb';
	if ($ENTRY['PROCESS']['PDB_SEP']=="OK")
	{
		
		///TODO CHECK EACH FILE EXISTENCE
		foreach ($ENTRY['FILES']['STRUCTURE'] as $F)
		{
			if (checkFileExist($F['FNAME']))continue;
			
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|UNABLE TO FIND ".$F['FNAME'];
			$ENTRY['PROCESS']['PDB_SEP']="ISSUE";
			return 'E1';
			
		}
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|PROCESS PREVIOUSLY ENDED SUCCESSFULLY";
		return 'OK';
	}
	
	if ($ENTRY['PROCESS']['PDB_PREP']!="OK")
	{
	 	$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|CALL PDB_SEP WHEN PDB_PREP STATUS IS NOT OK";
		$ENTRY['PROCESS']['PDB_SEP']="ISSUE";
		return 'E2';
	}
	if ($ENTRY['PROCESS']['PDB_SEP']=="TERM")
	{
		if ($IGNORE_TERM)	cleanData($ENTRY,"PDB_SEP");
		else return 'T1';
			
	}
	exec('wc -l '.$PREP_FILE,$r);
	
	if ($r[0]==3)
	{
		 $ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|PREP FILE EXIST BUT HAS ONLY 3 LINES ".$PREP_FILE;
		$ENTRY['PROCESS']['PDB_PREP']="ISSUE";
		return 'E3';
	}
	unset($r);
	chdir($PD_DIR);
	$fp=fopen($PDB."_start.pdb",'r');
	$fpo=fopen($PDB."_complete.pdb",'w');
	if (!$fp)
	{
		 $ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|UNABLE TO OPEN START FILE";
		$ENTRY['PROCESS']['PDB_PREP']="ISSUE";
		return 'E4';
	}
	if (!$fpo)
	{
		 $ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|UNABLE TO OPEN COMPLETE FILE";
		$ENTRY['PROCESS']['PDB_PREP']="ISSUE";
		return 'E5';
	}
	while(!feof($fp))
	{
		$line=stream_get_line($fp,200,"\n");
		if (substr($line,0,4)=="ATOM")break;
		fputs($fpo,$line."\n");
	}
	fclose($fp);
	$fp=fopen($PDB."_mprep.pdb",'r');
	if (!$fp)
	{
		 $ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|UNABLE TO OPEN MPREP FILE";
		$ENTRY['PROCESS']['PDB_PREP']="ISSUE";
		return 'E6';
	}
	$valid=false;
	while(!feof($fp))
	{
		$line=stream_get_line($fp,200,"\n");
		if (substr($line,0,4)=="ATOM")$valid=true;
		if (!$valid)continue;
		fputs($fpo,$line."\n");
	}
	fclose($fpo);
	fclose($fp);	
	$COMPLETE_FILE=$PD_DIR.'/'.$PDB.'_complete.pdb';
	if (!chdir($PD_DIR.'/STRUCTURE/'))
	{
		 $ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|UNABLE TO ACCESS STRUCTURE DIRECTORY";
		$ENTRY['PROCESS']['PDB_PREP']="ISSUE";
		return 'E7';
	
	}
	$INTERNAL="";
	$res=array();
	
	
	exec($TG_DIR.'/'.$GLB_VAR['TOOL']['PDBSEP']." -oXML ".$PDB.".xml  -sc -ppi -trim ".$COMPLETE_FILE." ".$PDB." &> CONTENT ",$res,$return);
	
	system(" egrep '^(FILE|COMB)' CONTENT > FILES ");
	if ($return!=0)
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|WRONG RETURN CODE ".$return;
		$ENTRY['PROCESS']['PDB_SEP']="ISSUE";
		return 'E8';
	}
	if (!checkFileExist('CONTENT'))
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|NO CONTENT FILE";
		$ENTRY['PROCESS']['PDB_SEP']="ISSUE";
		return 'E9';
	}
	if (!checkFileExist('FILES'))
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|NO FILES LIST FILE";
		$ENTRY['PROCESS']['PDB_SEP']="ISSUE";
		return 'E10';
	}
	$list_raw=array();
	exec("cat FILES",$list_raw);
$N_AA=0;
	foreach ($list_raw as $line)
	{
		if (substr($line,0,4)=='FILE')
		{
			$tab=explode("\t",$line);
			if ($tab[3]=="/")continue;
			
			if (isset($ENTRY['FILES']['STRUCTURE'][$tab[2]]))
			{
				$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|DUPLICATED NAME\t".$tab[2];
				$ENTRY['PROCESS']['PDB_SEP']="TERM";
				return 'T2';

			}
			if (isset($ENTRY['JOBS'][$tab[2]]))
			{
				$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|DUPLICATED NAME\t".$tab[2];
				$ENTRY['PROCESS']['PDB_SEP']="TERM";
				return 'T3';

			}
			$File_path=$PD_DIR.'/STRUCTURE/'.$tab[3].'.pdb';
			if (!checkFileExist($File_path))
			{
				$File_path=$PD_DIR.'/STRUCTURE/'.$tab[3].'.mol2';
				if (!checkFileExist($File_path))
				{
					$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|MISSING FILE \t".$PD_DIR.'/STRUCTURE/'.$tab[3].'.pdb and .mol2';
					$ENTRY['PROCESS']['PDB_SEP']="TERM";
					return 'T4';
				}
			}

			switch ($tab[1])
			{
				case 'SINGLE_CHAIN':
					
					$ENTRY['FILES']['STRUCTURE'][$tab[2]]=array('TYPE'=>$tab[1],'FNAME'=>$File_path,'PT'=>$tab[4]);
					$ENTRY['JOBS'][$tab[2]]=array(
							'INPUT'=>array('PROT'=>$tab[2],
 								       'LIG'=>$tab[2]),
							'VOLSITE'=>array('DIR'=>'',
									 'STATUS'=>''),
							'SITEMAP'=>array('DIR'=>'',
									 'STATUS'=>''));
					break;
				case 'RECEPTOR':
					$ENTRY['FILES']['STRUCTURE']['RECEPTOR']=array('FNAME'=>$File_path,'TYPE'=>'RECEPTOR');
					$ENTRY['JOBS']['RECEPTOR']=array(
							'INPUT'=>array('PROT'=>'RECEPTOR',
 								       'LIG'=>'RECEPTOR'),
							'VOLSITE'=>array('DIR'=>'',
									 'STATUS'=>''),
							'SITEMAP'=>array('DIR'=>'',
									 'STATUS'=>''));

					break;
				case 'PPI':
					$TT=explode("_",$tab[2]);sort($TT);$tab[2]=implode("_",$TT);
					$ENTRY['FILES']['STRUCTURE'][$tab[2]]=array('TYPE'=>'MULTIMER','FNAME'=>$File_path,'ARCH_TYPE'=>count($TT));
					$ENTRY['JOBS'][$tab[2]]=array(
							'INPUT'=>array('PROT'=>$tab[2],
 								       'LIG'=>$tab[2]),
							'VOLSITE'=>array('DIR'=>'',
									 'STATUS'=>''),
							'SITEMAP'=>array('DIR'=>'',
									 'STATUS'=>''));
					break;
				case 'LIGAND':
if ($tab[4]=="SER"||$tab[4]=="ALA"||$tab[4]=="GLY"||$tab[4]=="ARG"||$tab[4]=="ASN"||$tab[4]=="ASP"||$tab[4]=="CYS"||$tab[4]=="GLU"||$tab[4]=="GLN"||$tab[4]=="HIS"||$tab[4]=="ILE"||$tab[4]=="LEU"||$tab[4]=="LYS"||$tab[4]=="MET"||$tab[4]=="PHE"||$tab[4]=="PRO"||$tab[4]=="SER"||$tab[4]=="THR"||$tab[4]=="TRP"||$tab[4]=="TYR"||$tab[4]=="VAL")
{
$N_AA++;
						
}
				case 'COFACTOR':
					$ENTRY['FILES']['STRUCTURE'][$tab[2]]=array('TYPE'=>$tab[1],'FNAME'=>$File_path,'LIG_NAME'=>$tab[4]);
					break;
				case 'COF_PROT':
					$ENTRY['FILES']['STRUCTURE'][$tab[2]]=array('TYPE'=>$tab[1],'FNAME'=>$File_path);
					break;
			}
		}
		else if (substr($line,0,4)=="COMB")
		{
			$tab=explode("\t",$line);
			if (strpos($tab[1],"|")!==false){		$TT=explode("|",$tab[1]);sort($TT);$tab[1]=implode("|",$TT);}
			if (strpos($tab[2],"|")!==false){		$TT=explode("|",$tab[2]);sort($TT);$tab[2]=implode("|",$TT);}
			if (isset($ENTRY['JOBS'][$tab[1]."_".$tab[2]]))
			{
				$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|DUPLICATED JOB NAME\t".$tab[1]."_".$tab[2];
				$ENTRY['PROCESS']['PDB_SEP']="TERM";
				return 'T5';

			}				
			$ENTRY['JOBS'][$tab[1]."_".$tab[2]]=array(
							'INPUT'=>array('PROT'=>$tab[2],
 								       'LIG'=>$tab[1]),
							'VOLSITE'=>array('DIR'=>'',
									 'STATUS'=>''),
							'SITEMAP'=>array('DIR'=>'',
									 'STATUS'=>''));

		}

	}
	if ($N_AA > 2)
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|AMINO ACID AS LIGAND";
		$ENTRY['PROCESS']['PDB_SEP']="TERM";
		return 'T6';
	}
	
	$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|PDB_SEP|SUCCESS";
	$ENTRY['PROCESS']['PDB_SEP']="OK";
	return "OK";
}

function 		getDBREF(&$ENTRY)
{
	global $TG_DIR;
	global $GLB_VAR;
	$UNI_DIR=$TG_DIR.'/'.$GLB_VAR['PRD_DIR'].'/UNIPROT/';
	
	$PDB=&$ENTRY['INIT']['ENTRY_NAME'];
	$PD_DIR=&$ENTRY['DIR'];

	$fp=fopen('../'.$PDB."_start.pdb",'r');
	if (!$fp)
	{
	$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO OPEN INI PDB FILE FILE";
	$ENTRY['PROCESS']['BLASTP']="ISSUE";
	return 'E15';
	}
	$LIST_AC=array();
	$DATA=array();
	$FOUND_DBREF=false;
	while(!feof($fp))
	{
	$line=stream_get_line($fp,200,"\n");
	//echo $line."\n";
	if (substr($line,0,5)=="DBREF")
	{
		$FOUND_DBREF=true;
	$db_n=substr($line,26,5);
	

	if ($db_n!='UNP  ')continue;
	$db_V=substR($line,42,12);
	$chain = substr($line,12,1);
	$ac=trim(substR($line,33,8));
	$unip=trim(substr($line,42,12));
	$DATA[$chain][$ac.'|'.$unip]=array(trim(substr($line,14,4)),trim(substr($line,20,4)))	;
	

	}else if ($FOUND_DBREF)break;

	}
	fclose($fp);
	return $DATA;
}


function loadSEQRES(&$ENTRY,$PFILE)
{
	///COMPND    MOL_ID: 1;                                                                                               
///COMPND   3 CHAIN: A;                                                            
///COMPND   7 MOL_ID: 2;                                                           
///COMPND   9 CHAIN: B;                                                            
///COMPND  12 MOL_ID: 3;                                                           
///COMPND  14 CHAIN: C;                                                            
///SOURCE    MOL_ID: 1;                                                            
///SOURCE   2 ORGANISM_SCIENTIFIC: GALLUS GALLUS;                                  
///SOURCE   3 ORGANISM_COMMON: BANTAM,CHICKENS;                                    
///SOURCE   4 ORGANISM_TAXID: 9031;                                                
///SOURCE   5 GENE: LYZ;                                                           
///SOURCE   6 MOL_ID: 2;                                                           
///SOURCE   9 ORGANISM_TAXID: 10090;                                               
///Each species must be associated to its corresponding chain. so we need to read the COMPND to get mapping between MOLD_ID and CHAIN(s)
///Then we read the source to get the mapping between MOL_ID and TAXID
	$fp=fopen($PFILE,'r');if (!$fp)return 'E9';
	$CURR_MOL_ID=0;$MAP_MOLID=array();

$MAP_AA=array('ALA'=>'A','ARG'=>'R','ASN'=>'N','ASP'=>'D','CYS'=>'C','GLN'=>'Q',
'GLU'=>'E','GLY'=>'G','HIS'=>'H','ILE'=>'I','LEU'=>'L','LYS'=>'K','TRP'=>'W',
'MET'=>'M','PHE'=>'F','PRO'=>'P','SER'=>'S','THR'=>'T','TYR'=>'Y','VAL'=>'V');
	$STD_ARRAY=array('SEQRES'=>'','RESULTS'=>array(),'STATUS'=>'','PRIMARY'=>array(),'SPECIES'=>array());
	while(!feof($fp))
	{
		$line=stream_get_line($fp,120,"\n");
		if (substr($line,0,6)=='SEQRES'){
			$CHAIN=substr($line,11,1);
			if (!isset($ENTRY['BLASTP'][$CHAIN]))$ENTRY['BLASTP'][$CHAIN]=$STD_ARRAY;
			$tab=array_values(array_filter(explode(" ",substr($line,19))));

			foreach ($tab as $AA)
			{
				$K=&$MAP_AA[$AA];
				if (is_null($K)) $ENTRY['BLASTP'][$CHAIN]['SEQRES'].='X';
				else $ENTRY['BLASTP'][$CHAIN]['SEQRES'].=$K;
			}
			$ENTRY['BLASTP'][$CHAIN]['STATUS']='SEQRES';
			
		}
		else if (substr($line,0,6)=="COMPND")
		{
//012345678901234567890
//COMPND    MOL_ID: 1; 
//echo "CURR_MOL_ID:".$CURR_MOL_ID."\n";
			if (substr($line,10,6)=="MOL_ID") 
			{
				$POS=strpos($line,';');
				if ($POS!==false) $CURR_MOL_ID=trim(substr($line,17,$POS-17));
				else $CURR_MOL_ID=trim(substr($line,17));

			}
			else if (substr($line,11,6)=="MOL_ID") 
			{
				$POS=strpos($line,';');
				if ($POS!==false) $CURR_MOL_ID=trim(substr($line,18,$POS-18));
				else $CURR_MOL_ID=trim(substr($line,18));

			}
			else if (substr($line,11,5)=="CHAIN") 
			{
				$POS=strpos($line,';');
				if ($POS!==false) $CURR_CHAINS=trim(substr($line,17,$POS-17));
				else $CURR_CHAINS=trim(substr($line,17));
				
				if (strpos($CURR_CHAINS,',')!==false)
				{
					$tab=explode(",",str_replace(' ','',$CURR_CHAINS));
					$MAP_MOLID[$CURR_MOL_ID]=$tab;
				}else $MAP_MOLID[$CURR_MOL_ID][]=$CURR_CHAINS;
			}

		}
		else if (substr($line,0,6)=="SOURCE")
		{
			if (($pmol_id=strpos($line,'MOL_ID'))!==false)
			{
				$POS=strpos($line,';');$pmol_id+=7;
				if ($POS!==false) $CURR_MOL_ID=trim(substr($line,$pmol_id,$POS-$pmol_id));
				else $CURR_MOL_ID=trim(substr($line,17));

			}
			
			if (strpos($line,'ORGANISM_TAXID')!==false)
			{
			$line=str_replace(' ','',$line);

			$TMP=explode(",",substr($line,strpos($line,':')+1,substr($line,-1)==";"?-1:strlen($line)));
			
			
			foreach ($TMP as $TAX_ID)
			{
// 				print_r($MAP_MOLID);
// 				echo "CURR_MOL_ID:\n";
// 				print_r($CURR_MOL_ID);;
// echo "\n";
				if (!isset($MAP_MOLID[$CURR_MOL_ID]))
				{
					fclose($fp);
					$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO FIND MAP MOL ID ".$CURR_MOL_ID;
					$ENTRY['PROCESS']['BLASTP']="ISSUE";
					return 'E10';
				}

				foreach ($MAP_MOLID[$CURR_MOL_ID] as $CH){
					if (!isset($ENTRY['BLASTP'][$CH]))$ENTRY['BLASTP'][$CH]=$STD_ARRAY;
					
					$resTX=runQuery(" select DISTINCT T2.tax_id 
					FROM taxon T, taxon_tree th1, taxon_tree th2, taxon T2 
					WHERE T.taxon_Id=Th1.taxon_id AND th2.taxon_id = t2.taxon_id
					AND T.tax_id='".$TAX_ID."' 
					AND Th1.LEVEL_LEFT <= Th2.LEVEL_LEFT AND Th1.LEVEL_RIGHT >=Th2.LEVEL_RIGHT");
									if ($resTX===false) return 'E11';
									foreach ($resTX as $T)$LIST_UN[]=$T['tax_id'];
					//$LIST_UN=getUniprotTaxon($TAX_ID);;

					$ENTRY['BLASTP'][$CH]['SPECIES'][]=array($TAX_ID,$LIST_UN);//$TAX_MAP[$TAX_ID]
				
				}

			}
			}
		}
		
	}
	fclose($fp);
	return true;
}


function loadXraySeq(&$ENTRY,$path)
{
	
	if (!checkFileExist($path))return 'E12';
	$content=file_get_contents($path);
	$xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);
	$json = json_encode($xml);
	$scontent = json_decode($json,TRUE);
	if ($scontent==null)return 'E13';
	$XRES_INFO=array();
	$MAP_AA=array('ALA'=>'A','ARG'=>'R','ASN'=>'N','ASP'=>'D','CYS'=>'C','GLN'=>'Q',
				'GLU'=>'E','GLY'=>'G','HIS'=>'H','ILE'=>'I','LEU'=>'L','LYS'=>'K','TRP'=>'W',
				'MET'=>'M','PHE'=>'F','PRO'=>'P','SER'=>'S','THR'=>'T','TYR'=>'Y','VAL'=>'V');

			  $ALL_CHAINS=null;
			  if (isset($scontent['Chains']['Chain'][0]))$ALL_CHAINS=&$scontent['Chains']['Chain'];
			  else $ALL_CHAINS=&$scontent['Chains'];
	foreach ($ALL_CHAINS as &$CHAIN_INFO)
	{
		
		$CHAIN_NAME=$CHAIN_INFO['@attributes']['name'];
		$ALL_RESIDUES=null;
		if (isset($CHAIN_INFO['Residues']['Residue'][0]))$ALL_RESIDUES=&$CHAIN_INFO['Residues']['Residue'];
		else $ALL_RESIDUES=&$CHAIN_INFO['Residues'];

		foreach ($ALL_RESIDUES as &$RESIDUE)
		{
			$RNAME=$RESIDUE['@attributes']['rname'];
			if ($RNAME=='HOH'|| !isset($RESIDUE['@attributes']['cacoo']))continue;
			
			$XRES_INFO[$CHAIN_NAME][$RESIDUE['@attributes']['rid']]=array('NAME'=>$RNAME);
		}
	}

	foreach ($XRES_INFO as $CHAIN=>&$LIST_RES)
	{
		$fp=fopen($ENTRY['DIR'].'/BLASTP/XR_CHAIN_'.$CHAIN.'.fasta','w');if (!$fp)return 'E14';
		fputs($fp,'>'.$CHAIN."\n");
		$STR='';
		
		foreach ($LIST_RES as $RES)$STR.=(isset($MAP_AA[$RES['NAME']]))?$MAP_AA[$RES['NAME']]:'X';
		$XR_SEQ[$CHAIN]=$STR;
		
		fputs($fp,implode("\n",str_split($STR,90)));
		fclose($fp);
	}

	return array($XRES_INFO,$XR_SEQ);

}

function runBLASTP(&$ENTRY)
{
	
	global $TG_DIR;
	global $GLB_VAR;
	$UNI_DIR=$TG_DIR.'/'.$GLB_VAR['PRD_DIR'].'/UNIPROT/';
	$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|START";
	$PDB=&$ENTRY['INIT']['ENTRY_NAME'];
	$PD_DIR=&$ENTRY['DIR'];

	global $IGNORE_TERM;
	$STD_ARRAY=array('SEQRES'=>'','RESULTS'=>array(),'STATUS'=>'','PRIMARY'=>array(),'SPECIES'=>array());
	$STD_ARR_RES=array('IDEN'=>-1,'SIM'=>-1,'IDEN_COM'=>-1,'SIM_COM'=>-1,'UNIPROT'=>'','ALIGNMENT'=>array());
	global $SCRIPT_LOC;
	/// Those are the two files needed for the job. The first one contains all sequences from uniprot while the second contains a maaping betwee nTAX_ID and UNIPROT SPECIES name
	$UNI_FILES=array('SPROT'=>$UNI_DIR.'/SPROT/uniprot_all.fasta',
					 'PROTEOME'=>$UNI_DIR.'/PROTEOMES/ALL_SEQ.txt',
					 'TREMBL'=>$UNI_DIR.'/TREMBL/uniprot_trembl.fasta');
					 echo "IN\n";

	$UNI_TAXON=preloadTaxon($TG_DIR);
	if ($UNI_TAXON===false)return 'E0';
	echo "IN\n";
//$SEQ_ALIGN=$TG_DIR.


	// foreach ($UNI_FILES as $FILE)
	// if (!checkFileExist($FILE))
	// {
	// 	$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO FIND ".$FILE;
	// 	$ENTRY['PROCESS']['BLASTP']="ISSUE";
	// 	return 'E1';
	// }



	if ($ENTRY['PROCESS']['BLASTP']=="OK")
	{
		
		/// CHECK IF EACH SINGLE CHAIN AS AN ENTRY IN BLASTP
		foreach ($ENTRY['FILES']['STRUCTURE'] as $CHAIN=>$CHAIN_INFO)
		{
			if ($CHAIN_INFO['TYPE']!='SINGLE_CHAIN')continue;
			if (!isset($ENTRY['BLASTP'][$CHAIN]))
			{
				$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO FIND ".$CHAIN." AS BLASTP ENTRY";
				$ENTRY['PROCESS']['BLASTP']="ISSUE";
				return 'E2';
			}
			$DT_BP=$ENTRY['BLASTP'][$CHAIN];
			if (!isset($DT_BP['SEQRES']) ||$DT_BP['SEQRES']=='')
			{
				$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO FIND SEQRES FOR ".$CHAIN." IN BLASTP ENTRY";
				$ENTRY['PROCESS']['BLASTP']="ISSUE";
				return 'E3';
			}
			if (!isset($DT_BP['STATUS']) ||$DT_BP['STATUS']=='')
			{
				$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO FIND STATUS FOR ".$CHAIN." IN BLASTP ENTRY";
				$ENTRY['PROCESS']['BLASTP']="ISSUE";
				return 'E4';
			}
			if ($DT_BP['STATUS']=='OK' && count($DT_BP['RESULTS'])==0)
			{
				$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO FIND RESULTS WHEN STATUS OK FOR ".$CHAIN." IN BLASTP ENTRY";
				$ENTRY['PROCESS']['BLASTP']="ISSUE";
				return 'E5';
			}	

		}
		
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|PROCESS PREVIOUSLY ENDED SUCCESSFULLY";
		return 'OK';
	}
	if ($ENTRY['PROCESS']['DB_INSERT']!="OK")
	{
	 	$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|CALL BLASTP WHEN PDB_SEP STATUS IS NOT OK";
		$ENTRY['PROCESS']['BLASTP']="ISSUE";
		return 'E6';
	}
	if ($ENTRY['PROCESS']['BLASTP']=="TERM")
	{
			if ($IGNORE_TERM)	cleanData($ENTRY,"BLASTP");
		else return 'T1';

	}

	$MAP_AA=array('ALA'=>'A','ARG'=>'R','ASN'=>'N','ASP'=>'D','CYS'=>'C','GLN'=>'Q',
		      'GLU'=>'E','GLY'=>'G','HIS'=>'H','ILE'=>'I','LEU'=>'L','LYS'=>'K','TRP'=>'W',
		      'MET'=>'M','PHE'=>'F','PRO'=>'P','SER'=>'S','THR'=>'T','TYR'=>'Y','VAL'=>'V');

	$BLASTP_DIR=$PD_DIR.'/BLASTP/';	
	if (!is_dir($BLASTP_DIR) && !mkdir($BLASTP_DIR))
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO CREATE DIRECTORY BLASTP";
		$ENTRY['PROCESS']['BLASTP']="ISSUE";
		return 'E7';
	}
	chdir($BLASTP_DIR);
	if (!checkFileExist($PD_DIR.'/'.$PDB.'_start.pdb'))		
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO FIND INI PDB FILE";
		$ENTRY['PROCESS']['BLASTP']="ISSUE";
		return 'E8';
	}
	
	$JOB_SUCCESS=true;

	/// GETTING SEQRES FROM PDB AS WELL AS ORGANISM
	$ENTRY['BLASTP']=array();
	///E9->E11
	$SEQRES_RETURN_CODE=loadSEQRES($ENTRY,$ENTRY['DIR'].'/'.$PDB.'_start.pdb');
	if ($SEQRES_RETURN_CODE!=true)
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO GET SEQRES";
		$ENTRY['PROCESS']['BLASTP']="ISSUE";
		return $SEQRES_RETURN_CODE;
	}
	///E12->E14
	$TMP=loadXraySeq($ENTRY,$ENTRY['DIR'].'/STRUCTURE/'.$ENTRY['INIT']['ENTRY_NAME'].'.xml');
	if (!is_array($TMP))
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO GET XRAY SEQUENCES";
		$ENTRY['PROCESS']['BLASTP']="ISSUE";
		return $TMP;
	}
	$XRES_INFO=$TMP[0];
	$XR_SEQ=$TMP[1];
	//array($XRES_INFO,$XR_SEQ);
	
	///E15
	$DBREFS=getDBREF($ENTRY);
	if (!is_array($DBREFS))
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO GET DBREFS";
		$ENTRY['PROCESS']['BLASTP']="ISSUE";
		return $DBREFS;
	}
	
	$N_CHAIN_TO_PROCESS=0;
	foreach ($ENTRY['FILES']['STRUCTURE'] as &$P)
	{
		if ($P['TYPE']=='SINGLE_CHAIN')$N_CHAIN_TO_PROCESS++;
	}
	
	unset($TAX_MAP);
	$LIST_UNIP=array();

	/// STEP 1: BLAST EACH SEQRES AGAINST UNIPROT SEQUENCES:
	foreach ($ENTRY['BLASTP'] as $CHAIN=>$BP_CHAIN)
	{
		
		if ($CHAIN=='SPECIES')continue;
		$CH_FILE=$PD_DIR.'/BLASTP/'.$CHAIN.'.fasta';
		
		if (!isset($BP_CHAIN['SEQRES']))
		{
			$ENTRY['BLASTP'][$CHAIN]['STATUS']='NO_SEQRES';
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|NO SEQ RES FOR CHAIN ".$CHAIN;
			$ENTRY['PROCESS']['BLASTP']="TERM";
			return 'T2';
		}
		
	//	print_r($BP_CHAIN['SEQRES']);
		if (substr_count($BP_CHAIN['SEQRES'],'X')==strlen($BP_CHAIN['SEQRES']))
		{
			$ENTRY['BLASTP'][$CHAIN]['STATUS']='NO_PROT';
			continue;
		}

		if (strlen($BP_CHAIN['SEQRES'])<10 || 
		    (strlen($BP_CHAIN['SEQRES'])<30 && 
		     substr_count($BP_CHAIN['SEQRES'],'X')<0.3*strlen($BP_CHAIN['SEQRES'])))
		{
			
			$ENTRY['BLASTP'][$CHAIN]['STATUS']='PEPTIDE';
			continue;
		}
		
		/// Put the sequence in a file:
		$fp=fopen($CH_FILE,'w');
		if (!$fp)
		{
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO OPEN ".$CH_FILE;
			$ENTRY['PROCESS']['BLASTP']="ISSUE";
			return 'E16';
		}
		fputs($fp,'>'.$PDB.'|'.$CHAIN."\n");
		fputs($fp,$BP_CHAIN['SEQRES']."\n");
		fclose($fp);

		//ALIGN 	SEQRES TO PDB
		$out=array();
		exec($TG_DIR.'/'.$GLB_VAR['TOOL']['SEQALIGN'].' -all  -i -rn '.$CHAIN.' -cn '.$CHAIN.' '.$BP_CHAIN['SEQRES'].' '.$XR_SEQ[$CHAIN],$out,$return_code);
		if ($return_code!=0)
		{
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO FIND RESULTS FOR ".$BP_CHAIN.' '.$RES['UNIPROT'];
			$ENTRY['PROCESS']['BLASTP']="ISSUE";
			return 'E17';
		}
			if (count($out)!=5)
			{
				$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO FIND RESULTS FOR ".$BP_CHAIN.' '.$RES['UNIPROT'];
				$ENTRY['PROCESS']['BLASTP']="ISSUE";
				return 'E18';
			}
			$ENTRY['BLASTP'][$CHAIN]['SEQ_RES_ALIGN']['ALIGNMENT']=array('SEQRES'=>$out[1],'XRAY'=>$out[3]);
			$tab=explode("\t",$out[4]);

			$ENTRY['BLASTP'][$CHAIN]['SEQ_RES_ALIGN']['IDEN']=$tab[0];	
			$ENTRY['BLASTP'][$CHAIN]['SEQ_RES_ALIGN']['SIM']=$tab[1];
			$ENTRY['BLASTP'][$CHAIN]['SEQ_RES_ALIGN']['IDEN_COM']=$tab[2];
			$ENTRY['BLASTP'][$CHAIN]['SEQ_RES_ALIGN']['SIM_COM']=$tab[3];

		if (isset($DBREFS[$CHAIN]))
		{
			$HAS_EXACT=false;$N_RES=0;
			$N_VALID_CHAIN=0;
			foreach ($DBREFS[$CHAIN] as $UNI_INFO=>$RANGE)
			{
				$DBREFS_NAMES=explode("|",$UNI_INFO);
				$DBREF_AC=$DBREFS_NAMES[0];
				$DBREF_UP=$DBREFS_NAMES[1];
				//$DBREF_SPECIES='';
				
				if (!isset(explode("_",$DBREF_UP)[1]))
				{
					$res=runQuery("SELECT PROT_IDENTIFIER FROM PROT_ENTRY PE, PROT_AC PA WHERE PA.PROT_ENTRY_ID = PE.PROT_ENTRY_ID AND AC='".$DBREF_UP."'");
					if (count($res)==0)continue;
					$DBREF_SPECIES=explode("_",$res[0]['PROT_IDENTIFIER']);

				}
				else $DBREF_SPECIES=explode("_",$DBREF_UP)[1];
				$EXACT=false;
				foreach ($BP_CHAIN['SPECIES'] as $K=>$TAX_DT)
				{
				
						if (!isset($UNI_TAXON[$DBREF_SPECIES]))
						{
							$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO FIND TAXON ".$DBREF_SPECIES.'  IN UNI_TAXON';
							$ENTRY['PROCESS']['BLASTP']="ISSUE";
							return 'E19';
						}
						if ($UNI_TAXON[$DBREF_SPECIES][1]=='EXACT' && in_array($UNI_TAXON[$DBREF_SPECIES][0],$TAX_DT[1])){
							
							$POS=$K;
							$EXACT=true;$HAS_EXACT=true;
							break;
						}else if ($UNI_TAXON[$DBREF_SPECIES][1]=='RANGE')
						{
							if (!isset($UNI_TAXON[$DBREF_SPECIES][2]))
							{
								

								$resTX=runQuery("select DISTINCT T2.tax_id 
								FROM taxon T, taxon_tree th1, taxon_tree th2, taxon T2 
								WHERE T.taxon_Id=Th1.taxon_id AND th2.taxon_id = t2.taxon_id
								AND T.tax_id='".$UNI_TAXON[$DBREF_SPECIES][0]."' 
								AND Th1.LEVEL_LEFT <= Th2.LEVEL_LEFT AND Th1.LEVEL_RIGHT >=Th2.LEVEL_RIGHT");
								if ($resTX==false)
								{
									$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO GET LIST OF TAXON ";
									$ENTRY['PROCESS']['BLASTP']="ISSUE";
									return 'E20';
								}
								foreach ($resTX as $T)$UNI_TAXON[$DBREF_SPECIES][2][]=$T['TAX_ID'];

							}
							foreach ($UNI_TAXON[$DBREF_SPECIES][2] as $UT)
							foreach ($TAX_DT[1] as $XT)
							{
								if ($XT!=$UT)continue;
								$POS=$K;
								$EXACT=true;$HAS_EXACT=true;
								break;
							}
						}
					}


				$T=$STD_ARR_RES;
				$T['EXACT']=$EXACT;
				$T['SPECIES']=$BP_CHAIN['SPECIES'][$K];
				
				$T['UNIPROT']=$DBREF_UP;
				$T['AC']=$DBREF_AC;
				foreach($ENTRY['BLASTP'][$CHAIN]['RESULTS'] as $TEST)
				{
					if ($TEST['UNIPROT']!=$T['UNIPROT'])continue;
					$POS=1;break;
				}
				if ($POS==1)continue;
				$N_VALID_CHAIN++;
				$ENTRY['BLASTP'][$CHAIN]['RESULTS'][]=$T;
				$LIST_UNIP[]=$T['UNIPROT'];
				$LIST_AC[$T['AC']]=$T['UNIPROT'];
				$N_RES++;

			}
			/// IF at least one of those results is an exact match to the UNIPROT, we remove the others
			if ($HAS_EXACT)
			{
				foreach ($ENTRY['BLASTP'][$CHAIN]['RESULTS'] as $K=>$N) if (!$N['EXACT']) unset($ENTRY['BLASTP'][$CHAIN]['RESULTS'][$K]);
			}
			foreach ($ENTRY['BLASTP'][$CHAIN]['RESULTS'] as $K=>$N)  unset($ENTRY['BLASTP'][$CHAIN]['RESULTS'][$K]['EXACT']);
	//echo "COUNT RES:".count($ENTRY['BLASTP'][$CHAIN]['RESULTS'])."\n";
	//print_r($ENTRY['BLASTP'][$CHAIN]['RESULTS']);
			$res=array();
			if ($N_RES>0){
				$ENTRY['BLASTP'][$CHAIN]['STATUS']='BLASTP';
				continue;
			}
				
			
		}

echo $CHAIN.' next step';
		
			//print_r($ENTRY['BLASTP'][$CHAIN]);

			foreach ($UNI_FILES as  $UNI_NAME=>$UNI_FILE)
			{
				echo $UNI_FILE."\n";
				if (!checkFileExist($UNI_FILE))continue;
				
		// BLAST RESULTS:
		$res=array();
		$OUT_FILE=$PD_DIR.'/BLASTP/BLAST_RES_'.$CHAIN.'_'.$UNI_NAME.'.csv';
		//echo "\n".'module load blast && blastp  -query '.$CH_FILE.' -db '.$UNI_FILE.' -outfmt "6 qseqid sseqid pident qlen slen length nident mismatch gapopen evalue bitscore" -out '.$OUT_FILE."\n";
		exec($TG_DIR.'/'.$GLB_VAR['TOOL']['BLASTP'].'  -query '.$CH_FILE.' -db '.$UNI_FILE.' -outfmt "6 qseqid sseqid pident qlen slen length nident mismatch gapopen evalue bitscore" -out '.$OUT_FILE,$res,$return_code);
		if ($return_code!=0)
		{
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|Unable to run Blast";
			$ENTRY['PROCESS']['BLASTP']="ISSUE";
			return 'E21';
		}

		if (count($res)!=0)
		{
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|ISSUE WHEN RUNNING BLASTP ".implode($res,"|");
			$ENTRY['PROCESS']['BLASTP']="ISSUE";
			return 'E22';
			

		}
		//echo count($res)."\n";
		//
		if (!is_file($OUT_FILE))
		{
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO FIND ".$OUT_FILE;
			$ENTRY['PROCESS']['BLASTP']="ISSUE";
			return 'E23';
		}
		/// GET RESULTS LIST:
		$res=array();
		exec('sort -r -n -k3 '.$OUT_FILE.'  ',$res);
		
		/// In some cases, the species provided will not match a Uniprot Species Tag (UST) (Second portion of the uniprot identifier).
		/// sometimes, the uniprot species tag is used to cover a wider range of taxons.
		/// Therefore, the previous steps retrieved all UST

		$found=false;$N_RES=0;
		$EXACT=false;$HAS_EXACT=false;
		
		foreach ($res as $N_LINE=>$line)
		{
			//echo $N_LINE."\t".count($ENTRY['BLASTP'][$CHAIN]['RESULTS'])."\n";
			if (count($ENTRY['BLASTP'][$CHAIN]['RESULTS'])>10)break;
			$tab=explode("\t",$line);

			$POS=-1;
			/// FILTER BY SPECIES:
			$EXACT=false;
			

			echo $line."\n";
			foreach ($BP_CHAIN['SPECIES'] as $K=>$TAX_DT)
			{
				/// If 
				//if (substr($TAX_DT[1],0,1)=='9'){$POS=$K;break;}
				$UNI_SPECIE=explode("_",$tab[1])[1];
//print_r($UNI_TAXON[$UNI_SPECIE]);
				if (!isset($UNI_TAXON[$UNI_SPECIE]))
				{
					$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|Unable to find ".$UNI_SPECIE." in taxon list";
					$ENTRY['PROCESS']['BLASTP']="ISSUE";
					return 'E24';
				}
				if ($UNI_TAXON[$UNI_SPECIE][1]=='EXACT' && in_array($UNI_TAXON[$UNI_SPECIE][0],$TAX_DT[1])){
					echo "IN\n";
					$POS=$K;
					$EXACT=true;$HAS_EXACT=true;
					break;
				}else if ($UNI_TAXON[$UNI_SPECIE][1]=='RANGE')
				{
					if (!isset($UNI_TAXON[$UNI_SPECIE][2]))
					{
						

						$resTX=runQuery("select DISTINCT T2.tax_id 
						FROM taxon T, taxon_tree th1, taxon_tree th2, taxon T2 
						WHERE T.taxon_Id=Th1.taxon_id AND th2.taxon_id = t2.taxon_id
						AND T.tax_id='".$UNI_TAXON[$UNI_SPECIE][0]."' 
						AND Th1.LEVEL_LEFT <= Th2.LEVEL_LEFT AND Th1.LEVEL_RIGHT >=Th2.LEVEL_RIGHT");
						if ($resTX==false)
						{
							$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|Unable to query taxon table to get child taxons for ".$UNI_TAXON[$UNI_SPECIE][0];
							$ENTRY['PROCESS']['BLASTP']="ISSUE";
							return 'E25';
						}
						foreach ($resTX as $T)$UNI_TAXON[$UNI_SPECIE][2][]=$T['TAX_ID'];

					}
					foreach ($UNI_TAXON[$UNI_SPECIE][2] as $UT)
					foreach ($TAX_DT[1] as $XT)
					{
						if ($XT!=$UT)continue;
						$POS=$K;
						$EXACT=true;$HAS_EXACT=true;
						break;
					}
					

				}
			}
echo $POS."\t".$HAS_EXACT."\t".$tab[2]."\n";
			if ($POS==-1 && ($HAS_EXACT || $tab[2]<94))continue;
			echo "NEXT\n";

			$POS=-1;
			$T=$STD_ARR_RES;
			$T['EXACT']=$EXACT;
			$T['SPECIES']=$BP_CHAIN['SPECIES'][$K];
			$US_NAME=explode("|",$tab[1]);
			$T['UNIPROT']=$US_NAME[2];
			$T['AC']=$US_NAME[1];
			foreach($ENTRY['BLASTP'][$CHAIN]['RESULTS'] as $TEST)
			{
				if ($TEST['UNIPROT']!=$T['UNIPROT'])continue;
				$POS=1;break;
			}
			if ($POS==1)continue;
			++$N_RES;

			$ENTRY['BLASTP'][$CHAIN]['RESULTS'][]=$T;
			$LIST_UNIP[]=$T['UNIPROT'];
			echo "END\n";
			if ($N_RES>10)break;
			//break;
		}
		
//echo "HAS EXACT:".$HAS_EXACT."\n";
		/// IF at least one of those results is an exact match to the UNIPROT, we remove the others
		if ($HAS_EXACT)
		{
			foreach ($ENTRY['BLASTP'][$CHAIN]['RESULTS'] as $K=>$N) if (!$N['EXACT']) unset($ENTRY['BLASTP'][$CHAIN]['RESULTS'][$K]);
		}
		foreach ($ENTRY['BLASTP'][$CHAIN]['RESULTS'] as $K=>$N)  unset($ENTRY['BLASTP'][$CHAIN]['RESULTS'][$K]['EXACT']);
//echo "COUNT RES:".count($ENTRY['BLASTP'][$CHAIN]['RESULTS'])."\n";
//print_r($ENTRY['BLASTP'][$CHAIN]['RESULTS']);
		$res=array();
		if ($N_RES>0){
			$ENTRY['BLASTP'][$CHAIN]['STATUS']='BLASTP';
			break;}
			
		}
			
	}
	echo "IN4\n";
	
	sort($LIST_UNIP); $LIST_UNIP=array_unique($LIST_UNIP);
//	print_R($ENTRY['BLASTP']);exit;

	
	


	/// GET UNIPROT SEQUENCES:

	$ST='';$AC_MAPS=array();
	$LIST_UNIP_FOUND=array();
	foreach ($LIST_UNIP as $K=>$UNIP){$ST.="'".$UNIP."',";$LIST_UNIP_FOUND[$UNIP]=false;}
	//print_R($LIST_UNIP);
	if ($ST!='')
	{
		
		$res=runQuery("SELECT prot_identifier,prot_seq_pos_id,position,letter
		FROM prot_seq US, prot_seq_pos USP, prot_entry UE 
		WHERE US.prot_seq_id = USP.prot_seq_id AND UE.prot_entry_id = US.prot_entry_id 
		AND prot_identifier IN (".substr($ST,0,-1).") AND is_primary='T' ORDER BY prot_identifier,position ASC");
		if ($res===false)
		{
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|Unable to get protein sequences";
			$ENTRY['PROCESS']['BLASTP']="ISSUE";
			return 'E26';
		}
		$HAS_MISSING_UNIP=true;
		if ($res!=array())
		{
			foreach ($res as $line){
				$LIST_UNIP_FOUND[$line['prot_identifier']]=true;
				$ENTRY['BLASTP']['UNIPROT'][$line['prot_identifier']]['POS'][$line['position']]=array($line['prot_seq_pos_id'],$line['letter']);
				if (!isset($ENTRY['BLASTP']['UNIPROT'][$line['prot_identifier']]['SEQ']))$ENTRY['BLASTP']['UNIPROT'][$line['prot_identifier']]['SEQ']='';
				$ENTRY['BLASTP']['UNIPROT'][$line['prot_identifier']]['SEQ'].=$line['letter'];
			}
			$HAS_MISSING_UNIP=false;
			foreach ($LIST_UNIP_FOUND as $K=>$V)if (!$V)$HAS_MISSING_UNIP=true;

		}
		if ($HAS_MISSING_UNIP)
		{
			$ST='';
			foreach ($LIST_AC as $AC=>&$UNIP){$ST.="'".$AC."',";}
			$res=runQuery("SELECT prot_identifier,prot_seq_pos_id,position,letter,ac
			FROM prot_seq US, prot_seq_pos USP, prot_entry UE ,prot_ac AC
			WHERE US.prot_seq_id = USP.prot_seq_id AND UE.prot_entry_id = US.prot_entry_id  AND ac.prot_entry_Id = ue.prot_entry_id
			AND ac IN (".substr($ST,0,-1).") AND us.is_primary='T' AND ac.is_primary='T' ORDER BY prot_identifier,position ASC");
			if ($res===false||$res==array())
			{
				
					foreach ($LIST_UNIP_FOUND as $UNI_ID=>&$STATUS)$ENTRY['MISSING_UNIP'][]=$UNI_ID;
					$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|Unable to find all uniprot records";
					$ENTRY['PROCESS']['BLASTP']="ISSUE";
					return 'E27';
			}
			else
			{
				echo "G\n";
				$CORRECTED_MAP=array();
				foreach ($res as $line){

					unset($LIST_UNIP_FOUND[$LIST_AC[$line['ac']]]);
					$CORRECTED_MAP[$line['ac']]=$line['prot_identifier'];
					$LIST_UNIP_FOUND[$line['prot_identifier']]=true;
					$ENTRY['BLASTP']['UNIPROT'][$line['prot_identifier']]['POS'][$line['position']]=array($line['prot_seq_pos_id'],$line['letter']);
					if (!isset($ENTRY['BLASTP']['UNIPROT'][$line['prot_identifier']]['SEQ']))$ENTRY['BLASTP']['UNIPROT'][$line['prot_identifier']]['SEQ']='';
					$ENTRY['BLASTP']['UNIPROT'][$line['prot_identifier']]['SEQ'].=$line['letter'];
				}
				foreach ($CORRECTED_MAP as $AC=>&$PROT_IDENTIFIER)
				{
					foreach ($ENTRY['BLASTP'] as $CHAIN=>&$BP_CHAIN)
					{
						if ($CHAIN =='UNIPROT'||$CHAIN=='SPECIES')continue;
						if (isset($BP_CHAIN['RESULTS']))
						foreach ($BP_CHAIN['RESULTS'] as &$BP_RES)
						{
							
							if ($BP_RES['AC']==$AC && $BP_RES['UNIPROT']!=$PROT_IDENTIFIER)$BP_RES['UNIPROT']=$PROT_IDENTIFIER;
						}
					}
				}
			}
		}
		
		echo "L\n";
		$res=runQuery("SELECT AC, PROT_IDENTIFIER FROM PROT_ENTRY PE, PROT_AC PA WHERE PA.PROT_ENTRY_ID = PE.PROT_ENTRY_ID AND prot_identifier IN (".substr($ST,0,-1).")");
		if ($res ===false)
		{
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|Unable to find all protein entries from Accession";
			$ENTRY['PROCESS']['BLASTP']="ISSUE";
			return 'E28';
		}	
		foreach ($res as $line)$AC_MAPS[$line['prot_identifier']][]=$line['ac'];
		
	}
	$ISSUE=false;
	print_r($LIST_UNIP_FOUND);
	foreach ($LIST_UNIP_FOUND as $UNI_ID=>&$STATUS)
	if ($STATUS==false){
		$ENTRY['MISSING_UNIP'][]=$UNI_ID;
		$ISSUE=true;
	}
	
	if ($ISSUE)
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|Missing uniprot";
		$ENTRY['PROCESS']['BLASTP']="ISSUE";
		return 'E29';	
	}


	/// SEQUENCE ALIGNMENT:
//	print_r($ENTRY);
	foreach ($ENTRY['BLASTP'] as $CHAIN=>&$BP_CHAIN)
	{
		if ($CHAIN=='SPECIES'|| $CHAIN=='UNIPROT')continue;
		$SCORE=array();
		
		foreach ($BP_CHAIN['RESULTS'] as $K=>&$RES)
		{
			$out=array();
			// echo "IN\n"; echo $RES['UNIPROT']."\t".(isset($ENTRY['BLASTP']['UNIPROT'][$RES['UNIPROT']])?"YES":"NO")."\n";
			// print_r($ENTRY['BLASTP']['UNIPROT']);
			if (!isset($ENTRY['BLASTP']['UNIPROT'][$RES['UNIPROT']]))
			{
				$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE--- TO FIND SEQUENCE FROM UNIPROT FILE ".$RES['UNIPROT'];
				$ENTRY['PROCESS']['BLASTP']="ISSUE";
			
			return 'E30';
			}
			
			exec($TG_DIR.'/'.$GLB_VAR['TOOL']['SEQALIGN'].' -all  -i -rn '.$CHAIN.' -cn '.$RES['UNIPROT'].' '.$BP_CHAIN['SEQRES'].' '.$ENTRY['BLASTP']['UNIPROT'][$RES['UNIPROT']]['SEQ'],$out);

			if (count($out)!=5)
			{
				$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO FIND RESULTS FOR ".$BP_CHAIN.' '.$RES['UNIPROT'];
				$ENTRY['PROCESS']['BLASTP']="ISSUE";
				return 'E31';
			}
			$ENTRY['BLASTP'][$CHAIN]['RESULTS'][$K]['ALIGNMENT']=array('SEQRES'=>$out[1],'UNIPROT'=>$out[3]);
			$tab=explode("\t",$out[4]);

			$ENTRY['BLASTP'][$CHAIN]['RESULTS'][$K]['IDEN']=$tab[0];	
			$ENTRY['BLASTP'][$CHAIN]['RESULTS'][$K]['SIM']=$tab[1];
			$ENTRY['BLASTP'][$CHAIN]['RESULTS'][$K]['IDEN_COM']=$tab[2];
			$ENTRY['BLASTP'][$CHAIN]['RESULTS'][$K]['SIM_COM']=$tab[3];
			$ENTRY['BLASTP'][$CHAIN]['STATUS']='ALIGNMENT';	
			$SCORE_E=$tab[2]*4+$tab[3]*3+$tab[0]*2+$tab[1];
			if (isset($AC_MAPS[$RES['UNIPROT']])&& isset($DBREFS[$CHAIN]))
			{
				foreach ($AC_MAPS[$RES['UNIPROT']] as $AC_UNI)
				{
					if (isset($DBREFS[$CHAIN][$AC_UNI]))$SCORE_E+=5;
				}
			}

			$SCORE[$RES['SPECIES'][0]][$SCORE_E][]=$K;
		}
		foreach ($SCORE as $SP=>$SC)
		{
			krsort($SC);$SC=array_values($SC);
			foreach ($SC[0] as $BEST)
			{
				if ($ENTRY['BLASTP'][$CHAIN]['RESULTS'][$BEST]['IDEN_COM']<0.3 &&
					$ENTRY['BLASTP'][$CHAIN]['RESULTS'][$BEST]['SIM_COM']<0.3)continue;
				$ENTRY['BLASTP'][$CHAIN]['PRIMARY'][]=$ENTRY['BLASTP'][$CHAIN]['RESULTS'][$BEST];
				break;
			}
		}
		$ENTRY['BLASTP'][$CHAIN]['STATUS']='OK';
//print_r($ENTRY['BLASTP'][$CHAIN]['PRIMARY']);exit;

		
	}
	

	$SIM_MATRIX=loadBLOSSUM($TG_DIR);
	$MAP_AA=array('ALA'=>'A','ARG'=>'R','ASN'=>'N','ASP'=>'D','CYS'=>'C','GLN'=>'Q',
		      'GLU'=>'E','GLY'=>'G','HIS'=>'H','ILE'=>'I','LEU'=>'L','LYS'=>'K','TRP'=>'W',
		      'MET'=>'M','PHE'=>'F','PRO'=>'P','SER'=>'S','THR'=>'T','TYR'=>'Y','VAL'=>'V');

	foreach ($ENTRY['BLASTP'] as $CHAIN=>&$BP_CHAIN)
	{
		if ($CHAIN=='SPECIES'|| $CHAIN=='UNIPROT')continue;
		
		
		foreach ($BP_CHAIN['PRIMARY'] as $UNI_REC)
		{
		//echo $CHAIN."\t".$UNI_REC['UNIPROT']."\t".$UNI_REC['AC']."\n";
			$MAP_XRINFO=array();
			foreach ($XRES_INFO[$CHAIN] as $K=>&$V)$MAP_XRINFO[]=$K;
			$XRES_INFO_T=array_values($XRES_INFO[$CHAIN]);
			$SEQ_BLP_AL=&$UNI_REC['ALIGNMENT'];
			$SEQ_XR_AL=&$BP_CHAIN['SEQ_RES_ALIGN']['ALIGNMENT'];
			$LEN_BLP_AL=strlen($SEQ_BLP_AL['SEQRES']);
			$LEN_XR_AL=strlen($SEQ_XR_AL['SEQRES']);
			$LEN_SEQRES=strlen($BP_CHAIN['SEQRES']);
			$ALIGN_INFO=array();
			$IDENTITY=0;$SIMILARITY=0;
			$CURSOR_XR_AL=-1;$CURSOR_BLP_AL=-1;$POS_XR=-1;$POS_BLP=-1;$POS_UNIP=-1;$POS_XRES=-1;
			$LEN_XR=count($XRES_INFO[$CHAIN]);$DIFF_XR=$LEN_XR;$N_MUTANT=0;
			$LEN_UN=count($ENTRY['BLASTP']['UNIPROT'][$UNI_REC['UNIPROT']]['POS']);$DIFF_UN=$LEN_UN;
			for ($I=0;$I<$LEN_SEQRES;++$I)
			{
				$CURSOR_XR_AL++;
				for ($CURSOR_XR_AL;$CURSOR_XR_AL<$LEN_XR_AL;++$CURSOR_XR_AL)
				{
					if ($SEQ_XR_AL['XRAY'][$CURSOR_XR_AL]!='-')$POS_XRES++;
					if ($SEQ_XR_AL['SEQRES'][$CURSOR_XR_AL]=='-')continue;
					$POS_XR++;
					if ($POS_XR==$I)break;
				}
				$CURSOR_BLP_AL++;
				for ($CURSOR_BLP_AL;$CURSOR_BLP_AL<$LEN_BLP_AL;++$CURSOR_BLP_AL)
				{
					if ($SEQ_BLP_AL['UNIPROT'][$CURSOR_BLP_AL]!='-')$POS_UNIP++;
					
					if ($SEQ_BLP_AL['SEQRES'][$CURSOR_BLP_AL]=='-')continue;
					$POS_BLP++;
					if ($POS_BLP==$I)break;
				}
				
				assert($SEQ_BLP_AL['SEQRES'][$CURSOR_BLP_AL]==$SEQ_XR_AL['SEQRES'][$CURSOR_XR_AL]);
				//echo $I."\t".$CURSOR_XR_AL."\t".$CURSOR_BLP_AL."\t".$SEQ_BLP_AL['SEQRES'][$CURSOR_BLP_AL].':'.$SEQ_XR_AL['SEQRES'][$CURSOR_XR_AL];
				$DB_UN='NULL';
				$TYPE='';
				$DB_XR='NULL';$UN_AA='';
				if ($SEQ_BLP_AL['UNIPROT'][$CURSOR_BLP_AL]=='-')
				{
				//	echo "\tN/A\tN/A\tN/A";

				} 
				else 
				{
					$DIFF_UN--;
					$UN_AA=$ENTRY['BLASTP']['UNIPROT'][$UNI_REC['UNIPROT']]['POS'][$POS_UNIP+1][1];
					$DB_UN=$ENTRY['BLASTP']['UNIPROT'][$UNI_REC['UNIPROT']]['POS'][$POS_UNIP+1][0];
					//echo $POS_UNIP."\t".$UN_AA."\t".$DB_UN;
				}
				if ($SEQ_XR_AL['XRAY'][$CURSOR_XR_AL]=='-')
				{
				//	echo "\tN/A\tN/A\tN/A\n";$TYPE='Q';
					if ($DB_UN!='NULL')$ALIGN_INFO['DB_ALIGNMENT'][]=array('NULL',$DB_UN,$TYPE);
				}
				else 
				{
					$DIFF_XR--;
					//$DB_XR=$XRES_INFO_T[$POS_XRES]['ID'];
					$DB_XR=$MAP_XRINFO[$POS_XRES];
					//echo "\t".$DB_XR."\t".$XRES_INFO_T[$POS_XRES]['NAME'];
					$XAA_3L=$XRES_INFO_T[$POS_XRES]['NAME'];
					$XAA_1L=(isset($MAP_AA[$XAA_3L]))?$MAP_AA[$XAA_3L]:'X';

					if ($UN_AA!='')
					{
						if ($XAA_1L==$UN_AA){$IDENTITY++;$SIMILARITY++;//echo "\tIDENTICAL";
							$TYPE='I';}
						else
						{
							
							if ($SIM_MATRIX[$XAA_1L][$UN_AA]>0){$SIMILARITY++;//echo "\tSIMILAR";
								$TYPE='S';}
							if ($XAA_3L!='ACE'&& $XAA_3L!='NME'){$N_MUTANT++;//echo "\tMUTANT";
								$TYPE='M';}
						} 
					}
					//echo "\n";
					
					$ALIGN_INFO['DB_ALIGNMENT'][]=array($DB_XR,$DB_UN,$TYPE);
				} 
				
				
			}
			$LEN_ALIGN=count($ALIGN_INFO['DB_ALIGNMENT']);
			$ALIGN_INFO['DB_ALIGN_INFO']=array(
					'LEN_XR'=>$LEN_XR,
					'LEN_UN'=>$LEN_UN,
					'XR_NOT_COVERED'=>$DIFF_XR,
					'UN_NOT_COVERED'=>$DIFF_UN,
					'SIMILAR_AA'=>$SIMILARITY,
					'IDENTICAL_AA'=>$IDENTITY,
					'UNIPROT'=>$UNI_REC['UNIPROT'],
					'AC'=>$UNI_REC['AC'],
					'LEN_ALIGN'=>$LEN_ALIGN,
					'PERC_IDEN'=>round($IDENTITY/($LEN_ALIGN+$DIFF_XR+$DIFF_UN)*100,2),
					'PERC_SIM'=>round($SIMILARITY/($LEN_ALIGN+$DIFF_XR+$DIFF_UN)*100,2),
					'PERC_IDEN_COM'=>round($IDENTITY/($LEN_ALIGN)*100,2),
					'PERC_SIM_COM'=>round($SIMILARITY/($LEN_ALIGN)*100,2),
					'MUTANT'=>$N_MUTANT
			);
			$BP_CHAIN['ALIGNMENT_DATA'][]=$ALIGN_INFO;
			//print_r($BP_CHAIN['DB_ALIGN_INFO']);
			///print_r($ALIGN_INFO['DB_ALIGNMENT']);
		}

		
	}

	


	unset($ENTRY['BLASTP']['UNIPROT'],$ENTRY['BLASTP']['SPECIES']);
	if ($JOB_SUCCESS)
	{
		$JOB_SUCCESS=false;
		foreach ($ENTRY['BLASTP'] as $CH=>$CH_DATA) if (count($CH_DATA['PRIMARY'])>0)$JOB_SUCCESS=true;
		if (!$JOB_SUCCESS)
		{
			$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|UNABLE TO FIND UNIPROT INFO FOR ANY CHAIN";
			$ENTRY['PROCESS']['BLASTP']="TERM";
			return 'E32';
		}
	}
	if ($JOB_SUCCESS)
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|SUCCESS";
		$ENTRY['PROCESS']['BLASTP']="OK";
		return 'OK';
	}
	else
	{
		$ENTRY['LOG'][]=date("Y/m/d:H:i:s")."|BLASTP|ISSUE";
		$ENTRY['PROCESS']['BLASTP']="ISSUE";
		return 'E33';

	}
	
}

function loadBLOSSUM($TG_DIR)
{
	global $GLB_VAR;
	$PATH=$TG_DIR.$GLB_VAR['TOOL']['BLOSSUM_DIR'].'/EBLOSUM62';
	
	if (!is_file($PATH))return false;
	$fp=fopen($PATH,'r'); if(!$fp)return false;
	$HEAD=array();$MATRIX=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if ($line=="")continue;
		if (substr($line,0,1)=="#")continue;
		$AA=substr($line,0,1);
		
		
		if ($HEAD==array()){$tab=explode(" ",substr(preg_replace('!\s+!', ' ', $line),1));$HEAD=$tab;continue;}
		else{ $tab=explode(" ",substr(preg_replace('!\s+!', ' ', $line),2));}
		
		foreach ($tab as $K=>$V)
		{
			if ($V=='')continue;
			
			$MATRIX[$AA][$HEAD[$K]]=$V;
		}
		
	}
	fclose($fp);
	
	return $MATRIX;
}



?>

