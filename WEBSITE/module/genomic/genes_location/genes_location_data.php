<?php

if (!defined("BIORELS")) header("Location:/");

$MODULE_DATA=getGeneLocation($USER_INPUT['PORTAL']['VALUE']);
foreach ($MODULE_DATA['GENE_LOC'] as $CHR_SEQ_NAME=>&$LIST_GENES)
{
    foreach ($LIST_GENES as $GENE_ID=>&$GENE_SEQS)
    {
        if ($GENE_ID=='CHR_SEQ')continue;
        foreach ($GENE_SEQS as &$GS)
        {
           
            if ($LIST_GENES['CHR_SEQ']['SEQ_ROLE']!='assembled-molecule' && substr($GS['GENE_SEQ_NAME'],0,3)=='ENS')
            {
                if ($LIST_GENES['CHR_SEQ']['CHR_START_POS']!=1)
                {
                    $GS['START_POS']-=$LIST_GENES['CHR_SEQ']['CHR_START_POS']-1;
                    $GS['END_POS']-=$LIST_GENES['CHR_SEQ']['CHR_START_POS']-1;
                }
            }
        }
    }
}
//echo '<pre>';print_R($MODULE_DATA);exit;
?>