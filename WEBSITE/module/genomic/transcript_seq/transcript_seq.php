<?php

 if (!defined("BIORELS")) header("Location:/");




changeValue("transcript_seq","GENE_PORTAL","/GENEID/".$USER_INPUT['PORTAL']['DATA']['GENE_ID']);


if (isset($MODULE_DATA['ERROR']))
{
	removeBlock("transcript",'VALID_TRANSCRIPT');
	changeValue("transcript_seq","ERR_MSG",$MODULE_DATA['ERROR']);
	return;
}else removeBlock("transcript",'INVALID_TRANSCRIPT');



$TRANSCRIPT_NAME=$MODULE_DATA['INFO']['TRANSCRIPT_NAME'].(($MODULE_DATA['INFO']['TRANSCRIPT_VERSION']!=null)?'.'.$MODULE_DATA['INFO']['TRANSCRIPT_VERSION']:'');
$ENS=false;
if (substr($TRANSCRIPT_NAME,0,3)=='ENS')
{
	$ENS=true;
	changeValue("transcript_seq","TRANSCRIPT_LINK",str_replace('${LINK}',$TRANSCRIPT_NAME,$GLB_CONFIG['LINK']['ENSEMBL']['TRANSCRIPT']));
}
else 	changeValue("transcript_seq","TRANSCRIPT_LINK",str_replace('${LINK}',$TRANSCRIPT_NAME,$GLB_CONFIG['LINK']['REFSEQ']['TRANSCRIPT']));


$MAP=array();
foreach ($MODULE_DATA['SEQUENCE']['SEQUENCE'] as $POS=>&$INFO)$MAP[$INFO['TRANSCRIPT_POS_ID']]=$POS;
if (isset($MODULE_DATA['PROT'])&& count($MODULE_DATA['PROT'])>0)
		{
			$STR_L='';
			$STR='';
			foreach ($MODULE_DATA['PROT']['STAT'] as $PR)
			{
				$STR_L.="'".$PR['TR_PROTSEQ_AL_ID']."',";
				$STR.='<input type="checkbox" id="prot_'.$PR['TR_PROTSEQ_AL_ID'].'" checked="checked"/><label for="trans_seq_pos">Show Translation of '.$PR['ISO_NAME'].'</label><br/>';
			}
			changeValue("transcript_seq","LIST_PROT",substr($STR,0,-1));
			changeValue("transcript_seq","LIST_ALIGN",$STR_L);
if (isset($MODULE_DATA['PROT']['ALIGN']))
foreach ($MODULE_DATA['PROT']['ALIGN'] as $ALIGN_ID =>&$ALIGNMENT)
{
	$CORRECTION=array();
foreach ($ALIGNMENT as $TR_POS=>&$TR_INFO)$CORRECTION[$MAP[$TR_POS]]=$TR_INFO;
ksort($CORRECTION);
$MODULE_DATA['PROT']['ALIGN'][$ALIGN_ID]=$CORRECTION;
		}

		}else changeValue("transcript_seq","LIST_ALIGN",'');

changeValue("transcript_seq","TRANSCRIPT_SEQUENCE",str_replace("'","\\'",json_encode($MODULE_DATA)));
changeValue("transcript_seq","TRANSCRIPT_NAME",$TRANSCRIPT_NAME);
changeValue("transcript_seq","BIOTYPE",$MODULE_DATA['INFO']['BIOTYPE_NAME']);
changeValue("transcript_seq","FEATURE",$MODULE_DATA['INFO']['FEATURE_NAME']);
changeValue("transcript_seq","RANGE",'['.$MODULE_DATA['INFO']['START_POS'].' - '.$MODULE_DATA['INFO']['END_POS'].']');
$str='';
$sp=(int)$MODULE_DATA['INFO']['SUPPORT_LEVEL'];

