<?php

///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////// pp_uniprot ///////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////

/// Objectives: 
///   Get all uniprot entries needed by activity data or xray
///   Extract from TrEMBL all needed entries since we don't want to process all TrEMBL
///   Find all genes/uniprot entries from activity data or xray that are obsolete.
///   Using the list of existing uniprot entries, find ALL entries to delete / demerged / new.
ini_set('memory_limit','8000M');

/// Job name - Do not change
$JOB_NAME='pp_uniprot';

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
	/// GEt parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_uniprot_rel')];

	/// Setting up directory path:
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 								failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 								failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
	addLog("Working directory: ".$W_DIR);

	/// Update process control directory to the current release so that the next job can use it
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];
		
	/// Check CONFIG_USER for WITH_UNIPROT_SP and WITH_UNIPROT_TREMBL
	/// This will allow us to know if we need to process SwissProt and TrEMBL
	if (!isset($GLB_VAR['WITH_UNIPROT_SP']))														failProcess($JOB_ID."005",'WITH_UNIPROT_SP Not set in CONFIG_GLOBAL');
	if (!in_array($GLB_VAR['WITH_UNIPROT_SP'],array('Y','N')))										failProcess($JOB_ID."006",'WITH_UNIPROT_SP  value must be either Y or N');
	if (!isset($GLB_VAR['WITH_UNIPROT_TREMBL']))													failProcess($JOB_ID."007",'WITH_UNIPROT_TREMBL Not set in CONFIG_GLOBAL');
	if (!in_array($GLB_VAR['WITH_UNIPROT_TREMBL'],array('Y','N')))									failProcess($JOB_ID."008",'WITH_UNIPROT_TREMBL must be either Y or N');

	if (!isset($GLB_VAR['LINK']['FTP_UNIPROT']))													failProcess($JOB_ID."009",'FTP_UNIPROT path no set');
	$UNI_LINK=$GLB_VAR['LINK']['FTP_UNIPROT'].'/knowledgebase/complete/';
	
	

	addLog("Download dbxref");

	$WEB_PATH=$UNI_LINK.'/docs/dbxref.txt';
	if (!dl_file($WEB_PATH,3,'dbxref.txt'))															failProcess($JOB_ID."010",'Unable to download dbxref.txt ');


	/// Looking at 3rd party data that uses uniprot to reference their entries:
	$CHEMBL_UNIP=loadChEMBLInfo();
	
	$XRAY_UNIP=loadXrayInfo();
	
		




	

addLog("Check Uniprot info");
////////// Because we have different rulesets, we don't want to call failProcess if a file does not exist
//// So, depending on the configuration provided in CONFIG_USER, we are going to check the corresponding files
//// If they are not there, we set the job as VALID, i.e. it's going to wait for the data to be present.

	$UNI_FILES=array();
	if (checkFileExist('PROTEOMES/proteome_list'))
	{
		$UNI_FILES[]='PROTEOMES/proteome_list';
		if (!checkFileExist('PROTEOMES/ALL_SEQ.txt')) 			  {echo "MISSING PROTEOMES/ALL_SEQ.txt\n";successProcess('VALID');}
		if (!checkFileExist('PROTEOMES/ALL_PROT_UNIPROT.txt')){echo "MISSING PROTEOMES/ALL_PROT_UNIPROT.txt\n";successProcess('VALID');}
		if (!checkFileExist('PROTEOMES/proteome_list')) 	  {echo "MISSING PROTEOMES/proteome_list.txt\n";successProcess('VALID');}
	}

	if ($GLB_VAR['WITH_UNIPROT_SP']=='Y')
	{
		
		if (!checkFileExist('SPROT/sprot_list'))  		 {echo "MISSING SPROT/sprot_list\n";successProcess('VALID');}
		if (!checkFileExist('SPROT/uniprot_sprot.dat'))  {echo "MISSING SPROT/uniprot_sprot.dat\n";successProcess('VALID');}
		if (!checkFileExist('SPROT/uniprot_all.fasta'))  {echo "MISSING SPROT/uniprot_all.fasta'\n";successProcess('VALID');}
		$UNI_FILES[]='SPROT/sprot_list';
	}
	if ($GLB_VAR['WITH_UNIPROT_TREMBL']=='Y')
	{
		
		if (!checkFileExist('TREMBL/trembl_list')){echo "MISSING TREMBL/trembl_list\n";successProcess('VALID');}
		if (!checkFileExist('TREMBL/uniprot_trembl.dat')) {echo "MISSING TREMBL/uniprot_trembl.dat\n";successProcess('VALID');}
		if (!checkFileExist('TREMBL/uniprot_trembl.fasta')){echo "MISSING TREMBL/uniprot_trembl.fasta'\n";successProcess('VALID');}
		$UNI_FILES[]='TREMBL/trembl_list';
	}
	
