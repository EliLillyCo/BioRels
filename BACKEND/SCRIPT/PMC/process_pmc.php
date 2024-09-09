<?php
ini_set('memory_limit','8000M');
/**
 SCRIPT NAME: process_pmc
 PURPOSE:     Download, process and annotate pmc documents
 
*/

/// Job name - Do not change
$JOB_NAME='process_pmc';

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


$JOB_RUN=$argv[1];
if (!isset($argv[1]))die("No job provided");


addLog("Create directory");
	$R_DIR=$TG_DIR.'/'.$GLB_VAR['PROCESS_DIR'].'/'.$JOB_INFO['DIR'];
	
	if (!is_dir($R_DIR) && !mkdir($R_DIR)) 											failProcess($JOB_ID."001",'Unable to create new process dir '.$R_DIR);	
	//$W_DIR=$R_DIR.'/'.getCurrDate();
	$PMJ_INFO=$GLB_TREE[getJobIDByName('pmj_pmc')];
	//print_R($PMJ_INFO);
	$W_DIR=$R_DIR.'/'.$PMJ_INFO['TIME']['DEV_DIR'];
	addLog("Working directory ".$W_DIR);
	if (!is_dir($W_DIR)) 															failProcess($JOB_ID."002",'Unable to find process dir '.$W_DIR);
	if (!isset($GLB_VAR['LINK']['FTP_PMC']))										failProcess($JOB_ID."003",'FTP_PMC path no set');

	if (!is_dir($R_DIR.'/PMC'))														failProcess($JOB_ID."004",'Unable to find process dir '.$R_DIR.'/PMC');
	if (!chdir($R_DIR.'/PMC')) 														failProcess($JOB_ID."005",'Unable to create new process dir '.$R_DIR.'/PMC');

echo "CURRENT DIR:".getcwd()."\n";	



	/// To speed up process - we will use prepared statements
	$STMT=array();
	$STMT['pmc_entry']=$DB_CONN->prepare("INSERT INTO pmc_entry (pmc_entry_id,pmc_id,license,date_added,date_processed,pmid_entry_id, pmc_last_update, status_code) VALUES (nextval('pmc_entry_sq'),:pmc_id,:license,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,:pmid_entry_id,:pmc_last_update,:status_code) RETURNING pmc_entry_id");
	$STMT['pmc_section']=$DB_CONN->prepare("INSERT INTO pmc_section (pmc_section_id,section_type,section_subtype) VALUES (nextval('pmc_section_sq'),:section_type,:section_subtype) RETURNING pmc_section_id");
	$STMT['pmc_fulltext']=$DB_CONN->prepare("INSERT INTO pmc_fulltext (pmc_fulltext_id,pmc_entry_id,pmc_section_id,offset_pos,full_text,group_id) VALUES (nextval('pmc_fulltext_sq'),:pmc_entry_id,:pmc_section_id,:offset_pos,:full_text,:group_id) RETURNING pmc_fulltext_id");
	$STMT['pmc_fulltext_drug_map']=		$DB_CONN->prepare("INSERT INTO pmc_fulltext_drug_map 	 (pmc_fulltext_drug_map_id,pmc_fulltext_id,drug_entry_id,loc_info) 		 	  VALUES (nextval('pmc_fulltext_drug_map_sq'),:pmc_fulltext_id,(SELECT drug_entry_Id FROM drug_entry where drug_primary_name=:val),:loc_info)");
	$STMT['pmc_fulltext_sm_map']=		$DB_CONN->prepare("INSERT INTO pmc_fulltext_sm_map 		 (pmc_fulltext_sm_map_id,pmc_fulltext_id,sm_entry_id,loc_info) 			 	  VALUES (nextval('pmc_fulltext_sm_map_sq'),:pmc_fulltext_id,(SELECT sm_entry_Id FROM sm_entry where md5_hash=:val),:loc_info)");
	$STMT['pmc_fulltext_disease_map']=	$DB_CONN->prepare("INSERT INTO pmc_fulltext_disease_map  (pmc_fulltext_disease_map_id,pmc_fulltext_id,disease_entry_id,loc_info) 	  VALUES (nextval('pmc_fulltext_disease_map_sq'),:pmc_fulltext_id,(SELECT disease_entry_id FROM disease_entry where disease_tag=:val),:loc_info)");
	$STMT['pmc_fulltext_anatomy_map']=	$DB_CONN->prepare("INSERT INTO pmc_fulltext_anatomy_map  (pmc_fulltext_anatomy_map_id,pmc_fulltext_id,anatomy_entry_id,loc_info) 	  VALUES (nextval('pmc_fulltext_anatomy_map_sq'),:pmc_fulltext_id,(SELECT anatomy_entry_id FROM anatomy_entry where anatomy_tag=:val),:loc_info)");
	$STMT['pmc_fulltext_gn_map']=		$DB_CONN->prepare("INSERT INTO pmc_fulltext_gn_map 		 (pmc_fulltext_gn_map_id,pmc_fulltext_id,gn_entry_id,loc_info) 			 	  VALUES (nextval('pmc_fulltext_gn_map_sq'),:pmc_fulltext_id,(SELECT gn_entry_id FROM gn_Entry where gene_id=:val),:loc_info)");
	$STMT['pmc_fulltext_go_map']=		$DB_CONN->prepare("INSERT INTO pmc_fulltext_go_map 	 	 (pmc_fulltext_go_map_id,pmc_fulltext_id,go_entry_id,loc_info)			 	  VALUES (nextval('pmc_fulltext_go_map_sq'),:pmc_fulltext_id,(SELECT go_entry_id FROM go_Entry where ac =:val),:loc_info)");
	$STMT['pmc_fulltext_company_map']=	$DB_CONN->prepare("INSERT INTO pmc_fulltext_company_map  (pmc_fulltext_company_map_id,pmc_fulltext_id,company_entry_id,loc_info) 	  VALUES (nextval('pmc_fulltext_company_map_sq'),:pmc_fulltext_id,(SELECT company_entry_id FROM company_entry where company_name=:val),:loc_info)");
	$STMT['pmc_fulltext_cell_map']=		$DB_CONN->prepare("INSERT INTO pmc_fulltext_cell_map 	 (pmc_fulltext_cell_map_id,pmc_fulltext_id,cell_entry_id,loc_info) 		 	  VALUES (nextval('pmc_fulltext_cell_map_sq'),:pmc_fulltext_id,(SELECT cell_entry_id FROM cell_entry where cell_Acc=:val),:loc_info)");
	$STMT['pmc_fulltext_clinical_map']=	$DB_CONN->prepare("INSERT INTO pmc_fulltext_clinical_map (pmc_fulltext_clinical_map_id,pmc_fulltext_id,clinical_trial_id,loc_info)	  VALUES (nextval('pmc_fulltext_clinical_map_sq'),:pmc_fulltext_id,(SELECT clinical_trial_id FROM clinical_trial where trial_id =:val),:loc_info)");
	$STMT['pmc_fulltext_file']=			$DB_CONN->prepare("INSERT INTO pmc_fulltext_file   		 (pmc_fulltext_file_id,pmc_entry_id,file_name,file_id,mime_type,file_content) VALUES (nextval('pmc_fulltext_file_sq'),:pmc_entry_id,:file_name,:file_id,:mime_type,:file_content)");
	$STMT['pmc_fulltext_pub_map']=		$DB_CONN->prepare("INSERT INTO pmc_fulltext_pub_map 	 (pmc_fulltext_pub_map_id,pmc_fulltext_id,pmid_entry_id,loc_info) 			  VALUES (nextval('pmc_fulltext_pub_map_sq'),:pmc_fulltext_id,:val,:loc_info)");
	$STMT['pmc_fulltext_ontology_map']=	$DB_CONN->prepare("INSERT INTO pmc_fulltext_ontology_map (pmc_fulltext_ontology_map_id,pmc_fulltext_id,ontology_entry_id,loc_info) 	  VALUES (nextval('pmc_fulltext_ontology_map_sq'),:pmc_fulltext_id,(SELECT ontology_entry_id FROM ontology_Entry where ontology_tag=:val),:loc_info)");
	

addLog("Get sections");
	$res=runQuery("SELECT * FROM pmc_Section");
	if ($res===false)															failProcess($JOB_ID.'006','Unable to query pmc_section');
	$SECTIONS=array();
	foreach ($res as $line)$SECTIONS[$line['section_type']][$line['section_subtype']]=$line['pmc_section_id'];


addLog("Prepare annotations");;
	/// Annotations were prepared in dl_pmc. We just need to load them
	if (!is_file($TG_DIR.'/PROCESS/ANNOT/PREP_ANNOTS.json')) 						failProcess($JOB_ID."007",'Unable to find PREP_ANNOTS.json');
	$ANNOTS=json_decode(file_get_contents($TG_DIR.'/PROCESS/ANNOT/PREP_ANNOTS.json'),true);
	if ($ANNOTS==null)																failProcess($JOB_ID."009",'failed to load PREP_ANNOTS.json');
	foreach ($ANNOTS as $L=>&$V)echo "ANNOT ".$L." word(s)\t".count($V)."\n";
	
	//Default:
	$MAX_ANNOT_W=max(array_keys($ANNOTS));
	/// If you want lower (Recommended):
	$MAX_ANNOT_W=20;

addLog("Load exclusion rules");
	/// We want to exclude some annotations, all are in PUBLI_GENE_RULE.csv
	$STATIC_DIR=$TG_DIR.'/'.$GLB_VAR['STATIC_DIR'].'/PUBLI';
	$GENE_RULES_FILE	  =$STATIC_DIR.'/PUBLI_GENE_RULE.csv';
	if (!checkFileExist($GENE_RULES_FILE))										failProcess($JOB_ID."009",'Missing PUBLI_GENE_RULE.csv setup file ');

	/// Load the exclusion rules
	$RAW_EXCLUSION=array();
	$fp=fopen($GENE_RULES_FILE,'r');if (!$fp)								    failProcess($JOB_ID."010",'Unable to open PUBLI_GENE_RULE.csv file ');
	while(!feof($fp))
	{
		$line=stream_get_line($fp,1000,"\n");
		if ($line==""||$line[0]=="#")continue;
		/// The line is made of 2 columns separated by one or more tabs
		/// The first column is the word being excluded
		/// The second column is the exclusion rule - usually empty
		$tab=array_values(array_filter(explode("\t",$line)));
		if (!isset($tab[1]))$tab[1]='';
		$RAW_EXCLUSION[$tab[0]]=$tab[1];
	}
	fclose($fp);


	$START_MEMORY=memory_get_usage();


	$DEBUG=false;
	addLog("Start processing");
	
	/// We open the file containing the list of files to process
	$fp=fopen($W_DIR.'/SCRIPTS/process.csv','r');if (!$fp)						failProcess($JOB_ID."011",'Unable to open process.csv file ');
	/// Get the header
	$HEAD=fgetcsv($fp);

	/// if you want to debug a specific record, you can skip to the record by using fseek
//	fseek($fp,28887);
	while(!feof($fp))
	{
		$fpos=ftell($fp);
		$line=fgetcsv($fp);
		if ($line===false)continue;

		/// This will create an array with the header as keys and the line as values
		$tab=array_combine($HEAD,$line);

		if ($tab['License']=='NO-CC CODE')continue;
		/// Issue processing those:
		if ($tab['PMID']=='25077038'||$tab['PMID']=='24567768')continue;
		if (!$DEBUG && $tab['job_id']!=$JOB_RUN)continue;
		/// If you want to debug a specific record, you can use the following line with the $DEBUG set to true
		if ($DEBUG && $tab['PMID']!='15221030')continue;
		echo $fpos."\t".implode("\t",$line)."\n";
		
		// Getting the path. The path is a string with / as separator
		$path=explode("/",$tab['File']);
		$full_path=$R_DIR.'/PMC';
		for ($I=0;$I<count($path)-1;++$I)
		{
			$full_path.='/'.$path[$I];
			/// So we create the corresponding directories if they don't exist
			if (!is_dir($full_path) && !mkdir($full_path)) 									failProcess($JOB_ID."012",'Unable to create new process dir '.$name);
			
		}
		if (!chdir($full_path)) 															failProcess($JOB_ID."013",'Unable to get to process dir '.$full_path);
		/// The name of the archive is the last element of the path
		$arch_name=$path[count($path)-1];

		/// We remove the .tar.gz extension
		$name=substr($arch_name,0,-7);
		
		$INFO=array('RAW_INFO'=>$tab);
		$STATUS_CODE=0;
		$PMC_ENTRY_ID=getPMCEntry($INFO,$STATUS_CODE);
		if ($STATUS_CODE==1||$STATUS_CODE>=4)continue;
		/// If the directory does not exist, we download the file and untar it
		if (!is_dir($name))
		{
			echo "DOWNLOAD\n";
			if (!dl_file($GLB_VAR['LINK']['FTP_PMC'].'/'.$tab['File']))						
			{
				echo "ERROR\t".$tab['PMID']."\tUnable to download file ".$tab['File']."\n";
				updateStatusToFail($PMC_ENTRY_ID,$STATUS_CODE);
				continue;
			}
			if (!untar($arch_name))	
			{
				echo "FAIL\tDECOMPRESS ARCHIVE\t".implode("\t",$tab)."\n";
				updateStatusToFail($PMC_ENTRY_ID,$STATUS_CODE);
				continue;
			}
			if (is_file($arch_name)&& !unlink($arch_name))
			{
				updateStatusToFail($PMC_ENTRY_ID,$STATUS_CODE);
				echo "FAIL\tDELETE ARCHIVE\t".implode("\t",$tab)."\n";
				continue;
			}

		}
		if (!chdir($name)) 																	failProcess($JOB_ID."017",'Unable to access process dir '.$name);
		
		/// The file name is not normalized. So we need to find the nxml file
		$dir=scandir('.');
		$NXML='';
		foreach ($dir as $dir_file)
		{
			if (substr($dir_file,-4)!='nxml')continue;
			$NXML=$dir_file;
			break;
		}
		if (!is_file($NXML))
		{
			updateStatusToFail($PMC_ENTRY_ID,$STATUS_CODE);
			echo "FAIL\tNO XML FILE\t".implode("\t",$tab)."\n";
			continue;
		}
		//if (!copy($NXML,'./input.xml'))												failProcess($JOB_ID."019",'Unable to copy nxml file');
		// We set up a few variables to store the information
		// $INFO will have the simplified information
		
		/// $ALT_ANNOT will store the alternative annotations, such as acronyms
		$ALT_ANNOT=array();
		/// $EXCLUDE_ANNOT will store the annotations to exclude
		$EXCLUDE_ANNOT=$RAW_EXCLUSION;
		echo "START PROCESS\n";
		echo "MEMORY START:".memory_get_usage()."\n";
		$MEM_BEFORE=memory_get_usage();
try{
		/// We process the xml
		processXML($NXML);

		echo "MEMORY END:".memory_get_usage()."\n";
		gc_collect_cycles();
		echo "MEMORY END AFTER:".memory_get_usage()."\n";	
		echo "MEM_DIFF:".(memory_get_usage()-$MEM_BEFORE)."\n";	
		echo "MEM_SINCE_START:".(memory_get_usage()-$START_MEMORY)."\n";
}catch(Exception $e)
{
	updateStatusToFail($PMC_ENTRY_ID,$STATUS_CODE);
	echo "FAIL\tPROCESSING\t".implode("\t",$tab)."\n";
	print_R($e);
}
gc_collect_cycles();
echo memory_get_usage()."\n";
echo "END PROCESS\n";
		/// We go back to the working directory
		if (!chdir($R_DIR.'/PMC')) 															failProcess($JOB_ID."019",'Unable to get to process dir '.$R_DIR.'/PMC');

		// if we debug just one entry - we exit
		if ($DEBUG)exit;
	}
	fclose($fp);

	/// We are done
successProcess();



function updateStatusToFail($PMC_ENTRY_ID,$CURR_STATUS_CODE)
{

	$NEW_STATUS_CODE=2;
	if ($CURR_STATUS_CODE>=2)$NEW_STATUS_CODE+=$CURR_STATUS_CODE;
	runQueryNoRes("UPDATE pmc_Entry SET status_code=".$NEW_STATUS_CODE." WHERE pmc_entry_Id=".$PMC_ENTRY_ID);
}




class FULL_TEXT
{
	public string $full_string;
	public array $BLOCKS;
	public array $RESULTS;
	public array $INFO;
	public int $OFFSET=0;
	public array $LINEAR;
	public array $PUBLI_ANNOTS;
	public array $FILES;

	public function __construct(string $string)
	{
		$this->full_string=$string;
		$this->BLOCKS=array();
		$this->RESULTS=array();
		$this->INFO=array();
		$this->OFFSET=0;
		$this->LINEAR=array();
		$this->PUBLI_ANNOTS=array();
		$this->FILES=array();
	}

	

	function &getBlocks(){return $this->BLOCKS;}
	function &getResults(){return $this->RESULTS;}
	function &getInfo(){return $this->INFO;}
	function &getOffset(){return $this->OFFSET;}
	function &getLinear(){return $this->LINEAR;}
	function &getPubliAnnots(){return $this->PUBLI_ANNOTS;}
	function &getFiles(){return $this->FILES;}
	function &getFullString(){return $this->full_string;}

}




/// Core function to process the xml
/// This function will parse the xml and store the information in the database
/// This happens in multiple steps
/// 1/ Raw parsing of the xml
/// 2/ Simplification of the information by converting it into an array
/// 3/ Linearization of the array & annotation
/// 4/ Pushing the linearized array to the database
function processXML($XML_PATH)
{
	global $INFO;

	/// Get the xml as a string
	$FULL_TEXT=new FULL_TEXT(file_get_contents($XML_PATH));
	$string=$FULL_TEXT->getFullString();
	
	

	/// Extract the text blocks from the xml
	/// to avoid being parsed by the xml parser
	/// This is valid for all text with HTML tags
	/// such as <p>, <title>, <article-title>, <table>
	/// So the content of such tags will be removed from the xml
	/// and stored in an array. In the xml, the content will be replaced by a reference
	/// used as a key in the array
	$BLOCKS=&$FULL_TEXT->getBlocks();
	extractTextBlocks(
		$string,
		$BLOCKS);
	//print_R($BLOCKS);exit;

		//echo "#S#\n".$string."\n";

	/// Parse the xml using simplexml
	/// Now we have an XML object, which is difficult to walk through
	$xmlElement=simplexml_load_string($string, 'SimpleXMLElement', LIBXML_BIGLINES|LIBXML_HTML_NOIMPLIED);
	//if ($xmlElement===false) throw new Exception("Unable to parse xml:".$string);
	/// so we Convert the xml to an array for easier manipulation
	$RESULTS=$FULL_TEXT->getResults();
	convert($xmlElement,$RESULTS,1);

	

	/// Still, the array is not very easy to manipulate since it has a lot of dimensions
	/// So we simplify the array by extracting the information we need and put it into $INFO
	/// it is at this time at we replace the references by the actual text
	getBack($RESULTS,$INFO,$BLOCKS);
	getJournalInfo($RESULTS,$INFO);
	getArticleMeta($RESULTS,$INFO,$BLOCKS);
	getBody($RESULTS,$INFO,$BLOCKS);
	getFloats($RESULTS,$INFO,$BLOCKS);


	/// Linearize the simplified array.
	/// This will get all the text blocks in a linear array
	/// And the corresponding annotations in PUBLI_ANNOTS
	/// $LINEAR will be a simple 1 dimension array, with the key being the offset in the text and the value being the text
	/// $PUBLIC_ANNOTS will be an array with the key being the offset in the text and the value being the annotations
	/// $FILES will be an array with the key being the file id and the value being the file name
	$OFFSET=$FULL_TEXT->getOffset();
	$LINEAR=$FULL_TEXT->getLinear();
	$PUBLI_ANNOTS=$FULL_TEXT->getPubliAnnots();
	$FILES=$FULL_TEXT->getFiles();

	/// We start the linearization by looking at the front, i.e title/abstract
	linearizeFront($INFO,1,$OFFSET,$LINEAR,$PUBLI_ANNOTS);
	$INFO['TEXT']['TEXT_ID']=$INFO['TEXT_ID'];
	/// Then we look at the body
	linearize($INFO['TEXT'],1,$OFFSET,$LINEAR,$PUBLI_ANNOTS,$FILES);
	$INFO['TEXT_ID']=$INFO['TEXT']['TEXT_ID'];
	/// Then we look at the back, i.e references
	linearizeEnd($INFO,1,$OFFSET,$LINEAR,$PUBLI_ANNOTS,$FILES);
	//exit;
	
	/// Push the linearized array to the database
	pushToDB($LINEAR,$PUBLI_ANNOTS,$INFO,$FILES);
	
	$FULL_TEXT=null;
}






