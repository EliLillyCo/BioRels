<?php
if (!defined("BIORELS")) header("Location:/");/// BIORELS defined in index.php. Not existing? Go to index.php


require '/usr/local/bin/website-vendor/autoload.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;




function convertBiorelsTags($STR,$ID='')
{
	$DEBUG=false;
	if ($DEBUG)echo $STR."|||\n\n\n";
	$pos=strpos($STR,'[[[',0);
	if ($DEBUG)echo $pos."\n";
	if ($pos===false)return $STR;
	$N=0;
	if ($DEBUG)echo '<pre>';
	do 
	{
		++$N;
	$pos_end=strpos($STR,']]]',$pos);
	if ($DEBUG)echo "P2:".$pos_end."\n";
	if ($pos_end===false)return $STR;
	
	$STR_BLOCK=substr($STR,$pos+1,$pos_end-$pos+1);
	$STR_ORIG_BLOCK=$STR_BLOCK;
	
	if ($DEBUG)echo "\tSTR BLOCK://".$STR_BLOCK."//\n";
	$pos=strpos($STR_BLOCK,'[[');
	if ($DEBUG)echo "\t\tP3:".$pos."\n";
	$N2=0;
	do 
	{
		++$N2;
		$pos_end=strpos($STR_BLOCK,']]',$pos);
		if ($DEBUG)echo "\t\tP_END:".$pos_end."\n";
		if ($pos_end===false)break;
		$TAG=substr($STR_BLOCK,$pos+2,$pos_end-$pos-2);
		if ($DEBUG)echo "\t\t\tTAG://".$TAG."//\n";
		
		$tab=explode("||",$TAG);
		print_R($tab);
		if ($DEBUG)echo "\t\t\tTABS:".implode("|",$tab)."\n";
		if ($tab[0]=='PUBMED')
		{
			$link='<a href="/PUBMED/'.$tab[1].'">'.$tab[2].'</a>';
			if ($DEBUG)echo "\t\t\tBEFORE:".$STR_BLOCK."\n";
			$STR_BLOCK=str_replace('[['.$TAG.']]',$link,$STR_BLOCK);
		if ($DEBUG)	echo "\t\t\tAFTER:".$STR_BLOCK."\n";
		}
		else if ($tab[0]=='BOOK')
		{
			$link=$tab[1];
			$STR_BLOCK=str_replace('[['.$TAG.']]',$link,$STR_BLOCK);
		}
		else if ($tab[0]=='NCBI_GENE') 
		{
			$link='<a href="/GENEID/'.$tab[1].'">'.$tab[2].'</a>';
			$STR_BLOCK=str_replace('[['.$TAG.']]',$link,$STR_BLOCK);
		}
		else if ($tab[0]=='EC') 
		{
			$STR_BLOCK=str_replace('[['.$TAG.']]','(EC:'.$tab[2].')',$STR_BLOCK);
		}
		else if ($tab[0]=='GENBANK') 
		{
			$STR_BLOCK=str_replace('[['.$TAG.']]','(GENBANK:'.$tab[2].')',$STR_BLOCK);
		}
		else if ($tab[0]=='VARIANT') 
		{
			$link='<a href="/VARIANT/'.$tab[1].'">'.$tab[2].'</a>';
			$STR_BLOCK=str_replace('[['.$TAG.']]',$link,$STR_BLOCK);
		}
		else if ($tab[0]=='DISEASE') 
		{
			$link='<a href="/DISEASE/'.$tab[1].'">'.$tab[2].'</a>';
			$STR_BLOCK=str_replace('[['.$TAG.']]',$link,$STR_BLOCK);
		}
		else if ($tab[0]=='REF_IMG')
		{
			$link='<span onmouseover="showPubliData(\'IMG:'.$tab[1].':'.$tab[2].'\',\''.$ID.'\')">'.$tab[3].'</span>';
			$STR_BLOCK=str_replace('[['.$TAG.']]',$link,$STR_BLOCK);
		}
		else if ($tab[0]=='REF_CIT')
		{
			// echo '<pre>';
			// print_r(debug_backtrace());
			// exit;
			$link='<span style="vertical-align:super" onmouseover="showPubliData(\'CIT:'.$tab[1].'\',\''.$ID.'\')">['.$tab[2].']</span>';
			$STR_BLOCK=str_replace('[['.$TAG.']]',$link,$STR_BLOCK);
		}
		else if ($tab[0]=='REF_TBL')
		{
			
			$link='<span style="vertical-align:super" onmouseover="showPubliData(\'TBL:'.$tab[1].'\',\''.$ID.'\')">['.$tab[1].']</span>';
			$STR_BLOCK=str_replace('[['.$TAG.']]',$link,$STR_BLOCK);
		}
		else if ($tab[0]=='REF_SUPPL')
		{
			
			//$link='<span style="vertical-align:super" onmouseover="showPubliData(\'SUPPL:'.$tab[1].'\',\''.$ID.'\')">['.$tab[1].']</span>';
			$STR_BLOCK=str_replace('[['.$TAG.']]','',$STR_BLOCK);
		}
		else if ($tab[0]=='PUBLI_INFO')
		{
			$INFO=array();
			for ($I=1;$I<count($tab);$I++)
			{

				$COL=$tab[$I];
				switch ($COL)
				{
					case 'YEAR':
					case 'PAGE':
					case 'LPAGE':
					case 'URL':
					case 'TITLE':
					case 'AUTHOR':
					case 'VOLUME':
					case 'SOURCE':
					case 'ID':++$I;$INFO[$COL]=$tab[$I];break;
					case 'LINK':
						++$I;
						$L1=$tab[$I];
						++$I;
						$L2=$tab[$I];
						$INFO['LINK'][]=array($L1,$L2);

				}
			}
			$LINK='<div id="cit_'.$INFO['ID'].'" class="pub_entry">['.$INFO['ID'].']
			<a target="_blank" ${HREF}>'.$INFO['TITLE'].'</a>
			<br/>
			'.$INFO['AUTHOR'].'
			<span class="ital">'.$INFO['SOURCE'].'</span>, ';
			if (isset($INFO['VOLUME'])) $LINK.='<span class="bold">'.$INFO['VOLUME'].'</span>';
			if (isset($INFO['PAGE']))$LINK.= ' '.$INFO['PAGE'].'-'.$INFO['LPAGE'];
			if (isset($INFO['URL']))$LINE.='<a href="'.$INFO['URL'].'" target="_blank">'.$INFO['URL'].'</a>';
			
			foreach ($INFO['LINK'] as $L)
			{

				if ($L[0]!='PMID')continue;
				$LINK.='<br/><a href="https://pubmed.ncbi.nlm.nih.gov/'.$L[1].'" target="_blank">'.$L[0].'</a>';
				$LINK.='<div id="pub_ref_'.$INFO['ID'].'"></div>';
				$LINK.='<script>$(document).ready(function (id,value ) {loadModule("pub_ref_'.$INFO['ID'].'","/CONTENT/PUBLICATION_BATCH/'.$L[1].'");});</script>';
				
			}
			$LINK.=' </div>';
			$STR_BLOCK=str_replace('[['.$TAG.']]',$LINK,$STR_BLOCK);
			
		}
		else 
		{
			die ('TAG NOT FOUND:'.$TAG);
		}
		// else 
		// {
		// 	$link='<a href="/'.$tab[0].'/'.$tab[1].'">'.$tab[2].'</a>';
		// 	$STR_BLOCK=str_replace('[['.$TAG.']]','',$STR_BLOCK);
		// }
		$pos=strpos($STR_BLOCK,'[[');
		if ($DEBUG) echo "\t\tNEXT_POS:".$pos."\n";
	}while ($pos!==false && $N2<500);
	if ($DEBUG) echo "\t\t\tFINAL BLOCK:".$STR_BLOCK."\n";
	if ($DEBUG) echo "\t\t\tPREV STR:".$STR."\n";

	$STR=str_replace('['.$STR_ORIG_BLOCK.']',$STR_BLOCK,$STR);
	if ($DEBUG)echo "\t\t\tNEW STR:".$STR."\n";
	
	$pos=strpos($STR,'[[[',0);
	$N++;
	if ($DEBUG)echo "FOLLOWING NEXT P1:".$pos."|N ATTEMPT:".$N."\n";
	}while($pos!==false && $N<1000);
	if ($DEBUG)echo "\n\n".$STR."\n";
	//if ($DEBUG)exit;
	//echo $N."\n";
	//echo $STR."\n";
	$STR=str_replace("()","",$STR);
 
	return $STR;
}


