<?php
if (!defined("BIORELS")) header("Location:/");



if (!isset($MODULE_DATA['ASSAY_INFO']))
{
	removeBlock("assay_data","VALID");
	return;
}
else removeBlock("assay_data","INVALID");





$STR='';foreach ($MODULE_DATA['SEARCH']['TYPE'] as $T)     $STR.='<option value="'.$T.'">'.$T.'</option>';changeValue("assay_data",'TYPES',$STR);
$STR='';foreach ($MODULE_DATA['SEARCH']['REL'] as $T)      $STR.='<option value="'.$T.'">'.$T.'</option>';changeValue("assay_data",'REL',$STR);
$STR='';foreach ($MODULE_DATA['SEARCH']['UNITS'] as $T)    $STR.='<option value="'.$T.'">'.$T.'</option>';changeValue("assay_data",'UNIT',$STR);



$LIST=array('ASSAY_DESCRIPTION','ASSAY_NAME','ASSAY_TYPE','ASSAY_CATEGORY','BIOASSAY_LABEL','ASSAY_TARGET_LONGNAME','MUTATION_LIST','ASSAY_TARGET_TYPE_NAME','SCIENTIFIC_NAME','SCORE_CONFIDENCE','TAX_ID','SOURCE_NAME','ASSAY_CELL_NAME','ASSAY_CELL_DESCRIPTION','ASSAY_CELL_SOURCE_TISSUE','CELL_ACC','CELL_NAME','CELL_TYPE','CELL_DONOR_SEX','CELL_DONOR_AGE','CELL_VERSION',
'ASSAY_TISSUE_NAME');
foreach ($LIST as $T)
{
	if ($T=='ASSAY_TARGET_LONGNAME')
	{
		$MODULE_DATA['ASSAY_INFO'][$T]=str_replace("/","<br/>",$MODULE_DATA['ASSAY_INFO'][$T]);
	}
	if ($MODULE_DATA['ASSAY_INFO'][$T]=='')changeValue("assay_data",$T,'N/A');
	else changeValue("assay_data",$T,$MODULE_DATA['ASSAY_INFO'][$T]);
}

if (strtolower($MODULE_DATA['ASSAY_INFO']['SOURCE_NAME'])=='chembl')
{
	changeValue("assay_data","CHEMBL_LINK",str_replace('${LINK}',$MODULE_DATA['ASSAY_INFO']['ASSAY_NAME'], $GLB_CONFIG['LINK']['CHEMBL']['ASSAY']));
}

$TOT_DATA=0;
foreach ($MODULE_DATA['ASSAY_UNITS'] as $K)$TOT_DATA+=$K['CO'];

changeValue("assay_data",'COUNT',$TOT_DATA);
changeValue("assay_data",'NPAGE',ceil($TOT_DATA/10));


$STR='';
$STR_JS='';$SLIDER_ID=0;
foreach ($MODULE_DATA['SEARCH']['RANGE'] as $TYPE=>$LIST_UNITS)
{
    $STR.='<div class="w3-container w3-col s12">
    <span class="w3-col s2" ';
    //if (count($LIST_UNITS)==1)$STR.=' style="display:inline-block"';
    $STR.='>'.$TYPE.'</span><div class="w3-col s12">';
foreach ($LIST_UNITS as $UNIT=>$RANGE)
{
    
    $SLIDER_ID++;
    $NAME=$SLIDER_ID;
    $STR.='<div class="w3-container w3-col s12" style="display:inline-block">
    
    <input type="text" id="range_'.$NAME.'" readonly style="border:0; color:#f6931f; font-weight:bold;" class="w3-col s2">
    <label  class="w3-col s2" for="range_'.$NAME.'">'.$UNIT.'</label>
  <div  class="w3-col s8" style="position:relative;top:8px;" id="slider-range_'.$NAME.'"></div></div><br/>';
    $STR_JS.='$( function() {
        $( "#slider-range_'.$NAME.'" ).slider({
          range: true,
          min: '.$RANGE[0].',
          max: '.$RANGE[1].',
          values: [ '.$RANGE[0].', '.$RANGE[1].' ],
          step: '.round(($RANGE[1]-$RANGE[0])/100,0).',
          slide: function( event, ui ) {
            $( "#range_'.$NAME.'" ).val( "" + ui.values[ 0 ] + " - " + ui.values[ 1 ] );
          }
        });
        $( "#range_'.$NAME.'" ).val( "" + $( "#slider-range_'.$NAME.'" ).slider( "values", 0 ) +
          " - " + $( "#slider-range_'.$NAME.'" ).slider( "values", 1 ) );
      } );
      ';
}
$STR.='</div></div>';
}
changeValue("assay_data",'RANGE',$STR);
changeValue("assay_data",'RANGE_SLIDER',$STR_JS);


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