for ($i=5;$i>=1;$i--)
		{	
			if ($sp==0){$str.='<div class="blk_bc confidence_block"></div>';continue;}
			if ($i < $MODULE_DATA['INFO']['SUPPORT_LEVEL']){$str.='<div class="grey_bc confidence_block"></div>';continue;}
			if ($sp==5){$str.='<div class="dgrey_bc confidence_block"></div>';continue;}
			if ($sp==4){$str.='<div class="dred_bc confidence_block"></div>';continue;}
			if ($sp==3){$str.='<div class="orange_bc confidence_block"></div>';continue;}
			if ($sp==2){$str.='<div class="dgreen_bc confidence_block"></div>';continue;}
			if ($sp==1){$str.='<div class="green_bc confidence_block"></div>';continue;}
		}
		
		changeValue("transcript_seq","SUPPORT_LEVEL",$str);



		$GENE_NAME=$MODULE_DATA['INFO']['GENE_SEQ_NAME'].(($MODULE_DATA['INFO']['GENE_SEQ_VERSION']!=null)?'.'.$MODULE_DATA['INFO']['GENE_SEQ_VERSION']:'');
		if (substr($GENE_NAME,0,3)=='ENS')
		{
			changeValue("transcript_seq","GENESEQ_LINK",str_replace('${LINK}',$GENE_NAME,$GLB_CONFIG['LINK']['ENSEMBL']['GENE']));
			
		}
		else 	changeValue("transcript_seq","GENESEQ_LINK",str_replace('${LINK}',$MODULE_DATA['INFO']['GENE_ID'],$GLB_CONFIG['LINK']['REFSEQ']['GENE']));
		
		changeValue("transcript_seq","GENESEQ_NAME",$GENE_NAME);
		changeValue("transcript_seq","GENESEQ_RANGE",'['.$MODULE_DATA['INFO']['GENE_START'].' - '.$MODULE_DATA['INFO']['GENE_END'].']');
		changeValue("transcript_seq","STRAND",(($MODULE_DATA['INFO']['STRAND']=="+")?"Positive":"Negative"));
		changeValue("transcript_seq","GENE_ID",$MODULE_DATA['INFO']['GENE_ID']);
		changeValue("transcript_seq","SYMBOL",$MODULE_DATA['INFO']['SYMBOL']);
		
	
		
