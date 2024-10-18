<?php
error_reporting(E_ALL);
ini_set('memory_limit','5000M');
/**
 SCRIPT NAME: db_chembl_data
 PURPOSE:     PRocess ChEMBL data and push to DB
 
*/

/// Job name - Do not change
$JOB_NAME='db_chembl_data';

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

 

addLog("Access directory");
	/// Get Parent info
	$CK_INFO=$GLB_TREE[getJobIDByName('dl_chembl')];
	
	/// Create directory in PROCESS if it doesn't exist
	$W_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'];	if (!is_dir($W_DIR)) 								failProcess($JOB_ID."001",'NO '.$W_DIR.' found ');
	$W_DIR.='/'.$CK_INFO['DIR'].'/';   		   	if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID."002",'Unable to find and create '.$W_DIR);
	$W_DIR.=$CK_INFO['TIME']['DEV_DIR']; 	   	if (!is_dir($W_DIR) && !mkdir($W_DIR)) 				failProcess($JOB_ID."003",'Unable to create new process dir '.$W_DIR);
												if (!chdir($W_DIR)) 								failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);

	/// INSERT will be used for the new data
	if (!is_dir('INSERT') && !mkdir('INSERT')) 														failProcess($JOB_ID."005",'Unable to create INSERT dir '.$W_DIR);

	/// We assign the directory to the process control, so the next job knows where to look
	$PROCESS_CONTROL['DIR']=$CK_INFO['TIME']['DEV_DIR'];

	/// Then we look for ChEMBL as a source. Because different databases might have written ChEMBL differently, we do it case insensitive
	$source_id=getSource("ChEMBL");

	$STATIC_DATA=array();

	/// PRocess to public Biorels Schema

	
addLog("Process public");


	processChemblData($GLB_VAR['PUBLIC_SCHEMA']);
	
	addLog("Process drugs");
	processDrugs();

	
	
	
	$W_PRIVATE=($GLB_VAR['PRIVATE_ENABLED']=='T');
	

	/// Repeat operation with private schema
	if ($W_PRIVATE)
	{
		addLog("Process on private schema");
		processChemblData($GLB_VAR['SCHEMA_PRIVATE']);
	}



addLog("Update publications");
	/// Once we processed all the data, we need to update the pmid_gene_map table
	/// We are going to update the confidence of the pmid_gene_map table to 5 for all the entries that are associated to an assay