/// extractTextBlocks
/// Input: XML string
/// Input: Array of blocks
/// Extract html text blocks so that they can be replaced by a reference
/// This avoid to have to deal with html tags in the text being parsed by the xml
function extractTextBlocks(&$string,&$BLOCKS,$N_BLOCK=-1)
{

	$FULL_DEBUG=false;
	convertTags($string);
	

	/// Those are the keywords where any text block will be extracted
	$LIST_WORDS=array('p','table','alt-title','title','article-title');
	foreach ($LIST_WORDS as &$N)
	{
		//echo "\n\n\n######################################################INIT\n";
		
		/// Find the position of the first occurence of the keyword
		$pos=strpos($string,'<'.$N.'>');
		$pos2=strpos($string,'<'.$N.' ');
		
		if ($FULL_DEBUG)echo $N."---STARTER:".$pos.'|'.$pos2.'|';
		if ($pos==false && $pos2==false)continue;
		$LEN=null;
		$START_SHIFT=false;
		/// If the keyword is not found, try with the keyword with attributes
		if ($pos===false || ($pos2!==false && $pos2<$pos))
		{
			//echo "IN\n";
			
			$pos=strpos($string,'<'.$N.' ');
			if ($FULL_DEBUG)echo "A:".$pos.'|'.substr($string,$pos,100)."\n";;
			//echo "=>".$pos."\n";
			/// If the keyword is not found, continue to the next keyword
			if ($pos===false)continue;
			
			/// Find the end position of the opening tag
			$pos2=strpos($string,'>',$pos);
			$LEN=$pos2-$pos+1;
			if ($FULL_DEBUG)echo "|".$pos2.'|'.$LEN.'|'.substr($string,$pos+$LEN-2,1).'|';
			if (substr($string,$pos+$LEN-2,1)=='/')
			{
				$START_SHIFT=true;
			}
			//echo "|||".substr($string,$pos,$LEN)."|||\n";
		}else 
		{
			/// Issue with PMC2828327
			if ($N=='article-title' && $pos<100)continue;
			$LEN=(strlen($N)+2);
			if ($FULL_DEBUG)echo "B:".$pos."|".$LEN.'|'.substr($string,$pos,100)."\n";;
		}
		
		/// We do make sure there is a start position
		if ($pos!==false)
		do
		{
			//echo "IN2\n";
			if ($FULL_DEBUG)echo "C|";
			if ($START_SHIFT)
			{
				if ($FULL_DEBUG)echo "D|";
				
				$block=substr($string,$pos,$LEN);
			}
			else 
			{

			/// Find the end position of the closing tag
			$pos2=strpos($string,'</'.$N.'>',$pos);
			if ($FULL_DEBUG)echo "E|".$pos2."|";
		
			/// Extract the block
			$block=substr($string,$pos+$LEN,$pos2-$pos-$LEN);
			//echo "\n||".$block."\n||\n";
			}
			++$N_BLOCK;
			


			/// In the case where a  <p> is within another <p> $pos2 will find the end tag of the child <p> instead of the parent
			//// so we check that
			/// We also make it a loop due to the possibility of multiple <p> within <p>
			$CHANGE=false;$N_T=0;
			if ($FULL_DEBUG)echo "F|";
			$pos_r=1;
			do {
				//	echo '>>'.$pos_r." ".strlen($block)."\n";
				if ($pos_r>strlen($block))break;
				if ($FULL_DEBUG)echo "G|";
				$p_test=strpos($block,'<'.$N.' ',$pos_r);
				$p_test2=strpos($block,'<'.$N.'>',$pos_r);
				if ($p_test2!==false && ($p_test===false || $p_test2<$p_test))$p_test=$p_test2;
				//echo $p_test."|".$pos_r."\n";
				$CHANGE=false;
				if ($p_test!==false)
				{
					$pos3=strpos($string,'</'.$N.'>',$pos2+1);
					$block=substr($string,$pos+$LEN,$pos3-$pos-$LEN);
					$pos2=$pos3;
					$pos_r=$p_test+1;
					$CHANGE=true;
					++$N_T;
					//echo "#\n".$block."#\n#\n";
				
				}
				//if ($pos_r>strlen($block))break;
			}while($CHANGE && $N_T<3000);
		//	echo "X\n";
			if ($CHANGE)
			{
				
				$N_BLOCK=extractTextBlocks($block,$BLOCKS,$N_BLOCK);
				
			}
			if ($FULL_DEBUG)echo "H|";
			//if ($N_T)exit;
			//  echo "STARTER:".substr($string,$pos,600)."\n";
			if ($FULL_DEBUG)  echo "###### ".$N."||".$pos.'||'.$N_BLOCK."||".$N_T."||".$CHANGE."||".$LEN."||\n\n".$block."\n\n\n\n";
			if (!$START_SHIFT)
			{
				/// Store the block
				$BLOCKS['BLOCK'.$N_BLOCK]=$block;
				
				
				/// Replace the block by a reference
				$string=substr($string,0,$pos+$LEN).'BLOCK'.$N_BLOCK.substr($string,$pos2);
			}
			else
			{
				$START_SHIFT=false;
			}
			/// Find the position of the next occurence of the keyword
			$new_pos=strpos($string,'<'.$N.'>',$pos+$LEN+1+5+strlen((string)$N_BLOCK));
			$new_pos2=strpos($string,'<'.$N.' ',$pos+$LEN+1+5+strlen((string)$N_BLOCK));
			if ($FULL_DEBUG)echo "I|".$new_pos."|".$new_pos2."|";
			/// If the keyword is not found, try with the keyword with attributes
			if ($new_pos===false && $new_pos2===false)break;
			if (($new_pos!==false && $new_pos2!==false && $new_pos2<$new_pos)
			  || ($new_pos===false && $new_pos2!==false))
			{
				$new_pos=$new_pos2;
				/// Find the end position of the opening tag
				$new_pos2=strpos($string,'>',$new_pos);
				if ($FULL_DEBUG)echo "J|".$new_pos."|".$new_pos2."|";
				$LEN=$new_pos2-$new_pos+1;
				if ($N!='table' && $N!='p')$START_SHIFT=true;
			}else $LEN=strlen($N)+2;
			$pos=$new_pos;
			if ($FULL_DEBUG)echo "K|".$pos."\n";
			//exit;
		}while($pos!==false);
		//if ($FULL_DEBUG)exit;
	}
	if ($FULL_DEBUG)exit;
	$LIST_WORDS=null;
	$pos=null;
	$LEN=null;
	$pos2=null;
	$block=null;
	$pos_r=null;
	$p_test=null;
	$p_test2=null;
	$CHANGE=null;
	$pos3=null;
	$N_T=null;
	$new_pos=null;
	$LEN=null;
	return $N_BLOCK;
	
}


/// convertTags

/// So we want to convert them to BIORELS tags so that they can be recognized by the convert function
function convertTags(&$TEXT)
{
	global $INFO;
	if ($TEXT=='')return;
/// xlink:href are a special type of tag that are not recognized by simplexml as "simple" attributes 
	$TEXT=str_replace('xlink:href','href',$TEXT);

	// Finding the first xref
	$pos=strpos($TEXT,'<xref');
	//echo "########\n\n\n\n".$TEXT."\n\n\n\n";
	if ($pos!==false)
	do
	{
		/// Finding the end of the xref
		$pos2=strpos($TEXT,'</xref>',$pos);
		if ($pos2===false)break;

		/// Case oa_package/7d/15/PMC2217501.tar.gz      J Gen Physiol. 2005 May; 125(5):465-481 PMC2217501      2018-03-22 12:28:37     15824190
		/// Where an xref is within an xref
		$pos6=strpos($TEXT,'<xref',$pos+5);
		if ($pos6!==false && $pos6<$pos2)
		{

			$pos2=strpos($TEXT,'</xref>',$pos2+1);
		}
		$line='';
		if ($TEXT[$pos+5]==' ')
		{
			//echo "IN\n";
			$pos3=strpos($TEXT,' />',$pos);
			$pos4=strpos($TEXT,'"/>',$pos);
			$pos5=strpos($TEXT,'>',$pos);
			
			//echo $pos.'|'.$pos2.'|'.$pos3."|".$pos4."|".$pos5."\n";
			if (($pos3 !==false && $pos5<$pos3)
			|| ($pos4 !==false && $pos5<$pos4))
			{
				$line=substr($TEXT,$pos,$pos2-$pos+7);
			}
			else if ($pos3!==false && $pos4!==false && $pos4<$pos3)$pos3=$pos4;
			else if ($pos3===false && $pos4!==false) $pos3=$pos4;
			
			if ($pos3!==false && ($pos3<$pos2||$pos2===false)) 
			{

			$line=substr($TEXT,$pos,$pos3-$pos+3);
			}
		

			/// Extracting the xref
			else $line=substr($TEXT,$pos,$pos2-$pos+7);
		}else $line=substr($TEXT,$pos,$pos2-$pos+7);
		if ($line=='') throw new Exception("Unable to extract xref:".$TEXT);
		/// Parsing the xref
	//	echo "##\n##\n".$line."\n";
		$XML=simplexml_load_string($line);
		if ($XML===false) throw new Exception("Unable to parse xref:".$line);

		/// Getting the attributes
		$ATTR=array();
		foreach ($XML->attributes() as $K=>$V)$ATTR[$K]=(string)$V;

		/// Getting the value
		$VALUE=(string)$XML;
		$XML=null;
		$VALUE=str_replace("<"," ",$VALUE);//Need to improve handling of this situation: PMC4746424
		$VALUE=str_replace("&"," ",$VALUE);
			
		if (!isset($ATTR['ref-type']))
		{
			#//echo "NO REF-TYPE:".$line."\n"; Example: 23951336
			$TEXT=str_replace($line,$VALUE,$TEXT);	
			
		}
		else if ($ATTR['ref-type']=='bibr'||$ATTR['ref-type']=='bib')
		{
			/// For a citation, we replace the xref by a reference to the citation
			/// [[REF_CIT||ID OF THE CITATION||TEXT SHOWN IN PUBLICATION REFERRING TO THE CITATION]]
			$VALUE=str_replace("&"," ",$VALUE);
			$TEXT=str_replace($line,'[[REF_CIT||'.$ATTR['rid'].'||'.$VALUE.']]',$TEXT);
			
		}
		else if ($ATTR['ref-type']=='table')
		{
			/// For a table, we replace the xref by a reference to the table
			$VALUE=str_replace("&"," ",$VALUE);
			$TEXT=str_replace($line,'[[[REF_TBL||'.$VALUE.']]]',$TEXT);
		}
		else if ($ATTR['ref-type']=='scheme')
		{
			/// For a table, we replace the xref by a reference to the table
			$VALUE=str_replace("&"," ",$VALUE);
			$TEXT=str_replace($line,'[[[REF_SCHEME||'.$VALUE.']]]',$TEXT);
		}
		else if ($ATTR['ref-type']=='fig') {
			
			/// For a figure, we replace the xref by a reference to the figure
			/// [[REF_IMG||ID OF THE PAPER||ID OF THE FIGURE||TEXT SHOWN IN PUBLICATION REFERRING TO THE FIGURE]]
			/// The ID of the paper is the accession ID
			/// However, the process will split lines by spaces, so to avoid issues, we replace spaces by ____
			/// that we will replace bakc later on

			# ISSUE WITH 20148193/ PMC2818071 in which Figure as multiple additional characters.
			$tab=explode(",",$VALUE);
			$VALUE=$tab[0];
			$VALUE=str_replace("&"," ",$VALUE);
			$TEXT=str_replace($line,str_replace(" ","____",'[[[REF_IMG||'.$INFO['RAW_INFO']['Accession ID'].'||'.$ATTR['rid'].'||'.$VALUE.']]]'),$TEXT);
			$INFO['IMG'][$VALUE]=$ATTR;
		}
		else if ($ATTR['ref-type']=='disp-formula')
		{
			$VALUE=str_replace("&"," ",$VALUE);
			$TEXT=str_replace($line,str_replace(" ","____",'[[[REF_IMG||'.$INFO['RAW_INFO']['Accession ID'].'||'.$ATTR['rid'].'||'.$VALUE.']]]'),$TEXT);
			$INFO['IMG'][$VALUE]=$ATTR;
		}
		else if ($ATTR['ref-type']=='app') {
			
			/// However, the process will split lines by spaces, so to avoid issues, we replace spaces by ____
			/// that we will replace back later on
			$VALUE=str_replace("&"," ",$VALUE);
			$TEXT=str_replace($line,str_replace(" ","____",'[[[REF_SUPPL||'.$INFO['RAW_INFO']['Accession ID'].'||'.$ATTR['rid'].'||'.$VALUE.']]]'),$TEXT);
			$INFO['FILE'][$VALUE]=$ATTR;
		}
		else if ($ATTR['ref-type']=='aff')
		{
			/// Affiliation
			$VALUE=str_replace("&"," ",$VALUE);
			if ($VALUE==''||$VALUE==' ')$TEXT=str_replace($line,'',$TEXT);
			else $TEXT=str_replace($line,'[[[REF_AFF||'.$VALUE.']]]',$TEXT);
		
		}
		else if ($ATTR['ref-type']=='corresp')
		{
			/// Correspondance
			if (strpos($VALUE,'&')!==false)$VALUE='CORRESP_1';
			$TEXT=str_replace($line,'[[[REF_CORRESP||'.$VALUE.']]]',$TEXT);
			
		}
		else if ($ATTR['ref-type']=='author-notes')
		{
			//print_R($VALUE);
			//Authors notes
			$VALUE=str_replace("<"," ",$VALUE);//Need to improve handling of this situation: PMC4746424
			$VALUE=str_replace("&"," ",$VALUE);
			$TEXT=str_replace($line,'[[[REF_AUTH_NOTES||'.$VALUE.']]]',$TEXT);
		}
		else if ($ATTR['ref-type']=='supplementary-material')
		{
			///// However, the process will split lines by spaces, so to avoid issues, we replace spaces by ____
			/// that we will replace back later on
			$TEXT=str_replace($line,str_replace(" ","____",'[[[REF_SUPPL||'.$INFO['RAW_INFO']['Accession ID'].'||'.$ATTR['rid'].'||'.$VALUE.']]]'),$TEXT);
		}
		else if ($ATTR['ref-type']=='fn')
		{
			$TEXT=str_replace($line,'[[[REF_AFF||'.$VALUE.']]]',$TEXT);
		}
		else if ($ATTR['ref-type']=='annotation')
		{
			$TEXT=str_replace($line,'[[[REF_ANNOT||'.$VALUE.']]]',$TEXT);
		}
		else if ($ATTR['ref-type']=='bio')
		{
			$TEXT=str_replace($line,'[[[REF_BIO||'.$VALUE.']]]',$TEXT);
		}
		else if ($ATTR['ref-type']=='chem')
		{
			$TEXT=str_replace($line,'[[[REF_CHEM||'.$VALUE.']]]',$TEXT);
		}
		else if ($ATTR['ref-type']=='contrib')
		{
			$TEXT=str_replace($line,'[[[REF_CONTRIB||'.$VALUE.']]]',$TEXT);
		}
		else if ($ATTR['ref-type']=='funding-source')
		{
			$TEXT=str_replace($line,'[[[REF_FUNDING||'.$VALUE.']]]',$TEXT);
		}
		else if ($ATTR['ref-type']=='media')
		{
			$TEXT=str_replace($line,'[[[REF_MEDIA||'.$VALUE.']]]',$TEXT);
		}
		else if ($ATTR['ref-type']=='ref')
		{
			$TEXT=str_replace($line,'[[[REF_REF||'.$VALUE.']]]',$TEXT);
		}
		else if ($ATTR['ref-type']=='statement')
		{
			$TEXT=str_replace($line,'[[[REF_STATEMENT||'.$VALUE.']]]',$TEXT);
		}
		else if ($ATTR['ref-type']=='table-fn')
		{
			$TEXT=str_replace($line,'[[[REF_AFF||'.$VALUE.']]]',$TEXT);
		}
		else if ($ATTR['ref-type']=='sec')
		{
			/// Here we are supposed to reference to a section, but haven't figured out how yet.
			/// Case PMC514490
			$TEXT=str_replace($line,$VALUE,$TEXT);
		}
		else if ($ATTR['ref-type']=='boxed-text')
		{
			/// Here we are supposed to reference to a section, but haven't figured out how yet.
			/// Case PMC545204
			$TEXT=str_replace($line,$VALUE,$TEXT);
		}
		else if ($ATTR['ref-type']=='')
		{
			$TEXT=str_replace($line,$VALUE,$TEXT);
		}
		else if ($ATTR['ref-type']=='other')
		{
			$TEXT=str_replace($line,str_replace(" ","____",'[[[REF_OTHER||'.$ATTR['rid'].'||'.$VALUE.']]]'),$TEXT);
			
		}
		else if ($ATTR['ref-type']=='table-wrap')
		{
			$TEXT=str_replace($line,str_replace(" ","____",'[[[REF_TABLE||'.$ATTR['rid'].'||'.$VALUE.']]]'),$TEXT);
		}
		else
		{
			//echo $TEXT."\n";
			print_r($ATTR);
			echo $VALUE."\n";
			echo "UNKNOWN REF-TYPE:".$ATTR['ref-type']."\n";
			throw new Exception("UNKNOWN REF-TYPE:".$ATTR['ref-type']."\n");
		}
		/// Finding the next xref. We need to start from the beginning of the string
		/// since we are supposed to replace the xref by a reference
		$pos=strpos($TEXT,'<xref');
		
	}while($pos!==false);

	$pos=null;
	$pos2=null;
	$line=null;
	$pos3=null;
	$pos4=null;
	$pos5=null;
	$XML=null;
	$VALUE=null;
}



function linearizeEnd(&$INFO,$LEVEL,&$OFFSET,&$LINEAR,&$PUBLI_ANNOTS,&$FILES)
{
	global $DEBUG;

	/// Sometimes figures are in the back
	if (isset($INFO['FIG']))
	{
		
		foreach ($INFO['FIG'] as $LABEL=>&$FIG)
		{
			$FILES[$LABEL]=$FIG['href'];		
			++$INFO['TEXT_ID'];
			/// Title can be annotated
			splitLines($LINEAR,$OFFSET,$LABEL,'FIGURE','fig_title',$INFO['TEXT_ID'],$ANNOTS);
		
			++$INFO['TEXT_ID'];
			$LINEAR[$OFFSET]=array('FIGURE','fig_info',$LABEL,$INFO['TEXT_ID']);
			$OFFSET+=strlen($LABEL);


			$INFO['TEXT_ID']++;
			/// Caption can be annotated
			splitLines($LINEAR,$OFFSET,$FIG['CAPTION'],'FIGURE','caption',$INFO['TEXT_ID'],$PUBLI_ANNOTS);
		}
	}

	
	if (isset($INFO['back']['sec']))
	{	
		foreach ($INFO['back']['sec'] as &$SEC)
		{
			$TITLE='';
			if (isset($SEC['title']))$TITLE=$SEC['title'];
			else if (isset($SEC['caption']))$TITLE=$SEC['caption'][0];
			
			//echo "B";
			$LINEAR[$OFFSET]=array('SECTION','section',$TITLE,NULL);
			$OFFSET+=strlen($TITLE);
			if (isset($SEC['fig']))
			{
				foreach ($SEC['fig'] as &$FIG_INFO)
				{


					if (isset($FIG_INFO['title']))
					{
						$title=$FIG_INFO['label'];
						$title.=': '.$FIG_INFO['title'];
						++$INFO['TEXT_ID'];
						splitLines($LINEAR,$OFFSET,$title,'FIGURE','fig_title',$INFO['TEXT_ID'],$ANNOTS);
						$OFFSET+=strlen($title);
					}
					else if (isset($FIG_INFO['label']))
					{
						$title=$FIG_INFO['label'];
						++$INFO['TEXT_ID'];
						splitLines($LINEAR,$OFFSET,$title,'FIGURE','fig_title',$INFO['TEXT_ID'],$ANNOTS);
						$OFFSET+=strlen($title);
					}
					++$INFO['TEXT_ID'];
					if (isset($FIG_INFO['id']))
					{
						$LINEAR[$OFFSET]=array('FIGURE','fig_info',$FIG_INFO['id'],$INFO['TEXT_ID']);
						$OFFSET+=strlen($FIG_INFO['id']);
						$FILES[$FIG_INFO['id']]=$FIG_INFO['href'];
					}
					$LINEAR[$OFFSET]=array('FIGURE','fig_label',$FIG_INFO['label'],$INFO['TEXT_ID']);
					$OFFSET+=strlen($FIG_INFO['label']);
					++$INFO['TEXT_ID'];
					splitLines($LINEAR,$OFFSET,$FIG_INFO['text'],'FIGURE','fig_text',$INFO['TEXT_ID'],$ANNOTS);
					
					
				}
			}
			if (isset($SEC['table']))
			{
				foreach ($SEC['table'] as &$TBL_INFO)
				{
					$title=$TBL_INFO['label'];
					$LINEAR[$OFFSET]=array('TABLE','table_label',$title,NULL);
					$OFFSET+=strlen($title);

					$LINEAR[$OFFSET]=array('TABLE','table_id',$TBL_INFO['id'],NULL);
					$OFFSET+=strlen($TBL_INFO['id']);

					foreach ($TBL_INFO['caption'] as $capt)
					{
						$INFO['TEXT_ID']++;
						splitLines($LINEAR,$OFFSET,$capt,'TABLE','table_caption',$INFO['TEXT_ID'],$ANNOTS);
					}
					++$INFO['TEXT_ID'];
					$LINEAR[$OFFSET]=array('TABLE','table_text',$TBL_INFO['table'],$INFO['TEXT_ID']);
					$OFFSET+=strlen($TBL_INFO['table']);

					
					
					foreach ($TBL_INFO['foot'] as $foot)
					{
						++$INFO['TEXT_ID'];
						splitLines($LINEAR,$OFFSET,$foot,'TABLE','table_foot',$INFO['TEXT_ID'],$ANNOTS);
					}
				}
			}
		}
	
	}
	
	/// Then we process the references
	$LINEAR[$OFFSET]=array('REFERENCES','section','References',NULL);
	$OFFSET+=strlen('References');
	if (isset($INFO['back']['REF']))
	{
		foreach ($INFO['back']['REF'] as &$REF)
		{
			/// We create a reference
			/// and add any annotation we can on it.
			$STR='[[[PUBLI_INFO||ID||'.$REF['ID'];
			if (isset($REF['SOURCE']))$STR.='||SOURCE||'.$REF['SOURCE'];
			if (isset($REF['YEAR']))$STR.='||YEAR||'.$REF['YEAR'];
			if (isset($REF['FPAGE']))$STR.='||PAGE||'.$REF['FPAGE'];
			if (isset($REF['LPAGE']))$STR.='||LPAGE||'.$REF['LPAGE'];
			if (isset($REF['TITLE']))$STR.='||TITLE||'.$REF['TITLE'];
			if (isset($REF['NAMES']))$STR.='||AUTHOR||'.implode(',',$REF['NAMES']);
			if (isset($REF['URL']))$STR.='||URL||'.$REF['URL'];
			if (isset($REF['PUB_ID']))
			foreach ($REF['PUB_ID'] as $K=>$V)
			{
				$STR.='||LINK||'.strtoupper($K).'||'.$V;
			}
			$STR.=']]]';
			$INFO['TEXT_ID']++;
			$LINEAR[$OFFSET]=array('REF','text',$STR,$INFO['TEXT_ID']);
			$OFFSET+=strlen($STR);
		}
	}

	// echo "IN\n";
	// print_R($INFO['PERMISSION']);exit;
	$LINEAR[$OFFSET]=array('COPYRIGHT','section','Copyright',NULL);
	$OFFSET+=strlen('Copyright');
	if (isset($INFO['PERMISSION']))
	{
	$INFO['PERMISSION']=array_filter($INFO['PERMISSION']);
	foreach ($INFO['PERMISSION'] as $TYPE=> &$SUB_INFO)
	{
		if ($DEBUG)echo "#### LINEARIZE SECTION ".$TYPE."\n";
		if ($DEBUG)echo "NUMBER OF LINEAR: ".count($LINEAR)."\n";
			
			if ($TYPE=='copyright-statement')
			{
				$LINEAR[$OFFSET]=array('COPYRIGHT','title','Copyright statement',1);
				$OFFSET+=strlen('Copyright statement');
				$LINEAR[$OFFSET]=array('COPYRIGHT','text',$SUB_INFO,1);
				$OFFSET+=strlen($SUB_INFO);
			}
			else if ($TYPE=='copyright-year')
			{
				$LINEAR[$OFFSET]=array('COPYRIGHT','title','Copyright year',2);
				$OFFSET+=strlen('Copyright year');
				$LINEAR[$OFFSET]=array('COPYRIGHT','text',$SUB_INFO,2);
				$OFFSET+=strlen($SUB_INFO);
			}
			else if ($TYPE=='license-p')
			{
				$LINEAR[$OFFSET]=array('LICENSE','title','License',1);
				$OFFSET+=strlen('License');
				$LINEAR[$OFFSET]=array('LICENSE','text',$SUB_INFO,1);
				$OFFSET+=strlen($SUB_INFO);
			}
			else if ($TYPE=='license')
			{
				$LINEAR[$OFFSET]=array('LICENSE','title','License source',2);
				$OFFSET+=strlen('License source');
				$LINEAR[$OFFSET]=array('LICENSE','text',$SUB_INFO,2);
				$OFFSET+=strlen($SUB_INFO);
			}
			else
			{
				print_R($INFO['PERMISSION']);
				throw new Exception("UNRECOGNIZED TYPE IN PERMISSION:".$TYPE."\n");
			}
		//	if (isset($))
		
		if ($DEBUG)echo "NUMBER OF LINEAR: ".count($LINEAR)."\n";
	}
	}
	if (isset($INFO['back']['NOTES']))
	{
		$LINEAR[$OFFSET]=array('NOTES','section','Notes',NULL);
		$OFFSET+=strlen('Notes');
		foreach ($INFO['back']['NOTES'] as $NOTE)
		{
			$LINEAR[$OFFSET]=array('NOTES','title',$NOTE[0],NULL);
			$OFFSET+=strlen($NOTE[0]);
			$INFO['TEXT_ID']++;
			$LINEAR[$OFFSET]=array('NOTES','text',$NOTE[1],$INFO['TEXT_ID']);
			$OFFSET+=strlen($NOTE[1]);
		}
	}
	if (isset($INFO['back']['SUPPL']))
	{
		$LINEAR[$OFFSET]=array('SUPPL','section','Supplementary',NULL);
		$OFFSET+=strlen('Supplementary');
		foreach ($INFO['back']['SUPPL'] as $SUPPL)
		{
			//print_R($SUPPL);
			if (isset($SUPPL[1]))
			{
				$LINEAR[$OFFSET]=array('SUPPL','title',$SUPPL[1],NULL);
				$OFFSET+=strlen($SUPPL[1]);
			}
			
			$INFO['TEXT_ID']++;
			$LINEAR[$OFFSET]=array('SUPPL','text',$SUPPL[2],$INFO['TEXT_ID']);
			$OFFSET+=strlen($SUPPL[2]);
			
		}
	}
	if ($DEBUG)echo "END LINEARIZE END\n";
}




