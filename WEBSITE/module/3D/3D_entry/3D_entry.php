<?php

changeValue("3D_entry","PDB_ID",$MODULE_DATA['ENTRY']['PDB_ID']);
changeValue("3D_entry","TITLE",$MODULE_DATA['ENTRY']['TITLE']);
changeValue("3D_entry","TYPE",$MODULE_DATA['ENTRY']['EXPR_TYPE']);
changeValue("3D_entry","DATE",$MODULE_DATA['ENTRY']['DEPOSITION_DATE']);
if ($MODULE_DATA['ENTRY']['RESOLUTION']!='')
changeValue("3D_entry","RESOLUTION",'<span style="font-weight:bold;padding-left:10px;">Resolution:</span> '.$MODULE_DATA['ENTRY']['RESOLUTION'].' &#8491;');

$STR='';
$PDB_ID=$MODULE_DATA['ENTRY']['PDB_ID'];
foreach ($MODULE_DATA['UN_SEQ'] as $NUS=> &$US_INFO) {
	$GID=$US_INFO['INFO']['GENE_ID'];
	$STR_CH='CHAIN';
	
	if (count($US_INFO['CHAIN'])>1)$STR_CH.='S';
	$STR_MENU='
	<div id="menu" style="left: 98%;
    position: relative;
    top: -31px;">
		  	<div class="dropdown">
				<span class="dropbtn"><img id="transcript_seq_tool_but" src="/require/img/tools.png" style="width: 20px;"></span>
				<div class="dropdown-content" style="left:-130px">';

	foreach ($US_INFO['CHAIN'] as $CHAIN_ID)
	{
		$CH_INFO=&$MODULE_DATA['CHAIN'][$CHAIN_ID];
		//echo "<pre>";print_r($CH_INFO);exit;
		$STR_CH.=' '.$CH_INFO['CHAIN_NAME'];
		$CHN=$CH_INFO['CHAIN_NAME'];
		$STR_MENU.='<h3 style="text-align: center"> CHAIN '.$CHN.'</h3>
					<a href="/3D_VIEW/'.$PDB_ID.'/PARAMS/CHAIN/'.$CHN.'" class="btn">View 3D structure</a>
					<a href="/PDB/3D_FILE/'.$PDB_ID.'/PARAMS/CHAIN/'.$CHN.'" class="btn">Download PDB File</a>
					<a href="/MOL2/3D_FILE/'.$PDB_ID.'/PARAMS/CHAIN/'.$CHN.'" class="btn">Download MOL2 File</a>';
	}
	
					
		$STR_MENU.='</div></div></div>';
		$STR.='<h2>'.$STR_CH.'</h2>'.$STR_MENU.'
	<table class="table" style="width:100%"><tr><th colspan="2" style="width:50%;text-align:center">Gene information</th>
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

	$STR.='<div class="mseq_sum"><ol class="mseq_schema">';
	
	 $STR.='<li class="seq_schema">'.
	       '<div class="ss_row_name">'.$US_INFO['INFO']['ISO_ID'].'</div>';
	      $STR.='<div class="ss_row_line" ><div class="matches">';
	 $STR.=      '<a class="match" style="left:0%; width:100%; background-color:green"></a></div></div></li>';

	 $STR.='<li class="seq_schema">'.
	       '<div class="ss_row_name">Fold</div>';
	      $STR.='<div class="ss_row_line" ><div class="matches">';
		  $LEN=count($US_INFO['SEQ']);
		  $FOLD_COL=array('Helix'=>'red','Beta strand'=>'blue','Turn'=>'cyan');
	foreach ($US_INFO['FT']['FEATS'] as &$FT)
	{
		$ft_type=$US_INFO['FT']['FEAT_TYPE'][$FT['TYPE']]['NAME'];
		
		if (!($ft_type=='Helix'||$ft_type=='Beta strand'||$ft_type=='Turn'))continue;
		
		$LEFT=round($FT['START']*100/$LEN,2);
		$WIDTH=round(($FT['END']-$FT['START'])*100/$LEN,2);
		$STR.='<a class="match" data-block="'.$NUS.'" data-title="'.$ft_type.'" style="left:'.$LEFT.'%; width:'.$WIDTH.'%;  background-color:'.$FOLD_COL[$ft_type].'"></a>';


	}$STR.=      '</div></div></li>';

	

	 $DOM_TYPE_IMG=array("CHAIN"=>array("C","#a5a5a5"),"REPEAT"=>array("R","#ed7d31"),"DOMAIN"=>array("D","#70ad47"),"REGION"=>array("G","#843c0c"));


	 
	 if (isset($US_INFO['DOM']))
	{
		if (count($US_INFO['DOM'])>8)$SCROLL=true;
		else $SCROLL=false;
		if ($SCROLL)$STR.=	'<div  class="list_container" style="max-height:200px;overflow-y:scroll">';
		
		foreach ($US_INFO['DOM'] as &$DOM_INFO)
		{
			
			$STR.='<li class="seq_schema" ';
			if ($SCROLL)$STR.='id="item_direction"';
		   $STR.='><div class="ss_row_name"';
		   if ($SCROLL)$STR.=' style="width: 248px;"';
		   $STR.='><img src="/require/img/DO_'.$DOM_TYPE_IMG[$DOM_INFO['DOMAIN_TYPE']][0].'.png" title="'.$DOM_INFO['DOMAIN_TYPE'].'" class="ss_row_name_img"/>'.
		   $DOM_INFO['DOMAIN_NAME'].'</div>';
		   $STR.='<div class="ss_row_line"';
		   if ($SCROLL)$STR.=' style="margin-right: 254px;"';
		   $STR.=' ><div class="matches">';
		   
			$LEFT=round($DOM_INFO['POS_START']*100/$LEN,2);
			$WIDTH=round(($DOM_INFO['POS_END']-$DOM_INFO['POS_START'])*100/$LEN,2);
			$STR.=  '<a class="match" style="left:'.$LEFT.'%; width:'.$WIDTH.'%;'.(($SCROLL)?'margin-left:10px;':'').' background-color:'.$DOM_TYPE_IMG[$DOM_INFO['DOMAIN_TYPE']][1].'"></a>';
			
		
		$STR.='</div></div></li>';
		
		}
		if (count($US_INFO['DOM'])>8)$STR.=	'</div>';
	}

	if (isset($US_INFO['FT']['FEATS']))
	{
		if (count($US_INFO['FT']['FEATS'])>8)$SCROLL=true;
		else $SCROLL=false;
		if ($SCROLL)$STR.=	'<div  class="list_container" style="max-height:200px;overflow-y:scroll">';
		
		foreach ($US_INFO['FT']['FEATS'] as &$FT)
		{
			$ft_type=$US_INFO['FT']['FEAT_TYPE'][$FT['TYPE']]['NAME'];
		
		if (($ft_type=='Helix'||$ft_type=='Beta strand'||$ft_type=='Turn'))continue;
		
		$LEFT=round($FT['START']*100/$LEN,2);
		$WIDTH=round(($FT['END']-$FT['START'])*100/$LEN,2);

			$STR.='<li class="seq_schema" ';
			if ($SCROLL)$STR.='id="item_direction"';
		   $STR.='><div class="ss_row_name"';
		   if ($SCROLL)$STR.=' style="width: 248px;"';
		   $STR.='>'.$ft_type.'</div>';
		   $STR.='<div class="ss_row_line"';
		   if ($SCROLL)$STR.=' style="margin-right: 254px;"';
		   $STR.=' ><div class="matches">';

			$STR.=  '<a class="match" style="left:'.$LEFT.'%; width:'.$WIDTH.'%; '.(($SCROLL)?'margin-left:10px;':'').'background-color:purple"  data-block="'.$NUS.'"  data-title="'.$ft_type.': '.$FT['VALUE'].' ['.$FT['START'].' - '.$FT['END'].']"></a>';
			
		
		$STR.='</div></div></li>';
		
		}
		if (count($US_INFO['FT']['FEATS'])>8)$STR.=	'</div>';
	}

	foreach ($US_INFO['CHAIN'] as $CHAIN_ID)
	{
		$CH_INFO=&$MODULE_DATA['CHAIN'][$CHAIN_ID];
		$MUTS=array();
		$RANGE=array();$CURR=-1;$STARTED=false;
		
		foreach ($CH_INFO['RES'] as &$RES)
		{
			if ($RES[1]==''){$STARTED=false;continue;}
			if ($RES[3]!='I'){$MUTS[]=$RES;}
			if (!$STARTED){$CURR++;$RANGE[$CURR]=array('MIN'=>$RES[1],'MAX'=>$RES[1]);$STARTED=true;}
			
			$RANGE[$CURR]['MAX']=$RES[1];
		}
		
		$STR.='<li class="seq_schema">'.
		   '<div class="ss_row_name">'.$CH_INFO['CHAIN_NAME'].'</div>';
		   $STR.='<div class="ss_row_line" ><div class="matches">';
		   
		foreach ($RANGE as $R)
		{
			
			$LEFT=round($R['MIN']*100/$LEN,2);
			$WIDTH=round(($R['MAX']-$R['MIN'])*100/$LEN,2);
			$STR.=  '<a class="match" style="left:'.$LEFT.'%; width:'.$WIDTH.'%; background-color:green"></a>';
			
		}
		$STR.='</div></div></li>';
if ($MUTS!=array()){

		$STR.='<li class="seq_schema">'.
		'<div class="ss_row_name">'.$CH_INFO['CHAIN_NAME'].' Mutation</div>';
		$STR.='<div class="ss_row_line" ><div class="matches">';
		
	 foreach ($MUTS as $R)
	 {
		 
		 $LEFT=round($R[1]*100/$LEN,2);
		 $WIDTH=round(1*100/$LEN,2);
		 $STR.=  '<a class="match" style="left:'.$LEFT.'%; width:'.$WIDTH.'%; background-color:green"  data-block="'.$NUS.'" data-title="Mutation '.$R[0].$R[1].'>'.$R[2].'"></a>';
		 
	 }
	 $STR.='</div></div></li>';

	}
	}
	 $STR.='</ol></div><div><span class="bold">Description: </span><span id="info'.$NUS.'" style="font-style: italic">Mouse over an area to show more information</span></div><br/><br/>';


}
changeValue("3D_entry","UN_SEQ_BLOCK",$STR);