$res=runQueryNoRes('UPDATE pmid_gene_map SET CONFIDENCE=5 WHERE (pmid_entry_id, gn_entry_id)
 	IN (SELECT pmid_entry_id, gpm.gn_entry_id 
 		FROM assay_pmid am,assay_entry ae,assay_target at, assay_target_protein_map atpm, assay_protein ap, prot_seq ps, gn_prot_map gpm 
 		WHERE am.assay_entry_id = ae.assay_entry_id 
		AND ae.assay_target_id = at.assay_target_id 
		AND atpm.assay_target_id = at.assay_target_Id 
		AND atpm.assay_protein_id = ap.assay_protein_id 
		AND ps.prot_seq_id = ap.prot_seq_id 
		AND ps.prot_entry_Id = gpm.prot_entry_id)');
if ($res=== false)failProcess($JOB_ID."006",'Unable to update pmid_gene_map');

	/// And we insert the missing ones
$res=runQuery('SELECT DISTINCT pmid_entry_id, gpm.gn_entry_id 
FROM assay_pmid am,assay_entry ae,assay_target at, assay_target_protein_map atpm, assay_protein ap, prot_seq ps, gn_prot_map gpm 
WHERE am.assay_entry_id = ae.assay_entry_id AND ae.assay_target_id = at.assay_target_id 
AND atpm.assay_target_id = at.assay_target_Id AND atpm.assay_protein_id = ap.assay_protein_id
 AND ps.prot_seq_id = ap.prot_seq_id AND ps.prot_entry_Id = gpm.prot_entry_id
 AND (pmid_entry_id, gpm.gn_entry_id ) NOT IN (SELECT DISTINCT pmid_entry_Id,gn_entry_id FROM pmid_gene_map)');
 foreach ($res as $line)
 {
	 $query='INSERT INTO pmid_gene_map VALUES ('.$line['pmid_entry_id'].','.$line['gn_entry_id'].',5)';
	 if (!runQueryNoRes($query))failProcess($JOB_ID."007",'Unable to insert pmid_gene_map');
 }
 

	
function processChemblData($SCHEMA)
{
	/// Core of the process
	/// Molecules should already have been processed.
	/// First we add all the related information to the database
	
	processDNA_RNA_Component($SCHEMA);
	processComponent($SCHEMA);
	
	
	processAssayType($SCHEMA);
	processConfidence($SCHEMA);

	processTissue($SCHEMA);
	processVariants($SCHEMA);
	processCellLine($SCHEMA);

	processTargetType($SCHEMA);


 	processTarget($SCHEMA);


	/// then the assay
	processAssay($SCHEMA);
	
	processPublications($SCHEMA);


	/// Then the activities
 	processActivities($SCHEMA);
}







function processComponent($SCHEMA)
{
	global $GLB_VAR;
	global $DB_INFO;
	addLog("GET ASSAY PROTEIN");
		/// Getting the current list of protein with assays
		$DATA=array();
		$res=runQuery("SELECT * FROM ".$SCHEMA.".assay_protein");if ($res===false)									failProcess($JOB_ID."A01",'Unable to get assay_protein');
		
		/// MAX_DBID is going to be used to create new records
		$MAX_DBID=-1;
		$HAS_NEW=false;
		foreach ($res as $line)
		{
			$line['DB_STATUS']='FROM_DB';
			$DATA[$line['accession'].'_'.$line['sequence_md5sum']]=$line;
			$MAX_DBID=max($MAX_DBID,$line['assay_protein_id']);
		}


	addLog("GET COMPONENT SEQUENCE");
		/// Get ChEMBL PROTEIN components, which has a Uniprot Accession and md5 hash of the protein sequence
		/// EXAMPLE:
		/// 1 | PROTEIN        | O09028           | MSYSLYLAFVCLNLLAQRMCIQGNQFNVEVSRSDKLSLPGFENLTAGYNKFLRPNFGGDPVRIALTLDIASISSISESNMDYTATIYLRQRWTDPRL
		// VFEGNKSFTLDARLVEFLWVPDTYIVESKKSFLHEVTVGNRLIRLFSNGTVLYALRITTTVTCNMDLSKYPMDTQTCKLQLESWGYDGNDVEFSWLRGNDSVRGLENLRLAQYTIQQYFTLVTVSQQETGNYTRLVLQFELRRNVLYFI
		// LETYVPSTFLVVLSWVSFWISLESVPARTCIGVTTVLSMTTLMIGSRTSLPNTNCFIKAIDVYLGICFSFVFGALLEYAVAHYSSLQQMAVKDRGPAKDSEEVNITNIINSSISSFKRKISFASIEISGDNVNYSDLTMKASDKFKFVF
		//REKIGRIIDYFTIQNPSNVDRYSKLLFPLIFMLANVFYWAYYMYF  | 7473be17a767c25bb1d57beee67ffff7 | Gamma-aminobutyric acid receptor subunit pi |  10116 | Rattus norvegicus
		/// We can use the hash to compare the isoforms
		$res=runQuery("SELECT * FROM public.component_sequences where component_type='PROTEIN'");
		if ($res===false)																				failProcess($JOB_ID."A02",'Unable to get component_sequences');
		$LIST=array();
		foreach ($res as $line)$LIST["'".$line['accession']."'"][$line['sequence_md5sum']]='NULL';

	addLog("GET PROTEIN SEQUENCE");
		/// Finding all protein sequences with their hash that are associated to one of the Uniprot Accessions provided by ChEMBL
		$res2=runQuery("SELECT AC, PS.PROT_SEQ_ID, MD5(string_agg(LETTER,'' ORDER BY POSITION ASC)) as sequence 
		FROM prot_ac pa, prot_seq ps, prot_seq_pos psp
		where pa.prot_entrY_id = ps.prot_entry_id
		AND ps.prot_seq_id = psp.prot_seq_id 
		AND AC IN (".implode(',',array_keys($LIST)).") 
		GROUP BY AC, PS.PROT_SEQ_ID ");
		if ($res===false)																				failProcess($JOB_ID."A03",'Unable to get protein accessions');
		/// List of potential candidates
		foreach ($res2 as $line)$LIST["'".$line['ac']."'"][$line['sequence']]=$line['prot_seq_id'];
		$FOUND=0;

		/// Output file for new records
		$fp=fopen('INSERT/assay_protein.csv','w');if (!$fp)												failProcess($JOB_ID."A04",'Unable to open assay_protein.csv');
		/// Comparing data
		foreach ($res as $line)
		{
			/// entry already exist in database?
			if (isset($DATA[$line['accession'].'_'.$line['sequence_md5sum']]))
			{
				$E=&$DATA[$line['accession'].'_'.$line['sequence_md5sum']];
				$E['DB_STATUS']='VALID';

				/// If we cannot find the correct sequence based on Hash, in the database, the record will be empty
				/// so we initialize as empty
				$SEQ_ID='';
				/// We ensure we have the right sequence id based on checksum
				if (isset($LIST["'".$line['accession']."'"][$line['sequence_md5sum']]))
				$SEQ_ID=$LIST["'".$line['accession']."'"][$line['sequence_md5sum']];
				if ($SEQ_ID!=$E['prot_seq_id'])
				{
					$query='UPDATE '.$SCHEMA.'.assay_protein
					 SET prot_seq_id = '.$SEQ_ID.' 
					 WHERE assay_protein_id = '.$E['assay_protein_id'];
					if (!runQueryNoRes($query))															failProcess($JOB_ID."A05",'Unable to update assay_protein'); 
				}
			}
			else
			{
				++$MAX_DBID;
				
				/// HERE we want to insert, so we want to default to be NULL
				$SEQ_ID='NULL';
				if (isset($LIST["'".$line['accession']."'"][$line['sequence_md5sum']]))
				$SEQ_ID=$LIST["'".$line['accession']."'"][$line['sequence_md5sum']];
				fputs($fp,$MAX_DBID."\t".$line['accession']."\t".$line['sequence_md5sum']."\t".$SEQ_ID."\n");
				$HAS_NEW=true;
			}
			
		}



	addLog("PUSH TO DB");
		if ($HAS_NEW)
		{
			fclose($fp);
			$command='\COPY '.$SCHEMA.'.assay_protein (assay_protein_id,accession, sequence_md5sum,prot_seq_id) FROM \'INSERT/assay_protein.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
			echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
			system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
			if ($return_code !=0 )																		failProcess($JOB_ID."A06",'Unable to insert assay_protein'); 
			
		}
	

}



function processActivities($SCHEMA)
{
	/// Insert activity data

	addLog("Process Activities");
	echo "START:".memory_get_usage() . "\n";
	global $GLB_VAR;
	global $DB_INFO;
	global $source_id;
	global $STATIC_DATA;
	global $DB_CONN;
	global $STATS;

	//// STEP 1 - Getting all the static data necessary for the process
	echo "Get MAX ID\n";
	/// Get the maximum ID for the activity_entry table to create new records
	$res=runQuery("SELECT MAX(activity_entry_id) co FROM  ".$SCHEMA.".activity_entry ");
	if ($res===false)																failProcess($JOB_ID."B01",'Unable to get Max ID for '.$TBL);

	$MAX_ACT_ID=($res[0]['co']=='')?0:$res[0]['co'];

	
	/// Bioassay ontology entry describes the different types of assays
	/// We will need it. So we first query Chembl table to get the different bao_endpoint
	/// And then query the bioassay_onto_entry to get the bioassay_onto_entry_id
	echo "Prepare BAO\n";
	$query='select bioassay_tag_id,bioassay_onto_entry_id FROM bioassay_onto_entry';
	$res=runQuery($query);
	if ($res===false)													failProcess($JOB_ID."B02",'Unable to get BAO ENDPOINT. Need to run bioassay_onto');
	$STATIC_DATA['BAO']=array();
	foreach ($res as $line)$STATIC_DATA['BAO'][$line['bioassay_tag_id']]=$line['bioassay_onto_entry_id'];
	if ($STATIC_DATA['BAO']==array())									failProcess($JOB_ID."B03",'Unable to get BAO ENDPOINT. Need to run bioassay_onto');



	echo "Get list of assay:";
	/// Now we map the assays to the assay_entry_id
	$MAP_ASSAY=array();
	$res=runQuery("SELECT assay_entry_id, assay_name FROM  ".$SCHEMA.".assay_entry WHERE source_id=".$source_id);
	if ($res===false)														failProcess($JOB_ID."B04",'Unable to get assay_entry');
	foreach ($res as $line )$MAP_ASSAY[$line['assay_name']]=$line['assay_entry_id'];
	echo "\t".count($MAP_ASSAY)."\n";




	$res=runQuery("SELECT  a.assay_id ,a.chembl_id, count(*) co 
	FROM public.assays a,public.activities ac 
	WHERE ac.assay_id = a.assay_id 
	group by a.assay_id ,a.chembl_id ORDER BY chembl_id ASC");// AND a.chembl_id='CHEMBL1049061'
	if ($res===false )													failProcess($JOB_ID."B05",'Unable to get assay_id');
	
	$fp=fopen('ACTIVITIES.csv','w');			if (!$fp)					failProcess($JOB_ID."B06",'Unable to open ACTIVITIES.csv');
	$START=false;$N_ASSAY=count($res);$HAS_NEW=false;
	$CHUNKS=array();
	$NEW_CHUNK=array();
	$N_CHUNK=0;
	foreach ($res as $N=>$line)
	{
		if ($N_CHUNK+intval($line['co'])>50000)
		{
			$CHUNKS[]=$NEW_CHUNK;
			$NEW_CHUNK=array();
			$N_CHUNK=0;
		}
		$N_CHUNK+=intval($line['co']);
		$NEW_CHUNK[]=array('assay_name'=>$line['chembl_id'],'assay_id'=>$line['assay_id'],'assay_entry_id'=>$MAP_ASSAY[$line['chembl_id']],'count'=>$line['co']);
		
	}
	if ($NEW_CHUNK!=array())$CHUNKS[]=$NEW_CHUNK;


	foreach ($CHUNKS as $N_C=>&$CHUNK)
	{
		echo "\n\n\n###### CHUNK ".$N_C."/".count($CHUNKS)."\n";
		//print_R($CHUNK);
		processAssayActivity($SCHEMA,$CHUNK,$STMT,$MAX_ACT_ID,$fp);
		//exit;
	}
		

	

}




function processAssayActivity($SCHEMA,$LIST_ASSAYS,&$STMT,&$MAX_ACT_ID,&$fpO)
{
	global $STATIC_DATA;
	global $source_id;
	global $JOB_ID;
	global $DB_CONN;
	global $STATS;

	$DEBUG=false;
	$MAP=array();$MAP_K=array();
	$DATA=array();
	foreach ($LIST_ASSAYS as $T)
	{
		$MAP[$T['assay_id']]=array('ASSAY_NAME'=>$T['assay_name'],'ASSAY_ENTRY_ID'=>$T['assay_entry_id'],'ASSAY_ID'=>$T['assay_id'],'count'=>$T['count'],'NEW'=>0,'DEL'=>0,'VALID'=>0,'NOT_FOUND'=>0);
		$MAP_K[$T['assay_entry_id']]=$T['assay_id'];
		$DATA[$T['assay_name']]=array();
	}
	$HAS_NEW=false;
	echo "\t=>Get list of compounds for this assay: ";
	/// Get the list of chembl ids for those assays
	/// Again, we need to break them out into chunks because the number of data is too big
	$query='SELECT DISTINCT m.chembl_id
	FROM public.activities a, public.compound_records cr, public.molecule_dictionary m 
	WHERE m.molregno = a.molregno 
	AND a.record_id = cr.record_id 
	AND assay_id IN (\''.implode("','",array_column($LIST_ASSAYS,'assay_id')).'\')';
	//echo $query."\n";
	$ALL_CPD=array();
	$res=runQuery($query);
	if ($res===false) 																failProcess($JOB_ID."T01",'Unable to get chembl_id');
	foreach ($res as $line)$ALL_CPD[]="'".$line['chembl_id']."'";
	echo count($ALL_CPD)." compounds\n";
	echo "\t=> Current memory:".memory_get_usage() . "\n";
		

	/// Break into chunks
	$CHUNKS_CPDS=array_chunk($ALL_CPD,5000);
	/// Now to save memory, we unset the original array
	$ALL_CPD=null;unset($ALL_CPD);

	echo "\t=>Process ".count($CHUNKS_CPDS)."\tchunks of compounds\n";


	///We have fetch the data from ChEMBL and from BioRels/
	///For each ChEMBL id, we also have the corresponding molecular_entity_id
	///We are going to compare that data with the data already in the database
	///And add new data in the database
	
	echo "\t=>Comparing data\n";
	$HAS_NEW=0;$N_FOUND=0;$NO_VALUE=0;$NO_CPD=0;$N_CHEMBL_RECORDS=0;
$TOT_NEW=0;
	/// Now we process each chunks of chembl ids
	foreach ($CHUNKS_CPDS as $N_CPD=>&$CHUNK_CPD)
	{
		echo "\t\t".$N_CPD."\t".count($CHUNK_CPD)."\n";

		echo "\t=> Get ChEMBL data: ";
		/// Now we get the activities for thos assays and those compounds
		$query='SELECT DISTINCT standard_relation, standard_value,
						standard_units, standard_flag, standard_type,
						type, relation,value,units,assay_id,
						bao_endpoint,cr.compound_key,m.chembl_id
				FROM public.activities a, public.compound_records cr, public.molecule_dictionary m 
				where m.molregno = a.molregno 
				AND a.record_id = cr.record_id 
				AND assay_id IN (\''.implode("','",array_column($LIST_ASSAYS,'assay_id')).'\')
				AND m.chembl_id IN ('.implode(',',$CHUNK_CPD).')';
		
		$CHEMBL_DATA=array();
		$res=runQuery($query);
		if ($res===false) 													failProcess($JOB_ID."T02",'Unable to get activities');
		foreach ($res as $line)$CHEMBL_DATA[$line['assay_id']][]=$line;
		echo count($res)." records\n";
		$N_CHEMBL_RECORDS+=count($res);
		if ($DEBUG)print_R($CHEMBL_DATA);
		
		$LIST_CPD=array();
		foreach ($CHEMBL_DATA as $ASSAY_ID=>&$CHEMBL_ASSAY)
		foreach ($CHEMBL_ASSAY as &$C) 
		{
			foreach ($C as $K=>&$V)if ($V!='')$V=trim($V);
			$LIST_CPD["'".$C['chembl_id']."'"]=-1;
			/// Convert the bao_endpoint to the bioassay_onto_entry_id
			$C['bao_id']=$STATIC_DATA['BAO'][$C['bao_endpoint']];
		}

		/// Getting the molecular_entity_id for those compounds
		$query="SELECT molecular_entity_id, sm_name
				FROM  ".$SCHEMA.".sm_source sm, ".$SCHEMA.".sm_entry se,  ".$SCHEMA.".molecular_entity me 
				WHERE me.molecular_structure_hash = se.md5_hash  
				AND sm.sm_entry_id = se.sm_entry_id
				AND source_id = ".$source_id.'
				AND sm_name IN ('.implode(',',$CHUNK_CPD).')';
		$res2=runQuery($query);
		if ($res2===false) 															failProcess($JOB_ID."T03",'Unable to get molecular_entity_id');
		echo "\t=>N molecular entity\t".count($res2)."\n";
		foreach ($res2 as $line)
		{
			$LIST_CPD["'".$line['sm_name']."'"]=$line['molecular_entity_id'];
		}
		foreach ($LIST_CPD as $K=>&$V)if ($V==-1) echo "\tMISSING ".$K."\n";
	


		echo "\t=>Get existing data: ";
		$query="SELECT ae.*, aa.assay_name 
		FROM  ".$SCHEMA.".activity_entry ae, 
		 ".$SCHEMA.".assay_entry aa 
		 WHERE aa.assay_entry_id =ae.assay_entry_id 
		 AND aa.assay_name IN ('".implode("','",array_column($LIST_ASSAYS,'assay_name'))."')
		 AND aa.SOURCE_ID=".$source_id."
		 AND ae.molecular_entity_id IN (".implode(",",array_values($LIST_CPD)).")";
		$res=runQuery($query);
		if ($res===false)	 											failProcess($JOB_ID."T04",'Unable to get activity_entry for '.$STR);
		
		echo count($res)." ini records\t";
		/// We store those activity data in an array, grouped by assay_name and molecular_entity_id
		
		foreach ($res as $line)
		{
			$line['DB_STATUS']='FROM_DB';
			$DATA[$line['assay_name']][$line['molecular_entity_id']][]=$line;
		}
		
		if ($DEBUG)print_R($DATA);
		$N_BLOCK_NO_CPD=0;
		$N_BLOCK_NO_VALUE=0;
		$N_BLOCK_FOUND=0;
		foreach ($CHEMBL_DATA as $ASSAY_ID=>&$CHEMBL_ASSAY)
		{
			$ASSAY_ENTRY_ID=$MAP[$ASSAY_ID]['ASSAY_ENTRY_ID'];
			$ASSAY_NAME=$MAP[$ASSAY_ID]['ASSAY_NAME'];
			//echo $ASSAY_ID."\t".$ASSAY_ENTRY_ID."\t".$ASSAY_NAME."\n";
			foreach ($CHEMBL_ASSAY as &$C)
			{
				

				/// Getting the molecular_entity_id for that compound
				if (!isset($LIST_CPD["'".$C['chembl_id']."'"])){ $C['INVALID']=true;echo "MISSING:".$C['chembl_id']."\n";$MAP[$ASSAY_ID]['NOT_FOUND']++;;continue;}
				$C['molecular_entity_id']=$LIST_CPD["'".$C['chembl_id']."'"];
			


				/// Cleanup the data by removing trailing spaces
				foreach ($C as $K=>&$KV)if ($KV!='')$KV=trim($KV);

				if ($C['value']==''){$MAP[$ASSAY_ID]['NOT_FOUND']++;$C['INVALID']=true;continue;}
				if ($C['molecular_entity_id']==-1){$MAP[$ASSAY_ID]['NOT_FOUND']++;$C['INVALID']=true;continue;}

				/// Check if we have some data for this assay and this molecule
				/// If that molecule & assay association is not present, we can directly go to add the new record
				if (isset($DATA[$ASSAY_NAME][$C['molecular_entity_id']]))
				{
					if ($DEBUG) echo "IN\n";
					/// Reference to the data.
					$DB_E=&$DATA[$ASSAY_NAME][$C['molecular_entity_id']];
					$FOUND=false;

					/// Now we need to check if the additional information is present or not
					foreach ($DB_E as &$DB_EE)
					{
						if (isset($DB_EE['DB_STATUS'])&&$DB_EE['DB_STATUS']=='VALID')continue;
						if ($DB_EE['relation']!=$C['relation'])continue;
						if ($DB_EE['value']!=$C['value'])continue;
						if ($DB_EE['units']!=$C['units'])continue;
						if ($DB_EE['unit_type']!=$C['type'])continue;
						if ($DB_EE['std_relation']!=$C['standard_relation'])continue;
						if ($DB_EE['std_value']!=$C['standard_value'])continue;
						if ($DB_EE['std_units']!=$C['standard_units'])continue;
						if ($DB_EE['std_type']!=$C['standard_type'])continue;
						if ($DB_EE['bao_endpoint']!=$C['bao_id'])continue;
						if ($DB_EE['mol_pos']!=$C['compound_key'])continue;
						if ($DEBUG) echo "FOUND\n";
						$FOUND=true;
						//echo "FOUND!\n";
					
						$DB_EE['DB_STATUS']='VALID';
						$C['FOUND']=true;
						$MAP[$ASSAY_ID]['VALID']++;
						break;
					}
					if ($FOUND)continue;

				}
				$C['TO_INSERT']=true;
			}
		}
		
	

		



		/// We checked for those compounds and those assays if they are in the database and added the new records
		/// Now we are going to check for the records that are in the database but not in the ChEMBL data
		/// And that we are going to delete
		$TO_DEL=array();

		foreach ($DATA as $ASSAY_ENTRY_ID=>&$ASSAY_DB_DATA)
		foreach ($ASSAY_DB_DATA as $CPD_NAME=>&$CPD_INFO)

		foreach ($CPD_INFO as $D=>&$CPD_ACT)
		{

			if ($CPD_ACT['DB_STATUS']=='VALID')continue;
		//echo "TO DEL\n";
			//($CPD_ACT);
			$MAP[$ASSAY_ID]['DEL']++;
			$TO_DEL[]=$CPD_ACT['activity_entry_id'];
			
		}



		//If we have some records to delete, we do it
		if ($TO_DEL!=array())
		{
			/// Break that into chunks
			$CHUNKS_D=array_chunk($TO_DEL,10000);
			foreach ($CHUNKS_D as $CHUNK_D)
			{
				if ($DEBUG) echo 'DELETE FROM '.$SCHEMA.'.activity_entry where activity_entry_id IN ('.implode(',',$CHUNK_D).')'."\n";
			$res=runQueryNoRes('DELETE FROM '.$SCHEMA.'.activity_entry where activity_entry_id IN ('.implode(',',$CHUNK_D).')');
			if ($res===false)	 											failProcess($JOB_ID."T05",'Unable to delete activity_entry');
			}
		}

		

		foreach ($CHEMBL_DATA as $ASSAY_ID=>&$CHEMBL_ASSAY)
		{
			$ASSAY_ENTRY_ID=$MAP[$ASSAY_ID]['ASSAY_ENTRY_ID'];
			$ASSAY_NAME=$MAP[$ASSAY_ID]['ASSAY_NAME'];
			foreach ($CHEMBL_ASSAY as &$C)
			{
			if (!isset($C['TO_INSERT']))continue;	
			if ($DEBUG) echo "NEW\t".$ASSAY_ID."\t".$ASSAY_ENTRY_ID."\t".$ASSAY_NAME."\n";
			
			++$MAX_ACT_ID;
			$HAS_NEW=true;
			$MAP[$ASSAY_ID]['NEW']++;

		//	echo "NEW ";//print_R($C);
			$params=array(
				'activity_entry_id'=> $MAX_ACT_ID,
				'assay_entry_id'=>$ASSAY_ENTRY_ID,
				'std_relation'=>$C['standard_relation'],
				'std_value'=>$C['standard_value'],
				'std_units'=>$C['standard_units'],
				'std_flag'=>$C['standard_flag'],
				'std_type'=>$C['standard_type'],
				'relation'=>$C['relation'],
				'units'=>$C['units'],
				'value'=>$C['value'],
				'unit_type'=>$C['type'],
				'bao_endpoint'=>$C['bao_id'],
				'mol_pos'=>(($C['compound_key']!='')?str_replace('"','""',$C['compound_key']):''),
				'molecular_entity_id'=>$C['molecular_entity_id'],
				'source_id'=>$source_id
			);
			++$TOT_NEW;
			if ($DEBUG)print_R($params);
		
			fputs($fpO,implode("\t",$params)."\n");
			//$STMT['act_entry']->execute($params);
			$HAS_NEW++;
		}
	}
		
	}
	
global $DB_INFO;
	// Clean memory
	gc_collect_cycles();
	if ($HAS_NEW)
	{
		
		fclose($fpO);
		echo "\t=>>>>>>>>>>>> ".$TOT_NEW."\t new records\n";
		/// Create the copy command
		$command='\COPY '.$SCHEMA.'.activity_entry (activity_entry_id, assay_entry_id,std_relation,std_value,std_units,std_flag,std_type,relation,units,value,unit_type,bao_endpoint,mol_pos,molecular_entity_id,source_id) FROM \'ACTIVITIES.csv\''."  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		
		/// Run the command
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )													failProcess($JOB_ID."T06","ERROR:".'Unable to insert activity_entry'."\n");
		$fpO=fopen('ACTIVITIES.csv','w');if (!$fpO)								failProcess($JOB_ID."T07",'Unable to open ACTIVITIES.csv');
	}

	$query="SELECT COUNT(activity_entry_id) co ,assay_name
	FROM  ".$SCHEMA.".activity_entry ae, 
	 ".$SCHEMA.".assay_entry aa 
	 WHERE aa.assay_entry_id =ae.assay_entry_id 
	 
	 AND aa.assay_name IN('".implode("','",array_column($LIST_ASSAYS,'assay_name'))."') AND aa.SOURCE_ID=".$source_id.' GROUP BY assay_name';
	$SCORES=array();

	$res=runQuery($query);if ($res===false) 									failProcess($JOB_ID."T08",'Unable to get activity_entry');
	foreach ($res as $line)$SCORES[$line['assay_name']]=$line['co'];
	foreach ($LIST_ASSAYS as $AT)
	{

		$CO=0;
		if (isset($SCORES[$AT['assay_name']]))$CO=$SCORES[$AT['assay_name']];
			
		if ($AT['count']!=$MAP[$AT['assay_id']]['NEW']+$MAP[$AT['assay_id']]['VALID']+$MAP[$AT['assay_id']]['NOT_FOUND'])
		{
			/// This warning is NOT an error. Some values can be duplicated in ChEMBL due to other metadata information and will be considered twice.
			echo "WARNING\t".$MAP[$AT['assay_id']]['VALID'].'+'.$MAP[$AT['assay_id']]['NEW'].' registered + '.$MAP[$AT['assay_id']]['NOT_FOUND'].' with no cpd or values != '.$AT['count'].' records in ChEMBL for '.$AT['assay_name']."\n";
			
		}
	}	

	//exit;

}




function processPublications($SCHEMA)
{
	addLog("Process Publication");
	global $GLB_VAR;
	global $DB_INFO;
	global $STATIC_DATA;

	/// Getting all publications associated to assays from BioRels
	$res=runQuery("SELECT a.assay_name,pmid,ap.* 
		FROM ".$SCHEMA.".assay_pmid ap, pmid_entry p, ".$SCHEMA.".assay_entry a
		where a.assay_entry_id = ap.assay_entry_id 
		AND p.pmid_entry_id = ap.pmid_entry_id");
	if ($res===false) 															failProcess($JOB_ID."C01",'Unable to get assay_pmid');

	/// All data will be stored in an array, grouped by assay_name and pmid
	$DATA=array();
	/// We also get the maximum ID for the assay_pmid_entry table to be able to add new records
	$MAX_DBID=0;
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$DATA[$line['assay_name']][$line['pmid']]=$line;
		$MAX_DBID=max($MAX_DBID,$line['assay_pmid_entry']);
	}

	/// Then we get the data from ChEMBL
	$res=runQuery("SELECT pubmed_id, a.chembl_id 
	FROM public.assays a, public.docs d 
	where d.doc_id = a.doc_id and pubmed_id is not null");
	
	/// Static data will be used to get the assay_entry_id from the chembl_id later
	/// MISSING will be used to get the pmid_entry_id from the pubmed_id later
	$STATIC_DATA=array();
	$MISSING=array();
	foreach ($res as $line)
	{
		/// We have it already in the database?
		if (isset($DATA[$line['chembl_id']][$line['pubmed_id']]))
		{
			$E=&$DATA[$line['chembl_id']][$line['pubmed_id']];
			/// Update the status
			$E['DB_STATUS']='VALID';
			continue;
		}
		/// If not, we need to add it
		$STATIC_DATA["'".$line['chembl_id']."'"]='NULL';

		$MISSING[$line['pubmed_id']][]=$line['chembl_id'];
		
	}
	/// No new data? We are done
	if ($STATIC_DATA ==array() || $MISSING==array())return;
	
	/// First we get the Assay_entry_id from the chembl_id
	$res=runQuery("SELECT assay_entry_id,assay_name 
	FROM ".$SCHEMA.".assay_entry 
	where assay_name IN (".implode(",",array_keys($STATIC_DATA)).')');
	if ($res ===false) 															failProcess($JOB_ID."C02",'Unable to get assay_entry');
	foreach ($res as $line)$STATIC_DATA["'".$line['assay_name']."'"]=$line['assay_entry_id'];


	/// Then we get the pmid_entry_id from the pubmed_id
	$res=runQuery("SELECT pmid_entry_id,pmid 
	FROM pmid_entry 
	where pmid IN (".implode(",",array_keys($MISSING)).')');
	if ($res ===false) 															failProcess($JOB_ID."C03",'Unable to get pmid_entry');
	$fp=fopen('INSERT/pmid.csv','w');	if (!$fp) 								failProcess($JOB_ID."C04",'Unable to open pmid.csv');
	foreach ($res as $line)
	{
		$E=&$MISSING[$line['pmid']];
		foreach ($E as $chembl_id)
		{
			/// We ensure we have the assay_entry_id
			if ($STATIC_DATA["'".$chembl_id."'"]=='NULL')continue;
			++$MAX_DBID;
			fputs($fp,$MAX_DBID."\t".$line['pmid_entry_id']."\t".$STATIC_DATA["'".$chembl_id."'"]."\n");
		}
	}
	fclose($fp);

	/// Then we insert the new records
	$command='\COPY '.$SCHEMA.'.assay_pmid (assay_pmid_entry,pmid_entry_id,assay_entry_id) FROM \'INSERT/pmid.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )failProcess($JOB_ID."C05",'Unable to insert assay_pmid');

}

function processTarget($SCHEMA)
{
	global $GLB_VAR;
	global $DB_INFO;
	global $JOB_ID;
	global $STATIC_DATA;
	
	/// Assay can be assigned to different targets. Therefore, before we process the assay, we need to process the targets

	/// DATA will store all the current assay targets from Biorels
	$DATA=array();
	$res=runQuery("SELECT * FROM ".$SCHEMA.".assay_target WHERE assay_target_name LIKE 'CHEMBL%'");
	if ($res===false) 															failProcess($JOB_ID."D01",'Unable to get assay_target');
	$MAX_DBID=-1;
	foreach ($res as $line)
	{
		/// All will have a status of FROM_DB. So we can use that to check if we need to update/delete the record
		$line['DB_STATUS']='FROM_DB';
		foreach ($line as $K=>&$V)if ($V=='' && $V!='assay_target_id')$V='NULL';
		$DATA[$line['assay_target_name']]=$line;
		/// We also get the maximum ID to be able to add new records
		$MAX_DBID=max($MAX_DBID,$line['assay_target_id']);
	}



	/// There are some static data that we need to get from the database to add the appropriate metadata
	$STATIC_DATA=array();
	
	/// First getting the assay_Target_type that we previously added
	$res=runQuery("SELECT assay_target_type_id, assay_target_type_name FROM ".$SCHEMA.".assay_Target_type");
	if ($res ===false) 															failProcess($JOB_ID."D02",'Unable to get assay_target_type');
	foreach ($res as $line)$STATIC_DATA['TYPE'][$line['assay_target_type_name']]=$line['assay_target_type_id'];
	
	$res2=runQuery("SELECT DISTINCT c.tax_id
	FROM public.component_sequences c ");
	if ($res2===false)														failProcess($JOB_ID."D04",'Unable to get taxons from component_sequences');
	foreach ($res2 as $line)
	{
		if ($line['tax_id']!='NULL')	$STATIC_DATA['TAX']["'".$line['tax_id']."'"]='NULL';
	}

	///Then we get from ChEMBL the different taxons
	$res=runQuery("SELECT * FROM public.target_dictionary");
	if ($res===false)														failProcess($JOB_ID."D03",'Unable to get target_dictionary');
	foreach ($res as $line)
	{
		if ($line['tax_id']!='NULL')	$STATIC_DATA['TAX']["'".$line['tax_id']."'"]='NULL';
	}

	

	/// So we can map them to the taxon_id
	$res2=runQuery("select taxon_id, tax_id FROM taxon where tax_id IN (".implode(',',array_keys($STATIC_DATA['TAX'])).')');
	if ($res2===false)														failProcess($JOB_ID."D05",'Unable to get taxon');
	foreach ($res2 as $line)$STATIC_DATA['TAX']["'".$line['tax_id']."'"]=$line['taxon_id'];

	/// In some instances the taxon_id is not present in the database. We need to find the merged taxon_id
	foreach ($STATIC_DATA['TAX'] as $TAX_ID=>&$TAX_DBID)if ($TAX_DBID=='NULL')$TAX_DBID=findMergedTaxon(substr($TAX_ID,1,-1));


	$MAP_TARGET=array();
	$HAS_NEW=false;
	$fp=fopen('INSERT/assay_target.csv','w'); if (!$fp) 						failProcess($JOB_ID."D06",'Unable to open assay_target.csv');
	foreach ($res as $line)
	{
		// Assign the type
		$TYPE='NULL';	 if (isset($STATIC_DATA['TYPE'][$line['target_type']]))$TYPE=$STATIC_DATA['TYPE'][$line['target_type']];
		/// Assign the taxon_id
		$TAXON_ID='NULL';if (isset($STATIC_DATA['TAX']["'".$line['tax_id']."'"]))$TAXON_ID=$STATIC_DATA['TAX']["'".$line['tax_id']."'"];

		/// Then we check if we have that target in the database
		if (isset($DATA[$line['chembl_id']]))
		{
			$E=&$DATA[$line['chembl_id']];
			$MAP_TARGET[$line['chembl_id']]=$E['assay_target_id'];
			
			/// We set it up to VALID by default. then we check for differences
			$E['DB_STATUS']='VALID';
			$query='';
			
			if ($E['species_group_flag']!=$line['species_group_flag'])$query.=' species_group_flag = '.$line['species_group_flag'].',';
			if ($E['assay_target_longname']!=$line['pref_name'])$query.=" assay_target_longname= '".str_replace("'","''",$line['pref_name'])."',";
			if ($E['assay_target_type_id']!=$TYPE) $query.=" assay_target_type_id = ".$TYPE.',';
			if ($E['taxon_id']!=$TAXON_ID) $query.=" taxon_id = ".$TAXON_ID.',';

			/// Some data has changed -> we update the record
			if ($query!='')
			{
				if (!runQueryNoRes("UPDATE ".$SCHEMA.".assay_target SET ".substr($query,0,-1).' WHERE assay_target_id = '.$E['assay_target_id']))
				failProcess($JOB_ID."D07",'Unable to update assay_target'); 
			}
		}
		else
		{
			/// We need to add a new record
			++$MAX_DBID;
			$MAP_TARGET[$line['chembl_id']]=$MAX_DBID;
			$HAS_NEW=true;
			fputs($fp,$MAX_DBID."\t".$TAXON_ID."\t".$line['chembl_id']."\t".$line['pref_name']."\t".$TYPE."\t".$line['species_group_flag']."\n");
		}
		
		
	}
	fclose($fp);


	if ($HAS_NEW)
	{
	
		$command='\COPY '.$SCHEMA.'.assay_target (assay_target_id,taxon_id,assay_target_name,assay_target_longname,assay_target_type_id,species_group_flag) FROM \'INSERT/assay_target.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )failProcess($JOB_ID."D08",'Unable to insert assay_target'); 
	
	}
	



	processAssayProtein($SCHEMA,$MAP_TARGET);
	processAssayGenetics($SCHEMA,$MAP_TARGET);


}

function processAssayProtein($SCHEMA,&$MAP_TARGET)
{
	global $GLB_VAR;
	global $DB_INFO;
	global $JOB_ID;
	global $STATIC_DATA;
	/// Once we do the targets, we can move on to assay_protein and assay_genetic
	/// Target protein

	/// Getting all assay_protein records
	$PROT=array();
	$res=runQuery("SELECT * FROM ".$SCHEMA.".assay_protein");
	if ($res===false)									failProcess($JOB_ID."E01",'Unable to get assay_protein');

	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$PROT[$line['accession'].'_'.$line['sequence_md5sum']]=$line['assay_protein_id'];
	}

	/// Then getting all the assay_target_protein_map records
	$DATA=array();
	$res=runQuery("SELECT * FROM ".$SCHEMA.".assay_target_protein_map");
	if ($res===false) 															failProcess($JOB_ID."E02",'Unable to get assay_target_protein_map');
	$MAX_DBID=-1;
	foreach ($res as $line)
	{
		$DATA[$line['assay_target_id']][$line['assay_protein_id']]=
		array(
			'DBID'=>$line['assay_target_protein_map_id'],
			'is_homologue'=>$line['is_homologue'],
			'DB_STATUS'=>'FROM_DB'
		);
		$MAX_DBID=max($MAX_DBID,$line['assay_target_protein_map_id']);
	}



	$fp=fopen('INSERT/assay_target_protein.csv','w');
	if (!$fp) 																	failProcess($JOB_ID."E03",'Unable to open assay_target_protein.csv');
	$HAS_NEW=false;

	/// Getting from ChEMBL the different proteins
	$res=runQuery("SELECT td.chembl_id,accession,sequence,sequence_md5sum,homologue 
	FROM public.target_dictionary td,public.target_components t, public.component_sequences c 
	WHERE td.tid=t.tid AND c.component_id = t.component_id 
	AND component_type='PROTEIN'");
	foreach ($res as $line)
	{
		/// Based on the accession and the sequence_md5sum, we can get the assay_protein_id
		$ASSAY_PROTEIN_ID=$PROT[$line['accession'].'_'.$line['sequence_md5sum']];

		/// Based on the chembl_id, we can get the assay_target_id
		$ASSAY_TARGET_ID=$MAP_TARGET[$line['chembl_id']];

		/// If we have that association in the database, we check if the homologue status has changed
		if (!isset($DATA[$ASSAY_TARGET_ID][$ASSAY_PROTEIN_ID]))
		{
			++$MAX_DBID;
			fputs($fp,$MAX_DBID."\t".$ASSAY_TARGET_ID."\t".$ASSAY_PROTEIN_ID."\t".$line['homologue']."\n");
			$HAS_NEW=true;
		}
		else
		{
			/// We set it up to VALID by default. then we check for differences
			$DATA[$ASSAY_TARGET_ID][$ASSAY_PROTEIN_ID]['DB_STATUS']='VALID';

			/// If the homologue status has changed, we update the record
			if ($DATA[$ASSAY_TARGET_ID][$ASSAY_PROTEIN_ID]['is_homologue']!=$line['homologue'])
			{
				if (!runQueryNoRes("UPDATE ".$SCHEMA.".assay_target_protein_map 
				SET is_homologue=".$line['homologue'].' 
				where assay_target_protein_map_id='.$DATA[$ASSAY_TARGET_ID][$ASSAY_PROTEIN_ID]['DBID'])) 
				failProcess($JOB_ID."E04",'Unable to update assay_target_protein_map');
			}

		}
	}

	/// We checked for those proteins and those targets if they are in the database and added the new records
	/// Now we are going to check for the records that are in the database but not in the ChEMBL data
	/// And that we are going to delete them
	$TO_DEL=array();
	foreach ($DATA as $assay_target_id=>&$LIST_P)
	foreach ($LIST_P as $assay_prot_id=>&$E)
	{
		if ($E['DB_STATUS']=='FROM_DB')$TO_DEL[]=$E['DBID'];
	}
	if ($TO_DEL!=array())
	{
		
		if (!runQueryNoRes("DELETE FROM ".$SCHEMA.".assay_target_protein_map
							WHERE assay_target_protein_map_id IN (".implode(',',$TO_DEL).')'))
		failProcess($JOB_ID."E05",'Unable to delete assay_target_protein_map');
	}

	/// If we have new records, we insert them
	if ($HAS_NEW)
	{
		fclose($fp);
		$command='\COPY '.$SCHEMA.'.assay_target_protein_map (assay_target_protein_map_id,assay_target_id,assay_protein_id,is_homologue) FROM \'INSERT/assay_target_protein.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )failProcess($JOB_ID."E06",'Unable to insert assay_target_protein_map'); 
	}

	

}

function processAssayGenetics($SCHEMA,&$MAP_TARGET)
{
	/// Assay genetics specifies the genetic targets for different assays
	global $GLB_VAR;
	global $DB_INFO;
	global $JOB_ID;
	global $STATIC_DATA;

	/// DNA will have all the current assay_genetic records grouped by  taxon_id+genetic_description
	$DNA=array();
	$res=runQuery("SELECT * FROM ".$SCHEMA.".assay_genetic");
	if ($res===false)														failProcess($JOB_ID."F01",'Unable to get assay_genetic');
	foreach ($res as $line)
	{
		if ($line['accession']!='')$DNA[$line['accession']]=$line['assay_genetic_id'];
		$DNA[$line['taxon_id'].'_'.$line['genetic_description']]=$line['assay_genetic_id'];
	}

	/// DATA will have the current assay_target_genetic_map records
	$DATA=array();
	$res=runQuery("SELECT * FROM ".$SCHEMA.".assay_target_genetic_map");
	if ($res===false)													failProcess($JOB_ID."F02",'Unable to get assay_target_genetic_map');
	$MAX_DBID=-1;
	foreach ($res as $line)
	{
		$DATA[$line['assay_target_id']][$line['assay_genetic_id']]=array('DBID'=>$line['assay_target_genetic_map_id'],'is_homologue'=>$line['is_homologue'],'DB_STATUS'=>'FROM_DB');
		$MAX_DBID=max($MAX_DBID,$line['assay_target_genetic_map_id']);
	}
		

	/// Open the file for new records
	$fp=fopen('INSERT/assay_target_genetic.csv','w'); if (!$fp)			failProcess($JOB_ID."F03",'Unable to open assay_target_genetic.csv');
	$HAS_NEW=false;

	/// Getting all the ChEMBL target that are not protein -> so they are genetic
	$res=runQuery("SELECT td.chembl_id,accession,c.tax_id,description,homologue 
	FROM public.target_dictionary td,public.target_components t, public.component_sequences c 
	WHERE td.tid=t.tid AND c.component_id = t.component_id 
	AND component_type!='PROTEIN'");
	if ($res===false) 							failProcess($JOB_ID."F04",'Unable to get target_dictionary');
	foreach ($res as $line)
	{
		print_R($line);
		///Getting corresponding db ids:
		$ASSAY_GENETIC_ID=NULL;
		if ($line['accession']!='')$ASSAY_GENETIC_ID=$DNA[$line['accession']];
		else
		{
			$TAXON_ID=$STATIC_DATA['TAX']["'".$line['tax_id']."'"];
			$ASSAY_GENETIC_ID=$DNA[$TAXON_ID.'_'.$line['description']];
		}
		
		$ASSAY_TARGET_ID=$MAP_TARGET[$line['chembl_id']];

		/// Check if exists-> otherwise add
		if (!isset($DATA[$ASSAY_TARGET_ID][$ASSAY_GENETIC_ID]))
		{
			++$MAX_DBID;
			fputs($fp,$MAX_DBID."\t".$ASSAY_TARGET_ID."\t".$ASSAY_GENETIC_ID."\t".$line['homologue']."\n");
			$HAS_NEW=true;
		}
		/// If we have that association in the database, we check if the homologue status has changed
		else
		{
			$DATA[$ASSAY_TARGET_ID][$ASSAY_GENETIC_ID]['DB_STATUS']='VALID';
			if ($DATA[$ASSAY_TARGET_ID][$ASSAY_GENETIC_ID]['is_homologue']!=$line['homologue'])
			{
				if (!runQueryNoRes("UPDATE ".$SCHEMA.".assay_target_genetic_map 
				SET is_homologue=".$line['homologue'].' 
				where assay_target_genetic_map_id='.$DATA[$ASSAY_TARGET_ID][$ASSAY_GENETIC_ID]['DBID']))
				failProcess($JOB_ID."F05",'Unable to update assay_target_genetic_map');
			}

		}
	}

	/// We checked for those genetic and those targets if they are in the database and added the new records
	/// Now we are going to check for the records that are in the database but not in the ChEMBL data
	/// And that we are going to delete them
	$TO_DEL=array();
	foreach ($DATA as $assay_target_id=>&$LIST_P)
	foreach ($LIST_P as $assay_genetic_id=>&$E)
	{
		if ($E['DB_STATUS']=='FROM_DB')$TO_DEL[]=$E['DBID'];
	}
	if ($TO_DEL!=array())
	{
		if (!runQueryNoRes("DELETE FROM ".$SCHEMA.".assay_target_genetic_map 
							where assay_target_genetic_map_id IN (".implode(',',$TO_DEL).')'))
		failProcess($JOB_ID."F06",'Unable to delete assay_target_genetic_map');
	}

	/// If we have new records, we insert them
	if ($HAS_NEW)
	{
		fclose($fp);
		$command='\COPY '.$SCHEMA.'.assay_target_genetic_map (assay_target_genetic_map_id,assay_target_id,assay_genetic_id,is_homologue) FROM \'INSERT/assay_target_genetic.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )failProcess($JOB_ID."F07",'Unable to insert assay_target_genetic_map'); 
	
	}
}





function processTargetType($SCHEMA)
{
	global $JOB_ID;
	//// Process Type of targets
	//// This is a small table, so we don't have to create a file for that
	/// First we get the data from the Biorels schema
	$DATA=array();
	$res=runQuery("SELECT * FROM ".$SCHEMA.".assay_target_type");
	if ($res===false)													failProcess($JOB_ID."G01",'Unable to get assay_target_type');


	$MAX_DBID=-1;	// We need to get the maximum ID to be able to add new records
	$HAS_NEW=false;	// We will set that to true if we have new records
	
	foreach ($res as $line)
	{
		/// Add DB_STATUS to the data
		$line['DB_STATUS']='FROM_DB';	
		$DATA[$line['assay_target_type_name']]=$line;
		$MAX_DBID=max($MAX_DBID,$line['assay_target_type_id']);
	}

	/// Then we get the data from ChEMBL
	$res=runQuery("SELECT * FROM public.target_type");
	if ($res===false)												failProcess($JOB_ID."G02",'Unable to get target_type');
	foreach ($res as $line)
	{
		if (!isset($DATA[$line['target_type']]))
		{
			++$MAX_DBID;
			if ($line['parent_type']=='')$line['parent_type']='NULL';
			else $line['parent_type']="'".$line['parent_type']."'";
			$query ='INSERT INTO '.$SCHEMA.'.assay_target_type(
				assay_target_type_id,
				assay_target_type_name,
				assay_target_type_desc,
				assay_target_type_parent) 
			VALUES ('.$MAX_DBID.",
			'".$line['target_type']."',
			'".$line['target_desc']."',
			".$line['parent_type'].")";
			if (!runQueryNoRes($query))						failProcess($JOB_ID."G03",'Unable to insert assay_target_type');
		}
	}
}
	




function findMergedTaxon($TAX_ID)
{
	global $TG_DIR;
	global $JOB_ID;

	/// Sometime, Taxons are replaced by another one. We need to find the new one.
	/// However, if the record is old, it might have been replaced multiple times
	/// So we need to loop until we find the final one

	/// Cleaning up the taxon_id
	$TAX_ID=str_replace("'","",$TAX_ID);

	/// Going to the taxonomy directory
	
	$PATH=$TG_DIR.'/PRD_DATA/TAXONOMY/';
	if (!is_dir($PATH))												failProcess($JOB_ID."H01",'Unable to find PRD DIR for taxonomy'); 
	if (!is_file($PATH.'/merged.dmp'))								failProcess($JOB_ID."H02",'Unable to find merged.dmp in taxonomy PRD dir'); 
	
	
	$MERGED=array();
	/// merged.dmp contains the list of merged records
	$fp=fopen($PATH.'/merged.dmp','r');if (!$fp)					failProcess($JOB_ID."H03",'Unable to open merged.dmp'); 
	while(!feof($fp))
	{
		$line=stream_get_line($fp,10000,"\n");	if ($line=='')continue;
		$tab=explode("|",$line);				if (count($tab)!=3)continue;

		$FORMER=trim($tab[0]);	/// Tax ID being replaced
		$NEW=trim($tab[1]);		/// Replaced by this Tax id
		$MERGED[$FORMER]=$NEW;
	}
	fclose($fp);

	echo "REQUESTED TAXON : ".$TAX_ID."\n";
	
	do{
		/// We ensure that the next taxID is in the list. Otherwise, we are done
		if (!isset($MERGED[$TAX_ID]))break;
		$TAX_ID=$MERGED[$TAX_ID];
		echo "ALTERNATE TAXON : ".$TAX_ID."\n";
	}while(1);

	/// Getting the corresponding Primary Key taxon_id for this taxon
	$res=runQuery("SELECT taxon_id FROM taxon where tax_Id ='".$TAX_ID."'");
	if ($res===false)												failProcess($JOB_ID."H04",'Unable to get taxon_id');
	if (count($res)==0)return 'NULL';
	return $res[0]['taxon_id'];
}






function processDNA_RNA_Component($SCHEMA)
{
	addLog("Process DNA RNA Component");
	global $GLB_VAR;
	global $DB_INFO;
	global $JOB_ID;

	/// For a given genetic assay, find the corresponding RNA or DNA sequence

	addLog("\tGet data from database");

	/// First, getting existing data from Biorels schema
	$DATA=array();
	$res=runQuery("SELECT * FROM ".$SCHEMA.".assay_genetic");
	if ($res===false)													failProcess($JOB_ID."I01",'Unable to get assay_genetic');
	$MAX_DBID=-1;
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		if ($line['accession']!='')$DATA[$line['accession']]=$line;
		/// Data is grouped by taxon_id+genetic_description
		$DATA[$line['taxon_id'].'_'.$line['genetic_description']]=$line;

		/// We also get the maximum ID to be able to add new records
		$MAX_DBID=max($MAX_DBID,$line['assay_genetic_id']);
	}


	$HAS_NEW=false;
	$fp=fopen('INSERT/assay_genetic.csv','w');	if (!$fp)				failProcess($JOB_ID."I02",'Unable to open assay_genetic.csv');
	addLog("\tGet components from ChEMBL");

	$res=runQuery("SELECT * FROM public.component_sequences where component_type!='PROTEIN'");
	if ($res===false)												failProcess($JOB_ID."I03",'Unable to get component_sequences');
	
	/// Prior to processing, we get all the taxon and accession ids to get their PK
	$LIST=array('TAX'=>array(),'ACC'=>array());
	foreach ($res as $line)
	{
	if ($line['tax_id']!='')	$LIST['TAX']["'".$line['tax_id']."'"]='NULL';
	if ($line['accession']!='')	$LIST['ACC']["'".$line['accession']."'"]=array('NULL','');
	}

	/// Getting taxon
	$res2=runQuery("SELECT taxon_id, tax_id 
					FROM taxon 
					where tax_id IN (".implode(',',array_keys($LIST['TAX'])).')');
	if ($res2===false)											failProcess($JOB_ID."I04",'Unable to get taxon_id');
	foreach ($res2 as $line)$LIST['TAX']["'".$line['tax_id']."'"]=$line['taxon_id'];
	/// If any Taxon does not have a corresponding taxon_id, we need to find the merged taxon_id, i.e the new taxon id
	foreach ($LIST['TAX'] as $TAX_ID=>&$TAX_DBID)if ($TAX_DBID=='NULL')$TAX_DBID=findMergedTaxon($TAX_ID);

	// Search gene_seq_id and transcript_id
	$res2=runQuery("SELECT gene_seq_id, gene_seq_name 
					FROM gene_seq 
					where gene_seq_name IN (".implode(',',array_keys($LIST['ACC'])).')');
	if ($res2===false)										failProcess($JOB_ID."I05",'Unable to get gene_seq_id');
	foreach ($res2 as $line)$LIST['ACC']["'".$line['gene_seq_name']."'"]=array($line['gene_seq_id'],'GENE_SEQ');

	$res2=runQuery("SELECT transcript_id,transcript_name 
					FROM transcript 
					where transcript_name IN (".implode(',',array_keys($LIST['ACC'])).')');
	if ($res2===false)									failProcess($JOB_ID."I06",'Unable to get transcript_id');
	foreach ($res2 as $line)$LIST['ACC']["'".$line['transcript_name']."'"]=array($line['transcript_id'],'TRANSCRIPT');





	addLog("\tCompare data");
	foreach ($res as $line)
	{
		// Getting taxon_id
		$TAX_ID='NULL';
		if (isset($LIST['TAX']["'".$line['tax_id']."'"]))$TAX_ID=$LIST['TAX']["'".$line['tax_id']."'"];


		if ($line['accession']!='')
		{
			if (isset($DATA[$line['accession']]))	
			{
				$E=&$DATA[$line['accession']];
				if ($E['genetic_description']!=$line['description'])
				{
					$line['description']=str_replace("'","''",$line['description']);
					if(!runQueryNoRes("UPDATE ".$SCHEMA.".assay_genetic 
										SET genetic_description = '".$line['description']."' 
										WHERE assay_genetic_id=".$E['assay_genetic_id']))
					{
						failProcess($JOB_ID."I07",'Unable to update assay_genetic'); 
					}
				}
			}
			else 
			{
				/// New data.
				++$MAX_DBID;	/// Increase PK
				/// Set default
				$TR_ID='NULL';
				$GENE_SEQ_ID='NULL';
				/// If we have the accession in the gene_seq or transcript table, we get the corresponding PK
				if (isset($LIST['ACC']["'".$line['accession']."'"]))
				{
					$ACC=&$LIST['ACC']["'".$line['accession']."'"];
					if ($ACC[1]=='GENE_SEQ')$GENE_SEQ_ID=$ACC[0];
					else if ($ACC[0]=='TRANSCRIPT')$TR_ID=$ACC[0];
				}
				$HAS_NEW=true;
				fputs($fp,$MAX_DBID."\t".$line['description']."\t".$TAX_ID."\t".$GENE_SEQ_ID."\t".$TR_ID."\t".$line['accession']."\t".$line['sequence']."\n");
			}

		}
		else
		{
			/// If we don't have the accession, we use the taxon_id and the description to get the PK
			if (!isset($DATA[$TAX_ID.'_'.$line['description']]))	
			{
				++$MAX_DBID;
				$TR_ID='NULL';
				$GENE_SEQ_ID='NULL';
				$HAS_NEW=true;
				fputs($fp,$MAX_DBID."\t".$line['description']."\t".$TAX_ID."\t".$GENE_SEQ_ID."\t".$TR_ID."\t".$line['accession']."\t".$line['sequence']."\n");
			}
		}
		
		
	}




	addLog("\tInsert data");
	if ($HAS_NEW)
	{
	fclose($fp);
	$command='\COPY '.$SCHEMA.'.assay_genetic (assay_genetic_id,genetic_description,taxon_id,gene_seq_id,transcript_id, accession,sequence) FROM \'INSERT/assay_genetic.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )failProcess($JOB_ID."I08",'Unable to insert assay_genetic'); 
	}
}


function processAssayType($SCHEMA)
{
	addLog("Process Assay type");
	addLog("\tGet Assay type from database");
	$res=runQuery("SELECT * FROM ".$SCHEMA.".assay_type");
	if ($res===false) 							failProcess($JOB_ID."J01",'Unable to get assay_type');
	
	/// Getting all the assay type from the database
	$DATA=array();
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$DATA[$line['assay_type_id']]=$line;
	}



	addLog("\t Get assay type from ChEMBL");
	$res=runQuery("SELECT * FROM public.assay_type");
	foreach ($res as $line)
	{
		if (isset($DATA[$line['assay_type']]))
		{
			$E=&$DATA[$line['assay_type']];
			$E['DB_STATUS']='VALID';
			if ($line['assay_desc']!=$E['assay_desc'] )
			{
				if (!runQueryNoRes("UPDATE ".$SCHEMA.".assay_type 
									SET assay_desc = '".$line['assay_desc']."' 
									WHERE assay_type_id = ".$E['assay_type']))
				failProcess($JOB_ID."J02",'Unable to update assay_type');

			}
		}
		else
		{
			if (!runQueryNoRes("INSERT INTO ".$SCHEMA.".assay_type (assay_type_id, assay_desc) VALUES
									('".$line['assay_type']."', '".$line['assay_desc']."')"))
			failProcess($JOB_ID."J03",'Unable to insert assay_type');

		}
	}

	/// We checked for those assay type if they are in the database and added the new records
	/// Now we are going to check for the records that are in the database but not in the ChEMBL data
	/// And that we are going to delete them
	foreach ($DATA as $assay_type_id=>&$info)
	{
		if ($info['DB_STATUS']!='FROM_DB')continue;
		if (!runQueryNoRes("DELETE FROM ".$SCHEMA.".assay_type WHERE assay_type_id = ".$assay_type_id))
		failProcess($JOB_ID."J04",'Unable to delete assay_type');
	}
	
}

function processConfidence($SCHEMA)
{
	/// Process confidence score

	addLog("Process Confidence");
	$res=runQuery("SELECT * FROM ".$SCHEMA.".assay_confidence");
	if ($res===false) 															failProcess($JOB_ID."K01",'Unable to get assay_confidence');
	$DATA=array();$MAX=-1;
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$DATA[$line['confidence_score']]=$line;
		
	}

	/// Getting all the confidence score from the Chembl database
	$res=runQuery("SELECT * FROM public.confidence_score_lookup");
	if ($res===false) 															failProcess($JOB_ID."K02",'Unable to get confidence_score_lookup');
	foreach ($res as $line)
	{
		/// Already exists
		if (isset($DATA[$line['confidence_score']]))
		{
			/// We set it up to VALID by default. then we check for differences
			$E=&$DATA[$line['confidence_score']];
			$E['DB_STATUS']='VALID';
			if ($line['description']!=$E['description'] || $line['target_mapping']!=$E['target_mapping'])
			{
				if (!runQueryNoRes("UPDATE ".$SCHEMA.".assay_confidence 
					SET description = '".$line['description']."', 
						target_mapping='".$line['target_mapping']."' 
					WHERE confidence_score = ".$E['confidence_score']))			failProcess($JOB_ID."K03",'Unable to update assay_confidence');

			}
		}
		else
		{
			if (!runQueryNoRes("INSERT INTO ".$SCHEMA.".assay_confidence 
			(description, target_mapping,confidence_score) 
			VALUES
			('".$line['description']."', '".$line['target_mapping']."',".$line['confidence_score'].')'))
																			failProcess($JOB_ID."K04",'Unable to insert assay_confidence');

		}
	}

	/// We checked for those confidence score if they are in the database and added the new records
	/// Now we are going to check for the records that are in the database but not in the ChEMBL data
	/// And that we are going to delete them
	foreach ($DATA as $CONF=>$D)
	{
		if ($D['DB_STATUS']!='FROM_DB')continue;
		if (!runQueryNoRes("DELETE FROM  ".$SCHEMA.".assay_confidence 
							WHERE confidence_score = ".$CONF))				failProcess($JOB_ID."K05",'Unable to delete assay_confidence');
	}

}






function processAssay($SCHEMA)
{
	addLog("PROCESS ASSAY");
	global $GLB_VAR;
	global $DB_INFO;
	global $source_id;
	global $JOB_ID;
	global $STATIC_DATA;

	/// First, we get the maximum primary key Value for the assay_entry table
	$res=runQuery("SELECT MAX(assay_entry_id) co FROM ".$SCHEMA.".assay_entry ");
	if ($res===false)																failProcess($JOB_ID."L01",'Unable to get Max ID for '.$TBL);

	$MAX_ASSAY_ID=($res[0]['co']=='')?0:$res[0]['co'];
	
	/// Open the file for new records
	$fp=fopen('INSERT/assay_entry.csv','w');	if (!$fp)							failProcess($JOB_ID."L02",'Unable to open assay_entry.csv');

	/// Getting all taxons, bao_format, cell_id, tissue_id, mutation, target_id
	/// First taxon
	$STATIC_DATA['TAX']=array();
	$res=runQuery("SELECT DISTINCT assay_tax_id 
				FROM public.assays 
				where assay_tax_id IS NOT NULL");
	if ($res===false)																failProcess($JOB_ID."L03",'Unable to get assay_tax_id');
	if ($res==array())																failProcess($JOB_ID."L04",'No assay_tax_id');
	/// Create Biorels query
	$query='SELECT tax_id, taxon_id FROM taxon where tax_id IN (';
	foreach ($res as $line)
	{
		$STATIC_DATA['TAX'][$line['assay_tax_id']]='NULL';
		$query.="'".$line['assay_tax_id']."',";
	}
	$res=runQuery(substr($query,0,-1).')');
	if ($res===false)																failProcess($JOB_ID."L05",'Unable to get taxon_id');
	
	foreach ($res as $line)$STATIC_DATA['TAX'][$line['tax_id']]=$line['taxon_id'];
	
	/// If any Taxon does not have a corresponding taxon_id, we need to find the merged taxon_id, i.e the new taxon id
	foreach ($STATIC_DATA['TAX'] as $TAX_ID=>&$TAX_DBID)if ($TAX_DBID=='NULL')$TAX_DBID=findMergedTaxon($TAX_ID);


	/// Next bao_format
	$res=runQuery("SELECT DISTINCT bao_format FROM public.assays");
	if ($res===false)																failProcess($JOB_ID."L06",'Unable to get bao_format');
	if ($res==array())																failProcess($JOB_ID."L07",'No bao_format');

	$query='select bioassay_tag_id,bioassay_onto_entry_id FROM bioassay_onto_entry WHERE bioassay_tag_id IN (';
	foreach ($res as $line)$query.="'".$line['bao_format']."',";
	$res=runQuery(substr($query,0,-1).')');
	if ($res===false)																failProcess($JOB_ID."L08",'Unable to get bioassay_onto_entry_id');

	/// We create a static array to get the bioassay_onto_entry_id from the bioassay_tag_id
	$STATIC_DATA['BAO']=array();
	foreach ($res as $line)$STATIC_DATA['BAO'][$line['bioassay_tag_id']]=$line['bioassay_onto_entry_id'];
	
	
	/// Then cell_id
	$res=runQuery("SELECT assay_cell_id, chembl_id FROM ".$SCHEMA.".assay_cell");
	if ($res===false)																failProcess($JOB_ID."L09",'Unable to get assay_cell_id');
	foreach ($res as $line)$STATIC_DATA['CELL'][$line['chembl_id']]=$line['assay_cell_id'];


	/// Then tissue_id
	$res=runQuery("SELECT assay_tissue_id, assay_tissue_name FROM ".$SCHEMA.".assay_tissue");
	if ($res===false)																failProcess($JOB_ID."L10",'Unable to get assay_tissue_id');
	foreach ($res as $line)$STATIC_DATA['TISSUE'][$line['assay_tissue_name']]=$line['assay_tissue_id'];


	/// Then mutation
	$res=runQuery("SELECT assay_variant_id, mutation_list,ac FROM ".$SCHEMA.".assay_variant");
	if ($res===false)																failProcess($JOB_ID."L11",'Unable to get assay_variant_id');
	foreach ($res as $line)$STATIC_DATA['MUTATION'][$line['ac'].'_'.$line['mutation_list']]=$line['assay_variant_id'];



	$res=runQuery("SELECT assay_target_id, assay_target_name FROM ".$SCHEMA.".assay_target");
	if ($res===false)																failProcess($JOB_ID."L12",'Unable to get assay_target_id');
	foreach ($res as $line)$STATIC_DATA['TARGET'][$line['assay_target_name']]=$line['assay_target_id'];


	/// Now we are ready to process the assay information.
	/// However, it's too much to process in one go. So we are going to process it in chunks of 1000
	/// First, we need to get the mapping between the ChEMBL assay name and the Biorels assay_entry_id
	$res=runQuery("SELECT assay_entry_id, assay_name FROM ".$SCHEMA.".assay_entry where source_id = ".$source_id);
	if ($res===false)																failProcess($JOB_ID."L13",'Unable to get assay_entry_id');
	$INI_LIST=array();
	foreach ($res as $line)$INI_LIST[$line['assay_name']]=array($line['assay_entry_id'],'FROM_DB');

	///	We get all the assay names from the database
	$res=runQuery("SELECT DISTINCT chembl_id FROM public.assays ");
	if ($res===false)																failProcess($JOB_ID."L14",'Unable to get chembl_id');

	/// And we chunk them
	$LIST_CHEMBL_ASSAY=array();
	foreach ($res as $line)$LIST_CHEMBL_ASSAY[]="'".$line['chembl_id']."'";
	$CHUNKS=array_chunk($LIST_CHEMBL_ASSAY,1000);

	/// We then process each chunk
	foreach ($CHUNKS as $N_C=> $CHUNK)
	{
		addLog("Process Assays ".$N_C.'/'.count($CHUNKS));
		
		$DATA=array();
		$t=microtime_float();

		/// Getting all the information from Biorels Schema
		$query="SELECT * FROM ".$SCHEMA.".assay_entry
				 where assay_name IN (".implode(',',$CHUNK).') 
				 AND source_id = '.$source_id;

		
		$res=runQuery($query);
		if ($res===false)															failProcess($JOB_ID."L15",'Unable to get assay_entry');

		echo "GET ASSAY ".round(microtime_floaT()-$t,2)."\n";$t=microtime_float();
		foreach ($res as $line)$DATA[$line['assay_name']]=$line;
		
		
		/// Getting all the information from ChEMBL
		$res=runQuery("SELECT a.*, c.chembl_id as cell_id , t.pref_name as tissue_name,
			td.chembl_id as target_id,
		mutation,accession
		FROM public.assays a 
		LEFT JOIN public.cell_dictionary c ON c.cell_id = a.cell_id 
		LEFT JOIN public.tissue_dictionary t ON t.tissue_id = a.tissue_id 
		LEFT JOIN public.variant_sequences v ON v.variant_id=  a.variant_id
		LEFT JOIN public.target_dictionary td ON td.tid = a.tid
		where a.chembl_id IN (".implode(',',$CHUNK).')');
		if ($res===false)															failProcess($JOB_ID."L16",'Unable to get assay');


		echo "GET ASSAY FROM CHEMBL".round(microtime_floaT()-$t,2)."\n";$t=microtime_float();
		$HAS_NEW=false;
		foreach ($res as $ch_as)
		{

			$ch_as['assay_variant_id']='NULL';
			/// We set everything as Null by default
			foreach ($ch_as as $K=>&$V)if ($V=='')$V='NULL';

			/// We get the corresponding db identifiers from the database
			if (isset($STATIC_DATA['TARGET'][$ch_as['target_id']]))$ch_as['target_id']=$STATIC_DATA['TARGET'][$ch_as['target_id']];
			if (isset($STATIC_DATA['CELL'][$ch_as['cell_id']]))$ch_as['cell_id']=$STATIC_DATA['CELL'][$ch_as['cell_id']];
			if (isset($STATIC_DATA['BAO'][$ch_as['bao_format']]))$ch_as['bao_format']=$STATIC_DATA['BAO'][$ch_as['bao_format']];
			if (isset($STATIC_DATA['TISSUE'][$ch_as['tissue_name']]))$ch_as['tissue_name']=$STATIC_DATA['TISSUE'][$ch_as['tissue_name']];
			if (isset($STATIC_DATA['TAX'][$ch_as['assay_tax_id']]))$ch_as['assay_tax_id']=$STATIC_DATA['TAX'][$ch_as['assay_tax_id']];
			if (isset($STATIC_DATA['MUTATION'][$ch_as['accession'].'_'.$ch_as['mutation']]))$ch_as['assay_variant_id']=$STATIC_DATA['MUTATION'][$ch_as['accession'].'_'.$ch_as['mutation']];
			$ch_as['curated_by']=substr($ch_as['curated_by'],0,1);

			if (isset($INI_LIST[$ch_as['chembl_id']]))				$INI_LIST[$ch_as['chembl_id']][1]='VALID';
			
			/// If we don't have that assay in the database, we add it
			if (!isset($DATA[$ch_as['chembl_id']]))
			{
				$HAS_NEW=true;
				++$MAX_ASSAY_ID;
				fputs($fp,$MAX_ASSAY_ID."\t".$ch_as['chembl_id']."\t".'"'.str_replace('"','""',$ch_as['description']).'"'.
				"\t".$ch_as['assay_type'].
				"\t".$ch_as['assay_test_type'].
				"\t".$ch_as['assay_category'].
				"\t".$ch_as['curated_by'].
				"\t".$ch_as['bao_format'].
				"\t".$ch_as['cell_id'].
				"\t".$ch_as['tissue_name'].
				"\t".$ch_as['assay_tax_id'].
				"\t".$ch_as['assay_variant_id'].
				"\t".$ch_as['target_id'].
				"\t".$ch_as['confidence_score'].
				"\t".$source_id."\n");

			}
			else
			{
				/// We need to compare and update the data
				$E=&$DATA[$ch_as['chembl_id']];
				foreach ($E as $K=>&$V)if ($V=='')$V='NULL';
				$query='';
				if ($ch_as['description']!=$E['assay_description'])$query.= "assay_description = '".str_replace("'","''",$ch_as['description'])."',";
				if ($ch_as['assay_type']!=$E['assay_type'])$query.= "assay_type = '".str_replace("'","''",$ch_as['assay_type'])."',";
				if ($ch_as['assay_test_type']!=$E['assay_test_type'])$query.= "assay_test_type = '".str_replace("'","''",$ch_as['assay_test_type'])."',";
				if ($ch_as['assay_category']!=$E['assay_category'])$query.= "assay_category = '".str_replace("'","''",$ch_as['assay_category'])."',";
				if ($ch_as['curated_by']!=$E['curated_by'])$query.= "curated_by = '".$ch_as['curated_by']."',";
				if ($ch_as['bao_format']!=$E['bioassay_onto_entry_id'])$query.= "bioassay_onto_entry_id = ".$ch_as['bao_format'].',';
				if ($ch_as['cell_id']!=$E['assay_cell_id'])$query.= "assay_cell_id = ".$ch_as['cell_id'].',';
				if ($ch_as['tissue_name']!=$E['assay_tissue_id'])$query.= "assay_tissue_id = ".$ch_as['tissue_name'].',';
				if ($ch_as['assay_tax_id']!=$E['taxon_id'])$query.= "taxon_id = ".$ch_as['assay_tax_id'].',';
				if ($ch_as['target_id']!=$E['assay_target_id'])$query.= "assay_target_id = ".$ch_as['target_id'].',';
				if ($ch_as['assay_variant_id']!=$E['assay_variant_id'])$query.= "assay_variant_id = ".$ch_as['assay_variant_id'].',';
				if ($ch_as['confidence_score']!=$E['confidence_score'])$query.= "confidence_score = ".$ch_as['confidence_score'].',';
				if ($query=='')continue;
				$query='UPDATE '.$SCHEMA.'.assay_entry SET '.substr($query,0,-1).' WHERE assay_entry_Id = '.$E['assay_entry_id'];
				if (!runQueryNoRes($query) )failProcess($JOB_ID."L17",'Unable to update assay_entry'); 		
				echo "D".round(microtime_float()-$t,3)."\n";$t=microtime_float();
				echo $query."\n";
				echo "UPDATE\n";
				
			}
		}
		
		/// New data for this batch, we insert it
		if ($HAS_NEW)
		{
		fclose($fp);
		$command='\COPY '.$SCHEMA.'.assay_entry (assay_entry_id,assay_name,assay_description,assay_type,assay_test_type,assay_category,curated_by,bioassay_onto_entry_id,assay_cell_id,assay_tissue_id,taxon_id,assay_variant_id,assay_target_id,confidence_score,source_id) FROM \'INSERT/assay_entry.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
		echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
		system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
		if ($return_code !=0 )failProcess($JOB_ID."L18",'Unable to insert assay_entry'); 
		$fp=fopen('INSERT/assay_entry.csv','w');
		}

		
	}

	/// Then we delete the records that are in the database but not in the ChEMBL data
	$TO_DEL=array();
	foreach ($INI_LIST as $CHEMBL_NAME=>$INFO_CHEMBL)
	{
		if ($INFO_CHEMBL[1]=='FROM_DB')$TO_DEL[]=$INFO_CHEMBL[0];
	}
	if ($TO_DEL!=array())
	{
		$query='DELETE FROM '.$SCHEMA.'.ASSAY_ENTRY WHERE ASSAY_ENTRY_ID IN ('.implode(',',$TO_DEL).')';
		if (!runQueryNoReS($query))failProcess($JOB_ID."L19",'Unable to delete assay_entry'); 
	}


}



function processCellLine($SCHEMA)
{
	addLog("Process cell lines");
	global $MAIL_COMMENTS;
	global $GLB_VAR;
	global $DB_INFO;

	/// Getting all cell lines -related assays from Biorels
	$res=runQuery("SELECT assay_cell_id, a.cell_name,cell_description,cell_source_tissue, 
					chembl_id, a.taxon_id, a.cell_entry_id,tax_id, cell_acc 
					FROM ".$SCHEMA.".assay_cell a 
					LEFT JOIN taxon t ON t.taxon_id = a.taxon_id
					LEFT JOIN cell_entry c ON c.cell_entry_id = a.cell_Entry_id WHERE chembl_id IS NOT NULL");
	if ($res===false)															failProcess($JOB_ID."M01",'Unable to get assay_cell');
	$DATA=array();
	/// Maximum primary key value
	$MAXDBID=-1;
	/// Static data: taxon_id and cell_entry_id
	$TAXONS=array('NULL'=>'NULL');
	$CELL=array('NULL'=>'NULL');
	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';/// We set it up to FROM_DB by default. 
		$TAXONS[$line['tax_id']]=$line['taxon_id'];
		$CELL[$line['cell_acc']]=$line['cell_entry_id'];
		$DATA[$line['chembl_id']]=$line;
		$MAXDBID=max($MAXDBID,$line['assay_cell_id']);
	}

	$MISS_CELL=array();
	$MISS_TAX=array();

	/// Getting data from chembl
	$res=runQuery("SELECT cell_name,cell_description, cell_source_tax_id, cell_source_tissue, chembl_id, cellosaurus_id 
				   FROM public.cell_dictionary");
	if ($res===false)														failProcess($JOB_ID."M02",'Unable to get cell_dictionary');

	foreach ($res as $line)
	{
		/// If we don't have that taxon already, we add it to the list of missing taxon
		if ($line['cell_source_tax_id']!='' && !isset($TAXONS[$line['cell_source_tax_id']]))
		{
			$TAXONS[$line['cell_source_tax_id']]='NULL';
			$MISS_TAX[]="'".$line['cell_source_tax_id']."'";
		}
		/// If we don't have that cell already, we add it to the list of missing cell
		if ($line['cellosaurus_id']!='' && !isset($CELL[$line['cellosaurus_id']]))
		{
			$CELL[$line['cellosaurus_id']]='NULL';
			$MISS_CELL[]="'".$line['cellosaurus_id']."'";
		}
	}

	/// We then get the corresponding taxon_id and cell_entry_id
	if ($MISS_CELL!=array())
	{
		$res2=runQuery("SELECT cell_acc,cell_entry_id FROM cell_entry where cell_acc IN (".implode(",",$MISS_CELL).')');
		if ($res2===false)												failProcess($JOB_ID."M03",'Unable to get cell_entry_id');
		foreach ($res2 as $l2)$CELL[$l2['cell_acc']]=$l2['cell_entry_id'];
	}
	if ($MISS_TAX!=array())
	{
		$res2=runQuery("SELECT tax_id, taxon_id FROM taxon where tax_id IN (".implode(",",$MISS_TAX).')');
		if ($res2===false)												failProcess($JOB_ID."M04",'Unable to get taxon_id');
		foreach ($res2 as $l2)$TAXONS[$l2['tax_id']]=$l2['taxon_id'];
		foreach ($MISS_TAX as $TX)
		{
			/// And of course if it's an obsolete taxon, we need to find the new taxon_id
			if (!isset($TAXONS[substr($TX,1-1)]))
			$TAXONS[substr($TX,1-1)]=findMergedTaxon(substr($TX,1-1));
		}
	}

	/// We then compare the data and insert the new records
	$fp=fopen('INSERT/assay_cell.csv','w');if (!$fp)					failProcess($JOB_ID."M05",'Unable to open assay_cell.csv');
	foreach ($res as $line)
	{
		/// Exist? nothing to do
		if (isset($DATA[$line['chembl_id']]))
		{
			$DATA[$line['chembl_id']]['DB_STATUS']='VALID';
		}
		else
		{
			/// New record
			++$MAXDBID;
			/// The file accepts NULL values, not empty values
			if ($line['cellosaurus_id']=='')$line['cellosaurus_id']='NULL';
			if ($line['cell_source_tax_id']=='')$line['cell_source_tax_id']='NULL';
			if ($line['cell_description']=='')$line['cell_description']='NULL';
			if ($line['cell_source_tissue']=='')$line['cell_source_tissue']='NULL';
			fputs($fp,$MAXDBID."\t".'"'.$line['cell_name'].'"'."\t".'"'.$line['cell_description'].'"'."\t".'"'.$line['cell_source_tissue'].'"'."\t".$line['chembl_id']."\t".$TAXONS[$line['cell_source_tax_id']]."\t".$CELL[$line['cellosaurus_id']]."\n");
			//assay_cell_id, a.cell_name,cell_description,cell_source_tissue, chembl_id, a.taxon_id, a.cell_entry_id

		}
	}
	fclose($fp);
	$command='\COPY '.$SCHEMA.'.assay_cell (assay_cell_id, cell_name,cell_description,cell_source_tissue, chembl_id, taxon_id, cell_entry_id) FROM \'INSERT/assay_cell.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )failProcess($JOB_ID."M06",'Unable to insert assay_tissue'); 
	

	/// We then delete the records that are in the database but not in the ChEMBL data
	$TO_DEL=array();
	foreach ($DATA as $CHEMBL_NAME=>$INFO_CHEMBL)
	{
		if ($INFO_CHEMBL['DB_STATUS']=='FROM_DB')$TO_DEL[]=$INFO_CHEMBL['assay_cell_id'];
	}
	if ($TO_DEL!=array())
	{
		$query='DELETE FROM '.$SCHEMA.'.ASSAY_CELL WHERE ASSAY_CELL_ID IN ('.implode(',',$TO_DEL).')';
		if (!runQueryNoReS($query))failProcess($JOB_ID."M07",'Unable to delete assay_cell');
	}


}


function processTissue($SCHEMA)
{
	addLog("Process Tissue");
	global $MAIL_COMMENTS;
	global $GLB_VAR;
	global $DB_INFO;
	/// STEP 1 : tissues
	addLog("\tGet data from assay_tissue");
	$res=runQuery("SELECT assay_tissue_id, assay_tissue_name,aa.anatomy_entry_id, anatomy_tag 
					FROM ".$SCHEMA.".assay_tissue aa 
					LEFT JOIN anatomy_entry a ON a.anatomy_entry_id= aa.anatomy_entry_id ");
	if ($res===false) 															failProcess($JOB_ID."N01",'Unable to get assay_tissue');
	$DATA=array();
	$MAX_ASSAY_TISSUE=-1;///	Maximum primary key value
	foreach ($res as $line)
	{
		$DATA[$line['assay_tissue_name']]=array(
			'DBID'=>$line['assay_tissue_id'],
			'TAG'=>$line['anatomy_tag'],
			'DB_STATUS'=>'FROM_DB');/// We set it up to FROM_DB by default.

		/// We get the maximum primary key value
		$MAX_ASSAY_TISSUE=max($MAX_ASSAY_TISSUE,$line['assay_tissue_id']);
	}

	addLog("\tGet data from ChEMBL");
	$res=runQuery("SELECT tissue_id, uberon_id, efo_id, pref_name FROM public.tissue_dictionary");
	if ($res===false) 															failProcess($JOB_ID."N02",'Unable to get tissue_dictionary');

	/// Based on the source, we are going to process the data differently
	$UBERON=array();
	$EFO=array();
	foreach ($res as $line)
	{
		if (!isset($DATA[$line['pref_name']]))
		{
			++$MAX_ASSAY_TISSUE;
			$DATA[$line['pref_name']]=
			array('DBID'=>$MAX_ASSAY_TISSUE,
					'DB_STATUS'=>'TO_INS',
					'ANATOMY_ENTRY_ID'=>'NULL');
			/// Create list of tissue records mapping to UBERON or EFO
			if ($line['uberon_id']!='')$UBERON["'".str_replace(":","_",$line['uberon_id'])."'"][]=$line['pref_name'];
			if ($line['efo_id']!='')$EFO["'".substr($line['efo_id'],4)."'"][]=$line['pref_name'];
		}
		else
		{
			/// Update the status, it's a valid entry
			$DATA[$line['pref_name']]['DB_STATUS']='VALID';
		}
	}

	addLog("\tFind UBERON entry");
	if ($UBERON!=array())
	{
		$res=runQuery("SELECT anatomy_tag,anatomy_entry_id 
						FROM anatomy_entry 
						where anatomy_tag IN (".implode(",",array_keys($UBERON)).')');
		if ($res===false)													failProcess($JOB_ID."N03",'Unable to get anatomy_entry');

		foreach ($res as $line)
		{
			$TAG="'".$line['anatomy_tag']."'";
			$U_E=&$UBERON[$TAG];
			/// assign to each tissue record the corresponding anatomy_entry_id
			foreach ($U_E as $N)$DATA[$N]['ANATOMY_ENTRY_ID']=$line['anatomy_entry_id'];
			/// Remove the entry from the list
			unset($UBERON[$TAG]);
		}
		/// If there are still some entries, we add them to the list of comments
		if ($UBERON!=array())
		{
			$MAIL_COMMENTS[]='<h3>Unrecognized UBERON IDs while processing ChEMBL tissues</h3><ul>';
			foreach ($UBERON as $TAG=>$NAMES)
			{
				$MAIL_COMMENTS[]='<li>'.$TAG.' - '.implode(";",$NAMES).'</li>';
			}
			$MAIL_COMMENTS[].='</ul>';
		}
	
	}
	addLog("\tFind EFO Entry");
	if ($EFO!=array())
	{
		$res=runQuery("SELECT anatomy_extdb,anatomy_entry_id 
						FROM anatomy_extdb a, source s 
						where s.source_id = a.source_id
						AND source_name='EFO' 
						AND anatomy_extdb IN (".implode(",",array_keys($EFO)).')');
		if ($res===false)												failProcess($JOB_ID."N04",'Unable to get anatomy_extdb');
		foreach ($res as $line)
		{
			$TAG="'".$line['anatomy_extdb']."'";
			if (!isset($EFO[$TAG]))continue;
			$U_E=&$EFO[$TAG];
			foreach ($U_E as $N)
			{
				if ($DATA[$N]['ANATOMY_ENTRY_ID']=='NULL')$DATA[$N]['ANATOMY_ENTRY_ID']=$line['anatomy_entry_id'];
				/// If the anatomy_entry_id is different, we have a discrepancy between EFO and UBERON
				else if ($DATA[$N]['ANATOMY_ENTRY_ID']!=$line['anatomy_entry_id'])
				{

					echo "DISCREPANCY\n";
				}
			}
			unset($EFO[$TAG]);
		}	
		if ($EFO!=array())
		{
			$MAIL_COMMENTS[]='<h3>Unrecognized EFO IDs while processing ChEMBL tissues</h3><ul>';
			foreach ($EFO as $TAG=>$NAMES)
			{
				$MAIL_COMMENTS[]='<li>'.$TAG.' - '.implode(";",$NAMES).'</li>';
			}
			$MAIL_COMMENTS[].='</ul>';
		}
	}
	
	addLog("\tPush to database");
	$fp=fopen('INSERT/ASSAY_TISSUE.csv','w');if (!$fp)  						failProcess($JOB_ID."N05",'Unable to open ASSAY_TISSUE.csv');
	$HAS_NEW=false;
	foreach ($DATA as $NAME=>$INFO)
	{
		if ($INFO['DB_STATUS']!='TO_INS')continue;
		$HAS_NEW=true;
		fputs($fp,$INFO['DBID']."\t".$NAME."\t".$INFO['ANATOMY_ENTRY_ID']."\n");
	}
	fclose($fp);

	if (!$HAS_NEW)return;
	$command='\COPY '.$SCHEMA.'.assay_tissue (assay_tissue_id   ,assay_tissue_name ,anatomy_entry_id) FROM \'INSERT/ASSAY_TISSUE.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )														failProcess($JOB_ID."N06",'Unable to insert assay_tissue'); 
}


function processVariants($SCHEMA)
{
	addLog("Process Variants");
	global $MAIL_COMMENTS;
	global $GLB_VAR;
	global $DB_INFO;
	addLog("\tGet DAta from assay_variant");
	$res=runQuery("SELECT a.ac as ac_var,assay_variant_id, mutation_list,p.prot_seq_id, pa.ac
	 FROM ".$SCHEMA.".assay_variant a 
	 LEFT JOIN prot_seq p ON a.prot_seq_id = p.prot_seq_id
	 LEFT JOIN  prot_ac pa ON p.prot_entry_id = pa.prot_entry_id");
	if ($res===false)																failProcess($JOB_ID."O01",'Unable to get assay_variant');

	$DATA=array();
	$MAX_ASS_VAR_DBID=-1;
	$MAX_POS=-1;
	$ACs=array();
	foreach ($res as $line)
	{
		//echo implode("\t",$line)."\n";
		/// Create hash map for each ac_variant identifier mapping to the assay_variant_id
		$ACs[$line['ac_var']][$line['assay_variant_id']]=true;
		$ACs[$line['ac']][$line['assay_variant_id']]=true;
		if (!isset($DATA[$line['assay_variant_id']]))
			$DATA[$line['assay_variant_id']]['INFO']=
				array(
					'DBID'		=>$line['assay_variant_id'],
					'AC_DB'		=> $line['ac_var'],
					'POS'		=>array(),
					'MUTATIONS'	=>$line['mutation_list'],
					'DB_STATUS'	=>'FROM_DB',
					'AC'		=>array($line['ac'],$line['ac_var']),
					'PROT_SEQ_ID'=>$line['prot_seq_id']);
		else 
		{
			$DATA[$line['assay_variant_id']]['INFO']['AC'][]=$line['ac'];
			$DATA[$line['assay_variant_id']]['INFO']['AC'][]=$line['ac_var'];
		}

		$MAX_ASS_VAR_DBID=max($MAX_ASS_VAR_DBID,$line['assay_variant_id']);
	}
	
	echo "MAX DBID:".$MAX_ASS_VAR_DBID."\n";

	/// Get the list of variants associated to a assay_variant
	$res=runQuery("SELECT assay_variant_pos_id, assay_variant_id, a.prot_seq_pos_id, position,letter
	 FROM ".$SCHEMA.".assay_variant_pos a 
	 LEFT JOIN prot_seq_pos p ON p.prot_seq_pos_id = a.prot_seq_pos_id  ");
	if ($res===false)	failProcess($JOB_ID.'O02','Unable to query assay_variant_pos');

	foreach ($res as $line)
	{
		$ENTRY=&$DATA[$line['assay_variant_id']];
		$line['DB_STATUS']='FROM_DB';
		$ENTRY['POS'][]=$line;
		$MAX_POS=max($MAX_POS,$line['assay_variant_pos_id']);
	}




	addLog("\tGet variant sequences");
	$res=runQuery("SELECT mutation,accession, isoform FROM public.variant_sequences ");
	if ($res===false)	failProcess($JOB_ID.'O03','Unable to query variant_sequences');
	$SEARCH_AC=array();
	// 	//print_r($res);	
	


	
$DEBUG=true;
	foreach ($res as $line)
	{
		if ($DEBUG){
			echo "\n\n\n################# START\n";
			print_r($line);
			print_r($ACs[$line['accession']]);
		}

		// The accession doesn't exist -> we create a new entry
		if (!isset($ACs[$line['accession']]))
		{
			if ($DEBUG){	
				echo "NO AC\n";
				echo "NEW:".$line['mutation']."\t".$line['accession']."\n";
			}
			///NEW
			++$MAX_ASS_VAR_DBID;
			$tab=explode(",",str_replace(' ','',$line['mutation']));
			$T2=array();foreach ($tab as $T)$T2[$T]=-1;

			$SEARCH_AC["'".$line['accession']."'"]['POS'][]=array($MAX_ASS_VAR_DBID,$line['isoform'],$T2);
			unset($NEW_ENTRY_NO_AC);
			$NEW_ENTRY_NO_AC=array();
			
			$NEW_ENTRY_NO_AC['INFO']=array(
				'DBID'=>$MAX_ASS_VAR_DBID,
				'AC_DB'=>$line['accession'],
				'AC'=>$line['accession'],
				'MUTATIONS'=>$line['mutation'],
				'DB_STATUS'=>'TO_INS',
				'PROT_SEQ_ID'=>-1,
				'isoform'=>$line['isoform']);
			
			$POS=array();
			foreach ($tab as $mut)
			{
				++$MAX_POS;
				$POS[$mut]=array(
					'assay_variant_pos_id'=>$MAX_POS,
					'prot_seq_pos_id'=>-1,
					'position'=>-1,
					'letter'=>-1,
					'DB_STATUS'=>'TO_INS');
			}
			$ACs[$line['accession']][$MAX_ASS_VAR_DBID]=true;
			$NEW_ENTRY_NO_AC['POS']=$POS;
			if ($DEBUG)echo "ADD ".$MAX_ASS_VAR_DBID."\n";
			$DATA[$MAX_ASS_VAR_DBID]=$NEW_ENTRY_NO_AC;
			continue;
		}
		if ($DEBUG)echo "EXISTING AC\n";
		
		$FOUND=false;
		foreach ($ACs[$line['accession']] as $ID=>$DUMMY)
		{

			if (!isset($DATA[$ID]))continue;
			if ($DEBUG)echo $ID."\n";
			
			$ENTRY=&$DATA[$ID];
			
			if ($ENTRY['INFO']['MUTATIONS']!=$line['mutation'] || $ENTRY['INFO']['AC_DB']!=$line['accession'])continue;

			if ($DEBUG)echo "EXISTING MUTATION\n";
			$FOUND=true;
			$ENTRY['INFO']['isoform']=$line['isoform'];
			$ENTRY['INFO']['DB_STATUS']='VALID';
			$tab=explode(",",$line['mutation']);$T2=array();
			foreach ($tab as &$MUT)
			{
				
				$MUT=trim($MUT);
			
				$letter=substr($MUT,0,1);
				$st='';for($I=1;$I<strlen($MUT);++$I)if (is_numeric(substr($MUT,$I,1)))$st.=substr($MUT,$I,1);else break;
				$pos=(int)$st;
				$FOUND_M=false;
				
				if (isset($ENTRY['POS']))
				foreach ($ENTRY['POS'] as $P)
				{
					if ($P['letter']!=$letter)continue;
					if ($P['position']!=$pos)continue;
					$FOUND_M=true;
					if ($DEBUG)echo "EXISTING POSITION\n";	
				}
				if ($FOUND_M)continue;
				if ($DEBUG)echo "NO POSITION FOUND\n";
				$T2[$MUT]=-1;
			
				++$MAX_POS;
				$ENTRY['POS'][$MUT]=array('assay_variant_pos_id'=>$MAX_POS,'prot_seq_pos_id'=>-1,'position'=>-1,'letter'=>-1,'DB_STATUS'=>'TO_INS');
			}
			if ($T2!=array())$SEARCH_AC["'".$line['accession']."'"]['POS'][]=array($ENTRY['INFO']['DBID'],$line['isoform'],$T2);
			break;
		}
		if ($FOUND)continue;
		
		++$MAX_ASS_VAR_DBID;
		if ($DEBUG)echo "BRAND NEW\t".$MAX_ASS_VAR_DBID."\n";
			$tab=explode(",",str_replace(' ','',$line['mutation']));
			$NEW_ENTRY=null;
			$NEW_ENTRY=array();
			$NEW_ENTRY['INFO']=array('DBID'=>$MAX_ASS_VAR_DBID,'AC_DB'=>$line['accession'],'AC'=>$line['accession'],'MUTATIONS'=>$line['mutation'],'DB_STATUS'=>'TO_INS','PROT_SEQ_ID'=>-1,'isoform'=>$line['isoform']);
			$POS=array();$T2=array();
			foreach ($tab as $mut)
			{
				$mut=trim($mut);
				$T2[$mut]=-1;
				if ($DEBUG)echo "\tNEW POSITION: ".$MAX_POS."\n";
				++$MAX_POS;
				$POS[$mut]=array('assay_variant_pos_id'=>$MAX_POS,'prot_seq_pos_id'=>-1,'position'=>-1,'letter'=>-1,'DB_STATUS'=>'TO_INS');
			}
			$SEARCH_AC["'".$line['accession']."'"]['POS'][]=array($MAX_ASS_VAR_DBID,$line['isoform'],$T2);
			
			$NEW_ENTRY['POS']=$POS;
			if ($DEBUG)echo "\tNEW DBID: ".$MAX_ASS_VAR_DBID."\n";
			$DATA[$MAX_ASS_VAR_DBID]=$NEW_ENTRY;
			$ACs[$line['accession']][$MAX_ASS_VAR_DBID]=true;
	
	}

	addLog("\tSearch Accession");
	if ($SEARCH_AC!=array())
	{
		$res=runQuery("SELECT ac, prot_seq_id,iso_id
		FROM prot_ac a, prot_seq ps
		where a.prot_entry_id =ps.prot_entry_id 
		AND ac IN (".implode(",",array_keys($SEARCH_AC)).')');
		if ($res===false)	failProcess($JOB_ID.'O04','Unable to query prot seq');

		$query='SELECT prot_seq_id, prot_seq_pos_id, position, letter FROM prot_seq_pos WHERE (prot_seq_id, position, letter) IN (';
		$MAP_POS=array();
		$HAS_SOME=false;
		foreach ($res as &$line)
		{
			$S_E=&$SEARCH_AC["'".$line['ac']."'"];
			$isoform='';
			$tab=explode("-",$line['iso_id']);
			if (isset($tab[1]))$isoform=$tab[1]-1;
			$FOUND=false;
			foreach ($S_E['POS'] as &$P)
			{
				//echo "ISO_TEST\t".$isoform."\t".$P[1]."\n";
				if ($P[1]!=$isoform && !($P[1]==1 && $isoform==''))continue;
				$FOUND=true;
			//	print_r($S_E);
				$ENTRY=&$DATA[$P[0]];
				$ENTRY['INFO']['PROT_SEQ_ID']=$line['prot_seq_id'];
				foreach ($P[2] as $MUT=>&$MUT_ID)
				{
					preg_match('/([A-Z]{1})([0-9]{1,4})([A-Z]|del){1,4}/',$MUT,$matches);
					$T=$line['prot_seq_id']."_".$matches[2]."_".$matches[1];
					$MAP_POS[$T]=array($line['ac'],$MUT);
					//echo $MUT."\n";
					$HAS_SOME=true;
					$query.='('.$line['prot_seq_id'].",".$matches[2].",'".$matches[1]."'),";
				}
			}
			if (!$FOUND)
			{
				$DATA[$S_E['POS'][0][0]]['INFO']['PROT_SEQ_ID']=$line['prot_seq_id'];
			}
			//$SEARCH_AC["'".$line['ac']."'"]['PROT']=$line['prot_seq_id'];
		}
		//print_r($SEARCH_AC);
		if (!$HAS_SOME)return;
		$res=runQuery(substr($query,0,-1).')');
		if ($res===false) failProcess($JOB_ID.'O06','Unable to query prot_seq_pos');
		
		foreach ($res as &$line)
		{
			$T=$line['prot_seq_id'].'_'.$line['position'].'_'.$line['letter'];
		//	echo "##########\n";
	//		print_r($MAP_POS[$T]);
			$SEARCH_ENTRY=&$SEARCH_AC["'".$MAP_POS[$T][0]."'"];
			foreach ($SEARCH_ENTRY['POS'] as &$P)
			{
				$ENTRY=&$DATA[$P[0]];
				foreach ($ENTRY['POS'] as $MUT_NAME=> &$MUT_INFO)
				{
					
					if ($MUT_NAME!=$MAP_POS[$T][1])continue;
					
					$MUT_INFO['prot_seq_pos_id']=$line['prot_seq_pos_id'];
					$MUT_INFO['position']=$line['position'];
					$MUT_INFO['letter']=$line['letter'];
					//print_r($ENTRY);
				}
			}
			// print_r($SEARCH_ENTRY);
			// exit;
			// print_r(array_keys($SEARCH_AC));
			
			// $MUT_INFO=&$SEARCH_AC[$MAP_POS[$T][0]]['POS'][$MAP_POS[$T][1]];
			
			
		}
	}


	addLog("\tPush to DB");
	$fp=fopen('INSERT/assay_variant.csv','w'); if (!$fp) failProcess($JOB_ID."O07",'Unable to open assay_variant.csv');
	$fpP=fopen('INSERT/assay_variant_pos.csv','w'); if (!$fpP)	failProcess($JOB_ID."O08",'Unable to open assay_variant_pos.csv');
	foreach ($DATA as $DBID=>&$ENTRY)
	{
		//if ($ENTRY['INFO']['DBID']==2329){echo $DBID."\n";print_r($ENTRY);}
		if ($ENTRY['INFO']['DB_STATUS']=='TO_INS')
		{
			if ($ENTRY['INFO']['PROT_SEQ_ID']==-1)$ENTRY['INFO']['PROT_SEQ_ID']='NULL';
			
		//	echo $DBID."\t".$ENTRY['INFO']['DBID']."\t".$ENTRY['INFO']['MUTATIONS']."\t".$ENTRY['INFO']['PROT_SEQ_ID']."\n";
			fputs($fp,$ENTRY['INFO']['DBID']."\t".$ENTRY['INFO']['MUTATIONS']."\t".$ENTRY['INFO']['PROT_SEQ_ID']."\t".$ENTRY['INFO']['AC_DB']."\n");
		}
		if (isset($ENTRY['POS']))
		foreach ($ENTRY['POS'] as $MUT_NAME=>$P)
		{
			if ($P['DB_STATUS']!='TO_INS')continue;
			if ($P['prot_seq_pos_id']==-1)
			{
				$MAIL_COMMENTS[]="PROTEIN POSITION NOT FOUND\t".$ENTRY['INFO']['AC_DB']."::".$ENTRY['INFO']['isoform']."\t".$MUT_NAME;
				continue;
			}
			fputs($fpP,$P['assay_variant_pos_id']."\t".$ENTRY['INFO']['DBID']."\t".$P['prot_seq_pos_id']."\tNULL\n");
		}
		  

	}
	$command='\COPY '.$SCHEMA.'.assay_variant (assay_variant_id,mutation_list  ,prot_seq_id,ac	) FROM \'INSERT/assay_variant.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )failProcess($JOB_ID."O09",'Unable to insert assay_tissue'); 
	$command='\COPY '.$SCHEMA.'.assay_variant_pos (assay_variant_pos_id,assay_variant_id    ,prot_seq_pos_id     ,variant_protein_id) FROM \'INSERT/assay_variant_pos.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )failProcess($JOB_ID."O10",'Unable to insert assay_tissue'); 
	print_r($MAIL_COMMENTS);
}






function processDrugs()
{
	global $GLB_VAR;
	global $DB_INFO;
	global $source_id;
	global $JOB_ID;
	$SCHEMA=$GLB_VAR['PUBLIC_SCHEMA'];

	$FILES=array(
		'NAME'=>fopen('DRUG_NAME.csv','w'),
		'ENTRY'=>fopen('DRUG_ENTRY.csv','w'),
		'MAP'=>fopen('DRUG_MAP.csv','w'),
		'NAME_ID'=>0,
		'ENTRY_ID'=>0,
		'MAP_ID'=>0
	);
	if (!$FILES['NAME'] || !$FILES['ENTRY'] || !$FILES['MAP'])failProcess($JOB_ID."P01",'Unable to open files');
	$res=runQuery("SELECT max(drug_entry_id) m FROM drug_entry");
	if ($res===false)failProcess($JOB_ID."P02",'Unable to get drug_entry max PK value');
	$FILES['ENTRY_ID']=$res[0]['m'];
	$FILES['ENTRY_ID']=($FILES['ENTRY_ID']=='')?0:$FILES['ENTRY_ID'];

	$res=runQuery("SELECT max(drug_name_id) m FROM drug_name");
	if ($res===false)failProcess($JOB_ID."P03",'Unable to get drug_name max PK value');
	$FILES['NAME_ID']=$res[0]['m'];
	$FILES['NAME_ID']=($FILES['NAME_ID']=='')?0:$FILES['NAME_ID'];

	$res=runQuery("SELECT max(drug_mol_entity_map_id) m FROM drug_mol_entity_map");
	if ($res===false)failProcess($JOB_ID."P04",'Unable to get drug_mol_entity_map max PK value');
	$FILES['MAP_ID']=$res[0]['m'];
	$FILES['MAP_ID']=($FILES['MAP_ID']=='')?0:$FILES['MAP_ID'];



	$res=runQuery("SELECT * FROM public.molecule_dictionary where max_phase is NOT NULL");
	if ($res===false)failProcess($JOB_ID."P05",'Unable to get molecule_dictionary');
	foreach ($res as $line)
	{
		processDrugRecord($line,$FILES);
	}

	fclose($FILES['NAME']);
	fclose($FILES['ENTRY']);
	fclose($FILES['MAP']);



	$command='\COPY '.$SCHEMA.'.drug_entry (drug_entry_id,drug_primary_name,is_approved,is_withdrawn,is_investigational, is_experimental, is_nutraceutical,is_illicit, is_vet_approved,max_clin_phase,drugbank_id,chembl_id) FROM \'DRUG_ENTRY.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )																		failProcess($JOB_ID."P06",'Unable to insert drug_entry'); 
	
	
	$command='\COPY '.$SCHEMA.'.drug_name (drug_name_id,drug_entry_id,drug_name,is_primary,is_tradename,source_id) FROM \'DRUG_NAME.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\", ESCAPE '\\\\' ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )																		failProcess($JOB_ID."P07",'Unable to insert drug_name'); 
	
	$command='\COPY '.$SCHEMA.'.drug_mol_entity_map (drug_mol_entity_map_id,drug_entry_id,molecular_entity_id,is_preferred,source_id) FROM \'DRUG_MAP.csv'."'  (DELIMITER E'\\t', null \\\"NULL\\\" ,format CSV )";
	echo $DB_INFO['COMMAND'].' -c "'.$command.'"'."\n";
	system($DB_INFO['COMMAND'].' -c "'.$command.'"',$return_code);
	if ($return_code !=0 )																		failProcess($JOB_ID."P08",'Unable to insert drug_mol_entity_map'); 
	
	

}


function processDrugRecord($FROM_CHEMBL,&$FILES)
{
	global $JOB_ID;
	global $source_id;

	$MAP_CLIN_PHASE=array('0.5'=>'0.5',
		'1.0'=>'1',
		'2.0'=>'2',
		'3.0'=>'3',
		'4.0'=>'4',
		'-1.0'=>'N/A');

	$res=runQuery("SELECT * FROM drug_entry where chembl_id = '".$FROM_CHEMBL['chembl_id']."'");
	if ($res===false)failProcess($JOB_ID."Q01",'Unable to get drug_entry');
	if (count($res)==0)
	{
		echo "NEW DRUG\t".$FROM_CHEMBL['pref_name'].'::'.$FROM_CHEMBL['chembl_id']."\n";
		++$FILES['ENTRY_ID'];
		fputs($FILES['ENTRY'],
		$FILES['ENTRY_ID']."\t".
		$FROM_CHEMBL['pref_name']."\t".
		(($FROM_CHEMBL['first_approval']!='')?'T':'F')."\t".
		(($FROM_CHEMBL['withdrawn_flag']!=0)?'T':'F')."\tNULL\tNULL\tNULL\tNULL\tNULL\t".
		$MAP_CLIN_PHASE[$FROM_CHEMBL['max_phase']]."\tNULL\t".$FROM_CHEMBL['chembl_id']."\n");
		$FROM_DB['drug_entry_id']=$FILES['ENTRY_ID'];
		$FROM_DB['drug_primary_name']=$FROM_CHEMBL['pref_name'];
		processDrugSynonyms($FROM_CHEMBL,$FROM_DB,$FILES);
		processDrugStructure($FROM_CHEMBL,$FROM_DB,$FILES);
	}
	else
	{
		$FROM_DB=$res[0];
		$query=array();
		if ((int)$MAP_CLIN_PHASE[$FROM_CHEMBL['max_phase']]>(int)$FROM_DB['max_clin_phase'])
		{
			//echo $FROM_DB['drug_primary_name']."\tCLIN PHASE:".$FROM_DB['max_clin_phase']."=>".$MAP_CLIN_PHASE[$FROM_CHEMBL['max_phase']]."\n";
			$query[]='max_clin_phase='.$MAP_CLIN_PHASE[$FROM_CHEMBL['max_phase']];
		}
		if ($FROM_CHEMBL['first_approval']!='' && $FROM_DB['is_approved']!='T')
		{
			//echo $FROM_DB['drug_primary_name']."\tAPPROVED:".$FROM_DB['is_approved']."=>T\n";
			$query[]='is_approved=\'T\'';
		}
		if ($FROM_CHEMBL['withdrawn_flag']!=0 && $FROM_DB['is_withdrawn']=='F')
		{
			//echo $FROM_DB['drug_primary_name']."\tWITHDRAWN:".$FROM_DB['is_withdrawn']."=>".$FROM_CHEMBL['withdrawn_flag']."\n";
			$query[]='is_withdrawn=\'T\'';
		}
		
		if ($query!=array())
		{
			$query='UPDATE drug_entry SET '.implode(',',$query).' WHERE drug_entry_id='.$FROM_DB['drug_entry_id'];
			if (!runQueryNoRes($query))failProcess($JOB_ID."Q02",'Unable to update drug_entry');
		}
		processDrugSynonyms($FROM_CHEMBL,$FROM_DB,$FILES);
		processDrugStructure($FROM_CHEMBL,$FROM_DB,$FILES);
		
	}
}

function processDrugStructure(&$FROM_CHEMBL,$FROM_DB,&$FILES)
{
	global $JOB_ID;
	global $source_id;

	
	$res=runQuery("SELECT molecular_entity_id FROM 
	molecular_entity me, sm_entry se,sm_source ss 
	wHERE  se.md5_hash = me.molecular_structure_hash
	AND se.sm_entry_id = ss.sm_entry_id
	AND ss.source_id = ".$source_id."
	AND sm_name = '".$FROM_CHEMBL['chembl_id']."'");
	if ($res===false)failProcess($JOB_ID."R01",'Unable to get molecular_entity_id');
	if ($res==array())return;

	$mol_entity_id=$res[0]['molecular_entity_id'];
	$res=runQuery("SELECT * FROM drug_mol_entity_map WHERE drug_entry_id = ".$FROM_DB['drug_entry_id']);
	if ($res===false)failProcess($JOB_ID."R02",'Unable to get drug_mol_entity_map');
	$FOUND=false;
	foreach ($res as $line)
	{
		if ($line['molecular_entity_id']==$mol_entity_id)$FOUND=true;
	}
	if ($FOUND)return;
	$FILES['MAP_ID']++;
	fputs($FILES['MAP'],$FILES['MAP_ID']."\t".$FROM_DB['drug_entry_id']."\t".$mol_entity_id."\tT\t".$source_id."\n");
	echo $FROM_CHEMBL['chembl_id']."\t".$FROM_DB['drug_primary_name']."\t".$FROM_DB['drug_entry_id']."\t".$mol_entity_id."\n";
	//if (!runQueryNoRes($query))failProcess($JOB_ID."D04",'Unable to insert drug_mol_entity_map');

}


function processDrugSynonyms(&$FROM_CHEMBL,$FROM_DB,&$FILES)
{
	global $JOB_ID;
	global $source_id;
	$res=runQuery("SELECT * FROM drug_name dn
		WHERE drug_entry_id = ".$FROM_DB['drug_entry_id']."
		AND source_id = ".$source_id);
	if ($res===false)failProcess($JOB_ID."S01",'Unable to get drug_name');


	foreach ($res as $line)
	{
		$line['DB_STATUS']='FROM_DB';
		$FROM_DB['SYN'][]=$line;
	}

	$res=runQuery("SELECT DISTINCT synonyms 
	FROM public.molecule_synonyms ms, public.molecule_dictionary md 
	WHERE ms.molregno = md.molregno
	AND md.chembl_id = '".$FROM_CHEMBL['chembl_id']."'");
	if ($res===false)failProcess($JOB_ID."S02",'Unable to get molecule_synonyms');
	
	foreach ($res as $line)
	{
		$FOUND=false;
		if (isset($FROM_DB['SYN']))
		foreach ($FROM_DB['SYN'] as &$SYN_DB)
		{
			if ($SYN_DB['drug_name']==$line['synonyms'])
			{
				$SYN_DB['DB_STATUS']='VALID';
				$FOUND=true;
				break;
			}
		}
		if ($FOUND)continue;
		echo $FROM_DB['drug_primary_name']."\tNAME:".$line['synonyms']."\n";
		++$FILES['NAME_ID'];
		fputs($FILES['NAME'],$FILES['NAME_ID']."\t".$FROM_DB['drug_entry_id']."\t\"".str_replace('"','\"',str_replace("\t","",$line['synonyms']))."\"\tF\tF\t".$source_id."\n");
		//$query='INSERT INTO drug_name VALUES ('.$FILES['NAME_ID'].','.$FROM_DB['drug_entry_id'].',"'.$line['synonyms'].'","F","F",'.$source_id.')';
	}


}

successProcess();



?>
