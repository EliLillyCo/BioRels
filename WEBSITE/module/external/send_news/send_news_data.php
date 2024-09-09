<?php

try{
	//print_r($USER_INPUT['PARAMS']);
	

if (!isset($_SESSION['USER_DATA']))
{
	$_SESSION['USER_DATA']=getUserGroups($USER['id']);

}

ini_set('memory_limit','2000M');

	


//echo '<pre>';print_r($USER_INPUT);exit;
		$TAGS=array('GENE_PRIMARY'=>array(),'CLINICAL_PRIMARY'=>array(),'DISEASE_PRIMARY'=>array(),'COMPANY_PRIMARY'=>array(),'DRUG_PRIMARY'=>array());
	//echo "#######\n#####\n";	print_r($USER_INPUT['PARAMS']);
		$NEWS=array('USER_ID'=>$USER['DB_ID'],'NEWS_CONTENT'=>'','NEWS_HTML'=>'','TITLE'=>'','SOURCE'=>-1,'EMAIL'=>array());	
		$IS_RED=true;
		$IS_GREEN=true;
		$EMAIL_INFO=array();
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
				case 'delta_v':
					$I++;
					
					$NEWS['NEWS_CONTENT'] =htmlspecialchars_decode($USER_INPUT['PARAMS'][$I]);
					
					break;
				case 'html_v':
					$I++;

					$NEWS['NEWS_HTML']=htmlspecialchars_decode($USER_INPUT['PARAMS'][$I]);
					
					break;
				
				case 'source_name':
					$I++;
					$NEWS['SOURCE']=array_flip($MODULE_DATA['SOURCES'])[$USER_INPUT['PARAMS'][$I]];
					$NEWS['SOURCE_NAME']=$USER_INPUT['PARAMS'][$I];
					break;
				case 'email':
						$I++;
						$NEWS['EMAIL']=$USER_INPUT['PARAMS'][$I];
						break;
						
				
					case 'news':
						$I++;
						$TAGS['NEWS_REL']=$USER_INPUT['PARAMS'][$I];
						break;
				
			}
		}
		
		
		if (isset($NEWS['EMAIL'])&& $NEWS['EMAIL']!=array())
		{
			
			$PARAMS_EMAIL=array('email'=>array(),'id'=>$USER_INPUT['PAGE']['VALUE'],'html'=>$NEWS['NEWS_HTML']);
			foreach ($NEWS['EMAIL'] as $email_grp)
			{

				foreach ($GLB_CONFIG['GLOBAL']['EMAIL_GROUP'] as $GRPN=>$GRP_INFO)
				{
					if ($GRP_INFO[0]!=$email_grp)continue;
					$PARAMS_EMAIL['email'][]=$GRP_INFO;
					
				}
				
			}
			
			if ($PARAMS_EMAIL['email']!=array())
			{
				$MODULE_DATA=array();
			$MD5_EMAIL=submitJob('send_newsmail',$PARAMS_EMAIL,'news','news');
			if ($MD5_EMAIL!==false)$EMAIL_INFO['EMAIL_JOB']=$MD5_EMAIL;
			else $EMAIL_INFO['EMAIL_FAIL']=true;
			foreach ($EMAIL_INFO as $K=>$V)$MODULE_DATA[$K]=$V;
			return;
			}
			
		}
			

// echo '<pre>';
// //print_R($MODULE_DATA);
// 	exit;
$MATCH=array();

	$MATCH['RUN']=true;
		$ALLOWED=array('DRUG','CLINVAR','PATHWAY','GENE','DISEASE','PROT_FEAT','ASSAY','CELL','TISSUE','EVIDENCE','CLINICAL','COMPANY','NEWS');
		
		if (hasPrivateAccess())
		{
			$MODULE_DATA=private_getNewsByHash($USER_INPUT['PAGE']['VALUE'],true,true);
			foreach ($ALLOWED as $TYPE)$MODULE_DATA['TAGS'][$TYPE]=private_getNewsInfo($USER_INPUT['PAGE']['VALUE'],$TYPE);
		}
		else 
		{
		$MODULE_DATA=getNewsByHash($USER_INPUT['PAGE']['VALUE'],true,true);
		foreach ($ALLOWED as $TYPE)$MODULE_DATA['TAGS'][$TYPE]=getNewsInfo($USER_INPUT['PAGE']['VALUE'],$TYPE);
		}
		$MODULE_DATA['EDIT_MODE']=true;
		$MODULE_DATA['HASH']=$USER_INPUT['PAGE']['VALUE'];
		unset($MATCH['RUN']);
		$HAS_ANNOTS=false;
		$MODULE_DATA['TAGS_HTML']=genTags($MODULE_DATA['TAGS'],$HAS_ANNOTS);
		
		$MODULE_DATA['HAS_ANNOTS']=$HAS_ANNOTS;
 $MODULE_DATA['SOURCES']=getFullNewsSources();
 foreach ($EMAIL_INFO as $K=>$V)$MODULE_DATA[$K]=$V;
}catch(Exception $e)
{
	$MODULE_DATA['ERROR']=$e->getMessage();
}