$STR='';
$STR_JS='';
$STR_JS_RESIZE='';

foreach ($MODULE_DATA['LIGS'] as $N=> &$LIG)
{
	$NAME=$PDB_ID.'-'.$LIG['CHAIN_NAME'].'-'.$LIG['NAME'].'-'.$LIG['POSITION'];
	$STR.='<div class="flex">
	
	<div id="menu" style="    left: 97%;
    position: relative;
    top: 32px;">
		  	<div class="dropdown">
				<span class="dropbtn"><img id="transcript_seq_tool_but" src="/require/img/tools.png" style="width: 20px;"></span>
				<div class="dropdown-content" style="left:-130px">
					<h3 style="text-align: center">Ligand '.$NAME.'</h3>
					<a href="/3D_VIEW_LIG/'.$NAME.'" class="btn">View 3D structure</a>
					<a href="/PDB/3D_FILE/'.$PDB_ID.'/PARAMS/RESID/'.$LIG['CHAIN_NAME'].'-'.$LIG['POSITION'].'" class="btn">Download PDB File</a>
					<a href="/MOL2/3D_FILE/'.$PDB_ID.'/PARAMS/RESID/'.$LIG['CHAIN_NAME'].'-'.$LIG['POSITION'].'" class="btn">Download MOL2 File</a>
				</div>
			</div>
			
		</div>
	<div id="viewport'.$N.'" style="width:200px; height: 200px;display:inline-block"></div>
	<div style="width:79%;display:inline-block"><table class="table" style="width:100%">
		<tr><th>Name:</th><td>'.$LIG['NAME'].'</td></tr>
		<tr><th>Class:</th><td>'.ucfirst($LIG['CLASS']).'</td></tr>
		<tr><th>Location:</th><td>'.$PDB_ID.' '.$LIG['CHAIN_NAME'].'-'.$LIG['NAME'].':'.$LIG['POSITION'].'</td></tr>
		<tr><th>SMILES:</th><td>'.$LIG['SMILES'].'</td></tr>
		</table>
	</div></div>';
	$STR_JS.="var stage".$N.";
    stage".$N." = new NGL.Stage('viewport".$N."',{ backgroundColor: 'white' } );
  
    stage".$N.".loadFile('data://MOL2/3D_FILE/".$PDB_ID."/PARAMS/RESID/".$LIG['CHAIN_NAME']."-".$LIG['POSITION']."', { ext: 'mol2' })
    .then( function( o ){
      o.addRepresentation('licorice');
      o.autoView();
      return o;;
   });";
   $STR_JS_RESIZE.=" stage".$N.".handleResize();\n";
  // break;
}
changeValue("3D_entry","JS_CHEM_VIEW",$STR_JS);
changeValue("3D_entry","JS_RESIZE",$STR_JS_RESIZE);
changeValue("3D_entry","LIGANDS",$STR);


