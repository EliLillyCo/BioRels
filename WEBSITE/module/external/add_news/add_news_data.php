<?php

// we can check if this file is listening
// echo '<pre>';
// print_r($USER_INPUT);
// exit;
//echo json_encode($USER_INPUT['PARAMS']);exit;
//
 //print_r($USER_INPUT["PARAMS"]);

// echo '<pre>';
// print_R($_POST);
// print_r($USER_INPUT);
// exit;



function checkMatch(&$TEXT,&$query,$with_case=false)
{
	$CHARS=array('[','\'','"','(',' ','?','!',';','-','_',']',')','.',"\n","\t",'','/',chr(32),chr(13),chr(160),chr(10));
	if ($with_case) $pos=strpos($TEXT,$query);
	else $pos=stripos($TEXT,$query);
	if ($pos===false)return false;				
	$prev='';
	if ($pos-1!=0)$prev=substr($TEXT,$pos-1,1);
	$next='';
	if ($pos+1!=strlen($TEXT))$next=substr($TEXT,$pos+strlen($query),1);
	
	if (in_array($prev,$CHARS) && in_array($next,$CHARS))return true;
	return false;
				
}

function checkFile()
{
	global $_FILES;
	try {
		
		if ($_FILES==array())return true;
    for ($I=0;$I<count($_FILES['fpath']['size']);++$I)
	{
		if ($_FILES['fpath']['size'][$I]==0)continue;
		// Undefined | Multiple Files | $_FILES Corruption Attack
		// If this request falls under any of them, treat it invalid.
		if (
			!isset($_FILES['fpath']['error']) ) {
			throw new RuntimeException('Invalid parameters.');
		}
	
		// Check $_FILES['upfile']['error'] value.
		switch ($_FILES['fpath']['error'][$I]) {
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_NO_FILE:
				throw new RuntimeException('No file sent.');
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				throw new RuntimeException('Exceeded filesize limit.');
			default:
				throw new RuntimeException('Unknown errors.');
		}
	
		// You should also check filesize here. 
		if ($_FILES['fpath']['size'][$I] > 2000000) {
			throw new RuntimeException('Exceeded filesize limit.');
		}
	
		// DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
		// Check MIME Type by yourself.
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$_FILES['fpath']['mime'][$I]=$finfo->file($_FILES['fpath']['tmp_name'][$I]);
		if (false === $ext = array_search(
			$finfo->file($_FILES['fpath']['tmp_name'][$I]),
			array(
				'jpg' => 'image/jpeg',
				'png' => 'image/png',
				'gif' => 'image/gif',
				'pdf'=> 'application/pdf',
				'doc'=>     'application/msword',
				'dot'=>'application/msword',

				'docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'dotx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
				'docm'=>'application/vnd.ms-word.document.macroEnabled.12',
				'dotm'=>'application/vnd.ms-word.template.macroEnabled.12',

				'xls'=>'application/vnd.ms-excel',
				'xlt'=>'application/vnd.ms-excel',
				'xla'=>'application/vnd.ms-excel',
				'xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'xltx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
				'xlsm'=>'application/vnd.ms-excel.sheet.macroEnabled.12',
				'xltm'=>'application/vnd.ms-excel.template.macroEnabled.12',
				'xlam'=>'application/vnd.ms-excel.addin.macroEnabled.12',
				'xlsb'=>'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
				'ppt'=>'application/vnd.ms-powerpoint',
				'pot'=>'application/vnd.ms-powerpoint',
				'pps'=>'application/vnd.ms-powerpoint',
				'ppa'=>'application/vnd.ms-powerpoint',
				'pptx'=>'application/vnd.openxmlformats-officedocument.presentationml.presentation',
				'potx'=>'application/vnd.openxmlformats-officedocument.presentationml.template',
				'ppsx'=>'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
				'ppam'=>'application/vnd.ms-powerpoint.addin.macroEnabled.12',
				'pptm'=>'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
				'potm'=>'application/vnd.ms-powerpoint.template.macroEnabled.12',
				'ppsm'=>'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
				'mdb'=>'application/vnd.ms-access'),
			
			true
		)) {
			throw new RuntimeException('Invalid file format. '.$finfo->file($_FILES['fpath']['tmp_name'][$I]));
		}
	
	
		}
	} catch (RuntimeException $e) {
	
		return $e->getMessage();
	
	}
	return true;
}
try{
	
if (!isset($_SESSION['USER_DATA']))
{
	$_SESSION['USER_DATA']=getUserGroups($USER['id']);

}



$MODULE_DATA['SOURCES']=getNewsSources();

ini_set('memory_limit','2000M');
$MATCH=array();
if ($USER_INPUT['PARAMS']!=array())
{
	$MATCH['RUN']=true;
	$TEXT='';
$IS_TEST=false;

	
	
	if (in_array('submit_form_check',$USER_INPUT['PARAMS']))
	// we have annotated text to submit and we want to send it to db
	{
		checkFile();


		$TAGS=array('GENE_PRIMARY'=>array(),'CLINICAL_PRIMARY'=>array(),'DISEASE_PRIMARY'=>array(),'COMPANY_PRIMARY'=>array(),'DRUG_PRIMARY'=>array());
		
		$NEWS=array('USER_ID'=>$USER['DB_ID'],'NEWS_CONTENT'=>'','NEWS_HTML'=>'','TITLE'=>'','SOURCE'=>-1,'HASH'=>md5(microtime_float()));	
		$IS_PRIVATE=true;
		$IS_PUBLIC=true;
		$CURR_NEWS_ID='';
		for ($I=0;$I<count($USER_INPUT['PARAMS']);++$I)
		{
			
			//echo $I."<br/>";echo $USER_INPUT['PARAMS'][$I].'<br/>';
			//print_r($NEWS);
		
			
			switch ($USER_INPUT['PARAMS'][$I])
			{
				case 'submit_form_check':++$I;break;
				case 'articleTitle':
					$I++;
					$NEWS['TITLE']=$USER_INPUT['PARAMS'][$I];
					
					break;
					case 'content':
						$I++;
	
						$NEWS['NEWS_HTML']=htmlspecialchars_decode($USER_INPUT['PARAMS'][$I]);
						$NEWS['NEWS_CONTENT'] =htmlspecialchars_decode($USER_INPUT['PARAMS'][$I]);
						
						break;
				
				case 'news_id':
						$I++;
						$CURR_NEWS_ID=$USER_INPUT['PARAMS'][$I];
						break;
				case 'source_name':
					$I++;
					$NEWS['SOURCE']=array_flip($MODULE_DATA['SOURCES'])[$USER_INPUT['PARAMS'][$I]];
					$NEWS['SOURCE_NAME']=$USER_INPUT['PARAMS'][$I];
					break;
			
						
				case 'drug':
					$I++;
					$TAGS['DRUG']=$USER_INPUT['PARAMS'][$I];
					break;
				
				case 'company':
					$I++;
					$TAGS['COMPANY']=$USER_INPUT['PARAMS'][$I];
					break;
				case 'clinical':
					$I++;
					$TAGS['CLINICAL']=$USER_INPUT['PARAMS'][$I];
					break;
				case 'disease':
					$I++;
					$TAGS['DISEASE']=$USER_INPUT['PARAMS'][$I];
					break;
				case 'gene':
					$I++;
					$TAGS['GENE']=$USER_INPUT['PARAMS'][$I];
					break;
				case 'drug_primary':
					$I++;
					$TAGS['DRUG_PRIMARY']=$USER_INPUT['PARAMS'][$I];
					break;
				case 'company_primary':
					$I++;
					$TAGS['COMPANY_PRIMARY']=$USER_INPUT['PARAMS'][$I];
					break;
				case 'clinical_primary':
					$I++;
					$TAGS['CLINICAL_PRIMARY']=$USER_INPUT['PARAMS'][$I];
					break;
				case 'disease_primary':
					$I++;
					$TAGS['DISEASE_PRIMARY']=$USER_INPUT['PARAMS'][$I];
					break;
				case 'gene_primary':
					$I++;
					$TAGS['GENE_PRIMARY']=$USER_INPUT['PARAMS'][$I];
					break;
					case 'news':
						$I++;
						$TAGS['NEWS_REL']=$USER_INPUT['PARAMS'][$I];
						break;
				case 'fname':
					++$I;
						$_FILES['fpath']['file_name']=$USER_INPUT['PARAMS'][$I];
					
					break;
				case 'fdesc':
					
						++$I;
						$_FILES['fpath']['file_desc']=$USER_INPUT['PARAMS'][$I];
					
					break;
				case 'IS_PRIVATE':
					
						$I++;
						
						if ($USER_INPUT['PARAMS'][$I]=='on')$IS_PRIVATE=true;
						
						break;
					case 'IS_PUBLIC':
						$I++;
						if ($USER_INPUT['PARAMS'][$I]=='on')$IS_PUBLIC=true;
						break;
			}
		}	
		


if ($USER['Access'][1]!=1)
{
	$IS_PRIVATE=true;
	$IS_PUBLIC=true;
}


		$MATCH['SUBMIT_SUCCESS'] = true;		
		// $TEXT=$USER_INPUT['PARAMS'][1];
		// $NEWS_CONTENT = $TEXT['content'];
		// $NEWS_HTML = substr($TEXT['html'],1,-1);
		
		
		//if (isset($TEXT['tags']))		$TAGS = $TEXT['tags'];
		//$TITLE = $TEXT['title'];
		$MODULE_DATA = array('NEWS_INPUT'=>$NEWS,'SUCCESS'=>'true','MESSAGE'=>'');
		$FILE_MESS=checkFile($_FILES);
		if ($FILE_MESS!==true)
		{
			$MODULE_DATA['SUCCESS']=false;
			$MODULE_DATA['MESSAGE']=$FILE_MESS;
		}
		if ($NEWS['SOURCE']==-1)
		{
			$MODULE_DATA['SUCCESS']=false;
			$MODULE_DATA['MESSAGE']='Unrecognized source';
		}

// 		exit;
 global $DB_CONN;
// echo "START";

		if ($CURR_NEWS_ID=='')
		{

			
		if ($IS_PUBLIC && $MODULE_DATA['SUCCESS']==true)
		{
			
			$DB_CONN->beginTransaction();
			$res = submitNews($NEWS);	
		
			if ($res!=null){	
				
				
				$res_annotations = submitNewsAnnotations($res, $TAGS);	
										
				if ($res_annotations['SUCCESS']==false)
				{ 
					$DB_CONN->rollback();
					$MODULE_DATA['SUCCESS']=false;
					$MODULE_DATA['MESSAGE'].=$res_annotations['ERROR_MESSAGE'].' in public news feed<br/>';
					
				}else 
				{
					if (isset($_FILES))
					{
						echo "ADD GREEN FILES"		;
						$res_files=submitNewsFiles($res,$_FILES);
						if ($res_files['SUCCESS']==false)
						{ 
							$DB_CONN->rollback();
							$MODULE_DATA['SUCCESS']=false;
							$MODULE_DATA['MESSAGE'].=$res_files['ERROR_MESSAGE'].' in public news feed<br/>';
							
						}else 
						{

							$DB_CONN->commit();
							$MODULE_DATA['MESSAGE'].='News added in public news feed<br/>';
						}
					}
					else 
					{

					$DB_CONN->commit();
					$MODULE_DATA['MESSAGE'].='News added in public news feed<br/>';
					}
				}
				
			}else{
				$DB_CONN->rollback();
				$MODULE_DATA['SUCCESS']=false;
				$MODULE_DATA['MESSAGE'].='Unable to add news in public news feed<br/>';
			}
			echo "GREEN OUT";
		} 
		
		// exit;
		
		if ($IS_PRIVATE && $MODULE_DATA['SUCCESS']==true)
		{
			echo "RED";
			$DB_CONN->beginTransaction();
			$res = private_submitNews($NEWS);	
			echo "RED NEWS";
			
			if ($res!=null){	
					echo "ADD private ANNOTATION"		;
			
				$res_annotations = private_submitNewsAnnotations($res, $TAGS);	
									
				//print_r($res_annotations);
				if ($res_annotations['SUCCESS']==false)
				{ 
					$DB_CONN->rollback();
					$MODULE_DATA['SUCCESS']=false;
					$MODULE_DATA['MESSAGE'].=$res_annotations['ERROR_MESSAGE'].' in private news feed<br/>';
					
				}else 
				{
					if (isset($_FILES))
					{
						echo "ADD private FILES"		;
						$res_files=private_submitNewsFiles($res,$_FILES);
						if ($res_files['SUCCESS']==false)
						{ 
							$DB_CONN->rollback();
							$MODULE_DATA['SUCCESS']=false;
							$MODULE_DATA['MESSAGE'].=$res_files['ERROR_MESSAGE'].' in private news feed<br/>';
							
						}else 
						{

							$DB_CONN->commit();
							$MODULE_DATA['MESSAGE'].='News added in private news feed<br/>';
						}
					}
					else 
					{

					$DB_CONN->commit();
					$MODULE_DATA['MESSAGE'].='News added in private news feed<br/>';
					}
				}
				
				

			}else{
				$DB_CONN->rollback();
				$MODULE_DATA['SUCCESS']=false;
				$MODULE_DATA['MESSAGE'].='Unable to add news in private news feed<br/>';
				
			}

			
			

			echo "RED OUT";
			//exit;

			// exit;
		} 	
		
		
		//


		// echo '<pre>';
		// print_r($MODULE_DATA);exit;
		// exit;
	}
	else
	{
		
		$MODULE_DATA['UPDATE']=true;
		$NEWS['NEWS_HASH']=$CURR_NEWS_ID;
		if ($IS_PUBLIC && $MODULE_DATA['SUCCESS']==true)
		{
			echo "UPDATE GREEN";
			$DB_CONN->beginTransaction();
			
			
			$res = updateNews($NEWS);	
			
			
			
			
			if ($res!=null){	
							
				echo "UPDATE GREEN ANNOT";
				
				$res_annotations = updateNewsAnnotations($res, $TAGS);										
				if ($res_annotations['SUCCESS']==false)
				{ 
					$DB_CONN->rollback();
					$MODULE_DATA['SUCCESS']=false;
					$MODULE_DATA['MESSAGE'].=$res_annotations['ERROR_MESSAGE'].' in public news feed<br/>';
					
					
				}else 
				{
					
					
					$DB_CONN->commit();
					$MODULE_DATA['MESSAGE'].='News updated in public news feed<br/>';
					
				}
				
			}else{
				$DB_CONN->rollback();
				$MODULE_DATA['SUCCESS']=false;
				$MODULE_DATA['MESSAGE'].='Unable to update news in public news feed<br/>';
			}
			echo "GREEN OUT";
		} 
		// exit;
		
		if ($IS_PRIVATE && $MODULE_DATA['SUCCESS']==true)
		{
			echo "UPDATE private ANNOT";
			
			$DB_CONN->beginTransaction();
			$res = private_updateNews($NEWS);	
			echo "RED NEWS";
			
			if ($res!=null){	
					echo "UPDATE private ANNOTATION"		;
			
				$res_annotations = private_updateNewsAnnotations($res, $TAGS);										
				
				if ($res_annotations['SUCCESS']==false)
				{ 
					$DB_CONN->rollback();
					$MODULE_DATA['SUCCESS']=false;
					$MODULE_DATA['MESSAGE'].=$res_annotations['ERROR_MESSAGE'].' in private news feed<br/>';
					
				}else 
				{
					

					$DB_CONN->commit();
					$MODULE_DATA['MESSAGE'].='News updated in private news feed<br/>';
					
				}
				
			}else{
				$DB_CONN->rollback();
				$MODULE_DATA['SUCCESS']=false;
				$MODULE_DATA['MESSAGE'].='Unable to update news in private news feed<br/>';
				
			}
			
			//print_r($MODULE_DATA);
			echo "PRIVATE OUT";
			// exit;
			
		} 
		
	}
		
	}
	else  if ($USER_INPUT['PARAMS'][0]=='text')
		{

			for ($I=0; $I<count($USER_INPUT['PARAMS']);++$I)
			{
				if ($USER_INPUT['PARAMS'][$I]=='text')
				{
					++$I;
					$TEXT=$USER_INPUT['PARAMS'][$I];
				}
				else if ($USER_INPUT['PARAMS'][$I]=='title')
				{
					++$I;
					$TITLE=$USER_INPUT['PARAMS'][$I];
				}
				else if($USER_INPUT['PARAMS'][$I]=='delta')
				{
					++$I;
					$DELTA=json_decode(str_replace("&quot;",'"',$USER_INPUT['PARAMS'][$I]),true);
				}
			}
			
			
			$DELTA_INFO=array();
			foreach ($DELTA['ops'] as $V)
			{
				
				if (!is_array($V['insert']))$DELTA_INFO[]=$V['insert'];
				if (isset($V['attributes']['link']))$DELTA_INFO[]=$V['attributes']['link'];
			}
			
			
			$DELTA=implode(' ',$DELTA_INFO);
			
		}	  		
		if ($TEXT!='')											
		{		
			$MODULE_DATA['JOBS']=true;
			$MODULE_DATA['CLINICAL']=submitJob('search_clinical',array('QUERY'=>$TEXT,'DELTA'=>$DELTA),$DESCRIPTION,$TITLE);
			$MODULE_DATA['DRUG']=submitJob('search_drug',array('QUERY'=>$TEXT),$DESCRIPTION,$TITLE);
			$MODULE_DATA['GENE']=submitJob('search_gene',array('QUERY'=>$TEXT),$DESCRIPTION,$TITLE);
			$MODULE_DATA['COMPANY']=submitJob('search_company',array('QUERY'=>$TEXT),$DESCRIPTION,$TITLE);
			$MODULE_DATA['DISEASE']=submitJob('search_disease',array('QUERY'=>$TEXT),$DESCRIPTION,$TITLE);

		}
	else if ($USER_INPUT['PARAMS'][0]=='JOBS')	
	{
		$MODULE_DATA=array();
		for ($I=1;$I<=count($USER_INPUT['PARAMS']);++$I)
		{
			if (!in_array($USER_INPUT['PARAMS'][$I],array('GENE','DISEASE','DRUG','CLINICAL','COMPANY')))continue;
			$job_id=$USER_INPUT['PARAMS'][$I+1];
			$res=runQuery("SELECT time_end,job_status,job_cluster_id,web_job_id FROM web_job where md5id = '".$job_id."'");
			if ($res==array()) 
			{
				$MODULE_DATA[$USER_INPUT['PARAMS'][$I]]=array('STATUS'=>'Not found');
				++$I;
				continue;
			}
			if ($res[0]['JOB_CLUSTER_ID']=='')
			{
				$MODULE_DATA[$USER_INPUT['PARAMS'][$I]]=array('STATUS'=>'Submitted');
				++$I;
				continue;

			}
			if ($res[0]['TIME_END']=='')
			{
				$MODULE_DATA[$USER_INPUT['PARAMS'][$I]]=array('STATUS'=>'Running');
				++$I;
				continue;
			}
			$STATUS=json_decode($res[0]['JOB_STATUS'],true);
			echo $job_id;print_r($STATUS);
			if ($STATUS['STATUS']=='Running')
			{
				
				$MODULE_DATA[$USER_INPUT['PARAMS'][$I]]=array('STATUS'=>'Running');
				++$I;
				continue;
				
			}
			else if ($STATUS['STATUS']!='Success')
			{
				$MODULE_DATA[$USER_INPUT['PARAMS'][$I]]=array('STATUS'=>'Job failed');
				++$I;
				continue;
			}
			else $MODULE_DATA[$USER_INPUT['PARAMS'][$I]]=array('STATUS'=>'Success');
			$res_d=runQuery("SELECT document_name,document_description,document_content FROM web_job_document where web_job_id = ".$res[0]['WEB_JOB_ID']);
			foreach ($res_d as &$F)
			{
				$F['DOCUMENT_CONTENT']=stream_get_contents($F['DOCUMENT_CONTENT']);
				$MODULE_DATA[$USER_INPUT['PARAMS'][$I]]['RESULTS']=json_decode($F['DOCUMENT_CONTENT'],true);
			}
		}
		

	}
	else if  ($USER_INPUT['PARAMS'][0]=='NEWSID')	
	{
		
		$ALLOWED=array('DRUG','CLINVAR','PATHWAY','GENE','DISEASE','PROT_FEAT','ASSAY','CELL','TISSUE','EVIDENCE','CLINICAL','COMPANY','NEWS');
		if (hasPrivateAccess())
		{
			$MODULE_DATA=private_getNewsByHash($USER_INPUT['PARAMS'][1],true);
			foreach ($ALLOWED as $TYPE)$MODULE_DATA['TAGS'][$TYPE]=private_getNewsInfo($USER_INPUT['PARAMS'][1],$TYPE);
		}
		else
		{
		$MODULE_DATA=getNewsByHash($USER_INPUT['PARAMS'][1],true);
		foreach ($ALLOWED as $TYPE)$MODULE_DATA['TAGS'][$TYPE]=getNewsInfo($USER_INPUT['PARAMS'][1],$TYPE);
		}
		$MODULE_DATA['EDIT_MODE']=true;
		$MODULE_DATA['HASH']=$USER_INPUT['PARAMS'][1];
		unset($MATCH['RUN']);
		
	}
	
}

$MODULE_DATA['SOURCES']=getNewsSources();
}catch(Exception $e)
{
	$MODULE_DATA['ERROR']=$e->getMessage();
}
?>