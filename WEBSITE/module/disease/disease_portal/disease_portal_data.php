<?php

if (!defined("BIORELS")) header("Location:/");

	$MODULE_DATA=getDiseaseEntry($USER_INPUT['PORTAL']['DATA']['DISEASE_TAG'],true,true);
	//echo '<pre>S:';print_R($MODULE_DATA);exit;
	
	if (hasPrivateAccess())
	{
	$MODULE_DATA['INFO']=private_getDiseaseInfo($MODULE_DATA['DISEASE_ENTRY_ID']);
	}
	else 	
	
	$MODULE_DATA['INFO']=getDiseaseInfo($MODULE_DATA['DISEASE_ENTRY_ID']);
	 //$ENTRY['INFO']=getDiseaseInfo($USER_INPUT['PORTAL']['DATA']['DISEASE_TAG']);
	 

	 if (isset($MODULE_DATA['INFO']['DOCS']))
	 {
		foreach ($MODULE_DATA['INFO']['DOCS'] as &$DC)
		{
			if (!isset($DC['text/xml']))continue;

			echo '<pre>';

			$str=getNewsfile($DC['text/xml']['DOCUMENT_HASH'])['DOCUMENT_CONTENT'];
			$objXML = new xml2Array();
			$arrOutput = $objXML->parse($str);
			//print_r($arrOutput);

$ENTRY=array(
					'ABSTRACT'=>'',
					'AUTHORS'=>array()
				);


				$AUTHORS=&$arrOutput['BOOK-PART-WRAPPER']['children']['BOOK-META']['children']['CONTRIB-GROUP']['children']['CONTRIB'];
				foreach ($AUTHORS as &$AU)
				{
					$ENTRY['AUTHORS'][]=$AU['children']['NAME']['children']['SURNAME']['tagData'].' '.$AU['children']['NAME']['children']['GIVEN-NAMES']['tagData'].' - '.$AU['children']['ROLE']['tagData'];	
						}

				$ABSTR=&$arrOutput['BOOK-PART-WRAPPER']['children']['BOOK-PART']['children']['BOOK-PART-META']['children']['ABSTRACT']['children']['SEC'];
				foreach ($ABSTR as &$SEC)
				{
					$ENTRY['ABSTRACT'].='<h4>'.$SEC['children']['TITLE']['tagData'].'</h4>';
					if (isset($SEC['children']['P'][0]))
					{
						foreach ($SEC['children']['P'] as &$P)
						{
							$ENTRY['ABSTRACT'].='<p><span style="font-style:italic">'.$P['children']['ITALIC']['tagData'].'</span>'.$P['tagData'].'</p>';
						}
					}
					else $ENTRY['ABSTRACT'].='<p>'.$SEC['children']['P']['tagData'].'</p>';
				}
				$MODULE_DATA['DESCRIPTION']=$ENTRY;
		}
	 }
	 class xml2Array {
    
		var $arrOutput = array();
		var $resParser;
		var $strXmlData;
		

		function revisitArray(&$ARR)
		{
			$NEW_ARR=array();
			//var_dump($ARR);
			$LIST=array();
			foreach ($ARR as $K=> &$REC)
			{
				if (isset($REC['name']))
				{
					if (isset($REC['children']))$REC['children']=$this->revisitArray($REC['children']);
				$NEW_ARR[$REC['name']][]=$REC;
				$LIST[$REC['name']]=1;
				}
				else $NEW_ARR[$K]=$REC;
			}
			foreach ($LIST as $RC=>$RN)
			{
				if (count($NEW_ARR[$RC])==1)$NEW_ARR[$RC]=$NEW_ARR[$RC][0];
			}
			return $NEW_ARR;
			
			

		}

		function parse($strInputXML) {
		
				$this->resParser = xml_parser_create ();
				xml_set_object($this->resParser,$this);
				xml_set_element_handler($this->resParser, "tagOpen", "tagClosed");
				
				xml_set_character_data_handler($this->resParser, "tagData");
			
				$this->strXmlData = xml_parse($this->resParser,$strInputXML );
				if(!$this->strXmlData) {
				   die(sprintf("XML error: %s at line %d",
				xml_error_string(xml_get_error_code($this->resParser)),
				xml_get_current_line_number($this->resParser)));
				}
								
				xml_parser_free($this->resParser);
				//print_r($this->arrOutput);
				$TMP=$this->revisitArray($this->arrOutput);

				$this->arrOutput=$TMP;

				return $this->arrOutput;
		}
		function tagOpen($parser, $name, $attrs) {
		   $tag=array("name"=>$name,"attrs"=>$attrs); 
		   array_push($this->arrOutput,$tag);
		}
		
		function tagData($parser, $tagData) {
		   if(trim($tagData)) {
				if(isset($this->arrOutput[count($this->arrOutput)-1]['tagData'])) {
					$this->arrOutput[count($this->arrOutput)-1]['tagData'] .= $tagData;
				} 
				else {
					$this->arrOutput[count($this->arrOutput)-1]['tagData'] = $tagData;
				}
		   }
		}
		
		function tagClosed($parser, $name) {
		   $this->arrOutput[count($this->arrOutput)-2]['children'][] = $this->arrOutput[count($this->arrOutput)-1];
		   array_pop($this->arrOutput);
		}
	}





?>