<?php
ini_set('memory_limit','1000M');

/**
 SCRIPT NAME: del_obsl_uniprot
 PURPOSE:     Delete obsolete uniprot entries
  
 The process of deleting a Uniprot record is complex as it involves multiple tables.
 The process is as follows:
	- First we delete the prot_dom and prot_seq entries that have been flagged as obsolete
		-> Which will delete their associated entries in prot_dom_al, prot_dom_seq, prot_seq_al, prot_seq_al_seq
		-> Those are usually pretty long to run if there are multiple sequence alignments
	
*/


/// Job name - Do not change
$JOB_NAME='del_obsl_uniprot';

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
	$CK_INFO=$GLB_TREE[getJobIDByName('ck_uniprot_rel')];
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR']; if (!is_dir($W_DIR)) 					failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   			if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR'];		if (!is_dir($W_DIR) && !mkdir($W_DIR)) 	failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												   if (!chdir($W_DIR)) 					failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);
												   $PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];
addLog("Get data");


/// List of tables that are related to the prot_dom and prot_seq tables
/// That will be called for deletion if the prot_dom or prot_seq is deleted
	$RELATED_TABLES=array('prot_dom'=>array(array('prot_dom_al','prot_dom_ref_id'),
										  array('prot_dom_al','prot_dom_comp_id'),
										  array('prot_dom_seq','prot_dom_id'),
										  array('prot_dom','prot_dom_id')),
						  'prot_seq'=>array(array('prot_seq_al','prot_seq_ref_id'),
										  array('prot_seq_al','prot_seq_comp_id'),
										  array('prot_mut','prot_seq_id'),
										  array('prot_feat','prot_seq_id'),
										  array('xr_ch_prot_map','prot_seq_id'),
										  array('tr_unseq_al','prot_seq_id'),
										  array('ip_sign_prot_seq','prot_seq_id'),
										  array('prot_seq_pos','prot_seq_id'),
										  array('prot_seq','prot_seq_id')),
						  'prot_entry'=>array(
										array('prot_entry','prot_entry_id'))
										 
);


$res=runQuery("SELECT * FROM prot_dom where status=9");
if ($res===false)																						failProcess($JOB_ID."004","Unable to run query");
foreach ($res as $line) deleteDom($line['prot_dom_id']);

$res=runQuery("SELECT * FROM prot_seq where status=9");
if ($res===false)																						failProcess($JOB_ID."005","Unable to run query");
foreach ($res as $line) deleteSeq($line['prot_seq_id']);





/// Get the list of entries to delete:
if (!is_file('LISTS/DELETED.csv'))																	 	failProcess($JOB_ID."006","Unable to find LISTS/DELETED.csv");
$fp=fopen('LISTS/DELETED.csv','r');
if (!$fp)																								 failProcess($JOB_ID."007","Unable to open LISTS/DELETED.csv");
$DELETED_ENTRIES=array();
while(!feof($fp))
{
	$line=stream_get_line($fp,1000,"\n");
	if ($line=='')continue;
	$tab=explode("\t",$line);
	$DELETED_ENTRIES[]=$tab[0];
}
fclose($fp);

foreach ($DELETED_ENTRIES as &$ENTRY) deleteEntry($ENTRY);



successProcess();












