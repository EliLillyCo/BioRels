<?php

///////////////////
/////////////////// file: r_def_USER_INPUT['PAGE'].php
/////////////////// owner: DESAPHY Jeremy
/////////////////// creation date: 11/26/18
/////////////////// purpose: Define which USER_INPUT['PAGE'] to load

/// BIORELS defined in index.php. Not existing? Go to index.php
if (!defined("BIORELS")) header("Location:/");/// BIORELS defined in index.php. Not existing? Go to index.php

//#2	TARGET	Does it require target info: 1-YES 2-NO 3-ANY
//#3	PARAMS 	Does it require params: 1-YES 2-NO 3-ANY
$DEBUG_TG=false;
if ($DEBUG_TG){
	
 echo "<pre>";echo "USER INPUT:\n";print_r($USER_INPUT);
 echo "################################\n";
// echo "<pre>";echo "GLB CONFIG:\n";print_r($GLB_CONFIG);
 echo "################################\n";
 echo "START EXEC\n";
}
$DEBUG_TG=false;
$MAX_USER_ACCESS=0;
if ($USER['Access'][1])$MAX_USER_ACCESS=1;


	preloadHTML("HEADER");
	
	$LEFT_MENU_LOADED=false;	
	if ($DEBUG_TG){echo "SELECTION ";}
	global $GLB_CONFIG;
	if ($DEBUG_TG){print_r($GLB_CONFIG['PAGE'][$USER_INPUT['PAGE']['NAME']]);}
	
	if ($USER_INPUT['TOPIC']!='GLOBAL')
	{
		if ($DEBUG_TG){echo "PRELOAD TOPIC ";}
		$LEFT_MENU_LOADED=true;
		preloadTopicMenu($USER_INPUT['TOPIC'],($USER_INPUT['PAGE']['NAME']=='WELCOME'));
	}
	if($USER_INPUT['PORTAL']['NAME']!='' ) 
	{
		if ($DEBUG_TG){echo "PRELOAD PORTAL MENU\n ";}
		preloadPortalMenu($USER_INPUT['PORTAL']['NAME']);
		$LEFT_MENU_LOADED=true;
	}
	
	if (!$LEFT_MENU_LOADED && $USER_INPUT['PAGE']['NAME']!='WELCOME' && $USER_INPUT['PAGE']['NAME']!='SEARCH'
	&& $USER_INPUT['PAGE']['NAME']!='NEW_SEARCH'	){//echo "EMPTY\n";
		if ($DEBUG_TG){echo "EMPTY PORTAL ";}
		preloadPortalMenu("EMPTY_PORTAL");
		if ($DEBUG_TG){echo "OUT EMPTY PORTAL ";}
	}

	
	
	if ($USER_INPUT['PAGE']['NAME']=='WELCOME')	
	{
		
		if ($DEBUG_TG){echo "WELCOME\n";}
		if ($USER_INPUT['TOPIC']!='GLOBAL')
		{
			
			if ($DEBUG_TG){echo "GLOBAL\n ";}
			preloadHTML($USER_INPUT['TOPIC'],'TOPIC');
		}
		else if($USER_INPUT['PORTAL']['NAME']!='')
		{
			
			if ($DEBUG_TG){echo "PORTAL NAME\n";}
			$CURRENT_MODULE=null;
		
		
		foreach ($GLB_CONFIG['PORTAL'][$USER_INPUT['PORTAL']['NAME']] as $K_MOD=>$TEST_MOD)
		{
			for ($I=0;$I<strlen($TEST_MOD['LEVEL']);++$I)
			{
				if (substr($TEST_MOD['LEVEL'],$I,1)==1 && $USER['Access'][$I]==1)
				{
					$CURRENT_MODULE=&$GLB_CONFIG['PORTAL'][$USER_INPUT['PORTAL']['NAME']][$K_MOD];}
			}
		}
		if ($CURRENT_MODULE==null)  $CURRENT_MODULE=& $GLB_CONFIG['PAGE']['NO_ACCESS'][0];
			
			//print_r($GLB_CONFIG['PORTAL'][$USER_INPUT['PORTAL']['NAME']]);
			if(isset($CURRENT_MODULE['WITH_EXPORT']) && $CURRENT_MODULE['WITH_EXPORT']=='true'){
				if ($DEBUG_TG){echo "EXPORT LOAD\n";}
				$DIR='';
			if (isset($CURRENT_MODULE['IS_PRIVATE']))	$DIR='private/';
			$DIR.='module/'.$CURRENT_MODULE['LOC'];
			$TPATH=$DIR.'/'.$CURRENT_MODULE['FNAME'].'_export.php';
				
			require_once($TPATH);
				//echo "WITH EXPORT\n";
			}
			else
			{
				
				if ($DEBUG_TG)echo "PORTAL PRELOAD\n";
				preloadHTML($USER_INPUT['PORTAL']['NAME'],'PORTAL');
			}
		}
		else
		{

			if ($DEBUG_TG){echo "PAGE PRELOAD\n";}
		 preloadHTML($USER_INPUT['PAGE']['NAME']);
		}
	}
	else
	{
		if ($USER_INPUT['PAGE']['NAME']=='')
		{
			
			$HTML_ORDER[100]='MSG_ERR';
			$HTML['MSG_ERR']= "Unable to find requested module";
		}
		else {

		// echo "<pre>";
		// 	print_r($GLB_CONFIG['PAGE'][$USER_INPUT['PAGE']['NAME']] );exit;
		$CURRENT_MODULE=null;
		foreach ($GLB_CONFIG['PAGE'][$USER_INPUT['PAGE']['NAME']] as $K_MOD=>$TEST_MOD)
		{
			for ($I=0;$I<strlen($TEST_MOD['LEVEL']);++$I)
			{
				if (substr($TEST_MOD['LEVEL'],$I,1)==1 && $USER['Access'][$I]==1)
				{
					$CURRENT_MODULE=&$GLB_CONFIG['PAGE'][$USER_INPUT['PAGE']['NAME']][$K_MOD];}
			}
		}
		
		if ($CURRENT_MODULE==null)  $CURRENT_MODULE=& $GLB_CONFIG['PAGE']['NO_ACCESS'][0];
		if(isset($CURRENT_MODULE['WITH_EXPORT']) && $CURRENT_MODULE['WITH_EXPORT']=='true'){
			$DIR='';
			if (isset($CURRENT_MODULE['IS_PRIVATE']))	$DIR='private/';
			$DIR.='module/'.$CURRENT_MODULE['LOC'];
		
				if ($DEBUG_TG)echo "WITH NORMAL EXPORT LOADING\n";
				$TPATH=$DIR.'/'.$CURRENT_MODULE['FNAME'].'_export.php';
				require_once($TPATH);
				
				}
		
			else
			{
		if ($DEBUG_TG)echo "NORMAL PRELOAD\n";
		
		preloadHTML($USER_INPUT['PAGE']['NAME']);
			}
		}
	}//exit;

	



?>
