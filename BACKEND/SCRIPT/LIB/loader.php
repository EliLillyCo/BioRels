<?php

# FILE: 	load.php
# OWNER: 	DESAPHY JEREMY
# DATE:		05.30.2019
# PURPOSE:	master file to load all other processing files
 use PHPMailer\PHPMailer\PHPMailer;
 if (is_file('/composer/vendor/autoload.php'))require_once '/composer/vendor/autoload.php';
    

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
$START_SCRIPT_TIME=microtime_float();
$START_SCRIPT_TIMESTAMP=date('Y-m-d H:i:s');


$PROCESS_CONTROL=array('STEP'=>0,
		       'JOB_NAME'=>@$JOB_NAME,
			'DIR'=>'',
		       'LOG'=>array(),
		       'STATUS'=>'INIT',
		       'START_TIME'=>microtime_float(),
		       'END_TIME'=>'',
		       'STEP_TIME'=>microtime_float(),
		       'FILE_LOG'=>''
		    );



$time=microtime_float();
$TG_DIR= getenv('TG_DIR');

if ($TG_DIR===false)  sendKillMail('000001','NO TG_DIR found');
if (!is_dir($TG_DIR)) sendKillMail('000002','TG_DIR value is not a directory '.$TG_DIR);
if (!is_dir($TG_DIR.'/PROCESS') && !mkdir($TG_DIR.'/PROCESS'))sendKillMail('000002','TG_DIR/PROCESS can\'t be created');

$FILE_TO_LOAD=array(
	'/LIB/global.php',
	'/LIB/fct_utils.php',
	'/LIB/loader_process.php',
	'/LIB/loader_timestamp.php',
	'/LIB/loader_qengine.php',
	'/LIB/webjob_utils.php',
	
);


foreach ($FILE_TO_LOAD as $FILE)
{
	$PATH=$TG_DIR.'/BACKEND/SCRIPT/'.$FILE;

	$time=microtime_float();
	if ((include $PATH)==TRUE)continue;

	sendKillMail('000003','Unable to load file: '.$PATH);
}


if (defined("MONITOR_JOB"))
{
	foreach ($GLB_TREE as $ID=>&$INFO)
	{
		if (substr($INFO['NAME'],0,4)!='rmj_')continue;
		$PATH=$TG_DIR.'/BACKEND/';
		if ($INFO['IS_PRIVATE'])$PATH.='PRIVATE_SCRIPT/';
		else $PATH.='SCRIPT/';
		$PATH.=$INFO['DIR'].'/'.$INFO['NAME'].'.php';
		if (!checkFileExist($PATH))sendKillMail('000004','Unable to locate file: '.$PATH);
		if ((include $PATH)==TRUE)continue;

		sendKillMail('000005','Unable to load file: '.$PATH);
		
	}
}

function sendMail($ERROR_ID,$INFO)
{
	echo "SEND MAIL\n";
	echo $INFO."\n";
	global $GLB_VAR;
	global $PROCESS_CONTROL;
	
	if (isset($GLB_VAR['EMAIL']) && isset($GLB_VAR['EMAIL_FROM']))
	{

		$tab=explode("|",$GLB_VAR['EMAIL']);
		try{
			// create a new object
			$mail = new PHPMailer();
			// configure an SMTP
			$mail->isSMTP();
			// $mail->Host = 'live.smtp.mailtrap.io';
			// $mail->SMTPAuth = true;
			// $mail->Username = '1a2b3c4d5e6f7g';
			// $mail->Password = '1a2b3c4d5e6f7g';
			// $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			// $mail->Port = 2525;
			
			$mail->setFrom($GLB_VAR['EMAIL_FROM'], 'BIORELS - '.$ERROR_ID);
			foreach ($tab as $EM)
			$mail->addAddress($EM, 'Me');
			$mail->Subject = 'Issue';
			// Set HTML 
			$mail->isHTML(TRUE);
			$mail->Body = $INFO.' '.print_r($PROCESS_CONTROL,true);;
			$mail->AltBody = $INFO;
		   
			
			// send the message
			if(!$mail->send()){
				echo 'Message could not be sent.';
				echo 'Mailer Error: ' . $mail->ErrorInfo;
			} else {
				echo 'Message has been sent';
			}
			$mail=null;
		}catch(Exception $e)
		{}
	}
	
}
function sendKillMail($ERROR_ID,$INFO)
{
	echo "SEND KILL MAIL\n";
	sendMail($ERROR_ID,$INFO);
	exit(1);
}

date_default_timezone_set($GLB_VAR['TIMEZONE']);


$DB_CONN=null;
$DB_INFO=array();
$GLB_VAR['DB_SCHEMA']=getenv('DB_SCHEMA');
$GLB_VAR['SCHEMA_PRIVATE']=getenv('SCHEMA_PRIVATE');

connectDB();


loadTimestamps();

?>