function deleteEntry($ENTRY)
{
	echo "###### ".$ENTRY."\n";
	$res=runQuery("SELECT * FROM prot_entry where prot_IDENTIFIER='".$ENTRY."'");
	if ($res===false)																					failProcess($JOB_ID."A01","Unable to run query");
	if (count($res)!=1)return;
	$PROT_ENTRY_ID=$res[0]['prot_entry_id'];


	/// First deleting domains and subsequent alignment
	$res=runQuery("SELECT * FROM prot_dom where prot_entry_id='".$PROT_ENTRY_ID."'");
	if ($res===false)																					failProcess($JOB_ID."A02","Unable to find prot_dom for prot_entry_id=".$PROT_ENTRY_ID);
	foreach ($res as $line)deleteDom($line['prot_dom_id']);

	/// Then deleting sequences and subsequent alignment
	$res=runQuery("SELECT * FROM prot_seq where prot_entry_id='".$PROT_ENTRY_ID."'");
	if ($res===false)																					failProcess($JOB_ID."A03","Unable to find prot_seq for prot_entry_id=".$PROT_ENTRY_ID);
	foreach ($res as $line)deleteSeq($line['prot_seq_id']);

	/// Deleting the rest of the data for this entry:
	$res=runQuery("SELECT * FROM prot_ac where prot_entry_id='".$PROT_ENTRY_ID."'");
	if ($res===false)																					failProcess($JOB_ID."A04","Unable to find prot_ac for prot_entry_id=".$PROT_ENTRY_ID);
	foreach ($res as $line)
	{
		$res2=runQueryNoRes("DELETE FROM prot_ac where prot_ac_id='".$line['prot_ac_id']."'");
		if ($res2===false)																				failProcess($JOB_ID."A05","Unable to delete prot_ac for prot_ac_id=".$line['prot_ac_id']);	
	}

	$res=runQueryNoRes("DELETE FROM gn_prot_map where prot_entry_id='".$PROT_ENTRY_ID."'");	if ($res===false)failProcess($JOB_ID."A06","Unable to delete gn_prot_map for prot_entry_id=".$PROT_ENTRY_ID);
	$res=runQueryNoRes("DELETE FROM prot_name_map where prot_entry_id='".$PROT_ENTRY_ID."'");	if ($res===false)failProcess($JOB_ID."A07","Unable to delete prot_name_map for prot_entry_id=".$PROT_ENTRY_ID);
	$res=runQueryNoRes("DELETE FROM prot_go_map where prot_entry_id='".$PROT_ENTRY_ID."'");	if ($res===false)failProcess($JOB_ID."A08","Unable to delete prot_go_map for prot_entry_id=".$PROT_ENTRY_ID);
	$res=runQueryNoRes("DELETE FROM p_xrprot_site where prot_entry_id='".$PROT_ENTRY_ID."'");	if ($res===false)failProcess($JOB_ID."A10","Unable to delete p_xrprot_site for prot_entry_id=".$PROT_ENTRY_ID);
	$res=runQueryNoRes("DELETE FROM prot_desc where prot_entry_id='".$PROT_ENTRY_ID."'");	if ($res===false)failProcess($JOB_ID."A11","Unable to delete prot_desc for prot_entry_id=".$PROT_ENTRY_ID);
	$res=runQueryNoRes("DELETE FROM prot_extdb_map where prot_entry_id='".$PROT_ENTRY_ID."'");	if ($res===false)failProcess($JOB_ID."A12","Unable to delete prot_extdb_map for prot_entry_id=".$PROT_ENTRY_ID);
	$res=runQueryNoRes("DELETE FROM prot_pmid_map where prot_entry_id='".$PROT_ENTRY_ID."'");	if ($res===false)failProcess($JOB_ID."A13","Unable to delete prot_pmid_map for prot_entry_id=".$PROT_ENTRY_ID);
	$res=runQueryNoRes("DELETE FROM xr_prot_int_stat where prot_entry_id='".$PROT_ENTRY_ID."'");	if ($res===false)failProcess($JOB_ID."A14","Unable to delete xr_prot_int_stat for prot_entry_id=".$PROT_ENTRY_ID);
	$res=runQueryNoRes("DELETE FROM xr_prot_stat where prot_entry_id='".$PROT_ENTRY_ID."'");	if ($res===false)failProcess($JOB_ID."A15","Unable to delete xr_prot_stat for prot_entry_id=".$PROT_ENTRY_ID);
	$res=runQueryNoRes("DELETE FROM prot_entry where prot_entry_id='".$PROT_ENTRY_ID."'");	if ($res===false)failProcess($JOB_ID."A16","Unable to delete prot_entry for prot_entry_id=".$PROT_ENTRY_ID);
	
}