function linearizeFront(&$INFO,$LEVEL,&$OFFSET,&$LINEAR,&$PUBLI_ANNOTS)
{
	global $DEBUG;
	if ($DEBUG) echo "START LINEARIZE FRONT\n";
	// Linearize front starts with the title and abstract
	global $DEBUG;
	$INFO['TEXT_ID']=0;
	splitLines($LINEAR,$OFFSET,$INFO['TITLE'],'TITLE','title',NULL,$PUBLI_ANNOTS);
	if (!isset($INFO['ABSTRACT']))return;
	$LINEAR[$OFFSET]=array('ABSTRACT','section','Abstract',NULL);
	$OFFSET+=strlen('Abstract');
	foreach ($INFO['ABSTRACT'] as $S=> &$SEC_L1)
	{

		if ($DEBUG)echo "#### LINEARIZE SECTION ".$S."\n";
		if ($DEBUG)echo "NUMBER OF LINEAR: ".count($LINEAR)."\n";
		if (is_string($SEC_L1))
		{
			$INFO['TEXT_ID']++;
			splitLines($LINEAR,$OFFSET,$SEC_L1,'ABSTRACT','text',$INFO['TEXT_ID'],$PUBLI_ANNOTS);
		}
		else 
		{
			foreach ($SEC_L1 as $TYPE=>&$SUB_INFO)
			{
				if ($DEBUG)echo "\t".$TYPE."\n";
				
				if ($TYPE=='title')
				{
					splitLines($LINEAR,$OFFSET,$SEC_L1['title'],'ABSTRACT','title',$LEVEL,$PUBLI_ANNOTS);
				}
				else if ($TYPE=='text')
				{
					$INFO['TEXT_ID']++;
					splitLines($LINEAR,$OFFSET,$SUB_INFO,'ABSTRACT','text',$INFO['TEXT_ID'],$PUBLI_ANNOTS);
					
				}
				
				else
				{
					throw new Exception("UNRECOGNIZED TYPE IN ABSTRACT:".$TYPE."\n");
				}
			//	if (isset($))
			}
		}
		if ($DEBUG)echo "NUMBER OF LINEAR: ".count($LINEAR)."\n";
	}
	//print_r($LINEAR);
	//exit;
	if ($DEBUG)echo "END LINEARIZE FRONT\n";
}

/// Get floats-group that contains some of the figures
function getFloats(&$RESULTS,&$INFO,&$BLOCKS)
{

	global $DEBUG;
	if ($DEBUG)echo "START LINEARIZE END\n";
	if (!isset($RESULTS['floats-group']))return;
	
	
	if (isset($RESULTS['floats-group'][0]['child']['fig']))
	foreach($RESULTS['floats-group'][0]['child']['fig'] as &$FIG)
	{
		
		
		$LABEL='';
		$CAPTION='Unknown';
		if (isset($FIG['label'][0]['l_value_xml']))
			$LABEL=$FIG['label'][0]['l_value_xml'];
		else if (isset($FIG['caption'][0]['child']['title'][0]['l_value_xml']))
		$LABEL=$BLOCKS[$FIG['caption'][0]['child']['title'][0]['l_value_xml']];
		if (isset($FIG['caption'][0]['child']['p']))
		{
		$CAPTION=$BLOCKS[$FIG['caption'][0]['child']['p'][0]['l_value_xml']];
		if (count($FIG['caption'][0]['child']['p'])>1) die("Multiple captions for float grups");
		}
		else if (isset($FIG['caption'][0]['child']['title'][0]['l_value_xml']))
		{
			$CAPTION=$BLOCKS[$FIG['caption'][0]['child']['title'][0]['l_value_xml']];
		}
	
		$FILE_ID=$FIG['id'];
		$INFO['IMG'][$LABEL]['CAPTION']=$CAPTION;
		$INFO['IMG'][$LABEL]['FILE_ID']=$FILE_ID;

	}

	if (isset($RESULTS['floats-group'][0]['child']['table-wrap']))
	foreach($RESULTS['floats-group'][0]['child']['table-wrap'] as &$TBL_VALUE)
	{
		

		//print_R($TBL_VALUE);
		$TABLE=array('id'=>$TBL_VALUE['id'],
		
		'label'=>isset($TBL_VALUE['child'])?$TBL_VALUE['child']['label'][0]['l_value_xml']:
				(isset($TBL_VALUE['label'])?$TBL_VALUE['label'][0]['l_value_xml']:''),
		'caption'=>array(),
		
		'foot'=>array());

		if (isset($TBL_VALUE['position']))$TABLE['position']=$TBL_VALUE['position'];

		if (isset($TBL_VALUE['child']['table'])) $TABLE['table']=$BLOCKS[$TBL_VALUE['child']['table'][0]['l_value_xml']];
		else if (isset($TBL_VALUE['table'][0]['l_value_xml']))$TABLE['table']=$BLOCKS[$TBL_VALUE['table'][0]['l_value_xml']];
		else if (isset($TBL_VALUE['graphic'][0]['href'])) $TABLE['href']=$TBL_VALUE['graphic'][0]['href'];
		else if (isset($TBL_VALUE['child']['table'][0]['l_value_xml']))$TABLE['table']=$BLOCKS[$TBL_VALUE['child']['table'][0]['l_value_xml']];
		else if (isset($TBL_VALUE['child']['graphic'][0]['href'])) $TABLE['href']=$TBL_VALUE['child']['graphic'][0]['href'];
		else if (isset($TBL_VALUE['alternatives'][0]['child']['table'][0]))
		{
			$TABLE['table']=$BLOCKS[$TBL_VALUE['alternatives'][0]['child']['table'][0]['l_value_xml']];
		}
		else if (isset($TBL_VALUE['caption'][0]['child']['p'][0]['l_value_xml']))
		{
			$TABLE['table']=$BLOCKS[$TBL_VALUE['caption'][0]['child']['p'][0]['l_value_xml']];
		}
		else if (count($TBL_VALUE)==1 && isset($TBL_VALUE['id']))
		{
			# /// PMC3790234      2023-06-19 14:25:08     24018375
			continue;
		}
		else
		{
			echo "Unrecognized table-wrap\n";
			print_R($TBL_VALUE);
			

			throw new Exception("Unable to understand table-wrap");
		}
		
		if (isset($TBL_VALUE['child']))
		{
			//print_R($TBL_VALUE['child']);
			if (isset($TBL_VALUE['child']['caption'][0]['p']))
			foreach ($TBL_VALUE['child']['caption'][0]['p'] as $P)
			{
				$TABLE['caption'][]=$BLOCKS[$P['l_value_xml']];
			}
			else if (isset($TBL_VALUE['child']['caption'][0]['title']))
			{
				foreach ($TBL_VALUE['child']['caption'][0]['title'] as $P)
			{
				$TABLE['caption'][]=$BLOCKS[$P['l_value_xml']];
			}
			}
			
			if (isset($TBL_VALUE['child']['table-wrap-foot']))
			{
				if (isset($TBL_VALUE['child']['table-wrap-foot'][0]['p']))
				foreach ($TBL_VALUE['child']['table-wrap-foot'][0]['p'] as $P)
				{
					$TABLE['foot'][]=$BLOCKS[$P['l_value_xml']];
				}
				else if (isset($TBL_VALUE['child']['table-wrap-foot'][0]['fn'][0]['child']['p']))
				{
					foreach ($TBL_VALUE['child']['table-wrap-foot'][0]['fn'][0]['child']['p'] as $P)
					{
						$TABLE['foot'][]=$BLOCKS[$P['l_value_xml']];
					}
				}
			}
			
		}
		else 
		{
			$CAPT=NULL;
			if (isset($TBL_VALUE['caption'][0]['p']))$CAPT=$TBL_VALUE['caption'][0]['p'];
			else if (isset($TBL_VALUE['caption'][0]['child']['p']))$CAPT=$TBL_VALUE['caption'][0]['child']['p'];
			
			if ($CAPT!=NULL)
			foreach ($CAPT as $P)
			{
				$TABLE['caption'][]=$BLOCKS[$P['l_value_xml']];
			}
			
			$WRAP=NULL;
			if (isset($TBL_VALUE['table-wrap-foot'][0]['p']))$WRAP=$TBL_VALUE['table-wrap-foot'][0]['p'];
			else if (isset($TBL_VALUE['table-wrap-foot'][0]['child']['p']))$WRAP=$TBL_VALUE['table-wrap-foot'][0]['child']['p'];
			if ($WRAP!=null)
			foreach ($WRAP as $P)
			{
				$TABLE['foot'][]=$BLOCKS[$P['l_value_xml']];
			}
		}
		$INFO['table'][]=$TABLE;

	}



	if ($DEBUG)echo "END LINEARIZE END\n";
	
}


/// Process back section of the xml
/// Usually contains acknowledgments, notes, references, and supplementary material
function getBack(&$RESULTS,&$INFO,&$BLOCKS)
{
	global $DEBUG;
if ($DEBUG) echo "START GET BACK\n";
	$BACK=&$RESULTS['back'][0]['child'];
	if ($BACK==null)return;
	$INFO['back']=array();
	foreach ($BACK as $TYPE=>&$VALUE)
	{
		//$INFO['back'][$TYPE]=array();
		if ($TYPE=='ack')
		{
			if (isset($VALUE[0]['p']))
			foreach ($VALUE[0]['p'] as $V)
			{
				$INFO['back']['ACKNOWLEDGMENT'][]=array('',$BLOCKS[$V['l_value_xml']]);
			}
			else if (isset($VALUE[0]['sec']))
			foreach ($VALUE[0]['sec'] as &$sec)
		{
			
			$ACKN='Acknowledgment';
			if (isset($sec['child']['title']))$ACKN=$BLOCKS[$sec['child']['title'][0]['l_value_xml']];
			$INFO['back']['ACKNOWLEDGMENT'][]=array($ACKN,
			$BLOCKS[$sec['child']['p'][0]['l_value_xml']]);
		}
		
		}
		else if ($TYPE=='notes')
		{
			foreach ($VALUE as &$NOTES)
			{
				//print_R($NOTES);
				if (isset($NOTES['sec']))
				{
				$TITLE=$BLOCKS[$NOTES['sec'][0]['child']['title'][0]['l_value_xml']];
				$TEXT=$BLOCKS[$NOTES['sec'][0]['child']['p'][0]['l_value_xml']];
				$INFO['back']['NOTES'][]=array($TITLE,$TEXT);
				}
				else if (isset($NOTES['title']))
				{
					$TITLE=$BLOCKS[$NOTES['title'][0]['l_value_xml']];
				$TEXT=$BLOCKS[$NOTES['p'][0]['l_value_xml']];
				$INFO['back']['NOTES'][]=array($TITLE,$TEXT);
				}
				else if (isset($NOTES['fn-group']))
				{
					foreach ($NOTES['fn-group'][0]['child']['fn'] as &$FN)
					{
						$TITLE='Title:';
						if(isset($FN['fn-type']))$TITLE=$FN['fn-type'];
						$TEXT=$BLOCKS[$FN['p'][0]['l_value_xml']];
						$INFO['back']['NOTES'][]=array($TITLE,$TEXT);
					}
				}
			}
			
		}
		else if ($TYPE=='app-group')
		{
			foreach ($VALUE[0]['app'] as &$APP)
			{
				//print_R($APP);
				
				$ID=null;
				if (isset($APP['id']))$ID=$APP['id'];
				$E=null;
				if (isset($APP['child']['sec']))
				{
					$E=&$APP['child']['sec'][0];
				}
				else $E=$APP['child'];
				if (isset($E['id']))$ID=$E['id'];
				

				/// Issue with PMC3287300/22393496
				if (isset($E['fig'])||isset($E['table-wrap']))continue;

				$TITLE=$BLOCKS[$E['title'][0]['l_value_xml']];
				if (isset($E['p']))
				{
					$TEXT=$BLOCKS[$E['p'][0]['l_value_xml']];
					$INFO['back']['SUPPL'][]=array($ID,$TITLE,$TEXT);
				}
				else if (isset($E['sec']))
				{
					$TEXT='';
					
					foreach ($E['sec'][0] as &$SEC)
					{
						
						if (!is_array($SEC))continue;
						
						$TITLE=$BLOCKS[$SEC['title'][0]['l_value_xml']];
						if (isset($SEC['p']))
						{
						$TEXT=$BLOCKS[$SEC['p'][0]['l_value_xml']];
						$INFO['back']['SUPPL'][]=array($ID,$TITLE,$TEXT);
						}
						if (!isset($SEC['sec']))continue;
						foreach ($SEC['sec'] as &$SEC_2)
						{
							$TITLE=$BLOCKS[$SEC_2['title'][0]['l_value_xml']];
							$TEXT='';
							foreach ($SEC_2['p'] as $SP)$TEXT.=$BLOCKS[$SP['l_value_xml']].'<br/>';
							
						}
					}
				}
				
			}
		//	print_R($VALUE);exit;
		}

		else if ($TYPE=='fn-group')
		{
			
			foreach ($VALUE[0]['fn'] as &$FN)
			{
				if (isset($FN['fn-type']))
				$INFO['back']['FINANCIAL'][]=array('type'=>$FN['fn-type'],
				'text'=>$BLOCKS[$FN['child']['p'][0]['l_value_xml']]);
				else 
				$INFO['back']['FINANCIAL'][]=array(
				'text'=>$BLOCKS[$FN['child']['p'][0]['l_value_xml']]);
			}
			
			
		}
		else if ($TYPE=='ref-list')
		{
			//print_R($VALUE);
			$REFS=null;
			if (isset($VALUE[0]['ref']))$REFS=&$VALUE[0]['ref'];
			else if (isset($VALUE[0]['ref-list'][0]['ref']))$REFS=&$VALUE[0]['ref-list'][0]['ref'];
			if ($REFS==null)continue;
			foreach ($REFS as &$REF)
			{
				$REF_INFO=array('NAMES'=>array());
				$REF_ELEM=null;
				/// References can be under different tags
				if (isset($REF['child']['element-citation']))$REF_ELEM=&$REF['child']['element-citation'][0];
				else if (isset($REF['child']['citation']))$REF_ELEM=&$REF['child']['citation'][0];
				else if (isset($REF['child']['mixed-citation']))$REF_ELEM=&$REF['child']['mixed-citation'][0];
				else if (isset($REF['child']['note']))
				{
					# Case PMC3397360/22929786
					continue;
				}
				else 
				{
					print_R($REF);
					throw new Exception("Unrecognized reference");
					
				}
				//print_R($REF);
				/// Those are the authors:
				if (isset($REF_ELEM['person-group']))
				{
					//print_R($REF_ELEM);
					if (isset($REF_ELEM['person-group'][0]['child']['collab']))
					{
						if (isset($REF_ELEM['person-group'][0]['child']['collab'][0]['l_value_xml']))
						$REF_INFO['NAMES'][]=$REF_ELEM['person-group'][0]['child']['collab'][0]['l_value_xml'];
						else if ($REF_ELEM['person-group'][0]['child']['collab'][0]['italic'][0]['l_value_xml'])
						$REF_INFO['NAMES'][]=$REF_ELEM['person-group'][0]['child']['collab'][0]['italic'][0]['l_value_xml'];
					}
					else if (isset($REF_ELEM['person-group'][0]['child']['name']))
					{
						//print_R($REF_ELEM);
						foreach ($REF_ELEM['person-group'][0]['child']['name'] as &$NAME)
						{
							//print_R($NAME);
							$SURNAME=$NAME['surname'][0]['l_value_xml'];
							$REF_INFO['NAMES'][]=$SURNAME;
							if (isset($NAME['given-names']))$REF_INFO['NAMES'][].=' '.$NAME['given-names'][0]['l_value_xml'];
							
						}
					}
					else if (isset($REF_ELEM['person-group'][0]['child']))
					{
						//print_R($REF_ELEM);
						foreach ($REF_ELEM['person-group'][0]['child'] as &$NAME)
						{
						
							$SURNAME=$NAME[0]['surname'][0]['l_value_xml'];
							$REF_INFO['NAMES'][]=$SURNAME;
							if (isset($NAME[0]['given-names']))$REF_INFO['NAMES'][].=' '.$NAME[0]['given-names'][0]['l_value_xml'];
							
						}
					}
					else if (isset($REF_ELEM['person-group'][0]['l_value_xml']))
					{
						$REF_INFO['NAMES'][]=$REF_ELEM['person-group'][0]['l_value_xml'];
					}
				}
				
				/// Links, such as doi or pmid
				if (isset($REF_ELEM['pub-id']))
				{
					//print_R($REF_ELEM['pub-id']);
					foreach ($REF_ELEM['pub-id'] as $P)
					{
						$REF_INFO['PUB_ID'][$P['pub-id-type']]=$P['l_value_xml'];	
					}
				}
				
				//print_R($REF_ELEM);
				if (isset($REF_ELEM['article-title']))
				{
					$TITLE=$REF_ELEM['article-title'][0]['l_value_xml'];
					if (substr($TITLE,0,5)=='BLOCK')
					$REF_INFO['TITLE']=$BLOCKS[$REF_ELEM['article-title'][0]['l_value_xml']];
					///Case PMC3182029/21966286
					else $REF_INFO['TITLE']=$REF_ELEM['article-title'][0]['l_value_xml'];
				}
				else if (isset($REF_ELEM['collab']))$REF_INFO['TITLE']=$REF_ELEM['collab'][0]['l_value_xml'];

				//print_R($REF_ELEM);
				if (isset($REF_ELEM['source']))
				{
					if (isset($REF_ELEM['source'][0]['l_value_xml']))$REF_INFO['SOURCE']=$REF_ELEM['source'][0]['l_value_xml'];
					else if (isset($REF_ELEM['source'][0]['child']['italic'][0]['italic'][0])) $REF_INFO['SOURCE']=$REF_ELEM['source'][0]['child']['italic'][0]['italic'][0]['l_value_xml'];
					else if (isset($REF_ELEM['source'][0]['child']['italic'][0])) $REF_INFO['SOURCE']=$REF_ELEM['source'][0]['child']['italic'][0]['l_value_xml'];
					
				}
				else if (isset($REF_ELEM['source'][0]["l_value_xml"]))
				{
					//print_R($REF_ELEM);
					$REF_INFO['TITLE']=$REF_ELEM['source'][0]["l_value_xml"];
					if (isset($REF_ELEM['publisher-name']))$REF_INFO['source']=$REF_ELEM['publisher-name'][0]['l_value_xml'];
					else $REF_INFO['source']=$REF_ELEM['edition'][0]['l_value_xml'];
				}
				$REF_INFO['ID']=$REF['id'];
				if (isset($REF_ELEM['ext-link']))$REF_INFO['URL']=$REF_ELEM['ext-link'][0]['href'];
				
				if (isset($REF_ELEM['year']))$REF_INFO['YEAR']=$REF_ELEM['year'][0]['l_value_xml'];
				if (isset($REF_ELEM['fpage']))$REF_INFO['FPAGE']=$REF_ELEM['fpage'][0]['l_value_xml'];
				if (isset($REF_ELEM['lpage']))$REF_INFO['LPAGE']=$REF_ELEM['lpage'][0]['l_value_xml'];


				$INFO['back']['REF']["[".$REF_INFO['ID']."]"]=$REF_INFO;
				
			}
		}
		/// In some rare cases there are sub sections.
		else if ($TYPE=='sec')
		{
			foreach ($VALUE as $SUB_SEC=>&$SUB_VALUE)
			{
				
				if (!isset($INFO['back']['sec']))$INFO['back']['sec']=array();
				$N_REC=count($INFO['back']['sec']);
				$INFO['back']['sec'][$N_REC]=array();
				if (isset($SUB_VALUE['child']))processSection($SUB_VALUE['child'],$INFO['back']['sec'][$N_REC],$BLOCKS);
				else processSection($SUB_VALUE,$INFO['back']['sec'][$N_REC],$BLOCKS);
				
			}
			
		}
	}

	
	
	
}



