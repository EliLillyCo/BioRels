<?php
if (!defined("BIORELS")) header("Location:/");




if (!isset($MODULE_DATA['ASSAY_INFO']))
{
	removeBlock("assay_portal","VALID");
	return;
}
else removeBlock("assay_portal","INVALID");


$DATA_TTL=array(0=>$MODULE_DATA['ASSAY_INFO']['BIOASSAY_DEFINITION'],
				1=>$MODULE_DATA['ASSAY_INFO']['ASSAY_TARGET_TYPE_DESC'],
				2=>$MODULE_DATA['ASSAY_INFO']['CONFIDENCE_DESCRIPTION'],
				3=>$MODULE_DATA['ASSAY_INFO']['ANATOMY_DEFINITION'],);
$DATA_CO=0;


if ($MODULE_DATA['ASSAY_INFO']['CELL_DONOR_SEX']=='F')$MODULE_DATA['ASSAY_INFO']['CELL_DONOR_SEX']='Female';
else if ($MODULE_DATA['ASSAY_INFO']['CELL_DONOR_SEX']=='M')$MODULE_DATA['ASSAY_INFO']['CELL_DONOR_SEX']='Male';
$MODULE_DATA['ASSAY_INFO']['ASSAY_TARGET_TYPE_NAME']=ucfirst(strtolower($MODULE_DATA['ASSAY_INFO']['ASSAY_TARGET_TYPE_NAME']));
$MODULE_DATA['ASSAY_INFO']['BIOASSAY_LABEL']=ucfirst(strtolower($MODULE_DATA['ASSAY_INFO']['BIOASSAY_LABEL']));

$LIST=array('ASSAY_DESCRIPTION','ASSAY_NAME','ASSAY_TYPE','ASSAY_CATEGORY','BIOASSAY_LABEL','ASSAY_TARGET_LONGNAME','MUTATION_LIST','ASSAY_TARGET_TYPE_NAME','SCIENTIFIC_NAME','SCORE_CONFIDENCE','TAX_ID','SOURCE_NAME','ASSAY_CELL_NAME','ASSAY_CELL_DESCRIPTION','ASSAY_CELL_SOURCE_TISSUE','CELL_ACC','CELL_NAME','CELL_TYPE','CELL_DONOR_SEX','CELL_DONOR_AGE','CELL_VERSION',
'ASSAY_TISSUE_NAME');
foreach ($LIST as $T)
{
	if ($T=='ASSAY_TARGET_LONGNAME')
	{
		$MODULE_DATA['ASSAY_INFO'][$T]=str_replace("/","<br/>",$MODULE_DATA['ASSAY_INFO'][$T]);
	}
	if ($MODULE_DATA['ASSAY_INFO'][$T]=='')changeValue("assay_portal",$T,'N/A');
	else changeValue("assay_portal",$T,$MODULE_DATA['ASSAY_INFO'][$T]);
}

if (strtolower($MODULE_DATA['ASSAY_INFO']['SOURCE_NAME'])=='chembl')
{
	changeValue("assay_portal","CHEMBL_LINK",str_replace('${LINK}',$MODULE_DATA['ASSAY_INFO']['ASSAY_NAME'], $GLB_CONFIG['LINK']['CHEMBL']['ASSAY']));
}


if ($MODULE_DATA['ASSAY_INFO']['CELL_ACC']=='')removeBlock("assay_portal","HAS_CELL_DATA");
else 
{
	changeValue("assay_portal","ALT_CELL_NAME","(Official: ".$MODULE_DATA['ASSAY_INFO']['CELL_NAME'].")");
}
if ($MODULE_DATA['ASSAY_INFO']['ASSAY_CELL_NAME']=='')removeBlock("assay_portal","HAS_CELL_LINE");
else removeBlock("assay_portal","NO_CELL_LINE");


if ($MODULE_DATA['ASSAY_INFO']['ANATOMY_NAME']!='')changeValue("assay_portal","ALT_TISSUE_NAME","(Official: <a href='/TISSUE/".$MODULE_DATA['ASSAY_INFO']['ANATOMY_NAME'].'\'>'.$MODULE_DATA['ASSAY_INFO']['ANATOMY_NAME'].'</a>)');


