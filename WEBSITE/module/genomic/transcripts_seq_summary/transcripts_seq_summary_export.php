<?php

if (!defined("BIORELS")) header("Location:/");




switch ($USER_INPUT['VTYPE']) {
	case 'W':
		preloadHTML($USER_INPUT['PAGE']['NAME']);

		break;
	case 'CONTENT':
		$result['code'] = loadHTMLAndRemove($USER_INPUT['PAGE']['NAME']);
		if (ob_get_contents()) ob_end_clean();
		echo json_encode($result);
		exit;
		break;
	case 'JSON':
		$MODULE_DATA = preloadData($USER_INPUT['PAGE']['NAME']);
		$MODULE_DATA['GENE'] = $USER_INPUT['PORTAL']['DATA'];
		if (ob_get_contents()) ob_end_clean();
		header('Content-type: application/json');
		echo json_encode($MODULE_DATA);
		exit;
	case 'FASTA':

		$MODULE_DATA = preloadData($USER_INPUT['PAGE']['NAME']);
		$MODULE_DATA = removePrimaryKeys($MODULE_DATA);
		$FILE = '';
		$HAS_PARAMS = (count($USER_INPUT['PARAMS']) != 0);

		foreach ($MODULE_DATA['TRANSCRIPTS'] as $TR) {
			$NAME = $TR['TRANSCRIPT_NAME'];
			if ($TR['TRANSCRIPT_VERSION'] != null) $NAME .= '.' . $TR['TRANSCRIPT_VERSION'];
			if ($HAS_PARAMS && !in_array($NAME, $USER_INPUT['PARAMS'])) continue;

			$USER_INPUT['PAGE']['VALUE'] = $TR['TRANSCRIPT_NAME'];
			$RES = preloadData('TRANSCRIPT');
			$STR = '';
			foreach ($RES['SEQUENCE']['SEQUENCE'] as $N) $STR .= $N['NUCL'];
			$FILE .= '>' . $RES['INFO']['TRANSCRIPT_NAME'] . '.' . $RES['INFO']['TRANSCRIPT_VERSION'] . ' ' . $RES['INFO']['GENE_ID'] . ':' . $RES['INFO']['SYMBOL'] . "\n";
			$FILE .= implode(str_split($STR, 500), "\n") . "\n";
		}
		if (ob_get_contents()) ob_end_clean();


		header('Content-type: application/x-fasta');
		header('Content-Disposition: attachment; filename="' . $USER_INPUT['PORTAL']['DATA']['SYMBOL'] . '.' . $USER_INPUT['PORTAL']['DATA']['GENE_ID'] . '_transcripts.fasta"');
		echo $FILE . "\n";

		exit;
	case 'JPG':
		$MODULE_DATA = preloadData($USER_INPUT['PAGE']['NAME']);

		header('Content-type: image/png');
		header('Content-Disposition: attachment; filename="' . $USER_INPUT['PORTAL']['DATA']['SYMBOL'] . '.' . $USER_INPUT['PORTAL']['DATA']['GENE_ID'] . '_transcripts.png');
		$WIDTH = 2000;
		$HEADER = 210;
		$WIDTH_RANGE = $WIDTH - $HEADER - 40;
		$N_TR = 0;
		foreach ($MODULE_DATA['ASSEMBLY'] as &$A) $N_TR += count($A['TRANSCRIPTS']) + 1;
		$im = imagecreate($WIDTH, $N_TR * 20 + 40);

		putenv('GDFONTPATH=' . realpath('require/img/'));

		$white = imagecolorallocate($im, 255, 255, 255);
		$purple = imagecolorallocate($im, 128, 0, 128);
		$orange = imagecolorallocate($im, 255, 165, 0);
		$darkblue = imagecolorallocate($im, 0, 0, 139);
		$green = imagecolorallocate($im, 1, 90, 32);
		$black = imagecolorallocate($im, 0, 0, 0);

		$N = 0;
		foreach ($MODULE_DATA['ASSEMBLY'] as &$A)
			foreach ($A['TRANSCRIPTS'] as &$TR) {
				$TRN = $TR['TRANSCRIPT_NAME'];

				if ($TR['TRANSCRIPT_VERSION'] != '') $TRN .= '.' . $TR['TRANSCRIPT_VERSION'];
				imagestring($im, 10, 20, ($N) * 20 + 22, $TRN, $black);
				foreach ($TR['BOUNDARIES'] as $T) {
					$LEFT = round($HEADER + $WIDTH_RANGE * $T['LEFT'] / 100, 2);
					$RIGHT = $LEFT + round($WIDTH_RANGE * $T['WIDTH'] / 100, 2);
					$color = null;
					if ($T['TRANSCRIPT_POS_TYPE'] == "5'UTR" || $T['TRANSCRIPT_POS_TYPE'] == "3'UTR" || $T['TRANSCRIPT_POS_TYPE'] == "3'UTR-INFERRED" || $T['TRANSCRIPT_POS_TYPE'] == "5'UTR-INFERRED") $color = $green;
					else if ($T['TRANSCRIPT_POS_TYPE'] == 'CDS') $color = $purple;
					else if ($T['TRANSCRIPT_POS_TYPE'] == 'non-coded' || $T['TRANSCRIPT_POS_TYPE'] == "non-coded-INFERRED") $color = $darkblue;
					else $color = $orange;
					imagefilledrectangle($im, $LEFT, $N * 20 + 20, $RIGHT, $N * 20 + 36, $color);
				}
				++$N;
			}


		if (ob_get_contents()) ob_end_clean();

		imagepng($im);
		imagedestroy($im);


		exit;
}
