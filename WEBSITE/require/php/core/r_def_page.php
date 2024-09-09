<?php

///////////////////
/////////////////// file: r_def_page.php
/////////////////// owner: DESAPHY Jeremy
/////////////////// creation date: 11/26/18
/////////////////// purpose: Define which page to load

/// BIORELS defined in index.php. Not existing? Go to index.php
if (!defined("BIORELS")) header("Location:/"); /// BIORELS defined in index.php. Not existing? Go to index.php


if (isset($USER_INPUT['PORTAL']))
{
	
	switch ($USER_INPUT['PORTAL']['NAME'])
	{
		case 'GENE':
		{
			switch($USER_INPUT['PORTAL']['TYPE'])
			{
				case 'GENEID': 
				
					$USER_INPUT['PORTAL']['DATA']=gene_portal_geneID($USER_INPUT['PORTAL']['VALUE'],$GLB_CONFIG['GLOBAL']['TG_DIR']);
					if ($USER_INPUT['PORTAL']['DATA']==array())$USER_INPUT['PAGE']['NAME']='UNKNOWN_REC';
					break;
				case 'GENE': 
					$USER_INPUT['PORTAL']['DATA']=gene_portal_gene($USER_INPUT['PORTAL']['VALUE']);
				break;
			}
			break;
		}
		case 'PROTEIN':
		{
			switch($USER_INPUT['PORTAL']['TYPE'])
			{
				case 'UNIPROT_ID': 
					$USER_INPUT['PORTAL']['DATA']=protein_portal_uniprotID($USER_INPUT['PORTAL']['VALUE'],false)[0];
					
					break;
				case 'UNIPROT_AC': 
					$USER_INPUT['PORTAL']['DATA']=protein_portal_uniprotAC($USER_INPUT['PORTAL']['VALUE'],false)[0];
				break;
			}
			break;
		}
		case 'PUBLICATION':
			{
				
				switch($USER_INPUT['PORTAL']['TYPE'])
				{
					case 'PUBMED': 
						$USER_INPUT['PORTAL']['DATA']=loadPublicationData($USER_INPUT['PORTAL']['VALUE']);
						
						break;
					case 'GENE':
						$USER_INPUT['PORTAL']['DATA'] = gene_portal_gene($USER_INPUT['PORTAL']['VALUE']);
						break;
				}
				break;
			}
		case 'DRUG': {
				switch ($USER_INPUT['PORTAL']['TYPE']) {
					case 'DRUG':
						$USER_INPUT['PORTAL']['DATA'] = drug_portal_drug_name($USER_INPUT['PORTAL']['VALUE']);
						break;
				}
				break;
			}

		case 'COMPOUND': {
				switch ($USER_INPUT['PORTAL']['TYPE']) {
					case 'COMPOUND':
						$USER_INPUT['PORTAL']['DATA'] = compound_portal_compound_sm_name($USER_INPUT['PORTAL']['VALUE']);
						break;
				}
				break;
			}
		case 'PROTEIN': {
				switch ($USER_INPUT['PORTAL']['TYPE']) {
					case 'UNIPROT_ID':
						$USER_INPUT['PORTAL']['DATA'] = protein_portal_uniprotID($USER_INPUT['PORTAL']['VALUE'], false)[0];

						break;
					case 'UNIPROT_AC':
						$USER_INPUT['PORTAL']['DATA'] = protein_portal_uniprotAC($USER_INPUT['PORTAL']['VALUE'], false)[0];
						break;
				}
				break;
			}
		case 'PUBLICATION': {

				switch ($USER_INPUT['PORTAL']['TYPE']) {
					case 'PUBMED':
						$USER_INPUT['PORTAL']['DATA'] = loadPublicationData($USER_INPUT['PORTAL']['VALUE']);

						break;
					case 'DOI':
						$USER_INPUT['PORTAL']['DATA'] = loadPublicationData($USER_INPUT['PORTAL']['VALUE'], 'DOI');
						break;
				}
				break;
			}
		case 'ASSAY': {

				switch ($USER_INPUT['PORTAL']['TYPE']) {
					case 'ASSAY':
						$DEF_MODULE = null;
						foreach ($GLB_CONFIG['PORTAL'][$USER_INPUT['PORTAL']['NAME']] as $K_MOD => $TEST_MOD) {
							for ($I = 0; $I < strlen($TEST_MOD['LEVEL']); ++$I) {
								if (substr($TEST_MOD['LEVEL'], $I, 1) == 1 && $USER['Access'][$I] == 1) {
									$DEF_MODULE = &$GLB_CONFIG['PORTAL'][$USER_INPUT['PORTAL']['NAME']][$K_MOD];
								}
							}
						}
						//print_r($CURRENT_MODULE);exit;
						if (isset($DEF_MODULE['IS_PRIVATE'])) {

							$USER_INPUT['PORTAL']['DATA'] = assay_private_portal($USER_INPUT['PORTAL']['VALUE']);
						} else $USER_INPUT['PORTAL']['DATA'] = assay_portal($USER_INPUT['PORTAL']['VALUE']);
						break;
				}
				break;
			}
		case 'DISEASE': {
				switch ($USER_INPUT['PORTAL']['TYPE']) {
					case 'DISEASE':

						$USER_INPUT['PORTAL']['DATA'] = disease_portal_disease_name($USER_INPUT['PORTAL']['VALUE']);

						break;
				}
				break;
			}
	}
}
/*	
	
	if ($USER_INPUT['PAGE']=="WELCOME") $USER_INPUT['PAGE']='ENTRY';
	insertToStat($_SERVER['REQUEST_URI'],(($USER_INPUT['SIM']=="")?'F':'T'),$TARGET['GENE_ID']);
	
}else insertToStat($_SERVER['REQUEST_URI'],(($USER_INPUT['SIM']=="")?'F':'T'),-1); 
// print_r($USER_INPUT);
*/

function addExternalInfo(&$result)
{
}

/*
echo "<pre>";
print_r($USER_INPUT);
print_r($TARGET);
echo "</pre>";
*/
/// Define the target

/*
echo "<pre>";
print_r($USER_INPUT);
echo "</pre>";
echo $PAGE;


*/