function genAlt($SEQ,$MAX,&$LIST_ALT)
{

	
	$LIST_ALT[0][$SEQ]='';
	$RULES=array('A','T','C','G');
	$LEN=strlen($SEQ);
	for ($I=0;$I<$LEN;++$I)
	{
		foreach ($RULES as $N)
		{
			if ($SEQ[$I]==$N)continue;
			$SEQ_L1=substr($SEQ,0,$I).$N.substr($SEQ,$I+1);
			$LIST_ALT[1][$SEQ_L1]=$I;
			//echo $SEQ_L1."<br/>";
			for ($J=$I+1;$J<$LEN;++$J)
			{	
				foreach ($RULES as $N2)
				{
					if ($SEQ[$J]==$N2)continue;
					$SEQ_L2=substr($SEQ_L1,0,$J).$N2.substr($SEQ_L1,$J+1);
					$LIST_ALT[2][$SEQ_L2]=$I.'/'.$J;
                    if ($MAX>=3)
					for ($K=$J+1;$K<$LEN;++$K)
					{	
						foreach ($RULES as $N3)
						{
							if ($SEQ[$K]==$N3)continue;
							$SEQ_L3=substr($SEQ_L2,0,$K).$N3.substr($SEQ_L2,$K+1);
							$LIST_ALT[3][$SEQ_L3]=$I.'/'.$J.'/'.$K;
                            if ($MAX>=4)
                            for ($L=$K+1;$L<$LEN;++$L)
                            {	
                                foreach ($RULES as $N4)
                                {
                                    if ($SEQ[$L]==$N4)continue;
                                    $SEQ_L4=substr($SEQ_L3,0,$L).$N4.substr($SEQ_L3,$L+1);
                                    $LIST_ALT[4][$SEQ_L4]=$I.'/'.$J.'/'.$K.'/'.$L;
                                    if ($MAX>=5)
                                    for ($M=$L+1;$M<$LEN;++$M)
                                    {	
                                        foreach ($RULES as $N5)
                                        {
                                            if ($SEQ[$M]==$N5)continue;
                                            $SEQ_L5=substr($SEQ_L4,0,$M).$N5.substr($SEQ_L4,$M+1);
                                            $LIST_ALT[5][$SEQ_L5]=$I.'/'.$J.'/'.$K.'/'.$L.'/'.$M;
                                        }
                                    }
                                }
                            }
						}
					}
				}
			}
		}
	}
	return $LIST_ALT;
}


