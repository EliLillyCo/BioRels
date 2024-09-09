<?php

if (!defined("BIORELS")) header("Location:/");

$STR_HEAD='';
$STR='';$DBID=0;
foreach ($MODULE_DATA as $TYPE=>&$LIST_T)
{
	$STR_HEAD.='<tr><th>'.$TYPE.'</th>';
	$N_DB=0;$N_LINK=0;
	++$DBID;
	$STR.='<div class="w3-col s12 w3-container container-grey" style="margin-bottom:30px" id="DB_'.$DBID.'"><h4 style="width:100%; text-align:center;">'.$TYPE.'</h4>';
	
	foreach ($LIST_T as $DBN=>&$LIST_N)
	{
		$N_DB++;
		$STR.='<h5>'.$DBN.'</h5><ul>';
		foreach ($LIST_N['VALUES'] as $VALUE=>$LIST_UNIPROTS)
		{
			$N_LINK++;
			$tab=explode(";",$VALUE);
			if (strpos($LIST_N['INFO']['URL'],'%s')===false)
			{
				$STR.='<li><a href="'.str_replace("%s",$tab[0],$LIST_N['INFO']['URL']).'">'.$tab[0].'</a>';
				unset($tab[0]);
				$STR.=implode(";",$tab).': ';
				foreach ($LIST_UNIPROTS as $UNIPROT) $STR.= $UNIPROT[0].' ';
				$STR.='</li>';
			}
			else
			{
				foreach ($LIST_UNIPROTS as $UNIPROT)
				{
					$STR.='<li><a href="'.str_replace("%u",$UNIPROT[0],str_replace("%s",$tab[0],$LIST_N['INFO']['URL'])).'">'.$tab[0].' - '.$UNIPROT[0].'</a>';
					$T2=$tab;unset($T2[0]);
					$STR.=' ; '.implode(";",$T2);
				
				$STR.='</li>';
				}
			}
			//
			

		}
		$STR.='</ul>';
	}
	$STR.='</div>';
	$STR_HEAD.='<td>'.$N_DB.'</td><td>'.$N_LINK.'</td><td><a href="#DB_'.$DBID.'"><input type="button" value="GO TO"></a></td></tr>';
}

changeValue("links","GENE",$USER_INPUT['PORTAL']['DATA']['SYMBOL']);
changeValue("links","data",$STR);
changeValue("links","TABLE",$STR_HEAD);
?>