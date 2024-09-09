<?php

if (!defined("BIORELS")) header("Location:/");

/*
AB Med: 224 205
AB Small: -231 -16 76 77
Prot Med: -16 224 | 188 220

*/
changeValue("drugs","PATH",$PATH);


$DR_ST=array(1=>0,2=>0,3=>0,4=>0);
foreach ($MODULE_DATA as $DR)	$DR_ST[$DR['MAX_CLIN_PHASE']]++;
$MAX_LEV=0;
foreach ($DR_ST as $K=>$T)if ($T!=0)$MAX_LEV=$K;
$STR='<div class="w3-col s12 m12 l12 w3-container" style="margin-bottom:10px">
<div class="w3-col s3_1 m3_1 l3_1"><div class="text-circle blk_font" style="margin:0 auto">'.$DR_ST[1].'</div></div>
<div class="w3-col s3_1 m3_1 l3_1"><div class="text-circle blk_font" style="margin:0 auto">'.$DR_ST[2].'</div></div>
<div class="w3-col s3_1 m3_1 l3_1"><div class="text-circle blk_font" style="margin:0 auto">'.$DR_ST[3].'</div></div>
<div class="w3-col s3_1 m3_1 l3_1"><div class="text-circle blk_font" style="margin:0 auto">'.$DR_ST[4].'</div></div>
</div>
';
$STR_N='<div>';
$DR_I=array(1=>'I',2=>'II',3=>'III',4=>'IV');
$LIST=array(1=>array(),2=>array(),3=>array(),4=>array());
$MAX=0;
for ($I=1;$I<=4;++$I)
{
	//$STR_N.='<div class="w3-col s3_1 m3_1 l3_1"><div class="text-circle blk_font" style="margin:0 auto">'.$DR_ST[$I].'</div><div>';
	
	foreach ($MODULE_DATA as $K=>$DR)
	{
		if ($DR['MAX_CLIN_PHASE']!=$I)continue;
		$LIST[$I][]=$K;
	}
	$MAX=max($MAX,count($LIST[$I]));
}
for ($I=0;$I<=$MAX;++$I)
{
	$STR.='<div class="w3-col s12 m12 l12 w3-container" style="margin-bottom:10px">';
	for ($J=1;$J<=4;++$J)
	{
		$STR.='<div class="w3-col s3_1 m3_1 l3_1" ';
		if (isset($LIST[$J][$I]))
		{
			$STR.=' onclick="$(\'html,body\').animate({scrollTop: $(\'#DR_'.$K.'\').offset().top -100}, 1700);"><div class="w3-col s12 m3 l3 s-center">';
		$DR=$MODULE_DATA[$LIST[$J][$I]];
		switch($DR['DRUG_TYPE'])
		{
			case "A": $STR.='<div  class="sprite antibody_icon_s"></div>';break;
			case "S": $STR.='<div class="sprite sm_icon_s"></div>';break;
			case "O":$STR.='<div class="sprite o_icon_s"</div>';break;break;
			case "OS":$STR.='<div class="sprite os_icon_s"></div>';break;break;
			case "unknown":
			case "Enzyme":
			case 'PR': $STR.='<div class="sprite pr_icon_s"></div>';break;
			break;
		}
		// switch($DR['DRUG_TYPE'])
		// {
		// 	case "A": $STR_N.='<div class="sprite sm_icon_s style="background: url(require/img/DRUG_IMG.png) 0px 0;width: 50px;height: 41px;margin: 0 auto;margin: 0 auto;"></div>';break;
		// 	case "S": $STR_N.='<div style="background: url(require/img/DRUG_IMG.png) 0px -43px;width: 46px;height: 39px;margin: 0 auto;"></div>';break;
		// 	case "O":$STR_N.='<div style="background: url(require/img/DRUG_IMG.png) 0px -170px;width: 50px;height: 27px;margin: auto auto;"></div>';break;break;
		// 	case "OS":$STR_N.='<div style="background: url(require/img/DRUG_IMG.png) -0px -137px;width: 49px;height: 33px;margin: 0 auto;"></div>';break;break;
		// 					case "unknown":
			
		// 	case "Enzyme":
		// 	case 'PR': $STR_N.='<div style="background: url(require/img/DRUG_IMG.png)  -0px -83px;width: 49px;height: 52px;margin: 0 auto;"></div>';break;
		// 	break;
		// }
		$STR.='</div><div class="drug_block_name w3-col s12 m9 l9 ">'.$DR['DRUG_PRIMARY_NAME'].'</div></div>';
		}
		else
		{
			$STR.='></div>';
		}
	}
	$STR.='</div>';
}


	$STR_N.='</div></div>';
	$STR.='<div  class="chevron_start w3-col s3_1 l3_1 m3_1" style="'.(($MAX_LEV<1)?'background-color:grey':'').'">'.$DR_I[1].'</div>';
	$STR.='<div  class="chevron       w3-col s3_1 l3_1 m3_1" style="'.(($MAX_LEV<2)?'background-color:grey':'').'">'.$DR_I[2].'</div>';
	$STR.='<div  class="chevron       w3-col s3_1 l3_1 m3_1" style="'.(($MAX_LEV<3)?'background-color:grey':'').'">'.$DR_I[3].'</div>';
	$STR.='<div  class="chevron       w3-col s3_1 l3_1 m3_1" style="'.(($MAX_LEV<4)?'background-color:grey':'').'">'.$DR_I[4].'</div>';