function genAltMatch($SEQ,$MAX,&$POSSIBLE_MATCH,&$SQ_RES,$IS_ACT,$OFFSET)
{

	
	if (isset($POSSIBLE_MATCH[$SEQ])){$SQ_RES['RES'][]=array($OFFSET+$POSSIBLE_MATCH[$SEQ],0,$SEQ,$IS_ACT,$SEQ);}
	
	$RULES=array('A','T','C','G');
	$LEN=strlen($SEQ);
	if ($MAX>=1)
	for ($I=0;$I<$LEN;++$I)
	{
		foreach ($RULES as $N)
		{
			if ($SEQ[$I]==$N)continue;
			$SEQ_L1=substr($SEQ,0,$I).$N.substr($SEQ,$I+1);
			if (isset($POSSIBLE_MATCH[$SEQ_L1])){$SQ_RES['RES'][]=array($OFFSET+$POSSIBLE_MATCH[$SEQ_L1],1,$I,$IS_ACT,$SEQ_L1);}
			
			//echo $SEQ_L1."<br/>";
			if ($MAX>=2)
			for ($J=$I+1;$J<$LEN;++$J)
			{	
				foreach ($RULES as $N2)
				{
					if ($SEQ[$J]==$N2)continue;
					$SEQ_L2=substr($SEQ_L1,0,$J).$N2.substr($SEQ_L1,$J+1);
					if (isset($POSSIBLE_MATCH[$SEQ_L2])){$SQ_RES['RES'][]=array($OFFSET+$POSSIBLE_MATCH[$SEQ_L2],2,$I.'/'.$J,$IS_ACT,$SEQ_L2);}
					
                    if ($MAX>=3)
					for ($K=$J+1;$K<$LEN;++$K)
					{	
						foreach ($RULES as $N3)
						{
							if ($SEQ[$K]==$N3)continue;
							$SEQ_L3=substr($SEQ_L2,0,$K).$N3.substr($SEQ_L2,$K+1);
							if (isset($POSSIBLE_MATCH[$SEQ_L3])){$SQ_RES['RES'][]=array($OFFSET+$POSSIBLE_MATCH[$SEQ_L3],3,$I.'/'.$J.'/'.$K,$IS_ACT,$SEQ_L3);}
							
                            if ($MAX>=4)
                            for ($L=$K+1;$L<$LEN;++$L)
                            {	
                                foreach ($RULES as $N4)
                                {
                                    if ($SEQ[$L]==$N4)continue;
                                    $SEQ_L4=substr($SEQ_L3,0,$L).$N4.substr($SEQ_L3,$L+1);
									if (isset($POSSIBLE_MATCH[$SEQ_L4])){$SQ_RES['RES'][]=array($OFFSET+$POSSIBLE_MATCH[$SEQ_L4],4,$I.'/'.$J.'/'.$K.'/'.$L,$IS_ACT,$SEQ_L4);}
                                    
                                    if ($MAX>=5)
                                    for ($M=$L+1;$M<$LEN;++$M)
                                    {	
                                        foreach ($RULES as $N5)
                                        {
                                            if ($SEQ[$M]==$N5)continue;
											
                                            $SEQ_L5=substr($SEQ_L4,0,$M).$N5.substr($SEQ_L4,$M+1);
											if (isset($POSSIBLE_MATCH[$SEQ_L5])){$SQ_RES['RES'][]=array($OFFSET+$POSSIBLE_MATCH[$SEQ_L5],5,$I.'/'.$J.'/'.$K.'/'.$L.'/'.$M,$IS_ACT,$SEQ_L5);}
                                            
                                        }
                                    }
                                }
                            }
						}
					}
				}
			}
		}
	}
	return ;
}

