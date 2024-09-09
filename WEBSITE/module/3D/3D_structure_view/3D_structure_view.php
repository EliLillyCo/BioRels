<?php
ini_set('memory_limit', '2000M');
if (!defined("BIORELS")) {
    header("Location:/");
}

changeValue("3D_structure_view", "PDB_ID", $USER_INPUT['PAGE']['VALUE']);
if (isset($USER_INPUT['PARAMS'])) {
    changeValue("3D_structure_view", "PARAMS", '/PARAMS/' . implode("/", $USER_INPUT['PARAMS']));
}

$STR = '';
$PDB_ID = $MODULE_DATA['ENTRY']['PDB_ID'];
foreach ($MODULE_DATA['UN_SEQ'] as $NUS => &$US_INFO) {

    $GID = $US_INFO['INFO']['GENE_ID'];
    $STR_CH = 'CHAIN';

    if (count($US_INFO['CHAIN']) > 1) {
        $STR_CH .= 'S';
    }

    $STR_MENU = '
	<div id="menu" style="left: 98%;
    position: relative;
    top: -31px;">
		  	<div class="dropdown">
				<span class="dropbtn"><img id="transcript_seq_tool_but" src="/require/img/tools.png" style="width: 20px;"></span>
				<div class="dropdown-content" style="left:-130px">';

    foreach ($US_INFO['CHAIN'] as $CHAIN_ID) {
        $CH_INFO = &$MODULE_DATA['CHAIN'][$CHAIN_ID];
        $STR_CH .= ' ' . $CH_INFO['INFO']['CHAIN_NAME'];
        $CHN = $CH_INFO['INFO']['CHAIN_NAME'];
        $STR_MENU .= '<h3 style="text-align: center"> CHAIN ' . $CHN . '</h3>
					<a href="/3D_VIEW/' . $PDB_ID . '/PARAMS/CHAIN/' . $CHN . '" class="btn">View 3D structure</a>
					<a href="/PDB/3D_FILE/' . $PDB_ID . '/PARAMS/CHAIN/' . $CHN . '" class="btn">Download PDB File</a>
					<a href="/MOL2/3D_FILE/' . $PDB_ID . '/PARAMS/CHAIN/' . $CHN . '" class="btn">Download MOL2 File</a>';
    }

    $STR_MENU .= '</div></div></div>';
    $STR .= '<h2>' . $STR_CH . '</h2>' . $STR_MENU . '
	<table class="table" style="width:100%"><tr><th colspan="2" style="width:50%;text-align:center">Gene information</th>
	<th colspan="2" style="width:50%;text-align:center">Protein information</th></tr><tr><th style="width:16%">Gene ID:</th><td><a href="/GENEID/' . $GID . '">' . $GID . '</a></td>
					  <th>Uniprot:</th><td> <a href="' . str_replace('${LINK}', $US_INFO['INFO']['UN_IDENTIFIER'], $GLB_CONFIG['LINK']['UNIPROT']['UNIID']) . '">' . $US_INFO['INFO']['UN_IDENTIFIER'] . '</a></td></tr>
					</tr>
					<tr><th>Gene Symbol:</th><td><a href="/GENEID/' . $GID . '">' . $US_INFO['INFO']['SYMBOL'] . '</a></td>
					  <th>Sequence:</th><td>  <a href="/GENEID/' . $GID . '/SEQUENCE/' . $US_INFO['INFO']['ISO_ID'] . '">' . $US_INFO['INFO']['ISO_ID'] . '</a></td></tr>
					</tr>
					<tr><th>Gene Name:</th><td colspan="3">' . $US_INFO['INFO']['FULL_NAME'] . '</td>

					</tr>
					<tr><th>Sequence Description/Note:</th><td colspan="3">' . $US_INFO['INFO']['NOTE'] . ' ' . $US_INFO['INFO']['DESCRIPTION'] . '</td></tr>
					</table>';
    $STR .= '<div class="sequence" id="protein_seq_view" style="width:fit-content;max-width:1200px; max-height:500px;">
						  <div class="tsv_block" style="margin-bottom:50px; overflow:scroll; white-space:nowrap; display:flex; flex-direction:column">
						  <div style="white-space:pre;position:relative"  id="seq">';
    $STR_L = '';
    $STR_I = '1';
    foreach ($US_INFO['SEQ'] as $N => &$SQ) {
        $STR .= $SQ[1];
        if ($N % 10 == 1) {
            $STR_L .= '|';
            if ($N == 1) {
                continue;
            }

            $LT = strlen((string) ($N));
            for ($I = $LT; $I < 10; ++$I) {
                $STR_I .= ' ';
            }

            $STR_I .= ($N);

        } else {
            $STR_L .= '.';
        }

    }
    $STR .= '</div><div style="white-space:pre;position: relative;top: -10px;">' . $STR_L . '</div><div style="white-space:pre;  position: relative;top: -16px;">' . $STR_I . '</div>';

    $STR .= '</div></div>';
}
changeValue("3D_structure_view", "UN_SEQ_BLOCK", $STR);

?>