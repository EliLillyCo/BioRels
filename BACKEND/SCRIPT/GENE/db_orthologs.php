<?php
ini_set('memory_limit','500M');

/**
 SCRIPT NAME: db_orthologs
 PURPOSE:     Download orthologs gene files and process them
 
*/

/// Job name - Do not change
$JOB_NAME='db_orthologs';


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

addLog("Check directory");
	/// Get Parent info
	$DL_GENE_INFO=$GLB_TREE[getJobIDByName('db_gene')];

	/// Get to working directory
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'].'/'.$DL_GENE_INFO['TIME']['DEV_DIR'];	
	if (!is_dir($W_DIR)) 																	failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	if (!chdir($W_DIR)) 																	failProcess($JOB_ID."002",'NO '.$W_DIR.' found ');

	/// Update the process control so that the next job can access the directory
	$PROCESS_CONTROL['DIR']=$DL_GENE_INFO['TIME']['DEV_DIR'];
	
	$STATS=array('N_REL'=>0);

addLog("Download Gene Orthologs file");
	/// Two files are used: gene_orthologs from NCBI and human_all_hcop from HCOP
	if (!isset($GLB_VAR['LINK']['FTP_NCBI']))												failProcess($JOB_ID."003",'FTP_NCBI path no set');
	if (!dl_file($GLB_VAR['LINK']['FTP_NCBI'].'/gene/DATA/gene_orthologs.gz',3))			failProcess($JOB_ID."004",'Unable to download archive');
	

	if (!isset($GLB_VAR['LINK']['FTP_EBI_HCOP']))											failProcess($JOB_ID."005",'FTP_EBI_HCOP path no set');
	if (!dl_file($GLB_VAR['LINK']['FTP_EBI_HCOP'].'/human_all_hcop_sixteen_column.txt.gz',3))failProcess($JOB_ID."006",'Unable to download archive');

addLog("Untar archive");
	 if (!ungzip('gene_orthologs.gz'))														failProcess($JOB_ID."007",'Unable to extract archive');
	 if (!ungzip('human_all_hcop_sixteen_column.txt.gz'))									failProcess($JOB_ID."008",'Unable to extract archive');

addLog("File check");
	if (!validateLineCount('gene_orthologs',4980000))										failProcess($JOB_ID."009",'gene_orthologs is smaller than expected'); 
	if (!validateLineCount('human_all_hcop_sixteen_column.txt',700000))						failProcess($JOB_ID."010",'human HCOP file is smaller than expected	'); 


	/// HCOP has a much smaller list compared to NCBI, so we load them in memory to augment NCBI 
	$fp=fopen('human_all_hcop_sixteen_column.txt','r'); if(!$fp)							failProcess($JOB_ID."010",'Unable to open human hcop file'); 
	$line=stream_get_line($fp,10000,"\n");//header
	
	$STAT['ORTHO']=0;
	$HCOP=array();
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;		
		$tab=explode("\t",$line);

		if (!is_numeric($tab[1]) || !is_numeric($tab[8]))continue;
		if ($tab[15]=='OrthoMCL')continue;///OrthoMCL is very loose, so if an ortholog is only defined by it, then we ignore it.
		$HCOP[$tab[1]][$tab[0]][$tab[8]]=false;
		
	}
	fclose($fp);
	// For logging purpose
	$N_L=0;

	$fpO=fopen('gene_ortho_tmp','w');if(!$fpO)												failProcess($JOB_ID."011",'Unable to open output file'); 
	$fp=fopen('gene_orthologs','r');if(!$fp)												failProcess($JOB_ID."012",'Unable to open orthologs file'); 
	$line=stream_get_line($fp,10000,"\n");
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");if ($line=='')continue;
		
		// For logging purpose
		++$N_L; if ($N_L%100000==0)echo $N_L."\n";

		$tab=explode("\t",$line);
		/// NCBI gene only provides a one way relationship so we make it two ways
		$BUFFER[]=array($tab[1],$tab[4]);
		$BUFFER[]=array($tab[4],$tab[1]);
	
		/// We push the buffer every 50000 lines
		if (count($BUFFER)==50000){pushBuffer();$BUFFER=array();}

		/// Now we check with HCOP. Since HCOP is only human, no need to look for it if it's not human records
		if ($tab[0]!=9606)continue;
		if (!isset($HCOP[$tab[1]]))continue;
		if (!isset($HCOP[$tab[1]][$tab[3]]))continue;
		/// So if we found that record in HCOP, we set it to true
		if (!isset($HCOP[$tab[1]][$tab[3]][$tab[4]]))$HCOP[$tab[1]][$tab[3]][$tab[4]]=true;
	}
	fclose($fp);
	/// We push the last buffer
	pushBuffer();$BUFFER=array();


	/// From there, we are going to look if each record has been set to true or not
	foreach ($HCOP as $H_GENE_ID=>&$TAXS)
	foreach ($TAXS as $TAX_ID=>$O_GENES)
	foreach ($O_GENES as $O_GENE_ID=>$TAG)
	{
		/// If not, we can insert it.
		if ($TAG)continue;
		$BUFFER[]=array($H_GENE_ID,$O_GENE_ID);
		$BUFFER[]=array($O_GENE_ID,$H_GENE_ID);
		
		if (count($BUFFER)==50000){pushBuffer();$BUFFER=array();}
	}
	pushBuffer();
	fclose($fpO);
	
	/// In this script, we don't perform update to strain the system,
	//// especially since there is no foreign keys associated to that table.
	/// So here we are going to remove all data from the table and reload it
	/// This shell command is just going to add primary key column to each line 
	exec('sort gene_ortho_tmp| uniq | awk \'{printf "%s\t%s\n",  NR, $0}\' > gene_ortho_file.csv',$res,$return_code);
	if ($return_code!=0)												failProcess($JOB_ID."013",'Unable to prep orthologs file'); 
	

	function pushBuffer()
	{
		global $JOB_ID;
		global $BUFFER;
		global $fpO;
		global $STAT;
		
		/// The situation here is that, depending on whether or not the user has selected some taxons or not, the list of genes might differ and therefore the list of orthologs
		/// Therefore, we will check for all genes whether they are in the database or not, assuming db_gene did its job properly.

		$LIST=array();
		foreach ($BUFFER as $T){$LIST[$T[0]]=-1;$LIST[$T[1]]=-1;}
		if ($LIST==array())return;
		$res=runQuery("SELECT gene_id,gn_entry_Id FROM gn_entry WHERE gene_id IN (".implode(',',array_keys($LIST)).')');
		if ($res===false)												failProcess($JOB_ID."014",'Unable to run query'); 
		foreach ($res as $l)$LIST[$l['gene_id']]=$l['gn_entry_id'];
		foreach ($BUFFER as $T)
		if ($LIST[$T[0]]!=-1 && $LIST[$T[1]]!=-1)
		{
		//	$STAT['ORTHO']+=2;
			fputs($fpO,$LIST[$T[0]]."\t".$LIST[$T[1]]."\tO\n");
		}
	}
	