addLog("Create List directory");	
	if (!is_dir('LISTS') && !mkdir('LISTS')) 												failProcess($JOB_ID."011",'Unable to create directory LISTS ');
	

	/// IN CONFIG_USER, you can provide a list of taxonomy ID corresponding to the organisms you want to consider
	$TAXON_LIMIT_LIST=defineTaxonList();
	//print_R($TAXON_LIMIT_LIST);exit;
	//$TAXON_LIMIT_LIST=array(10085,10089,10090);
	$TAXON_KEYS=array_flip($TAXON_LIMIT_LIST);


addLog("Compare to list");

	/// By default, we assume that all Proteome and Swiss-Prot (if enabled) are needed
	/// TrEMBL, even if enabled, will not be processed entirely due to its size
	/// So for TrEMBL, We will only process the records that are needed by:
	///		- the activity data
	///		- XRAY
	///		- is from a Taxon specified in the configuration ($TAXON_LIMIT_LIST)


	$LIST_AC_NEEDED=array();
	$LIST_TREMBL_NEEDED=array();
	$LIST_UNI_NEEDED=array();
	$LIST_ACs=array();
	$LIST_DL_ID=array();


	readUniFiles();



	$MISSING_RECORDS=array();

	/// If CHEMBL is enabled, we will have a list of uniprot entries that are needed
	/// Otherwise the array is empty and nothing will be done
	/// Here we check if we have found in the list we have downloaded all the uniprot entries that are needed
	/// If not, we add them to the list of missing records
	/// This is useful IF for instance TrEMBL is not downloaded
	/// But we still need some records from it.
	/// If TrEMBL and SwissProt are downloaded, the missing records will usually be obsolete records
	foreach ($CHEMBL_UNIP as $ID=>$T) 
	{
		if($T)continue;

		/// We don't want to process Ensembl entries
		if (checkRegex($ID,"ENSEMBL"))continue;
	
		echo "FROM CHEMBL ".$ID."\n";
	
		$MISSING_RECORDS[$ID]=false;
	}


	foreach ($XRAY_UNIP   as $ID=>$T) 
	{
		if($T)continue;

		if (checkRegex($ID,"ENSEMBL"))continue;
		
		echo "FROM XRAY ".$ID."\n";
	

		$MISSING_RECORDS[$ID]=false;
	}
	$N=0;
	

	/// If there are missing records, we process them
	if (count($MISSING_RECORDS)!=array())
	{
		processMissingRecords($MISSING_RECORDS,$LIST_ACs,$LIST_DL_ID,$LIST_AC_NEEDED);
	}




addLog("Saving list of uniprot entries that are needed");
	/// We save all  uniprot entries that are needed in LIST_UNIP_NEEDED.csv
	if ($LIST_AC_NEEDED!=array())
	{
		$fpREQ_AC=fopen('LISTS/LIST_UNIP_NEEDED.csv','w');
		if (!$fpREQ_AC)																			failProcess($JOB_ID."012",'Unable to open LISTS/LIST_UNIP_NEEDED.csv');
		fputs($fpREQ_AC,implode("\n",array_keys($LIST_AC_NEEDED)));
		fclose($fpREQ_AC);
	}
	
	if ($GLB_VAR['WITH_UNIPROT_TREMBL']=='Y')
	{
		createTrEMBLFile();
		

	}

addLog("Import current list from DB");
	$CURR_ENTRY_ID=array();
	$CURR_ENTRY_AC=array();
	importCurrentList($CURR_ENTRY_ID,$CURR_ENTRY_AC);



	$DELETED=array();
	$DEMERGED=array();

	$STR= "NUMBER OF ENTRIES:".count($CURR_ENTRY_ID)."\n";
	$ISSUE=array();


	/// Now we compare the list of current entries vs the list of downloaded entries.
	/// Removing from the current entries all the ones that are found in the downloaded entries
	/// Thus we will be left with current entries that are not in the downloaded entries, thus deleted.
	foreach ($CURR_ENTRY_ID as $ID=>$AC)
	{
		if (!isset($LIST_DL_ID[$ID]))continue;
		unset($LIST_DL_ID[$ID]);
		unset($CURR_ENTRY_ID[$ID]);	
	}

	echo count($LIST_DL_ID)."\t".count($CURR_ENTRY_ID)."\t".count($LIST_ACs)."\t".count($CURR_ENTRY_AC)."\n";
	