function unique_matrix($matrix) {
    $matrixAux = $matrix;

    foreach($matrix as $key => $subMatrix) {
        unset($matrixAux[$key]);

        foreach($matrixAux as $subMatrixAux) {
            if($subMatrix === $subMatrixAux) {
            // Or this
            //if($subMatrix[0] === $subMatrixAux[0]) {
                unset($matrix[$key]);
            }
        }
    }

    return $matrix;
}
function sendMailToUser($userAddress,$fromTitle,$subject,$body,$alt_body, $attachments=array())
{
	global $GLB_CONFIG;
	
    //Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer();
    try {
        //Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host       = 'smtp.messaging.svc';                   //Set the SMTP server to send through    
        // comment in this line for local testing, comment out line above
        // $mail->Host       = 'localhost';
        $mail->Port       = 1025;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        // Senders  &  Recipients
        $mail->setFrom($GLB_CONFIG['GLOBAL']['EMAIL_FROM'], $fromTitle);
        $mail->addAddress($userAddress);
        $mail->addReplyTo($GLB_CONFIG['GLOBAL']['EMAIL_REPLY'], 'Information');
        // $mail->addCC('cc@example.com');
        // $mail->addBCC('bcc@example.com');

        //Attachments		
		if ($attachments != array()){
			$mail->addAttachment($attachments['path'], $attachments['file_name']);         	
		}        

        //Content
        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $alt_body;

        $mail->send();
        return true;
    }  catch (phpmailerException $e) {
		echo $e->errorMessage(); //Pretty error messages from PHPMailer
	  }catch (Exception $e) {
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}




function shift($TO,$OFFSET,&$ALIGNMENT)
{
	$end=max(array_keys($ALIGNMENT));
	for ($pos=$end;$pos>=$TO;$pos--)
	{
		$ALIGNMENT[$pos+$OFFSET]=$ALIGNMENT[$pos];
	}
	for($I=$TO;$I<$TO+$OFFSET;++$I)
	$ALIGNMENT[$I]=array();
	ksort($ALIGNMENT);
}

function getSSLPage($url) {
	ini_set('max_execution_time', 300);
	// if (!defined('CURL_HTTP_VERSION_2_0')) {

	// 	define('CURL_HTTP_VERSION_2_0', 3);
		
	// 	}
	// $ch = curl_init();
	// //curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	
	// curl_setopt($ch, CURLOPT_HEADER, 1);
	// curl_setopt($ch, CURLOPT_URL, $url);
	// //curl_setopt($ch, CURLOPT_SSLVERSION,3); 
	$options = array(
		CURLOPT_RETURNTRANSFER => true,     // return web page
		CURLOPT_HEADER         => false,    // don't return headers
		CURLOPT_FOLLOWLOCATION => true,     // follow redirects
		CURLOPT_ENCODING       => "",       // handle all encodings
		CURLOPT_USERAGENT      => "spider", // who am i
		CURLOPT_AUTOREFERER    => true,     // set referer on redirect
		CURLOPT_CONNECTTIMEOUT => 240,      // timeout on connect
		CURLOPT_TIMEOUT        => 240,      // timeout on response
		CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	//	CURLOPT_SSL_VERIFYPEER => false,     // Disabled SSL Cert checks
		CURLOPT_PROXY			=>'',
		CURLOPT_PROXYPORT		=>9000,
		//CURLOPT_HTTP_VERSION    =>CURL_HTTP_VERSION_2_0
	);

	$ch      = curl_init( $url );
	curl_setopt_array( $ch, $options );
	
$response=curl_exec($ch);


curl_close($ch);


	
	return $response;
}


function convertUniprotText($list_t)
{
	global $NRID;
	global $USER_INPUT;
	$str='';
	if (count($list_t)>1)$str.='<ul>';
	foreach ($list_t as $t)
	{
		
		if (count($list_t)>1)$str.='<li>';
		$t=str_replace('Note=','<br/><span class="bold">Note: </span>',$t);
		$refs='';
		
		$pos=strpos($t,'{');
		while($pos!==false) {
			$pos2=strpos($t,'}',$pos);
			if ($pos2!==false)$refs.=substr($t,$pos+1,$pos2-$pos-1).' ';
			$t=substr($t,0,$pos).substr($t,$pos2+1);
			$pos=strpos($t,'{');
		};
		
		preg_match_all("/PubMed:([0-9]{4,9})/",$t,$matches);
		
		foreach ($matches[1] as $K)
		{
			$refs.=', PubMed:'.$K;
		}
		$actual_link = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]";
		$t=preg_replace("/PubMed:([0-9]{4,9})/",'PubMed:<a href="'.$actual_link.'/PUBMED/$1" target="_blank">$1</a>',$t);
		$USER_INPUT['PAGE']['VALUE']=$refs;
		++$NRID;
		
		$USER_INPUT['PARAMS']=array('ID',$NRID);
		
		$str.=$t.'<br/>';
		// if ($refs!=''&& strpos($refs,'PubMed')!==false){$str.=loadHTMLAndRemove('PUBLI_ECO');
		// }
		if (count($list_t)>1)$str.='</li>';
	}
	if (count($list_t)>1)$str.='</ul>';
	return $str;
}