/// PRocess that analyze a word (or set of words) and check if it's part of the annotation

function validateWord(&$word,&$start,$n_word,&$RESULTS)
{
	/// Annotation list
	global $ANNOTS;
	global $DEBUG;
	/// Those are the acronyms:
	global $ALT_ANNOT;
	/// Excluded words
	global $EXCLUDE_ANNOT;


	/// First we remove any special characters
	$TO_REMOVE=array('(',')','[',']');
	foreach ($TO_REMOVE as $R)$word=str_replace($R,'',$word);
	
	/// We don't want to have numbers, they are non-specific
	if (is_numeric($word)||is_float($word))return false;
	
	/// Special situation
	if ($word=='a gene')return false;

	/// If it's an acronym
	if (isset($ALT_ANNOT[$word]))
	{
		if (isset($ALT_ANNOT[$word]['NULL']))return false;
		// echo $word."\n";
		// print_R($ALT_ANNOT[$word]);
		// Then we add the annotations associated with the acronym
		foreach ($ALT_ANNOT[$word] as &$AA)
		{
			$TYPE=$AA[0];
			$TYPE_V=$AA[1];
			$FOUND=false;
			if ($DEBUG)echo "\t\t".$word."\t".$TYPE."\t".$TYPE_V."\t".$start."\t".$n_word."\tFROM ACRONYM\n";
			
			/// But we don't want to have duplicates
			foreach ($RESULTS as &$E)
			{
				if ($E[0]!=$TYPE)continue;
				if ($E[1]==$TYPE_V)continue;
				if ($E[2]!=$start)continue;
				if ($E[3]!=$n_word)continue;
				$FOUND=true;
			}
			if (!$FOUND)$RESULTS[]=array($TYPE,$TYPE_V,$start,$n_word,$word);
		}
		return true;
	
	}
	/// If it's an excluded word - we don't want to have it
	else if (isset($EXCLUDE_ANNOT[$word]))return false;

	/// If it's a word that is not part of an annotation - we don't want to have it
	if (!isset($ANNOTS[$n_word][$word]))return false;	
	
	/// Then we add the annotations associated with the word
	foreach ($ANNOTS[$n_word][$word] as $annot)
	{
		if ($DEBUG)echo "\t\t".$word."\t".$annot[0]."\t".$annot[1]."\t".$start."\t".$n_word."\n";
		$FOUND=false;
		
		/// But we don't want to have duplicates
		foreach ($RESULTS as &$E)
		{
			if ($E[0]!=$annot[0])continue;
			if ($E[1]!=$annot[1])continue;
			if ($E[2]!=$start)continue;
			if ($E[3]!=$n_word)continue;
			$FOUND=true;
			
		}
		
		if (!$FOUND)$RESULTS[]=array($annot[0],$annot[1],$start,$n_word,$word);
	}
	return true;
	
}



/// Clean up a line (or set of words)
/// Remove any special characters
function cleanLine($line)
{
	/// Greek characters replaced by their name
$GREEK=array(
	'&#x003b1;'=>'alpha',
	'&#x003b2;'=>'beta',
	'&#x003b3;'=>'gamma',
	'&#x003b4;'=>'delta',
	'&#x003b5;'=>'epsilon',
	'&#x003b6;'=>'zeta',
	'&#x003b7;'=>'eta',
	'&#x003b8;'=>'theta',
	'&#x003b9;'=>'iota',
	'&#x003ba;'=>'kappa',
	'&#x003bb;'=>'lambda',
	'&#x003bc;'=>'mu',
	'&#x003bd;'=>'nu',
	'&#x003be;'=>'xi',
	'&#x003bf;'=>'omicron',
	'&#x003c0;'=>'pi',
	'&#x003c1;'=>'rho',
	'&#x003c2;'=>'sigmaf',
	'&#x003c3;'=>'sigma',
	'&#x003c4;'=>'tau',
	'&#x003c5;'=>'upsilon',
	'&#x003c6;'=>'phi',
	'&#x003c7;'=>'chi',
	'&#x003c8;'=>'psi',
	'&#x003c9;'=>'omega',
	'&#x00391;'=>'ALPHA',
	'&#x00392;'=>'BETA',
	'&#x00393;'=>'GAMMA',
	'&#x00394;'=>'DELTA',
	'&#x00395;'=>'EPSILON',
	'&#x00396;'=>'ZETA',
	'&#x00397;'=>'ETA',
	'&#x00398;'=>'THETA',
	'&#x00399;'=>'IOTA',
	'&#x0039a;'=>'KAPPA',
	'&#x0039b;'=>'LAMBDA',
	'&#x0039c;'=>'MU',
	'&#x0039d;'=>'NU',
	'&#x0039e;'=>'XI',
	'&#x0039f;'=>'OMICRON',
	'&#x003a0;'=>'PI',
	'&#x003a1;'=>'RHO',
	'&#x003a3;'=>'SIGMA',
	'&#x003a4;'=>'TAU',
	'&#x003a5;'=>'UPSILON',
	'&#x003a6;'=>'PHI',
	'&#x003a7;'=>'CHI',
	'&#x003a8;'=>'PSI',
	'&#x003a9;'=>'OMEGA',
	'&#x000b5;'=>'micro'
);

/// Other characters with special meaning replaced by their regular letters or removed
	$RULES=array(','=>'',
	'&#x02014;'=>'',
	'&#x02013;'=>'',
	'&#x02212;'=>'',
	'&#x000d7;'=>'x',
	'&#x000fc;'=>'u',
	'&#x000dc;'=>'U',
	'&#x000e4;'=>'a',
	'&#x000c4;'=>'A',
	'&#x000c3;'=>'A',
	'&#x000e3;'=>'a',
	'&#x000f6;'=>'o',
	'&#x000d6;'=>'O',
	'&#x000f8;'=>'o',
	'&#x000d8;'=>'O',
	'&#x000e9;'=>'e',
	'&#x000c9;'=>'E',
	'&#x000e8;'=>'e',
	'&#x000c8;'=>'E',
	'&#x000e0;'=>'a',
	'&#x000c0;'=>'A',
	'&#x000e1;'=>'a',
	'&#x000c1;'=>'A',
	'&#x000e2;'=>'a',
	'&#x000c2;'=>'A',
	'&#x000ea;'=>'e',
	'&#x000ca;'=>'E',
	'&#x000eb;'=>'e',
	'&#x000cb;'=>'E',
	'&#x000ed;'=>'i',
	'&#x000cd;'=>'I',
	'&#x000ee;'=>'i',
	'&#x000ce;'=>'I',
	'&#x000f3;'=>'o',
	'&#x000d3;'=>'O',
	'&#x000f4;'=>'o',
	'&#x000d4;'=>'O',
	'&#x000f5;'=>'o',
	'&#x000d5;'=>'O',
	'&#x000fa;'=>'u',
	'&#x000da;'=>'U',
	'&#x000fb;'=>'u',
	'&#x000db;'=>'U',
	'&#x000fc;'=>'u',
	'&#x000dc;'=>'U',
	'&#x000f1;'=>'n',
	'&#x000d1;'=>'N',
	'&#x02019;'=>"'",
	'&#x000b4;'=>"'",
	'&#x02032;'=>"'",
	'&#x00026;'=>'&',
	'&#x0002b;'=>'+',
	'&#x00025;'=>'%',
	'&#x000ef;'=>'i',
	'&#x000e5;'=>'a',
	'&#x000ba;'=>'o',
	'&#x000aa;'=>'a',
	'&#x000e7;'=>'c',
	'&#x000c7;'=>'C',
	'&#x000e7;'=>'c',
	'&#x000df;'=>'B',
	'&#x025b5;'=>'e',
	'&#x02248;'=>'=',
	'&#x00040;'=>'@',
'&#x000a6;'=>'|',
'&#x000a9;'=>'©',
'&#x000ab;'=>'<<',
'&#x000af;'=>'-',
'&#x000b2;'=>'',
'&#x000bb;'=>'>>',
'&#x000bc;'=>'1/4',
'&#x000bd;'=>'1/2',
'&#x000d2;'=>'O',
'&#x000e6;'=>'ae',
'&#x000f7;'=>'/',
'&#x00107;'=>'C',
'&#x0010d;'=>'c',
'&#x0011f;'=>'g',
'&#x00160;'=>'S',
'&#x02297;'=>'O',
'&#x002dc;'=>'~',
'&#x02002;'=>' ',
'&#x02009;'=>' ',
'&#x0200a;'=>' ',
'&#x02018;'=>"'",
'&#x0201e;'=>'"',
'&#x02026;'=>'...',
'&#x020ac;'=>'€',
'&#x1d4cf;'=>'z',
'&#x1d4be;'=>'i',
'['=>'',
']'=>'',

	);

	foreach ($RULES as $FROM=>$TO)$line=str_ireplace($FROM,$TO,$line);


	/// Greek characters are a bit of a special case.
	/// some will be placed at the beginning of the word and some at the end
	/// Many time with a greek letter there will be a dash
	/// So we need to locate the greek letter within the word and position the dash accordingly
	foreach ($GREEK as $FROM=>$TO)
	{
		$pos=strpos($line,$FROM);
		if ($pos===false)continue;
		$tab=explode(" ",$line);
		//echo "GREEK:".$FROM."\t".$TO."\t".$pos."\t".strlen($line)."\n";
		foreach ($tab as &$tmp_word)
		{
			$pos=strpos($tmp_word,$FROM);
			if ($pos===false)continue;
			//echo $tmp_word."\t".$pos."\n";
			if ($pos==0)
			{
				$tmp_word=str_ireplace($FROM,$TO.'-',$tmp_word);
			}
			else if($pos+strlen($FROM)==strlen($tmp_word))
			{
				$tmp_word=str_ireplace($FROM,'-'.$TO,$tmp_word);
			}
			else 
			{
				$tmp_word=str_ireplace($FROM,'-'.$TO.'-',$tmp_word);
			}
				 
				
			
		}
		$line=implode(" ",$tab);
		
	}
	/// However, in some cases, the dash is already present
	/// so we need to remove it and keep just one
	$line=str_replace('--','-',$line);
	//echo "\t".$line."\n";

	/// Some special characters are allowed/ignored
	$ALLOWED=array(
		'&#x000B1;','&#x02022;','&#x02245;','&#x02314;','&#x025aa;','&#x000b7;','&#x0221a;','&#x000a0;','&#x000fd;','&#x025a9;',
		'&#x025a1;','&#x02313;','&#x025cf;','&#x025cb;','&#x000c5;','&#x02666;','&#x025b8;','&#x025b9;','&#x02122;','&#x000b1;',
		'&#x02021;','&#x02211;','&#x000a3;','&#x0223c;','&#x02192;','&#x02190;','&#x02191;','&#x02193;','&#x02194;','&#x02195;',
		'&#x02196;','&#x02197;','&#x02198;','&#x02199;','&#x000b6;','&#x02020;','&#x000a7;','&#x000ae;','&#x0003c;','&#x0003e;',
		'&#x000b0;','&#x0201c;','&#x0201d;','&#x02003;','&#x02265;','&#x02264;','&#x0226A;','&#x0226B;','&#x02113;','&#x0211d;',
		'&#x02200;','&#x02205;','&#x02207;','&#x02208;','&#x02209;','&#x0221d;','&#x0221e;','&#x02229;','&#x0222a;','&#x0222b;',
		'&#x02243;','&#x02260;','&#x02261;','&#x0226a;','&#x0226b;','&#x02272;','&#x02273;','&#x0025b;','&#x002c7;','&#x02329;',
		'&#x1d4c3;','&#x0232a;','&#x02524;','&#x025a0;','&#x025b2;','&#x025b3;','&#x025b4;','&#x025bc;','&#x025c6;','&#x025c7;',
		'&#x025ca;','&#x02640;','&#x02642;','&#x02660;','&#x02663;','&#x1d4cc;','&#x02217;','&#x1d4c2;','&#x1d4bf;','&#x1d4bb;',
		'&#x1d4ca;','&#x02282;','&#x02227;','&#x000ac;','&#x02203;','&#x02286;','&#x021d2;','&#x0002a;','&#x000a1;','&#x0fe38;',
	'&#x02a7e;','&#x02a7d;','&#x02a7c;','&#x02a7b;','&#x02a7a;','&#x02a79;','&#x02a78;','&#x02a77;','&#x02a76;','&#x02a75;',);
	$pos=strpos($line,'&#')	;
	while ($pos!==false)
	{
		$pos2=strpos($line,';',$pos);
		$len=$pos2-$pos+1;
		$code=strtolower(substr($line,$pos,$len));
		/// But to keep track of them we print them
		// if (!in_array($code,$ALLOWED))
		// {
		// 	echo "WEIRD CHAR\t".$code."\n";
		// //	echo $line."\n";
		// 	//exit;
		// }
		$pos=strpos($line,'&#',$pos+1);
	}

	return $line;
}