/// Check for ACs of multiple entries:
addLog("Search for demerged records");
	$DEMERGED=processDemerged($LIST_ACs,$CURR_ENTRY_AC);

	$TO_DEL_ACS=findToDelete($CURR_ENTRY_ID);
	

	$REPLACED=array();


addLog("Create pointers");
	createMasterPointer();

	echo "NEW:".count($LIST_DL_ID).
	"\tDELETED:".count($CURR_ENTRY_ID).
	"\t".count($LIST_ACs).
	"\tTOTAL:".count($CURR_ENTRY_AC).
	"\tREPLACED:".count($REPLACED)."\n";;

addLog("Export lists");
	exportLists();



successProcess();

















function loadChEMBLInfo()
{
	global $GLB_TREE;
	global $GLB_VAR;
	global $TG_DIR;
	global $JOB_ID;
	
	$CHEMBL_UNIP=array();
	$CHEMBL_CK_INFO=$GLB_TREE[getJobIDByName('ck_chembl_rel')];
	$CHEMBL_INFO=$GLB_TREE[getJobIDByName('dl_chembl')];
	if ($CHEMBL_INFO['ENABLED']=='T' && $CHEMBL_CK_INFO['ENABLED']=='T')
	{
		if ($CHEMBL_INFO['TIME']['DEV_DIR']==-1)return $CHEMBL_UNIP;
		/// There should be a chembl uniprot mapping file
		$CHEMBL_FILE=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$CHEMBL_INFO['DIR'].'/'.$CHEMBL_INFO['TIME']['DEV_DIR'].'/chembl_uniprot_mapping.txt';
		echo $CHEMBL_FILE."\n";
		
		if (!checkFileExist($CHEMBL_FILE)) 														failProcess($JOB_ID."A01",'Unable to find chembl_uniprot_mapping.txt');
		
		/// So we add all accessions
		$fp=fopen($CHEMBL_FILE,'r');if (!$fp)													failProcess($JOB_ID."A02",'Unable to open chembl_uniprot_mapping.txt');
		stream_get_line($fp,1000,"\n");

		while(!feof($fp))
		{
			$line=stream_get_line($fp,1000,"\n");if ($line=='')continue;
			$tab=explode("\t",$line);
			$CHEMBL_UNIP[$tab[0]]=false;
		}
		fclose($fp);
	}
	return $CHEMBL_UNIP;
}


function loadXrayInfo()
{
	global $GLB_TREE;
	global $GLB_VAR;
	global $TG_DIR;
	global $JOB_ID;


	addLog("Get Uniprot From PDB");
	$XRAY_UNIP=array();

	// /// Get the XRAY info. ck_xray_rel check for release of xray structures
	// $XRAY_CK_INFO=$GLB_TREE[getJobIDByName('ck_xray_rel')];

	// /// Now if it's disabled, we don't need to do anything
	// if ($XRAY_CK_INFO['ENABLED']!='T')return $XRAY_UNIP;
	
	// /// Otherwise we need to check if we have the FTP link for SIFT
	// if (!isset($GLB_VAR['LINK']['FTP_SIFT']))														failProcess($JOB_ID."B01",'FTP_SIFT path no set');
	// $SIFT_LINK=$GLB_VAR['LINK']['FTP_SIFT'];

	// /// Remove previous uniprot_pdb file if it exists
	// if (checkFileExist('uniprot_pdb.tsv') && !unlink('uniprot_pdb.tsv'))							failProcess($JOB_ID.'B02','Unable to remove previous uniprot_pdb');
	
	// /// Download the file
	// if (!dl_file($SIFT_LINK.'/flatfiles/tsv/uniprot_pdb.tsv.gz'))									failProcess($JOB_ID.'B03','Unable to download uniprot_pdb');

	// /// Extract the file
	// if (!ungzip('uniprot_pdb.tsv.gz'))																failProcess($JOB_ID.'B04','Unable to extract uniprot_pdb');
	
	// /// Get all accessions
	// $fp=fopen('uniprot_pdb.tsv','r'); if(!$fp)														failProcess($JOB_ID.'B05','Unable to open uniprot_pdb.tsv');
	
	// while(!feof($fp))
	// {
	// 	$line=stream_get_line($fp,100000,"\n");if ($line=='')continue;
	// 	$tab=explode("\t",$line);if ($tab[0]=='')continue;
	// 	$XRAY_UNIP[$tab[0]]=false;
	// }
	// fclose($fp);
	return $XRAY_UNIP;
}


