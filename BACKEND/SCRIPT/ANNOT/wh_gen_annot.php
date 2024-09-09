<?php
ini_set('memory_limit','6000M');
/**
 SCRIPT NAME: wh_gen_annot
 PURPOSE:     Generate annotation files
 
*/
$JOB_NAME='wh_gen_annot';
$TG_DIR= getenv('TG_DIR');
if ($TG_DIR===false)  die('NO TG_DIR found ');
if (!is_dir($TG_DIR)) die('TG_DIR value is not a directory '.$TG_DIR);
require_once($TG_DIR.'/BACKEND/SCRIPT/LIB/loader.php');
$JOB_ID=getJobIDByName($JOB_NAME);
$PROCESS_CONTROL['DIR']='N/A';
$JOB_INFO=$GLB_TREE[$JOB_ID];



addLog("Create directory");
	/// Define the working directory
	$R_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'];
	if (!is_dir($R_DIR) && !mkdir($R_DIR)) 											failProcess($JOB_ID."001",'Unable to create new process dir '.$R_DIR);	
	$W_DIR=$R_DIR;
	addLog("Working directory: ". $W_DIR);
	if (!is_dir($W_DIR) && !mkdir($W_DIR)) 											failProcess($JOB_ID."002",'Unable to create new process dir '.$W_DIR);	
	if (!is_dir($W_DIR)) 															failProcess($JOB_ID."003",'NO '.$W_DIR.' found ');
	$PROCESS_CONTROL['DIR']=getCurrDate();
	if (!chdir($W_DIR)) 															failProcess($JOB_ID."004",'Unable to access process dir '.$W_DIR);


	/// Define the static directory where we are going to find filtering rules
	/// Usually common words
	$STATIC_DIR=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/PUBLI';
	$GENE_RULES_FILE	  =$STATIC_DIR.'/PUBLI_GENE_RULE.csv';
	if (!checkFileExist($GENE_RULES_FILE))										 	failProcess($JOB_ID."005",'Missing PUBLI_GENE_RULE.csv setup file ');


addLog("Get List of filters");
	$EXEMPT=array();
	$fp=fopen($GENE_RULES_FILE,'r');if (!$fp)										failProcess($JOB_ID."006",'Unable to open PUBLI_GENE_RULE.csv file ');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if ($line==""||$line[0]=="#")continue;
		$tab=array_values(array_filter(explode("\t",$line)));
		if (!isset($tab[1]))continue;
		$EXEMPT[$tab[0]]=$tab[1];
	}
	fclose($fp);

$COUNTS=0;
	function test_exclusion($STR)
	{
		global $COUNTS;
		$EXCLUSION_MATCH=array('/CHEMBL[0-9]{1,10}/',
	'/DB[0-9]{4,10}/',

	'/CHEBI:[0-9]{3,10}/');
		foreach ($EXCLUSION_MATCH as $R)
		{
			if (preg_match($R,$STR)==1)
			{
				++$COUNTS;
				//echo $STR."\n";
				return false;
			}
		}
		return true;
	}


	/// For each annotation type, we are going to generate a file
	/// The first column will be the primary name of the annotation
	/// The second or third column will be the list of synonyms


	

addLog("Ontology");
	$res=runQuery("SELECT * FROM ontology_entry oe LEFT JOIN ontology_syn os on os.ontology_entry_Id = oe.ontology_entry_id where w_pubmed='T'");

	if ($res===false)															failProcess($JOB_ID.'006','Unable to query ontology');
	$DATA=array();
	foreach ($res as $line)
	{
		$HEADER=$line['ontology_tag'];
		$DATA[$HEADER][]=$line['ontology_name'];
		$DATA[$HEADER][]=strtolower($line['ontology_name']);
		if ($line['syn_value']=='')continue;
		if (substr($line['syn_value'],0,6)=='PUBMED')continue;
		if (isset($EXEMPT[$line['syn_value']]))continue;
		$DATA[$HEADER][]=$line['syn_value'];
		$DATA[$HEADER][]=strtolower($line['syn_value']);
		
	}

		/// Then we are concatenating the synonyms and writing the file
		$STR='';
		foreach ($DATA as $ID=>&$ENTRY)
		{
			$ENTRY=array_unique($ENTRY);
			$STR.=$ID."\t".implode("|",$ENTRY)."\n";
		}
		$fp=fopen('ONTOLOGY','w');
		if (!$fp)																	failProcess($JOB_ID.'009','Unable to open ONTOLOGY');
		fputs($fp,$STR);
		fclose($fp);



