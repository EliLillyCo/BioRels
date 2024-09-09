<?php


$SEARCH_VALUE=htmlentities(strip_tags( trim($USER_INPUT['PAGE']['VALUE'])));
$res=loadPubliAuthorData($SEARCH_VALUE,"");

/*
$RESULTS='';
foreach ($res] as $N=> $line)
{
		if ($FIRST)
		{
			$FIRST=false;

			foreach ($line as $K=>$T)$RESULTS.='<th>'.$K.'</th>';
			$RESULTS.='</tr></thead>'."\n".'<tbody>';

		}
		$RESULTS.='<tr>';
		foreach ($line as $HEAD=>$V)
		{
			$pos=stripos($V,$SEARCH_VALUE);
			if ($pos!==false)
			{
				$T=substr($V,0,$pos).'<span class="gree_c">'.substr($V,$pos,strlen($SEARCH_VALUE)).'</span>'.substr($V,$pos+strlen($SEARCH_VALUE));
				$V=$T;
			}

			$RESULTS.='<td>';
			if (($HEAD=='Symbol' || $HEAD=='Gene ID') && isset($line['Gene ID']))$RESULTS.='<a href="/GENEID/'.$line['Gene ID'].'">'.$V.'</a>';
			else $RESULTS.=$V;
			$RESULTS.='</td>';
		}
		$RESULTS.='</tr>'."\n";
}
*/
changeValue("search_gene","result",$RESULTS);
$result['code']=$HTML["search_gene"];
$result['count']=count($SEARCH_RESULTS['GENE']);
		echo json_encode($result);
		exit;
?>