$STR='';

foreach ($MODULE_DATA['PPI'] as $PPI)
{
	//echo "<pre>";print_r($PPI);
	$CH1=$PPI[0]['CHAIN_NAME'];
	$CH2=$PPI[1]['CHAIN_NAME'];
	$STR.='
	<div id="menu" style="    left: 97%;
    position: relative;
    top: 32px;">
		  	<div class="dropdown">
				<span class="dropbtn"><img id="transcript_seq_tool_but" src="/require/img/tools.png" style="width: 20px;"></span>
				<div class="dropdown-content" style="left:-130px">
					<h3 style="text-align: center">PPI '.$CH1.' '.$CH2.'</h3>
					<a href="/3D_VIEW/'.$PDB_ID.'/PARAMS/CHAIN/'.$CH1.'-'.$CH2.'" class="btn">View 3D structure</a>
					<a href="/PDB/3D_FILE/'.$PDB_ID.'/PARAMS/CHAIN/'.$CH1.'-'.$CH2.'" class="btn">Download PDB File</a>
					<a href="/MOL2/3D_FILE/'.$PDB_ID.'/PARAMS/CHAIN/'.$CH1.'-'.$CH2.'" class="btn">Download MOL2 File</a>
				</div>
			</div>
		</div>';
	$STR.='<table class="table" style="width:100%">';
	$STR.='<tr><td>'.$PPI[0]['CHAIN_NAME'].'</td><td>'.$PPI[1]['CHAIN_NAME'].'</td></tr>
	<tr><td>'.$PPI[0]['INFO'][array_keys($PPI[0]['INFO'])[0]]['UN_IDENTIFIER'].'</td><td>'.$PPI[1]['INFO'][array_keys($PPI[0]['INFO'])[0]]['UN_IDENTIFIER'].'</td></tr></table>';
}
changeValue("3D_entry","PPI",$STR);
?>