// for ($I=1;$I<=4;++$I)
// {
// 	$STR_N.='<div class="w3-col s3_1 m3_1 l3_1"><div class="text-circle blk_font" style="margin:0 auto">'.$DR_ST[$I].'</div><div>';
// 	foreach ($MODULE_DATA as $K=>$DR)
// 	{
// 		if ($DR['MAX_CLIN_PHASE']!=$I)continue;
// 		$STR_N.='<div class="drug_block" onclick="$(\'html,body\').animate({scrollTop: $(\'#DR_'.$K.'\').offset().top -100}, 1700);"><div class="drug_img">';
		
// 		switch($DR['DRUG_TYPE'])
// 		{
// 			case "A": $STR_N.='<div  class="sprite antibody_icon_s"></div>';break;
// 			case "S": $STR_N.='<div class="sprite sm_icon_s"></div>';break;
// 			case "O":$STR_N.='<div class="sprite o_icon_s"</div>';break;break;
// 			case "OS":$STR_N.='<div class="sprite os_icon_s"></div>';break;break;
// 			case "unknown":
// 			case "Enzyme":
// 			case 'PR': $STR_N.='<div class="sprite pr_icon_s"></div>';break;
// 			break;
// 		}
// 		// switch($DR['DRUG_TYPE'])
// 		// {
// 		// 	case "A": $STR_N.='<div class="sprite sm_icon_s style="background: url(require/img/DRUG_IMG.png) 0px 0;width: 50px;height: 41px;margin: 0 auto;margin: 0 auto;"></div>';break;
// 		// 	case "S": $STR_N.='<div style="background: url(require/img/DRUG_IMG.png) 0px -43px;width: 46px;height: 39px;margin: 0 auto;"></div>';break;
// 		// 	case "O":$STR_N.='<div style="background: url(require/img/DRUG_IMG.png) 0px -170px;width: 50px;height: 27px;margin: auto auto;"></div>';break;break;
// 		// 	case "OS":$STR_N.='<div style="background: url(require/img/DRUG_IMG.png) -0px -137px;width: 49px;height: 33px;margin: 0 auto;"></div>';break;break;
// 		// 					case "unknown":
			