/// Test if the word is an acronym
function testForAcronym(&$clean_words,&$words,&$start,&$n_word,&$len,&$RESULTS)
{
	global $ALT_ANNOT;
	// It is!! Great, no we need to check if the next word could be an acronym
		/// The rule is that the next word should be in parenthesis
		/// And if it's a single word, we can consider it as an acronym
	if (!isset($clean_words[$start+$n_word])) return;
	if ($clean_words[$start+$n_word]=='') return;
	if ($clean_words[$start+$n_word][0]!='(')return;


	// In some case, the acronym is in the next word
	/// But in others, it's just a parenthesis with many words
	// So we need to go back to the initial words, get the full content of the parenthesis
	/// because clean_words is the cleaned version of the words that removes the parenthesis
	$ini_wording='';
	for ($I=$start+$n_word;$I<$len;++$I)
	{
		$pos=strpos($words[$I],')');
		if ($pos===false)$ini_wording.=$words[$I].' ';
		else 
		{
			$ini_wording.=substr($words[$I],0,$pos);
			break;
		}
	}
	
	if (substr($ini_wording,0,3)=='([[')return;
		
	/// Number of words in the parenthesis
	$N_ACR_WORD=count(explode(" ",$ini_wording));
	/// And if it's a single word, we can consider it as an acronym
	if ($N_ACR_WORD!=1)return;
	
	/// We reconstruct the acronym
	$ACR=str_replace("(","",str_replace(")","",$clean_words[$start+$n_word]));
	$ACR=cleanLine($ACR);
	
	/// If there's a ; we remove it
	$pos=strpos($ACR,';');
	
	if ($pos!==false)$ACR=substr($ACR,0,$pos);


	/// This is an acronym without annotations
	if (isset($ALT_ANNOT[$ACR]['NULL']))return;


	if (is_numeric($ACR))return;
	if (is_float($ACR))return;
	if (!preg_match('/[a-zA-Z0-9]/',$ACR))return;
	if (preg_match('/[AUTCG]+/',$ACR))return;
	if (preg_match('/^[%$][-+]?\d+([,.]\d{0,2})?|^[-+]?\d+([,.]\d{0,2})?[$%]/',$ACR))return;
	if (preg_match('/&#x[0-9a-zA-Z]/',$ACR))return;
	if (preg_match('/[0-9]{1,5}\s?([\-\:\/]\s?[0-9]{1,5}){1,4}/',$ACR))return;
	if (preg_match('/[0-9.]+/',$ACR))return;
	if (preg_match('/^[\-vwVW\+]([\/][\+\-vwVW]){1,5}$/',$ACR))return;
	if (preg_match('/^[nN]\s{0,1}=\s{0,1}\d+$/',$ACR))return;
	if (preg_match('/^[pP]\s{0,1}=\s?\d*\.?\d*+$/',$ACR))return;

	$UNITS=array(
		'mu-M','mu-mol/L','micro-M','micro-g/ml','mu-mol/gDW',
		'mg/kg',' g','moL','mM/min','mg/g','mg/kg',
		'%',
		'ng/mL','mu-g/mL','IU/mL','mg/mL','mu-g ml1',
		'ng/ml','mu-g/ml','IU/ml','mg/ml','mu-g mL1',
		's/sequence','v/v','mg/day',
		'molecules/cell/s','mu-g/m3',
		'mg/dl',
		'mmol/L','mg/L','g/L',
		'mmol/l','mg/l','g/l',
		'g/dL',
		'g/dl',
		'ng ml1',
		'kg/ms','mg kg1',
		'%w/v',' mol','mol%','mmol','mmol/g',' mg kg1',
		'cm2','Hz','M','/year','mk2',',mg/cm2',
		'mM','nM',' %/unit'
	);

	$CASE_INSENSITIVE=array(
		'Figure','control','BioRad','Bio-Rad','method','Invitrogen','Sigma','Aldrich',
		'Roche','red','blue','green','yellow','black','white','orange','purple',
		'pink','brown','grey','gray','cyan','magenta','violet','indigo','ligand',
		'arrow','arrowhead','circle','triangle','square','underlined','continous','centered',
		'diamond','asterisk','CENTRAL','arrowed','PharMingen','case','Clonetics','HyClone','Ethicon',
		'Fisher','Scientific','Applied','Biosystems','Qiagen','Promega','Agilent','Technologies','Illumina',
		'GE','Healthcare','BioLegend','BD','Biosciences','Bio-Techne','BioLegend','Sigma-Aldrich','Clontech','PeproTech',
		'eBioscience','DakoCytomation','PerkinElmer','Novagen','Boehringer','Adobe','Peprotech','Merck','Fermentas','GibcoBRL',
		'Covance','Mediatech','Affymetrix','Millipore','Calbiochem','Stratagene','Ambion','Gibco','Pierce','ambion',
		'SigmaAldrich','Table','Takara','vector','Beyotime','Wako','Hyclone','Difco','Fluka','Bioline','Proteintech',
		'Dharmacon','Amersham','QUIAGEN','Chemicon','Pharmacia','Solarbio','Selleckchem','Ventana','Venzyme','Worthington',
		'above','left','right','top','bottom','upper','middle','Upstate','strongly','fixed','inset','forward','below','lower','center',
		'mitophagy','Milipore','Siemens','Review',
		'Thermo','process','human','pdf','apoptosis','years','Novartis',
		'pg/ml','mmol/l','ng/ml','mg/dl','cm','+/','+/-','db/db','mu-g/ml','mg/day','vol/vol/5%','mg/dL','10 mg/kg',
		'ng/mL','mg/L','monoclonal','pmol/l',
		'y-axis','x-axis',
		'wild-type',
		'positive',
	);
	foreach ($CASE_INSENSITIVE as $M)
	{
		if (strpos(strtolower($ACR),strtolower($M))!==false)return;
	}

	if (strlen($ACR)==1)return;
	$exception=array(
		'iii','III','vi','VI','v','V','iv','IV','ii','II','i','I',
		'x200','1x105','100 mu-g/mL','x400','mmol/L/22.5','2x106','5 mg/kg','50 mu-M','g/cm2','50 mg/kg','x100','2x105','100 nM',
		'5 mu-M','20 mu-M','mM','pg/mL','100 mg/kg','10mu-g','10 nM','100x','200x','2 mM','400x','5 mM','++','liver','colon','g/L','10 ng/ml',
		'breast','negative','chicken','mu-mol/L','g/dL','mu-U/mL','1 mg/kg','baseline','1x106/ml','active','nuclei','50 mu-g/ml','ADME','3-fold','Pfizer','protein','20 mg/kg','1-micro-M','10 ng/mL',
		'1 mu-M','100 mu-M','10 mu-M','100 mu-g ml1','106/ml','mmol/L','1 mM','1x105','30 mu-g','mmHg','yes','no','serum',
		'wt','mg','rat','mouse','low','high','normal','up','In','solid','Japan','cc','respectively','brain','columns','input','rows','weak',
		'vol/vol','wt/vol','m/z','kg','mu-g/mL','g/day','reference','micro-g/mL','France','bilaterally','plasma','Fujufilm','Polysciences','upregulated','no/yes','forward:','v:v','variable','pm','primary','Yes/No',
		'untreated','unregulated','downregulated','mu-mol/l','females','wt%','women','mg/ml','four','dashed','receptor','lines','Human','blank','severe','alone','insert','inactivation','increased','self-reported','intervention','reverse:','down-regulated','mol%','unspecified',
		'combined','inactive','light','weeks','medium','open','alpha','acetone','crosses','decreased','translation','mean','secondary','increase','binary','median','anterior','umol/L','shaded','mg/d','five','Normal','resistant','nmol/l','rare','cytosol','parental','intracellular',
		'membrane','m/v','seven','adult','acid','experimental','soluble','output','treated','exporsure',
		'self-report','decrease','sugar','Bacteria','Lower','Forward','surface','heart','Fragment',
		'phenotype','mix','mixed','beta','dotted','units','trans','dorsal','humans','mice','mild','Input','solvent','powder','drug','background','antisense','year','Spain',
		'mother','model','enzyme','susceptible','minutes','Italy','product','shown',
		'Mouse','malignant','responders','degrees','benign','overall','vertical',
		'cells','min/day','absolute','overnight','linear','free','analyte','naive',
		'ground','focal','unknown','nm','saline','intravenous','color','transcription','exposure','inhibition','growth','Beijing',
		'up-regulated','presence/absence','six','two','three','four','five','seven',
		'neutral','seed','none','min/day','mmol','major','mm/h','precursor','positively',
		'rectangle','downregulation','categorical','agonist','resting','products',
		'inhibitory','activity','receptors','ng/g','first','adults','negatively',
		'eight','bovine','surfactant','m/s','other','fasting','erasers','template',
		'mg/dL',
		'induced','optional','mono','saturated','null','carrier','anionic','parent',
		'gas','g/g','upregulation',

		'one','yes/no','three','two','twice','tissue','thickness','summar',
		'surviving','swallowed','synthesis','anhydrous','placebo','turquoise','reverse','g/l',
		'activated','-like',
		'female','male','mg/L','lower','human',
		'pipette','continuous','asterisk','sense','activation','diamonds','vehicle','star','donor','Ctrl',
		'P=0.001','p = 0.001',
		'standard','unpublished','total',
		'Ins/SII', 'Kcal/mol', 'kg/kg', 'KOH/methyl', 'laterally/area', 'L/kg', 'L/M', 'mcg/day', 
		'MDS/myeloproliferative', 'MFH/undifferentiated', 'mg/dl*', 'mg/dlHDL', 'mg/mm', 'minutes/week',
		 'M/L', 'mmoL/L', 'mmol/La', 'mmol/l x fasting', 'mmol/L x insulin', 'm/p+', 'MP/PBS', 'm/sec',
		  'mu-M/L', 'mu-mol/kg', 'M/V', 'Na/K', 'ng/ml/h', 'nose/throat', 'NS/MPD', 'PBS/', 'p-ERK/ERK',
		   'pg/dL', 'Pg/mg', 'P/P', 'primary/secondary', 'p/second', 'Smac/direct', 'SOF/LDV', 
		'total/direct', 'units/L', 'WD/DDLS', 'W/O', '%w/w', 'absence/presence', 'BLM/', 'd/d',
		'D/recipient',  'ER-positive/human', 'exp/', 'fibers/mm', 'Firefly/Renilla', 
		'F/M', 'g/rat/day', '/h', '%/h', 'HF/HS', 'HR+/human', 'Ile/Val', 'l/day', 
		'l/min', 'Met/Met', 'micro-g/dl', 'micro-g/mg', 'mmol/dL', 'mmol/l', 'mmol/L/',
		'mouse/human', 'mu-mol/min/mg', 'on/off', 'patientYes/noOR:', 'pg/dl', 
		'P/L', 'P/M', 'pRb/', 'S/N', 'SOF/ledipasvir', 'S/P', 'w/v%', 'yes/no',
		'FL/RL', 'Ins/Del', 'kcal/day', 'KOH/g', 'LDLr/', 'Leu/Leu', 'mg/dla',
		'mg/L/serum', 'mg/week', 'minutes/day', 'ml/kg/min', 'mmol/g', 'mmol/L*',
		'M/NEMS', 'Mouse/Rabbit', 'mu-kat/l', 'mu-mol/mL', 'm/V', 'ng/ml:', 
		'n/-micro-l', 'pmol/ml', '%/s', 'S/D', 'times/week', 'ug/dl', 'up/down', 
		'v/v:', 'wt/v', 'yes/no?', 'ar/R', '%/day', 'D/HH', 'E/D', 'E/P', 
		'F/P', 'FVB/N', 'Ile/Ile', 'L/d', 'LE/lysosome', 'LE/lysosomes', 
		'mg/dLc', 'mg/liter', 'mg/weight', 'micro-g/d', 'mm/hour', 'Mono/Mono', 
		'N/%', 'ng/ml', 'ng/mL', 'ng/-mu-l', 'nitrate/nitrite', 'PBS/FBS', 'SD/-Leu',
		'SI/SII', 'years:/', 'c/EBP', 'cfu/ml', 'D+/R', 'fa/fa', 'F/S', 'g/week', 
		'HS/heparin', 'I/F', 'IPP/dimethylallyl', 'kcal/kg', 'kJ/g', 'LDL/vLDL', 
		'LPV/r-based', 'L/Z', 'mg/kg/h', 'mm/s', 'MRL/lpr', '/-mu-l', 'mu-m/day', 
		'mu-mol/L:', 'ng/mL:', 'nitrite/nitrate', 'PSP/reg', 'B/M', 'cfu/mL', 
		'dV/dtmax', 'hours/day', 'kg/ha', 'kJ/mol', 'K+/Na+', '/L', 'M/F',
		'mg/kg/min', 'micro-m/s', 'M/M', 'mm/hr', 'OE/HS', 'PE/E', 'pg/mL:',
		'+/PI', 'PI/r-based', 'ug/ml', '-/anti-HBc', 'Balb/c', 'BPH/lower',
		'F/B', 'FSH/LH', 'gm/dl', 'g/min', 'HDL/low-density', 'I/R', 'L/N', 
		'p/v', 'R/K',  '%/year', '+dP/dt', 'ES/primitive', 
		'Fv/Fo', 'H/H', 'L/day', 'live/dead', 'L/P', 'mg/body',
		'mg/dLb', 'micro-mol/g', 'min/wk', 'M/P', 'negative/positive', 'Q/D', 
		'Rp/Sp', 'S/M', 'Val/Val', 'VOD/SOS', 'w/vol', 'w/w%', 'iv/sc', 'Kt/V', 
		'ME/chronic', 'mL/L', 'mM/L', 'mmol/ml', 'mu-m/s', 'No/Yes', 'pg/ml:',
		'volume/volume', 'weight/weight', 'cells/-micro-L', '', 'L/h', 'mcg/dL', 'mg/dl x fasting',
		'micro-g/kg', '/-micro-L', 'mmol/la', 'mg/mg', 'Na+/K+', 'cells/-micro-l', 'cm/height', 'D/P', 
		'mg/dl:', 'min/+', 'mu-g/d', 'Peg-IFN/RBV', 'ug/dL', 'g/body', 'kg/height', 'mg/dlSerum', 
		'cells/ml', 'cells/mL', 'g/cm', 'g/ml', 'mg/dL*', 'mg/dLxfasting', 'mL/g', 'mmol/kg', 'mmol/Lb',
		'D/D', 'g/kg/day', 'LH/human', 'micro-g/kg/min', 'mu-g/kg/min', 'P/O', 'mg/L:', 'umol/l', 'D/E',
		'fl/fl', 'ug/mL', 'cm/hip', 'dP/dt', 'HDL/LDL', 'LDLR/', 'MDS/acute', 'mmol/d', 'PML/retinoic', 
		'R/S', 'serum/plasma', 'ug/L', 'XX/XY', 'a/b', 'cells/-mu-l', 'F/R', 'Ldlr/', 'mmol/l:', 'W/kg',
		'BLM+/+', 'LDV/SOF', 'm/zProduct', 'III/IV', 'mmol/day', 'nmol/mg', 'null/present', 'PI/annexin', 
		'plasma/serum', 'prM/M', 'Fluc/Rluc', 'low/high', 'ng/mL/h', 'high/low', 'mu-g/mu-l',
		'VIP/secretin/glucagon', 'apoptosis/necrosis', 'g/mol', 'N/R', 'mEq/l', 'mu-g/mg', 'P/R', 'w/o',
		'O/L', 'K/R', 'LH/FSH', 'nmol/g', 'g/mL', 'I/II', 'micro-g/day', 'ng/l', 'flexion/extension', 
		'g/l:', 'g/L', 'L/R', 'Min/+', 'RV/LV+S', 'II/III', 'N/L', 'mg/dLa', '/-mu-L', 'left/right',
		'm/m', 'mg/dL:', 'micro-g/l', 'anterior/posterior', 'FV/FM', 'L/S', 'mEq/day', 'cm/s', 'LDL/HDL',
		'L/min', 'weight/volume', 'mg/kg/day', 'mu-g/dl', 'mu-g/kg', 'Mw/Mn', 'SWI/SNF', 'nmol/ml',
		'nr/nt', '%ID/g', 'ml/min', 'mmol/Lxfasting', 'n/%', 'FN/SFN', 'H/L', 'kg/day', 'right/left',
		'mg/dL x fasting', 'micro-g/g', 'mol/mol', 'cells/-mu-L', 'mu-mol/g', 'P/F', 'mol/L', 'ng/dl',
		'N/P', 'mu-g/day', 'mmol/mol', 'mu-g/l', 'kcal/mol', 'micro-g/dL', 'nmol/mL', 'y/n', 'MS/MS',
		'mmol/L', 'ng/mg', 'MEK/extracellular', 'E/Q', 'kg/d', 'DRV/r', 'mmol/L x fasting', 'alpha-/-beta', 
		'mmol/L:', 'NO/cyclic', 'ob/ob', 'g/L:', 'pg/mg', 'mL/min', 'ng/dL', 'mu-g/dL', 'I/D', 'micro-g/L',
		'ER/PR', 'mu-g/g', 'ng/L', 'Y/N', 'mEq/L', 'micro-mol/l', 'wt/wt', 'micro-g/ml', 'PI/r', 'pmol/l',
		'LPV/r', 'H/E', 'g/kg', 'mu-g/L', 'g/d', 'mg/mL', 'mg/g', 'mg/kg', 'micro-mol/L', 'P/S', 'pmol/L',
		'nmol/L', 'g/dl', 'mg/l', 'm/z:', 'Fv/Fm', 'ng/mL',
		'Table&#x000a0','p&#x0003c','');
	if (isset(array_flip($exception)[$ACR]))return;
	
	echo "\t\tNEW ACRONYM\t|".$ACR."|\n";
	/// We add the acronym to the list of acronyms
	/// and we add the annotation associated with the acronym
	foreach ($RESULTS as &$RES)
	{
		/// We ensure that we are adding the annotation found with the word(s) prior to the acromym
		if ($RES[2]==$start && $RES[3]==$n_word)
		{
			$FOUND=false;
			if (isset($ALT_ANNOT[$ACR]))
			foreach ($ALT_ANNOT[$ACR] as $R)
			{
				if ($R[0]==$RES[0] && $R[1]==$RES[1])$FOUND=true;
			}
			if ($FOUND)continue;
			$ALT_ANNOT[$ACR][]=$RES;
			
		}
	}
		
	
}


/// Recursive function that test a word and its combination with the following words
/// Parameters:
/// $words: the original words
/// $clean_words: the cleaned version of the words
/// $start: the position of the word in the original words
/// $n_word: the number of words to consider, starting at $start position
/// $RESULTS: the list of annotations
function testWord(&$words,&$clean_words,&$start,$n_word,&$RESULTS)
{
	global $MAX_ANNOT_W;

	global $ALT_ANNOT;
	
	global $EXCLUDE_ANNOT;
	
	global $DEBUG;

	
	
	// Total number of words
	$len=count($clean_words);

	// If we are at the end of the words, we stop
	if ($n_word+$start>$len)return;
	

	// We compose the substring based on the clean words, starting at the position $start and with $n_word words
	/// merging them with a space to make a substring and then clean it by removing special characters and changing greek letters to alpha, beta, etc.
	$tested_substring=cleanLine(implode(" ",array_slice($clean_words,$start,$n_word)));
	//echo $tested_raw_substring."\t".$tested_substring[0]."\t".$tested_raw_substring[strlen($tested_raw_substring)-1]."\t".strlen($tested_raw_substring)."\n";
	if ($tested_substring=='')return;



	/// Sometimes we will have acronyms defined for some words that are not annotations
	/// Example with oestrogen–progestin therapy (EPT) in  PMC2361783/ PMID 15900297   
	/// EPT is also a gene so without the block below, EPT would be annotated as a gene
	/// Therefore, we test the raw string for brackets, less than 6-2 (for the brackets) = 4 characters
	/// And not already an acronym.
	/// If it matches those rules, them we make it as an acronym with no annotation.
	$tested_raw_substring= implode(" ",array_slice($clean_words,$start,$n_word));

	if ($tested_raw_substring[0]=='(' && $tested_raw_substring[strlen($tested_raw_substring)-1]==')'
	&& strlen($tested_raw_substring)<6
	&& !isset($ALT_ANNOT[substr($tested_raw_substring,1,-1)]))
	{
		echo "\tEXCEPTION ACRONYM\t".substr($tested_raw_substring,1,-1)."\n";

		$ALT_ANNOT[substr($tested_raw_substring,1,-1)]=array('NULL'=>'NULL');
		return;
	}
	/// Then we test that substring to see if it's part of the annotations
	if (validateWord($tested_substring,$start,$n_word,$RESULTS))
	{
		/// If it is, we test if it's an acronym after
		testForAcronym($clean_words,$words,$start,$n_word,$len,$RESULTS);
		
		/// Then we move on by adding the next word to that substring
		if ($n_word+1<$MAX_ANNOT_W)testWord($words,$clean_words,$start,$n_word+1,$RESULTS);
		return;
	}
	/// In many cases the annotation is in plural
	else if (substr($tested_substring,-1)=='s')
	{

		/// So we remove the s and test again
		$alt_word=substr($tested_substring,0,strlen($tested_substring)-1);
		
		/// If it's part of the annotation, we add it to the list
		if (validateWord($alt_word,$start,$n_word,$RESULTS))
		{
			/// And we test if it's an acronym after
			testForAcronym($clean_words,$words,$start,$n_word,$len,$RESULTS);

			/// Then we move on by adding the next word to that substring
			if ($n_word+1<$MAX_ANNOT_W)testWord($words,$clean_words,$start,$n_word+1,$RESULTS);
			return;
		}
	}


	
	

	/// If it's not part of the annotation, we test the next combination
	if ($n_word+1<$MAX_ANNOT_W)testWord($words,$clean_words,$start,$n_word+1,$RESULTS);
}




/// This function is used to split the text into lines
/// To identify the annotations
function splitLines(&$LINEAR,&$OFFSET,$TEXT,$HEADER,$TITLE,$LEV,&$PUBLI_ANNOTS)
{
	if ($TEXT=='')return;
	global $DEBUG;
	//if ($DEBUG)echo "SPLITTING LINES\n";
	/// Convert some of the tags
	convertTags($TEXT);
	
	/// We split the text into lines
	$lines=explode(".",$TEXT);
	
	

	/// This is in the case of a title  like this: "2.1. Characterization of Lipid Profiles of Different Leukemic Cell Lines"
	/// We don't want to split by dot if it's a numbering followed by a title
	if (is_numeric($lines[0]) && strpos($TITLE,'title')!==false)
	{
		$TMP_N=count($lines);
		for ($I=1;$I<$TMP_N;++$I)
		{
		//	echo $I." ";
			$lines[0].='.'.$lines[$I];
			if (is_numeric($lines[$I])){unset($lines[$I]);continue;}
			unset($lines[$I]);
			break;

		}
		
		$lines=array_values($lines);
	}
	
	
	$N_L=count($lines)-1;
	for ($I=1;$I<$N_L;++$I)
	{
		if ($I==0)continue;///Yes it can
		$prev_line=trim($lines[$I-1]);
		if (strtolower(substr($prev_line,-4))=='(fig'
		||strtolower(substr($prev_line,-4))==' fig'
		|| strtolower(substr($prev_line,-6))=='table')
		{

			$lines[$I-1].='.'.$lines[$I];
			unset($lines[$I]);
			$lines=array_values($lines);
			$N_L=count($lines)-1;
			$I=-1;
		}
	}




	for ($I=0;$I<$N_L;++$I)
	{
		
		$NEXT_LINE=trim($lines[$I+1]);
		if (strlen($NEXT_LINE)==0)continue;
	//	echo $I."\t".strlen($NEXT_LINE)."\n";
		if (!(ctype_lower($NEXT_LINE[0])
		|| (is_numeric($NEXT_LINE[0]) && is_numeric(substr($lines[$I],-1)))))continue;
	//echo "=> MERGE\n";
		$lines[$I].='.'.$lines[$I+1];
		unset($lines[$I+1]);
		$lines=array_values($lines);
		$I=-1;
		$N_L=count($lines)-1;
	}


	/// We go through each line
	foreach ($lines as $line)
	{
		if (trim($line)=='')continue;
		/// We split the line into words
		$words = explode(" ",trim($line));

		/// Array with "clean words"
		$clean_words=array();
		
		/// So we test each word
		foreach ($words as $k=>$w)
		{
			/// If it's a reference or a Biorels Tag, we add it to the list of references
			if (substr($w,0,2)=='[[' && substr($w,-2)==']]') 
			{
				$REFS=array();
				preg_match_all('/REF_CIT\|\|([^\]]+)\|\|/',$w,$matches);
				foreach ($matches[1] as $K) $REFS[$K]=true;

				preg_match_all('/\[\[REF_ID\|([^\]]+)\]\]-\[\[REF_ID\|([^\]]+)\]\]/',$line,$matches);
		
				if ($matches[0]!=array())
				{
					
					foreach ($matches[1] as $K=>$M)
					{
						for ($I=$M;$I<=$matches[2][$K];++$I)
						{
							$REFS[$I]=true;
						}
					}
		
				}
				preg_match_all('/\[\[REF_ID\|([^\]]+)\]\]/',$line,$matches);
				if ($matches[0]!=array())
				{
						
					foreach ($matches[1] as $M)
					{
						$REFS[$M]=true;
						
					}
				}
				
				if ($REFS!=array())
				{
					foreach ($REFS as $R=>&$DUMMY)
					{
						$PUBLI_ANNOTS[$OFFSET][]=array('REF','REF',$R,0,0);
					}
				}

				
				$w='';
			}
			// And we remove all HTML tags
			$w=strip_tags(trim($w));
			
			$clean_words[$k]=$w;
		}
		//print_R($clean_words);

		/// Now that we have each word ready, we trigger the annotation test
		/// testWord is a recursive function that will test each word and its combination with the following words
		/// so we trigger it by testing one word at a time
		// and it will call the recursive function to add the next word and then the next word.
		/// Example:
		///   The quick brown fox
		/// The block below will call testWord to test:
		///   The
		///   	Recursive: The quick
		///   		Recursive: The quick brown
		///   			Recursive: The quick brown fox
		///	  Quick
		///   	Recursive: Quick brown
		///   		Recursive: Quick brown fox
		///	  Brown
		///   	Recursive: Brown fox
		///	  Fox
		///   	Recursive: STOP
		///  This way all combination of consecutive words are tested
		/// However when it's the start of the sentence, we also test the first word in lower case
		$n_word=count($clean_words);
		//echo $n_word.' Words'."\t";
		$RESULTS=array();
		//if ($DEBUG)echo "START TEST WORD\t".$n_word."\n";
		for ($I=0;$I<$n_word;$I++)
		{
			
			testWord($words,$clean_words,$I,1,$RESULTS);
			if ($I==0 && strtolower($clean_words[0])!=$clean_words[0])
			{
				$clean_words[0]=strtolower($clean_words[0]);
				testWord($words,$clean_words,$I,1,$RESULTS);
			}
			
		}
		//echo "END\n";
		//if ($DEBUG)echo "END TEST WORD\n";
		/// After testing all the words, we have a list of annotation with their starting position in the line and the number of words
		/// We add that to the final list of annotations but we do an additional check for duplicates
		if ($RESULTS!=array())
		{
			if (!isset($PUBLI_ANNOTS[$OFFSET]))
				$PUBLI_ANNOTS[$OFFSET]=array();
			
			
				foreach ($RESULTS as $R)
				{
					$FOUND=false;
					foreach ($PUBLI_ANNOTS[$OFFSET] as $A)
					{
						
						if ($A[0]!=$R[0])continue;
						if ($A[1]!=$R[1])continue;
						if ($A[2]!=$R[2])continue;
						if($A[3]!=$R[3])continue;
						if($A[4]!=$R[4])continue;
						
							$FOUND=true;
							break;
						
					}
					if (!$FOUND)
					$PUBLI_ANNOTS[$OFFSET][]=$R;
				}
			
			
		}
		
		/// Then we add the line to the linearized text
		$LINEAR[$OFFSET]=array($HEADER,$TITLE,trim($line),$LEV);
		$OFFSET+=strlen($line);
	

	}
	

	//exit;

	//if ($DEBUG) echo "END SPLITTING LINES\n";
}



