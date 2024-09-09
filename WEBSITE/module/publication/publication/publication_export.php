<?php

if (!defined("BIORELS")) {
    header("Location:/");
}

switch ($USER_INPUT['VTYPE']) {
    case 'W':
        preloadHTML($USER_INPUT['PAGE']['NAME']);

        break;
    case 'HCONTENT':
    case 'CONTENT':
        $result['code'] = loadHTMLAndRemove($USER_INPUT['PAGE']['NAME'], ($USER_INPUT['VTYPE'] == 'HCONTENT'));
        if (ob_get_contents()) {
            ob_end_clean();
        }

        echo json_encode($result);
        exit;
        break;
    case 'JSON':
        $MODULE_DATA = preloadData($USER_INPUT['PAGE']['NAME']);
        $MODULE_DATA = removePrimaryKeys($MODULE_DATA);
        if (ob_get_contents()) {
            ob_end_clean();
        }

        header('Content-type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST');
        header("Access-Control-Allow-Headers: X-Requested-With");
        echo json_encode($MODULE_DATA);
        exit;
}
?>