function hasPrivateAccess()
{
	global $USER;
	return ($USER['Access'][1]==1);
}

function getReverse($STR)
{
  $REV=strrev($STR);
  $RES='';
  $MAP=array('A'=>'T','T'=>'A','G'=>'C','C'=>'G');
  for($I=0;$I<strlen($REV);++$I)$RES.=$MAP[$REV[$I]];
  return $RES;
  
};
function genAligner(&$DATA_SEQ)
{
//echo "<pre>";print_r($DATA_SEQ);exit;
	$ALIGNMENT=array();
	$POS=0;
	$TAG_AL='ALIGNMENT_ID';
	$R_SEQ_ID=$DATA_SEQ['REF_SEQ']['SEQ_ID'];
	foreach ($DATA_SEQ['ENTRIES'][$R_SEQ_ID]['SEQ'] as $K=>$V) 
	{
		$ALIGNMENT[$POS][0]=$K;
		if (!isset($DATA_SEQ['ALIGN_DATA'][$K])){++$POS;continue;}
		foreach ($DATA_SEQ['ALIGN_DATA'][$K] as $AL_ID=>$E)
		{
			$ALIGNMENT[$POS][$AL_ID]=$E;
		}
		++$POS;
	}

	$N=0;
	foreach ($DATA_SEQ['ENTRIES'] as $U_SEQ_ID=>$DATA)
	{

		if ($U_SEQ_ID==$R_SEQ_ID){continue;}
		/// Now we check that this sequence is complete
		/// find first position of prim seq
		$START_MAIN=-1;$START_SEQ=-1;
		if (!isset($DATA[$TAG_AL])) throw new Exception("No ".$TAG_AL." found in alignment ");
		foreach ($ALIGNMENT as $POS=>$LIST)
		{

			/// We try to get in the alignment the starting position of the main sequence:
			if (isset($LIST[0]) && $LIST[0]!="" && $START_MAIN==-1)$START_MAIN=$POS;
			/// Then we try to get in the alignment the starting position of the current sequence

			if ($U_SEQ_ID!=$R_SEQ_ID && isset($LIST[$DATA[$TAG_AL]]) && $START_SEQ==-1) $START_SEQ=$POS;
			if ($START_MAIN!=-1 && $START_SEQ!=-1)break;
		}

//			echo implode(array_keys($ALIGNMENT),' '); echo "<br/>";
		++$N;
//

		//echo $DATA['C_UN_IDENTIFIER'].'|'.$DATA['C_ISO_ID']."\t".$N."\t".$START_SEQ."\t".$START_MAIN."\t".$ALIGNMENT[$START_SEQ][$DATA['UN_SEQ_AL_ID']]."\t".$DATA['SEQ_AL'][1]."\n";

		/// If the starting position of the current sequence is not the beggining of the current sequence
		/// then these two values will be different
		if (!isset($DATA['SEQ_AL'][1]) )continue;

		if ($ALIGNMENT[$START_SEQ][$DATA[$TAG_AL]]==$DATA['SEQ_AL'][1])continue;
		/// So now the find the position in the current sequence of the starting position
		$POS=$DATA['SEQ'][$ALIGNMENT[$START_SEQ][$DATA[$TAG_AL]]][0];
		/// That defines the offset to add
		/// However, in the case where another sequence has already made an offset
		/// We substract the START_MAIN which correspond to the already existing offset.
		$TO_SHIFT=$POS-$START_MAIN-1;
//		echo $POS."\tSHIFT:".$TO_SHIFT."\n";
		/// So we shift the sequence alignment of TO_SHIFT, starting at position 0, i.e. the beginning of the alignment	
		if ($TO_SHIFT < 0)$TO_SHIFT=0;
		shift(0,$TO_SHIFT,$ALIGNMENT);	
		/// And we add the missing starting sequence
		for($i=1;$i<=$POS;++$i)
		{
			if (isset($DATA['SEQ_AL'][$i]))
			$ALIGNMENT[$i-1][$DATA[$TAG_AL]]=$DATA['SEQ_AL'][$i];
		}

	
	}
//echo "<pre>";echo "########## ALIGNMENT \n";print_r($ALIGNMENT);exit;
//exit;
/// We do the same for the end sequence

	foreach ($DATA_SEQ['ENTRIES'] as $U_SEQ_ID=>$DATA)
	{
		if ($U_SEQ_ID==$R_SEQ_ID)continue;
		/// Now we check that this sequence is complete
		/// find last position of prim seq
		$END_MAIN=-1;$END_SEQ=-1;
		foreach ($ALIGNMENT as $POS=>$LIST)
		{
			/// We try to get in the alignment the ending position of the main sequence:
			if (isset($LIST[0]) && $LIST[0]!="")$END_MAIN=$POS;
			/// Then we try to get in the alignment the ending position of the current sequence
			if (isset($LIST[$DATA[$TAG_AL]]) && $LIST[$DATA[$TAG_AL]]!="") $END_SEQ=$POS;
			/// But here we don't stop when we get the first one.
		///if ($START_MAIN!=-1 && $START_SEQ!=-1)break;
	}
	//echo "END:".$START_MAIN." ".$START_SEQ."<br/>";
	$N_S=max(array_keys($DATA['SEQ_AL']));
	//echo $END_SEQ."\t".$END_MAIN."\t".$ALIGNMENT[$END_SEQ][$DATA['UN_SEQ_AL_ID']]."\t".$DATA['SEQ_AL'][$N_S]."\n";

	/// If the last position of the current sequence is not the end of the current sequence
	/// then these two values will be different
	if ($ALIGNMENT[$END_SEQ][$DATA[$TAG_AL]]==$DATA['SEQ_AL'][$N_S])continue;
	/// So now the find the position in the current sequence of the last position
	$POS=$DATA['SEQ'][$ALIGNMENT[$END_SEQ][$DATA[$TAG_AL]]][0];
	/// The offset is the difference between the last position and the length of the sequence
	$DIFF=$N_S-$POS;
	/// However, in the case where another sequence has already made an offset
	/// We substract the length of the alignment with the END_MAIN -> corresponding to the already existing offset.
	$TO_SHIFT=$DIFF-(count($ALIGNMENT)-($END_MAIN+1));

//echo (count($ALIGNMENT)-1)."\t".$END_MAIN."\t".$N_S."\t".$POS."\t".$TO_SHIFT."\n";

	/// So we add new elements at the end of the alignment
	shift(count($ALIGNMENT),$TO_SHIFT,$ALIGNMENT);	
	/// And we add the missing ending sequence
	for($i=1;$i<=$DIFF;++$i)
	{
		$ALIGNMENT[$END_MAIN+$i][$DATA[$TAG_AL]]=$DATA['SEQ_AL'][$POS+$i];
	}

	
}


//// We do the same for gaps
foreach ($DATA_SEQ['ENTRIES'] as $U_SEQ_ID=>$DATA)
{
if ($U_SEQ_ID==$R_SEQ_ID)continue;
	/// Now we need to find any position that is missing
	$LIST_SEQ=array();
//echo $DATA['C_ISO_ID'].'<br/>';
//echo $TAG_AL.'<br/>';
	foreach ($ALIGNMENT as $POS=>$LIST)
	{
	
		if (isset($LIST[$DATA[$TAG_AL]]) && $LIST[$DATA[$TAG_AL]]!="") $LIST_SEQ[]=$LIST[$DATA[$TAG_AL]];
	}

//echo count($DATA['SEQ'])." ".count($LIST_SEQ).'<br/>';
	$LIST_INI=array_keys($DATA['SEQ']);


	$DIFF=array_diff($LIST_INI,$LIST_SEQ);
if (count($DIFF)==0)continue;
//echo "DIFF:<pre>";
//print_r($DATA);
//echo "DIFF";
	//print_r($DIFF);
//echo "ALIGNMENT";
//print_r($ALIGNMENT);


	$INSERTIONS=array();
	foreach ($DIFF as $K=>$D)$INSERTIONS[$DATA['SEQ'][$D][0]]=array(false,$DATA['SEQ'][$D][1]);
	$START_POS=min(array_keys($INSERTIONS));
//echo "INSERTION STARTS FROM ".$START_POS;
//print_r($INSERTIONS);
//echo "</pre>";
$STR="";
	foreach ($ALIGNMENT as $POS_ALIGN=>$LIST)
	{
		if (!isset($LIST[$DATA[$TAG_AL]]) || $LIST[$DATA[$TAG_AL]]=="")continue;
		$PSEQ=$DATA['SEQ'][$LIST[$DATA[$TAG_AL]]];	
		// echo "ALIGNMENTS:".$LIST[$DATA[$TAG_AL]].' '.$PSEQ[0].'<br/>';
		if ($PSEQ[0]+1 != $START_POS)continue;
		$STR.=$INSERTIONS[$START_POS][1];
		++$START_POS;
		//echo "GAP SEQ " .$STR."<br/>";
		foreach ($INSERTIONS as $T =>$D)
		{
			if ($T<$START_POS)continue;
			if ($T>$START_POS)break;
			$STR.=$D[1];
			$INSERTIONS[$T][0]=true;
			++$START_POS;
		//echo "GAP SEQ " .$STR."<br/>";
		}
		$ENTRY[$U_SEQ_ID]['GAP'][]=array('START'=>$POS_ALIGN,'SEQ'=>$STR);
		$STR="";
		
	}
	//foreach ($INSERTIONS as $T=>$D) if ($D[0]==false)exit;
//	print_r($DIFF);
	//exit;	
	
}


///Now we convert into sequences
$MULTI_SEQ=array();
$STR=array();$STR_L=array();
$STEP=100;$N_STR=0;
$max_len=strlen($DATA_SEQ['REF_SEQ']['UN_IDENTIFIER']);
$FIRST=true;
$JS=array();


foreach ($DATA_SEQ['ENTRIES'] as $U_SEQ_ID=>$DATA)
{

	if ($U_SEQ_ID==$R_SEQ_ID)$SEQ_AL=0;else $SEQ_AL=$DATA[$TAG_AL];
	$STR="";$BLOCK=0;
	unset($STR_L);$STR_L=array();
	if (isset($DATA['C_UN_IDENTIFIER']) && strlen($DATA['C_UN_IDENTIFIER'])>$max_len)$max_len=strlen($DATA['C_UN_IDENTIFIER']);

	if (isset($DATA['C_UN_IDENTIFIER'])) $NAME=$DATA['C_UN_IDENTIFIER'];
	else 				     $NAME=$DATA_SEQ['REF_SEQ']['UN_IDENTIFIER'];

	if ($DATA_SEQ['REF_SEQ']['INFO']=='SEQ')
	{

	if (!isset($DATA['C_UN_IDENTIFIER'])){$NAME.='|'.$DATA_SEQ['REF_SEQ']['ISO_ID'];}
	else $NAME.='|'.$DATA['C_ISO_ID'];
	}

	$T=array_keys($DATA['SEQ_AL']);
	$DATA_SEQ['ENTRIES'][$U_SEQ_ID]['START']=$T[0];

	$DATA_SEQ['ENTRIES'][$U_SEQ_ID]['ALIGNED_SEQ']='';
	$FIRST=true;
	foreach ($ALIGNMENT as $POS=>$LIST)
	{
		
		if (isset($LIST[$SEQ_AL]))
		{
			$POS_DATA=$DATA['SEQ'][$LIST[$SEQ_AL]];
			$DATA_SEQ['ENTRIES'][$U_SEQ_ID]['ALIGNED_SEQ'].=$POS_DATA[1];
		}
		else
		{
			$DATA_SEQ['ENTRIES'][$U_SEQ_ID]['ALIGNED_SEQ'].='-';
		}
	}

	if (!isset($DATA['GAP']))continue;
		foreach ($DATA['GAP'] as $D)
	{
		$DATA_SEQ['ENTRIES'][$U_SEQ_ID]['GAP'][]=$D;
	}
}

return $DATA_SEQ;
}


?>
