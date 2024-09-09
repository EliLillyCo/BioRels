<?php


$STR_SEL='';
$STR='';
$N=0;


if (isset($MODULE_DATA['INFO']['OMIM']))
{

	foreach ($MODULE_DATA['INFO']['OMIM'] as $OM=>$TXT)
		{
			$tab=explode("-",$OM);
			$GROUPS[trim($tab[0])][]=$OM;
		}
		$N+=count($GROUPS);
			foreach ($GROUPS as $G=>&$LIST_BLOCK)
			$STR_SEL.='<option value="OMIM_'.$G.'">OMIM Record - '.$G.'</option>';
		
		foreach ($GROUPS as $G=>&$LIST_BLOCK)
		{
			$STR.='<div id="OMIM_'.$G.'" style="display:none">';
			foreach ($MODULE_DATA['INFO']['OMIM'] as $OM=>$TXT)
			{
				$tab=explode("-",$OM);
				$STR.='<h4 style="margin-top:15px;margin-bottom:5px;">'.$tab[1].':</h4>'.convertBiorelsTags($TXT).'<br/>';
			}
			$STR.='</div>';
		}
}


$ALLOWED=array('FUNCTION','DISEASE','CATALYTIC ACTIVITY','SEQUENCE CAUTION','SIMILARITY','SUBCELLULAR LOCATION','SUBUNIT','TISSUE SPECIFICITY');
if (isset($MODULE_DATA['UNIPROT_DESC']))
{
	++$N;
	$STR_SEL.='<option value="UNIPROT_DESC">UniProt (Protein description)</option>';
	$STR.='<div id="UNIPROT_DESC" style="display:none">';
	foreach ($MODULE_DATA['UNIPROT_DESC'] as $NAME=>$DESC)

	{
		if (!in_array($NAME,$ALLOWED))continue;
		$STR.='<h4 style="margin-top:15px;margin-bottom:5px;">'.ucfirst(strtolower($NAME)).'</h4>';
	 	$STR.=convertUniprotText($DESC);
	}
	$STR.='</div>';
}

if (isset($MODULE_DATA['DESCRIPTION']))
	{
	
			$STR_SEL.='<option value="GeneReviews">Gene Reviews</option>';
			$STR.='<div id="GeneReviews" style="display:none">';
		++$N;
		$STR.=$MODULE_DATA['DESCRIPTION']['ABSTRACT'];


		if (isset($MODULE_DATA['INFO']['DOCS']))
		{
			$STR.='<br/><br/><h5>Learn more:</h5>';
			foreach ($MODULE_DATA['INFO']['DOCS'] as $HASH=>$LIST)
			foreach ($LIST as $MIME=>&$D)
			{
				if ($MIME!='application/pdf')continue;
				if ($D['SOURCE_NAME']=='Liver Tox')
				$STR.='<a target="_blank" href="/NEWS_FILE/'.$D['DOCUMENT_HASH'].'"><img style="width:120px" src="https://www.ncbi.nlm.nih.gov/corehtml/pmc/pmcgifs/bookshelf/thumbs/th-livertox-lrg.png"/></a>';
				else
				$STR.='<a target="_blank"  href="/NEWS_FILE/'.$D['DOCUMENT_HASH'].'"><img style="width:120px"  src="https://www.ncbi.nlm.nih.gov/corehtml/pmc/pmcgifs/bookshelf/thumbs/th-gene-lrg.png"/></a>';		
			}
		}
		$STR.='</div>';
	}

	if ($N>1)
	{
		changeValue("gene_description","MARGIN","5");
		changeValue("gene_description",'OPTS',$STR_SEL);
	}else changeValue("gene_description","MARGIN","44");
	if ($STR!='')changeValue("gene_description","DESC",$STR);
	else changeValue("gene_description","DESC",'No description available');



?>