/*
TRANSCRIPT_ID: "704848",
TRANSCRIPT_NAME: "NM_001798",
TRANSCRIPT_VERSION: "5",
START_POS: "55966830",
END_POS: "55972789",
BIOTYPE_NAME: "NULL",
BIOTYPE_SO_ID: null,
BIOTYPE_SO_NAME: null,
BIOTYPE_SO_DESC: null,
FEATURE_NAME: "mRNA",
FEATURE_SO_ID: "SO:0000234",
FEATURE_SO_NAME: "mRNA",
FEATURE_SO_DESC: "Messenger RNA is the intermediate molecule between DNA and protein. It includes UTR and coding sequences. It does not contain introns. ",
SUPPORT_LEVEL: "5",
GENE_SEQ_NAME: "CDK2",
GENE_SEQ_VERSION: null,
STRAND: "+",
GENE_START: "55966830",
GENE_END: "55972789",
GENE_ID: "1017",
SYMBOL: "CDK2",
FULL_NAME: "cyclin dependent kinase 2"



<script type="text/javascript">




function showTransSeqOpts()
{
	$( "#transcript_seq_options" ).dialog({width: "40%",modal: true,
      buttons: {
        "Confirm": function() {
			processTranscriptView("transcript_seq_view");
          $( this ).dialog( "close" );
        },
        
      }});	
}

var data_transcript='${TRANSCRIPT_SEQUENCE}';
var list_align=[${LIST_ALIGN}];
var info;


function genSimpleView()
{
	var search=RNAtoDNA($("#trans_seq_search").val().toUpperCase()).split("\n");
	 
	seq='';
	$.each(info.SEQUENCE.SEQUENCE, function(index,value){if (value['NUCL']!='')seq+=value['NUCL'];else seq+=' ';});
	var indices=[];
	var indices_alt=[];
	$.each(search, function(index,value){
		
		indices.push(getIndicesOf(value,seq.replace(" ","")));
		
		indices_alt.push(getIndicesOf(genReverseComplement(value),seq.replace(" ","")));
		
	});
	var len=seq.length;
	var str='Simplified view:<br/><div style="position:relative">';var sum=0;
		is_first=true;
	$.each(info.SEQUENCE.EXONS, function(index,value){
			if (index=='')return true;
			var min_exon=value.MIN;
			var max_exon=value.MAX;
			var left=parseFloat((min_exon-1)*100/len).toFixed(4);
			var width=parseFloat((max_exon*100/len)-left).toFixed(4);
			sum+=width;
			str+="<div  class=' ";
			//console.log(value.MIN+" "+value.MAX+" "+len);
			//if (index%2==0){str+='exon_odd'; n_exon=true;}
			//else {str+='exon_even'; n_exon=false}
			str+='exon_even';
			str+="' style='font-size: 0.7em;font-style: italic;position:absolute;display: inline-table;left:"+left+"%;width:"+width+"%;'></div>";
			is_first=false;
	});
	//console.log(sum);
	str+='</div>'; str+='<div style="position:relative;top:16px;">';
	is_first=true;
	$.each(info.SEQUENCE.POS_TYPE, function(index,value){
			var min_exon=value.MIN;
			var max_exon=value.MAX;
			var left=parseFloat((min_exon-1)*100/len).toFixed(2);
			var width=parseFloat((max_exon*100/len)-left).toFixed(2);
			str+="<div  class='";
			if (value.TYPE=="5'UTR"||value.TYPE=="3'UTR"||value.TYPE=="3'UTR-INFERRED"|| value.TYPE=="5'UTR-INFERRED") str+="trsq_UTR_view";
			else if (value.TYPE=='CDS'||value.TYPE=="CDS-INFERRED")str+='trsq_CDS_view';
			else if (value.TYPE=='non-coded'||value.TYPE=="non-coded-INFERRED")str+='trsq_nc_view';
			else if (value.TYPE=='poly-A'||value.TYPE=="unknown")str+='trsq_unk';

			str+="' style='font-size: 0.7em;font-style: italic;position:absolute;display: inline-table;left:"+left+"%;width:"+width+"%;";
			is_first=false;
			
			str+="'>";
			if (value.TYPE=="3'UTR-INFERRED"|| value.TYPE=="5'UTR-INFERRED") str+="</div>";
			else str+=value.TYPE+"</div>";
	});
	str+='</div><div style="position:relative;top:34px;">';
	$.each(indices,function(index,list_search)
	{
		$.each(list_search, function(index_s,pos)
		{
			var left=parseFloat((pos)*100/len).toFixed(2);
			var width=Math.max(parseFloat((search[index].length/len)).toFixed(2),2);
			str+="<div class='exon_odd' style='background-color:grey;position:absolute;display: inline-table;left:"+left+"%;width:"+width+"px; '></div>";
		});
						console.log (index+" "+search[index]+" "+list_search);
	});
	$.each(indices_alt,function(index,list_search)
	{
		$.each(list_search, function(index_s,pos)
		{
			var left=parseFloat((pos)*100/len).toFixed(2);
			var width=Math.max(parseFloat((search[index].length/len)).toFixed(2),2);
			str+="<div class='exon_odd' style='background-color:green;position:absolute;display: inline-table;left:"+left+"%;width:"+width+"px; '></div>";
		});
						console.log (index+" "+search[index]+" "+list_search);
	});
					/*$.each(indices_alt,function(index,list_search)
					{
						if ($.inArray(pos,list_search)!=-1 && current_start==-1)
						{
							current_start=pos;
							current_search_strand='-';
							current_search_len=search[index].length;
							str+='<span style="background-color:#99FFCC">';
						}
					});
					str+="</div>";

	console.log("VIEW");
	console.log(indices);
	$("#search_view").html(str);
}

function processTranscriptView(view)
{
	
	var search=RNAtoDNA($("#trans_seq_search").val().toUpperCase()).split("\n");
	 

	info=JSON.parse(data_transcript);
	$("#"+view).css("width","inherit");
	var dw=$("#"+view).width();	/// Width of div
	var fs = 16;					/// fs and fc set for Courier New
	var fc = 1.61;
	//var ratio=9.592307;
	var ratio=9.601907;
	var cpl = Math.floor(Math.floor(fc*dw / fs )/10)*10; /// Character per line.
	$("#"+view).css("width",(ratio*cpl+5)+"px");
	$("#search_view").css("width",(ratio*cpl+5)+"px");
	$("#trsq_options").css("width",(ratio*cpl+5)+"px");
	var max_width=cpl*fs/fc;
	var seq='';
	var str='';
	
	genSimpleView();
	
	$.each(info.SEQUENCE.SEQUENCE, function(index,value)
	{
		
		if (value['NUCL']!=null)seq+=value['NUCL'];
		else seq+=' ';
		
	});
	var indices=[];
	var indices_alt=[];
	$.each(search, function(index,value){
		
		indices.push(getIndicesOf(value,seq.replace(" ","")));
		indices_alt.push(getIndicesOf(genReverseComplement(value),seq.replace(" ","")));
		
	});

	console.log(search);
	
	var tot_line=Math.ceil(seq.length/cpl);
	var debug=false;
	
	if (debug)console.log("LENGTH: "+seq.length+" ; CHAR PER LINE: "+cpl+" ; N LINES: "+tot_line+" MAX WIDTH:"+max_width);
	var current_start=-1,current_search_len=-1,current_search_strand='+';
	for(n_line=1;n_line<=tot_line;++n_line)
	{
		var start_pos=(n_line-1)*cpl;
		var end_pos=start_pos+cpl;
		if (debug)console.log("LINE "+n_line+" ["+start_pos+" - "+end_pos+"] /" +current_start);
		str+='<div style="margin-bottom:50px"><div class="seq">';
			if (search[0] !="")
			{
			
			for(pos=start_pos; pos<end_pos;++pos)
			{
				if (current_start==-1)
				{
					$.each(indices,function(index,list_search)
					{
						if ($.inArray(pos,list_search)!=-1 && current_start==-1)
						{
							current_start=pos;
							current_search_strand='+';
							current_search_len=search[index].length;

							str+='<span style="background-color:grey">';
						}
					});
					$.each(indices_alt,function(index,list_search)
					{
						if ($.inArray(pos,list_search)!=-1 && current_start==-1)
						{
							current_start=pos;
							current_search_strand='-';
							current_search_len=search[index].length;
							str+='<span style="background-color:#99FFCC">';
						}
					});
					if (seq.charAt(pos)==' ')
					{
						str+='<span style="border:1px solid black" data-toggle="tooltip" data-placement="top" title="A nucleotide is missing when compared to the DNA"> </span>';
						if (current_start!=-1)current_search_len+=1;
					}
					 else str+=seq.charAt(pos);
				}
				else if (current_start!=-1)
				{
					if (pos==start_pos)
					{
						if (current_search_strand='+')str+='<span style="background-color:grey">';
							else 					  str+='<span style="background-color:#99FFCC">';
					}
					if (pos>=current_start+current_search_len)
					{
						current_start=-1;
						str+="</span>";
					}
					if (seq.charAt(pos)==' ')
					{
						str+='<span style="border:1px solid black" data-toggle="tooltip" data-placement="top" title="A nucleotide is missing when compared to the DNA"> </span>';
						if (current_start!=-1)current_search_len+=1;
					
					}
					 else str+=seq.charAt(pos);
				}
				
			}
			if (current_start!=-1)str+='</span>';
		}else str+=seq.substr(start_pos,cpl).replace(" ",'<span style="border:1px solid black" data-toggle="tooltip" data-placement="top" title="A nucleotide is missing when compared to the DNA"> </span>');
			//console.log(n_line+" "+current_start);
			str+="</div>";
			if ($('#trans_seq_pos').prop("checked"))
			{
			str+="<div class='ids'>";
			for(var i=1;i<=cpl;++i)
			{
				if (i+start_pos>seq.length)break;
				if (i%10==0)str+="|";
				else str+="&#183;";
			}
			
			str+="</div><div class='tens'>";
		var min_ten=Math.ceil(start_pos/10);
		var max_ten=Math.floor(end_pos/10);
		for (var ten=min_ten;ten<=max_ten;++ten)
		{
			if (ten*10<seq.length)
			str+="<div class='ten_bc' style='left:"+((ten*10-start_pos-((ten==min_ten)?0:1.5))*(ratio))+"px'>"+((ten==min_ten)?ten*10+1:(ten*10))+"</div>";
		}
		str+="</div>";
		}
		
		if ($('#trans_seq_exon').prop("checked"))
		{
		str+="<div class='exon'>";
		$.each(info.SEQUENCE.EXONS, function(index,value){
			if (index=='')return true;
			var min_exon=value.MIN;
			var max_exon=value.MAX;
			if (end_pos < min_exon)return true;
			if (start_pos > max_exon)return true;
			if (debug)console.log("---> EXON "+index+" ["+min_exon+"-"+max_exon+"]");
			var left=0;
			var width=0;
			if (min_exon>=start_pos)
			{
				if (max_exon>=end_pos){left=(Math.max(min_exon,0)-start_pos)*ratio;width=(end_pos-((min_exon==0)?0:min_exon))*ratio;
				if (left > 0){left -=ratio;width+=ratio;}
					if (left+width>max_width)width=max_width-left;
					if (debug)	console.log("------> TYPE1 : "+(Math.max(min_exon-1,0)-start_pos)+" / "+(end_pos-min_exon));
					}
				else if (max_exon<end_pos){left=(Math.max(min_exon-1,0)-start_pos)*ratio;width=(max_exon-min_exon+1)*ratio;
					if (left+width>max_width)width=max_width-left;
					if (debug)console.log("------> TYPE2 : "+(Math.max(min_exon-1,0)-start_pos)+" / "+(max_exon-min_exon));
				}
			}
			else if (min_exon < start_pos){
				if (max_exon>=end_pos)
				{
					left=0;width=(cpl)*ratio;
					if (left+width>max_width)width=max_width-left;
					if (debug)console.log("------> TYPE3 : 0 / "+(cpl));
					}
					else if (max_exon < end_pos)
					{
						left=0;width=(max_exon-start_pos)*ratio;
						if (left+width>max_width)width=max_width-left;
						if (debug)console.log("------> TYPE4 : 0 / "+(cpl));
					}
				}
			
			str+="<div id='exon_"+index.toString()+"_"+n_line+"' class='transcript_seq_info ";
			//if (index%2==0){str+='exon_odd'; n_exon=true;}
			//else {str+='exon_even'; n_exon=false}
			str+='exon_even';
			str+="' style='left:"+left+"px;width:"+width+"px;'>Exon "+index+"</div>";
		});
		str+="</div>";
		}
		if ($('#trans_seq_cds').prop("checked"))
		{
		str+="<div class='utrs'>";
				$.each(info.SEQUENCE.POS_TYPE, function(index,value){
			var min_exon=value.MIN;
			var max_exon=value.MAX;
			if (end_pos < min_exon)return true;
			if (start_pos > max_exon)return true;
			if (debug)console.log("---> POS_TYPE "+index+" ["+min_exon+"-"+max_exon+"] "+value.TYPE);
			var left=0;
			var width=0;
			if (min_exon>=start_pos)
			{
				if (max_exon>=end_pos){left=(Math.max(min_exon,0)-start_pos)*ratio;width=(end_pos-((min_exon==0)?0:min_exon))*ratio;
				if (left > 0){left -=ratio;width+=ratio;}
					if (left+width>max_width)width=max_width-left;
					if (debug)console.log("------> TYPE1 : "+(min_exon-start_pos)+" / "+(end_pos-min_exon));
					}
				else if (max_exon<end_pos){left=(Math.max(min_exon-1,0)-start_pos)*ratio;width=(max_exon-min_exon+1)*ratio;
					if (left+width>max_width)width=max_width-left;
					if (debug)console.log("------> TYPE2 : "+(min_exon-start_pos)+" / "+(max_exon-min_exon));
				}
			}
			else if (min_exon < start_pos){
				if (max_exon>=end_pos)
				{
					left=0;width=(cpl)*ratio;
					if (debug)console.log("------> TYPE3 : 0 / "+(cpl));
					}
					else if (max_exon < end_pos)
					{
						left=0;width=(max_exon-start_pos)*ratio;
						if (debug)console.log("------> TYPE4 : 0 / "+(cpl));
					}
				}
			
			str+="<div id='pos_Type_"+index.toString()+"_"+n_line+"' class='transcript_seq_info ";
			if (value.TYPE=="5'UTR"||value.TYPE=="3'UTR"||value.TYPE=="3'UTR-INFERRED"|| value.TYPE=="5'UTR-INFERRED") str+="trsq_UTR_view";
			else if (value.TYPE=='CDS'||value.TYPE=="CDS-INFERRED")str+='trsq_CDS_view';
			else if (value.TYPE=='non-coded'||value.TYPE=="non-coded-INFERRED")str+='trsq_nc_view';
			else if (value.TYPE=='poly-A'||value.TYPE=="unknown")str+='trsq_unk';

			
			
			
			
			if(value.TYPE=="3'UTR-INFERRED"|| value.TYPE=="5'UTR-INFERRED") 
			{
				str+="' style='left:"+left+"px;width:"+width+"px;color:green;border:1px solid white' data-toggle='tooltip' data-placement='top' title="+'"No DNA position found -'+value.TYPE+'">_</div>';
			
			}else str+="'  style='left:"+left+"px;width:"+width+"px;'>"+value.TYPE+"</div>";
		

		});

		
		str+="</div>";
		
	}
	$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})
	
		var n_step=0;
		$.each(list_align,function(index,id_div){
			if ($("#prot_"+id_div).prop("checked")==false)return true;
			n_step+=15;
			str+='<div class="seq" style="position:relative; top:37px">';
			for(pos=start_pos; pos<end_pos;++pos)
			{
				var t=pos+1;
				if (t in info.PROT.ALIGN[id_div])
				{
					if (info.PROT.ALIGN[id_div][t][2]!=2)str+='-';
					else str+=info.PROT.ALIGN[id_div][t][0];
				}else if (pos <seq.length) str+='.';
			}
			str+='</div>';

		});
		if ($('#trans_seq_snp').prop("checked"))
	{
			str+="<div class='tr_snp' style='position:relative; top:37px'>";
				for(pos=start_pos; pos<end_pos;++pos)
			{
				var t=pos+1;
				if (t in info.SNP.LIST)
				{
					str+='<div style="display:inline" onmouseover="showSNPInfo(this,'+t+')">-</div>';
					
				}else  if (pos <seq.length) str+='.';
			}
			str+='</div>';
			
	}
		str+="</div>";



	}

	$("#"+view).html(str);
}



// onmouseout="hideSNPInfo(this,'+t+')" 
var current_show=null;
function showSNPInfo(div_entry,pos)
{
	if ($(div_entry).children().length>0)return;
	if (current_show!=null)clean();

	$(div_entry).addClass("tooltipt");
	var snp_i=info.SNP.LIST[pos];
	
	var str='<div class="tooltiptextv" style="min-width:250px !important">';
	$.each(snp_i, function(rsid, value)
	{
		str+='<span class="bold">Variant ID:</span><a href="${GENE_PORTAL}/MUTATION/'+rsid+'">'+rsid+'</a></span>';
		$.each(value, function(index,infos)
		{
			str+="<br/>DNA:"+index+" ("+info.SNP.TYPES[infos[0]]+") &#x27A1 Transcript:"+infos[2]+" ("+info.SNP.IMPACT[infos[1]]+")";
		});
		str+='<br/>';
	});
	$(div_entry).prepend(str);
	current_show=div_entry;
	console.log(current_show);
}

function clean()
{
	if (current_show==null)return;
	console.log("clean");
	$(current_show).empty();
	$(current_show).removeClass("tooltipt");
	$(current_show).html('-');
}

function hideSNPInfo(div_entry,pos)
{
	if ($(div_entry).children().length==0)return;
	console.log("clean");
	setTimeout(clean,4000);
	
	
}

            


$(document).ready(function(){
	processTranscriptView("transcript_seq_view");
	var transcript_view = $("transcript_seq_view");
	var lastwidth = transcript_view.css('width');
function checkForTranscriptViewChanges()
{
    if (transcript_view.css('width') != lastwidth)
    {
        lastwidth = transcript_view.css('width'); 
		processTranscriptView("transcript_view");
    }

    setTimeout(checkForTranscriptViewChanges, 500);
};


});


</script>
*/
?>