function getParagraph($xmlElement,&$str)
{
	foreach ($xmlElement->children() as $name => $data) {
		$str.=(string)$data;
	}

}


/// This converts the XML tags into a more readable array
function convert(&$xmlElement,&$RESULTS,$LEVEL)
{
	global $DEBUG;
	//if ($DEBUG) echo "CONVERT\n";
	//if ($LEVEL>4)return;
	
	/// We go through each child
	foreach ($xmlElement->children() as $name => $data) {
		
		// Create an entry for each child
		$ENTRY=array('child'=>array(),'l_value_xml'=>(string)$data);
		/// We add the attributes
		foreach ($data->attributes() as $attr=>$attrV)
		{
			$ENTRY[$attr]=(string)$attrV;
		}
		/// We add the xlink attributes as regular attributes
		foreach ($data->attributes('http://www.w3.org/1999/xlink') as $attr=>$attrV)
			{
			$ENTRY[$attr]=(string)$attrV;
				
			}
		$NAMES=array();
		$P_B=0;
		/// We go through each child of the child
		foreach ($data->children() as $name_ch=>$data_ch)
		{  
			//echo "\t".$LEVEL."\t".$name."\t".(isset($ENTRY['id'])?$ENTRY['id']:'')."\t".$name_ch."\n";
			
			/// No data, we just add the name and value of the child
			if ($data_ch->count()==0 )
			{
				
				if (!isset($ENTRY['child'][$name_ch]))
				{
					if ((string)$data_ch!='')	$ENTRY['child'][$name_ch][0]['l_value_xml']=(string)$data_ch;
				} 
				else 
				{
					/// Sometimes there will be multiple children with the same name
					++$P_B;
					$name_ch.=':::'.$P_B; 
					if ((string)$data_ch!='')$ENTRY['child'][$name_ch][0]['l_value_xml']=(string)$data_ch;
				}
				foreach ($data_ch->attributes() as $attr=>$attrV)
				{
					$ENTRY['child'][$name_ch][0][$attr]=(string)$attrV;
				}
				foreach ($data_ch->attributes('http://www.w3.org/1999/xlink') as $attr=>$attrV)
					{
					//	echo $attr."\t".(string)$attrV."\n";
					$ENTRY['child'][$name_ch][0][$attr]=(string)$attrV;
						
					}
				
			}
			
			else 
			{
				/// Otherwise we call a recusive function to process the child
				$KC=0;
				if (!isset($NAMES[$name_ch]))$NAMES[$name_ch]=0;
				else {$NAMES[$name_ch]++;$KC=$NAMES[$name_ch];}
				
				$ENTRY['child'][$name_ch][$KC]=array();
				
				convert($data_ch,$ENTRY['child'][$name_ch][$KC],$LEVEL+1);
				/// We add the attributes of the child
				foreach ($data_ch->attributes() as $attr=>$attrV)
				{
					$ENTRY['child'][$name_ch][$KC][$attr]=(string)$attrV;
				}
				foreach ($data_ch->attributes('http://www.w3.org/1999/xlink') as $attr=>$attrV)
					{
						$ENTRY['child'][$name_ch][$KC][$attr]=(string)$attrV;
					}
			}
		}

		// No need to keep empty entries
		if ($ENTRY['child']==array())unset($ENTRY['child']);
		if ($ENTRY['l_value_xml']=='')unset($ENTRY['l_value_xml']);
		if ($ENTRY==array())return;
	
		  $RESULTS[$name][]=$ENTRY;
	
		//print_R($ENTRY);
	}
	//print_R($RESULTS);
	//if ($DEBUG) echo "END CONVERT\n";
}



/// This function will convert the simplified array into a linearized array to push to database
function linearize(&$INFO,$LEVEL,&$OFFSET,&$LINEAR,&$ANNOTS,&$FILES)
{
	global $DEBUG;
	if ($DEBUG) echo "LINEARIZE\n";
	$SECTION_MAP=array(
		'Results'=>"RESULTS",
		'Discussion'=>'DISCUSSION',
		'Conclusions'=>'CONCLUSION',
		'Background'=>'BACKGROUND',
	'Materials and Methods'=>'METHODS');
	
	foreach ($INFO as $S=> &$SEC_L1)
	{
		/// We don't want to process the TEXT_ID which is internally used to define paragraphs
		if ($S=='TEXT_ID')continue;
		if ($DEBUG)echo "#### LINEARIZE SECTION ".$LEVEL."\t".$S."\n";
		if ($SEC_L1==array())
		{
			if ($DEBUG)echo "EMPTY SECTION\n";
			continue;
		}
		
		/// We first cover the section type
		if (isset($SEC_L1['sec-type']))
		{
			$SUB_INFO=$SEC_L1['sec-type'];
			
		
			if (!is_array($SUB_INFO))
			{
				//echo "SUB INFO ".$SUB_INFO."\n";
				$LINEAR[$OFFSET]=array('SECTION',$SUB_INFO,$SUB_INFO,NULL);
				$OFFSET+=strlen($SUB_INFO);
			}
			else{
				if (isset($SUB_INFO['title']))
				{
					//echo "SUB INFO ".$SUB_INFO['title']."\n";
					++$INFO['TEXT_ID'];;
					splitLines($LINEAR,$OFFSET,$SUB,'title_'.($LEVEL+1),$INFO['TEXT_ID'],$ANNOTS);
				}
				if (isset($SUB_INFO['sec']))
				{
					$SUB=&$SUB_INFO['sec'];
					$SUB['TEXT_ID']=$INFO['TEXT_ID'];
					//echo "SUB INFO ".$SUB['TEXT_ID']."\n";
					linearize($SUB,$LEVEL+2,$OFFSET,$LINEAR,$ANNOTS,$FILES);
					$INFO['TEXT_ID']=$SUB['TEXT_ID'];
				}				
			}

		}
		/// Then the title
		$SECTION_NAME='TEXT';
		if (isset($SEC_L1['title']))
		{
			$INFO['TEXT_ID']++;
			if (isset($SECTION_MAP[$SEC_L1['title']]))$SECTION_NAME=$SECTION_MAP[$SEC_L1['title']];
			splitLines($LINEAR,$OFFSET,$SEC_L1['title'],'SECTION','title_'.($LEVEL+1),$INFO['TEXT_ID'],$ANNOTS);
		}
		

		//print_R(array_keys($SEC_L1));
		if ($DEBUG)echo "NUMBER OF LINEAR: ".count($LINEAR)."\n";


		
		/// Then the rest:
		foreach ($SEC_L1 as $TYPE=>&$SUB_INFO)
		{
			//if ($DEBUG)
		//	echo "\t".$S."\n";
			
			/// Already processed outside the loop to ensure it's taken first
			if ($TYPE=='title')continue;
			else if ($TYPE=='sec-type')continue;

			if ($TYPE=='text')
			{
				
				
				foreach ($SUB_INFO as $T)
				{
					++$INFO['TEXT_ID'];
					splitLines($LINEAR,$OFFSET,$T,$SECTION_NAME,'text',$INFO['TEXT_ID'],$ANNOTS);
					
				}
			}
			else if ($TYPE=='fig')
			{
				foreach ($SUB_INFO as $FIG_INFO)
				{
					//print_R($FIG_INFO);
					
					if (!isset($FIG_INFO['href']) && !isset($FIG_INFO['id']))continue;
					if (isset($FIG_INFO['href']) && !isset($FIG_INFO['id']))$FIG_INFO['id']=$FIG_INFO['href'];
					if (isset($FIG_INFO['id']) && !isset($FIG_INFO['href']))$FIG_INFO['href']=$FIG_INFO['id'];
					$FILES[$FIG_INFO['id']]=$FIG_INFO['href'];
					if (isset($FIG_INFO['title']))
					{
						$title='';
						if (isset($FIG_INFO['label']))$title=$FIG_INFO['label'].': ';
						$title.=$FIG_INFO['title'];
						++$INFO['TEXT_ID'];
						splitLines($LINEAR,$OFFSET,$title,'FIGURE','fig_title',$INFO['TEXT_ID'],$ANNOTS);
						$OFFSET+=strlen($title);
					}
					++$INFO['TEXT_ID'];
					$LINEAR[$OFFSET]=array('FIGURE','fig_info',$FIG_INFO['id'],$INFO['TEXT_ID']);
					$OFFSET+=strlen($FIG_INFO['id']);
					
					if (isset($FIG_INFO['text']))
					{

						++$INFO['TEXT_ID'];
						splitLines($LINEAR,$OFFSET,$FIG_INFO['text'],'FIGURE','fig_text',$INFO['TEXT_ID'],$ANNOTS);
					}
				}

				//print_R($SUB_INFO);
			}
			else if ($TYPE=='sec')
			{
				$SUB_INFO['TEXT_ID']=$INFO['TEXT_ID'];
				linearize($SUB_INFO,$LEVEL+1,$OFFSET,$LINEAR,$ANNOTS,$FILES);
				$INFO['TEXT_ID']=$SUB_INFO['TEXT_ID'];
			}
			
			else if ($TYPE=='table')
			{
				// print_R($SUB_INFO);
				// exit;
				foreach ($SUB_INFO as $TBL_INFO)
				{
					$title=$TBL_INFO['label'];
					$LINEAR[$OFFSET]=array('TABLE','table_label',$title,NULL);
					$OFFSET+=strlen($title);

					$LINEAR[$OFFSET]=array('TABLE','table_id',$TBL_INFO['id'],NULL);
					$OFFSET+=strlen($TBL_INFO['id']);

					foreach ($TBL_INFO['caption'] as $capt)
					{
						$INFO['TEXT_ID']++;
						splitLines($LINEAR,$OFFSET,$capt,'TABLE','table_caption',$INFO['TEXT_ID'],$ANNOTS);
					}
					++$INFO['TEXT_ID'];
					if (isset($TBL_INFO['table']))
					{
					$LINEAR[$OFFSET]=array('TABLE','table_text',$TBL_INFO['table'],$INFO['TEXT_ID']);
					$OFFSET+=strlen($TBL_INFO['table']);
					}
					else if (isset($TBL_INFO['xref']))
					{
						$FILES[$TBL_INFO['id']]=$FIG_INFO['xref'];
						$LINEAR[$OFFSET]=array('FIGURE','fig_info',$TBL_INFO['id'],$INFO['TEXT_ID']);
						$OFFSET+=strlen($TBL_INFO['id']);
					}
					
					
					foreach ($TBL_INFO['foot'] as $foot)
					{
						++$INFO['TEXT_ID'];
						splitLines($LINEAR,$OFFSET,$foot,'TABLE','table_foot',$INFO['TEXT_ID'],$ANNOTS);
					}
					
				}
				
			}
			else if ($TYPE=='id')continue;
			else if ($TYPE=='suppl')
			{
				
				foreach ($SUB_INFO as $SUPPL_INFO)
				{
					//print_R($SUPPL_INFO);
					$title=$SUPPL_INFO['label'];
					$LINEAR[$OFFSET]=array('SUPPL','title_'.($LEVEL+2),$title,NULL);
					$OFFSET+=strlen($title);
					++$INFO['TEXT_ID'];
					splitLines($LINEAR,$OFFSET,$SUPPL_INFO['media-caption'],'SUPPL','suppl_caption',$INFO['TEXT_ID'],$ANNOTS);
					++$INFO['TEXT_ID'];
					splitLines($LINEAR,$OFFSET,implode(".",$SUPPL_INFO['caption']),'SUPPL','suppl_caption',$INFO['TEXT_ID'],$ANNOTS);
					$FILES[$SUPPL_INFO['id']]=$SUPPL_INFO['href'];
				}
				
			}
			else
			{
				print_R($INFO);
				throw new Exception( "UNRECOGNIZED TYPE:".$TYPE."\n");
			}
		//	if (isset($))
		}
		if ($DEBUG)echo "NUMBER OF LINEAR: ".count($LINEAR)."\n";
	}
	if ($DEBUG) echo "END LINEARIZE\n";
	// print_r($LINEAR);
	// exit;
}