$STR='';
if (!isset($MODULE_DATA['ASSAY_TARGET']))
{
	changeValue("assay_portal","TARGETS",'<div class="alert alert-info">No target information provided</div>');
}
else
{
	
	foreach ($MODULE_DATA['ASSAY_TARGET'] as &$TARGET)
	{
		if ($TARGET['TARGET_TYPE']=='PROTEIN')
		{
		$STR.='<div class="w3-col s12"><div class=" w3-half w3-container ">
		<h3>'.ucfirst($TARGET['FULL_NAME']).':</h3>
		<table class="table table-sm">
			
			<tr><th class="boldright">Organism:</th>		<td>'.$TARGET['SCIENTIFIC_NAME'].' (TaxId:'.$TARGET['TAX_ID'].')</td></tr>
			<tr><th class="boldright">Gene:</th>			<td>'.$TARGET['SYMBOL'].' </td></tr>
			<tr><th class="boldright">NCBI Gene Id:</th>			<td>'.$TARGET['GENE_ID'].'</td></tr>
			<tr><th class="boldright">Uniprot Identifier</th>			<td>'.$TARGET['PROT_IDENTIFIER'].' </td></tr>
			<tr><th class="boldright">Uniprot Accession:</th>	<td>'.$TARGET['ACCESSION'].'</td></tr>
			<tr><th class="boldright">Target Type:</th>			<td>'.ucfirst(strtolower($TARGET['TARGET_TYPE'])).'</td></tr>';

if ($MODULE_DATA['ASSAY_INFO']['MUTATION_AC']==$TARGET['ACCESSION'])
$STR.='<tr><th class="boldright">Mutation list:</th>			<td>'.$MODULE_DATA['ASSAY_INFO']['MUTATION_LIST'].'</td></tr>';
		$STR.='</table>
	</div>
	<div class=" w3-half overflow_y_simple long_text" >
			<h3>Description:</h3>';
			if (isset($TARGET['DESC']['FUNCTION']))
			{
				$STR.=convertUniprotText($TARGET['DESC']['FUNCTION']);
			}			
			
			
			$STR.='</div></div>';
		}


	}
	changeValue("assay_portal","TARGETS",$STR);
}

if ($MODULE_DATA['ASSAY_PUBLI']!=array())
{
$LIST_PMID=array();
foreach ($MODULE_DATA['ASSAY_PUBLI'] as $P)$LIST_PMID[]=$P['PMID'];
$USER_INPUT['PAGE']['VALUE']=implode('_',$LIST_PMID);
changeValue("assay_portal","PUBLI",loadHTMLAndRemove('PUBLICATION_BATCH'));
}
if ($MODULE_DATA['ASSAY_UNITS']!=array())
{
	removeBlock("assay_portal",'NO_UNITS');
	$STR='';
	foreach ($MODULE_DATA['ASSAY_UNITS'] as &$UNIT)
	{
		$STR.='<tr><td>'.$UNIT['STD_TYPE'].'</td><td>'.$UNIT['STD_UNITS'].'</td><td>'.$UNIT['MIN'].'</td><td>'.$UNIT['MAX'].'</td><td>'.$UNIT['COUNT'].'</td></tr>';
	}
	changeValue("assay_portal",'UNITS',$STR);

}else removeBlock("assay_portal",'HAS_UNITS');