function getCompoundImage($smiles,$width=600)
{
    
    $url='https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/smiles/'.$smiles.'/PNG?image_size='.$width.'x'.$width;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$output = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if ($httpcode!=200)return null;
	return 'data:image/png;base64,'.base64_encode($output);
    
}


function genTags(&$LIST,&$HAS_ANNOTS)
{

$STR='<h1 style="width:100%;text-align:center">BioRels Annotations</h1>';
foreach ($LIST as $TYPE=>&$LIST_TAGS)
{
    if ($LIST_TAGS==array())
continue;
switch($TYPE)
    {
        case 'DRUG':
			$STR.='<h2 style="font-weight:bold;margin-top:15px;margin-bottom:10px"> Drugs:</h2>
			<table style="border-collapse: collapse; width: 100%;  max-width: 100%; margin-bottom: 10px;  background-color: transparent; color: #000 !important;background-color: #fff !important;font-family: "Nunito", sans-serif !important;" >
			<thead><tr style="border-bottom: 1px solid #ddd;text-align:center"><th>Drug Name</th><th>Structure</th><th>Is Approved</th><th>Max clinical Phase</th></tr></thead><tbody>';
			
            $N_L=0;
             foreach ($LIST_TAGS as $G=>$R)
			 {
                ++$N_L;
				$HAS_ANNOTS=true;
                //print_r($R);
				$STR.='<tr style="border-bottom: 1px solid #ddd ; background-color: #f1f1f1;text-align:center">
                <td style=" padding:3px; vertical-align: middle; border-top: 1px solid #dee2e6;">
                <a href="/DRUG/'.$R['DRUG_PRIMARY_NAME'].'" target="_blank">'.$R['DRUG_PRIMARY_NAME'].'</a></td>';
				
                if (isset($R['SM']))
				{
					foreach ($R['SM'] as &$SM)
					{
                        
						if ($SM['FULL_SMILES']=='')continue;
						
						$STR.='<script> $(document).ready(function () {getCompoundImage("'.$SM['FULL_SMILES'].'","CPD_IMG_'.$N_L.'" ,Math.max(300,parseFloat($("#P_CPD_IMG_'.$N_L.'").css("width"))));});</script>'."\n";
					// 	$IMG=getCompoundImage($SM['FULL_SMILES']);
                    // if ($IMG!=null){
				$STR.='<td id="P_CPD_IMG_'.$N_L.'" style="margin:0 auto;width:fit-content;text-align:center">
				<div id="CPD_IMG_'.$N_L.'" style="margin:0 auto;width:fit-content"></div></td>';
				break;
					}
                }else $STR.='<td style=" padding:3px; vertical-align: middle; border-top: 1px solid #dee2e6;">No image</td>';
					
					
				
               
				
				$STR.='<td style=" padding:3px; vertical-align: middle; border-top: 1px solid #dee2e6;">'.(($R['IS_APPROVED']=="T")?"Yes":"No").'</td>
                <td style=" padding:3px; vertical-align: middle; border-top: 1px solid #dee2e6;">'.$R['MAX_CLIN_PHASE'].'</td></tr>';
                $DESC=getDrugDescription($G);
				$DESC_STR='';
				foreach ($DESC['DESCRIPTION'] as &$E_DESC)
				{
					if ($E_DESC['SOURCE_NAME']=='DrugBank' && $E_DESC['TEXT_TYPE']=='Complete' && $E_DESC['TEXT_DESCRIPTION']!='')
					$DESC_STR=preg_replace('/\[[A-Z0-9, ]{1,100}\]/','', $E_DESC['TEXT_DESCRIPTION']).'<br/><span style="font-weight:bold">Source: </span> DrugBank';
				}

				if ($DESC_STR=='')
				foreach ($DESC['DESCRIPTION'] as &$E_DESC)
				{
					if ($E_DESC['text_description']=='')$E_DESC['text_description']='No description';
					if ($E_DESC['SOURCE_NAME']=='OpenTargets')
					$DESC_STR=$E_DESC['text_description'].'<br/><span style="font-weight:bold">Source: </span> OpenTargets';
				}
				$STR.='</tr><tr style="border-top: hidden;
				border-bottom: 3px solid grey;"><td colspan="4"><span style="font-weight:bold; margin-right:10px">Description:</span>
                <span style="text-align:justify; line-height:1.5em">'.$DESC_STR."</span></td></tr>";
				
				
			 }

			
			 $STR.='</tbody></table>';
			
            break;
            
		case 'CLINICAL':
            $N_L=0;
			$STR.='<h2 style="font-weight:bold;margin-top:15px;margin-bottom:10px"> Clinical:</h2><table style="border-collapse: collapse; width: 100%;  max-width: 100%; margin-bottom: 10px;  background-color: transparent; color: #000 !important;background-color: #fff !important;font-family: "Nunito", sans-serif !important;text-align:center;" >
			<thead><tr style="border-bottom: 1px solid #ddd;text-align:center"><th>Trial ID</th><th>Clinical Phase</th><th>Status</th><th>Title</th></tr></thead><tbody>';
			foreach ($LIST_TAGS as $TRIAL_ID=>&$INFO)
			{
				$HAS_ANNOTS=true;
                ++$N_L;
				$STR.='<tr style="border-bottom: 1px solid #ddd'.(($N_L%2==0)?' ;background-color: #f1f1f1':'').';text-align:center"><td style=" padding:3px; vertical-align: top; border-top: 1px solid #dee2e6;"><a href="CLINICAL_TRIAL/'.$TRIAL_ID.'" target="_blank">'.$TRIAL_ID.'</a></td>
                <td style=" padding:3px; vertical-align: top; border-top: 1px solid #dee2e6;">'.$INFO['CLINICAL_PHASE']
				.'</td><td style=" padding:3px; vertical-align: top; border-top: 1px solid #dee2e6;">'.$INFO['CLINICAL_STATUS']
				.'</td><td style=" padding:3px; vertical-align: top; border-top: 1px solid #dee2e6;text-align:justify; line-height:1.5em">'.$INFO['OFFICIAL_TITLE']
				.'</td></tr>
                <tr style="    border-top: hidden;border-bottom: 3px solid grey;"><td colspan="4" style="text-align:justify; line-height:1.5em"><span style="font-weight:bold; margin-left:20px;margin-right:10px">Description:<br/></span>'.convertion($INFO['BRIEF_SUMMARY']).'</td></tr>';
                
				
			}
			$STR.='</tbody></table>'."\n";
			break;
		
       
			
			
			
        case 'GENE':
            $N_L=0;
			$STR.='<h2 style="font-weight:bold;margin-top:15px;margin-bottom:10px"> Gene:</h2><table style="border-collapse: collapse; width: 100%;  max-width: 100%; margin-bottom: 10px;  background-color: transparent; color: #000 !important;background-color: #fff !important;font-family: "Nunito", sans-serif !important;;vertical-align:middle;text-align:justify;">
            <thead><tr style="border-bottom: 1px solid #ddd;text-align:center"><th>Symbol</th><th>Gene ID</th><th>Name</th></tr></thead><tbody>';
             foreach ($LIST_TAGS as $G=>$R)
			 {
                ++$N_L;
				$HAS_ANNOTS=true;
				$STR.='<tr style="border-bottom: 1px solid #ddd;background-color: #f1f1f1;text-align:center">
                <td style="vertical-align:middle;text-align:center; border-top: 1px solid #dee2e6;">
                    <a href="GENEID/'.$G.'" target="_blank">'.$R['SYMBOL'].'</a></td>
                <td style="vertical-align:middle;text-align:center; border-top: 1px solid #dee2e6;"><a href="/GENEID/'.$G.'" target="_blank">'.$G.'</a></td>
                <td style="vertical-align:middle;text-align:center; border-top: 1px solid #dee2e6;">'.$R['NAME'].'</td></tr>';
                $STR.='<tr style="    border-top: hidden;border-bottom: 3px solid grey;"><td colspan="4" style="text-align:justify; line-height:1.5em"><span style="font-weight:bold; margin-left:20px;margin-right:10px">Description:</span>';
				if ($R['DESCRIPTION'][0]!='')
                {$STR.=convertUniprotText(array($R['DESCRIPTION'][0]));
                }
				$STR.='</td></tr>';

			 }
			 $STR.='</tbody></table>';
            break;
        case 'DISEASE':
            $N_L=0;
            $STR.='<h2 style="font-weight:bold;margin-top:15px;margin-bottom:10px"> Disease:</h2><table style="border-collapse: collapse; width: 100%;  max-width: 100%; margin-bottom: 10px;  background-color: transparent; color: #000 !important;font-family: "Nunito", sans-serif !important;;background-color: #fff !important vertical-align:middle;text-align:justify;"><thead>
			<tr style="border-bottom: 1px solid #ddd;text-align:center"><th>Disease Tag</th><th>Name</th></tr></thead><tbody>';
             foreach ($LIST_TAGS as $G=>$R)
			 {
                ++$N_L;
				$HAS_ANNOTS=true;
				$STR.='<tr style="border-bottom: 1px solid #ddd;text-align:center;background-color: #f1f1f1">
                <td style="vertical-align:middle;text-align:center"><a href="/DISEASE/'.$G.'" target="_blank">'.$R['DISEASE_TAG'].'</a></td>
				<td style="vertical-align:middle;text-align:center"><a href="/DISEASE/'.$G.'" target="_blank">'.$R['DISEASE_NAME'].'</a></td></tr>';
                $STR.='<tr style="    border-top: hidden;border-bottom: 3px solid grey;"><td colspan="3" style="text-align:justify; line-height:1.5em"><span style="font-weight:bold; margin-left:20px;margin-right:10px">Description:</span>';
				$STR.=$R['DISEASE_DEFINITION'].'</td></tr>';

			 }
			 $STR.='</tbody></table>';
            break;
		case 'NEWS':
			if (!isset($LIST_TAGS['PARENT']))break;
			$N_L=0;
			$STR.='<h2 style="font-weight:bold;margin-top:15px;margin-bottom:10px"> Related news:</h2>
			<table style="border-collapse: collapse; width: 100%;  max-width: 100%; margin-bottom: 10px;  background-color: transparent; color: #000 !important;font-family: "Nunito", sans-serif !important;;background-color: #fff !important vertical-align:middle;text-align:justify;">
			<thead>
			<tr style="border-bottom: 1px solid #ddd;text-align:center"><th>Author</th><th>Date</th><th>Title</th></tr></thead><tbody>';
			$STR_JS='<script>$(document).ready(function () {';
				$STR_JS2='';
             foreach ($LIST_TAGS['PARENT'] as $G=>$R)
			 {
                ++$N_L;
				$HAS_ANNOTS=true;
				
				$STR.='<tr style="border-bottom: 1px solid #ddd;text-align:center;background-color: #f1f1f1">
                <td style="vertical-align:middle;text-align:center"><h5>'.$R['LAST_NAME'].' '.$R['FIRST_NAME'].'</h5><p>'.$R['BUSINESS_TITLE'].'</p></td>
				<td style="vertical-align:middle;text-align:center">'.$R['NEWS_RELEASE_DATE'].'</a></td>
				<td style="vertical-align:middle;text-align:center"><a href="/NEWS_CONTENT/PARAMS/newsTitle/'.$R['NEWS_HASH'].'" target="_blank">'.$R['NEWS_TITLE'].'</a></td></tr>';
               

			 }
			 
			 
			 $STR.='</tbody></table>'.$STR_JS.'});'.$STR_JS2.'</script>';
			 break;
       
    }
}
    return $STR;
}


function convertion($str)
{
$res=explode("\n",$str);
$UL_OPEN=false;
foreach ($res as &$line)
{

	if (substr($line,0,1)=='*')
	{
		
		if (!$UL_OPEN)
		{
			$UL_OPEN=true;
$line='<ul><li>'.substr($line,1).'</li>';
		}
		else $line='<li>'.substr($line,1).'</li>';
	}
	if ($line=='' && $UL_OPEN){$line='</ul>';$UL_OPEN=false;}
}
if ($UL_OPEN)$res[]= '</ul>';

return str_replace('\\','',str_replace('</li><br/>','</li>',implode("<br/>",$res)));
}

?>