/// Recursive function that process the sections
function processSection(&$SECTION,&$RECORD,&$BLOCKS)
{
	global $DEBUG;
	//if ($DEBUG)echo "PROCESS SECTION\n";
	//print_R(array_keys($SECTION));
	//exit;
	if ($SECTION==null)return;
	$RECORD['sec']=array();
	/// Title and section type needs to come first
	if (isset($SECTION['title']))$RECORD['title']=$BLOCKS[$SECTION['title'][0]['l_value_xml']];
	if (isset($SECTION['sec-type']))$RECORD['sec-type']=(string)$SECTION['sec-type'];
	
	foreach ($SECTION as $TYPE=>&$VALUE)
	{
		$tab=explode(":::",$TYPE);
		
		
		$TYPE_V=$tab[0];
		switch ($TYPE_V)
		{
			case 'sec-type':
			case 'title':#Previously processed;
				break;
			case 'p':
				//print_R($VALUE);
				foreach ($VALUE as $V)
				$RECORD['text'][]=$BLOCKS[$V['l_value_xml']];break;
			case 'statement':
			case 'sec':
				//echo "IN\n";
			//	print_R($RECORD);
			
				foreach ($VALUE as $SUB_SEC=>&$SUB_VALUE)
				{
					$N_REC=count($RECORD['sec']);
					$RECORD['sec'][$N_REC]=array();
					if (isset($SUB_VALUE['child']))processSection($SUB_VALUE['child'],$RECORD['sec'][$N_REC],$BLOCKS);
					else processSection($SUB_VALUE,$RECORD['sec'][$N_REC],$BLOCKS);
				}
				//$RECORD['sec'][$VALUE]=array();
				//print_R($VALUE);exit;
				//processSection($SECTION[$TYPE],$VALUE,$BLOCKS);
				break;
			case 'fn':
				$RECORD['text'][]=$VALUE[0]['fn-type'];
				$RECORD['text'][]=$BLOCKS[$VALUE[0]['p'][0]['l_value_xml']];break;
				break;
			 case 'disp-level':
				/// Nothing to do
				break;
			case 'fn-group':
				foreach ($VALUE as $SUB_REC=>&$SUB_VALUE)
				{
					$N_REC=count($RECORD['sec']);
					$RECORD['sec'][$N_REC]=array();
					if (isset($SUB_VALUE['child']))processSection($SUB_VALUE['child'],$RECORD['sec'][$N_REC],$BLOCKS);
					else processSection($SUB_VALUE,$RECORD['sec'][$N_REC],$BLOCKS);
				}
				break;
			case 'disp-formula':
			case 'fig':

				
				foreach ($VALUE as $K=>$FIG_INFO)
				{
					
					$FIG=array();
					if (!isset($FIG_INFO['child']))
					{
					
					//print_R($FIG_INFO);
						if (isset($FIG_INFO['object-id']))
						{
							$FIG['object-id']=$FIG_INFO['object-id'][0]['l_value_xml'];
							$FIG['pub-id-type']=$FIG_INFO['object-id'][0]['pub-id-type'];
						}
						else if (isset($FIG_INFO['inline-graphic']))
						{
							$FIG_INFO['id']=$FIG_INFO['inline-graphic'][0]['href'];
							$FIG_INFO['href']=$FIG_INFO['inline-graphic'][0]['href'];
						}
						if (isset($FIG_INFO['label']))$FIG['label']=$FIG_INFO['label'][0]['l_value_xml'];
						if (isset($FIG_INFO['caption'][0]['child']['title'][0]['l_value_xml']))
						$FIG['title']=$BLOCKS[$FIG_INFO['caption'][0]['child']['title'][0]['l_value_xml']];
						if (isset($FIG_INFO['caption'][0]['child']['p'][0]['l_value_xml']))$FIG['text']=$BLOCKS[$FIG_INFO['caption'][0]['child']['p'][0]['l_value_xml']];
						
						if (isset($FIG_INFO['graphic']))$FIG['href']=$FIG_INFO['graphic'][0]['href'];

						if (isset($FIG_INFO['id']))$FIG['id']=$FIG_INFO['id'];
						else $FIG['id']=$FIG['href'];
					}
					else 
					{
						
						//print_R($FIG_INFO);
						
						
						if (isset($FIG_INFO['child']['href']))$FIG['href']=$FIG_INFO['child']['href'];
						else if (isset($FIG_INFO['graphic']))$FIG['href']=$FIG_INFO['graphic'][0]['href'];
						else if (isset($FIG_INFO['child']['graphic']))$FIG['href']=$FIG_INFO['child']['graphic'][0]['href'];
						
						if (isset($FIG_INFO['child']['object-id']))$FIG['object-id']=$FIG_INFO['child']['object-id'];
						
						if (isset($FIG_INFO['child']['pub-id-type']))$FIG['pub-id-type']=$FIG_INFO['child']['pub-id-type'];
						
						if (isset($FIG_INFO['child']['label']))
						{
							if (isset($FIG_INFO['child']['label'][0]['l_value_xml']))
							$FIG['label']=$FIG_INFO['child']['label'][0]['l_value_xml'];
						/// Poor handling of PMC2229542
						else if (isset($FIG_INFO['child']['label'][0]['sc'][0]['l_value_xml']))
						$FIG['label']=$FIG_INFO['child']['label'][0]['sc'][0]['l_value_xml'];
						}
						if (isset($FIG_INFO['child']['caption'][0]['title']))
						$FIG['title']=$BLOCKS[$FIG_INFO['child']['caption'][0]['title'][0]['l_value_xml']];
						if (isset($FIG_INFO['child']['caption'][0]['p']))$FIG['text']=$BLOCKS[$FIG_INFO['child']['caption'][0]['p'][0]['l_value_xml']];

						

					//print_R($VALUE);
					}
					//print_R($FIG);exit;
					$RECORD['fig'][]=$FIG;
				}
				//print_R($RECORD['fig']);
				//exit;
			//case 'sec-type':$RECORD['section-type']=(string)$VALUE;break;
			break;
			case 'supplementary-material':
				//echo "######\n";
				//print_R($VALUE);
				foreach ($VALUE as $SUPPL=>&$SUPPL_VALUE)
				{
					//PMC2959549
					if (!isset($SUPPL_VALUE['id']))continue;
					//print_R($SUPPL_VALUE);
					$TITLE='';
					
					if(isset($SUPPL_VALUE['child']['label'][0]['l_value_xml']))$TITLE=$SUPPL_VALUE['child']['label'][0]['l_value_xml'];
					else if (isset($SUPPL_VALUE['child']['caption']))
					{
						if (isset($SUPPL_VALUE['child']['caption'][0]['title']))
						$TITLE=$BLOCKS[$SUPPL_VALUE['child']['caption'][0]['title'][0]['l_value_xml']];
					else $TITLE='Supplementary';
					}
					else if (isset($SUPPL_VALUE['caption']))
					{
						$TITLE=$SUPPL_VALUE['caption'][0]['child']['title'][0]['l_value_xml'];
					}
					else if (isset($SUPPL_VALUE['href']))
					{
						$TITLE=$SUPPL_VALUE['href'];
						if (!isset($SUPPL_VALUE['id']))
						$SUPPL_VALUE['id']=$SUPPL_VALUE['href'];
					}
				
					else $TITLE='Supplementary';
					
					
					
					//print_R($SUPPL_VALUE);
				$SUPPL=array(
					'id'=>$SUPPL_VALUE['id'],
					'content-type'=>'',
					'label'=>$TITLE,
					'caption'=>array(),
					'media-caption'=>'',
					'href'=>isset($SUPPL_VALUE['child'])?$SUPPL_VALUE['child']['media'][0]['href']:
					$SUPPL_VALUE['media'][0]['href']
				);
				if (isset($SUPPL_VALUE['content-type']))$SUPPL['content-type']=$SUPPL_VALUE['content-type'];
				else if (isset($SUPPL_VALUE['child']['media'][0]['mimetype']))
				{
					$SUPPL['content-type'].=$SUPPL_VALUE['child']['media'][0]['mimetype'].'/'.$SUPPL_VALUE['child']['media'][0]['mime-subtype'];
				}
				if (isset($SUPPL_VALUE['child']['media'][0]['caption']))
				{
					$SUPPL['media-caption']=$BLOCKS[$SUPPL_VALUE['child']['media'][0]['caption'][0]['child']['p'][0]['l_value_xml']];
				}
				else if (isset($SUPPL_VALUE['media'][0]['child']['caption']))
				{
					$SUPPL['media-caption']=$BLOCKS[$SUPPL_VALUE['media'][0]['child']['caption'][0]['p'][0]['l_value_xml']];
				}
					// print_R($SUPPL_VALUE['child']['caption'][0]['p']);
					// echo "=>\n";
					if (isset($SUPPL_VALUE['child']['caption'][0]['p']))
					foreach ($SUPPL_VALUE['child']['caption'][0]['p'] as $P)
					{
					$SUPPL['caption'][]=$BLOCKS[$P['l_value_xml']];
					}
					$RECORD['suppl'][]=$SUPPL;
				// print_R($SUPPL['caption']);
				// exit;
				}
				//exit;
				break;
				case 'table-wrap-group':
			case 'table-wrap':
				foreach ($VALUE as $TBL_VALUE)
				{
					
					//print_R($TBL_VALUE);
					$TABLE=array('id'=>$TBL_VALUE['id'],'position'=>$TBL_VALUE['position'],
					'label'=>isset($TBL_VALUE['child']['label'])?$TBL_VALUE['child']['label'][0]['l_value_xml']:
							(isset($TBL_VALUE['label'])?$TBL_VALUE['label'][0]['l_value_xml']:''),
					'caption'=>array(),
					
					'foot'=>array());


					if (isset($TBL_VALUE['child']['table'])) $TABLE['table']=$BLOCKS[$TBL_VALUE['child']['table'][0]['l_value_xml']];
					else if (isset($TBL_VALUE['table'][0]['l_value_xml']))$TABLE['table']=$BLOCKS[$TBL_VALUE['table'][0]['l_value_xml']];
					else if (isset($TBL_VALUE['graphic'][0]['href'])) $TABLE['href']=$TBL_VALUE['graphic'][0]['href'];
					else if (isset($TBL_VALUE['child']['table'][0]['l_value_xml']))$TABLE['table']=$BLOCKS[$TBL_VALUE['child']['table'][0]['l_value_xml']];
					else if (isset($TBL_VALUE['child']['graphic'][0]['href'])) $TABLE['href']=$TBL_VALUE['child']['graphic'][0]['href'];
					else if (isset($TBL_VALUE['table-wrap'][0]['child']['table']))
					$TABLE['table']=$BLOCKS[$TBL_VALUE['table-wrap'][0]['child']['table'][0]['l_value_xml']];
					else if (isset($TBL_VALUE['alternatives']))
					{
						$TABLE['href']=$TBL_VALUE['alternatives'][0]['child']['graphic'][0]['href'];
						$TABLE['table']=$BLOCKS[$TBL_VALUE['alternatives'][0]['child']['table'][0]['l_value_xml']];
					}
					else if (isset($TBL_VALUE['child']['alternatives']))
					{
						//print_R($TBL_VALUE['child']['alternatives']);
						$TABLE['href']=$TBL_VALUE['child']['alternatives'][0]['graphic'][0]['href'];
						$TABLE['table']=$BLOCKS[$TBL_VALUE['child']['alternatives'][0]['table'][0]['l_value_xml']];
					}
					else
					{
						print_R($TBL_VALUE);
						throw new Exception("Unable to understand table-wrap");
					}
					
					if (isset($TBL_VALUE['child']))
					{
						//print_R($TBL_VALUE['child']);
						if (isset($TBL_VALUE['child']['caption'][0]['p']))
						foreach ($TBL_VALUE['child']['caption'][0]['p'] as $P)
						{
							$TABLE['caption'][]=$BLOCKS[$P['l_value_xml']];
						}
						else if (isset($TBL_VALUE['child']['caption'][0]['title']))
						{
							foreach ($TBL_VALUE['child']['caption'][0]['title'] as $P)
						{
							$TABLE['caption'][]=$BLOCKS[$P['l_value_xml']];
						}
						}
						
						if (isset($TBL_VALUE['child']['table-wrap-foot']))
						{
							if (isset($TBL_VALUE['child']['table-wrap-foot'][0]['p']))
							foreach ($TBL_VALUE['child']['table-wrap-foot'][0]['p'] as $P)
							{
								$TABLE['foot'][]=$BLOCKS[$P['l_value_xml']];
							}
							else if (isset($TBL_VALUE['child']['table-wrap-foot'][0]['fn'][0]['child']['p']))
							{
								foreach ($TBL_VALUE['child']['table-wrap-foot'][0]['fn'][0]['child']['p'] as $P)
								{
									$TABLE['foot'][]=$BLOCKS[$P['l_value_xml']];
								}
							}
						}
						
					}
					else 
					{
						$CAPT=NULL;
						if (isset($TBL_VALUE['caption'][0]['p']))$CAPT=$TBL_VALUE['caption'][0]['p'];
						else if (isset($TBL_VALUE['caption'][0]['child']['p']))$CAPT=$TBL_VALUE['caption'][0]['child']['p'];
						
						if ($CAPT!=NULL)
						foreach ($CAPT as $P)
						{
							$TABLE['caption'][]=$BLOCKS[$P['l_value_xml']];
						}
						
						$WRAP=NULL;
						if (isset($TBL_VALUE['table-wrap-foot'][0]['p']))$WRAP=$TBL_VALUE['table-wrap-foot'][0]['p'];
						else if (isset($TBL_VALUE['table-wrap-foot'][0]['child']['p']))$WRAP=$TBL_VALUE['table-wrap-foot'][0]['child']['p'];
						if ($WRAP!=null)
						foreach ($WRAP as $P)
						{
							$TABLE['foot'][]=$BLOCKS[$P['l_value_xml']];
						}
					}
					$RECORD['table'][]=$TABLE;
				}
				break;
				case 'graphic':
					
					foreach ($VALUE as $GR)
					$RECORD['text'][]='[[GRAPH|'.$GR['href'].']]';
					break;
			case 'id':
				
				break;
			case 'label':
				// Need to work on it oa_package/46/e1/PMC2785872.tar.gz      Behav Res Ther. 2005 Jun; 43(6):691-701 PMC2785872      2023-11-04 14:25:27     15890163        CC BY   28
				break;
			case 'list':
				# Need to work on this 8a/16/PMC1323323
				break;
			case 'ref-list':
				# Need to work on this: PMC523848 / 15526055
				break;
			case 'disp-quote':
				foreach ($VALUE as $QUOTE)
				{

					if (isset($QUOTE['child']['p'][0]['l_value_xml']))
						$RECORD['text'][]=$BLOCKS[$QUOTE['child']['p'][0]['l_value_xml']];
					else if (isset($QUOTE['p'][0]['l_value_xml']))
						$RECORD['text'][]=$BLOCKS[$QUOTE['p'][0]['l_value_xml']];
				}
				break;
			case 'boxed-text':
				$BOX_VALUE=null;
				foreach ($VALUE as &$TMP_BOX)
				{

					//print_R($TMP_BOX);
					$N_REC=count($RECORD['sec']);
					$RECORD['sec'][$N_REC]=array();
					$RECORD['sec'][$N_REC]['sec-type']='boxed-text';
					if (isset($TMP_BOX['child']))
					{
						$BOX_VALUE=&$TMP_BOX['child'];
						if (isset($BOX_VALUE['caption']))
						$RECORD['sec'][$N_REC]['title']=$BLOCKS[$BOX_VALUE['caption'][0]['title'][0]['l_value_xml']];
					
					
					
					
						
					}
					else
					{
						if (isset($TMP_BOX['caption']))
						$RECORD['sec'][$N_REC]['title']=$BLOCKS[$TMP_BOX['caption'][0]['child']['title'][0]['l_value_xml']];
						$BOX_VALUE=&$TMP_BOX;
					}	

					foreach ($BOX_VALUE as $KEY=>$NAME)
					{
						
						$tab=explode(":::",$KEY);
		
		
						$TYPE_V=$tab[0];
						if ($TYPE_V=='p')
						$RECORD['sec'][$N_REC]['text'][]=$BLOCKS[$NAME[0]['l_value_xml']];
						else if ($TYPE_V=='caption')continue;
						else if ($TYPE_V=='id')$RECORD['sec'][$N_REC]['id']=$NAME;
						else if ($TYPE_V=='position')continue;
						else if ($TYPE_V=='fig')
						{
							foreach ($NAME as $FIG_INFO)
							{
								//print_R($FIG_INFO);
								$FIG=array();
								if (!isset($FIG_INFO['child']))
								{
								
								
								$FIG['id']=$FIG_INFO['id'];
								//$FIG['href']=$FIG_INFO['href'];
								if (isset($FIG_INFO['object-id']))
								{
									$FIG['object-id']=$FIG_INFO['object-id'][0]['l_value_xml'];
									$FIG['pub-id-type']=$FIG_INFO['object-id'][0]['pub-id-type'];
								}
								
									if (isset($FIG_INFO['label']))$FIG['label']=$FIG_INFO['label'][0]['l_value_xml'];
									if (isset($FIG_INFO['caption'][0]['child']['title'][0]['l_value_xml']))
									$FIG['title']=$BLOCKS[$FIG_INFO['caption'][0]['child']['title'][0]['l_value_xml']];
									if (isset($FIG_INFO['caption'][0]['child']['p'][0]['l_value_xml']))$FIG['text']=$BLOCKS[$FIG_INFO['caption'][0]['child']['p'][0]['l_value_xml']];
									
									if (isset($FIG_INFO['graphic']))$FIG['href']=$FIG_INFO['graphic'][0]['href'];
								}
								else 
								{
									
									//print_R($FIG_INFO);
									$FIG['id']=$FIG_INFO['id'];
									
									if (isset($FIG_INFO['child']['href']))$FIG['href']=$FIG_INFO['child']['href'];
									else if (isset($FIG_INFO['graphic']))$FIG['href']=$FIG_INFO['graphic'][0]['href'];
									else if (isset($FIG_INFO['child']['graphic']))$FIG['href']=$FIG_INFO['child']['graphic'][0]['href'];
									
									if (isset($FIG_INFO['child']['object-id']))$FIG['object-id']=$FIG_INFO['child']['object-id'];
									
									if (isset($FIG_INFO['child']['pub-id-type']))$FIG['pub-id-type']=$FIG_INFO['child']['pub-id-type'];
									$FIG['label']=$FIG_INFO['child']['label'][0]['l_value_xml'];
									if (isset($FIG_INFO['child']['caption'][0]['title']))
									$FIG['title']=$BLOCKS[$FIG_INFO['child']['caption'][0]['title'][0]['l_value_xml']];
									if (isset($FIG_INFO['child']['caption'][0]['p']))$FIG['text']=$BLOCKS[$FIG_INFO['child']['caption'][0]['p'][0]['l_value_xml']];
								//print_R($VALUE);
								}
								//print_R($FIG);exit;
								$RECORD['fig'][]=$FIG;
							}
						}
						else if ($TYPE_V=='sec')
						{
							foreach ($NAME as $SUB_SEC=>&$SUB_VALUE)
							{
								$N_REC=count($RECORD['sec']);
								$RECORD['sec'][$N_REC]=array();
								if (isset($SUB_VALUE['child']))processSection($SUB_VALUE['child'],$RECORD['sec'][$N_REC],$BLOCKS);
								else processSection($SUB_VALUE,$RECORD['sec'][$N_REC],$BLOCKS);
							}
						}
						else if ($TYPE_V=='orientation')continue;
						else if ($TYPE_V=='content-type')continue;
						else if ($TYPE_V=='block-alternatives')
						{
							foreach ($NAME[0] as $TYPE2=>&$ALT)
							{
								if ($TYPE2=='fig')
								{
									foreach ($ALT as &$FIG_INFO)
									{
										$FIG=array();
										if (!isset($FIG_INFO['child']))
										{
										
										
										$FIG['id']=$FIG_INFO['id'];
										//$FIG['href']=$FIG_INFO['href'];
										if (isset($FIG_INFO['object-id']))
										{
											$FIG['object-id']=$FIG_INFO['object-id'][0]['l_value_xml'];
											$FIG['pub-id-type']=$FIG_INFO['object-id'][0]['pub-id-type'];
										}
										
											if (isset($FIG_INFO['label']))$FIG['label']=$FIG_INFO['label'][0]['l_value_xml'];
											if (isset($FIG_INFO['caption'][0]['child']['title'][0]['l_value_xml']))
											$FIG['title']=$BLOCKS[$FIG_INFO['caption'][0]['child']['title'][0]['l_value_xml']];
											if (isset($FIG_INFO['caption'][0]['child']['p'][0]['l_value_xml']))$FIG['text']=$BLOCKS[$FIG_INFO['caption'][0]['child']['p'][0]['l_value_xml']];
											
											if (isset($FIG_INFO['graphic']))$FIG['href']=$FIG_INFO['graphic'][0]['href'];
										}
										else 
										{
											
											//print_R($FIG_INFO);
											$FIG['id']=$FIG_INFO['id'];
											
											if (isset($FIG_INFO['child']['href']))$FIG['href']=$FIG_INFO['child']['href'];
											else if (isset($FIG_INFO['graphic']))$FIG['href']=$FIG_INFO['graphic'][0]['href'];
											else if (isset($FIG_INFO['child']['graphic']))$FIG['href']=$FIG_INFO['child']['graphic'][0]['href'];
											
											if (isset($FIG_INFO['child']['object-id']))$FIG['object-id']=$FIG_INFO['child']['object-id'];
											
											if (isset($FIG_INFO['child']['pub-id-type']))$FIG['pub-id-type']=$FIG_INFO['child']['pub-id-type'];
											$FIG['label']=$FIG_INFO['child']['label'][0]['l_value_xml'];
											if (isset($FIG_INFO['child']['caption'][0]['title']))
											$FIG['title']=$BLOCKS[$FIG_INFO['child']['caption'][0]['title'][0]['l_value_xml']];
											if (isset($FIG_INFO['child']['caption'][0]['p']))$FIG['text']=$BLOCKS[$FIG_INFO['child']['caption'][0]['p'][0]['l_value_xml']];
										//print_R($VALUE);
										}
										//print_R($FIG);exit;
										$RECORD['fig'][]=$FIG;
									}
								}
							}
						}
						else if ($TYPE_V=='label')$RECORD['text'][]=$NAME[0]['l_value_xml'];
						else if ($TYPE_V=='fn-group')
						{
							if (isset($NAME[0]['child']['fn'][0]['child']['p'][0]))
							$RECORD['text'][]=$BLOCKS[$NAME[0]['child']['fn'][0]['child']['p'][0]['l_value_xml']];
							else if (isset($NAME[0]['child']['fn'][0]['p'][0]))
							$RECORD['text'][]=$BLOCKS[$NAME[0]['child']['fn'][0]['p'][0]['l_value_xml']];
							else if (isset($NAME[0]['fn'][0]['child']['p'][0]))
							$RECORD['text'][]=$BLOCKS[$NAME[0]['fn'][0]['child']['p'][0]['l_value_xml']];
							else
							{
								print_R($NAME);echo $TYPE_V."\n";
								throw new Exception( "Unrecognized fn-group subsection ".$TYPE_V."\n");
							} 
						}
						else if ($TYPE_V=='list')
						{
							foreach ($NAME as &$LIST)
							{
								if (!isset($LIST['child']['list-item']))continue;
								$ITEMS=&$LIST['child']['list-item'];
								foreach ($ITEMS as $ITEM)
								{
									$RECORD['text'][]=$BLOCKS[$ITEM['p'][0]['l_value_xml']];
								}
							}
						}
						else
						{
							
							print_r($NAME);echo $TYPE_V."\n";
							throw new Exception( "Unrecognized boxed-text subsection ".$TYPE_V."\n");
							
						}
					}

				}
				break;
				case 'verse-group':
					foreach ($VALUE as &$VERSE)
					{
						$TXT='';
						foreach ($VERSE['verse-line'] as $VS)
						{
						
							if (isset($VS['l_value_xml']))
							$TXT.=$VS['l_value_xml']."\n";
							else if (isset($VS['child']['italic'][0]))
							$TXT.='<i>'.$BLOCKS[$VS['child']['italic'][0]['l_value_xml']].'</i>'."\n";
							
						}
						$RECORD['text'][]=$TXT;
					}
					break;
				case 'def-list':
					foreach ($VALUE as &$DEFS)
					foreach ($DEFS['child']['def-item'] as &$DEF)
					{
						$RECORD['text'][]='<term>'.$DEF['term'][0]['l_value_xml'].'</term>';
						$RECORD['text'][]=$BLOCKS[$DEF['def'][0]['child']['p'][0]['l_value_xml']];
					}
					break;
			case 'sec-meta':
				#Need to work on this PMC4381052/25769528
				break;	
			default:
		
				print_R($SECTION);
				print_R($VALUE);
				echo "\n|".$TYPE_V."|\n\n";
				throw new Exception( "UNRECOGNIZED SECTION TYPE:".$TYPE_V."\n");

		}

		
	}
	if ($RECORD['sec']==array()) unset($RECORD['sec']);

	// if ($DEBUG) echo "END PROCESS SECTION\n";
	// echo "WRAPPING UP\n";
	// 	print_R($RECORD);
	// 	echo "###### END ".$RECORD['title']."\n";

	
	
	
}

function getBody(&$RESULTS,&$INFO,&$BLOCKS)
{
	global $DEBUG;
	//if ($DEBUG) echo "GET BODY\n";
	$BODY=&$RESULTS['body'];
	
	if (!isset($INFO['TEXT']))$INFO['TEXT']=array();
	if ($BODY==array())return;
	foreach ($BODY[0]['child'] as $child_name=>&$list)
	{
		$tb=explode(":::",$child_name);
		if ($tb[0]=='sec')
		foreach ($list as  $K=>&$B)
		{
			//print_R($B);
			$N=count($INFO['TEXT']);
			$INFO['TEXT'][$N]=array();
			processSection($B,$INFO['TEXT'][$N],$BLOCKS);
		}
		else if ($tb[0]=='p')
		{
			foreach ($list as $V)
				$RECORD['text'][]=$BLOCKS[$V['l_value_xml']];
		}
	}
	//print_R($INFO);exit;
}


/// Get journal information
function getJournalInfo(&$RESULTS,&$INFO)
{
	$JOURNAL_ID=&$RESULTS['front'][0]['child']['journal-meta'][0]['journal-id'];
	foreach ($JOURNAL_ID as $ID)	$INFO['JOURNAL'][$ID['journal-id-type']]=$ID['l_value_xml'];
	
	$ISSN=&$RESULTS['front'][0]['child']['journal-meta'][0]['issn'];
	foreach ($ISSN as $ID)$INFO['ISSN'][$ID['pub-type']]=$ID['l_value_xml'];
	
}