if ($MODULE_DATA['RELATED_ASSAY']!=array())
{
	$STR='';
	foreach ($MODULE_DATA['RELATED_ASSAY'] as &$REL)
	{
		$STR.='<div class="w3-half">
		<h4>'.$REL['ASSAY_NAME'].'</h4><p style="min-height:40px">'.$REL['ASSAY_DESCRIPTION'].'</p>
	<table class="table table-sm">
			
			<tr><th class="boldright">Organism:</th>		<td>'.$REL['SCIENTIFIC_NAME'].' (TaxId:'.$REL['TAX_ID'].')</td></tr>
			<tr><th class="boldright">Source:</th>			<td>'.$REL['SOURCE_NAME'].' </td></tr>
			<tr><th class="boldright">Category:</th>			<td>'.$REL['ASSAY_CATEGORY'].' </td></tr>
			<tr><th class="boldright">Confidence score:</th>			<td>'.$REL['CONFIDENCE_SCORE'].' </td></tr>
			
			<tr><th class="boldright">Class:</th>			<td>'.$REL['ASSAY_TYPE'].'</td></tr>
			<tr><th class="boldright">Bioassay type:</th>	<td>'.$REL['BIOASSAY_LABEL'].'</td></tr>
			<tr><th class="boldright">Target Type:</th>			<td>'.$REL['ASSAY_TARGET_TYPE_NAME'].'</td></tr>
			<tr><th class="boldright">Target name:</th>	<td>'.$REL['ASSAY_TARGET_LONGNAME'].'</td></tr>
			<tr><th class="boldright">Tissue:</th>			<td>'.$REL['ASSAY_TISSUE_NAME'];
			if ($REL['ANATOMY_NAME']!='')$STR.="(Official: ".$REL['ANATOMY_NAME'].')';
			$STR.='</td></tr>
			<tr><th class="boldright">Mutation List:</th>			<td>'.$REL['MUTATION_LIST'].' </td></tr>

		</table>
		</div>';
	}
	changeValue("assay_portal",'LIST_RELATED',$STR);
	removeBlock("assay_portal",'NO_RELATED_ASSAYS');
}





changeValue("assay_portal","TOOLTIPS",str_replace("'","\\'",json_encode(str_replace("\n","",$DATA_TTL))));



/*
rray
(
    [ASSAY_INFO] => Array
        (
            [ASSAY_NAME] => CHEMBL1002021
            [ASSAY_DESCRIPTION] => Inhibition of human CDK2 by HTRF assay
            [ASSAY_TEST_TYPE] => 	X
            [ASSAY_CATEGORY] => 
            [ASSAY_TYPE] => Binding	X
            [BIOASSAY_TAG_ID] => BAO_0000357
            [BIOASSAY_LABEL] => single protein format	X
            [BIOASSAY_DEFINITION] => one protein sequence

            [ASSAY_CELL_NAME] => X
            [ASSAY_CELL_DESCRIPTION] => X
            [ASSAY_CELL_SOURCE_TISSUE] => X
            [CELL_ACC] => X
            [CELL_NAME] => X
            [CELL_TYPE] => X
            [CELL_DONOR_SEX] => X
            [CELL_DONOR_AGE] =>X 
            [CELL_VERSION] => X
            [ASSAY_TISSUE_NAME] => 
            [ANATOMY_TAG] => 
            [ANATOMY_NAME] => 
            [ANATOMY_DEFINITION] => 
            [TAX_ID] => 9606	X
            [SCIENTIFIC_NAME] => Homo sapiens	X
            [MUTATION_LIST] => 
            [MUTATION_AC] => 
            [MUTATION_PROT_ISO] => 
            [MUTATION_PROT_DESC] => 
            [CONFIDENCE_SCORE] => 9		X
            [SOURCE_NAME] => ChEMBL		X
            [ASSAY_TARGET_NAME] => CHEMBL301
            [ASSAY_TARGET_LONGNAME] => Cyclin-dependent kinase 2
            [SPECIES_GROUP_FLAG] => 0
            [ASSAY_TARGET_TAX] => 9606
            [ASSAY_TARGET_TAXNAME] => Homo sapiens
            [ASSAY_TARGET_TYPE_NAME] => SINGLE PROTEIN
            [ASSAY_TARGET_TYPE_DESC] => Target is a single protein chain
        )

    [ASSAY_TARGET] => Array
        (
            [0] => Array
                (
                    [IS_HOMOLOGUE] => 0
                    [ACCESSION] => P24941
                    [ISO_ID] => P24941-1
                    [ISO_NAME] => Displayed
                    [PROT_IDENTIFIER] => CDK2_HUMAN
                    [GN_ENTRY_ID] => 76973
                    [PROT_SEQ_ID] => 934232
                    [PROT_ENTRY_ID] => 893661
                    [SYMBOL] => CDK2
                    [TAX_ID] => 9606
                    [GENE_ID] => 1017
                    [SCIENTIFIC_NAME] => Homo sapiens
                    [TARGET_TYPE] => PROTEIN
                )

        )

)
 */
?>