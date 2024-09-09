<?php

		changeValue("3D_view_ligand","LIG_NAME",	$MODULE_DATA['RES_INFO']['NAME']);
		changeValue("3D_view_ligand","LIG_CLASS",	$MODULE_DATA['RES_INFO']['CLASS']);
		changeValue("3D_view_ligand","SMILES",		$MODULE_DATA['RES_INFO']['SMILES']);
		changeValue("3D_view_ligand","LIG_ID",		'['.$MODULE_DATA['RES_INFO']['NAME'].']'.$MODULE_DATA['RES_INFO']['XR_POS'].':'.$MODULE_DATA['RES_INFO']['CHAIN_NAME']);
		changeValue("3D_view_ligand","PDB_ID",		$MODULE_DATA['RES_INFO']['FULL_COMMON_NAME']);
		changeValue("3D_view_ligand","LOCATION",	$MODULE_DATA['RES_INFO']['FULL_COMMON_NAME'].'>'.
													$MODULE_DATA['RES_INFO']['CHAIN_NAME'].'-'.
													$MODULE_DATA['RES_INFO']['NAME'].':'.
													$MODULE_DATA['RES_INFO']['XR_POS']);
													changeValue("3D_view_ligand","LIG_RES_ID",$MODULE_DATA['RES_INFO']['XR_POS']);

$STR='';
foreach ($MODULE_DATA['INTERS'] as &$INTER)
{
$STR.='<tr><td>'.$INTER['CHAIN_NAME'].'</td><td>'
				.$INTER['NAME'].'</td><td>'
				.$INTER['POSITION'].'</td><td>'
				.$INTER['ATOM_LIST_2'].'</td><td>'
				.$INTER['ATOM_LIST_1'].'</td><td>'
				.$INTER['INTER_NAME'].'</td><td>'
				.$INTER['DISTANCE'].'</td><td>'
				.$INTER['ANGLE'].'</td><td>';
				if (!isset($INTER['STAT'])){$STR.='</td><td></td></tr>';continue;}
				if (isset($INTER['STAT']['T']))
				{
					$S=array_sum($INTER['STAT']['T']);
					$ST='';
					foreach ($INTER['STAT']['T'] as $type=>$co)$ST.=$co.' '.ucfirst(strtolower($type))."\n";
					if ($S==1)$STR.='<span style="font-weight:bold;color:green;" data-title="'.$ST.'">NEW!</span>';
					else if ($S<=5)$STR.='<span style="font-weight:bold;color:orange;" data-title="'.$ST.'">Uncommon</span>';
					else if ($S>5)$STR.='<span  data-title="'.$ST.'">Common</span>';
				}
				$STR.='</td><td>';
				if (isset($INTER['STAT']['F']))
				{
					$S=array_sum($INTER['STAT']['F']);
					$ST='';
					foreach ($INTER['STAT']['F'] as $type=>$co)$ST.=$co.' '.ucfirst(strtolower($type))."\n";
					if ($S==1)$STR.='<span style="font-weight:bold;color:green;" data-title="'.$ST.'">NEW!</span>';
					else if ($S<=5)$STR.='<span style="font-weight:bold;color:orange;" data-title="'.$ST.'">Uncommon</span>';
					else if ($S>5)$STR.='<span  data-title="'.$ST.'">Common</span>';
				}
				$STR.='</td></tr>';
}
changeValue("3D_view_ligand","INTERS",$STR);
$STR_PREP=array();


$STR='';
$STR_JS='';
$PDB_ID=$MODULE_DATA['RES_INFO']['FULL_COMMON_NAME'];
 $INFO=array();