addLog("Drug");
	$DATA=array();
	$res=runQuery("SELECT drug_name,drug_primary_name 
	FROM drug_entry de, drug_name dn
	 where dn.drug_entry_Id = de.drug_Entry_Id");
	 if ($res===false)																failProcess($JOB_ID.'007','Unable to query drugs');
	foreach ($res as $line)
	{
		$HEADER=$line['drug_primary_name'];
		
		/// We are going to skip some entries
		if ($HEADER=='Amino acids')continue;
		if (strlen($line['drug_name'])>1 && !test_exclusion($line['drug_name'])
		&& !in_array($line['drug_name'],array('IES','IAA','Rabbit','Chicken','Orange','MONITOR','DES')))
		{



			/// If they are filtered because too common or too short, we are going to add the word drug
			if (isset($EXEMPT[$line['drug_name']])|| strlen($line['drug_name'])<=3)$line['drug_name'].=' drug';
			$DATA[$HEADER]['SYN'][]=$line['drug_name'];
			// also adding the lowercase version
			$DATA[$HEADER]['SYN'][]=strtolower($line['drug_name']);

			/// Sometimes the name is not capitalized
			if ($line['drug_name'][0]!=strtolower($line['drug_name'][0]))
			{	
				$line['drug_name']=lcfirst($line['drug_name']);
				if (!isset($EXEMPT[$line['drug_name']]))
				$DATA[$HEADER]['SYN'][]=$line['drug_name'];
			}

		}


		/// Adding the primary name
		$T=$line['drug_primary_name'];
		if (isset($EXEMPT[$T])|| strlen($T)<=3)$T.=' drug';
		$DATA[$HEADER]['SYN'][]=$T;
		$DATA[$HEADER]['SYN'][]=strtolower($T);

		

		/// Sometimes the primary name is not capitalized
		if ($line['drug_primary_name'][0]!=strtolower($line['drug_primary_name'][0]))
		{
		$line['drug_primary_name']=lcfirst($line['drug_primary_name']);
		if (!isset($line['drug_primary_name']))
		$DATA[$HEADER]['SYN'][]=$line['drug_primary_name'];
		}
		
	}
	
	/// Then we also query the small molecule side that can contain additional names
	$res=runQuery("SELECT drug_primary_name, sm_name 
	FROM drug_entry de, drug_mol_entity_map dmem, molecular_entity me, sm_Entry se,sm_source ss 
	where de.drug_entry_Id = dmem.drug_entrY_id 
	AND dmem.molecular_entity_id = me.molecular_entity_id 
	AND me.molecular_structure_hash = se.md5_hash 
	and se.sm_entry_Id = ss.sm_entry_Id ");
	if ($res===false)															failProcess($JOB_ID.'008','Unable to query drug synonyms');
	foreach ($res as $line)
	{
		/// Removing short names
		if (strlen($line['sm_name'])==1)continue;
		if (!test_exclusion($line['sm_name']))continue;
		if (in_array($line['sm_name'],array('IES','IAA','i',"Ino",
		"Inosie",'Lactic acid','Olein',"Expansion","PAT",
		"Inosin",'Rabbit','Chicken','Orange','MONITOR','Monitor','Sniper',
		'Monitor (insecticide)','DES','Statin','EDT','ADR','Acetate','Metric','Estrogen','Glutathione')))continue;
		/// Removing numeric names
		if (is_numeric($line['sm_name']) || is_float($line['sm_name']))continue;
		/// If they are filtered because too common or too short, we are going to add the word drug
		if (isset($EXEMPT[$line['sm_name']])|| strlen($line['sm_name'])<=3)
		$line['sm_name'].=' drug';
		if (!isset($DATA[$line['drug_primary_name']]['SYN']))
		$DATA[$line['drug_primary_name']]['SYN'][]=$line['sm_name'];
		else if (!in_array($line['sm_name'],$DATA[$line['drug_primary_name']]['SYN']))
			$DATA[$line['drug_primary_name']]['SYN'][]=$line['sm_name'];
		
	
	}
			
			
	/// Then we are concatenating the synonyms and writing the file
	$STR='';
	foreach ($DATA as $ID=>&$ENTRY)
	{
		$ENTRY['SYN']=array_unique($ENTRY['SYN']);
		$STR.=$ID."\t".implode("|",$ENTRY['SYN'])."\n";
	}
	$fp=fopen('DRUG','w');
	if (!$fp)																	failProcess($JOB_ID.'009','Unable to open DRUG');
	fputs($fp,$STR);
	fclose($fp);


addLog("Disease");
	/// We are going to query the disease table and the disease synonyms
	/// We are only considering EXACT synonyms
	$query = "SELECT disease_tag, disease_name, syn_Value 
	FROM disease_entry de, disease_syn ds 
	where de.disease_entry_Id = ds.disease_Entry_Id AND syn_type='EXACT'";
	
	$DATA=array();
	$res=runQuery($query);
	if ($res===false)															failProcess($JOB_ID.'010','Unable to query disease');
		
	
	$STR='';
	foreach ($res as $line)
	{
		/// We can add the tag if it's not exempt or too short
		if (!isset($EXEMPT[$line['disease_name']]) && strlen($line['disease_name'])>3)
		$DATA[$line['disease_tag']][]=$line['disease_name'];

		/// We also add it with the word disease
		$DATA[$line['disease_tag']][]=$line['disease_name'].' disease';

		/// Same thing for the synonym
		if (!isset($EXEMPT[$line['syn_value']]) && strlen($line['syn_value'])>3)
		$DATA[$line['disease_tag']][]=$line['syn_value'];
		$DATA[$line['disease_tag']][]=$line['syn_value'].' disease';
		if (lcfirst($line['disease_name'])!=$line['disease_name'])
		{
			$n=lcfirst($line['disease_name']);
			if (!isset($EXEMPT[$n]) && strlen($n)>3)
			$DATA[$line['disease_tag']][]=$n;
		}
		if (lcfirst($line['syn_value'])!=$line['syn_value'])
		{
			$n=lcfirst($line['syn_value']);
			if (!isset($EXEMPT[$n]) && strlen($n)>3)
			$DATA[$line['disease_tag']][]=$n;
		}
	}
	
	$fp=fopen('DISEASE','w');
	if (!$fp)																	failProcess($JOB_ID.'011','Unable to open DISEASE');
	foreach ($DATA as $TAG=>&$LIST)
	{
		/// Making the synonyms unique
		sort($LIST);
		$LIST=array_unique($LIST);
		/// Writing the file
		$STR.=$TAG."\t".implode("|",$LIST)."\n";
	}
	fputs($fp,$STR);
	fclose($fp);

addLog("COMPOUND");
	/// We are going to query the compound table and the compound synonyms
	/// ChEMBL is an excellent source of names that are not just identifiers
	$res=runQuery("SELECT md5_hash,sm_name 
	FROM sm_entry se, sm_source  sm, source s 
	where se.sm_entry_Id = sm.sm_entry_id 
	AND s.source_id = sm.source_id 
	AND LOWER(s.source_name)=LOWER('ChEMBL')");
	if ($res===false)															failProcess($JOB_ID.'012','Unable to query ChEMBL');
	$DATA=array();
	foreach ($res as $line)
	{
		$name=$line['sm_name'];
		/// We can add the tag if it's not exempt, too short, or numeric
		if (strlen($name)<3)continue;
		if (is_numeric($name))continue;
		if (is_float($name))continue;
		if (isset($EXEMPT[$name]))continue;
		if (is_numeric(str_replace('-','',$name)))continue;
		if (!test_exclusion($name))continue;
		//echo $line['sm_name']."\n";
		$DATA[$line['md5_hash']][]=$line['sm_name'];
	}
	$STR='';
	foreach ($DATA as $TAG=>&$LIST)
	{
		/// Making the synonyms unique
		sort($LIST);
		$LIST=array_unique($LIST);
		/// Writing the file
		$STR.=$TAG."\t".implode("|",$LIST)."\n";
	}


	$fp=fopen('CPD','w');
	if (!$fp)																	failProcess($JOB_ID.'013','Unable to open CPD');
	fputs($fp,$STR);fclose($fp);



addLog("CELL");
	$res=runQuery(" SELECT cell_acc, cell_name, cell_syn_name FROM cell_entry ce LEFT JOIN cell_syn cs on cs.cell_entry_id = ce.cell_Entry_Id");
	if ($res===false)															failProcess($JOB_ID.'014','Unable to query cell');
	$DATA=array();
	foreach ($res as $line)
	{

		
		/// Accession are pretty unique, little risk of collision
		$DATA[$line['cell_acc']][]=$line['cell_acc'];
		if (!in_array($line['cell_name'],array('SNP','ALT','CA1')))
		{
		/// Cell name and synonym are more problematic so we add cell after
		$DATA[$line['cell_acc']][]=$line['cell_name'].' cell';
		$DATA[$line['cell_acc']][]=$line['cell_syn_name'].' cell';
		if (!isset($EXEMPT[$line['cell_name']])
		&&	strlen($line['cell_name'])>=3 
		&& !is_numeric(str_replace('-','',$line['cell_name'])))$DATA[$line['cell_acc']][]=$line['cell_name'];
		}
		if (in_array($line['cell_name'],array('Mouse','Fisher','NK','Center','2T')))continue;
		/// We also filter out numeric names
		if (isset($EXEMPT[$line['cell_syn_name']]))continue;
		if (strlen($line['cell_syn_name'])<4)continue;
		if (is_numeric($line['cell_syn_name']))continue;
		if (is_float($line['cell_syn_name']))continue;
		$DATA[$line['cell_acc']][]=$line['cell_syn_name'];

		/// We also add the WT version of the cell line
		if (strpos($line['cell_syn_name'],'/WT')!==false)
		{
			$DATA[$line['cell_acc']][]=str_replace('/WT','WT',$line['cell_syn_name']);
		}
	}
	$STR='';
	foreach ($DATA as $TAG=>&$LIST)
	{
		/// Making the synonyms unique
		sort($LIST);
		$LIST=array_unique($LIST);
		/// Writing the file
		$STR.=$TAG."\t".implode("|",$LIST)."\n";
	}
	$fp=fopen('CELL','w');														if (!$fp)failProcess($JOB_ID.'015','Unable to open CELL');
	fputs($fp,$STR);fclose($fp);


addLog("ANATOMY");
	$res=runQuery("SELECT anatomy_tag, anatomy_name, syn_value 
	from anatomy_entry ae 
	LEFT JOIN anatomy_syn ay on ay.anatomy_entry_id = ae.anatomy_entry_id
	 AND syn_Type='EXACT'");
	if ($res===false)															failProcess($JOB_ID.'016','Unable to query anatomy');
	$DATA=array();
	foreach ($res as $line)
	{
		if (isset($EXEMPT[$line['anatomy_name']]))continue;
		$DATA[$line['anatomy_tag']][]=$line['anatomy_name'];
		if (isset($EXEMPT[$line['syn_value']]))continue;
	}
	$STR='';
	foreach ($DATA as $TAG=>&$LIST)	
	{
		/// Making the synonyms unique
		sort($LIST);
		$LIST=array_unique($LIST);
		$STR.=$TAG."\t".implode("|",$LIST)."\n";
	}
	$fp=fopen('ANATOMY','w');if (!$fp)											failProcess($JOB_ID.'017','Unable to open ANATOMY');
	fputs($fp,$STR);fclose($fp);
	

addLog("GO");
	/// We only keep the exact synonyms
	$res=runQuery("SELECT AC, name, syn_value 
	from go_entry ge 
	LEFT JOIN go_syn gs 
	ON ge.go_entry_id = gs.go_entry_id AND syn_Type='E' AND is_obsolete='F'");
	if ($res===false)															failProcess($JOB_ID.'018','Unable to query GO');
	$DATA=array();
	foreach ($res as $line)
	{
		$DATA[$line['ac']][]=$line['name'];
		if ($line['syn_value']=='')continue;
		if (isset($EXEMPT[$line['syn_value']]))continue;
		if (is_numeric($line['syn_value']))continue;
		if (is_float($line['syn_value']))continue;
		
		if (strlen($line['syn_value'])<4)continue;
		$DATA[$line['ac']][]=$line['syn_value'];
	}
	$STR='';
	foreach ($DATA as $AC=>&$LIST)
	$STR.=$AC."\t".implode("|",$LIST)."\n";
	$fp=fopen('GO','w');	if (!$fp)											failProcess($JOB_ID.'019','Unable to open GO');
	fputs($fp,$STR);fclose($fp);


addLog("Gene");
    $list=array();
    $query="SELECT DISTINCT g.gn_entry_Id, symbol, gene_id 
			FROM  gn_entry g, chr_gn_map cgm, chr_map cm, chromosome c, taxon t
            where t.taxon_id = c.taxon_id AND c.chr_id = cm.chr_id
             AND cm.chr_map_id= cgm.chr_map_id AND g.gn_entry_id = cgm.gn_entry_id 
             and tax_id='9606' AND symbol NOT LIKE 'LOC%'";
    $res=runQuery($query);if ($res===false)										failProcess($JOB_ID.'020','Unable to query genes');
	
    foreach ($res as $line)$list[$line['gn_entry_id']]=array($line['gene_id'],$line['symbol']);

	/// Taking all synonyms at once is too much, we are going to chunk the list
    $BLOCKS=array_chunk(array_keys($list),5000);
    $fp=fopen('GENE','w');if (!$fp)												failProcess($JOB_ID.'021','Unable to open GENE');
    foreach ($BLOCKS as $BLOCK)
    {
        
        $DATA=array();
        /// then we get ALL synonyms for those genes.
        $res=runQuery("select syn_value,gene_id, symbol
                            FROM gn_syn GS, gn_syn_map GSM, gn_entry ge
                             WHERE GS.gn_syn_id = GSM.gn_syn_id 
                             AND ge.gn_Entry_Id = gsm.gn_entry_id
                             AND ge.gn_entry_id IN (".implode(',',$BLOCK).')');
        if ($res===false)													failProcess($JOB_ID.'022','Unable to get gene synonyms');
        foreach ($res as $line)
        {
			/// This is a special case, we are going to skip it INS-IGF2 readthrough
			if ($line['syn_value']=='insulin' && $line['gene_id']=='723961')continue;
			/// This is a special case, CO2 is carbon dioxide, can't be used for  complement C2 Gene
			if ($line['syn_value']=='CO2' && $line['gene_id']=='717')continue;
			/// This is a special case, NMR is not a gene, it's a technique
			if ($line['syn_value']=='NMR' && $line['gene_id']=='100505887')continue;
			/// This is a special case, COPD is a disease, not a gene
			if ($line['syn_value']=='COPD' && $line['gene_id']=='372')continue;
			/// This is a special case, MSC is used for mesenchymal stem cells, not a gene
			if ($line['syn_value']=='MSC' && $line['gene_id']=='51312')continue;
			/// This is a special case, hole is not a gene
			if ($line['syn_value']=='hole' && $line['gene_id']=='80757')continue;
			/// This is a special case, ROS is used as Reactive Oxygen Species, not a gene
			if ($line['syn_value']=='ROS' && $line['gene_id']=='6098')continue;
			/// This is a special case, protein kinase is not a gene
			if ($line['syn_value']=='protein kinase' && $line['gene_id']=='7465')continue;
			/// This is a special case, FBS is used for fetal bovine serum or fasting blood sugar, not a gene
			if ($line['syn_value']=='FBS' && ($line['gene_id']=='26269'||$line['gene_id']=='64319'))continue;
			/// This is a special case, LAP is a common word, not a gene
			if ($line['syn_value']=='LAP' && $line['gene_id']=='7040')continue;
			/// This is a special case, PD1 is a protein, not a gene
			if ($line['syn_value']=='PD1' && $line['gene_id']=='6622')continue;

			/// This is a special case, All is a common word, not a gene
			if ($line['syn_value']=='All' && $line['gene_id']=='114548')continue;

			/// This is a special case, OB is a common word, not a gene
			if ($line['syn_value']=='OB' && $line['gene_id']=='3952')continue;

			/// This is a special case, SAS is a common word, not a gene
			if ($line['syn_value']=='SAS' && ($line['gene_id']=='6302' || $line['gene_id']=='54187'))continue;

			/// This is a special case, ALS is a disease, not a gene
			if (($line['syn_value']=='ALS'||$line['syn_value']=='homodimer') && $line['gene_id']=='6647')continue;
			/// This is a special case, ALS is a disease, not a gene
			if ($line['syn_value']=='TOLL' && $line['gene_id']=='7099')continue;
			/// This is a special case, ALS is a disease, not a gene
			if ($line['syn_value']=='BDNF' && $line['gene_id']=='497258')continue;
			
			
			/// Adding different form
			$DATA[$line['gene_id']]['PRIM']=$line['gene_id']."\t".$line['symbol'];
			if (!isset($EXEMPT[$line['symbol']]) && strlen($line['symbol'])>=3 )
			{
				/// Special cases when the symbol is a common word or well used synonym
				/// We don't add it, but we add the word gene as a prefix
				if (in_array($line['symbol'],array('Kit','She','COPD','MS','GPT','Cat','tor','pad','MSC')))
				{
					$DATA[$line['gene_id']]['SYN'][]='gene '.$line['symbol'];
				}
				else $DATA[$line['gene_id']]['SYN'][]=$line['symbol'];
			}
			$DATA[$line['gene_id']]['SYN'][]=$line['symbol'].' gene';
			if (!isset($EXEMPT['h'.$line['symbol']]))$DATA[$line['gene_id']]['SYN'][]='h'.$line['symbol'];
			$DATA[$line['gene_id']]['SYN'][]='h'.$line['symbol'].' gene';
			if (!isset($EXEMPT[$line['syn_value']]) && strlen($line['syn_value'])>=3)$DATA[$line['gene_id']]['SYN'][]=$line['syn_value'];
			$DATA[$line['gene_id']]['SYN'][]='h'.$line['syn_value'].' gene';
        }
		/// Once we processed the block, we write the file
		
    	$STR='';
        foreach ($DATA as $gn=>&$info)
        {
            
			sort($info['SYN']);
			$info['SYN']=array_unique($info['SYN']);
            $STR.=$info['PRIM']."\t".implode("|",$info['SYN'])."\n";
        }
        fputs($fp,$STR);
    
    }
	fclose($fp);
   // exit;



addLog("Gene Mouse");
	/// We are going to query the mouse gene table, but not the synonyms
	$list=array();
	$query="SELECT DISTINCT g.gn_entry_Id, symbol, gene_id 
			FROM  gn_entry g, chr_gn_map cgm, chr_map cm, chromosome c, taxon t
			where t.taxon_id = c.taxon_id AND c.chr_id = cm.chr_id
			AND cm.chr_map_id= cgm.chr_map_id AND g.gn_entry_id = cgm.gn_entry_id 
			and tax_id='10090'";
	$fp=fopen('GENE_MOUSE','w');if (!$fp)							failProcess($JOB_ID.'023','Unable to open GENE_MOUSE');

	$res=runQuery($query);if ($res===false)							failProcess($JOB_ID.'024','Unable to query genes mouse');

	$STR='';
	foreach ($res as $line)
	{
		/// Same rules as usual: not Exempt, not too short
		$T='';
		if (!isset($EXEMPT[$line['symbol']]) && strlen($line['symbol'])>=3)$T.=$line['symbol'].'|';
		$T.=$line['symbol'].' gene|';
		if (!isset($EXEMPT['m'.$line['symbol']]) && strlen($line['symbol'])>=3)$T.='m'.$line['symbol'].'|';
		$T.='m'.$line['symbol'].' gene|';
		if (!isset($EXEMPT['m'.strtoupper($line['symbol'])]) && strlen($line['symbol'])>=3)$T.='m'.strtoupper($line['symbol']).'|';
		$T.='m'.strtoupper($line['symbol']).' gene|';
		
		if ($T!='')$STR.= $line['gene_id']."\t".$line['symbol']."\t".substr($T,0,-1)."\n";
	}

	fputs($fp,$STR);
	fclose($fp);


addLog("Clinical");
	$fp=fopen('CLINICAL','w');if (!$fp)									failProcess($JOB_ID.'025','Unable to open CLINICAL');
   
	/// For the clinical trial, we are mainly using the primary names
	$res=runQuery("SELECT  alias_name, alias_type, clinical_trial_id
		FROM clinical_trial_alias ");
		if ($res===false)											failProcess($JOB_ID.'026','Unable to query clinical trial');

	$DATA=array();
	foreach ($res as $line)
	{
		if (isset($EXEMPT[$line['alias_name']]))continue;
		if ($line['alias_type']=='Primary')$DATA[$line['clinical_trial_id']]['PRIM']=$line['alias_name'];
		$DATA[$line['clinical_trial_id']]['SYN'][]=$line['alias_name']."\t".$line['alias_type'];
	}
	$STR='';
	foreach ($DATA as $REC)
	{
		foreach ($REC['SYN'] as $E)$STR.=$E."\t".$REC['PRIM']."\n";
	}
	fputs($fp,$STR);
	fclose($fp);

addLog("Company");
	$STR='';    
	$res=runQuery("SELECT company_name FROM company_entry");if ($res===false)failProcess($JOB_ID.'027','Unable to query company');
	foreach ($res as $line)
	$STR.=$line['company_name']
	."\n";
	$fp=fopen('COMPANY','w');if (!$fp)										failProcess($JOB_ID.'028','Unable to open COMPANY');
	fputs($fp,$STR);fclose($fp);



	prep_annots();

	/// Generate a json file will all the annotations, grouped by words
	function prep_annots()
	{
		global $TG_DIR;
		$ANNOTS=array();
		echo "PROCESS GO\n";
		$fp=fopen('GO','r');if (!$fp)								failProcess($JOB_ID."029",'Unable to open GO file ');
		while(!feof($fp))
		{
			/// Read each row
			$line=stream_get_line($fp,100000,"\n");
			if ($line==""||$line[0]=="#")continue;
			/// Split the line into columns
			$tab=array_values(array_filter(explode("\t",$line)));
			/// Synonyms are separated by |
			$list=explode("|",$tab[1]);
			foreach ($list as $l)
			{
				/// Remove , and split the word into words
				$sp=explode(" ",str_replace(",","",$l));
				if ($l=='signaling')continue;
				/// If the word is not in the array, we add it
				if (!isset($ANNOTS[count($sp)][$l]))$ANNOTS[count($sp)][$l][]=array('GO',$tab[0]);
				else 
				{
					/// If the word is already in the array, we check if the annotation is already there
					$FOUND=false;
					foreach ($ANNOTS[count($sp)][$l] as &$R)
					if ($R[0]=='GO' && $R[1]==$tab[0]){$FOUND=true;break;}
					if (!$FOUND) $ANNOTS[count($sp)][$l][]=array('GO',$tab[0]);
				}
			}	
		}
		fclose($fp);
		echo "PROCESS GENE\n";
		$fp=fopen('GENE','r');if (!$fp)								failProcess($JOB_ID."030",'Unable to open GENE file ');
		while(!feof($fp))
		{
			$line=stream_get_line($fp,1000000,"\n");
			if ($line==""||$line[0]=="#")continue;
			$tab=array_values(array_filter(explode("\t",$line)));
			
			$list=explode("|",$tab[2]);
			foreach ($list as $l)
			{
				$sp=explode(" ",str_replace(",","",$l));
				if (!isset($ANNOTS[count($sp)][$l]))$ANNOTS[count($sp)][$l][]=array('GN',$tab[0]);
				else 
				{
					$FOUND=false;
					foreach ($ANNOTS[count($sp)][$l] as &$R)
					if ($R[0]=='GN' && $R[1]==$tab[0]){$FOUND=true;break;}
					if (!$FOUND) $ANNOTS[count($sp)][$l][]=array('GN',$tab[0]);
				}
			}	
		}
		fclose($fp);
		echo "PROCESS ONTOLOGY\n";
		$fp=fopen('ONTOLOGY','r');if (!$fp)								failProcess($JOB_ID."030",'Unable to open GENE file ');
		while(!feof($fp))
		{
			$line=stream_get_line($fp,1000000,"\n");
			if ($line==""||$line[0]=="#")continue;
			$tab=array_values(array_filter(explode("\t",$line)));
			
			$list=explode("|",$tab[1]);
			foreach ($list as $l)
			{
				$sp=explode(" ",str_replace(",","",$l));
				if (!isset($ANNOTS[count($sp)][$l]))$ANNOTS[count($sp)][$l][]=array('ON',$tab[0]);
				else 
				{
					$FOUND=false;
					foreach ($ANNOTS[count($sp)][$l] as &$R)
					if ($R[0]=='ON' && $R[1]==$tab[0]){$FOUND=true;break;}
					if (!$FOUND) $ANNOTS[count($sp)][$l][]=array('ON',$tab[0]);
				}
			}	
		}
		fclose($fp);
		echo "PROCESS GENE MOUSE\n";
		$fp=fopen('GENE_MOUSE','r');if (!$fp)								failProcess($JOB_ID."031",'Unable to open GENE_MOUSE file ');
		while(!feof($fp))
		{
			$line=stream_get_line($fp,1000000,"\n");
			if ($line==""||$line[0]=="#")continue;
			$tab=array_values(array_filter(explode("\t",$line)));
			
			$list=explode("|",$tab[2]);
			foreach ($list as $l)
			{
				$sp=explode(" ",str_replace(",","",$l));
				if (!isset($ANNOTS[count($sp)][$l]))$ANNOTS[count($sp)][$l][]=array('GN',$tab[0]);
				else 
				{
					$FOUND=false;
					foreach ($ANNOTS[count($sp)][$l] as &$R)
					if ($R[0]=='GN' && $R[1]==$tab[0]){$FOUND=true;break;}
					if (!$FOUND) $ANNOTS[count($sp)][$l][]=array('GN',$tab[0]);
				}
			}	
		}
		fclose($fp);
		echo "PROCESS CLINICAL\n";
		$fp=fopen('CLINICAL','r');if (!$fp)								failProcess($JOB_ID."032",'Unable to open CLINICAL file ');
		while(!feof($fp))
		{
			$line=stream_get_line($fp,100000,"\n");
			if ($line==""||$line[0]=="#")continue;
			$tab=array_values(array_filter(explode("\t",$line)));
			if ($tab[1]!='Primary')continue;
			$ANNOTS[1][$tab[2]][]=array('CI',$tab[2]);
		}
		fclose($fp);
		echo "PROCESS DISEASE\n";
		$fp=fopen('DISEASE','r');if (!$fp)								failProcess($JOB_ID."033",'Unable to open DISEASE file ');
		while(!feof($fp))
		{
			$line=stream_get_line($fp,100000,"\n");
			if ($line==""||$line[0]=="#")continue;
			$tab=array_values(array_filter(explode("\t",$line)));
			$list=explode("|",$tab[1]);
			foreach ($list as $l)
			{
				$sp=explode(" ",str_replace(",","",$l));
				if ($l=='disease')continue;
				if ($l=='diseases')continue;
				if ($l=='tumor')continue;
				if ($l=='cancer')continue;
				if (!isset($ANNOTS[count($sp)][$l]))$ANNOTS[count($sp)][$l][]=array('DS',$tab[0]);
				else 
				{
					$FOUND=false;
					foreach ($ANNOTS[count($sp)][$l] as &$R)
					if ($R[0]=='DS' && $R[1]==$tab[0]){$FOUND=true;break;}
					if (!$FOUND) $ANNOTS[count($sp)][$l][]=array('DS',$tab[0]);
				}
			}	
		}
		fclose($fp);
	
		echo "PROCESS ANATOMY\n";
		$fp=fopen('ANATOMY','r');if (!$fp)								failProcess($JOB_ID."034",'Unable to open ANATOMY file ');
		while(!feof($fp))
		{
			$line=stream_get_line($fp,100000,"\n");
			if ($line==""||$line[0]=="#")continue;
			$tab=array_values(array_filter(explode("\t",$line)));
			$list=explode("|",$tab[1]);
			foreach ($list as $l)
			{
				$sp=explode(" ",str_replace(",","",$l));
				
				if (!isset($ANNOTS[count($sp)][$l]))$ANNOTS[count($sp)][$l][]=array('AN',$tab[0]);
				else 
				{
					$FOUND=false;
					foreach ($ANNOTS[count($sp)][$l] as &$R)
					if ($R[0]=='AN' && $R[1]==$tab[0]){$FOUND=true;break;}
					if (!$FOUND) $ANNOTS[count($sp)][$l][]=array('AN',$tab[0]);
				}
			}	
		}
		fclose($fp);
	
		echo "PROCESS CELL\n";
		$fp=fopen('CELL','r');if (!$fp)								failProcess($JOB_ID."035",'Unable to open CELL file ');
		while(!feof($fp))
		{
			$line=stream_get_line($fp,100000,"\n");
			if ($line==""||$line[0]=="#")continue;
			$tab=array_values(array_filter(explode("\t",$line)));
			$list=explode("|",$tab[1]);
			foreach ($list as $l)
			{
				$sp=explode(" ",str_replace(",","",$l));
				
				if (!isset($ANNOTS[count($sp)][$l]))$ANNOTS[count($sp)][$l][]=array('CL',$tab[0]);
				else 
				{
					$FOUND=false;
					foreach ($ANNOTS[count($sp)][$l] as &$R)
					if ($R[0]=='CL' && $R[1]==$tab[0]){$FOUND=true;break;}
					if (!$FOUND) $ANNOTS[count($sp)][$l][]=array('CL',$tab[0]);
				}
			}	
		}
		fclose($fp);
		
		echo "PROCESS CPD\n";
		$fp=fopen('CPD','r');if (!$fp)								failProcess($JOB_ID."036",'Unable to open CPD file ');
		$nl=0;
		while(!feof($fp))
		{
			++$nl;
			if ($nl%10000==0)echo $nl."\n";
			$line=stream_get_line($fp,100000,"\n");
			if ($line==""||$line[0]=="#")continue;
			$tab=array_values(array_filter(explode("\t",$line)));
			$list=explode("|",$tab[1]);
			foreach ($list as $l)
			{
				if (strlen($l)<3)continue;
				$sp=explode(" ",$l);
				$n=count($sp);
				if (!isset($ANNOTS[$n][$l]))$ANNOTS[$n][$l][]=array('SM',$tab[0]);
				else 
				{
					$FOUND=false;
					foreach ($ANNOTS[$n][$l] as &$R)
					if ($R[0]=='SM' && $R[1]==$tab[0]){$FOUND=true;break;}
					if (!$FOUND) $ANNOTS[$n][$l][]=array('SM',$tab[0]);
				}
				
			}	
		}
		fclose($fp);
	
		echo "PROCESS DRUG\n";
		$fp=fopen('DRUG','r');if (!$fp)								failProcess($JOB_ID."037",'Unable to open DRUG file ');
		while(!feof($fp))
		{
			$line=stream_get_line($fp,100000,"\n");
			if ($line==""||$line[0]=="#")continue;
			$tab=array_values(array_filter(explode("\t",$line)));
			$list=explode("|",$tab[1]);
			foreach ($list as $l)
			{
				$sp=explode(" ",str_replace(",","",$l));
				if (strlen($l)<3)continue;
				if (!isset($ANNOTS[count($sp)][$l]))$ANNOTS[count($sp)][$l][]=array('DR',$tab[0]);
				else 
				{
					$FOUND=false;
					foreach ($ANNOTS[count($sp)][$l] as &$R)
					if ($R[0]=='DR' && $R[1]==$tab[0]){$FOUND=true;break;}
					if (!$FOUND) $ANNOTS[count($sp)][$l][]=array('DR',$tab[0]);
				}
				if (strlen($l)<6)continue;
				$l=strtolower($l);
				if (!isset($ANNOTS[count($sp)][$l]))$ANNOTS[count($sp)][$l][]=array('DR',$tab[0]);
				else 
				{
					$FOUND=false;
					foreach ($ANNOTS[count($sp)][$l] as &$R)
					if ($R[0]=='DR' && $R[1]==$tab[0]){$FOUND=true;break;}
					if (!$FOUND) $ANNOTS[count($sp)][$l][]=array('DR',$tab[0]);
				}
			}	
		}
		fclose($fp);
	

		/// This is an additional filtering step to remove words that are too common across different annotations
		foreach ($ANNOTS as $L=>&$LIST)
		{
			if ($L>10)continue;
		foreach ($LIST as $W=>&$WORDS)
		{
	
			if (count($WORDS)==1)continue;
			$TYPES=array();
			$GR=array();
			foreach ($WORDS as $V)
			{
				$GR[$V[0]][$V[1]]=true;
				$TYPES[$V[0]]=true;
			}
			$MAX=0;
			foreach ($GR as $K=>$N) $MAX=max($MAX,count($N));
	
			if (count($TYPES)==1 && $MAX<=4)continue;
			if (count($TYPES)==2 && $MAX<=3)continue;
			//echo "REMOVE ".$W."\t".count($TYPES)."\t".$MAX."\n";
			unset($ANNOTS[$L][$W]);
		}
		echo "NUMBER OF WORDS: ".$L."\t".count($ANNOTS[$L])."\n";
		}
		
	
		$fp=fopen('PREP_ANNOTS.json','w');if (!$fp)						failProcess($JOB_ID."038",'Unable to open PREP_ANNOTS.json file ');
		fputs($fp,json_encode($ANNOTS));
		fclose($fp);
	
	}
	
	
successProcess();
?>