addLog("delete former table");
	if (!runQueryNoRes("truncate table gn_rel"))failProcess($JOB_ID."015",'Unable to truncate GN_REL'); 





addLog("Insert content in table");
	$command='\COPY '.$GLB_VAR['DB_SCHEMA'].'.gn_rel(gn_ortho_id,gn_entry_r_id,gn_entry_c_id,rel_type) FROM \''."gene_ortho_file.csv"."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )											failProcess($JOB_ID."016",'Unable to insert gn_rel table'); 



addLog("Exporting orthologs From genome assembly into file");
	
	$res=runQuery("SELECT taxon_id FROM genome_assembly ga");
	if ($res===false)																	failProcess($JOB_ID."017",'Unable to run query');

	$LIST_TAX=array();
	foreach ($res as $line)$LIST_TAX[]=$line['taxon_id'];

	if ($LIST_TAX==array())successProcess();
	

	$QUERY=' select DISTINCT ge.gene_Id as HUMAN_GENE_ID, ge.symbol as HUMAN_GENE_SYMBOL, t2.tax_id as ORTHO_TAX_ID, ge2.gene_id as ORTHO_GENE_ID, ge2.symbol as ORTHO_SYMBOL FROM taxon t1, chromosome c1, chr_map cm1, chr_gn_map cgm, gn_entry ge, gn_rel gr, gn_entry ge2, chr_gn_map cgm2, chr_map cm2, chromosome c2, taxon t2 
	where t1.taxon_id = c1.taxon_id
	ANd c1.chr_id = cm1.chr_id
	AND cm1.chr_map_id = cgm.chr_map_id
	AND cgm.gn_entry_id = ge.gn_entry_Id
	AND ge.gn_entry_id = gn_entry_r_id
	AND gn_entry_c_id = ge2.gn_entry_Id
	AND ge2.gn_entry_id = cgm2.gn_entry_Id
	AND cgm2.chr_map_id = cm2.chr_map_id
	AND cm2.chr_id = c2.chr_id
	AND c2.taxon_id = t2.taxon_id AND t1.tax_id=\'9606\' AND t2.taxon_id IN ('.implode(',',$LIST_TAX).')';
	$res=runQuery($QUERY);
	
	if ($res===false)																	failProcess($JOB_ID."022",'Unable to run query '.$QUERY);
	if (count($res)!=0)
	{
	$fpOR=fopen('LIST_GENOMIC_ORTHO','w'); if(!$fpOR)										failProcess($JOB_ID."023",'Unable to open file LIST_GENOMIC_ORTHO ');
	fputs($fpOR,implode("\t",array_keys($res[0]))."\n");
	foreach ($res as $line) fputs($fpOR,implode("\t",$line)."\n");
	fclose($fpOR);
	}


	
successProcess();

?>
