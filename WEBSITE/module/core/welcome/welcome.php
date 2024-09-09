<?php
global $GLB_CONFIG;






if (isset($USER_INPUT['STATUS']) && $USER_INPUT['STATUS'][0] == 'ISSUE') {
    changeValue("welcome", "ISSUE_MESSAGE", '<div class="w3-container w3-col s12 issue_content">' . $USER_INPUT['STATUS'][1] . '</div>');
}

$STR = "";

foreach ($GLB_CONFIG['PAGE'] as $TOOL => $TOOL_LEVS) {
    foreach ($TOOL_LEVS as $TOOL_INFO) {
        if (!isset($TOOL_INFO['DESC']) || !isset($TOOL_INFO['TAG']) || !isset($TOOL_INFO['NAME'])) {
            continue;
        }

        if (isset($TOOL_INFO['LEVEL'])) {
            $has_access = compareAccess($TOOL_INFO['LEVEL']);
            if ($has_access) {
                $STR .= '<tr><td style="cursor:pointer;"><a href="/' . $TOOL_INFO['TAG'][0][0] . '">' . $TOOL_INFO['NAME'] . '</a></td><td>' . $TOOL_INFO['DESC'] . '</td></tr>';
            } else {
                $STR .= '<tr><td style="cursor:pointer;">' . $TOOL_INFO['NAME'] . '<div class="sprite small_lock_icon"></div></td><td>' . $TOOL_INFO['DESC'] . '</td></tr>';
            }
        } else {
            $STR .= '<tr><td style="cursor:pointer;"><a href="/' . $TOOL_INFO['TAG'][0][0] . '">' . $TOOL_INFO['NAME'] . '</a></td><td>' . $TOOL_INFO['DESC'] . '</td></tr>';
        }
    }
}

changeValue("welcome", "LIST_TOOL", $STR);




if (!isset($_SESSION['USER_DATA']))
{
	$_SESSION['USER_DATA']=getUserGroups($USER['id']);

}



if (hasPrivateAccess())
{
    
    $res=private_getNewsSourceStat();
}
else
$res=getNewsSourceStat();

$STR='';
foreach ($res as $line)
{

  if ($line['LAST_NAME']!=''){
        $STR.='<tr><td><a style="color:black;text-decoration:none" tooltip="Show all news for '.$line['SOURCE_NAME'].'" target="_blank" href="/PUBLI_NEWS/PARAMS/SOURCE/'.$line['SOURCE_NAME'].'">';
   
		$STR.=$line['LAST_NAME'].' '.$line['FIRST_NAME'];
	} 
	else {
        $STR.='<tr><td><a style="color:black;text-decoration:none" tooltip="Show all news for '.$line['SOURCE_NAME'].'" target="_blank" href="/SEARCH/PARAMS/NEWS_SEARCH/NEWS_COMPLEX/SOURCE='.$line['SOURCE_NAME'].'">';
   
		 $STR.=$line['SOURCE_NAME']; 
	}
    $STR.='</a></td><td>'.$line['CO'].'</td></tr>';
}
changeValue("welcome", "NEWS_SOURCE",$STR);





// /*ETER:
// Reference to the ETER project has to be made as follows: “Data source: ETER project. Download date XXX”.
// In scientific publications and reports, the following acknowledgment should be included: “Data have been provided by the European Tertiary Education Register (ETER), funded by the European Commission under the contracts EAC-2013-0308, EAC-2015-0280, 934533-2017 A08-CH and EAC-2021-0170”. */

$RELEASE_DATE = array();

    $RELEASE_DATE=getCurrReleaseDate();

foreach ($RELEASE_DATE['SOURCE'] as $TYPE=>$V)
{
    changeValue("welcome",$TYPE.'_0',$V[0]);
    changeValue("welcome",$TYPE.'_1',$V[1]);
}

$STR='<div class="w3-container w3-col s12 l12 m12" style="padding:10px">
<h3 class="portal_col1">Organism covered:</h3>';
foreach ($RELEASE_DATE['GENOME'] as $TAX_ID=>&$INFO_TAX)
{
    $STR.='<div class="w3-col s3 l3 m3" style="font-size:0.9em">
    <h4 style="text-align:center">'.$INFO_TAX[0]['SCIENTIFIC_NAME'].'</h4>';
    foreach ($INFO_TAX as &$ASSEMBLY)
    {
        $STR.='<table class="table table-sm"><tr><th>Assembly:</th><td>'.$ASSEMBLY['ASSEMBLY_ACCESSION'].'.'.$ASSEMBLY['ASSEMBLY_VERSION'].'</td></tr>
        <tr><th>Assembly Name:</th><td>'.$ASSEMBLY['ASSEMBLY_NAME'].'</td></tr>'.
        
        '<tr><th>Creation date:</th><td>'.$ASSEMBLY['CREATION_DATE'].'</td></tr>';
        $ANNOT=json_decode($ASSEMBLY['ANNOTATION'],true);
        $STR.='<tr><th>ContigN50:</th><td>'.$ANNOT['ContigN50'].'</td></tr>';
        $STR.='<tr><th>ScaffoldN50:</th><td>'.$ANNOT['ScaffoldN50'].'</td></tr>';
        $STR.='<tr><th>GenBank link:</th><td><a href="'.$ANNOT['FtpPath_GenBank'].'" target="_blank" rel="noopener">LINK</a></td></tr>';
        $STR.='<tr><th>Refseq link:</th><td><a href="'.$ANNOT['FtpPath_RefSeq'].'" target="_blank" rel="noopener">LINK</a></td></tr>';
        
        $STR.='</table>';
    }
    $STR.='</div>';
}
$STR.='</div>';

changeValue("welcome","GENOMES",$STR);

changeValue("welcome","NEWS",loadHTMLAndRemove('PUBLI_NEWS'));

?>