function deleteDom($DOM_ID)
{
	echo "\tDOMAIN ".$DOM_ID."\n";
	$res=runQuery("SELECT * FROM prot_dom_al where prot_dom_ref_id='".$DOM_ID."' OR prot_dom_comp_id='".$DOM_ID."'");
	if ($res===false)failProcess($JOB_ID."B01","Unable to delete prot_dom_al for prot_dom_id=".$DOM_ID);
	foreach ($res as $line)deleteDomAl($line['prot_dom_al_id']);

	echo ";DOM SEQ ";
	$res=runQuery("SELECT * FROM prot_dom_seq where prot_dom_id = '".$DOM_ID."'");
	if ($res===false)failProcess($JOB_ID."B02","Unable to delete prot_dom_seq for prot_dom_id=".$DOM_ID);
	foreach ($res as $line)
	{
		$res2=runQueryNoRes("DELETE FROM prot_dom_seq where prot_dom_seq_id='".$line['prot_dom_seq_id']."'");
		if ($res2===false)failProcess($JOB_ID."B03","Unable to delete prot_dom_seq for prot_dom_seq_id=".$line['prot_dom_seq_id']);
	}

	echo ";DOM OTHER ";
	$res=runQueryNoRes("DELETE FROM xr_ch_udom_map where prot_dom_id='".$DOM_ID."'");
	if ($res===false)failProcess($JOB_ID."B04","Unable to delete xr_ch_udom_map for prot_dom_id=".$DOM_ID);
	$res=runQueryNoRes("DELETE FROM xr_prot_dom_cov where prot_dom_id='".$DOM_ID."'");
	if ($res===false)failProcess($JOB_ID."B05","Unable to delete xr_prot_dom_cov for prot_dom_id=".$DOM_ID);


	echo ";DOM ITSELF";
	$res=runQueryNoRes("DELETE FROM prot_dom where prot_dom_id='".$DOM_ID."'");
	if ($res===false)failProcess($JOB_ID."B06","Unable to delete prot_dom for prot_dom_id=".$DOM_ID);
	echo "=>DOM END\n";
}



function deleteSeqAl($SEQ_AL_ID)
{
	echo "; SEQ AL ".$SEQ_AL_ID;
	$res=runQueryNoRes("DELETE FROM prot_seq_al_seq where prot_seq_al_id='".$SEQ_AL_ID."'");
	if ($res===false)failProcess($JOB_ID."C01","Unable to delete prot_seq_al_id for prot_seq_al_id=".$SEQ_AL_ID);
	echo "; ITSELF ";
	$res=runQueryNoRes("DELETE FROM prot_seq_al where prot_seq_al_id='".$SEQ_AL_ID."'");
	if ($res===false)failProcess($JOB_ID."C02","Unable to delete prot_seq_al for prot_seq_al_id=".$SEQ_AL_ID);
	echo ";  END ";
}




function deleteDomAl($DOM_AL_ID)
{
	echo "; DOM AL ".$DOM_AL_ID;
	$res=runQueryNoRes("DELETE FROM prot_dom_al_seq where prot_dom_al_id='".$DOM_AL_ID."'");
	if ($res===false)failProcess($JOB_ID."D01","Unable to delete prot_dom_al_seq for prot_dom_al_id=".$DOM_AL_ID);
	echo "; ITSELF ";
	$res=runQueryNoRes("DELETE FROM prot_dom_al where prot_dom_al_id='".$DOM_AL_ID."'");
	if ($res===false)failProcess($JOB_ID."D02","Unable to delete prot_dom_al for prot_dom_al_id=".$DOM_AL_ID);
	echo ";  END ";
}




function deleteSeq($SEQ_ID)
{
	echo "\tSEQ ".$SEQ_ID;
	$res=runQuery("SELECT * FROM prot_seq_al where prot_seq_ref_id='".$SEQ_ID."' OR prot_seq_comp_id='".$SEQ_ID."'");
	if ($res===false)failProcess($JOB_ID."E01","Unable to delete prot_seq_al for prot_seq_id=".$SEQ_ID);
	foreach ($res as $line)deleteSeqAl($line['prot_seq_al_id']);

	
	$res=runQueryNoRes("DELETE FROM xr_ch_prot_map where prot_seq_id='".$SEQ_ID."'");
	if ($res===false)failProcess($JOB_ID."E02","Unable to delete xr_ch_prot_map for prot_seq_id=".$SEQ_ID);
	foreach ($res as $line)deleteXrSeqAl($line['xr_ch_prot_map_id']);

	$res=runQuery("SELECT * FROM tr_protseq_al where prot_seq_id='".$SEQ_ID."'");
	if ($res===false)failProcess($JOB_ID."E03","Unable to delete tr_protseq_al for prot_seq_id=".$SEQ_ID);
	foreach ($res as $line)deleteTrSeq($line['tr_protseq_al_id']);


	
	echo "; OTHER";
	$res=runQueryNoRes("DELETE FROM prot_extdb_map where prot_seq_id='".$SEQ_ID."'");
	if ($res===false)failProcess($JOB_ID."E04","Unable to delete prot_extdb_map for prot_seq_id=".$SEQ_ID);
	$res=runQueryNoRes("DELETE FROM prot_feat where prot_seq_id='".$SEQ_ID."'");
	if ($res===false)failProcess($JOB_ID."E05","Unable to delete prot_feat for prot_seq_id=".$SEQ_ID);
	
	
	$res=runQueryNoRes("DELETE FROM ip_sign_prot_seq where prot_seq_id='".$SEQ_ID."'");
	if ($res===false)failProcess($JOB_ID."E06","Unable to delete ip_sign_prot_seq for prot_seq_id=".$SEQ_ID);

	echo "; POS";
	$res=runQuery("SELECT * FROM prot_seq_pos where prot_seq_id = '".$SEQ_ID."'");
	if ($res===false)failProcess($JOB_ID."E07","Unable to delete prot_seq_pos for prot_seq_id=".$SEQ_ID);
	foreach ($res as $line)deleteSeqPos($line['prot_seq_pos_id']);

	echo "; ITSELF";
	$res=runQueryNoRes("DELETE FROM prot_seq where prot_seq_id='".$SEQ_ID."'");
	if ($res===false)failProcess($JOB_ID."E08","Unable to delete prot_seq for prot_seq_id=".$SEQ_ID);
	echo "; END";
}