function createTrEMBLFile()
{
	global $LIST_TREMBL_NEEDED;
	global $JOB_ID;
	addLog("Create trembl needed file");

	/// TrEMBL List contains all the records that are in TrEMBL
	/// However, not all of them are needed, so we have a list of the ones that are needed
	/// We are going to read the TrEMBL list and create a subset called TrEMBL_SEL
	$fp=fopen('TREMBL/trembl_list','r');
	if (!$fp)																						failProcess($JOB_ID."C01",'Unable to open  TREMBL_LIST file ');
	while (!feof($fp))
	{
		$line=stream_get_line($fp,300,"\n");
		if ($line=='')continue;
		$tab=explode("\t",$line);
		if (!isset($tab[1]))continue;
		
		if (!isset($LIST_TREMBL_NEEDED[$tab[1]]))continue;
		
		$LIST_TREMBL_NEEDED[$tab[1]]=$tab[4];

	}
	fclose($fp);


	/// Here we are going to read uniprot_trembl file and create a subset, called TREMBL_SEL, based on the list of TREMBL records needed
	$fpI=fopen('TREMBL/uniprot_trembl.dat','r');if (!$fpI)											failProcess($JOB_ID."C02",'Unable to open uniprot_trembl.dat ');
	$fp=fopen('TREMBL/TREMBL_SEL','w');if (!$fp)													failProcess($JOB_ID."C03",'Unable to open  DELETED file ');
	foreach ($LIST_TREMBL_NEEDED as $ID=>$FPOS)
	{
		if ($FPOS==-1)																				failProcess($JOB_ID."C04",'Unable to find '.$ID);
		fseek($fpI,$FPOS);
		$n=0;
		do
		{
			$line=stream_get_line($fpI,1000,"\n");
			fputs($fp,$line."\n");
			if ($line=='//')break;
			++$n;
		}while($n<100000);
			
	}
	fclose($fp);
	fclose($fpI);
}



function processDemerged(&$LIST_ACs,&$CURR_ENTRY_AC)
{
	global $JOB_ID;
	$STR='';
	$DEMERGED=array();
	$fpo=fopen("LISTS/DEMERGED.csv","w");if (!$fpo)												failProcess($JOB_ID."D01",'Unable to open  DEMERGED file ');

	foreach ($LIST_ACs as $AC=>$LIST)
	{
		if (count($LIST)==1) continue;/// Demerged records can be detected if an Accession exists in multiple records
		if (!isset($CURR_ENTRY_AC[$AC]))continue;
		echo $AC."\n";
		print_r($LIST);
		foreach ($LIST as $V) 
		{
			$DEMERGED[]=$V;
			$STR.=$V."\t".$AC."\n";
			
		}
		
		
	}
	fputs($fpo,$STR);
	fclose($fpo);
	return $DEMERGED;
}



function importCurrentList(&$CURR_ENTRY_ID,&$CURR_ENTRY_AC)
{
	global $JOB_ID;
	/// here we list all the entries currently existing in the database:
	if (!is_file('LISTS/LIST_CURRENT_PRIM_AC'))
	{
		$QUERY="SELECT ac,prot_identifier,status 
				FROM prot_entry UE 
				LEFT JOIN prot_ac UA ON UA.prot_entry_id = UE.prot_entry_id 
				WHERE is_primary='T'";
		$res=runQuery($QUERY);
		if ($res===false)																		failProcess($JOB_ID."E01",'Unable to run query');
		$fp=fopen("LISTS/LIST_CURRENT_PRIM_AC",'w');if (!$fp)									failProcess($JOB_ID."E02",'Unable to oget current list');
		
		foreach ($res as $line)
		{
			fputs($fp,implode("\t",$line)."\n");
		}
		fclose($fp);
	}

	/// Getting the list of current entries
	/// We just read the file we just did.
	/// This was made for development purposes to keep track of the different lists more easily:
	$TMP=array();
	exec('cat LISTS/LIST_CURRENT_PRIM_AC',$TMP);
	unset($TMP[0]);///remove header
	foreach ($TMP as $L)
	{
		$tab=explode("\t",$L);
		$CURR_ENTRY_ID[$tab[1]]=$tab[0];
		$CURR_ENTRY_AC[$tab[0]][]=$tab[1];
	}

}


