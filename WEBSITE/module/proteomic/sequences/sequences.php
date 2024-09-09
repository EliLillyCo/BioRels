<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

if (isset($MODULE_DATA['ERROR'])) {
    removeBlock("sequences", "VALID");
    removeBlock("sequences", "INVALID");
    changeValue("sequences", "ERROR_MSG", $MODULE_DATA['ERROR']);
    return;
}
$LINK = '';
if ($MODULE_DATA['INPUT'] == 'GENE') {
    $GENE_ID = $USER_INPUT['PORTAL']['DATA']['GENE_ID'];
    changeValue("sequences", "ENTRY_NAME", $USER_INPUT['PORTAL']['DATA']['SYMBOL']);
    $LINK = '/GENEID/' . $USER_INPUT['PORTAL']['DATA']['GENE_ID'];
    changeValue("sequences", "LINK", $LINK);
} else if ($MODULE_DATA['INPUT'] == 'PROTEIN') {
    changeValue("sequences", "ENTRY_NAME", $USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER']);
    $LINK = '/UNIPROT_ID/' . $USER_INPUT['PORTAL']['DATA']['PROT_IDENTIFIER'];
    changeValue("sequences", "LINK", $LINK);
}

if (count($MODULE_DATA['SEQ']) == 0) {
    removeBlock("sequences", "VALID");
    removeBlock("sequences", "NO_INPUT");
    return;
}

removeBlock("sequences", "NO_INPUT");

$str = '';
$MAP = array();
foreach ($MODULE_DATA['SEQ'] as $ID => $SEQ) {
    $MAP[$ID] = $SEQ['SEQ']['ISO_ID'];
    $str .= '<tr><td> ' . $SEQ['SEQ']['PROT_IDENTIFIER'];
    $str .= ' ' . (($SEQ['SEQ']['STATUS'] == 'T') ? '<span class="check"></span>' : '');
    if ($SEQ['SEQ']['CONFIDENCE'] != '') {
        $str .= '<div style="    position: relative;
    cursor: pointer;
    top: -10px;height:10px"><span style="color:gold">';
        for ($I = 5; $I >= 1; --$I) {
            $str .= '&#8902;';
            if ($I == $SEQ['SEQ']['CONFIDENCE']) {
                $str .= '</span>';
            }

        }
        $str .= '</div>';
    }
    $str .= '</td>
<td>' . $SEQ['SEQ']['ISO_ID'] . ' ' . (($SEQ['SEQ']['IS_PRIMARY'] == 'T') ? '<span class="check"></span>' : '') . '</td>
<td>' . $SEQ['SEQ']['DESCRIPTION'] . '</td>
<td>';
    if (isset($SEQ['TRANSCRIPT'])) {
        foreach ($SEQ['TRANSCRIPT'] as $TR) {
            $NAME = $TR['TRANSCRIPT_NAME'];
            if ($TR['TRANSCRIPT_VERSION'] != '') {
                $NAME .= '.' . $TR['TRANSCRIPT_VERSION'];
            }

            $str .= $NAME . '<div class="dropdown">
		<span class="dropbtn">&#128279;</span>
		<div class="dropdown-content">';

            $str .= '<a href="' . $LINK . '/TRANSCRIPT/' . $NAME . '">Show transcript sequence</a>
		</div>
	  </div><br/>';
        }
    }
    $str .= '</td><td>
<div class="dropdown">
	<span class="dropbtn"><img  src="/require/img/tools.png" style="width: 20px;"/></span>
	<div class="dropdown-content" style="left:-175px">
	<a href="' . $LINK . '/SEQUENCE/' . $SEQ['SEQ']['ISO_ID'] . '"   class="btn">Show protein sequence</a>
	<a href="' . $LINK . '/BLASTP/PARAMS/SEQUENCE/' . $TR['TRANSCRIPT_NAME'] . '"   class="btn" >Blast this sequence</a>

	</div></div>';

    $str .= '</td></tr>';

}
changeValue("sequences", "TBL", $str);
function lineargradient($ra, $ga, $ba, $rz, $gz, $bz, $iterationnr)
{
    $colorindex = array();
    for ($iterationc = 1; $iterationc <= $iterationnr; $iterationc++) {
        $iterationdiff = $iterationnr - $iterationc;
        $R = dechex(intval((($ra * $iterationc) + ($rz * $iterationdiff)) / $iterationnr));
        $G = dechex(intval((($ga * $iterationc) + ($gz * $iterationdiff)) / $iterationnr));
        $B = dechex(intval((($ba * $iterationc) + ($bz * $iterationdiff)) / $iterationnr));
        
        if ($R == "0") {
            $R = '00';
        }

        if ($G == "0") {
            $G = '00';
        }

        if ($B == "0") {
            $B = '00';
        }

        
        $colorindex[] = '#' .
            $R .
            $G .
            $B;
    }
    return $colorindex;
}

$colorindex = lineargradient(
    0, 255, 100, // rgb of the start color
    254, 1, 1, // rgb of the end color
    100// number of colors in your linear gradient
);

$str = '<table class="table table-sm" style="overflow-x:scroll;max-width:100%;width:97%"><thead><tr><td></td>';
foreach ($MAP as $NAME) {
    $str .= '<th class="rotate"><div><span>' . $NAME . '</span></div></th>';
}

$str .= '</tr></thead><tbody>';
$ORDER = array_flip(array_keys($MAP));
$MATRIX = array();
foreach ($ORDER as $ID => $K) {
    $MATRIX[$ID] = array_fill(0, count($ORDER), 'N/A');
}

foreach ($MODULE_DATA['SIM'] as &$REC) {
    $MATRIX[$REC['PROT_SEQ_REF_ID']][$ORDER[$REC['PROT_SEQ_COMP_ID']]] = $REC['PERC_IDENTITY'] * 100;
    $MATRIX[$REC['PROT_SEQ_COMP_ID']][$ORDER[$REC['PROT_SEQ_REF_ID']]] = $REC['PERC_IDENTITY'] * 100;
}

foreach ($MATRIX as $REF => $LIST) {
    $str .= '<tr><td>' . $MODULE_DATA['SEQ'][$REF]['SEQ']['ISO_ID'] . '</td>';
    foreach ($LIST as $T) {
        if ($T != 'N/A') {
            $str .= '<td style="color:' . $colorindex[floor($T)] . '">' . $T . '</td>';
        } else {
            $str .= '<td>' . $T . '</td>';
        }

    }
    $str .= '</tr>';
}
changeValue("sequences", "MATRIX", $str . '</tbody></table>');

$USER_INPUT['PAGE']['VALUE'] = '30395287';
$STR = loadHTMLAndRemove('PUBLICATION');
changeValue("sequences", "PUBLI", $STR);
removeBlock("sequences", "INVALID");

?>