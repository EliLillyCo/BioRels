<?php

if (!defined("BIORELS")) {
    header("Location:/");
}


switch ($USER_INPUT['VTYPE']) {
    case 'W':
        preloadHTML($USER_INPUT['PAGE']['NAME']);

        break;
    case 'CONTENT':
        $result['code'] = loadHTMLAndRemove($USER_INPUT['PAGE']['NAME']);
        ob_end_clean();
        echo json_encode($result);
        exit;
        break;
    case 'JSON':
        $MODULE_DATA = preloadData($USER_INPUT['PAGE']['NAME']);
        $MODULE_DATA = removePrimaryKeys($MODULE_DATA);
        foreach ($MODULE_DATA['GENE_SEQ_LOC'] as $K => &$V) {
            unset($V['CHR_SEQ_ID']);
        }
        $MODULE_DATA['GENE'] = $USER_INPUT['PORTAL']['DATA'];
        unset($MODULE_DATA['GENE']['GN_ENTRY_ID']);
        if (ob_get_contents()) {
            ob_end_clean();
        }
        header('Content-type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST');
        header("Access-Control-Allow-Headers: X-Requested-With");
        echo json_encode($MODULE_DATA);
        exit;
    case 'FASTA':
ini_set('memory_limit','1000M');
        $MODULE_DATA = getTranscriptsSequence($USER_INPUT['PORTAL']['DATA']['GN_ENTRY_ID']);
        
        $MODULE_DATA = removePrimaryKeys($MODULE_DATA);


        //echo '<pre>';print_r($MODULE_DATA);exit;
        $STR = '';
        foreach ($MODULE_DATA as &$TR) {
            if ($USER_INPUT['PARAMS'] != array()) {
                $TRNAME = $TR['INFO']['TRANSCRIPT_NAME'];
                if ($TR['INFO']['TRANSCRIPT_VERSION'] != '') {
                    $TRNAME .= '.' . $TR['INFO']['TRANSCRIPT_VERSION'];
                }
                if (!in_array($TRNAME, $USER_INPUT['PARAMS'])) {
                    continue;
                }
            }
            $STR .= '>' . $TR['INFO']['ASSEMBLY_NAME'] . '|' . $TR['INFO']['CHR_SEQ_NAME'] . '|' . $TR['INFO']['GENE_SEQ_NAME'];
            if ($TR['INFO']['GENE_SEQ_VERSION'] != '') {
                $STR .= '.' . $TR['INFO']['GENE_SEQ_VERSION'];
            }
            $STR .= '|' . $TR['INFO']['TRANSCRIPT_NAME'];
            if ($TR['INFO']['TRANSCRIPT_VERSION'] != '') {
                $STR .= '.' . $TR['INFO']['TRANSCRIPT_VERSION'];
            }
            $STR .= "\n";
            $STRL = '';
            foreach ($TR['SEQ'] as $P) {
                $STRL .= $P['NUCL'];
            }
            $STR .= implode("\n", str_split($STRL, 100)) . "\n";
        }
        
        if (ob_get_contents()) {
            ob_end_clean();
        }


        header('Content-type: application/x-fasta');
        header('Content-Disposition: attachment; filename="' . $USER_INPUT['PORTAL']['DATA']['SYMBOL'] . '.' . $USER_INPUT['PORTAL']['DATA']['GENE_ID'] . '_transcripts.fasta"');
        echo $STR . "\n";

        exit;

}


?>
