<?php

ini_set('memory_limit','300M');
$NAME=$MODULE_DATA['ENTRY'][0]['NAME'];
changeValue("publi_author","AUTHOR",$NAME);
if ($MODULE_DATA['ENTRY'][0]['PRIM_NAME']!='')changeValue("publi_author","INSTIT",$MODULE_DATA['ENTRY'][0]['PRIM_NAME']);
else changeValue("publi_author","INSTIT",$MODULE_DATA['ENTRY'][0]['INSTIT_NAME']);

print_r($MODULE_DATA);
$STR='';
$COLLEAGUES=array();$COL=array();
	foreach ($MODULE_DATA['PUBS'] as $PUB)
	{
		$USER_INPUT['PAGE']['VALUE']=$PUB['PMID'];
		$STR.=str_replace($NAME,'<span class="bold gree_c">'.$NAME.'</span>',loadHTMLAndRemove('PUBLICATION'));
		foreach ($PUB['INFO']['AUTHORS'] as $AU)
		{
			if (!isset($COLLEAGUES[$AU['NAME']]))$COLLEAGUES[$AU['NAME']]=1;
			else $COLLEAGUES[$AU['NAME']]++;
			$COL[$AU['NAME']][]=array($AU['INSTIT_NAME'],$AU['PMID_AUTHOR_ID'],(($AU['INSTIT_PRIM_ID']!="")?$AU['INSTIT_PRIM_ID']:$AU['PMID_INSTIT_ID']));
		}
	}
	changeValue("publi_author","PUBLICATIONS",$STR);
arsort($COLLEAGUES);
$STR='';
foreach ($COLLEAGUES as $COL_NAME=>$NUM)
{
	if ($COL_NAME==$NAME)continue;
	$STR2='';$STR3='';
	if (isset($COL[$COL_NAME]))
	{
		$COL[$COL_NAME]=array_unique($COL[$COL_NAME]);
		foreach ($COL[$COL_NAME] as $INS)
		{
			$STR2.=$INS[0].'<a href="/PUBLI_INSTIT/'.$INS[2].'">&#128279;</a><br/>';
			$STR3.='<a href="/PUBLI_AUTHOR/'.$INS[1].'">&#128279;</a>';
		}	
		$STR2=substr($STR2,0,-5);
	}
	
	$STR.='<tr><td>'.$COL_NAME.' '.$STR3.'</td><td>'.$NUM.'</td><td>';
	$STR.=$STR2;
	$STR.='</td></tr>';
	
}
changeValue("publi_author","COLLEAGUES",$STR);

$STR='';
$INSTITS=array();
foreach ($MODULE_DATA['IDENTITY'] as &$ENTRY)
{
	$IN=(($ENTRY['PRIM_NAME']!="")?$ENTRY['PRIM_NAME']:$ENTRY['INSTIT_NAME']);
	
	$INSTITS[$ENTRY['NAME']][$IN]=array($ENTRY['PMID_AUTHOR_ID'],(($ENTRY['PRIM_ID']==""?$ENTRY['PMID_INSTIT_ID']:$ENTRY['PRIM_ID'])));
}
	
foreach ($INSTITS as $AUTH_NAME=>$TI)	
	
foreach ($TI as $INSTIT_NAME=>$INFO)
{
	$STR.='<tr><td>'.$AUTH_NAME.' <a href="/PUBLI_AUTHOR/'.$INFO[0].'">&#128279;</a></td>
	<td>'.$INSTIT_NAME.'<a href="/PUBLI_INSTIT/'.$INFO[1].'">&#128279;</a>';
	$STR.='</td></tr>';
	
	
}
changeValue("publi_author","ALT",$STR);


/*



Array
(
    [ENTRY] => Array
        (
            [0] => Array
                (
                    [PMID_AUTHOR_ID] => 13933481
                    [NAME] => Furst, Christine
                    [PMID_INSTIT_ID] => 340657
                    [ORCID_ID] => 
                    [INSTIT_NAME] => Institute of Geosciences and Geography, Martin Luther University Halle-Wittenberg, Halle, Germany.
                    [INSTIT_PRIM_ID] => 7651532
                )

        )

    [ALT] => Array
        (
            [0] => Array
                (
                    [PMID_AUTHOR_ID] => 6256
                    [NAME] => Furst, Christine
                    [PMID_INSTIT_ID] => 7651532
                    [ORCID_ID] => 
                    [INSTIT_NAME] => Institute for Geosciences and Geography, Department Sustainable Landscape Development, Martin Luther University Halle-Wittenberg, Von-Seckendorff-Platz 4,  06120, Halle, Germany.
                    [INSTIT_PRIM_ID] => 
                )

            [1] => Array
                (
                    [PMID_AUTHOR_ID] => 13933481
                    [NAME] => Furst, Christine
                    [PMID_INSTIT_ID] => 7651532
                    [ORCID_ID] => 
                    [INSTIT_NAME] => Institute for Geosciences and Geography, Department Sustainable Landscape Development, Martin Luther University Halle-Wittenberg, Von-Seckendorff-Platz 4,  06120, Halle, Germany.
                    [INSTIT_PRIM_ID] => 
                )

            [2] => Array
                (
                    [PMID_AUTHOR_ID] => 2445516
                    [NAME] => Furst, Christine
                    [PMID_INSTIT_ID] => 7651532
                    [ORCID_ID] => 0000-0002-9678-4844
                    [INSTIT_NAME] => Institute for Geosciences and Geography, Department Sustainable Landscape Development, Martin Luther University Halle-Wittenberg, Von-Seckendorff-Platz 4,  06120, Halle, Germany.
                    [INSTIT_PRIM_ID] => 
                )

            [3] => Array
                (
                    [PMID_AUTHOR_ID] => 4220504
                    [NAME] => Furst, Christine
                    [PMID_INSTIT_ID] => 7651532
                    [ORCID_ID] => 
                    [INSTIT_NAME] => Institute for Geosciences and Geography, Department Sustainable Landscape Development, Martin Luther University Halle-Wittenberg, Von-Seckendorff-Platz 4,  06120, Halle, Germany.
                    [INSTIT_PRIM_ID] => 
                )

        )

    [PUBS] => Array
        (
            [0] => Array
                (
                    [PMID] => 32841244
                    [PMID_AUTHOR_ID] => 6256
                )

            [1] => Array
                (
                    [PMID] => 32406977
                    [PMID_AUTHOR_ID] => 2445516
                )

            [2] => Array
                (
                    [PMID] => 32064338
                    [PMID_AUTHOR_ID] => 4220504
                )

            [3] => Array
                (
                    [PMID] => 29258035
                    [PMID_AUTHOR_ID] => 13933481
                )

            [4] => Array
                (
                    [PMID] => 30059918
                    [PMID_AUTHOR_ID] => 13933481
                )

        )

)
*/
?>