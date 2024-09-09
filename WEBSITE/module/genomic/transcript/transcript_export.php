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
        if (ob_get_contents()) {
            ob_end_clean();
        }

        echo json_encode($result);
        exit;
        break;
    case 'JSON':
        $MODULE_DATA = preloadData($USER_INPUT['PAGE']['NAME']);
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
        $MODULE_DATA = preloadData($USER_INPUT['PAGE']['NAME']);
        $MODULE_DATA = removePrimaryKeys($MODULE_DATA);
        if (ob_get_contents()) {
            ob_end_clean();
        }

        $STR = '';
        $NAME = $MODULE_DATA['INFO']['TRANSCRIPT_NAME'] . '.' . $MODULE_DATA['INFO']['TRANSCRIPT_VERSION'];
        if (isset($MODULE_DATA['FILTERS'])) {
            foreach ($MODULE_DATA['FILTERS'] as $K => $V) {
                if ($V) {
                    $NAME .= '-' . $K;
                }
            }
        }

        foreach ($MODULE_DATA['SEQUENCE']['SEQUENCE'] as $N) {
            $STR .= $N['NUCL'];
        }

        header('Content-type: application/x-fasta');
        header('Content-Disposition: attachment; filename="' . $NAME . '.fasta"');
        echo '>' . $NAME . ' ' . $MODULE_DATA['INFO']['GENE_ID'] . ':' . $MODULE_DATA['INFO']['SYMBOL'] . "\n";
        echo implode("\n", str_split($STR, 500));
        exit;
}

?>