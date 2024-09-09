<?php

if (!defined("BIORELS")) header("Location:/");



$TEMPLATE='<div class="api_grp" id="api_grp_${ID}">
    <div class="news_entry" id="api_all_${ID}">
			<div class="news_header" onclick="toggleAPI(\'${ID}\')">

				<div class="news_title_long">
					<div class="news_arrow" style="position:relative;top:15px" id="arrow_${ID}">▶</div>
					<div class="api_path" id="api_path_${ID}">/${FUNCTION}</div>
					<div class="api_title">${TITLE}</div>
				</div>
				<div class="news_source">${SOURCE}</div>
			</div>
			<div class="w3-container news_content w3-col s12 l12 m12" style="display:none" id="api_content_${ID}">
		
			<div class="api_description"><p>${DESCRIPTION}</p></div>
			<form id="api_form_${ID}" onsubmit="return false;">
		<div class="api_parameters">
			<table class="table table-sm">
				<thead>
					<tr>
						<th>Parameter</th>
						<th>Type</th>
						
						<th>Default:</th>
						<th>Value:</th>
					</tr>
				</thead>
				<tbody>
					${PARAMS}
				</tbody>
				</table>
				<br/>
				<div class="api_buttons">
				<button class="btn btn-primary" onclick="toggleValues(\'${ID}\')">Try it out</button>
				<button class="btn btn-primary" onclick="submitAPI(\'${ID}\')">Submit</button>
				<div style="display:inline-block" id="api_loading_${ID}"></div>
				</div>
<br/>
		</div>
		</form>
		</div>
		<div class="api_all_results" id="api_all_results_${ID}">
		<h5>POST Query:</h5>
		<div class="api_results" id="api_post_${ID}">
		</div><br/>
		<h5>GET Query:</h5>
		<div class="api_results" id="api_get_${ID}">
		</div><br/>
		<h5>Results:</h5>
		<div class="api_results" id="api_results_${ID}">
		</div>
		</div>
	</div></div>';

$PORTALS=array();
foreach ($MODULE_DATA['API'] as $K=> &$API)
{
	foreach ($API['PORTAL'] as $P=>&$LIST)
	{
		if (!isset($PORTALS[$P]))$PORTALS[$P]=array();
		foreach ($LIST as $L)
		{
			$PORTALS[$P][$L][]=$K;
			
		}
	}
}
changeValue("API_HOME","RULES",json_encode($PORTALS));
$STR='';
foreach ($PORTALS as $P=>$LIST)
{
	$STR='';
	foreach ($LIST as $L=>&$API)
	{
		$STR.='<option value="'.$P.'|'.$L.'">'.ucfirst($L).'</option>';
	}
	changeValue("API_HOME",str_replace(" ","_",$P).'_sel',$STR);
}
// exit;



$STR='';
foreach ($MODULE_DATA['API'] as $K=> &$API)
{
	
	$NEW_TEMPLATE=$TEMPLATE;
	$NEW_TEMPLATE=str_replace('${ID}',$K,$NEW_TEMPLATE);
	$NEW_TEMPLATE=str_replace('${TITLE}',$API['TITLE'],$NEW_TEMPLATE);
	$NEW_TEMPLATE=str_replace('${FUNCTION}',$API['FUNCTION'],$NEW_TEMPLATE);
	$NEW_TEMPLATE=str_replace('${DESCRIPTION}',$API['DESCRIPTION'],$NEW_TEMPLATE);

	// $tab=explode("|",$API['PORTAL']);
	// $ECOSYSTEM=explode("-",$tab[0]);
	
	$PARAMS='';
	foreach ($API['PARAMS'] as $NAME=>&$PARAM)
	{
		if ($PARAM['MULTI']!=array() )
		{
			if ($PARAM['EXAMPLE']=='multi_option')
			{
				for ($I=0;$I<10;++$I)
				{
					$PARAMS.='<tr><td style="max-width:35%;width:35%;">';

					$PARAMS.='<select name="'.$NAME.'__'.$I.'__key"><option value="" ></option>';
					foreach ($PARAM['MULTI'] as $NAMEP=> $P)
					{
						$PARAMS.='<option value="'.$NAMEP.'" >'.$P['NAME'].'</option>';
					}
					$PARAMS.='</select>';
					$PARAMS.='</td><td></td><td></td><td><input type="text" name="'.$NAME.'__'.$I.'__value'.'" value="" /></td></tr>';
				}
			}
			else 
			{
				foreach ($PARAM['MULTI'] as $NAMEP=> $P)
				{
					$PARAMS.='<tr><td style="max-width:35%;width:35%;';
					if ($P['REQUIRED']=='required') $PARAMS.=' font-weight:bold">*';else $PARAMS.='">';
					$PARAMS.=$P['NAME'].'</td><td>'.$P['TYPE'].'</td><td>'.$P['DEFAULT'].'</td><td><input type="text" name="'.$NAME.'__'.$NAMEP.'" value="" placeholder="'.$P['EXAMPLE'].'"/></td></tr>';
				}
			}
			continue;
		}
		else 
		{
		$PARAMS.='<tr><td style="max-width:35%;width:35%;';
		if ($PARAM['REQUIRED']=='required') $PARAMS.=' font-weight:bold">*';else $PARAMS.='">';
		$PARAMS.=$PARAM['NAME'].'</td><td>'.$PARAM['TYPE'].'</td><td>'.$PARAM['DEFAULT'].'</td><td><input type="text" name="'.$NAME.'" value="" placeholder="'.$PARAM['EXAMPLE'].'"/></td></tr>';
		}
	}
	$NEW_TEMPLATE=str_replace('${PARAMS}',$PARAMS,$NEW_TEMPLATE);
	$STR.=$NEW_TEMPLATE;
}
changeValue("API_HOME", "API_BLOCKS", $STR);



?>