<?php

if (!defined("BIORELS")) header("Location:/");

$STR='';


if ($USER_INPUT['PORTAL']['NAME']=='GENE'){
	foreach ($MODULE_DATA as $line)
	{
	//	ALL_COUNT,YEAR_COUNT,SPEED30,ACCEL30,SPEED60,ACCEL60
		$STR.='<tr><td><a class="blk_font" href="/DISEASE/'.$line['DISEASE_NAME'].'">'.$line['DISEASE_NAME'].'</a></td>
		<td>'.$line['ALL_COUNT'].'</td>
		<td>'.$line['YEAR_COUNT'].'</td>';
		$STR.='<td data-order="'.$line['SPEED30'].'">';
		if ($line['SPEED30']>0.06)
		{
			if ($line['ACCEL30']>0.0011)$STR.='<div  data-toggle="tooltip" data-placement="left" title="Highly Accelerating & very Fast pace of evidence over the last year" class="ttp" style="background: url(require/img/DRUG_IMG.png) -7px -297px;width: 36px;height: 21px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']==0.0011)$STR.='<div data-toggle="tooltip" data-placement="left" title="Accelerating & very Fast pace of evidence over the last year" class="ttp" style="background: url(require/img/DRUG_IMG.png) -25px -297px;width: 29px;height: 21px;margin: 0 auto;"></div>';///GOOD
			else if ($line['ACCEL30']<0.0011 && $line['ACCEL30']>-0.0022)$STR.='<div data-toggle="tooltip" data-placement="left" title="very fast pace of evidence over the last year" class="ttp" style="background: url(require/img/DRUG_IMG.png) -56px -297px;width: 34px;height: 21px;margin: 0 auto;"></div>';///GOOD
			else if ($line['ACCEL30']==-0.0022)$STR.='<div data-toggle="tooltip" data-placement="left" title="Slowing but very Fast pace of evidence over the last year" class="ttp" style="background: url(require/img/DRUG_IMG.png) -88px -296px;width: 33px;height: 22px;margin: 0 auto;"></div>';
			else if ($line['ACCEL30']<-0.0022)$STR.='<div  data-toggle="tooltip" data-placement="left" title="Descelerating but very Fast pace of evidence over the last year"  class="ttp" style="background: url(require/img/DRUG_IMG.png) -120px -297px;width: 29px;height: 21px;margin: 0 auto;"></div>';//GOOD
		}
		else if ($line['SPEED30']==0.06667)
		{
			if ($line['ACCEL30']>0.0011)$STR.='<div   data-toggle="tooltip" data-placement="left" title="Highly Accelerating and fast pace of evidence over the last year"  class="ttp" style="background: url(require/img/DRUG_IMG.png) -10px -319px;width: 34px;height: 21px;margin: 0 auto;"></div>';
			else if ($line['ACCEL30']==0.0011)$STR.='<div   data-toggle="tooltip" data-placement="left" title="Accelerating and fast pace of evidence over the last year"  class="ttp" style="background: url(require/img/DRUG_IMG.png) -43px -319px;width: 26px;height: 21px;margin: 0 auto;"></div>';
			else if ($line['ACCEL30']<0.0011 && $line['ACCEL30']>-0.0022)$STR.='<div   data-toggle="tooltip" data-placement="left" title="Steady and fast pace of evidence over the last year"  class="ttp" style="background: url(require/img/DRUG_IMG.png) -58px -319px;width: 26px;height: 21px;margin: 0 auto;"></div>';
			else if ($line['ACCEL30']==-0.0022)$STR.='<div   data-toggle="tooltip" data-placement="left" title="Slowing but fast pace of evidence over the last year"  class="ttp" style="background: url(require/img/DRUG_IMG.png) -84px -321px;width: 26px;height: 21px;margin: 0 auto;"></div>';
			else if ($line['ACCEL30']<-0.0022)$STR.='<div  data-toggle="tooltip" data-placement="left" title="Descelerating but fast pace of evidence over the last year"  class="ttp"  style="background: url(require/img/DRUG_IMG.png) -109px -321px;width: 25px;height: 21px;margin: 0 auto;"></div>';
		}
		else if ($line['SPEED30']<0.06667 && $line['SPEED30']>-0.06667)
		{
			if ($line['ACCEL30']>0.0011)$STR.='<div  data-toggle="tooltip" data-placement="left" title="Highly Accelerating pace of evidence over the last year"  class="ttp"  style="background: url(require/img/DRUG_IMG.png) -7px -342px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']==0.0011)$STR.='<div  data-toggle="tooltip" data-placement="left" title="Accelerating pace of evidence over the last year"  class="ttp"  style="background: url(require/img/DRUG_IMG.png) -25px -320px;width: 29px;height: 21px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']<0.0011 && $line['ACCEL30']>-0.0022)$STR.='<div  data-toggle="tooltip" data-placement="left" title="Steady pace of evidence over the last year"  class="ttp"  style="background: url(require/img/DRUG_IMG.png) -68px -342px;width: 27px;height: 23px;margin: 0 auto;"></div>';//Good
			else if ($line['ACCEL30']==-0.0022)$STR.='<div  data-toggle="tooltip" data-placement="left" title="Slowing pace of evidence over the last year"  class="ttp"  style="background: url(require/img/DRUG_IMG.png) -82px -342px;width: 27px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']<-0.0022)$STR.='<div  data-toggle="tooltip" data-placement="left" title="Descelerating pace of evidence over the last year"  class="ttp"  style="background: url(require/img/DRUG_IMG.png) -109px -342px;width: 27px;height: 23px;margin: 0 auto;"></div>';//GOOD
		}
		else if ($line['SPEED30']==-0.06667)
		{
			if ($line['ACCEL30']>0.0011)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -7px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']==0.0011)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -26px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']<0.0011 && $line['ACCEL30']>-0.0022)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -75px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//Good
			else if ($line['ACCEL30']==-0.0022)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -110px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']<-0.0022)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -145px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
		}
		else if ($line['SPEED30']<-0.06667)
		{
			if ($line['ACCEL30']>0.0011)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -7px -390px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']==0.0011)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -45px -390px;width: 33px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']<0.0011 && $line['ACCEL30']>-0.0022)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -75px -390px;width: 35px;height: 23px;margin: 0 auto;"></div>';//Good
			else if ($line['ACCEL30']==-0.0022)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -110px -390px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']<-0.0022)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -145px -390px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
		}
		//background: url(require/img/DRUG_IMG.png) -8px -295px;width: 36px;height: 22px;margin: 0 auto;
		$STR.='</td>';
		$STR.='<td>';
		if ($line['SPEED60']>0.06)
		{
			if ($line['ACCEL60']>0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -7px -297px;width: 36px;height: 21px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']==0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -25px -297px;width: 29px;height: 21px;margin: 0 auto;"></div>';///GOOD
			else if ($line['ACCEL60']<0.05 && $line['ACCEL60']>-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -56px -297px;width: 34px;height: 21px;margin: 0 auto;"></div>';///GOOD
			else if ($line['ACCEL60']==-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -88px -296px;width: 33px;height: 22px;margin: 0 auto;"></div>';
			else if ($line['ACCEL60']<-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -120px -297px;width: 29px;height: 21px;margin: 0 auto;"></div>';//GOOD
		}
		else if ($line['SPEED60']==0.06667)
		{
			if ($line['ACCEL60']>0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -10px -319px;width: 34px;height: 21px;margin: 0 auto;"></div>';
			else if ($line['ACCEL60']==0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -43px -319px;width: 26px;height: 21px;margin: 0 auto;"></div>';
			else if ($line['ACCEL60']<0.05 && $line['ACCEL60']>-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -58px -319px;width: 26px;height: 21px;margin: 0 auto;"></div>';
			else if ($line['ACCEL60']==-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -84px -321px;width: 26px;height: 21px;margin: 0 auto;"></div>';
			else if ($line['ACCEL60']<-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -109px -321px;width: 25px;height: 21px;margin: 0 auto;"></div>';
		}
		else if ($line['SPEED60']<0.06667 && $line['SPEED60']>-0.06667)
		{
			if ($line['ACCEL60']>0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -7px -342px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']==0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -25px -320px;width: 29px;height: 21px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']<0.05 && $line['ACCEL60']>-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -68px -342px;width: 27px;height: 23px;margin: 0 auto;"></div>';//Good
			else if ($line['ACCEL60']==-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -82px -342px;width: 27px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']<-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -109px -342px;width: 27px;height: 23px;margin: 0 auto;"></div>';//GOOD
		}
		else if ($line['SPEED60']==-0.06667)
		{
			if ($line['ACCEL60']>0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -7px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']==0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -26px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']<0.05 && $line['ACCEL60']>-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -75px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//Good
			else if ($line['ACCEL60']==-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -110px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']<-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -145px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
		}
		else if ($line['SPEED60']<-0.06667)
		{
			if ($line['ACCEL60']>0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -7px -390px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']==0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -45px -390px;width: 33px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']<0.05 && $line['ACCEL60']>-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -75px -390px;width: 35px;height: 23px;margin: 0 auto;"></div>';//Good
			else if ($line['ACCEL60']==-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -110px -390px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']<-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -145px -390px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
		}
		
		$STR.='</td>';
		$STR.='<td><a href="/GENEID/'.$USER_INPUT['PORTAL']['VALUE'].'/DISEASE_GENE_VIEW/'.$line['DISEASE_TAG'].'"><img src="/require/img/view.png" style="width: 20px;"></a></td></tr>';
	}
	removeBlock("disease_genes","GENE");
	changeValue("disease_genes","COL",2);
}
else if ($USER_INPUT['PORTAL']['NAME']=='DISEASE')
{
	foreach ($MODULE_DATA as $line)
	{
		$STR.='<tr><td><a href="/GENEID/'.$line['GENE_ID'].'">'.$line['SYMBOL'].'</a></td>
		<td>'.$line['GENE_ID'].'</td>
		<td>'.$line['FULL_NAME'].'</td>
		<td>'.$line['ALL_COUNT'].'</td>
		<td>'.$line['YEAR_COUNT'].'</td>';
		$STR.='<td data-order="'.$line['SPEED30'].'">';
		if ($line['SPEED30']>0.06)
		{
			if ($line['ACCEL30']>0.0011)$STR.='<div  data-toggle="tooltip" data-placement="left" title="Highly Accelerating & very Fast pace of evidence over the last year" class="ttp" style="background: url(require/img/DRUG_IMG.png) -7px -297px;width: 36px;height: 21px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']==0.0011)$STR.='<div data-toggle="tooltip" data-placement="left" title="Accelerating & very Fast pace of evidence over the last year" class="ttp" style="background: url(require/img/DRUG_IMG.png) -25px -297px;width: 29px;height: 21px;margin: 0 auto;"></div>';///GOOD
			else if ($line['ACCEL30']<0.0011 && $line['ACCEL30']>-0.0022)$STR.='<div data-toggle="tooltip" data-placement="left" title="very fast pace of evidence over the last year" class="ttp" style="background: url(require/img/DRUG_IMG.png) -56px -297px;width: 34px;height: 21px;margin: 0 auto;"></div>';///GOOD
			else if ($line['ACCEL30']==-0.0022)$STR.='<div data-toggle="tooltip" data-placement="left" title="Slowing but very Fast pace of evidence over the last year" class="ttp" style="background: url(require/img/DRUG_IMG.png) -88px -296px;width: 33px;height: 22px;margin: 0 auto;"></div>';
			else if ($line['ACCEL30']<-0.0022)$STR.='<div  data-toggle="tooltip" data-placement="left" title="Descelerating but very Fast pace of evidence over the last year"  class="ttp" style="background: url(require/img/DRUG_IMG.png) -120px -297px;width: 29px;height: 21px;margin: 0 auto;"></div>';//GOOD
		}
		else if ($line['SPEED30']==0.06667)
		{
			if ($line['ACCEL30']>0.0011)$STR.='<div   data-toggle="tooltip" data-placement="left" title="Highly Accelerating and fast pace of evidence over the last year"  class="ttp" style="background: url(require/img/DRUG_IMG.png) -10px -319px;width: 34px;height: 21px;margin: 0 auto;"></div>';
			else if ($line['ACCEL30']==0.0011)$STR.='<div   data-toggle="tooltip" data-placement="left" title="Accelerating and fast pace of evidence over the last year"  class="ttp" style="background: url(require/img/DRUG_IMG.png) -43px -319px;width: 26px;height: 21px;margin: 0 auto;"></div>';
			else if ($line['ACCEL30']<0.0011 && $line['ACCEL30']>-0.0022)$STR.='<div   data-toggle="tooltip" data-placement="left" title="Steady and fast pace of evidence over the last year"  class="ttp" style="background: url(require/img/DRUG_IMG.png) -58px -319px;width: 26px;height: 21px;margin: 0 auto;"></div>';
			else if ($line['ACCEL30']==-0.0022)$STR.='<div   data-toggle="tooltip" data-placement="left" title="Slowing but fast pace of evidence over the last year"  class="ttp" style="background: url(require/img/DRUG_IMG.png) -84px -321px;width: 26px;height: 21px;margin: 0 auto;"></div>';
			else if ($line['ACCEL30']<-0.0022)$STR.='<div  data-toggle="tooltip" data-placement="left" title="Descelerating but fast pace of evidence over the last year"  class="ttp"  style="background: url(require/img/DRUG_IMG.png) -109px -321px;width: 25px;height: 21px;margin: 0 auto;"></div>';
		}
		else if ($line['SPEED30']<0.06667 && $line['SPEED30']>-0.06667)
		{
			if ($line['ACCEL30']>0.0011)$STR.='<div  data-toggle="tooltip" data-placement="left" title="Highly Accelerating pace of evidence over the last year"  class="ttp"  style="background: url(require/img/DRUG_IMG.png) -7px -342px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']==0.0011)$STR.='<div  data-toggle="tooltip" data-placement="left" title="Accelerating pace of evidence over the last year"  class="ttp"  style="background: url(require/img/DRUG_IMG.png) -25px -320px;width: 29px;height: 21px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']<0.0011 && $line['ACCEL30']>-0.0022)$STR.='<div  data-toggle="tooltip" data-placement="left" title="Steady pace of evidence over the last year"  class="ttp"  style="background: url(require/img/DRUG_IMG.png) -68px -342px;width: 27px;height: 23px;margin: 0 auto;"></div>';//Good
			else if ($line['ACCEL30']==-0.0022)$STR.='<div  data-toggle="tooltip" data-placement="left" title="Slowing pace of evidence over the last year"  class="ttp"  style="background: url(require/img/DRUG_IMG.png) -82px -342px;width: 27px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']<-0.0022)$STR.='<div  data-toggle="tooltip" data-placement="left" title="Descelerating pace of evidence over the last year"  class="ttp"  style="background: url(require/img/DRUG_IMG.png) -109px -342px;width: 27px;height: 23px;margin: 0 auto;"></div>';//GOOD
		}
		else if ($line['SPEED30']==-0.06667)
		{
			if ($line['ACCEL30']>0.0011)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -7px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']==0.0011)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -26px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']<0.0011 && $line['ACCEL30']>-0.0022)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -75px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//Good
			else if ($line['ACCEL30']==-0.0022)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -110px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']<-0.0022)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -145px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
		}
		else if ($line['SPEED30']<-0.06667)
		{
			if ($line['ACCEL30']>0.0011)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -7px -390px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']==0.0011)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -45px -390px;width: 33px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']<0.0011 && $line['ACCEL30']>-0.0022)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -75px -390px;width: 35px;height: 23px;margin: 0 auto;"></div>';//Good
			else if ($line['ACCEL30']==-0.0022)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -110px -390px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL30']<-0.0022)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -145px -390px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
		}
		//background: url(require/img/DRUG_IMG.png) -8px -295px;width: 36px;height: 22px;margin: 0 auto;
		$STR.='</td>';
		$STR.='<td>';
		if ($line['SPEED60']>0.06)
		{
			if ($line['ACCEL60']>0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -7px -297px;width: 36px;height: 21px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']==0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -25px -297px;width: 29px;height: 21px;margin: 0 auto;"></div>';///GOOD
			else if ($line['ACCEL60']<0.05 && $line['ACCEL60']>-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -56px -297px;width: 34px;height: 21px;margin: 0 auto;"></div>';///GOOD
			else if ($line['ACCEL60']==-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -88px -296px;width: 33px;height: 22px;margin: 0 auto;"></div>';
			else if ($line['ACCEL60']<-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -120px -297px;width: 29px;height: 21px;margin: 0 auto;"></div>';//GOOD
		}
		else if ($line['SPEED60']==0.06667)
		{
			if ($line['ACCEL60']>0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -10px -319px;width: 34px;height: 21px;margin: 0 auto;"></div>';
			else if ($line['ACCEL60']==0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -43px -319px;width: 26px;height: 21px;margin: 0 auto;"></div>';
			else if ($line['ACCEL60']<0.05 && $line['ACCEL60']>-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -58px -319px;width: 26px;height: 21px;margin: 0 auto;"></div>';
			else if ($line['ACCEL60']==-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -84px -321px;width: 26px;height: 21px;margin: 0 auto;"></div>';
			else if ($line['ACCEL60']<-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -109px -321px;width: 25px;height: 21px;margin: 0 auto;"></div>';
		}
		else if ($line['SPEED60']<0.06667 && $line['SPEED60']>-0.06667)
		{
			if ($line['ACCEL60']>0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -7px -342px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']==0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -25px -320px;width: 29px;height: 21px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']<0.05 && $line['ACCEL60']>-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -68px -342px;width: 27px;height: 23px;margin: 0 auto;"></div>';//Good
			else if ($line['ACCEL60']==-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -82px -342px;width: 27px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']<-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -109px -342px;width: 27px;height: 23px;margin: 0 auto;"></div>';//GOOD
		}
		else if ($line['SPEED60']==-0.06667)
		{
			if ($line['ACCEL60']>0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -7px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']==0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -26px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']<0.05 && $line['ACCEL60']>-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -75px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//Good
			else if ($line['ACCEL60']==-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -110px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']<-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -145px -365px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
		}
		else if ($line['SPEED60']<-0.06667)
		{
			if ($line['ACCEL60']>0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -7px -390px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']==0.05)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -45px -390px;width: 33px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']<0.05 && $line['ACCEL60']>-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -75px -390px;width: 35px;height: 23px;margin: 0 auto;"></div>';//Good
			else if ($line['ACCEL60']==-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -110px -390px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
			else if ($line['ACCEL60']<-0.03333)$STR.='<div style="background: url(require/img/DRUG_IMG.png) -145px -390px;width: 36px;height: 23px;margin: 0 auto;"></div>';//GOOD
		}
		
		$STR.='</td>';


		$STR.='<td><a href="/DISEASE/'.$USER_INPUT['PORTAL']['VALUE'].'/DISEASE_GENE_VIEW/'.$line['GENE_ID'].'"><img src="/require/img/view.png" style="width: 20px;"></a></td></tr>';
	}
	removeBlock("disease_genes","DISEASE");
	changeValue("disease_genes","COL",4);
}


changeValue("disease_genes","tbl",$STR);
?>