function deleteTrSeq($TR_SEQ_AL_ID)
{
	echo "; TR_SEQ_AL ".$TR_SEQ_AL_ID;
	/// Getting the different transcript/protein sequence alignment
	$res=runQuery("SELECT * FROM tr_protseq_pos_al where tr_protseq_al_id = '".$TR_SEQ_AL_ID."'");
	if ($res===false)failProcess($JOB_ID."F01","Unable to delete tr_protseq_pos_al for tr_protseq_al_id=".$TR_SEQ_AL_ID);
	foreach ($res as $line)
	{
		/// Delete the alignment nucleotide/amino-acid
		$res2=runQueryNoRes("DELETE FROM tr_protseq_pos_al where tr_protseq_pos_al_id='".$line['tr_protseq_pos_al_id']."'");
		if ($res2===false)failProcess($JOB_ID."F02","Unable to delete tr_protseq_pos_al for tr_protseq_pos_al_id=".$line['tr_protseq_pos_al_id']);
	}
	$res=runQueryNoRes("DELETE FROM tr_protseq_al where tr_protseq_al_id='".$TR_SEQ_AL_ID."'");
	if ($res===false)failProcess($JOB_ID."F03","Unable to delete tr_protseq_al for tr_protseq_al_id=".$TR_SEQ_AL_ID);
	echo "; END ".$TR_SEQ_AL_ID;
}
function deleteSeqPos($SEQ_POS_ID)
{

	$tables=array('ptm_seq','assay_variant_pos','prot_feat_seq','p_xrprot_site_seq','variant_protein_map','xr_prot_int_stat','xr_ch_prot_pos');
	foreach ($tables as $t)
	{
		$res=runQueryNoRes("DELETE FROM ".$t." where prot_seq_pos_id='".$SEQ_POS_ID."'");
		if ($res===false)failProcess($JOB_ID."G01","Unable to delete ".$t." for prot_seq_pos_id=".$SEQ_POS_ID);
	}

	$res=runQueryNoRes("DELETE FROM prot_seq_pos where prot_seq_pos_id='".$SEQ_POS_ID."'");
	if ($res===false)failProcess($JOB_ID."G02","Unable to delete prot_seq_pos for prot_seq_pos_id=".$SEQ_POS_ID);
}


function deleteXrSeqAl($XR_SEQ_AL_ID)
{
	$res=runQuery("SELECT * FROM xr_ch_prot_pos where xr_ch_prot_map_id = '".$XR_SEQ_AL_ID."'");
	if ($res===false)failProcess($JOB_ID."H01","Unable to delete xr_ch_prot_pos for xr_ch_prot_map_id=".$XR_SEQ_AL_ID);
	foreach ($res as $line)
	{
		$res2=runQueryNoRes("DELETE FROM xr_ch_prot_pos where xr_ch_prot_pos_id='".$line['xr_ch_prot_pos_id']."'");
		if ($res2===false)failProcess($JOB_ID."H02","Unable to delete xr_ch_prot_pos for xr_ch_prot_pos_id=".$line['xr_ch_prot_pos_id']);
	}
	$res=runQueryNoRes("DELETE FROM xr_ch_prot_map where xr_ch_prot_map_id='".$XR_SEQ_AL_ID."'");
	if ($res===false)failProcess($JOB_ID."H03","Unable to delete xr_ch_prot_map for xr_ch_prot_map_id=".$XR_SEQ_AL_ID);
}


?>

