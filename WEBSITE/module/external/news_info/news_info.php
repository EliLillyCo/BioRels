<?php


$STR='';
switch ($MODULE_DATA['TYPE'])
    {
        case 'DRUG':
			$STR.='<table class="table table-sm w3-striped w3-bordered w3-border w3-hoverable w3-white" >
			<thead><tr><th>Drug Name</th><th>Structure</th><th>Is Approved</th><th>Max clinical Phase</th>
			</tr></thead><tbody>';
			//<th style="max-width:60%;width:60%">Description</th>
			$STR_I='';
			
             foreach ($MODULE_DATA['RESULT'] as $G=>$R)
			 {

				$STR.='<tr><td><a href="/DRUG/'.$R['DRUG_PRIMARY_NAME'].'" target="_blank">'.$R['DRUG_PRIMARY_NAME'].'</a></td>';
				if (isset($R['SM']))
				{
					foreach ($R['SM'] as &$SM)
					{
						if ($SM['FULL_SMILES']=='')continue;
						$ID=md5($MODULE_DATA['PMID'].'_'.$R['DRUG_PRIMARY_NAME']);
						$STR.='<td id="P_CPD_IMG_'.$ID.'" style="margin:0 auto;width:fit-content">
						<div id="CPD_IMG_'.$ID.'" style="margin:0 auto;width:fit-content"></div></td>';
						$STR_I.='getCompoundImage("'.$SM['FULL_SMILES'].'","CPD_IMG_'.$ID.'",200 );'."\n";
						break;
					}
				}
				else $STR.='<td>No image</td>';
				$STR.='<td>'.(($R['IS_APPROVED']=='T')?'Yes':'No').'</td><td>'.$R['MAX_CLIN_PHASE'].'</td>';
				$DESC=getDrugDescription($G);
				$DESC_STR='';
				foreach ($DESC['DESCRIPTION'] as &$E_DESC)
				{
					if ($E_DESC['SOURCE_NAME']=='DrugBank' && $E_DESC['TEXT_TYPE']=='Complete')
					$DESC_STR=preg_replace('/\[[A-Z0-9, ]{1,100}\]/','', $E_DESC['TEXT_DESCRIPTION']).'<br/><span style="font-weight:bold">Source: </span> DrugBank';
				}

				if ($DESC_STR=='')
				foreach ($DESC['DESCRIPTION'] as &$E_DESC)
				{
					if ($E_DESC['SOURCE_NAME']=='OpenTargets')
					$DESC_STR=$E_DESC['TEXT_DESCRIPTION'].'<br/><span style="font-weight:bold">Source: </span> OpenTargets';
				}
				$STR.='</tr><tr style="border-top: hidden;
				border-bottom: 3px solid grey;"><td colspan="4"><span style="font-weight:bold; margin-right:10px">Description:</span><span style="text-align:justify; line-height:1.5em">'.$DESC_STR."</span></td></tr>";
				
			 }

			 

			
			 $STR.='</tbody></table>';
			 changeValue("news_info","LIST_IMGS",$STR_I);
            break;
            
        case 'CLINVAR':
            break;
        case 'PATHWAY':
            break;
			case 'COMPANY':
				$STR.='<table class="table table-sm w3-striped w3-bordered w3-border w3-hoverable w3-white" >
				<thead><tr><th>Company name</th><th>Company type</th></tr></thead><tbody>';
				foreach ($MODULE_DATA['RESULT'] as $COMPANY_NAME=>&$INFO)
				{
					$STR.='<tr><td>'.$COMPANY_NAME.'</td><td>'.$INFO['COMPANY_TYPE']
					.'</td></tr>';
					
				}
				$STR.='</tbody></table>'."\n";
				break;
		case 'CLINICAL':
			$STR.='<table class="table table-sm w3-striped w3-bordered w3-border w3-hoverable w3-white" >
			<thead><tr><th>Trial ID</th><th>Clinical Phase</th><th>Status</th><th>Title</th></tr></thead><tbody>';
			foreach ($MODULE_DATA['RESULT'] as $TRIAL_ID=>&$INFO)
			{
				$STR.='<tr><td ><a href="/CLINICAL_TRIAL/'.$TRIAL_ID.'" target="_blank">'.$TRIAL_ID.'</a></td><td>'.$INFO['CLINICAL_PHASE']
				.'</td><td>'.$INFO['CLINICAL_STATUS']
				.'</td><td>'.$INFO['OFFICIAL_TITLE']
				.'</td></tr><tr style="    border-top: hidden;border-bottom: 3px solid grey;"><td colspan="4"><span style="font-weight:bold; margin-left:20px;margin-right:10px">Description:</span><span style="text-align:justify; line-height:1.5em">'.$INFO['BRIEF_SUMMARY']
				.'</span></td></tr>';
				
			}
			$STR.='</tbody></table>'."\n";
			break;
		
		case 'EVIDENCE':
			
			
			foreach ($MODULE_DATA['RESULT']['EVIDENCE'] as $DS_TAG=>&$GSL)
			{
				$DS_INFO=&$MODULE_DATA['RESULT']['DISEASE'][$DS_TAG];
				$STR.='<h4 style="text-align:center;width:100%;font-weight:bold">'.$DS_INFO['NAME'].'</h4>';
				$STR.='<table class="table table-sm w3-striped w3-bordered w3-border w3-hoverable w3-white" >
			<thead><tr><th>Section</th><th>Content</th></tr></thead>
			<tbody>';
				foreach ($GSL as $GID=>&$LIST)
				{
					$GENE_INFO=&$MODULE_DATA['RESULT']['GENE'][$GID];
					$STR.='<tr><th colspan="2" style="text-align:center;font-size:1em">'.$GENE_INFO['SYMBOL'].' ('.$GENE_INFO['NAME'].')'.'</span></th></tr>'."\n";
					foreach ($LIST as $TYPE=>&$LIST_C)
					{
						$STR.='<tr><td rowspan = "'.count($LIST_C).'">';
						
						switch ($TYPE)
						{
							case 'o':$STR.='Other:';break;
							case 'r':$STR.='Results:';break;
							case 'a':$STR.='Abstract:';break;
							case 'i':$STR.='Introduction:';break;
							case 'm':$STR.='Methods:';break;
							case 't':$STR.='Title:';break;
							case 'f':$STR.='Figure:';break;
							case 'c':$STR.='Conclusion:';break;
							case 'd':$STR.='Discussion:';break;
							case 's':$STR.='Supplementary:';break;
						}
						$STR.='</td><td>'.$LIST_C[0].'</td></tr>'."\n";
						for ($I=1;$I<count($LIST_C);++$I)
						$STR.='<tr><td>'.$LIST_C[$I].'</td></tr>'."\n";
					}

				}
				$STR.='</tbody></table>'."\n";
			}
			
			
			break;
			
				
			case 'NEWS':
				foreach ($MODULE_DATA['RESULT'] as $R_TYPE=>&$R_LIST)
				{
					if ($R_TYPE=='PARENT')
					$STR.='<h4 style="text-align:center;width:100%;font-weight:bold">Related article:</h4>';
				else $STR.='<h4 style="text-align:center;width:100%;font-weight:bold">Other articles Mentioning this article:</h4>';
				$STR.='<table class="table table-sm w3-striped w3-bordered w3-border w3-hoverable w3-white" style="vertical-align:middle;text-align:justify;">
				<thead><tr><th>Title</th><th>Source</th><th>Date</th></tr></thead><tbody>';
				
				 foreach ($R_LIST as $G=>$R)
				 {
					$STR.='<tr><td style="vertical-align:middle;text-align:center"><a href="/NEWS_CONTENT/PARAMS/newsTitle/'.$G.'" target="_blank">'.$R['NEWS_TITLE'].'</a></td>
					<td style="vertical-align:middle;text-align:center">';
					
					 $STR.=$R['SOURCE_NAME'];
					$STR.='</td><td style="vertical-align:middle;text-align:center">'.$R['NEWS_RELEASE_DATE'].'</td></tr>';
	
				 }
				 $STR.='</tbody></table>';
				}
			
				break;
			
        case 'GENE':
			$STR.='<table class="table table-sm w3-striped w3-bordered w3-border w3-hoverable w3-white" style="vertical-align:middle;text-align:justify;"><thead><tr><th>Symbol</th><th>Gene ID</th><th>Name</th></tr></thead><tbody>';
             foreach ($MODULE_DATA['RESULT'] as $G=>$R)
			 {
				$STR.='<tr><td style="vertical-align:middle;text-align:center"><a href="/GENEID/'.$G.'" target="_blank">'.$R['SYMBOL'].'</a>';
				
				$STR.='</td><td style="vertical-align:middle;text-align:center"><a href="/GENEID/'.$G.'" target="_blank">'.$G.'</a></td><td style="vertical-align:middle;text-align:center">'.$R['NAME'].'</td></tr>';
				if ($R['DESCRIPTION'][0]!='')
				{
				$STR.='<tr    style="border-top: hidden;border-bottom: 3px solid grey;"><td colspan="4"><span style="font-weight:bold; margin-left:20px;margin-right:10px">Description:</span><span style="text-align:justify; line-height:1.5em">';
				
				$STR.=convertUniprotText(array($R['DESCRIPTION'][0]));
				$STR.="</span></td></tr>";
				}

			 }
			 $STR.='</tbody></table>';
			 break;
        case 'DISEASE':
            $STR.='<table class="table table-sm w3-striped w3-bordered w3-border w3-hoverable w3-white" style="vertical-align:middle;text-align:justify;"><thead>
			<tr><th>Disease Tag</th><th>Name</th><th style="max-width:60%;width:60%">Description</th></tr></thead><tbody>';
             foreach ($MODULE_DATA['RESULT'] as $G=>$R)
			 {
				$STR.='<tr><td style="vertical-align:middle;text-align:center"><a href="/DISEASE/'.$G.'" target="_blank">'.$R['DISEASE_TAG'].'</a></td>
				<td style="vertical-align:middle;text-align:center"><a href="/DISEASE/'.$G.'" target="_blank">'.$R['DISEASE_NAME'].'</a></td>
				<td style="vertical-align:middle;text-align:center">'.$R['DISEASE_DEFINITION'].'</td></tr>';

			 }
			 $STR.='</tbody></table>';
            break;
        case 'PROT_FEAT':
			
			//FEAT_NAME,PROT_IDENTIFIER,FEAT_VALUE,ISO_ID 
			$STR.='<table class="table table-sm w3-striped w3-bordered w3-border w3-hoverable w3-white" style="vertical-align:middle;text-align:justify;"><thead>
			<tr><th>Gene</th><th>Gene ID</th><th>Protein Entry</th><th>Feature name</th><th>Value</th><th>Sequence</th></tr></thead><tbody>';
             foreach ($MODULE_DATA['RESULT'] as $G=>$R)
			 {
				$STR.='<tr><th>'.$R['SYMBOL'].'</th><td>'.$R['GENE_ID'].'</td><td>'.$R['PROT_IDENTIFIER'].'</td><td>'.$R['FEAT_NAME'].'</td><td>'.$R['FEAT_VALUE'].'</td><td>'.$R['ISO_ID'].'</td></tr>';

			 }
			 $STR.='</tbody></table>';
            break;
        case 'ASSAY':
            break;
        case 'CELL':
            break;
        case 'TISSUE':
			$STR.='<table class="table table-sm w3-striped w3-bordered w3-border w3-hoverable w3-white" style="vertical-align:middle;text-align:justify;">
			<thead>
			<tr>
			<th>Tag</th><th>Tissue Name</th><th style="max-width:60%;width:60%">Description</th></tr></thead>
			<tbody>';
             foreach ($MODULE_DATA['RESULT'] as $G=>$R)
			 {
				$STR.='<tr><td style="vertical-align:middle;text-align:center"><a href="/TISSUE/'.$G.'" target="_blank">'.$G.'</a></td>
				<td style="vertical-align:middle;text-align:center"><a href="/TISSUE/'.$G.'" target="_blank">'.$R['ANATOMY_NAME'].'</a></td>
				<td>'.$R['ANATOMY_DEFINITION'].'</td></tr>';
				

			 }
			 $STR.='</tbody></table>';
            break;
    }
	changeValue("news_info","INFO",$STR);

?>