foreach ($MODULE_DATA['CHAIN'] as $CH_NAME=> &$CH_INFO)
{
	
	$STR_PREP[$CH_NAME]=array('OFFSET'=>-1,'POSITION'=>-1,'LEFT'=>-1);
	//$CH_NAME=$CH_INFO['CHAIN_NAME'];
	$STR_JS.='$(function() {
		$("#LIG_SEQ_VIEW_'.$CH_NAME.'").click(function(e) {
		
		  var offset = $(this).offset();
		  var x = (e.pageX - offset.left);
		  
		  
		  var scrollLeft=$("#LIG_SEQ_VIEW_'.$CH_NAME.'_L").scrollLeft();
		  console.log(scrollLeft);
		  
		  left=Math.floor(x/ratio)*ratio-1+(scrollLeft/ratio-Math.round(scrollLeft/ratio));
		  
		  position=Math.floor((x+scrollLeft)/ratio);
		  var prev_p=sel_position["'.$CH_NAME.'"]["POSITION"];
		  if (prev_p!=-1)highlight("'.$CH_NAME.'",id_mapping[prev_p],true);
		  console.log(x+" "+e.pageX+" "+offset.left+" "+scrollLeft+" "+position);
			sel_position["'.$CH_NAME.'"]["OFFSET"]=scrollLeft;
			sel_position["'.$CH_NAME.'"]["POSITION"]=position+1;
			sel_position["'.$CH_NAME.'"]["LEFT"]=left;
			highlight("'.$CH_NAME.'",id_mapping[position],false);
			showLigSiteView();
		});
		});'."\n";
	$STR_MENU='
	<div id="menu" style="left: 98%;
    position: relative;
    top: -31px;">
		  	<div class="dropdown">
				<span class="dropbtn"><img id="transcript_seq_tool_but" src="/require/img/tools.png" style="width: 20px;"></span>
				<div class="dropdown-content" style="left:-130px">
				<h3 style="text-align: center"> CHAIN '.$CH_NAME.'</h3>
					<a href="/3D_VIEW/'.$PDB_ID.'/PARAMS/CHAIN/'.$CH_NAME.'" class="btn">View 3D structure</a>
					<a href="/PDB/3D_FILE/'.$PDB_ID.'/PARAMS/CHAIN/'.$CH_NAME.'" class="btn">Download PDB File</a>
					<a href="/MOL2/3D_FILE/'.$PDB_ID.'/PARAMS/CHAIN/'.$CH_NAME.'" class="btn">Download MOL2 File</a></div></div></div>';
	$STR.='<h2>Chain '.$CH_NAME.'</h2>'.$STR_MENU;
	foreach ($CH_INFO['UNIP'] as &$US_INFO)
	{
		$GID=$US_INFO['INFO']['GENE_ID'];
		$STR.='
		<div id="LIG_SEQ_VIEW_'.$CH_NAME.'" style="position:relative;margin-bottom:50px;display:flex;font-family: Courier New, Courier, Lucida Sans Typewriter, Lucida Typewriter, monospace;font-size: 16px;"></div><table class="table" style="width:100%"><tr><th colspan="2" style="width:50%;text-align:center">Gene information</th>
		<th colspan="2" style="width:50%;text-align:center">Protein information</th></tr><tr><th style="width:16%">Gene ID:</th><td><a href="/GENEID/'.$GID.'">'.$GID.'</a></td>
						  <th>Uniprot:</th><td> <a href="'.str_replace('${LINK}',$US_INFO['INFO']['UN_IDENTIFIER'],$GLB_CONFIG['LINK']['UNIPROT']['UNIID']).'">'.$US_INFO['INFO']['UN_IDENTIFIER'].'</a></td></tr>
						</tr>
						<tr><th>Gene Symbol:</th><td><a href="/GENEID/'.$GID.'">'.$US_INFO['INFO']['SYMBOL'].'</a></td>
						  <th>Sequence:</th><td>  <a href="/GENEID/'.$GID.'/SEQUENCE/'.$US_INFO['INFO']['ISO_ID'].'">'.$US_INFO['INFO']['ISO_ID'].'</a></td></tr>
						</tr>
						<tr><th>Gene Name:</th><td colspan="3">'.$US_INFO['INFO']['FULL_NAME'].'</td>
						  
						</tr>
						<tr><th>Sequence Description/Note:</th><td colspan="3">'.$US_INFO['INFO']['NOTE'].' '.$US_INFO['INFO']['DESCRIPTION'].'</td></tr>
						</table>';
	}

	$TMP_INTERS=array();
	foreach ($CH_INFO['INTER'] as $N=>&$INTER)$TMP_INTERS[$INTER['INTER_NAME']][]=$INTER;
	$MODULE_DATA['CHAIN'][$CH_NAME]['INTER']=$TMP_INTERS;

	

		
	
}

changeValue("3D_view_ligand","LIG_SEQ_VIEW",$STR);
changeValue("3D_view_ligand","DATA_JS",$STR_JS);
changeValue("3D_view_ligand","DATA_SEQ_VIEW",str_replace("'","\\'",json_encode($MODULE_DATA['CHAIN'])));

changeValue("3D_view_ligand","DATA_PREP_VIEW",str_replace("'","\\'",json_encode($STR_PREP)));

?>