function getArticleMeta(&$RESULTS,&$INFO,&$BLOCKS)
{
	global $DEBUG;
	//if ($DEBUG) echo "ARTICLE META\n";
	// print_R($RESULTS['front']);
	// exit;
	$ARTICLE_META=&$RESULTS['front'][0]['child']['article-meta'][0];
	
	$INFO['TITLE']=$BLOCKS[$ARTICLE_META['title-group'][0]['child']['article-title'][0]['l_value_xml']];


	$ARTICLE_ID=&$ARTICLE_META['article-id'];
	foreach ($ARTICLE_ID as $ID)	$INFO['ARTICLE'][$ID['pub-id-type']]=$ID['l_value_xml'];
	$ARTICLE_CAT=&$ARTICLE_META['article-categories'][0]['child']['subj-group'];;
	foreach ($ARTICLE_CAT as $CAT)
	{
		$INFO['CATEGORIES'][]=$CAT['subject'];
	}
	//$ARTICLE_CAT=&$ARTICLE_META['article-categories']

	if (isset($ARTICLE_META['abstract'][0]['child']['sec']) && $ARTICLE_META['abstract'][0]['child']['sec']!=array())
	{
		
		$ABSTRACTS=&$ARTICLE_META['abstract'][0]['child']['sec'];
		
		foreach ($ABSTRACTS as &$ABS)
		{
			if ($ABS==array())continue;
			
			$ABS_I=array('title'=>$BLOCKS[$ABS['title'][0]['l_value_xml']],
			'text'=>'');
			if (isset($ABS['p'][0]['l_value_xml']))$ABS_I['text']=$BLOCKS[$ABS['p'][0]['l_value_xml']];
			else if (isset($ABS['fig'][0]['child']['label'][0]['l_value_xml']))$ABS_I['text']=$ABS['fig'][0]['child']['label'][0]['l_value_xml'];
			if (isset($ABS['abstract-type']))
			$ABS_I['abstract-type']=$ABS['abstract-type'];
			$INFO['ABSTRACT'][]=$ABS_I;
			
		}
		//exit;
	}
	else if (isset($ARTICLE_META['abstract'][0]['child']['p']))
	{
		foreach ($ARTICLE_META['abstract'][0]['child']['p'] as $P)
		{
			$INFO['ABSTRACT'][]=$BLOCKS[$P['l_value_xml']];
		}
	}


	$PUB_DATE=&$ARTICLE_META['pub-date'];
	
	foreach ($PUB_DATE as $DATE)
	{
		
		$INFO['PUB_DATE'][$DATE['pub-type']]=(isset($DATE['child']['month'])?$DATE['child']['month'][0]['l_value_xml']:'01').'/'.(isset($DATE['child']['day'])?$DATE['child']['day'][0]['l_value_xml']:'01').'/'.$DATE['child']['year'][0]['l_value_xml'];
	}
	
	if (isset($ARTICLE_META['volume']))$INFO['VOLUME']=$ARTICLE_META['volume'][0]['l_value_xml'];
	if (isset($ARTICLE_META['issue']))$INFO['ISSUE']=$ARTICLE_META['issue'][0]['l_value_xml'];
	if (isset($ARTICLE_META['elocation-id']))$INFO['ELOCATION-ID']=$ARTICLE_META['elocation-id'][0]['l_value_xml'];
	if (isset($ARTICLE_META['permissions']))
	{
		$PERM=&$ARTICLE_META['permissions'][0]['child'];
		
		
		$INFO['PERMISSION']=array(
			
		);

		if (isset($PERM['copyright-statement']))
		{
			if (isset($PERM['copyright-statement'][0]['italic']))
			{
				$INFO['PERMISSION']['copyright-statement']='';
				foreach ($PERM['copyright-statement'][0]['italic'] as $N)
				{
					$INFO['PERMISSION']['copyright-statement'].=' '.$N['l_value_xml'];
				}
			}
			else $INFO['PERMISSION']['copyright-statement']=$PERM['copyright-statement'][0]['l_value_xml'];
		}
		if (isset($PERM['copyright-year'][0]['l_value_xml']))$INFO['copyright-year']=$PERM['copyright-year'][0]['l_value_xml'];
		if (isset($PERM['license']))
		{
			
			if (isset($PERM['license'][0]['href'][0]['l_value_xml']))
			$INFO['PERMISSION']['license']=$PERM['license'][0]['href'][0]['l_value_xml'];
			else
			{ 
				
				if (isset($PERM['license'][0]['license-p'][0]['child']['href']))
				$INFO['PERMISSION']['license']=$PERM['license'][0]['license-p'][0]['child']['href'];
				else if (isset($PERM['license'][0]['href']))$INFO['PERMISSION']['license']=$PERM['license'][0]['href'];
				else if (isset($PERM['license'][0]['license-p'][0]['child']['ext-link'][0]['href']))
				{
					$INFO['PERMISSION']['license']=$PERM['license'][0]['license-p'][0]['child']['ext-link'][0]['href'];
				}
			}
			//print_R($PERM['license']);
			if (isset($PERM['license'][0]['license-p'][0]['child']['italic'][0]['l_value_xml']))
			$INFO['PERMISSION']['license-p']=$PERM['license'][0]['license-p'][0]['child']['italic'][0]['l_value_xml'];
			else if (isset($PERM['license'][0]['license-p']))
			$INFO['PERMISSION']['license-p']=$PERM['license'][0]['license-p'][0]['l_value_xml'];
			
			else  $INFO['PERMISSION']['license-p']=$BLOCKS[$PERM['license'][0]['p'][0]['l_value_xml']];
		}
		else if (isset($PERM['copyright-holder']))
		{
			
		$INFO['PERMISSION']['license-p']=$PERM['copyright-holder'][0]['l_value_xml'];
		}
	}
	else if (isset($ARTICLE_META['copyright-statement']))
	{
		$INFO['PERMISSION']=array('copyright-statement'=>$ARTICLE_META['copyright-statement'][0]['l_value_xml']);
	}


	//if ($DEBUG)echo "END ARTICLE META\n";
}







	/// Get PMC information from the database
	function getPMCEntry(&$INFO,&$STATUS_CODE)
	{
		global $JOB_ID;
		global $STMT;
		/// STEP1: INSERT PMC_ENTRY
		$PMC_ID=$INFO['RAW_INFO']['Accession ID'];
		$PMID=$INFO['RAW_INFO']['PMID'];
		$PMID_ENTRY_ID='NULL';
		$PMC_ENTRY_ID='NULL';
		// Get PMID_ENTRY_ID
		if ($PMID!='')
		{
			$DATA=runQuery("SELECT pmid_entry_id FROM  pmid_entry where pmid=".$PMID);
			if ($DATA===false)failProcess($JOB_ID.'001','Unable to query pmid_entry');
			if ($DATA!=array())
			$PMID_ENTRY_ID=$DATA[0]['pmid_entry_id'];
		}

		/// Get PMC Info:
		$DATA=runQuery("SELECT * FROM  pmc_entry where pmc_id='".$PMC_ID."'");
		if ($DATA===false)failProcess($JOB_ID.'002','Unable to query pmc_entry');
		if ($DATA==array())
		{
			//(pmc_entry_id,pmc_id,license,date_added,date_processed,pmid_entry_id)
			$params=array(
				':pmc_id'=>$PMC_ID,
				':license'=>$INFO['RAW_INFO']['License'],
				':pmc_last_update'=>$INFO['RAW_INFO']['Last Updated (YYYY-MM-DD HH:MM:SS)'],
				':status_code'=>'0',
			);

			$STATUS_CODE=0;
			if ($PMID_ENTRY_ID!='NULL')$params[':pmid_entry_id']=$PMID_ENTRY_ID;
			else $params[':pmid_entry_id']=NULL;
			$STMT['pmc_entry']->execute($params);
			$PMC_ENTRY_ID=$STMT['pmc_entry']->fetch(PDO::FETCH_ASSOC)['pmc_entry_id'];
			
			$DATA=runQuery("SELECT * FROM  pmc_entry where pmc_id='".$PMC_ID."'");
			if ($DATA===false)failProcess($JOB_ID.'003','Unable to query pmc_entry');
		}
		else 
		{
			$PMC_ENTRY_ID=$DATA[0]['pmc_entry_id'];
			$STATUS_CODE=$DATA[0]['status_code'];
			$UPDS=array();
			if ($DATA[0]['license']!=$INFO['RAW_INFO']['License'])
			{
				$UPDS[]="license ='".$INFO['RAW_INFO']['License']."'";
			}
			if ($DATA[0]['pmc_last_update']!=$INFO['RAW_INFO']['Last Updated (YYYY-MM-DD HH:MM:SS)'])
			{
				$UPDS[]="pmc_last_update ='".$INFO['RAW_INFO']['Last Updated (YYYY-MM-DD HH:MM:SS)']."'";
			}
			$UPDS[]="date_processed='".date('Y-m-d H:i:s')."'";
			if ($UPDS!=array())
				$res=runQueryNoRes("UPDATE pmc_entry 
						set ".implode(',',$UPDS)."
						where pmc_entry_id=".$PMC_ENTRY_ID);
				if ($res===false)failProcess($JOB_ID.'004','Unable to update pmc_entry');
			
		}
		return $PMC_ENTRY_ID;
	}

	/// Get all the associated annotation and the fulltext from the database
	function getPMCData($PMC_ENTRY_ID)
	{
		global $JOB_ID;
		$DATA['ID']=array();
		$DATA['STATS']=array();
		$DATA['FULLTEXT']=array();
		$TMP=runQuery("SELECT * FROM  pmc_fulltext pf where pmc_entry_id = ".$PMC_ENTRY_ID);
		if ($TMP===false)failProcess($JOB_ID.'B01','Unable to query pmc_fulltext');
		foreach ($TMP as $line)
		{
			$line['DB_STATUS']='FROM_DB';
			$DATA['FULLTEXT'][$line['pmc_fulltext_id']]=$line;
		}
		if ($DATA['FULLTEXT']==array())return $DATA;;
		
		$TBL=array(
			'pmc_fulltext_drug_map'=>array('drug_entry','drug_primary_name','DR'),
			'pmc_fulltext_disease_map'=>array('disease_entry','disease_tag','DS'),
			'pmc_fulltext_anatomy_map'=>array('anatomy_entry','anatomy_tag','AN'),
			'pmc_fulltext_gn_map'=>array('gn_entry','gene_id','GN'),
			'pmc_fulltext_go_map'=>array('go_entry','ac','GO'),
			'pmc_fulltext_company_map'=>array('company_entry','company_name','CO'),
			'pmc_fulltext_cell_map'=>array('cell_entry','cell_acc','CL'),
			'pmc_fulltext_sm_map'=>array('sm_entry','md5_hash','SM'),
			'pmc_fulltext_clinical_map'=>array('clinical_trial','trial_id','CI'),
			'pmc_fulltext_pub_map'=>array('pmid_entry','pmid_entry_id','REF'),
			'pmc_fulltext_ontology_map'=>array('ontology_entry','ontology_tag','ON'));
		
		foreach ($TBL as $T=>&$V)
		{
			
			$DATA['STATS'][$T]=array('NEW'=>0,'DEL'=>0,'VAL'=>0);
			
			//$res=runQuery("SELECT * FROM  ".$T." where pmc_fulltext_id in (SELECT pmc_fulltext_id FROM pmc_fulltext where pmc_entry_id = ".$pmc_ENTRY_ID.")");
			$res=runQuery("SELECT ".$T."_id, pmc_fulltext_id,loc_info,b.".$V[1]." 
							FROM ".$T." a ,".$V[0]." b
							WHERE a.".$V[0]."_id=b.".$V[0]."_id 
							and pmc_fulltext_id in (".implode(',',array_keys($DATA['FULLTEXT'])).")");

			if ($res===false)failProcess($JOB_ID.'B02','Unable to query '.$T);
			foreach ($res as &$E)
			{
				$E['DB_STATUS']='FROM_DB';
				$DATA['ID'][$E['pmc_fulltext_id']][$V[2]][]=array($E[$V[1]],$E['loc_info'],'FROM_DB',$E[$T.'_id']);
			}
			
		}
		return $DATA;
	}



	

	/// Push the full text to the database
	function pushFullText(&$LINEAR,&$PMC_ENTRY_ID,&$DATA)
	{
		global $JOB_ID;
		global $STMT;
		$res=runQuery("SELECT * FROM pmc_section");
		if ($res===false)	failProcess($JOB_ID.'C01','Unable to query pmc_section');
		$SECTIONS=array();
		foreach ($res as $line)
		{
			$SECTIONS[$line['section_type']][$line['section_subtype']]=$line['pmc_section_id'];
		}
		/// We loop over the full text
		foreach ($LINEAR as $OFFSET=>&$LINE)
		{
			//echo $OFFSET."\t";print_R($LINE);

			/// Some specific text have their space replaced by ____ so we replace them back
			if (!isset($LINE[2]))continue;
			
			$LINE[2]=str_replace("____"," ",$LINE[2]);
			if ($LINE[2]=='')continue;

			/// We check if the section is already in the database
			/// If not we insert it
			if (!isset($SECTIONS[$LINE[0]][$LINE[1]]))
			{
				
				$params=array(':section_type'=>$LINE[0],':section_subtype'=>$LINE[1]);

				$res=$STMT['pmc_section']->execute($params);
				
				if ($res===false)failProcess($JOB_ID.'C02','Unable to insert pmc_section');
				
				$SECTIONS[$LINE[0]][$LINE[1]]=$STMT['pmc_section']->fetch(PDO::FETCH_ASSOC)['pmc_section_id'];
			}
			/// We get the section id from the database
			$PMC_SECTION_ID=$SECTIONS[$LINE[0]][$LINE[1]];


			/// We check if the full text is already in the database
			$FOUND=false;
			foreach ($DATA['FULLTEXT'] as &$DB_ENTRY)
			{
				/// It must be the same section and the same offset
				if ($DB_ENTRY['pmc_section_id']!=$PMC_SECTION_ID)continue;
				if ($DB_ENTRY['offset_pos']!=$OFFSET)continue;

				/// If the text isn't the same we update it
				if ($DB_ENTRY['full_text']!=$LINE[2] || $DB_ENTRY['group_id']!=$LINE[3])
				{
					echo "\tUPDATE\n";
					if (!runQueryNoRes("UPDATE pmc_fulltext 
						set full_text='".str_replace("'","''",$LINE[2])."' 
						".(($LINE[3]!='')?", group_id=".$LINE[3]:"")."
						where pmc_fulltext_id=".$DB_ENTRY['pmc_fulltext_id']))
						failProcess($JOB_ID.'C03','Unable to update pmc_fulltext');
				}
					$LINE['DB_ID']=$DB_ENTRY['pmc_fulltext_id'];
					$DB_ENTRY['DB_STATUS']='VALID';
					$FOUND=true;
					break;
				
				
			}
			if ($FOUND)continue;
			
			/// Not found in the database, we insert it
			$PARAMS=array(
				':pmc_entry_id'=>$PMC_ENTRY_ID,
				':pmc_section_id'=>$PMC_SECTION_ID,
				':offset_pos'=>$OFFSET,
				':full_text'=>$LINE[2]);
			if ($LINE[3]!=NULL)$PARAMS[':group_id']=$LINE[3];
			else $PARAMS[':group_id']=NULL;

			$res=$STMT['pmc_fulltext']->execute($PARAMS);
			
			if ($res===false)failProcess($JOB_ID.'C04','Unable to insert pmc_fulltext');
			
			$PMC_FULLTEXT_ID=$STMT['pmc_fulltext']->fetch(PDO::FETCH_ASSOC)['pmc_fulltext_id'];
			$LINE['DB_ID']=$PMC_FULLTEXT_ID;
	
		}
		/// We delete the full text records that we didn't find in the linear
		$TO_DEL=array();
		foreach ($DATA['FULLTEXT'] as $I=>&$DB_ENTRY)
		{
			if ($DB_ENTRY['DB_STATUS']=='VALID')continue;
			$TO_DEL[]=$DB_ENTRY['pmc_fulltext_id'];
		}
		if ($TO_DEL!=array())
		{
			echo "DEL ".count($TO_DEL).' full text'."\n";
			$res=runQueryNoRes("DELETE FROM pmc_fulltext where pmc_fulltext_id in (".implode(',',$TO_DEL).")");
			if ($res===false)failProcess($JOB_ID.'C05','Unable to delete pmc_fulltext');
		}
	}






	/// Main function to push everything to the database
	function pushToDB(&$LINEAR,&$PUBLI_ANNOTS,&$INFO,&$FILES)
	{

		global $JOB_ID;

		/// First we get all the PMIDs from the references
		/// So we can map it back to the annotations
		$PMIDS=array();
		if (isset($INFO['back']['REF']))
		foreach ($INFO['back']['REF'] as $REF)
		{
			if (!isset($REF['PUB_ID']['pmid']))continue;
			$PMIDS[]=$REF['PUB_ID']['pmid'];
			$PMID_REF[$REF['PUB_ID']['pmid']]=$REF['ID'];
		}
		$MAP_REF_PMID=array();
		if ($PMIDS!=array())
		{
			$res=runQuery("SELECT pmid, pmid_entry_id 
				FROM pmid_entry
				where pmid IN (".implode(',',$PMIDS).")");
			if ($res===false)	failProcess($JOB_ID.'D01','Unable to query pmid_entry');
			foreach ($res as $line)
			{
				$MAP_REF_PMID[$PMID_REF[$line['pmid']]]=$line['pmid_entry_id'];
			}

		}

		
		/// Get PMC Entry
		$STATUS_CODE=0;
		$PMC_ENTRY_ID=getPMCEntry($INFO,$STATUS_CODE);
		/// Get all data from the database
		
		$DATA=getPMCData($PMC_ENTRY_ID);

		/// Push full text
		pushFullText($LINEAR,$PMC_ENTRY_ID,$DATA);
		
		/// Push annotations
		pushAnnotations($LINEAR,$PUBLI_ANNOTS,$PMC_ENTRY_ID,$DATA,$MAP_REF_PMID);

		// Push files
		pushFiles($FILES,$PMC_ENTRY_ID);
		
		runQueryNoRes("UPDATE pmc_entry set status_code=1 where pmc_entry_id=".$PMC_ENTRY_ID);
		
	}


	function pushFiles($FILES,$PMC_ENTRY_ID)
	{
		global $JOB_ID;
		global $STMT;
		/// Get all the files from the database. We don't want the content, so we only get the hash
		$res=runQuery("SELECT pmc_fulltext_file_id,file_name,file_id,mime_type,md5(file_content) as hash_file 
		FROM  pmc_fulltext_file where pmc_entry_id = ".$PMC_ENTRY_ID);
		if ($res===false)failProcess($JOB_ID.'E01','Unable to query pmc_fulltext_file');
		$DB_FILES=array();
		foreach ($res as $line)
		{
			$line['DB_STATUS']='FROM_DB';
			$DB_FILES[]=$line;
		}
		
		foreach ($FILES as $FID=>&$FPATH)
		{
			if ($FPATH=='')continue;
			/// Sometimes the file name provided does not have the extension
			if (!is_file($FPATH))
			{
				/// We try to find the file with the extension
				$res=array();
				$list_f=scandir('.');
				/// And take the biggest file
				$maxfsize=0;$curr_f='';
				foreach($list_f as $f)
				{
					
					if (strpos($f,$FPATH)===false) continue;
					$size=filesize($f);
					if ($size <$maxfsize)continue;
					$maxfsize=$size;
					$curr_f=$f;
				}
				
				$FPATH=$curr_f;
			}
			if ($FPATH=='')continue;
			/// We get the hash and the mime type of the file
			$MD5=md5_file($FPATH);
			$MIME=mime_content_type($FPATH);

			/// We check if the file is already in the database
			$FOUND=false;
			foreach ($DB_FILES as &$DB_FILE)
			{
				if ($DB_FILE['file_id']!=$FID)continue;
				
				if ($DB_FILE['hash_file']!=$MD5 || $DB_FILE['mime_type']!=$MIME || $DB_FILE['file_name']!=$FPATH)
				{
					echo "DELETE ".$FID."\n";
					if (!runQueryNoRes("DELETE FROM pmc_fulltext_file 
						where pmc_fulltext_file_id=".$DB_FILE['pmc_fulltext_file_id']))
						
						failProcess($JOB_ID.'E03','Unable to delete file '.$FPATH);
					continue;
				}else $FOUND=true;
				break;
			}
			if ($FOUND)continue;

			/// We insert the file
			$CONTENT=file_get_contents($FPATH);
			$STMT['pmc_fulltext_file']->bindParam(':file_content', $CONTENT, PDO::PARAM_LOB);
			$STMT['pmc_fulltext_file']->bindParam(':pmc_entry_id', $PMC_ENTRY_ID, PDO::PARAM_INT);
			$STMT['pmc_fulltext_file']->bindParam(':file_name', $FPATH, PDO::PARAM_STR);
			$STMT['pmc_fulltext_file']->bindParam(':file_id', $FID, PDO::PARAM_STR);
			$STMT['pmc_fulltext_file']->bindParam(':mime_type', $MIME, PDO::PARAM_STR);
			$STMT['pmc_fulltext_file']->execute();
		}
			

	}

	

	function pushAnnotations(&$LINEAR,&$PUBLI_ANNOTS,&$PMC_ENTRY_ID,&$DATA,&$MAP_REF_PMID)
	{
		global $JOB_ID;
		/// We map the type of annotation to the table in the database
		$MAP_STMT=array(
			'DR'=>'pmc_fulltext_drug_map',
			'SM'=>'pmc_fulltext_sm_map',
			'DS'=>'pmc_fulltext_disease_map',
			'AN'=>'pmc_fulltext_anatomy_map',
			'GN'=>'pmc_fulltext_gn_map',
			'ON'=>'pmc_fulltext_ontology_map',
			'GO'=>'pmc_fulltext_go_map',
			'CO'=>'pmc_fulltext_company_map',
			'CL'=>'pmc_fulltext_cell_map',
			'CI'=>'pmc_fulltext_clinical_map',
			'REF'=>'pmc_fulltext_pub_map');
		global $STMT;
		
		/// Annotations are grouped by the full text offset
		foreach ($PUBLI_ANNOTS as $OFFSET=>&$LIST_ANNOTS)
		{
			/// We get the full text id from the database
			$PMC_FULLTEXT_ID=$LINEAR[$OFFSET]['DB_ID'];

			/// We get the annotations from the db for this offset as a reference
			$DB_ANNOTS=&$DATA['ID'][$PMC_FULLTEXT_ID];
		
			/// We loop over the annotations
			foreach ($LIST_ANNOTS as &$ANNOTS)
			{
				$TYPE=$ANNOTS[0];
				$TAG=$ANNOTS[1];

				/// In the case of a reference, column are shifted
				/// and we need to map the citation id back to the pmid_entry_id
				if ($TYPE=='REF')
				{
					$TAG=$ANNOTS[2];
					$ANNOTS[2]=0;
					/// But if the REF is not a pmid, we continue
					if (!isset($MAP_REF_PMID[$TAG]))continue;
					$TAG=$MAP_REF_PMID[$TAG];
				}

				/// We define the location of the annotation
				$LOC=$ANNOTS[2].'|'.$ANNOTS[3];
				
				/// For debug
				//echo $OFFSET."\t".$TYPE."\t".$TAG."\t".$LOC."\t".(($DB_ANNOTS!=null)?'Y':'N')."\t".(isset($DB_ANNOTS[$TYPE])?"Y":"N")."\n";


				/// We check if the annotation is already in the database
				$FOUND=false;
				if ($DB_ANNOTS!=null && isset($DB_ANNOTS[$TYPE]))
				foreach ($DB_ANNOTS[$TYPE] as &$DB_ENTRY)
				{
				//	echo "\t".implode("\t",$DB_ENTRY)."\n";
					if ($DB_ENTRY[0]!=$TAG)continue;
					if ($DB_ENTRY[1]!=$LOC)continue;
					$DB_ENTRY[2]='VALID';
					$FOUND=true;
					break;
				}
				//echo "FOUND: ".$FOUND."\n";
				if ($FOUND)continue;

				/// Not found in the database, we insert it
				$PARAMS=array(':pmc_fulltext_id'=>$PMC_FULLTEXT_ID,
					':loc_info'=>$LOC,
					':val'=>$TAG);
				
				$DB_ANNOTS[$TYPE][]=array($TAG,$LOC,'NEW',NULL);
				$res=$STMT[$MAP_STMT[$TYPE]]->execute($PARAMS);
				if ($res===false)failProcess($JOB_ID.'013','Unable to insert '.$TYPE);
			}
		}

		/// We delete the annotations that we didn't find in the linear
		$TO_DEL=array();
		//print_r($DATA['ID']);
		foreach ($DATA['ID'] as $PMC_FULLTEXT_ID=>&$LIST_DB_ANNOTS)
		{
			if ($LIST_DB_ANNOTS==null || $LIST_DB_ANNOTS==array())continue;
			foreach ($LIST_DB_ANNOTS as $TYPE=> &$LIST_TYPE)
			{
				foreach ($LIST_TYPE as &$DB_ENTRY)
				{
				if ($DB_ENTRY[2]=='VALID'||$DB_ENTRY[2]=='NEW')continue;
				$TO_DEL[$MAP_STMT[$TYPE]][]=$DB_ENTRY[3];
				}
			}
		}
		foreach ($TO_DEL as $T=>&$LIST)
		{
			if ($LIST==array())continue;
			//echo "DEL ".count($LIST).' '.$T."\n";
			$res=runQuery("DELETE FROM ".$T." where ".$T."_id in (".implode(',',$LIST).")");
			if ($res===false)failProcess($JOB_ID.'F01','Unable to delete '.$T);
			
		}
	}



/*


*/

	

?>