// 		// 	case "Enzyme":
// 		// 	case 'PR': $STR_N.='<div style="background: url(require/img/DRUG_IMG.png)  -0px -83px;width: 49px;height: 52px;margin: 0 auto;"></div>';break;
// 		// 	break;
// 		// }
// 		$STR_N.='</div><div class="drug_block_name">'.$DR['NAME']['PRIMARY'][0].'</div></div>';
// 	}	
	

// }
changeValue("drugs","SUMMARY",$STR_N.'</div>'.$STR.'</div>');

$STR='';
$STR_JS=array();
foreach ($MODULE_DATA as $K=>$DR)
{
	$STR.='<div  class="w3-container w3-col s12 l12 m12 container-grey" style="margin-top:20px">
		<div class="w3-col s12 m12 l4" id="P_DR_'.$K.'">
		<h4 style="text-align: center;padding-top: 10px;"  >'.$DR['DRUG_PRIMARY_NAME'].'<a href="/DRUG/'.$DR['DRUG_PRIMARY_NAME'].'"><div class="portal_icon"></div></a></h4>';
		if ($DR['SMILES']=='')
		{
			$STR.='<div class="large_drug_icon" id="DR_'.$K.'">';
			switch($DR['DRUG_TYPE'])
			{
				case "A": $STR.='<div class="sprite antibody_icon_l"></div>';break;
				case "S": $STR.='<div class="sprite sm_icon_l"></div>';break;
				case "O":$STR.='<div class="sprite o_icon_l"></div>';break;break;
				case "OS":$STR.='<div class="sprite os_icon_l"></div>';break;break;
								case "unknown":
				case "Unknown": $DRUG_TYPE='UN';break;
				case "Cell": $DRUG_TYPE='CE';break;
				case "Gene": $DRUG_TYPE='GN';break;
				case "Enzyme":
				case 'PR': $STR.='<div class="sprite protein_icon_l"></div>';break;
				break;
			}
		}else $STR.='<div style="margin: 0 auto;width: fit-content;"id="DR_'.$K.'">';
		
		$STR.='</div></div>
		<div  class="w3-col s12 m12 l8 high_line w3-cell-top w3-col-600">';
	$STR.='<table class="table" style="width:100%"><tr><th style="width:20%">Maximal clinical phase:</th><td colspan="2">'.$DR['MAX_CLIN_PHASE'].'</td></tr>
		<tr><th>Synonyms:</th><td  style="line-height:3em" colspan="2" >'; 
		foreach ($DR['NAME']['SYNONYM'] as $S) 
		$STR.=' <span class="drug_syn">'.$S.'</span>';
		$STR.='</td></tr>';
		if (isset($DR['NAME']['TRADENAME'])){
		$STR.='<tr><th>Trade name:</th><td class="container-grid" style="line-height:3em">'; 
		foreach ($DR['NAME']['TRADENAME'] as $S) $STR.=' <span class="drug_syn">'.$S.'</span>';
		$STR.='</td></tr>';
		}
		$STR.='</table>';
		
		$STR.='<div style="width:100%;max-height:300px;overflow-y:scroll"><table class="table">';
			$STR.='<tr><th class="w3-hide-600" style="width:20%" rowspan="'.(count($DR['DISEASE'])+1).'">Indication:</th><th>Disease Name</th><th>Max disease clinical phase</th></tr>';
			$FIRST=true;
		foreach ($DR['DISEASE'] as $DS)
		{
			$STR.='<tr><td><a target="_blank"  class="blk_font" href="/DISEASE/'.$DS['DISEASE_NAME'].'">'.$DS['DISEASE_NAME'].'<div class="portal_icon"></div></a></td><td>'.$DS['MAX_DISEASE_PHASE'].'</td></tr>';
		}
	$STR.='</table></div>';
	$STR.='</div></div>';
	if ($DR['SMILES']!='')$STR_JS['DR_'.$K]=$DR['SMILES'];
	//
	
}
changeValue("drugs","TABLE",$STR);
changeValue("drugs","LIST",json_encode(str_replace("/","\/",str_Replace("\\","\\\\",$STR_JS))));