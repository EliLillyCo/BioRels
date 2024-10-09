<?php

//// Once processed, push all new records into the appropriate files for insertion
function insertEntry(&$ENTRY)
{
	global $TMP_PNAMES;
	global $FILES;
	global $FILE_STATUS;
	global $DBIDS;
	global $STATIC_DATA;
	global $fpNAMES;
	global $fpNAMES_MAP;
	$DEBUG=false;
	

	
	if ($DEBUG)echo "START\n";
	$MEM=memory_get_usage();
	
	
	/// We start with Uniprot Record
	if ($ENTRY['prot_entry']['DB_STATUS']=='TO_INS')
	{
		/// Each record is associated to a Taxon, so we look for it.
		//// By default (in db_insert_uniprot), we already listed all taxons for existing uniprot records
		/// but if it is a new taxon, we are going to search for it and add it to the STATIC_DATA['TAXON'] list
		if (!isset($STATIC_DATA['TAXON'][trim($ENTRY['prot_entry']['tax_id'])])) 
		{
			$res=runQuery("SELECT taxon_id, tax_id 
						FROM taxon 
						WHERE tax_id='".trim($ENTRY['prot_entry']['tax_id'])."'");

			if ($res==array())return false;
			$STATIC_DATA['TAXON'][trim($ENTRY['prot_entry']['tax_id'])]=$res[0]['taxon_id'];
		}
		/// Increase max prot_entry PK id
		$DBIDS['prot_entry']++;
		/// assign it
		$ENTRY['prot_entry']['prot_entry_id']=$DBIDS['prot_entry'];
		/// Push it in the file
		$FILE_STATUS['prot_entry']++;
		fputs($FILES['prot_entry'],
			$DBIDS['prot_entry']."\t".
			$ENTRY['prot_entry']['prot_identifier']."\t".
			"NOW()\t".
			"NULL\t".
			$ENTRY['prot_entry']['status']."\t".
			$STATIC_DATA['TAXON'][trim($ENTRY['prot_entry']['tax_id'])]."\t".
			$ENTRY['prot_entry']['confidence']."\n");
		

	}
	if ($DEBUG)echo "AC\n";
	/// Then we look at each Accession
	if (isset($ENTRY['ac']))
	{	
	//	print_r($ENTRY['ac']);
		foreach ($ENTRY['ac'] as $ac_id=>&$ac)
		{
			// only new accession are considered
			if ($ac['DB_STATUS']!='TO_INS')continue;
			/// Increase max prot_ac PK id
			$DBIDS['prot_ac']++;
			$FILE_STATUS['prot_ac']++;
			/// Push it in the file
			fputs($FILES['prot_ac'],
				$DBIDS['prot_ac']."\t".
				$ENTRY['prot_entry']['prot_entry_id']."\t".
				$ac_id."\t".
				$ac['is_primary']."\n");
		}
	}
	$ac_id=null;
	if ($DEBUG)echo "UN SEQ\n";
	/// Since prot sequences are used other tables, we will need to know which 
	/// one is the primary (canonical )sequence and the db id for each sequence
	$PRIM_SEQ='';
	$DBSEQ_ID=array();
	if (isset($ENTRY['prot_seq']))
	foreach ($ENTRY['prot_seq'] as $DBID=>&$UNS)
	{
		if ($UNS==array())continue;
		if ($UNS['is_primary']=='T')$PRIM_SEQ=$DBID;
		$DBSEQID=$DBID;
		

		/// New sequence -> we insert
		if ($UNS['DB_STATUS']=='TO_INS')
		{
			// Add to prot_seqmax primary value so it can become this sequence database id
			$DBIDS['prot_seq']++;
			$FILE_STATUS['prot_seq']++;
			/// Assign it
			$UNS['DBID']=$DBIDS['prot_seq'];;
			$DBSEQID=$DBIDS['prot_seq'];

			/// Insert sequence information and properties in the file
			fputs($FILES['prot_seq'],$DBIDS['prot_seq']."\t".
			$ENTRY['prot_entry']['prot_entry_id']."\t".
			$UNS['iso_name']."\t".
			$UNS['iso_id']."\t".
			$UNS['is_primary']."\t".
			(($UNS['description']=='')?'NULL':$UNS['description'])."\t".
			"NULL\t".
			((!isset($UNS['note']) || $UNS['note']=='')?'NULL':$UNS['note'])."\n");
		}
		/// Map the iso id against the db id. We distinguish between a new and already existing sequence
		$DBSEQ_ID[$UNS['iso_id']]=isset($UNS['DBID'])?$UNS['DBID']:$DBID;
		$DBSEQ_ID[$DBID]=$DBID;
		/// Checking if the sequence need to be inserted or not.
		/// This is independent from whether the sequence annotation is new or not.
		if ($UNS['SEQ_DB_STATUS']!='TO_INS')continue;

		foreach ($UNS['SEQ'] as $POS=>&$IFP)
		{
			$FILE_STATUS['prot_seq_pos']++;
			$DBIDS['prot_seq_pos']++;
			fputs($FILES['prot_seq_pos'],$DBIDS['prot_seq_pos']."\t".$UNS['DBID']."\t".$POS."\t".$IFP['AA']."\n");
			$IFP['DBID']=$DBIDS['prot_seq_pos'];

		}

	}
	
	if ($DEBUG)echo "DOM\n";
	/// Then we insert protein domains
	if(isset($ENTRY['prot_dom']))
	foreach ($ENTRY['prot_dom'] as $DBID=>&$UNS)
	{
		$DBSEQID=$DBID;
		$PRIM_SEQ_D=$PRIM_SEQ;
		
		/// New entry -> let's insert it.
		if ($UNS['DB_STATUS']=='TO_INS')
		{
			/// We currently don't consider ranges, so we remove that info
			$UNS['end']=preg_replace('/[<>=?]/','',$UNS['end']);
			$UNS['start']=preg_replace('/[<>=?]/','',$UNS['start']);

			/// In some cases, a domain can be associated to an isoform
			/// So we will get the isoform name and the range
			if (strpos($UNS['start'],':')!==false)
			{
				$SEQ_NAME=substr($UNS['start'],0,strpos($UNS['start'],':'));
				$UNS['start']=substr($UNS['start'],strpos($UNS['start'],':')+1);
				
				if (strpos($UNS['end'],':')!==false)$UNS['end']=substr($UNS['end'],strpos($UNS['end'],':')+1);
				
				/// We check that the protein isoform is in hte list
				$FOUND=false;
				foreach ($ENTRY['prot_seq'] as $USID=>&$INFO_SQ)
				if ($INFO_SQ['iso_id']==$SEQ_NAME)
				{
					$FOUND=true;
					$PRIM_SEQ_D=$USID;	
					break;
				}
				/// Otherwise we ignore it.
				/// TODO: Log the issue
				if (!$FOUND)continue;
			}
		
			$DBIDS['prot_dom']++;
			$FILE_STATUS['prot_dom']++;
			$UNS['DBID']=$DBIDS['prot_dom'];;
			$DBSEQID=$DBIDS['prot_dom'];
			fputs($FILES['prot_dom'],
				$DBIDS['prot_dom']."\t".
				$ENTRY['prot_entry']['prot_entry_id']."\t".
				(($UNS['domain_name']=='')?'NULL':$UNS['domain_name'])."\t".
				"NULL\t".
				$UNS['type']."\t".
				$UNS['start']."\t".
				$UNS['end']."\n");
		}
	
		/// Now we check if we need to insert the domain sequence
		/// which is independent from whether the domain annotation is new or not.
		if ($UNS['SEQ_DB_STATUS']!='TO_INS')continue;
	
		/// We check that we have all the correct amino-acids from the isoform
		$VALID=true;
		foreach ($UNS['SEQ']  as $POS=>&$IFP)
		{
			
			if (!isset($ENTRY['prot_seq'][$PRIM_SEQ_D]) ||!isset($ENTRY['prot_seq'][$PRIM_SEQ_D]['SEQ'][$IFP['POS_SQ']]))	$VALID=false;
				
			
		}
		gc_collect_cycles();
	
		// if ($VALID==false)			{echo "\t\tFAILED\t".$ENTRY['prot_entry']['prot_identifier']."\n";	continue;}
		
		/// If we have them all, we push those in the file
		foreach ($UNS['SEQ'] as $POS=>&$IFP)
		{
			$DBIDS['prot_dom_seq']++;
			$FILE_STATUS['prot_dom_seq']++;
			fputs($FILES['prot_dom_seq'],
				$DBIDS['prot_dom_seq']."\t".
				$UNS['DBID']."\t".
				$ENTRY['prot_seq'][$PRIM_SEQ_D]['SEQ'][$IFP['POS_SQ']]['DBID']."\t".
				$POS."\n");
			$IFP['DBID']=$DBIDS['prot_dom_seq'];

		}

	}
	
	if ($DEBUG)echo "pname\n";
	
	/// Protein name is a difficult case to handle.
	/// it's a two step process:
	/// 1/ record the protein name itself
	/// 2/ then map the protein name to the uniprot record.
	/// However within a block of uniprot records that are been processed, a new protein name can appear twice!
	/// If the protein name is new, then we put it inthe PROT_NAME array for later use
	/// Otherwise we insert the mapping
	if(isset($ENTRY['pname']))
	{
		global $PROT_NAMES;
		foreach ($ENTRY['pname'] as &$PN)
		{
			if ($PN['DB_STATUS']!='TO_INS')continue;
			if ($PN['name'][0]==-1)
			{
				$PROT_NAMES[$PN['name'][1].'__'.$PN['name'][2]]['LIST']=array($ENTRY['prot_entry']['prot_entry_id']."\t".'${PROT_NAME_ID}'."\t".$PN['group_id']."\t".$PN['class_name']."\t".$PN['name_type']."\t".$PN['name_subtype']."\t".$PN['name_link']."\t".$PN['is_primary']."\n");
			}
			else {
				$DBIDS['prot_name_map']++;
				$FILE_STATUS['prot_name_map']++;
				fputs($FILES['prot_name_map'],
					$DBIDS['prot_name_map']."\t".
					$ENTRY['prot_entry']['prot_entry_id']."\t".
					$PN['name'][0]."\t".
					$PN['group_id']."\t".
					$PN['class_name']."\t".
					$PN['name_type']."\t".
					$PN['name_subtype']."\t".
					$PN['name_link']."\t".
					$PN['is_primary']."\n");
			}
		}
	}



	if (isset($ENTRY['GO']))
	{
		
		foreach ($ENTRY['GO'] as &$GO)
		{
			
			if ($GO['DB_STATUS']!='TO_INS' && $GO['DB_STATUS']!='TO_UPD')continue;
			/// Needs to be inserted, se we first search the source
			$DBIDS['prot_go_map']++;

			$source_id=getSource($GO['source']);
			
			/// Then insert in the file
			$FILE_STATUS['prot_go_map']++;
			fputs($FILES['prot_go_map'],
				$DBIDS['prot_go_map']."\t".
				$GO['go_entry_id']."\t".
				$ENTRY['prot_entry']['prot_entry_id']."\t".
				$GO['evidence']."\t".
				$source_id."\n");
		}
	}

	

	
	if ($DEBUG)echo "gene\n";
	if(isset($ENTRY['gene']))
	foreach ($ENTRY['gene'] as $gene_id=>&$PN)
	{
		if ($PN['DB_STATUS']!='TO_INS')continue;
		//print_r($ENTRY);
		/// MISSING GENE (can happen)
		/// TODO: Look at gene history
		if (!isset($STATIC_DATA['GENE'][$gene_id]))
		{
			// echo "MISSING ".$gene_id."\n";
			// print_r($ENTRY['prot_entry']); 				
			//failProcess($JOB_ID.'004','Unable to run query '.$QUERY);
			if ($DEBUG) echo "gn hist\n";
			$n_test=0;
			do
			{
				++$n_test;
				$res=runQuery("SELECT alt_gene_id FROM gn_history where gene_id = ".$gene_id);
				if ($DEBUG)
				{
					echo $gene_id."\n";
					print_r($res);
				}
				if ($res==array())break;
				if ($res===false || count($res)!=1)			continue;
				$gene_id = $res[0]['alt_gene_id'];
				if ($DEBUG)echo "NEW GENE : ".$gene_id."\n";
				if ($gene_id=='-')break;
				if (isset($ENTRY['gene'][$gene_id]))continue;
				if (!isset($STATIC_DATA['GENE'][$gene_id]))continue;
				
			}while($n_test<30);
			if ($DEBUG) echo "end gn hist\t".$gene_id."\n";
		}
		if ($gene_id=='-')continue;
		if (!isset($STATIC_DATA['GENE'][$gene_id]))
		{

			//addLog("Missing gene ".$gene_id);
			$query= "SELECT DISTINCT m.gn_entry_id,m.gene_id FROM mv_gene m WHERE  m.gene_id = ".$gene_id;
			//echo $query."\n";
			$res=runQuery($query);if ($res===false)			failProcess($JOB_ID."003",'Unable to get genes');
			foreach ($res as $line)
			$STATIC_DATA['GENE'][$line['gene_id']]=$line['gn_entry_id'];
		}
		if (!isset($STATIC_DATA['GENE'][$gene_id])){
			// echo "MISSING ".$gene_id."\n";
			// print_r($ENTRY['prot_entry']); 				
			//failProcess($JOB_ID.'004','Unable to run query '.$QUERY);
			if ($DEBUG) echo "gn hist\n";
			$n_test=0;
			do
			{
				$res=runQuery("SELECT alt_gene_id FROM gn_history where gene_id = ".$gene_id);
				echo $gene_id."\t";
				print_r($res);
				$n_test++;
				if ($res===false || count($res)!=1)			continue;
				$gene_id = $res[0]['alt_gene_id'];
				if ($gene_id=='-')break;
				if (isset($ENTRY['gene'][$gene_id]))continue;
				if (!isset($STATIC_DATA['GENE'][$gene_id]))continue;
				
			}while($n_test<30);
			if ($DEBUG) echo "end gn hist\t".$gene_id."\n";
		}
		if ($gene_id=='-')continue;
		if (!isset($STATIC_DATA['GENE'][$gene_id]))continue;
		$DBIDS['gn_prot_map']++;
		$FILE_STATUS['gn_prot_map']++;
		fputs($FILES['gn_prot_map'],$DBIDS['gn_prot_map']."\t".$STATIC_DATA['GENE'][$gene_id]."\t".$ENTRY['prot_entry']['prot_entry_id']."\n");
		
		
	}

	
	if ($DEBUG)echo "FEAT\n";
	
	if (isset($ENTRY['ft'])&& $PRIM_SEQ!='')
	{
		
		/// We then look at the features"
		foreach ($ENTRY['ft'] as &$ft)
		{
			/// The following types are associate to protein domain or isoform naming, so we ignore them
			if ($ft['type']=='CHAIN' ||
				$ft['type']=='REPEAT'||
				$ft['type']=='REGION'||
				$ft['type']=='DOMAIN'||
				$ft['type']=='VAR_SEQ')continue;
			
			/// Need to insert the feature
			if ($ft['DB_STATUS']=='TO_INS')
			{
				/// New type, we insert it
				if (!isset($STATIC_DATA['FEAT'][$ft['type']]))
				{
					$ft_type_ID=max($STATIC_DATA['FEAT'])+1;
					$query="INSERT INTO prot_feat_type (feat_name,description,section,tag,prot_feat_type_id) 
							VALUES ('".$ft['type']."_TBD','TBD', 'TBD','".$ft['type']."',".$ft_type_ID.")";
					if (!runQueryNoRes($query)){echo "Unable to insert new feature";continue;}
					$STATIC_DATA['FEAT'][$ft['type']]=$ft_type_ID;
				}	
			
				
				$DBIDS['prot_feat']++;
				$FILE_STATUS['prot_feat']++;
				$ft['DBID']=$DBIDS['prot_feat'];
				$ft_type_ID=$STATIC_DATA['FEAT'][$ft['type']];
				
				
				
				
				
				/// If the feature is associated to an isoform, then we need to find the corresponding sequence
				$ft_UNSEQ_KEY=-1;
				$SEQ_NAME=$ft['SEQ_NAME'];
				$FOUND=false;
				foreach ($ENTRY['prot_seq'] as $KEY_SEQ=>&$INFO_SQ)
				if ($INFO_SQ['iso_id']==$SEQ_NAME)
				{
					$FOUND=true;
					
					$ft_UNSEQ_KEY=$KEY_SEQ;
					break;
				}
				if (!$FOUND)continue;
				$UNSQ_ENTRY=$ENTRY['prot_seq'][$ft_UNSEQ_KEY];

				/// Then we get the range	
				$NS=0;
				$end=preg_replace('/[<>=?]/','',$ft['end']);
				$start=preg_replace('/[<>=?]/','',$ft['start']);
				
				if (is_numeric($start) && is_numeric($end))
				{
					/// Now we extract the feature sequence based on the isoform sequence and the range
					$STR='';
					$VALID=true;
					for ($I=$start;$I<=$end;++$I)
					{
						/// Ensuring that all amino-acid within that range actually exist
						if (!isset($UNSQ_ENTRY['SEQ'][$I]['DBID'])){$VALID=false;break;}
						$DBIDS['prot_feat_seq']++;++$NS;
						$FILE_STATUS['prot_feat_seq']++;
						$STR.=$DBIDS['prot_feat_seq']."\t".$DBIDS['prot_feat']."\t".$UNSQ_ENTRY['SEQ'][$I]['DBID']."\t".$NS."\n";
					}
					if (!$VALID){echo "INVALID FEATURE\n";print_r($ft);continue;}
					fputs($FILES['prot_feat_seq'],$STR);
				}
				
				/// Then we save this into a file
				fputs($FILES['prot_feat'],$DBIDS['prot_feat'].
				"\t".$ft_type_ID.
				"\t".(($ft['value']=='')?'NULL':$ft['value']).
				"\tNULL".
				"\t".$UNSQ_ENTRY['DBID'].
				"\t".$ft['start'].
				"\t".$ft['end'].
				"\n");
				
			}
			// If any publication associated to those features need to be inserted, we add them.
			foreach ($ft['PUBLI'] as $PUB)
			{
				if ($PUB['DB_STATUS']!='TO_INS')continue;
				if ($ft['DBID']==-1){echo "PUBLI_FEATURE WITH NO FEATURE DBID\n";print_r($ft,true);return false;}
				$DBIDS['prot_feat_pmid']++;
				$FILE_STATUS['prot_feat_pmid']++;
				fputs($FILES['prot_feat_pmid'],$DBIDS['prot_feat_pmid']."\t".$ft['DBID']."\t".$PUB['pmid']."\t".$PUB['ECO']."\n");
			}


		}
	}
	

	/// External identifiers
	if ($DEBUG)echo "extdb\n";
	if(isset($ENTRY['extdb']))
	foreach ($ENTRY['extdb'] as &$PN)
	{
		if ($PN['DB_STATUS']!='TO_INS')continue;
		$DBIDS['prot_extdb_map']++;
		
		/// The external database should be in the system, based on db_uniprot_extdb script
		if (!isset($STATIC_DATA['EXTDB'][$PN['DBNAME']]))continue;
		
		/// If that identifier is associated to a specific isoform, we find  it.
		$SEQ='NULL';
		if ($PN['SEQ']!=''){
			foreach ($ENTRY['prot_seq'] as $DBID=>$INFO)
			{
				if (!isset($INFO['iso_id']))print_r($INFO);
				if ($INFO['iso_id']!=$PN['SEQ'])continue;
				$SEQ=$INFO['DBID'];
			}
			
		}
		$FILE_STATUS['prot_extdb_map']++;
		fputs($FILES['prot_extdb_map'],$DBIDS['prot_extdb_map']."\t".$STATIC_DATA['EXTDB'][$PN['DBNAME']]."\t".
		$ENTRY['prot_entry']['prot_entry_id']."\t".
		str_replace('"','""',$PN['value'])."\t".$SEQ."\n");
		
	}
	
	if ($DEBUG)echo "comments\n";
	if (isset($ENTRY['comments']))
	{
		/// For comments
		foreach ($ENTRY['comments'] as $N=>&$C)
		{
			if ($C['DB_STATUS']!='TO_INS')continue;
			
			$DBIDS['prot_desc']++;
			$FILE_STATUS['prot_desc']++;
			fputs($FILES['prot_desc'],$DBIDS['prot_desc']."\t".
			$ENTRY['prot_entry']['prot_entry_id']."\t".
			'"'.str_replace('"','""',isset($C['value'])?$C['value']:"").'"'."\t".
			$C['type']."\n");
			$DBIDS['DESC_FILES']++;$INCLUDED_PMID=array();
			/// Evidence associated to comments are also inserted.
			foreach ($C['ECO'] as $EVID)
			{
				if (isset($EVID['pmidDBID']) && isset(
					$STATIC_DATA['ECO'][str_replace(":","_",$EVID['ECO'])]) && !isset($INCLUDED_PMID[$EVID['pmidDBID']]))
				{
					$FILE_STATUS['prot_desc_pmid']++;
					$DBIDS['prot_desc_pmid']++;
					$INCLUDED_PMID[$EVID['pmidDBID']]=true;
					fputs($FILES['prot_desc_pmid'],$DBIDS['prot_desc_pmid']."\t".
					$DBIDS['prot_desc']."\t".$EVID['pmidDBID']."\t".$STATIC_DATA['ECO'][str_replace(":","_",$EVID['ECO'])]."\n");
							
				}
			}
		}

	}
	

	return true;
//	exit;
}







/// Function that will process a single entry
/// And update/delete data from the database
/// At last it will save the array as a json string in the output file
function processEntry(&$INFO_ENTRY,&$fpO,&$fpE)
{
	$time=microtime_float();
	
	echo "LOAD DATA:\n";
	$ENTRY=loadData($INFO_ENTRY[0]);
	if (!is_array($ENTRY))
	{
		echo "ERROR:".$ENTRY."\n";
		fputs($fpE,$INFO_ENTRY[0]."\t".$ENTRY."\n");
		return;
	}
	echo "LOAD FROM FILE\n";
	$return_code=loadFromFile($ENTRY,$INFO_ENTRY[1],$INFO_ENTRY[2]);
	echo "END LOADING FILE\n";
	if ($return_code!==true)
	{
		echo "RETURN CODE:".$return_code."\n";
		fputs($fpE,$INFO_ENTRY[0]."\t".$return_code."\n");
		return;
	}

	
	echo "PUSH TO DB\n";
	$return_code=pushToDB($ENTRY);
	echo "end PUSH TO DB\n";
	if ($return_code!==true)
	{
		fputs($fpE,$INFO_ENTRY[0]."\t".$return_code."\n");
		return;
	}
	
	fputs($fpO,json_encode($ENTRY)."\n");
	echo round(microtime_float()-$time,2)."\t".convert(memory_get_usage(true))."\n";
	
//	exit;
	$ENTRY=null;

}








function loadData($UNI_IDENTIFIER)
{
	global $GLB_VAR;
	global $JOB_ID;



	/// First we get the initial information for the entry, including the taxon
	$QUERY="SELECT prot_entry_id, prot_identifier, u.date_created, status, tax_id,confidence
			FROM prot_entry U, taxon T 
			WHERE T.taxon_id= U.taxon_id
			AND U.prot_identifier='".$UNI_IDENTIFIER."'";

	$res=runQuery($QUERY); if ($res===false) 	return "Unable to run query ".$QUERY;
	
	/// Then we create an array, i.e. a snapshot of all uniprot data related to that record
	$DATA_ENTRY=array();
	$DATA_ENTRY['prot_entry']=array(
		'prot_entry_id'	=>-1,
		'prot_identifier'	=>$UNI_IDENTIFIER,
		'date_created'	=>'NOW',
		'status'		=>'',
		'tax_id'		=>'',
		'DB_STATUS'		=>'TO_INS',
		'confidence'	=>'');
		$DATA_ENTRY['ac']	   =array();
		$DATA_ENTRY['GO']	   =array();
		$DATA_ENTRY['prot_seq']  =array();
		$DATA_ENTRY['prot_dom']  =array();
		$DATA_ENTRY['pname']   =array();
		$DATA_ENTRY['gene']    =array();
		$DATA_ENTRY['comments']=array();
		$DATA_ENTRY['extdb']   =array();
		$DATA_ENTRY['ft'] 	   =array();


	/// No data? then no records in the database, we are done:
	if (count($res)==0)	return $DATA_ENTRY;
		
	///Otherwise we fill the array with the data from the database
	$DATA_ENTRY['prot_entry']=array(
		'prot_entry_id'	=>$res[0]['prot_entry_id'],
		'prot_identifier'	=>$res[0]['prot_identifier'],
		'date_created'	=>$res[0]['date_created'],
		'status'		=>$res[0]['status'],
		'tax_id'		=>$res[0]['tax_id'],
		'DB_STATUS'		=>'FROM_DB',
		'confidence'	=>$res[0]['confidence']);
		

	/// Get all Uniprot Accession:
	$QUERY='SELECT ac, is_primary,prot_ac_id 
			FROM prot_ac 
			WHERE prot_entry_id = '.$DATA_ENTRY['prot_entry']['prot_entry_id'].' 
			ORDER BY is_primary DESC';
	
		$res=runQuery($QUERY); 
	if ($res===false) 	return "Unable to run query ".$QUERY;
	$tmp=array();
	foreach ($res as $tab)
	{
		$tmp[$tab['ac']]=array(
			'DBID'=>$tab['prot_ac_id'],
			'is_primary'=>$tab['is_primary'],
			'DB_STATUS'=>'FROM_DB');
	}
	$DATA_ENTRY['ac']=$tmp;
		

	/// Get Gene:
	$QUERY='SELECT GE.symbol, GE.gn_entry_id, GE.gene_id, gn_prot_map_id 
			FROM gn_prot_map GUM, gn_entry GE 
			WHERE GE.gn_entry_id = GUM.gn_entry_id 
			AND prot_entry_id = '.$DATA_ENTRY['prot_entry']['prot_entry_id'];
			
	$res=runQuery($QUERY); 
	if ($res===false) 	return "Unable to run query ".$QUERY;		
	
	$tmp=array();
	foreach ($res as $tab)
		$tmp[$tab['gene_id']]=array(
			'symbol'		=>$tab['symbol'],
			'DB_ID'			=>$tab['gn_prot_map_id'],
			'DB_STATUS'		=>'FROM_DB',
			'gn_entry_id'	=>$tab['gn_entry_id']);
	
	$DATA_ENTRY['gene']=$tmp;
		

	/// Get protein sequence information:
	$QUERY='SELECT prot_seq_id, iso_name,iso_id, is_primary,description, modification_date,note ,status
			FROM prot_seq 
			WHERE  prot_entry_id = '.$DATA_ENTRY['prot_entry']['prot_entry_id'].' ORDER BY is_primary DESC';
	
	$res=runQuery($QUERY); 
	if ($res===false) 	return "Unable to run query ".$QUERY;		
	
	$NS=0;$DATA_ENTRY['MAP_SEQ']=array();
	foreach ($res as $tab)
	{
		++$NS;
		if ($tab['status']==9)continue;
		$DATA_ENTRY['prot_seq'][$NS]=array(
			'DBID'				=>$tab['prot_seq_id'],
			'iso_name'			=>$tab['iso_name'],
			'iso_id'			=>$tab['iso_id'],
			'is_primary'		=>$tab['is_primary'],
			'description'		=>$tab['description'],
			'modification_date'	=>$tab['modification_date'],
			'note'				=>$tab['note'],
			'status'			=>$tab['status'],
			'DB_STATUS'			=>'FROM_DB',
			'SEQ_DB_STATUS'		=>'FROM_DB');
		$DATA_ENTRY['MAP_SEQ'][$tab['prot_seq_id']]=$NS;
	}
		

	/// Get protein sequences:
	if (count($DATA_ENTRY['prot_seq'])>0)
	{
		
		$QUERY='SELECT prot_seq_pos_id,  prot_seq_id, position, letter 
				FROM prot_seq_pos 
				WHERE  prot_seq_id IN ('.implode(",",array_keys($DATA_ENTRY['MAP_SEQ'])).') ORDER BY prot_seq_id, position ASC';
		$res=array();
		$res=runQuery($QUERY);
		if ($res===false) return "Unable to run query ".$QUERY;
		foreach ($res as $tab)
		{
			/// We map the sequence id to the position in the array:
			$MAP_PROT_SEQ_ID=$DATA_ENTRY['MAP_SEQ'][$tab['prot_seq_id']];
			/// All protein sequences, the sequence assay,-> SEQ -> position -> DBID, AA
			$DATA_ENTRY['prot_seq'][$MAP_PROT_SEQ_ID]['SEQ'][$tab['position']]=array('DBID'=>$tab['prot_seq_pos_id'],'AA'=>$tab['letter']);
		}
		
	}
	/// Get domain:
	$QUERY='SELECT prot_dom_id, domain_name, modification_date, domain_type,pos_start,pos_end,status 
			FROM prot_dom 
			WHERE  prot_entry_id = '.$DATA_ENTRY['prot_entry']['prot_entry_id'];
	$res=array();
	
	$res=runQuery($QUERY);
	if ($res===false)return "Unable to get protein domains: ".$QUERY;
	
	$DATA_ENTRY['MAP_DOM']=array();$ND=0;
	foreach ($res as $tab)
	{
		++$ND;
		if ($tab['status']==9)continue;
		$DATA_ENTRY['prot_dom'][$ND]=array(
			'DBID'				=>$tab['prot_dom_id'],
			'domain_name'		=>$tab['domain_name'],
			'type'				=>$tab['domain_type'],
			'start'				=>$tab['pos_start'],
			'end'				=>$tab['pos_end'],
			'status'			=>$tab['status'],
			'modification_date'	=>$tab['modification_date'],
			'DB_STATUS'			=>'FROM_DB',
			'SEQ_DB_STATUS'		=>'FROM_DB');

			/// We map the domain id to the position in the array:
		$DATA_ENTRY['MAP_DOM'][$tab['prot_dom_id']]=$ND;
	}

	/// Get domain pos
	if (count($DATA_ENTRY['prot_dom']))
	{
		$QUERY='SELECT prot_dom_seq_id,  prot_dom_id,pd.prot_seq_pos_id, pd.position , letter, psp.position as pos_sq
				FROM prot_dom_seq pd, prot_seq_pos psp 
				WHERE psp.prot_seq_pos_id = pd.prot_seq_pos_id 
				AND  prot_dom_id IN ('.implode(",",array_keys($DATA_ENTRY['MAP_DOM'])).') 
				ORDER BY prot_dom_id,position ASC';
		$res=array();
		$res=runQuery($QUERY);
		if ($res===false)return "Unable to query protein domain sequence: ".$QUERY;
		
		foreach ($res as $tab)
		{
			$MAP_DOM_ID=$DATA_ENTRY['MAP_DOM'][$tab['prot_dom_id']];
			/// Protein domains -> DOMAIN -> SEQ info -> position -> DBID, AA, Position in main sequence
			$DATA_ENTRY['prot_dom'][$MAP_DOM_ID]['SEQ'][$tab['position']]=array(
				'DBID'	=>$tab['prot_dom_seq_id'],
				'SQ'	=>$tab['prot_seq_pos_id'],
				'POS_SQ'=>$tab['pos_sq'],
				'AA'=>$tab['letter']);
		}
	}
	


	/// Get Protein Name;
	$QUERY='SELECT prot_name_map_id, prot_name_id, group_id, class_name, name_type, name_subtype,name_link, is_primary 
			FROM prot_name_map 
			WHERE  prot_entry_id = '.$DATA_ENTRY['prot_entry']['prot_entry_id'];
			
	$res=array();
	$res=runQuery($QUERY);
	if ($res===false) return "Unable to run query prot_name_map: ".$QUERY;
	$pnameID=array();
	$K=-1;
	foreach ($res as $tab)
	{
		++$K;
		$DATA_ENTRY['pname'][$K]=array(
			'DB_MAPID'		=>$tab['prot_name_map_id'],
			'DB_PID'		=>$tab['prot_name_id'], 
			'group_id'		=>$tab['group_id'], 
			'class_name'	=>$tab['class_name'], 
			'name_type'		=>$tab['name_type'], 
			'name_subtype'	=>$tab['name_subtype'],
			'name_link'		=>$tab['name_link'], 
			'is_primary'	=>$tab['is_primary'],
			'DB_STATUS'		=>'FROM_DB'

		);
		$pnameID[$tab['prot_name_id']][]=$K;
	}
	if (count($pnameID)!=0)
	{
		$QUERY='SELECT prot_name_id, protein_name, ec_number, date_created 
				FROM prot_name 
				WHERE  prot_name_id IN ('.implode(",",array_keys($pnameID)).')';
		$res=array();
		$res=runQuery($QUERY);
		if ($res===false)return "Unable to get protein name: ".$QUERY;
		
		foreach ($res as $tab)	
		foreach ($pnameID[$tab['prot_name_id']] as $P)$DATA_ENTRY['pname'][$P]['name']=array_values($tab);
	
	}

	/// Get Uniprot Description
	$QUERY='SELECT prot_desc_id,  desc_type,description 
			FROM prot_desc 
			WHERE prot_entry_id = '.$DATA_ENTRY['prot_entry']['prot_entry_id'];
	$res=array();
	$res=runQuery($QUERY);
	if ($res===false)return "Unable to get protein description from query ".$QUERY;
		
	
	$res=array_filter($res);
	foreach ($res as $tab)
	{
		
		$DATA_ENTRY['comments'][$tab['prot_desc_id']]=array(
			'type'		=>$tab['desc_type'],
			'DBID'		=>$tab['prot_desc_id'],
			'value'		=>($tab['description']),
			'ECO'		=>array(),
			'DB_STATUS'	=>'FROM_DB');
			
	}

	if ($DATA_ENTRY['comments']!=array())
	{

		/// Get Uniprot Description ECO & publication
		$QUERY='SELECT prot_desc_pmid_id,prot_desc_id,pmid,eco_id 
				FROM prot_desc_pmid U, eco_entry E, pmid_entry P 
				WHERE P.pmid_entry_id = U.pmid_entry_id 
				AND U.eco_entry_id = E.eco_entry_id AND prot_desc_id IN ('.implode(",",array_keys($DATA_ENTRY['comments'])).')';
		$res=array();
		$res=runQuery($QUERY);
		if ($res===false) return "Unable to get protein publication from query ".$QUERY;
		
		
		foreach ($res as $tab)
			$DATA_ENTRY['comments'][$tab['prot_desc_id']]['ECO'][]=array(
				'eco'		=>$tab['eco_id'],
				'pmid'		=>$tab['pmid'],
				'DB_STATUS'	=>'FROM_DB',
				'DBID'		=>$tab['prot_desc_pmid_id']);
				
	}
	

	$QUERY='SELECT prot_extdb_map_id, U.prot_extdb_id,prot_extdb_value, prot_extdbabbr , iso_name
			FROM  prot_extdb UE, prot_extdb_map U 
			LEft  JOIN prot_seq S ON S.prot_seq_id = U.prot_seq_id
			WHERE UE.prot_extdbid=U.prot_extdb_id 
			AND   U.prot_entry_id = '.$DATA_ENTRY['prot_entry']['prot_entry_id'];
	$res=array();
	$res=runQuery($QUERY);
	if ($res===false)return "Unable to get protein external identifiers from ".$QUERY;
		
	foreach ($res as $tab)
		$DATA_ENTRY['extdb'][]=array(
			'DBID'		=>$tab['prot_extdb_map_id'],
			'extdbid'	=>$tab['prot_extdb_id'],
			'value'		=>$tab['prot_extdb_value'],
			'DBNAME'	=>$tab['prot_extdbabbr'],
			'DB_STATUS'	=>'FROM_DB',
			'SEQ'		=>$tab['iso_name']);
		
	
	if (count($DATA_ENTRY['MAP_SEQ']))
	{
		$QUERY='SELECT UF.prot_feat_id, feat_name, feat_value, feat_link, ps.prot_seq_id, ps.iso_id, start_pos,end_pos,tag
		FROM prot_feat UF, prot_feat_type UT, prot_seq ps
		WHERE UT.prot_feat_type_id = UF.prot_feat_type_id 
		AND ps.prot_seq_id = UF.prot_seq_id 
		AND ps.prot_seq_id IN ('.implode(',',array_keys($DATA_ENTRY['MAP_SEQ'])).')';
		$res=array();
		$res=runQuery($QUERY);
		if ($res===false)  return "Unable to get protein features from query: ".$QUERY;
		$NF=0;
		$MAP_ft=array();
		foreach ($res as $tab)
		{
			++$NF;
			$MAP_ft[$tab['prot_feat_id']]=$NF;
			$DATA_ENTRY['ft'][$NF]=array(
				'DBID'		=>$tab['prot_feat_id'],
				'type'		=>$tab['tag'],
				'start'		=>$tab['start_pos'],
				'end'		=>$tab['end_pos'],
				'value'		=>$tab['feat_value'],
				'INFO'		=>$tab['feat_link'],
				'DB_STATUS'	=>'FROM_DB',
				'SEQ'		=>$tab['prot_seq_id'],
				'SEQ_NAME'	=>$tab['iso_id'],
				'POS'		=>array(),
				'PUBLI'		=>array());
		}
		/// Get the corresponding papers from the different features:
		if (count($MAP_ft)>0)	
		{
			$CHUNKS=array_chunk(array_keys($MAP_ft),1000);
			foreach ($CHUNKS as $CHUNK){
			$QUERY='SELECT prot_feat_pmid_id,prot_feat_id,pmid_entry_id,eco_entry_id 
			FROM prot_feat_pmid 
			WHERE prot_feat_id IN ('.implode(',',$CHUNK).")";
			$res=array();
			$res=runQuery($QUERY);
			if ($res===false) return "Unable to get protein publications ".$QUERY;
			foreach ($res as $tab)
				$DATA_ENTRY['ft'][$MAP_ft[$tab['prot_feat_id']]]['PUBLI'][]=array(
					'DBID'		=>$tab['prot_feat_pmid_id'],
					'pmid'		=>$tab['pmid_entry_id'],
					'ECO'		=>$tab['eco_entry_id'],
					'DB_STATUS'	=>'FROM_DB');
			}
			
		}
	}

	$query="SELECT ac,prot_go_map_id,evidence,source_name,ge.go_entry_id
	FROM prot_go_map GP 
	LEFT JOIN source s ON s.source_id = GP.source_id,
	 GO_ENTRY GE WHERE GE.GO_ENTRY_Id=GP.GO_ENTRY_ID AND PROT_ENTRY_ID=".$DATA_ENTRY['prot_entry']['prot_entry_id'];
	
	$res=runQuery($query);
	  if ($res===false) return "Unable to get protein go mapping from query: ".$query."\n";
	foreach ($res as $line)
	{
		$DATA_ENTRY['GO'][$line['ac']]=array(
			'DBID'=>$line['prot_go_map_id'],
			'go_entry_id'=>$line['go_entry_id'],
			'evidence'=>$line['evidence'],
			'source'=>$line['source_name'],
			'DB_STATUS'=>'FROM_DB');
	}

	return $DATA_ENTRY;
			
}







function loadFromFile(&$ENTRY,$FILE,$FPOS)
{
	/// First we open the corresponding file
	global $JOB_ID;
	$fp=fopen('../'.$FILE,'r');
	if (!$fp)failProcess($JOB_ID."014","Unable to open file ".$FILE);
	/// fpos is the file position of that record in the file, so we go there
	fseek($fp,$FPOS);
	
	$RECORD=array();
	/// Then we start reading
	do
	{
		$line=stream_get_line($fp,1000,"\n");
		
		if ($line=="//")break;// // marks the end of the record
		if ($line=="")continue;

		$RECORD[]=$line;
	}while(!feof($fp));


	
	$EXISTS= ($ENTRY['prot_entry']['DB_STATUS']=='FROM_DB');
	
	$N_ac_LINE=0;$GN_INFO=array();$DE=array();$ft=array();$DR=array();
	///Each line starts with two characters as the header and then the value
	foreach ($RECORD as $line)
	{
		$HEAD=substr($line,0,2);
		$VALUE=substr($line,5);
		/// depending on the header, we call various functions or push the lines into specific arrays
		switch ($HEAD)
		{
			case 'ID':processID($ENTRY,$VALUE);break;
			case 'AC':++$N_ac_LINE;processac($ENTRY,$VALUE,$N_ac_LINE);break;
			case 'OX':processTaxon($ENTRY,$VALUE);break;
			case 'GN':processGene($VALUE,$GN_INFO);break;
			case 'CC':$comments[]=$VALUE;break;
			case 'FT':$ft[]=$VALUE;break;
			case 'DE':$DE[]=$VALUE;break;
			case 'PE':if ($ENTRY['prot_entry']['confidence']!=substr($VALUE,0,1)){$ENTRY['prot_entry']['confidence']=substr($VALUE,0,1);if ($ENTRY['prot_entry']['DB_STATUS']!='TO_INS')$ENTRY['prot_entry']['DB_STATUS']='TO_UPD';}break;
			case 'DR':$DR[]=$VALUE;break;
			case 'SQ':
			case '  ':$SQ[]=$VALUE;break;
		}
	}
	
	/// Now that we have all the information, we can start processing

	$PROT_ID=$ENTRY['prot_entry']['prot_identifier'];
	
	$DEBUG=true;
	$time=microtime_float();
	if ($DEBUG)echo $PROT_ID.' PROT NAME';
	$return_code=processProtName($ENTRY,$DE);	if ($return_code!==true)return $return_code;
	
	if ($DEBUG){ echo " ".round(microtime_float()-$time,2)."\n".$PROT_ID.' COMMENTS';$time=microtime_float();}
	$return_code=processComments($ENTRY,$comments);	if ($return_code!==true)return $return_code;

	if ($DEBUG){ echo " ".round(microtime_float()-$time,2)."\n".$PROT_ID.' FEATURE';$time=microtime_float();}
	$return_code=processFeatures($ENTRY,$ft);		if ($return_code!==true)return $return_code;


	if ($DEBUG){ echo " ".round(microtime_float()-$time,2)."\n".$PROT_ID.' SEQUENCE';$time=microtime_float();}
	$return_code=processSequence($ENTRY,$SQ,$FILE);	if ($return_code!==true)return $return_code;
	
	if ($DEBUG){ echo " ".round(microtime_float()-$time,2)."\n".$PROT_ID.' CHAIN';$time=microtime_float();}
	$return_code=processChain($ENTRY);	if ($return_code!==true)return $return_code;

	if ($DEBUG){ echo " ".round(microtime_float()-$time,2)."\n".$PROT_ID.' DATABASE';$time=microtime_float();}
	$return_code=processDR($ENTRY,$DR);	if ($return_code!==true)return $return_code;


	if ($DEBUG){ echo " ".round(microtime_float()-$time,2)."\n".$PROT_ID.' GENE ONTOLOGY';$time=microtime_float();}
	$return_code=processGO($ENTRY);	if ($return_code!==true)return $return_code;

	if ($DEBUG){ echo " ".round(microtime_float()-$time,2)."\n".$PROT_ID.' GENE';$time=microtime_float();}
	$return_code=findGene($ENTRY,$GN_INFO);	if ($return_code!==true)return $return_code;
	if ($DEBUG)addLog($PROT_ID.' End process');
	//print_r($ENTRY);
	//process
	return true;
}









function findGene(&$ENTRY,$GN_NAME)
{
$DEBUG=false;
	/// We are going to find a gene using different sources.
	/// 1: GeneID
	/// 2: Ensembl
	/// 3: Gene Symbol

	$ini_gene_id=-1;
	$gene_ids=array();
	
	$ENSG=array();
	/// So we look at all external identifier
	foreach ($ENTRY['extdb'] as $extdb)
	{
		/// To find GeneID (DR   GeneID; 1017; -.)
		if ($extdb['DBNAME']=='GeneID')	
		{
			$gene_id=trim(explode(";",$extdb['value'])[0]);
			$gene_ids[]=$gene_id;
			if ($DEBUG)echo "FIND GENE\tGENE ID:".$gene_id."\n";
		}
		/// Or ensembl gene
		/// DR   Ensembl; ENST00000266970; ENSP00000266970; ENSG00000123374.
		/// DR   Ensembl; ENST00000354056; ENSP00000243067; ENSG00000123374. [P24941-2]
		//// In this case, we are getting the last value (ENSG)
		if ($extdb['DBNAME']=='Ensembl')
		{
			$ENSG[]=$extdb['value'];
			if ($DEBUG)echo "FIND GENE\tENSEMBL:".$extdb['value']."\nn";
		}
	}
	
//if ($DEBUG)	echo $gene_id."\n";
	
	$ALL_geneS=array();
	if ($gene_ids!=array())
	{
		foreach ($gene_ids as $gene_id)
		$ALL_geneS[$gene_id]=array(1.5,true);
	}
	if ($ENSG!=array())
	{
		/// We search for NCBI gene from ensembl Gene
		$query='SELECT DISTINCT gene_id FROM gn_entry GE, gene_seq GS WHERE GS.gn_entry_id = GE.gn_entry_id AND gene_seq_name IN (';
		foreach ($ENSG as $G)$query.="'".$G."',";
		
		$query=substr($query,0,-1).')';
		if ($DEBUG)echo $query."\n";
		$res=runQuery($query);
		
		if ($res===false)failProcess($JOB_ID."015","Unable to run query ".$QUERY);
		
		foreach ($res as $tab)
		{
			if ($DEBUG) echo "FOUND ENSG MATCH:".$tab['gene_id']."\n";
			if (isset($ALL_geneS[$tab['gene_id']]))$ALL_geneS[$tab['gene_id']][0]++;
			else 								   $ALL_geneS[$tab['gene_id']]=array(1,false);
		}
	}
	/// Otherwise, if it's a symbol, we search for the symbol
	/// And we only consider that gene symbol if we didn't find any other valid gene information before.
	if (isset($GN_NAME[0]) && $ALL_geneS==array())
	{
		$query="select gene_id
				 FROM mv_gene G 
				 WHERE symbol='".str_replace("'","''",$GN_NAME[0])."' 
				 AND tax_id='".$ENTRY['prot_entry']['tax_id'].
				"' GROUP BY gene_id";
				if ($DEBUG)echo $query."\n";
		$res=runQuery($query);
		if ($DEBUG)print_R($res);
		if ($res===false) failProcess($JOB_ID."016","Unable to run query ".$QUERY);
		/// We only consider that gene symbol if it is unique
		if (count($res)==1)
		{
			
			foreach ($res as $tab)
			{
				if ($DEBUG) echo "FOUND SYMBOL  MATCH:".$tab['gene_id']."\n";
				if (isset($ALL_geneS[$tab['gene_id']]))$ALL_geneS[$tab['gene_id']][0]++;else $ALL_geneS[$tab['gene_id']]=array(1,false);
			}
		}
	}
	
	///$ALL_geneS should have a key the gene Ids and as value the number of itmes it was found based on the different sources
	if ($DEBUG)print_r($ALL_geneS);
	if (count($ALL_geneS)==1)
	{
		
		$gene_id=array_keys($ALL_geneS)[0];
		$correct_gene_id=checkGeneHistory($gene_id);
		if (isset($ENTRY['gene'][$correct_gene_id]))
		{
			$ENTRY['gene'][$correct_gene_id]['DB_STATUS']='VALID';
		}
		else if ($correct_gene_id!='-')
		{
			$ENTRY['gene'][$gene_id]=array('DB_STATUS'=>'TO_INS');
		}
	}
	/// No gene - OK
	else if (count($ALL_geneS)==0) return true;
	//// If there are multiple genes involved, we consider them all
	else {
		foreach ($ALL_geneS as $gene_id=>$INFO)
		{
			$correct_gene_id=checkGeneHistory($gene_id);
			if (isset($ENTRY['gene'][$correct_gene_id])){$ENTRY['gene'][$correct_gene_id]['DB_STATUS']='VALID';}
			else if ($correct_gene_id!='-')
			{
				$ENTRY['gene'][$correct_gene_id]=array('DB_STATUS'=>'TO_INS');
			}
		}
		// print_r($ENTRY['prot_entry']);
		// print_r($ALL_geneS);
	}
	
	return true;

}






function checkGeneHistory($gene_id,$prev=-2)
{
	/// This is a recursive function that will check the gene history
	/// So first we check if the gene_id is in the history table
	$new_prev=$gene_id;
	$res=runQuery("SELECT alt_gene_id FROM gn_history where gene_id = ".$gene_id);
	
	/// No? We ensure it is then in the gn_entry table
	if ($res==array())
	{
		$res=runQuery("SELECT gene_id FROM gn_entry where gene_id = ".$gene_id);
		/// Still no? then we don't know the gene:
		if ($res==array())	 return '-';

		/// Otherwise we return it:
		else 			return $gene_id;
	}
	/// If there are multiple genes in the history, we cannot make a decision, so we don't know:
	if ($res===false || count($res)!=1)			return '-';
	

	$gene_id = $res[0]['alt_gene_id'];
	//echo "NEW GENE : ".$gene_id."\n";
	
	if ($gene_id=='-')return $gene_id;
	else if ($gene_id==$prev)return $gene_id;
	else  return checkGeneHistory($gene_id,$new_prev);
			
}

function find_first_not_of($str, $chars,$offset=0)
{
  // todo: escape $chars, or not, depending on the desired behavior
	for($I=$offset;$I<strlen($str);++$I) if (strpos($chars,$str[$I])===false)return $I;
	return true;;
}










function processDR(&$ENTRY,$VALUE)
{
	$ISOS=array();
	$LIST_SEQ_DB=array('Ensembl',
	'EnsemblBacteria',
	'EnsemblFungi',
	'EnsemblMetazoa',
	'EnsemblPlants',
	'EnsemblProtists','GenBank','CCDS',
	'TopDownProteomics',
	'Reactome',
	'RefSeq',
	'ProteomicsDB',
	'Ensembl',
	'UCSC');
	$NSEQ=count($ENTRY['prot_seq']);
	foreach($ENTRY['prot_seq'] as $UNS)$ISOS[]=$UNS['iso_id'];
	
	foreach ($VALUE as $line)
	{
		///DR   DrugBank; DB04669; TRIAZOLOPYRIMIDINE
		///DR   ProteomicsDB; 54241; -. [P24941-1]
		/// Always start with the database name, then the ID and some additional info
		$pos=strpos($line,';');
		$DB=substr($line,0,$pos);/// Database Name
		$DB_ID=substr($line,$pos+2);/// Database ID
		$FOUND=false;
		$SEQ='';
		/// If we have only one isoform, it is an easy situation
		if ($NSEQ==1)
		{
			if (in_array($DB,$LIST_SEQ_DB)) 
			{
				//print_r($ENTRY['prot_seq'][array_keys($ENTRY['prot_seq'])[0]]);
				$SEQ=$ENTRY['prot_seq'][array_keys($ENTRY['prot_seq'])[0]]['iso_id'];
			}
		}
		else {
			//// If we have multiple, then we need to check if the line has a specific isoform
			foreach ($ISOS as $ISO)
			if (strpos($line,'['.$ISO.']')!==false)
			{
				$SEQ=$ISO;
				$DB_ID=str_replace('['.$ISO.']','',$DB_ID);
			}
		}
		if (in_array($DB,$LIST_SEQ_DB)) 
		{
			//print_r($ENTRY['prot_seq'][array_keys($ENTRY['prot_seq'])[0]]);
			

			if (substr($DB_ID,-1)=='.')$DB_ID=substr($DB_ID,0,-1);
			$tab=explode(";",$DB_ID);
			foreach ($tab as &$DBV)
			{
				$DBV=trim($DBV);
				if ($DBV=='-.')continue;
				/// Here we compare that line against the list of external identifier
				if (isset($ENTRY['extdb']))
				foreach ($ENTRY['extdb'] as &$EXT)
				{
					if ($EXT['DBNAME']!=$DB)continue;
					if ($EXT['value']!=$DBV)continue;
					$FOUND=true;
					$EXT['DB_STATUS']='VALID';
					break;
					
				}
				if ($FOUND)continue;
				/// Not ofund? let's create it
				$ENTRY['extdb'][]=array('DBID'=>-1,'extdbID'=>-1,'value'=>$DBV,'DB_STATUS'=>'TO_INS','SEQ'=>$SEQ,'DBNAME'=>$DB);
			}
		}
		else 
		{
			/// Here we compare that line against the list of external identifier
			if (isset($ENTRY['extdb']))
			foreach ($ENTRY['extdb'] as &$EXT)
			{
				if ($EXT['DBNAME']!=$DB)continue;
				if ($EXT['value']!=$DB_ID)continue;
				$FOUND=true;
				$EXT['DB_STATUS']='VALID';
				break;
				
			}
			if ($FOUND)continue;
			/// Not ofund? let's create it
			$ENTRY['extdb'][]=array('DBID'=>-1,'extdbID'=>-1,'value'=>$DB_ID,'DB_STATUS'=>'TO_INS','SEQ'=>$SEQ,'DBNAME'=>$DB);
		}
	}
	
	return true;
}








function processGO(&$ENTRY)
{
	global $STATIC_DATA;
	/// GO is a subset of Database Relationships in Uniprot
	///DR   GO; GO:0015030; C:Cajal body; IDA:UniProtKB.
	// DR   GO; GO:0005813; C:centrosome; IDA:HPA.
	// DR   GO; GO:0000781; C:chromosome, telomeric region; IEA:Ensembl.
	// DR   GO; GO:0000793; C:condensed chromosome; IEA:Ensembl.
	// DR   GO; GO:0097123; C:cyclin A1-CDK2 complex; IEA:Ensembl.
	/// Technically ALL External db should have already been processed and stored in extdb array
	foreach ($ENTRY['extdb'] as $K=>$E)
	{
		/// So we look for all GO
		if ($E['DBNAME']!='GO')continue;
		$tab2=explode(";",$E['value']);
		
		//print_r($tab2);
		/// Get the accession ID
		$GO_AC=$tab2[0];
		
		
		/// Which should be in the list of accession from GO Database
		if (!isset($STATIC_DATA['GO'][$GO_AC])){continue;}
		
		
		/// Third column as a : separator ( IEA:Ensembl)
		$tab3=explode(":",$tab2[2]);
		$evidence=trim($tab3[0]);// Evidence is before the :. IEA
		$source=substr(trim($tab3[1]),0,-1);/// Source if after the : -> Ensembl
		unset($ENTRY['extdb'][$K]);/// Since we know it is a GO entry, we remove it from the ENTRY['extdb'] to add it to ENTRY['GO]
		
		
		if (isset($ENTRY['GO'][$GO_AC]))
		{
			$E_GO=&$ENTRY['GO'][$GO_AC];
			$E_GO['DB_STATUS']='VALID';
			if ($evidence!=$E_GO['evidence']){$E_GO['evidence']=$evidence;$E_GO['DB_STATUS']='TO_UPD';}
			if (strcasecmp($source,$E_GO['source'])!=0){$E_GO['source']=$source;$E_GO['DB_STATUS']='TO_UPD';}
			continue;
		}
		/// Not found -> insert it
		$ENTRY['GO'][$GO_AC]=array(
			'DBID'=>-1,
			'evidence'=>$evidence,
			'go_entry_id'=>$STATIC_DATA['GO'][$GO_AC],
			'source'=>$source,
			'DB_STATUS'=>'TO_INS');
	}
	
	return true;
//print_r($ENTRY['GO']);exit;
}











function processChain(&$ENTRY)
{
	$DEBUG=false;
	$TMP_CH=array();
	$LIST_ft=array();
	/// All feature should have been processed prior to starting the chain
	foreach ($ENTRY['ft'] as $ft_INFO)
	{
		/// We only consider CHAIN, REGION, DOMAIN or REPEAT (note the ! at the beginning)
		if (!($ft_INFO['type']=='CHAIN'||
			  $ft_INFO['type']=='REGION'||
			  $ft_INFO['type']=='DOMAIN'||
			  $ft_INFO['type']=='REPEAT'))continue;


		/// We ignore those without boundaries
		if ($ft_INFO['start']=='?' || $ft_INFO['end']=='?')continue;


		/// And we simplify by removing all fuzzy range
		$end=preg_replace('/[<>=?]/','',$ft_INFO['end']);
		$start=preg_replace('/[<>=?]/','',$ft_INFO['start']);

		$NAME=$ft_INFO['type'].'-'.$start.'-'.$end;
		$LIST_ft[$NAME][]=$ft_INFO;
	}
	if ($DEBUG)print_r($LIST_ft);
	
	/// We want to find the primary sequence, from which those sequences are based upon
	$PRIM_SEQ_POS=-1;

	foreach ($ENTRY['prot_seq'] as $NID=>$SQINFO)
	{
		$T=$SQINFO;
		unset($T['SEQ']);
		//print_r($T);
		if ($SQINFO['is_primary']=='F')continue;
		$PRIM_SEQ_POS=$NID;break;
		if (!isset($SQINFO['SEQ']) || count($SQINFO['SEQ'])==0) return 'No sequence found in primary record';
	}
	if ($PRIM_SEQ_POS==-1)return 'No primary sequence found';




	foreach ($LIST_ft as $ftS)
	{
		
		/// We ensure we have clear boundaries
		$FOUND=false;
		$end=preg_replace('/[<>=?]/','',$ftS[0]['end']);
		$start=preg_replace('/[<>=?]/','',$ftS[0]['start']);
		
		if ($start=='?' || $end=='?')continue;
		/// Some information can be on multi lines, so we concatenate them
		$VALUE='';
		foreach ($ftS as $T)$VALUE.=$T['value'].';';
		$VALUE=substr($VALUE,0,-1);
		
		/// Then we compare against existing chains
		foreach ($ENTRY['prot_dom'] as &$DOM_INFO)
		{
			
			if ($ftS[0]['type']!=$DOM_INFO['type']||
					     $start!=$DOM_INFO['start']||
						   $end!=$DOM_INFO['end'])continue;
			// Found it? Let's compare the sequence then
			if ($DEBUG) echo "FOUND\n";
			$FOUND=true;
			$DOM_INFO['DB_STATUS']='VALID';
			/// There should be a sequence, if not, we need to create it.
			if (!isset($DOM_INFO['SEQ']))
			{
				$DOM_INFO['SEQ_DB_STATUS']='TO_INS';
				$N=0;
				$SEQ_INFO=$ENTRY['prot_seq'][$PRIM_SEQ_POS];
				for ($I=$start;$I<=$end;++$I)
				{
					++$N;
					if (!isset($SEQ_INFO['SEQ'][$I])) {return 'Unable to find '.$I.' '.$DOM_INFO['type']."\t".$DOM_INFO['start']."\t".$DOM_INFO['end']."\n";}
					$DOM_INFO['SEQ'][$N]=array('DBID'=>-1,'SQ'=>-1,'POS_SQ'=>$I,'AA'=>$SEQ_INFO['SEQ'][$I]['AA']);
				}
			}
			else
			{
				$VALID=true;
				$N=0;
				$SEQ_INFO=$ENTRY['prot_seq'][$PRIM_SEQ_POS];
				for ($I=$start;$I<=$end;++$I)
				{
					++$N;
					if (!isset($SEQ_INFO['SEQ'][$I])) {return 'Unable to find '.$I.' '.$DOM_INFO['type']."\t".$DOM_INFO['start']."\t".$DOM_INFO['end']."\n";}
					if (!isset($DOM_INFO['SEQ'][$N])){$VALID=false;break;}
					if ($DOM_INFO['SEQ'][$N]['POS_SQ']!=$I){$VALID=false;break;}
					if ($DOM_INFO['SEQ'][$N]['SQ']!=$SEQ_INFO['SEQ'][$I]['DBID']){$VALID=false;break;}
				}
				if (!$VALID)
				{
					$DOM_INFO['SEQ_DB_STATUS']='TO_UPD';
					$DOM_INFO['SEQ']=array();
					$N=0;
					$SEQ_INFO=$ENTRY['prot_seq'][$PRIM_SEQ_POS];
					for ($I=$start;$I<=$end;++$I)
					{
						++$N;
						if (!isset($SEQ_INFO['SEQ'][$I])) {return 'Unable to find '.$I.' '.$DOM_INFO['type']."\t".$DOM_INFO['start']."\t".$DOM_INFO['end']."\n";}
						$DOM_INFO['SEQ'][$N]=array('DBID'=>-1,'SQ'=>-1,'POS_SQ'=>$I,'AA'=>$SEQ_INFO['SEQ'][$I]['AA']);
					}
					
				}else $DOM_INFO['SEQ_DB_STATUS']='VALID';
			}
			if ($VALUE!=$DOM_INFO['domain_name'] && $DOM_INFO['DB_STATUS']!='TO_INS'){
				$DOM_INFO['domain_name']=$VALUE;
				$DOM_INFO['DB_STATUS']='TO_UPD';
			}
			if ($FOUND)break;
		}

		if ($FOUND)continue;
		/// Not found, let's create it
		$NAME=$ftS[0]['type'].$ftS[0]['start'].$ftS[0]['end'].$ftS[0]['value'];
		$ENTRY['prot_dom'][$NAME]=array('domain_name'=>$VALUE,
		'type'=>$ftS[0]['type'],
		'start'=>$start,
		'end'=>$end,
		'modification_date'=>'NOW()',
		'DB_STATUS'=>'TO_INS',
		'SEQ_DB_STATUS'=>'TO_INS');
		$N=0;
		$SEQ_INFO=$ENTRY['prot_seq'][$PRIM_SEQ_POS];
		///Then create the sequence
		for ($I=$start;$I<=$end;++$I)
		{
			++$N;
			if (!isset($SEQ_INFO['SEQ'][$I]))  {return 'Unable to find '.$I.' '.$NAME.' '.print_r($ENTRY['prot_seq'][$PRIM_SEQ_POS],true)."\n";}
			$ENTRY['prot_dom'][$NAME]['SEQ'][$N]=array('DBID'=>-1,'SQ'=>-1,'POS_SQ'=>$I,'AA'=>$SEQ_INFO['SEQ'][$I]['AA']);
		}
		
	}
	
	if ($DEBUG)
	{
		foreach ($ENTRY['prot_dom'] as $K)
		{
			$T=$K;unset($T['SEQ']);
			print_r($T);
		}
	}
	
	//print_r($ENTRY['prot_dom']);
	
	return true;;

}






function processFeatures(&$ENTRY,&$VALUES)
{
	$DEBUG=false;
//For debug purposes, we list all features
	if ($DEBUG)
	{
		foreach ($ENTRY['ft'] as $FT)
		{
			echo $FT['DBID']."\t".$FT['type']."\t".$FT['start'].'-'.$FT['end']."\t".$FT['value']."\t".$FT['INFO']."\t".$FT['SEQ']."\t".$FT['SEQ_NAME']."\t".$FT['DB_STATUS']."\n";
			foreach ($FT['PUBLI'] as $PUB)echo "\t".$PUB['DBID']."\t".$PUB['pmid']."\t".$PUB['ECO']."\t".$PUB['DB_STATUS']."\n";
						

		}
	}
	
	$PRIM_SEQ_POS=getPrimSeq($ENTRY);
	if ($PRIM_SEQ_POS==-1)return 'No primary sequence found';
	/// Finding the canonical sequence
	// $PRIM_SEQ_POS='';
	// foreach ($ENTRY['prot_seq'] as $NID=>$SQINFO)
	// {
	// 	// $T=$SQINFO;
	// 	// unset($T['SEQ']);
	// 	//print_r($T);
	// 	if ($SQINFO['is_primary']=='F')continue;
	// 	$PRIM_SEQ_POS=$SQINFO['iso_id'];break;
	// 	if (!isset($SQINFO['SEQ']) || count($SQINFO['SEQ'])==0) return 'No sequence found in primary record';
	// }
	// if ($PRIM_SEQ_POS=='')return 'No primary sequence found';


	global $STATIC_DATA;
	$TMP_ft=array();
	global $GLB_VAR;
	
	for ($I=0;$I<count($VALUES);++$I)
	{
		/// Processing each line
		///  FT   <TYPE>          <Location>
		/// (FT                   /<Qualifier>(="<Value>")?)*
		$line=$VALUES[$I];
		$pos=strpos($line,' ');
		$HEAD=substr($line,0,$pos);
		$pos2=find_first_not_of($line," ",$pos);
		//echo $HEAD."\t|".substr($line,16)."|\n";
		if ($HEAD=='')continue;
		/// Finding the location:
		if (strpos($line,'.',16)!==false){
		$start=substr($line,$pos2,strpos($line,'.',$pos2)-$pos2);
		$end=substr($line,strpos($line,'.',$pos2)+2);
		}
		else
		{
			$start=substr($line,16);
			$end=substr($line,16);
		}
		/// By default those features are mapped to the primary sequence
		/// but in some cases, it can be specific to a given isoform:
		$ft_seq_id=$PRIM_SEQ_POS;
		if (strpos($start,':')!==false)
		{
			$ft_seq_id = substr($start,0,strpos($start,':'));
			$start=substr($start,strpos($start,':')+1);


		}
		if (strpos($end,':')!==false)
		{
			$ft_end_seq_id = substr($end,0,strpos($end,':'));
			if ($ft_seq_id!=null && $ft_seq_id!=$ft_end_seq_id) return false;

			$end=substr($end,strpos($end,':')+1);
		}
		
		/// No next line? we save it in tmp_ft and move on
		if (isset($VALUES[$I+1]) && strpos($VALUES[$I+1],' ')>10)
		{
				$TMP_ft[]=array('type'=>$HEAD,'start'=>$start,'end'=>$end,'SEQ_NAME'=>$ft_seq_id);
				--$I;
				continue;
		}
		//// Otherwise we Look at additional lines
		$head_line='';
		$ADD_DATA=array();
		for ($I+=1;$I<count($VALUES);++$I)
		{
			$line=$VALUES[$I];
			if ($line[0]!=' '){ --$I;break;}
			$TMP_V=substr($VALUES[$I],17);
			$pos=strpos($TMP_V,'=');
			if ($pos!==false){
				$head_line=substr($TMP_V,0,$pos);
				$ADD_DATA[$head_line]=substr($TMP_V,$pos+1);
			}
			else
			{
				if (substr($ADD_DATA[$head_line],-1)!=' ')$ADD_DATA[$head_line].=' ';
				$ADD_DATA[$head_line].=substr($VALUES[$I],16);
				$ADD_DATA[$head_line]=trim($ADD_DATA[$head_line]);
			}
		}
		foreach ($ADD_DATA as $head=>$value)$ADD_DATA[$head]=substr($value,1,-1);

		$TMP_ft[]=array('type'=>$HEAD,'start'=>$start,'end'=>$end,'INFO'=>$ADD_DATA,'SEQ_NAME'=>$ft_seq_id);
	}
	if ($DEBUG)print_r($TMP_ft);

	//// Now that we have all features, we compare them against the database
	foreach ($TMP_ft as &$ft_DATA)
	{
		$FOUND_ft=false;
		if ($DEBUG) echo "|".$ft_DATA['type']."|\t".$ft_DATA['start']."\t".$ft_DATA['end']."\t".$ft_DATA['SEQ_NAME']."\n";
		if (isset($ENTRY['ft']))
		foreach ($ENTRY['ft'] as $ID_REF=>&$ft_REF)
		{
			/// The feature must be of the same type, range, the same sequence and not
			if (strcmp($ft_REF['type'],$ft_DATA['type'])!=0)	continue;
			if ($DEBUG) echo "\t\t|".$ft_REF['type']."|\t".$ft_REF['start']."\t".$ft_REF['end']."\t".$ft_REF['SEQ_NAME']."\t";
			if ($ft_REF['start']!=$ft_DATA['start']){if ($DEBUG) echo "DIFFSTART\n";continue;}
			if ($ft_REF['end']!=$ft_DATA['end']){if ($DEBUG) echo "DIFFEND\n";continue;}
			if ($ft_REF['SEQ_NAME']!=$ft_DATA['SEQ_NAME']){if ($DEBUG) echo "DIFFSEQNAME\n";continue;}
			
			if (isset($ft_DATA['INFO']['note']))
			if ($ft_REF['value']!=$ft_DATA['INFO']['note']){if ($DEBUG) echo "DIFFNOTE\n";continue;}
			/// By default it means we found it.
			if ($ft_REF['DB_STATUS']=='FROM_DB')$ft_REF['DB_STATUS']='VALID';
			if ($DEBUG) echo "FOUND\n";
			
			//// Then we lead at the different prot_feat_pmid records
			$FOUND_ft=true;
			foreach ($ft_DATA['INFO'] as $type_info=>$value_info)
			{
				$FOUND_INFO=false;
				if ($type_info=='note')continue;
				if (strpos($value_info,'PubMed')!==false)
				{
					$tab=explode(",",$value_info);
					foreach ($tab as $T)
					{
						if (strpos($T,'PubMed')===false)continue;
						$T2=explode("|",$T);
						$ECO=$STATIC_DATA['ECO']['ECO_'.substr(trim($T2[0]),4)];
						$pmid=substr(trim($T2[1]),7);
						$QUERY="SELECT pmid_entry_id FROM pmid_entry WHERE pmid=".$pmid;

						$res=runQuery($QUERY);
						
						if ($res===false) failProcess($JOB_ID."017","Unable to run query ".$QUERY);
						if (count($res)!=1)continue;
						$FOUND=false;
						foreach ($ft_REF['PUBLI'] as &$ref_pub_entry)
						{
								if ($ref_pub_entry['pmid']!=$res[0]['pmid_entry_id'])continue;
								if ($ref_pub_entry['ECO']!=$ECO)continue;
								$ref_pub_entry['DB_STATUS']='VALID';
								$FOUND=true;
								break;
						}
						if (!$FOUND)
						$ft_REF['PUBLI'][]=array('ECO'=>$ECO,'pmid'=>$res[0]['pmid_entry_id'],'DB_STATUS'=>'TO_INS');
						

					}
				}
				else {
					if (isset($ft_REF['INFO']))
					foreach ($ft_REF['INFO'] as $info_ref=>&$value_ref)
					{
							if ($info_ref!=$type_info)continue;
							$FOUND_INFO=true;
							$value_ref['DB_STATUS']='VALID';
							if ($value_ref['value']!=$value_info)
							{
								$value_ref['value']=$value_info;
								$value_ref['DB_STATUS']='TO_UPD';
							}
							break;
					}
					if ($FOUND_INFO)continue;
					$ft_REF['INFO'][$type_info]=array('DB_STATUS'=>'TO_INS','DBID'=>-1,'value'=>$value_info);
				}
			}
			break;
		}
		if ($FOUND_ft)continue;
		/// Not found, we add them
		$INFO=array();$PUBLI=array();
		foreach ($ft_DATA['INFO'] as $type_info=>$value_info)
			{
				if ($type_info=='note')continue;
				if (strpos($value_info,'PubMed')!==false)
				{
				$tab=explode(",",$value_info);
				foreach ($tab as $T)
				{
					if (strpos($T,'PubMed')===false)continue;
					$T2=explode("|",$T);
					$ECO=$STATIC_DATA['ECO']['ECO_'.substr(trim($T2[0]),4)];
					$pmid=substr(trim($T2[1]),7);
					$QUERY="SELECT pmid_entry_id FROM pmid_entry WHERE pmid=".$pmid;
					$res=runQuery($QUERY);
					if ($res===false) failProcess($JOB_ID."018","Unable to run query ".$QUERY);
					if (count($res)==1)
						$PUBLI[]=array('ECO'=>$ECO,'pmid'=>$res[0]['pmid_entry_id'],'DB_STATUS'=>'TO_INS','DBID'=>-1);
					
					
					

				}
				}else 				$INFO[$type_info]=array('DB_STATUS'=>'TO_INS','DBID'=>-1,'value'=>$value_info);
				
			}
			
			//if (!isset($ft_DATA['INFO']['note'])){echo $ENTRY['prot_entry']['prot_identifier'];print_r($ft_DATA);}
		$ENTRY['ft'][]=array('DBID'=>-1,'DB_STATUS'=>'TO_INS','type'=>$ft_DATA['type'],'start'=>$ft_DATA['start'],'end'=>$ft_DATA['end'],
		'value'=>isset($ft_DATA['INFO']['note'])?$ft_DATA['INFO']['note']:'','INFO'=>$INFO,'PUBLI'=>$PUBLI,'SEQ_NAME'=>$ft_DATA['SEQ_NAME']);
		

	}
	// foreach ($ENTRY['ft'] as $FT)
	// 	{
	// 		if ($FT['DB_STATUS']=='TO_INS'||$FT['DB_STATUS']=='FROM_DB')
	// 		echo $FT['DBID']."\t".$FT['type']."\t".$FT['start'].'-'.$FT['end']."\t".$FT['value']."\t".$FT['SEQ_NAME']."\t".$FT['DB_STATUS']."\n";
			
						

	// 	}
	//print_r($ENTRY['ft']);
	if ($DEBUG)
	{
		echo "\n\n\n\n#############################\n";
		foreach ($ENTRY['ft'] as $FT)
		{
			//print_r($FT);
			echo $FT['DBID']."\t".$FT['type']."\t".$FT['start'].'-'.$FT['end']."\t".$FT['value']."\t".$FT['SEQ_NAME']."\t".$FT['DB_STATUS']."\n";
			foreach ($FT['PUBLI'] as $PUB)echo "\t".$PUB['DBID']."\t".$PUB['pmid']."\t".$PUB['ECO']."\t".$PUB['DB_STATUS']."\n";
						

		}
		//exit;
	}
	return true;;
	
	
}




function getPrimSeq(&$ENTRY)
{
	$DEBUG=false;
	
	$SEQ='';
$ALT_NAMES=array();
//print_r($ENTRY['ac']);
	$PRIM_ac=array_keys($ENTRY['ac'])[0];
	$N_comments=count($ENTRY['comments']);
	foreach ($ENTRY['comments'] as &$COMMENT)
	{
		
		
		if (isset($COMMENT['type'])&& $COMMENT['type']!='ALTERNATIVE PRODUCTS')continue;
		
		$tab=explode(";",$COMMENT['value']);
		for ($I=0;$I<count($tab);++$I)
		{
			$pos=strpos($tab[$I],'Name=');
			if ($pos===false)continue;
			$NAME=substr($tab[$I],$pos+5);
			//echo $NAME."\n";
			if (strpos($tab[$I+1],'Name=')!==false)continue;
			$ALT_NAMES[$NAME]=array('iso_id'=>array(),'SEQ_NAME'=>array());
			
			for ($I+=1;$I<count($tab);++$I)
			{
				$pos=strpos($tab[$I],'IsoId=');if ($pos!==false)$ALT_NAMES[$NAME]['iso_id']=explode(",",str_replace(" ","",substr($tab[$I],$pos+6)));
				$pos=strpos($tab[$I],'Sequence=');if ($pos!==false)$ALT_NAMES[$NAME]['SEQ_NAME']=explode(",",str_replace(" ","",substr($tab[$I],$pos+9)));
				if (strpos($tab[$I],'Name=')!==false){--$I;break;}
			}
			
			
		}
		//print_r($tab);
		
	}
	if ($DEBUG){echo "###### ALT NAMES:\n";print_r($ALT_NAMES);}


	/// The primary sequence might not be iso_id=ac-1 but possibly ac-n.
	/// Therefore we need to look at the one defined as "Displayed"
	$PRIM_ID=-1;
	foreach ($ALT_NAMES as $TID=>$TINFO)
	{
		if (!isset($TINFO['SEQ_NAME'][0])){echo $TID."\n"; print_r($TINFO);}
		if ($TINFO['SEQ_NAME'][0]=='Displayed')$PRIM_ID=$TID;
	}

	/// Sadly can't remember why I did that.... sorry
	if ($PRIM_ID==-1)
	{
		for ($I=1;$I<10;++$I)
		{
			if (isset($ALT_NAMES[$I]))continue;
		$ALT_NAMES[$I]=array('iso_id'=>array($PRIM_ac.'-1'),'SEQ_NAME'=>array($PRIM_ac.'-1'));
		$PRIM_ID=$I;
		break;
		}
	}
	//echo "PRIMARY SEQUENCE: ".$ALT_NAMES[$PRIM_ID]['iso_id'][0]."\n";
	
	return $ALT_NAMES[$PRIM_ID]['iso_id'][0];
}







function processSequence(&$ENTRY,&$VALUES,$FPATH)
{
	$DEBUG=false;

	
	$INFO=array_values(array_filter(explode(" ",$VALUES[0])));
	
	if ($DEBUG)
	{
		echo "\n\n#########\nINITIAL\n\n\n";
		foreach ($ENTRY['prot_seq'] as $TID=>$IF)
		{
			$T=$IF;
			unset($T['SEQ']);
			echo "#### ".$TID."\n";
			print_r($T);
		}
		
	}
	$time=microtime_float();
	

	$CRC64=$INFO[5];
	$SEQ='';
	$ALT_NAMES=array();
//print_r($ENTRY['ac']);
	$PRIM_ac=array_keys($ENTRY['ac'])[0];
	$N_comments=count($ENTRY['comments']);
	foreach ($ENTRY['comments'] as &$COMMENT)
	{
		
		
		if (isset($COMMENT['type'])&& $COMMENT['type']!='ALTERNATIVE PRODUCTS')continue;
		
		$tab=explode(";",$COMMENT['value']);
		for ($I=0;$I<count($tab);++$I)
		{
			$pos=strpos($tab[$I],'Name=');
			if ($pos===false)continue;
			$NAME=substr($tab[$I],$pos+5);
			//echo $NAME."\n";
			if (strpos($tab[$I+1],'Name=')!==false)continue;
			$ALT_NAMES[$NAME]=array('iso_id'=>array(),'SEQ_NAME'=>array());
			
			for ($I+=1;$I<count($tab);++$I)
			{
				$pos=strpos($tab[$I],'IsoId=');if ($pos!==false)$ALT_NAMES[$NAME]['iso_id']=explode(",",str_replace(" ","",substr($tab[$I],$pos+6)));
				$pos=strpos($tab[$I],'Sequence=');if ($pos!==false)$ALT_NAMES[$NAME]['SEQ_NAME']=explode(",",str_replace(" ","",substr($tab[$I],$pos+9)));
				if (strpos($tab[$I],'Name=')!==false){--$I;break;}
			}
			
			
		}
		//print_r($tab);
		
	}
	if ($DEBUG){
		echo "TIME:".round(microtime_float()-$time,3)."s\n";$time=microtime_float();
		echo "###### ALT NAMES:\n";print_r($ALT_NAMES);}


	/// The primary sequence might not be iso_id=ac-1 but possibly ac-n.
	/// Therefore we need to look at the one defined as "Displayed"
	$PRIM_ID=-1;
	foreach ($ALT_NAMES as $TID=>$TINFO)
	{
		if (!isset($TINFO['SEQ_NAME'][0])){echo $TID."\n"; print_r($TINFO);}
		if ($TINFO['SEQ_NAME'][0]=='Displayed')$PRIM_ID=$TID;
	}

	/// Sadly can't remember why I did that.... sorry
	if ($PRIM_ID==-1)
	{
		for ($I=1;$I<10;++$I)
		{
			if (isset($ALT_NAMES[$I]))continue;
		$ALT_NAMES[$I]=array('iso_id'=>array($PRIM_ac.'-1'),'SEQ_NAME'=>array($PRIM_ac.'-1'));
		$PRIM_ID=$I;
		break;
		}
	}
	if ($DEBUG){
		echo "TIME:".round(microtime_float()-$time,3)."s\n";$time=microtime_float();
		echo "###### ALT NAMES:\n";print_r($ALT_NAMES);}
	
	/// Primary sequence:
	$SEQ='';
	for ($I=1;$I<count($VALUES);++$I)$SEQ.=str_replace(" ",'',$VALUES[$I]);
	$HAS_PRIMARY=false;
	
	/// Looking at all protein sequence - (at this step the data in ENTRY['prot_seq'] is coming from the database)
	foreach ($ENTRY['prot_seq'] as $prot_seq_id=>&$SEQ_INFO)
	{
		$TT=$SEQ_INFO;
		unset($TT['SEQ']);
		
		if ($SEQ_INFO['is_primary']!='T')continue;///to search the primary sequence
		//echo $SEQ_INFO['iso_name']."\n";
		$SEQ_REF='';
		$HAS_PRIMARY=true;
		/// Rebuilding the sequence
		if (isset($SEQ_INFO['SEQ']))foreach ($SEQ_INFO['SEQ'] as $P)$SEQ_REF.=$P['AA'];
		if ($DEBUG) echo $SEQ_INFO['iso_name'].' => Same sequence:'.($SEQ_REF==$SEQ)."\t".strlen($SEQ_REF).' '.strlen($SEQ)."\n";
		
		/// Checking the canonical sequence is the same from the database and from the record
		if ($SEQ_REF==$SEQ){
			$SEQ_INFO['DB_STATUS']='VALID';
			$SEQ_INFO['SEQ_DB_STATUS']='VALID';
		
			/// So we check the Isoform identifier is still the same
			if (!in_array($SEQ_INFO['iso_id'],$ALT_NAMES[$PRIM_ID]['iso_id']) && $ALT_NAMES[$PRIM_ID]['iso_id']!='Displayed')
			{

				echo "\nCHANGE\t".$SEQ_INFO['iso_id']."\t".$ALT_NAMES[$PRIM_ID]['iso_id'][0]."\n";
				$SEQ_INFO['iso_id']=$ALT_NAMES[$PRIM_ID]['iso_id'][0];
				$SEQ_INFO['DB_STATUS']='TO_UPD';
			}
			/// And the sequence name too, otherwise, we will have to update it.
			
			if ($SEQ_INFO['iso_name']!=implode(",",$ALT_NAMES[$PRIM_ID]['SEQ_NAME']))
			{
				$SEQ_INFO['iso_name']=implode(",",$ALT_NAMES[$PRIM_ID]['SEQ_NAME']);
				$SEQ_INFO['DB_STATUS']='TO_UPD';
			}
		
			continue;
		}
		/// If we get here, the canonical sequence from the database is different then from the file
		/// so ALL isoforms, domains, features become obsolete
		/// Therefore we copy them over the OLD_**, so we can delete them later
		$ENTRY['OLD_prot_seq']=$ENTRY['prot_seq'];
		$ENTRY['OLD_prot_dom']=$ENTRY['prot_dom'];
		$ENTRY['prot_seq']=array();
		$ENTRY['prot_dom']=array();
		$HAS_PRIMARY=false;
		if (isset($ENTRY['prot_feat']))
		{
			$ENTRY['OLD_prot_feat']=$ENTRY['prot_feat'];
			$ENTRY['prot_feat']=array();
		}
		//$ENTRY['prot_seq']=
		
		/// Then we create a new record
		
		$DATA_ENTRY['prot_seq'][$ALT_NAMES[$PRIM_ID]['iso_id'][0]]=array('iso_name'=>implode(",",$ALT_NAMES[$PRIM_ID]['SEQ_NAME']),
											 'iso_id'=>$ALT_NAMES[$PRIM_ID]['iso_id'][0],
											'is_primary'=>'T',
											'description'=>'',
											'modification_date'=>'',
											'DB_STATUS'=>'TO_INS',
										'SEQ_DB_STATUS'=>'TO_INS','SEQ'=>array());
			for($I=0;$I<strlen($SEQ);++$I)$DATA_ENTRY['prot_seq'][$ALT_NAMES[$PRIM_ID]['iso_id'][0]]['SEQ'][$I+1]=array('DBID'=>-1,'AA'=>substr($SEQ,$I,1));
		//$DATA_ENTRY['prot_seq'][$tab[0]]['SEQ'][$tab[2]]=array('DBID'=>$tab[0],'AA'=>$tab[3]);
	}
	if ($DEBUG)echo "TIME:".round(microtime_float()-$time,3)."s\n";$time=microtime_float();
	
	/// NEXT: Isoforms
	$MAP_SEQ=array('PROTEOMES/ALL_PROT_UNIPROT.txt'=>'PROTEOMES/ALL_SEQ.txt',
				   'SPROT/uniprot_sprot.dat'=>'SPROT/uniprot_all.fasta',
				   'TREMBL/TREMBL_SEL'=>'TREMBL/uniprot_trembl.fasta',
				'ALT/ALT_ENTRIES.txt'=>'ALT/ALT_ENTRIES.fasta');
	$POINTERS=array('PROTEOMES/ALL_PROT_UNIPROT.txt'=>'PROTEOMES/ALL_SEQ.pointers',
	'SPROT/uniprot_sprot.dat'=>'SPROT/uniprot_all_fasta.pointers',
	'TREMBL/TREMBL_SEL'=>'TREMBL/uniprot_trembl_fasta.pointers',
	'ALT/ALT_ENTRIES.txt'=>'ALT/ALT_ENTRIES.pointers');
	global $JOB_ID;

	if ($DEBUG)echo "PRIMARY ac:".$PRIM_ac."\n";
	if ($DEBUG)echo "FILE:".$FPATH.' || ../../'.$MAP_SEQ[$FPATH]."\n";
	/// If it is a TrEMBL record
	$fpos=-1;
	
		exec("egrep -m 1 '^".$PRIM_ac."\s' ../".$POINTERS[$FPATH],$res,$return_code);
		if ($return_code==0)
		{
			//print_r($res);
			if (count($res)==1)
			{
				$tab=explode("\t",$res[0]);
				$fpos=$tab[2];
			}
		}
	
	if ($DEBUG)echo "GET ISOFORM - TIME:".round(microtime_float()-$time,3)."s\n";$time=microtime_float();

	$fp=fopen('../'.$MAP_SEQ[$FPATH],'r');if (!$fp)failProcess($JOB_ID."019","Unable to open file ".$FILE);
	if ($fpos!=-1)fseek($fp,$fpos);
	$SEQS=array();$NAME='';
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
	//	echo $line."\n";
		/// Some ac can be found in other: B4HVU2 and A0A0B4HVU2
		/// Therefore we need to add a prefix. Since two prefixs are available: sp| for swiss-prot and tr| for trembl, we put them both.
		if (preg_match("/(sp|tr)\|".$PRIM_ac.'(\-[0-9]{1,10}){0,1}\|/',$line,$matches)==0)break;
		$tab=explode("|",$line);
		if ($DEBUG)echo $line."\n";
		$NAME=$tab[1];
		if (strpos($NAME,'-')===false)$NAME.='-1';
		$FULL_NAME='';
		$pos=strpos($line,'OS');
		$p1=strpos($line," ");
		if ($pos!==false)$FULL_NAME=substr($line,$p1+1,$pos-$p1-1);
		else $FULL_NAME=substr($line,$p1);
		$SEQ=array('INFO'=>$line,'SEQ'=>'','NAME'=>$FULL_NAME,'IS_ISOFORM'=>(strpos($FULL_NAME,'Isoform')!==false));
		do
		{
			$pos=ftell($fp);
			$line=stream_get_line($fp,1000,"\n");
			if ($line[0]=='>'){fseek($fp,$pos);break;}
			$SEQ['SEQ'].=$line;
		}while(!feof($fp));
		$SEQS[$NAME]=$SEQ;
		
	}
	fclose($fp);
	if ($DEBUG)echo "READ FASTA FILE TIME:".round(microtime_float()-$time,3)."s\n";$time=microtime_float();
	echo "N SEQ:". count($SEQS)."\n";
	
		//print_r($SEQS);
		//exit;
	
		
	/// Looking at ft
	$ftS=array();
	///An isoform can be defined by multiple VAR_SEQ features due to multiple insertion/deletion sites.
	foreach ($ENTRY['ft'] as $INFO_ft)
	{
		if ($INFO_ft['type']!='VAR_SEQ')continue;
		//print_r($INFO_ft);
		$NAME=$INFO_ft['INFO']['id']['value'];
		$iso_id='';
		foreach ($ALT_NAMES as $ALT_NAME)
		{
			if (!in_array($NAME,$ALT_NAME['SEQ_NAME']))continue;
			foreach ($ALT_NAME['iso_id'] as $IDD)
			$ftS[$IDD][]=$INFO_ft;;
		}
		
	}
	if ($DEBUG)echo "FEATURES:".round(microtime_float()-$time,3)."s\n";$time=microtime_float();
	if ($DEBUG){echo "########### ftS #############\n";print_r($ftS);}
	if ($DEBUG){echo "########### SEQS #############\n";print_r($SEQS);}

	/// In the case where there is only 1 sequence, no isoforms.
	if ($ftS==array() && count($SEQS)==1)	$ftS[array_keys($SEQS)[0]]=array();

	
	
	
	foreach ($ftS as $iso_id=>$INFO_ftS){
		if ($DEBUG)echo "############## APPLYING ftS \tiso_id:".$iso_id."\n";
		if (!isset($SEQS[$iso_id])) {if ($DEBUG) echo "NOT FOUND\n";continue;}
		$SEQ=$SEQS[$iso_id]['SEQ'];
		//echo $SEQ."\n";
		$description=trim($SEQS[$iso_id]['NAME']);
		
		$note='';
		$NAME='';
		
		foreach ($INFO_ftS as $ftI)
		{
			
			if (isset($ftI['value']))			$note.=$ftI['value'].' ['.$ftI['start'].'-'.$ftI['end'].'] ; ';
			if (isset($ftI['INFO']['id']['value']))$NAME.=$ftI['INFO']['id']['value'].' ; ';
		}
		if ($NAME=='')$NAME=$iso_id;
		else $NAME=substr($NAME,0,-3);
		if ($note!='')
		{
			$note=preg_replace("/ \(in isoform [0-9]{1,2}\)/","",$note);
			$note=preg_replace("/ \(in isoform [0-9]{1,2} and isoform [0-9]{1,2}\)/","",$note);
			if (strlen($note)>4000)$note=substr($note,0,3990).'...';
		}
		
		$FOUND=false;
		foreach ($ENTRY['prot_seq'] as $prot_seq_id=>&$SEQ_INFO)
		{
			//if ($SEQ_INFO['is_primary']=='T')continue;
			$SEQ_REF='';
			if (isset($SEQ_INFO['SEQ']))
			foreach ($SEQ_INFO['SEQ'] as $T)$SEQ_REF.=$T['AA'];
			if ($SEQ_REF!=$SEQ) continue;
			
			$SEQ_INFO['SEQ_DB_STATUS']='VALID';
			if ($SEQ_INFO['DB_STATUS']!='TO_UPD')$SEQ_INFO['DB_STATUS']='VALID';
			
			
			$FOUND=true;
			if ($SEQ_INFO['iso_id']!=$iso_id){ echo 'ID:'.$SEQ_INFO['iso_id'].'=>'.$iso_id."\n";$SEQ_INFO['iso_id']=$iso_id;$SEQ_INFO['DB_STATUS']='TO_UPD';}
			if ($SEQ_INFO['iso_name']!=$NAME)		   {echo 'NAME:'.$SEQ_INFO['iso_name'].'=>'.$NAME."\n";$SEQ_INFO['iso_name']=$NAME;		  $SEQ_INFO['DB_STATUS']='TO_UPD';}
			if ($SEQ_INFO['description']!=$description){ echo 'DESC:'.$SEQ_INFO['description'].'=>'.$description."\n";$SEQ_INFO['description']=$description;$SEQ_INFO['DB_STATUS']='TO_UPD';}
			if ($SEQ_INFO['note']!=$note)  { echo 'note:'.$SEQ_INFO['note']."=>".$note."\n";$SEQ_INFO['note']=$note;  $SEQ_INFO['DB_STATUS']='TO_UPD';}
		}
		if ($FOUND)continue;
		$IS_PRIM= (!$HAS_PRIMARY && count($SEQS)==1)?'T':'F';
		if ($IS_PRIM=='T')$HAS_PRIMARY=true;
		$ENTRY['prot_seq'][$iso_id]=array('DBID'=>-1,'iso_name'=>$NAME,'iso_id'=>$iso_id,'is_primary'=>$IS_PRIM,
		'description'=>$description,'modification_date'=>'NOW()','note'=>$note,
		'DB_STATUS'=>'TO_INS',
		'SEQ_DB_STATUS'=>'TO_INS');
		for ($J=0;$J<strlen($SEQ);++$J)	$ENTRY['prot_seq'][$iso_id]['SEQ'][$J+1]=array('DBID'=>-1,'AA'=>substr($SEQ,$J,1));
		

	}
	if ($DEBUG)echo "MAP FEATS TIME:".round(microtime_float()-$time,3)."s\n";$time=microtime_float();
	if (!$HAS_PRIMARY)
	{
		foreach ($SEQS as $iso_id=>$SQ)
		{
			if ($SQ['IS_ISOFORM'])	continue;
			
			$ENTRY['prot_seq'][$iso_id]=array('DBID'=>-1,'iso_name'=>'Displayed','iso_id'=>$iso_id,'is_primary'=>'T',
		'description'=>$SQ['NAME'],'modification_date'=>'NOW()',
		'DB_STATUS'=>'TO_INS',
		'SEQ_DB_STATUS'=>'TO_INS');
		for ($J=0;$J<strlen($SQ['SEQ']);++$J)	$ENTRY['prot_seq'][$iso_id]['SEQ'][$J+1]=array('DBID'=>-1,'AA'=>substr($SQ['SEQ'],$J,1));

		}
	}
	
	if ($DEBUG)echo "NO PRIM TIME:".round(microtime_float()-$time,3)."s\n";$time=microtime_float();
	if ($DEBUG)
	{
		echo "\n\n#########\nFINAL\n\n\n";
		foreach ($ENTRY['prot_seq'] as $TID=>$IF)
		{
			$T=$IF;
			unset($T['SEQ']);
			echo "#### ".$TID."\n";
			print_r($T);
		}
		//print_r($ENTRY['ft']);
		exit;
	}
	echo "OUT SEQ\n";
	return true;;
//	exit;	


}

function processProtName(&$ENTRY,$VALUES)
{
	/*
	  [5] => DE   RecName: Full=F-box/WD repeat-containing protein 4;
    [6] => DE   AltName: Full=Dactylin;
    [7] => DE   AltName: Full=F-box and WD-40 domain-containing protein 4;

	*/
	$N_LINE=count($VALUES);
	$TMP_NAMES=array();
	$group_id=0;$prev_cn='';
	for($I_LINE=0;$I_LINE<$N_LINE;++$I_LINE)
	{
		
		$line=&$VALUES[$I_LINE];
		++$group_id;
		//echo "NEW GROUP ".$group_id."\n";
		$pname=array( 'group_id'=>$group_id,
		'class_name'=>'', 
		'name_type'=>'', 
		'name_subtype'=>'',
		'name_link'=>'', 
		'is_primary'=>'',
		'name'=>'',
		'EC'=>'',
		'DB_prot_name'=>-1,
		'DB_prot_name_map'=>-1
		);
		$GROUP_pname=array(); 
		$pos1=strpos($line,' ');
		if ($pos1===false)$head=$line;
		else $head=trim(substr($line,0,$pos1));
		//echo '|'.$line."|".$pos1."|HEAD:".$head."|\n";
		

        if ($head=="RecName:")      {$pname['class_name']="REC";$prev_cn=$pname['class_name'];}
        else if ($head=="AltName:") {$pname['class_name']="REC";$prev_cn=$pname['class_name'];}
        else if ($head=="Contains:"){$pname['class_name']="CON";$prev_cn=$pname['class_name'];}
        else if ($head=="Includes:"){$pname['class_name']="INC";$prev_cn=$pname['class_name'];}
		else if ($head=="Flags:")   {

			$pname['class_name']="FLA";}
		else {$pname['class_name']=$prev_cn;}
		
		
		//$start_line=( $pname['class_name']=="REC")?0:1;
		$FIRST=true;
		if ($line=='Contains:'){$FIRST=false;++$I_LINE;}


		///First step is to extract the information
        for($I_LINE;$I_LINE<$N_LINE;++$I_LINE)
        {
			$line=$VALUES[$I_LINE];
			//echo "\tSUBLINE:".$line."\tCHAR:" .substr($line,5,1)."\n";

			if (!$FIRST && substr($line,5,1)!=" "&& $pname['class_name']!="CON"){--$I_LINE;break;}
			if (!$FIRST && $line=='Contains:'){--$I_LINE;break;}
			
			$pname['name_type']='';
			$pname['name_subtype']='';
			$pname['name_link']='';
			$pname['is_primary']='';
			$pname['name']='';
			$pname['EC']='';
			$pname['DB_prot_name']='';
			$pname['DB_prot_name_map']='';
			
			
                        
            $start=find_first_not_of($line," ",0);
			$end=strpos($line," ",$start);
			//echo "STEP1\t".$start.'|'.$end.'|';
			$value=substr($line,$start,$end-$start);
			//echo "name_type:|".$value."|\t";
            $IS_FLAG=($value=='Flags:');
            /// Get Type:
            if ($value=="RecName:")     {$pname['name_type']="REC";$prev_type=$pname['name_type'];   $EC="";$EC_link="";}
            else if ($value=="AltName:"){$pname['name_type']="ALT";$prev_type=$pname['name_type'];   $EC="";$EC_link="";}
            else if ($value=="SubName:"){$pname['name_type']="SUB";$prev_type=$pname['name_type'];   $EC="";$EC_link="";}
            else {$pname['name_type']=$prev_type;$end=5;}
            //            std::cout <<"\t|->"<<ts['name_type']<<std::endl;
			///Get SubType:
			$pname['is_primary']=($pname['name_type']=="REC" && $pname['name_subtype']=="F"&&$pname['class_name']=="REC")?"T":"F";
			
			/// Get the subtype and assign it
			$start=find_first_not_of($line," ",$end);
			$end=strpos($line,"=",$start);
            $value=trim(substr($line,$start,$end-$start));
            if ($value=="Short")          $pname['name_subtype']="S";
            else if ($value=="Full")      $pname['name_subtype']="F";
            else if ($value=="CD_antigen")$pname['name_subtype']="A";
            else if ($value=="INN")       $pname['name_subtype']="I";
            else if ($value=="Biotech")   $pname['name_subtype']="B";
            else if ($value=="Allergen")  $pname['name_subtype']="L";
            else if ($value=="EC")
            {
				/// Find te EC value:
                $start=$end+1;
                $end=strpos($line,";",$start);
                $end2=strpos($line,"{",$start);
                if ($end2===false)   $EC=substr($line,$start,$end-$start);
                else
                {
                    $EC=substr($line,$start,$end2-$start-1);
                    $EC_link=substr($line,$end2);
                }
                foreach($GROUP_pname as &$pname_t)
                {
                    $pname_t['EC']=$EC;
                    if ($EC_link!="")$pname_t['name_link'].=" EC:".$EC_link;
                }
                continue;
            }

			/// Get the protein name and link
            $start=$end;
			if (!$IS_FLAG)$start++;
            $end=strpos($line,";",$start);
            $end2=strpos($line,"{",$start);
            if ($end2===false)   $pname['name']=substr($line,$start,$end-$start);
            else
            {
                $pname['name']=substr($line,$start,$end2-$start-1);
                $pname['name_link'].=substr($line,$end2);
            }

            /// Add EC if previously described:
            if ($EC!="")$pname['EC']=$EC;
            if ($EC_link!="")$pname['name_link'].=" EC:".$EC;
			
				$GROUP_pname[]=$pname;
			//std::cout <<"\t|->"<<ts['name_subtype']<<"\t->"<<ts.EC<<std::endl;
			
		}
		//print_r($GROUP_pname);
		foreach ($GROUP_pname as $PN)$TMP_NAMES[]=$PN;
		$GROUP_pname=array();

	}

	/// Then we compare each record against the one in the database
	foreach ($TMP_NAMES as $T)
	{
		$FOUND=false;
		
		if (isset($ENTRY['pname']) && $ENTRY['pname']!=array())
		foreach ($ENTRY['pname'] as &$REF)
		{
			if ($REF['group_id']!=$T['group_id'])continue;
			if ($REF['class_name']!=$T['class_name'])continue;
			if ($REF['name_type']!=$T['name_type'])continue;
			if ($REF['name_subtype']!=$T['name_subtype'])continue;
			
			if ($REF['name'][1]!=$T['name'])continue;
			if ($REF['name'][2]!=$T['EC'])continue;
			$FOUND=true;
			$REF['DB_STATUS']='VALID';
			if ($REF['name_link']!=$T['name_link']){$REF['name_link']=$T['name_link'];$REF['DB_STATUS']='TO_UPD';}
			if ($REF['is_primary']!=$T['is_primary']){$REF['is_primary']=$T['is_primary'];$REF['DB_STATUS']='TO_UPD';}
			break;
		}
		if ($FOUND)continue;

		/// Not found? We insert it
		$ENTRY['pname'][]=array(
			'DB_MAPID'=>-1,
			'DB_PID'=>-1, 
			'group_id'=>$T['group_id'], 
			'class_name'=>$T['class_name'], 
			'name_type'=>$T['name_type'], 
			'name_subtype'=>$T['name_subtype'],
			'name_link'=>$T['name_link'], 
			'is_primary'=>$T['is_primary'],
			'DB_STATUS'=>'TO_INS',
			'name'=>array(-1,$T['name'],$T['EC'],'NOW()'));
	}

	/// Because protein names can exist in multiple entries, we need to "dissociate them" from the uniprot process
	/// So we are going to check if they already exist in the database or not.
	/// In addition, a protein name/EC Number must be uniquely defined once in the PROT_NAME table
	/// However, since an EC_NUmber can be null, 2 queries must be run, one with protein name and one with protein name/EC (when applicable)
	$Q1='(protein_name,ec_number) IN (';
		$Q2='(EC_number is NULL AND protein_name IN (';
		
		foreach ($ENTRY['pname'] as $PN)
		{
			if ($PN['name'][0]!=-1)continue;
			
			if ($PN['name'][1]!='' && $PN['name'][2]!='')$Q1.="('".str_replace("'","''",$PN['name'][1])."', '".$PN['name'][2]."'),";
			if ($PN['name'][1]!='' && $PN['name'][2]=='')$Q2.="'".str_replace("'","''",$PN['name'][1])."',";
		}
		
		$QUERY="SELECT prot_name_id,protein_name,ec_number FROM prot_name WHERE ";
		if (strlen($Q1) > 29)$QUERY .= substr($Q1,0,-1).') OR '; 
		if (strlen($Q2) > 40)$QUERY .= substr($Q2,0,-1).')) OR ';
		
		if ($QUERY!="SELECT prot_name_id,protein_name,ec_number FROM prot_name WHERE "){
		$res=array();
		
		$res=runQuery( substr($QUERY,0,-4));
		/// Assigning existing protein naes
		$TMP_PNAMES=array();
		foreach ($res as $line)$TMP_PNAMES[($line['protein_name'].$line['ec_number'])]=$line['prot_name_id'];
		
		foreach ($ENTRY['pname'] as $P=>$V)
		{
			if (isset($TMP_PNAMES[$V['name'][1].$V['name'][2]]))
			$ENTRY['pname'][$P]['name'][0]=$TMP_PNAMES[$V['name'][1].$V['name'][2]];
		}
	}
	
	foreach ($ENTRY['pname'] as &$REF)
	{
		if ($REF['DB_STATUS']!='FROM_DB')continue;
		if (!runQueryNoRes("DELETE FROM prot_name_map where prot_name_map_id = ".$REF['DB_MAPID']))
		{
			echo "Unable to delete ".$REF['name'][1]."\n";
			return false;
		}
	}
	//print_R($ENTRY['pname']);exit;
	return true;;

	
	//print_r($TMP_NAMES);

}

	function processGene(&$VALUE,&$GN_INFO)
	{
		$DEBUG=false;
		
		///GN   Name=FBXW4; Synonyms=FBW4, SHFM3;
		/// Extract gene name and synonyms and put them in GN_INFO array
		$pos1=strpos($VALUE,"Name=");
		if ($pos1!==false)
		{
			$pos1+=5;
			$pos2=strpos($VALUE,";",$pos1);
			$pos3=strpos($VALUE,"{",$pos1);
				if ($pos2!==false && $pos3!==false)$pos4=min($pos2,$pos3);
				else if ($pos2===false && $pos3!==false)$pos4=$pos3;
				else if ($pos2!==false && $pos3===false)$pos4=$pos2;
				
			
			//echo $pos1.'|'.$pos2.'|'.$pos3.'|' .$pos4.'|' .substr($VALUE,$pos1,$pos4-$pos1)."\n";
			$GN_INFO[]=substr($VALUE,$pos1,$pos4-$pos1);
		}
		
		
    	$pos1=strpos($VALUE,"Synonyms=");
    	if ($pos1===false)    return;
    
    	$pos1+=9;
		$pos2=strpos($VALUE,";",$pos1);
		$pos3=strpos($VALUE,"{",$pos1);
		if ($pos3!==false)$tmp=substr($VALUE,$pos1,$pos3-$pos1);
		else $tmp=substr($VALUE,$pos1,$pos2-$pos1);
		
		$toks=explode(",",$tmp);
		foreach ($toks as $name)	$GN_INFO[]=trim($name);
		if ($DEBUG)
		{
			print_r($GN_INFO);
		}
		return true;;
	}

	
	function processTaxon(&$ENTRY,&$VALUE)
	{
		//OX   NCBI_TaxID=9606;
		$pos=strpos($VALUE,'NCBI_TaxID');
		if($pos===false)return ;
		$pos2=strpos($VALUE,';');
		if($pos2===false)return ;
		$pos3=strpos($VALUE,'{');
		if ($pos3!==false && $pos3<$pos2)$pos2=$pos3;
		/// Getting the tax ID:
		$tax_id= trim(substr($VALUE,$pos+11,$pos2-$pos-11));
		/// Checking if is different or not
		if ($ENTRY['prot_entry']['DB_STATUS']=='TO_INSERT')$ENTRY['prot_entry']['tax_id']=$tax_id;
		else if ($ENTRY['prot_entry']['tax_id']!=$tax_id)
		{
			$ENTRY['prot_entry']['tax_id']=$tax_id;
			$ENTRY['prot_entry']['DB_STATUS']=='TO_UPD';
		}

		return true;;
	}

	function processac(&$ENTRY,&$VALUE,$N_ac_LINE)
	{
		//AC   AC_number_1;[ AC_number_2;]...[ AC_number_N;]
		//AC   Q16653; O00713; O00714; O00715; Q13054; Q13055; Q14855; Q92891;
		// AC   Q92892; Q92893; Q92894; Q92895; Q93053; Q96KU9; Q96KV0; Q96KV1;
		// AC   Q99605;
		$tab=array_values(array_filter(explode(";",$VALUE)));	
		foreach ($tab as $k=>$tac)
		{
			$ac=trim($tac);///remove space
			if (isset($ENTRY['ac'][$ac])) /// check if exist
			{
				$ENTRY['ac'][$ac]['DB_STATUS']='VALID';/// by default it's valid
				$PRIM=($k==0&&$N_ac_LINE==1)?'T':'F';/// BUT it's primary status can change over time
				if ($PRIM != $ENTRY['ac'][$ac]['is_primary'])/// So if changed, we need to update
				{
					$ENTRY['ac'][$ac]['is_primary']=$PRIM;
					$ENTRY['ac'][$ac]['DB_STATUS']='TO_UPD';
				}
			}
			/// Not found -> create it.
			else $ENTRY['ac'][$ac]=array('DBID'=>-1,'is_primary'=>($k==0&&$N_ac_LINE==1)?'T':'F','DB_STATUS'=>'TO_INS');
			
		}

		return true;;
		
	}

function processComments(&$ENTRY,$VALUES)
{
	$DEBUG=fALSE;
		global $GLB_VAR;
		$N_LINE=count($VALUES);
		for ($I=0;$I<$N_LINE;++$I)
		{
			$COMMENT=array('type'=>'','value'=>'','ECO'=>array(),'DB_STATUS'=>'TO_INS');
			$line=$VALUES[$I];
			if (strpos($line,'---------')!==false)break;
			$pos=strpos($line,'-!-');
			if ($DEBUG)echo "NEW LINE:\t".$line."##".$pos."\n";
			if ($pos===false)continue;
			$pos2=strpos($line,':',$pos);
			if ($pos2===false)continue;
			if ($DEBUG)echo "PASS\n";
			$COMMENT['type']=substr($line,$pos+4,$pos2-$pos-4);
			if ($DEBUG)echo $COMMENT['type']."\n";
			if ($pos2!=strlen($line))$COMMENT['value']=substr($line,$pos2+1);
			if (strpos($VALUES[$I+1],'-!-')===false){
			$I+=1;
			for (;$I<$N_LINE;++$I)
			{
				$line=&$VALUES[$I];
				if (strpos($line,'---------')!==false)break;
				if (strpos($line,'-!-')!==false ){$I--;break;}
				if (substr($COMMENT['value'],-1)!=' ')$COMMENT['value'].=' ';
				$COMMENT['value'].=trim($line);
			}
		
		
			}
			if ($DEBUG)echo "PASS STEP2\n";
			$pos=strpos($COMMENT['value'],'{');
			if ($pos!==false)
			{
				do
				{
					$posE=strpos($COMMENT['value'],'}',$pos);
					$line2=substr($COMMENT['value'],$pos+1,$posE-$pos-1);
					if ($DEBUG)	echo "ECO:||".$line."||\n";
					$tab=explode(",",$line2);
					foreach ($tab as $rec)
					{
						$rec=trim($rec);
						$tab2=explode("|",$rec);
						if (count($tab2)!=2)continue;
						$pos=strpos($tab2[1],'PubMed');
						if ($pos===false)continue;
						$pmid=trim(substr($tab2[1],$pos+7));
						$QUERY="SELECT pmid_entry_id FROM pmid_entry WHERE pmid=".$pmid;
						$res=array();
						$res=runQuery($QUERY);if ($res===false) failProcess($JOB_ID."020","Unable to run query ".$QUERY);
						if ($res!=array())
						{
							$RECS=array('ECO'=>$tab2[0],'pmid'=>$pmid,'pmidDBID'=>$res[0]['pmid_entry_id'],'DB_STATUS'=>'TO_INS','DBID'=>-1);
						//	print_r($RECS);
						$COMMENT['ECO'][]=$RECS;
						}
						
					}
					
					$pos2=strpos($COMMENT['value'],'{',$posE);
					if ($pos2===false)	break;
					$pos=$pos2;
				}while(1);
			}
			
			if ($DEBUG)echo "CURRLINE:".$line."\n";
			$FOUND_COM=false;
			if (isset($ENTRY['comments']))
			foreach ($ENTRY['comments'] as &$REF_COMMENT)
			{
				if ($REF_COMMENT['type']!=$COMMENT['type'])continue;
				if ($REF_COMMENT['value']!=$COMMENT['value'])continue;
				if ($DEBUG)echo "EXISTING COMMENT\n";
				$FOUND_COM=true;
				$REF_COMMENT['DB_STATUS']='VALID';
				foreach ($COMMENT['ECO'] as $ECO_INFO)
				{
					$FOUND=false;
					foreach ($REF_COMMENT['ECO'] as &$REF_ECO)
					{
						if ($DEBUG)echo $REF_ECO['pmid'].'|'.$ECO_INFO['pmid'].'=>'.($REF_ECO['pmid']==$ECO_INFO['pmid'])."\t".$REF_ECO['eco'].'|'.$ECO_INFO['ECO'].'=>'.($REF_ECO['eco']==$ECO_INFO['ECO'])."\n";
						if ($REF_ECO['pmid']!=$ECO_INFO['pmid'])continue;

						/// In Uniprot ECO are formatted with : while ECO ontology provides it with _
						
						if (str_replace('_',':',$REF_ECO['eco'])!=$ECO_INFO['ECO'])continue;
						if ($DEBUG)echo "\tEXISTING ECO\n";
						$REF_ECO['DB_STATUS']='VALID';
						$FOUND=true;break;
					}
					if ($FOUND)continue;
					if ($DEBUG)echo "\tNEW ECO\n";
					$REF_COMMENT['ECO'][]=$ECO_INFO;
				}
				
			}
			if ($FOUND_COM)continue;
			$ENTRY['comments'][]=$COMMENT;
			if ($DEBUG)echo "end:".$line."\n";
			
		}
		if ($DEBUG)print_r($ENTRY['comments']);
		
		return true;;
	
}
	function processID(&$ENTRY,&$VALUE)
	{
		global $JOB_ID;
		/// Check if the entry is already in the database or not
		$EXISTS= ($ENTRY['prot_entry']['DB_STATUS']=='FROM_DB');

		/// Process line:
		///   CDK2_HUMAN              Reviewed;         298 AA.
		/// $tab=>array(0=>'CDK2_HUMAN', 1=>'Reviewed;', 2=>'298 AA.');
		$tab=array_values(array_filter(explode(" ",$VALUE)));
		$ID=$tab[0];
		$status=$tab[1];
		/// If exist, check fo differences
		if ($EXISTS)
		{				
			if ($ENTRY['prot_entry']['prot_identifier']!=$ID)return "Different identifier ".$ID.' '.$ENTRY['prot_entry']['prot_identifier'] ;
			if ($status =='Reviewed;' && $ENTRY['prot_entry']['status']=='F'){$ENTRY['prot_entry']['status']='T';$ENTRY['prot_entry']['DB_STATUS']='TO_UPD';}

		}
		else /// Otherwise just assignment
		{
			$ENTRY['prot_entry']['prot_identifier']=$ID;
			$ENTRY['prot_entry']['status']=($status =='Reviewed;')?"T":"F";
		}
		return true;;
	}


	function convert($size)
	{
		$unit=array('b','kb','mb','gb','tb','pb');
		return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
	}
	
	

	function pushToDB(&$ENTRY)
	{
		
		global $GLB_VAR;
		/// if it's a new entry, there is therefore no update/deletion to do
		if ($ENTRY['prot_entry']['DB_STATUS']=='TO_INS')	return true;
		
		/// If the entry need to be updated
		if ($ENTRY['prot_entry']['DB_STATUS']=='TO_UPD')
		{
			$UEI=&$ENTRY['prot_entry'];
			$QUERY="UPDATE prot_entry 
			SET prot_identifier ='".$UEI['prot_identifier']."',
				date_updated=NOW(),
				status='".$UEI['status']."',
				taxon_id=(SELECT taxon_id FROM taxon WHERE tax_id='".$UEI['tax_id']."'),
				confidence='".$UEI['confidence']."' WHERE prot_entry_id=".$UEI['prot_entry_id'];
			if (!runQueryNoRes($QUERY)) return "Unable to run Query ".$QUERY;
		}

		/// Uniprot Accession
		/// Can either be deleted or is_primary change
		foreach ($ENTRY['ac'] as $ac=>&$INFO)
		{
			if ($INFO['DB_STATUS']=='FROM_DB')
			{
				$QUERY='DELETE FROM prot_ac WHERE prot_ac_id='.$INFO['DBID'];
				if (!runQueryNoRes($QUERY))  return "Unable to run Query ".$QUERY;
			}
			else if ($INFO['DB_STATUS']=='TO_UPD')
			{
				$QUERY="UPDATE prot_ac SET is_primary='".$INFO['is_primary']."' WHERE prot_ac_id =".$INFO['DBID'];
				if (!runQueryNoRes($QUERY))  return "Unable to run Query ".$QUERY;
			}
		}

		// Domain can be deleted
		// If the canonical sequence has changed, then we delete all domains
		$NDEL=0;
		if (isset($ENTRY['OLD_prot_dom']))
		{
			foreach ($ENTRY['OLD_prot_dom'] as $DBID=>&$SEQINFO)
			{
				
				++$NDEL;
				$SEQINFO['domain_name']=str_replace("'","''",$SEQINFO['domain_name']);
				$QUERY="UPDATE prot_dom SET status=9, domain_name= '".$SEQINFO['domain_name']."-9".$NDEL."' WHERE prot_dom_id=".$SEQINFO['DBID'];
				//echo $QUERY."\n";
				if (!runQueryNoRes($QUERY)) return "Unable to run Query ".$QUERY;
			}
		}
		// and all sequences
		if (isset($ENTRY['OLD_prot_seq']))
		{
			foreach ($ENTRY['OLD_prot_seq'] as $DBID=>&$SEQINFO)
			{
				++$NDEL;
				$QUERY="UPDATE prot_seq SET status=9, iso_id= '".$SEQINFO['iso_id']."-9".$NDEL."' WHERE prot_seq_id=".$SEQINFO['DBID'];
				//echo $QUERY."\n";
				if (!runQueryNoRes($QUERY))  return "Unable to run Query ".$QUERY;
			}
		}
		$ORDER=array();

		/// Get the isoform ID so we can Order the sequences by isoform number
		foreach ($ENTRY['prot_seq'] as $DBID=>&$SEQINFO)
		{
			$ORDER[substr($SEQINFO['iso_id'],strpos($SEQINFO['iso_id'],'-')+1)][]=$DBID;
			$T=$SEQINFO;
			unset($T['SEQ']);
		}
		if ($ORDER!=array())
		{

			/// Order the sequences by isoform number
			krsort($ORDER);
			
			/// The order is in fact important. We need to starting from the canonical and to the last isoform
			foreach ($ORDER as $L_ORDER)
			foreach ($L_ORDER as $DBID)
			{
				$SEQINFO=&$ENTRY['prot_seq'][$DBID];
				
				if ($SEQINFO['DB_STATUS']=='TO_UPD')
				{
					$SEQINFO['description']=str_replace("'","''",$SEQINFO['description']);
					$SEQINFO['note']=str_replace("'","''",$SEQINFO['note']);
					$QUERY="UPDATE prot_seq 
					SET iso_name='".$SEQINFO['iso_name']."',
						iso_id='".$SEQINFO['iso_id']."',
						is_primary='".$SEQINFO['is_primary']."',
						description='".$SEQINFO['description']."',
						modification_date=NOW(),
						note='".$SEQINFO['note']."'
						 WHERE prot_seq_id=".$SEQINFO['DBID'];
					//echo $QUERY."\n";
					if (!runQueryNoRes($QUERY))  return "Unable to run Query ".$QUERY;
				}else if ($SEQINFO['DB_STATUS']=='FROM_DB')
				{

					++$NDEL;
					$QUERY="UPDATE prot_seq SET status=9, iso_id= '".$SEQINFO['iso_id']."-9_DEL".$NDEL."' WHERE prot_seq_id=".$SEQINFO['DBID'];
					//echo $QUERY."\n";
					if (!runQueryNoRes($QUERY))  return "Unable to run Query ".$QUERY;
				}
				else  if ($SEQINFO['DB_STATUS']!='VALID' && $SEQINFO['DB_STATUS']!='TO_INS')
				{
					
					return "Unrecognized status for sequence ".$SEQINFO['DB_STATUS'].' '.$SEQINFO['iso_id'];
				}
				
			}
		}


		/// Check if we have any protein domain
		if (isset($ENTRY['prot_dom']))
		foreach ($ENTRY['prot_dom'] as $DBID=>&$SEQINFO)
		{
			/// We don't update domain sequences, we delete them and reinsert them
			if ($SEQINFO['SEQ_DB_STATUS']=='TO_UPD')
			{
				++$NDEL;
				$SEQINFO['domain_name']=str_replace("'","''",$SEQINFO['domain_name']);
				/// So we delete the sequence:
				$QUERY="DELETE FROM prot_dom_seq  WHERE prot_dom_id=".$SEQINFO['DBID'];
				echo $QUERY."\n";
				if (!runQueryNoRes($QUERY)) return "Unable to run Query ".$QUERY;
			}
			if ($SEQINFO['DB_STATUS']=='TO_UPD')
			{
				$SEQINFO['domain_name']=str_replace("'","''",$SEQINFO['domain_name']);
				/// Then we update any necessary information:
				$QUERY="UPDATE prot_dom SET domain_name='".$SEQINFO['domain_name']."',
				modification_date=NOW(),
				domain_type='".$SEQINFO['type']."',
				pos_start='".$SEQINFO['start']."',
				pos_end='".$SEQINFO['end']."'
				WHERE prot_dom_id=".$SEQINFO['DBID'];
				
				if (!runQueryNoRes($QUERY)) return "Unable to run Query ".$QUERY;


			}else if ($SEQINFO['DB_STATUS']=='VALID' || $SEQINFO['DB_STATUS']=='TO_INS')continue;
			/// and if they are from DB, we set them up for deletion:
			else if ($SEQINFO['DB_STATUS']=='FROM_DB')
			{
				++$NDEL;
				$QUERY="UPDATE prot_dom SET domain_name='".$SEQINFO['domain_name']."-".$NDEL."9', status=9 WHERE prot_dom_id=".$SEQINFO['DBID'];
				if (!runQueryNoRes($QUERY))  return "Unable to run Query ".$QUERY;
			}else return "Unrecognized status for domain ".$SEQINFO['DB_STATUS'].' '.$SEQINFO['domain_name'];
			
		}
		
		///Gene;
		foreach ($ENTRY['gene'] as $gene_id=>&$INFO)
		{
			if ($INFO['DB_STATUS']=='FROM_DB')
			{
				$QUERY='DELETE FROM gn_prot_map WHERE gn_prot_map_id = '.$INFO['DB_ID'];
				if (!runQueryNoRes($QUERY))  return "Unable to run Query ".$QUERY;
			}
		}

		///FEAT;
		foreach ($ENTRY['ft'] as &$INFO)
		{
			if ($INFO['DB_STATUS']=='FROM_DB')
			{
				$QUERY='DELETE FROM prot_feat WHERE prot_feat_id = '.$INFO['DBID'];
				echo $QUERY."\n";
				
				if (!runQueryNoRes($QUERY))  return "Unable to run Query ".$QUERY;
			}
			
		}
		///DEscription;
		foreach ($ENTRY['comments'] as &$INFO)
		{
			if ($INFO['DB_STATUS']=='FROM_DB')
			{
				$QUERY='DELETE FROM prot_desc WHERE prot_desc_id = '.$INFO['DBID'];
				if (!runQueryNoRes($QUERY))  return "Unable to run Query ".$QUERY;
			}
		}
		///pname
		foreach ($ENTRY['pname'] as &$INFO)
		{
			if ($INFO['DB_STATUS']=='FROM_DB')
			{
				$QUERY='DELETE FROM prot_name_map WHERE prot_name_map_id = '.$INFO['DB_MAPID'];
				if (!runQueryNoRes($QUERY))  return "Unable to run Query ".$QUERY;
			}
			else if ($INFO['DB_STATUS']=='TO_UPD')
			{
				/// Those are the only two columns allowed to be updated
				$QUERY="UPDATE prot_name_map SET name_link='".$INFO['name_link']."',
				is_primary='".$INFO['is_primary']."' WHERE prot_name_map_id = ".$INFO['DB_MAPID'];
				if (!runQueryNoRes($QUERY))  return "Unable to run Query ".$QUERY;
			}
			
		}
		///GO;
		if (isset($ENTRY['GO']))
		foreach ($ENTRY['GO'] as $gene_id=>&$INFO)
		{
			if ($INFO['DB_STATUS']=='FROM_DB' || $INFO['DB_STATUS']=='TO_UPD')
			{

				$QUERY='DELETE FROM prot_go_map WHERE prot_go_map_id = '.$INFO['DBID'];
				if (!runQueryNoRes($QUERY))  return "Unable to run Query ".$QUERY;
			}
		}
//		print_r($ENTRY);
		return true;;
	}
	/*
	 DE   RecName: Full=Fibroblast growth factor 5;
    [6] => DE            Short=FGF-5;
    [7] => DE   AltName: Full=Heparin-binding growth factor 5;
    [8] => DE            Short=HBGF-5;
    [9] => DE   AltName: Full=Smag-82;
    [10] => DE   Flags: Precursor;

	*/ 

?>