function findToDelete(&$CURR_ENTRY_ID)
{
	$TO_DEL_ACS=array();
	/// CURR_ENTRY_ID only has the database entries that are not present in the downloaded list, so we need to delete those
	if (count($CURR_ENTRY_ID)>0)
	{
		$TMP=array();
		/// Since there can be a lot of entries, we do by chunks
		$CHUNKS=array_chunk(array_keys($CURR_ENTRY_ID),5000);
		foreach ($CHUNKS as $CHUNK)
		{
			$STR='SELECT ac, prot_identifier 
					FROM prot_entry UE, prot_ac UA
					WHERE UE.prot_entry_id = UA.prot_entry_id 
					AND prot_identifier IN (';
			foreach ($CHUNK as $CH)$STR.="'".$CH."',";
			$STR=substr($STR,0,-1).')';
			$res=runQuery($STR);
			if ($res===false)																		failProcess($JOB_ID."F01",'Unable to run query');
			foreach ($res as $l)
			{
				$TMP[$l['prot_identifier']][]=$l['ac'];
			}

		}
		foreach ($TMP as $UNI_ID=>$LT)
		{
			sort($LT);
			$TO_DEL_ACS[implode(',',$LT)]=$UNI_ID;
		}
		print_r($TO_DEL_ACS);

	}
	return $TO_DEL_ACS;
}







function  processMissingRecords(&$MISSING_RECORDS,&$LIST_ACs,&$LIST_DL_ID,&$LIST_AC_NEEDED)
{
	global $GLB_VAR;
	global $JOB_ID;
	if (count($MISSING_RECORDS)==0)return;
	
	addLog(count($MISSING_RECORDS).' missing records identified - Downloading');
	
	
	if (!isset($GLB_VAR['LINK']['FTP_UNIPROT_REST']))									failProcess($JOB_ID."G01",'FTP_UNIPROT_REST path no set');
	if (!is_dir('ALT') && !mkdir('ALT')) 												failProcess($JOB_ID."G02",'Unable to create new ALT dir ');
	if (checkFileExist('ALT/ALT_ENTRIES.txt')&&!unlink('ALT/ALT_ENTRIES.txt'))			failProcess($JOB_ID."G03",'Fail to delete ALT/ALT_ENTRIES.txt');
	
	$N=0;
	
	echo getcwd()."\n";

	//// Here we download the missing records
	foreach ($MISSING_RECORDS as $ID=>&$DUMMY)
	{
		++$N;
		if ($N%100==0)
		{
			addLog("\t".$N.'/'.count($MISSING_RECORDS));
		}

		if (!dl_file($GLB_VAR['LINK']['FTP_UNIPROT_REST'].'/'.$ID.'.txt',3,'ALT/'.$ID.'.txt'))	
		{
			sleep(500);
			if (!dl_file($GLB_VAR['LINK']['FTP_UNIPROT_REST'].'/'.$ID.'.txt',3,'ALT/'.$ID.'.txt'))	
			{
				addLog("Failed to download ".$ID.'.txt');
				continue;
			}
		}
		/// here we use is_file instead of checkFileExist because we want to check
		/// if the file is there, but if the entry is obsolete the file will be empty
		if (!is_file('ALT/'.$ID.'.txt'))						                            failProcess($JOB_ID."G04",'unable to find '.$ID.'.txt');
		$res=array();
		exec('cat ALT/'.$ID.'.txt >> ALT/ALT_ENTRIES.txt',$res,$return_code);
		if ($return_code!=0)																failProcess($JOB_ID."G05",'unable to copy content of  '.$ID.'.txt to ALT_ENTRIES.txt');

		if (!unlink('ALT/'.$ID.'.txt'))				                                        failProcess($JOB_ID."G06",'Fail to delete '.$ID.'.txt');
	}

	addLog("Entries downloaded - Processing");
	if (getLineCount('ALT/ALT_ENTRIES.txt')==0)return;

	/// Now that we have downloaded the entries, we need to read the file and extract the information we need
	/// to create the pointers for the records

	$fp=fopen('ALT/ALT_ENTRIES.txt','r');if (!$fp)										failProcess($JOB_ID."G07",'Unable to open ALT_ENTRIES data ');
	$fpO=fopen('ALT/ALT_list','w');	   if (!$fpO)										failProcess($JOB_ID."G08",'Unable to open ALT_list');
	$fpE=fopen('ALT/ALT_ensembl','w');   if (!$fpE)										failProcess($JOB_ID."G09",'Unable to open ALT_ensembl');



	$CURR_UID='';$AC=array();$TAX_ID=array();$GENE_ID=array();
	$START_P=0;$LIST_MISSING_UNI_ID=array();
	while(!feof($fp))
	{
		$pos=ftell($fp);
		$line=stream_get_line($fp,1000,"\n");
		if ($line=="")continue;
		$head=substr($line,0,2);

		if ($head=='ID')
		{
			$START_P=$pos;
			$tab=array_values(array_filter(explode(" ",$line)));
			$CURR_UID=$tab[1];
			
		}
		else if ($head=='AC')
		{
			$tab=array_values(array_filter(explode(";",substr($line,5))));
			
			foreach ($tab as $I) $AC[]=trim($I);
				
			
		}
		else if ($head=='OX')
		{
			if (strpos($line,'OX   NCBI_TaxID=')!==false)
			{
					$tab=array_values(array_filter(explode(" ",substr($line,16))));	
				$TAX_ID[]=substr(trim($tab[0]),0,-1);	

			}
		}
		else if ($head=='DR')
		{
			$tab=array_values(array_filter(explode(" ",$line)));//print_r($tab);
			if ($tab[1]=='GeneID;')
			{
					$tab=array_values(array_filter(explode(";",substr($line,13))));		
					$GENE_ID[]=$tab[0];

			}
			else if ($tab[1]=="Ensembl;"){
				for($i=2;$i<=4;++$i)
				{
					$str=substr($tab[$i],0,-1)."\t".$CURR_UID."\t";
					if (isset($tab[5])) $str.= substr($tab[5],1,-1);
					$str.="\n";
					fputs($fpE,$str);
				}
			}
		}
		/// End of record => we save the information
		else if ($head=='//')
		{
			$LIST_MISSING_UNI_ID[]=$CURR_UID;
			
			
			fputs($fpO,implode("|",$GENE_ID)."\t".$CURR_UID."\t".implode("|",$AC)."\t".implode("|",$TAX_ID)."\t".$START_P."\n");

			/// Here we continue the pp_uniprot process of checking individual files
			foreach ($AC as $tAC)	
			{
				if (!isset($LIST_AC_NEEDED[$CURR_UID]))$LIST_AC_NEEDED[$CURR_UID]=true;
				$LIST_ACs[$tAC][]=$CURR_UID;
			}
			if (!isset($LIST_DL_ID[$CURR_UID]))$LIST_DL_ID[$CURR_UID]=array();
			$LIST_DL_ID[$CURR_UID]=$AC;
				
					
			$CURR_UID='';$AC=array();$TAX_ID=array();$GENE_ID=array();
		}
	}
	fclose($fp);
	fclose($fpO);
	fclose($fpE);

	echo "COUNT LIST_MISSING_UNI_ID:".count($LIST_MISSING_UNI_ID)."\n";
	if (checkFileExist('ALT/ALT_ENTRIES.fasta')&&!unlink('ALT/ALT_ENTRIES.fasta'))				failProcess($JOB_ID."G10",'Fail to delete ALT_ENTRIES.fasta');
	$N=0;
	foreach ($LIST_MISSING_UNI_ID as $UNI_ID)
	{
		++$N;
		if ($N%100==0)
		{
			echo $N.'/'.count($LIST_MISSING_UNI_ID)."\n";
			break;
		}
		
		$W_PATH=$GLB_VAR['LINK']['FTP_UNIPROT_REST'].'/search?format=fasta&includeIsoform=true&query=id%3A'.$UNI_ID;
		if (!dl_file($W_PATH,3,'ALT/'.$UNI_ID.'.fasta'))										failProcess($JOB_ID."G11",'Fail to download '.$UNI_ID.'.fasta');
		if (!checkFileExist('ALT/'.$UNI_ID.'.fasta'))						                    failProcess($JOB_ID."G12",'unable to find '.$UNI_ID.'.fasta');
		$res=array();
		exec('cat ALT/'.$UNI_ID.'.fasta >> ALT/ALT_ENTRIES.fasta',$res,$return_code);
		if ($return_code!=0)failProcess($JOB_ID."G12",'unable to copy content of  '.$UNI_ID.'.fasta to ALT_ENTRIES.fasta');
		if (!unlink('ALT/'.$UNI_ID.'.fasta'))				                                    failProcess($JOB_ID."G13",'Fail to delete '.$UNI_ID.'.fasta');
	}



	$fp=fopen('ALT/ALT_ENTRIES.fasta','r');if (!$fp)											failProcess($JOB_ID."G14",'Unable to open ALT/ALT_ENTRIES.fasta'); 
	$fpR=fopen('ALT/ALT_ENTRIES.pointers','w');if (!$fpR)										failProcess($JOB_ID."G15",'Unable to open ALT/ALT_ENTRIES.pointers'); 
	while(!feof($fp))
	{
		$Fpos=ftell($fp);
		$line=stream_get_line($fp,1000,"\n");
		if (substr($line,0,1)!='>')continue;
		$pos=strpos($line,' ');
		$tab=explode("|",substr($line,1,$pos-1));
		fputs($fpR,$tab[1]."\t".$tab[2]."\t".$Fpos."\n");
	}
	fclose($fp);

}




function createMasterPointer()
{
	global $JOB_ID;
	global $LIST_DL_ID;
	global $TO_DEL_ACS;
	global $CURR_ENTRY_ID;
	global $LIST_DL_ID;

	$files=array('PROTEOMES/ALL_PROT_UNIPROT.txt',
				'SPROT/uniprot_sprot.dat',
				'TREMBL/TREMBL_SEL',
				'ALT/ALT_ENTRIES.txt');
	
	/// Create a master file that contains all the pointers to the records
	$fpo=fopen('unique_pointers.csv','w');if (!$fpo)												failProcess($JOB_ID."H01",'Unable to open  pointers file ');
	$SELECTED=array();
	
	foreach ($files as $file)
	{
		echo "PROCESSING ".$file."\n";
		if (!checkFileExist($file))continue;
		$fp= fopen($file,'r');if (!$fp)																failProcess($JOB_ID."H02",'Unable to open '.$file);
		while(!feof($fp))
		{
			$pointer=ftell($fp);
			$line=stream_get_line($fp,200,"\n");
			if (substr($line,0,2)!="ID")continue;
			$tab=array_values(array_filter(explode(" " ,$line)));
			/// Check if this entry has already been considered or not
			if (isset($SELECTED[$tab[1]]))continue;
			$SELECTED[$tab[1]]=true;

			/// So we add the pointer
			fputs($fpo, $tab[1]."\t".$file."\t".$pointer."\n");


			/// LIST_DL_IDs contains the list of records that exist in the database but not in the downloaded files.
			/// This can be potentially due to the fact that the uniprot identifier itself has been updated.

			if (!isset($LIST_DL_ID[$tab[1]]))continue;
			
			/// So we are get all the uniprot accession for that record
			$ID=$tab[1];$TMP_ACs=array();
			while(!feof($fp))
			{
				$line=stream_get_line($fp,1000,"\n");
				if ($line=="//")break;
				if (substr($line,0,2)!='AC')continue;
				$tab=explode(";",str_replace(' ','',substr($line,2)));
				foreach ($tab as $AC)if ($AC!='')$TMP_ACs[]=$AC;
			}
			sort($TMP_ACs);
			$STR=implode(',',$TMP_ACs);
			/// And check if it's in the list of potentially deleted entries
			foreach ($TO_DEL_ACS as $AC_STR=>$ID_STR)
			{
				if (strpos($STR,$AC_STR)===false)continue;
				/// So it's actually replaced
				$REPLACED[$ID][]=$ID_STR;
				/// Therfore we removed it from the list of deleted records
				unset($CURR_ENTRY_ID[$ID_STR]);
				unset($LIST_DL_ID[$ID]);
				echo "REPLACED\t".$ID."\t".$ID_STR."\n";
			}
		}
		fclose($fp);
	}
	fclose($fpo);
}



function exportLists()
{
	global $JOB_ID;
	global $REPLACED;
	global $DEMERGED;
	global $CURR_ENTRY_ID;
	global $LIST_DL_ID;

	$STR='';
	/// Listing all replaced records:
	$fpo=fopen("LISTS/REPLACED.csv","w");if (!$fpo)										failProcess($JOB_ID."I01",'Unable to open  LISTS/REPLACED file ');
	foreach ($REPLACED as $NEW=>$OLD)
	{
		/// If there is only one Uniprot ID, we just update the record
		if (count($OLD)==1)
		{
			$query="UPDATE prot_entry 
				SET prot_identifier='".$NEW."' 
				WHERE prot_identifier ='".$OLD[0]."'";
			
			$res=runQueryNoRes($query);
			if ($res===false)														failProcess($JOB_ID."I02",'Unable to update query');
			
			echo $query."\n";
			
			fputs($fpo,$OLD[0]."\t".$NEW."\n");
			
			$STR.="REPLACED\t".$OLD[0]."\t".$NEW."\n";
		}
		/// But if there are multiple Uniprot IDs, we need to set the records for deletion
		else 
		{
			fputs($fpo,implode("|",$OLD)."\t".$NEW."\tSET FOR DELETION\n");
			
			$STR.="REPLACED\t".implode("|",$OLD)."\t".$NEW."\n";
			
			foreach($OLD as $ID)
			{
				$CURR_ENTRY_ID[$ID]='N/A (Replaced)';
			}
		}
	}
	fclose($fpo);
	
	foreach ($DEMERGED as $ID)$CURR_ENTRY_ID[$ID]='N/A (Demerged)';
	
	/// We save the list of deleted records 
	$fpo=fopen("LISTS/DELETED.csv","w");if (!$fpo)							failProcess($JOB_ID."I03",'Unable to open  DELETED file ');
	
	foreach ($CURR_ENTRY_ID as $ID=>$AC) 
	{
		fputs($fpo,$ID."\t".$AC."\n");
		$STR.="DELETED\t".$ID."\t".$AC."\n";
	}
	fclose($fpo);
		
	/// We save the list of new records
	$fpo=fopen("LISTS/NEW.csv","w");if (!$fpo)							failProcess($JOB_ID."I04",'Unable to open  NEW file ');
	
	foreach ($LIST_DL_ID as $ID=>$AC)
	{ 
		if (!is_array($AC))continue;
		
		foreach ($AC as $A)
		{
			$STR.="NEW\t".$ID."\t".$A."\n";
			fputs($fpo,$ID."\t".$A."\n");
		} 
		
	}
	fclose($fpo);

	echo $STR."\n";
}


function readUniFiles()
{
	global $UNI_FILES;
	global $LIST_AC_NEEDED;
	global $LIST_TREMBL_NEEDED;
	global $LIST_DL_ID;
	global $LIST_ACs;
	global $TAXON_KEYS;
	global $XRAY_UNIP;
	global $CHEMBL_UNIP;
	global $JOB_ID;
	
$N_T=0;
	foreach ($UNI_FILES as $UNI_FILE)//// We read each uniprot file to look at all the records to check the ones we need
	{
		addLog($UNI_FILE);
		
		$fp=fopen($UNI_FILE,'r');
		if (!$fp)																				failProcess($JOB_ID."J01",'Unable to open  list '.$UNI_FILE);
		while(!feof($fp))
		{
			$line=stream_get_line($fp,1000,"\n");
			if ($line=="")continue;
			
			$tab=explode("\t",$line);
			
			$NEEDED=false;
			
			if (count($tab)<2)continue;
			
			/// Each record can have one or more accession IDS, we test them all
			if ($tab[2]!='')
			{
				$tab2=explode("|",$tab[2]);
				foreach ($tab2 as $tAC)
				{
					/// we test them against XRAY and CHEMBL
					if (isset($XRAY_UNIP[$tAC]))
					{
						$XRAY_UNIP[$tAC]=true;$NEEDED=true;
						/// And add them in the list if they are not already there
						if (!isset($LIST_AC_NEEDED[$tab[1]]))$LIST_AC_NEEDED[$tab[1]]=true;
					}
					if (isset($CHEMBL_UNIP[$tAC]))
					{
						$CHEMBL_UNIP[$tAC]=true;$NEEDED=true;
						/// And add them in the list if they are not already there
						if (!isset($LIST_AC_NEEDED[$tab[1]]))$LIST_AC_NEEDED[$tab[1]]=true;
					}
				}
			}

			
			/// For TrEMBL, we try to limit to only the needed records
			/// As for SwissProt & Proteome, we cover everything
			if (($UNI_FILE=='TREMBL/trembl_list' && $NEEDED)
			   ||$UNI_FILE!='TREMBL/trembl_list')
			{
				$ID=$tab[1];
				/// So TrEMBL has a specific list for it
				if ($UNI_FILE=='TREMBL/trembl_list')$LIST_TREMBL_NEEDED[$ID]=-1;
				
				if (!isset($LIST_DL_ID[$ID]))$LIST_DL_ID[$ID]=array();
				
				$tabAC=explode("|",$tab[2]);
				$LIST_DL_ID[$ID]=$tabAC;
				foreach ($tabAC as $AC)
				{
					$LIST_ACs[$AC][]=$tab[1];
				}
			}

		}
		fclose($fp);
	}

	/// This is all of the accession numbers that are needed
	foreach ($LIST_ACs as $AC=>$LIST_ID)
	{
		/// Depending on the source, we can have duplicated uniprot_identifiers, so we need to remove them
		$LIST_ACs[$AC]=array_unique($LIST_ID);
	}
}
?>
