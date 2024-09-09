let ms_graph_url = 'https://graph.microsoft.com/v1.0/';
let ms_graph_beta_url = 'https://graph.microsoft.com/beta/';




function showUserPicture(id,data_users,text_pos="below",id_img='')
{ 
try {

    
var index=0;


$.each(data_users,function(email,user_info){
++index;
str_id=id_img+'_'+index;
console.log(text_pos);
let str = '<div style="height:100px">';
//else if (text_pos=='right') str+=
str += '<div id="user_pic_' + str_id + '" class="user-avatar w3-col s3 l1 m1 w3-center" style="min-width:150px;max-width:150px;width:150px;"></div>';
str += '<div class="w3-col s9 m9 l9" style="position:relative; top:15px;width:auto">';
str+='<h5>' + user_info['First_name'] + ' ' + user_info['Last_name'] + '</h5>';
str += '<p>';
    console.log(email+" "+user_info);
    $.each(user_info['ACCESS'],function(index,v){
        str+=v+"<br/>";

    });str+= '</p></div>';
str += '</div>';
$("#"+id).html(str);
if (email !== null && email !== 'NA') {
    getUserPicture(email, str_id, 'max-height:75px; height:75px;');
} else {
    $("#user_pic_" + str_id).css("display", "none");
}
});



} catch (err) {
console.log(err);

}

}

async function getUserPicture(email, tag,css='') {
try {
    console.log("CALL GET USER PICTURE FOR "+email);
let isTokenExists = Object.keys(localStorage).filter(x => localStorage.getItem(x).includes('tokenType'));
if (isTokenExists.length > 0) {
    let token = JSON.parse(localStorage[isTokenExists[0]]).secret;
    const response = await axios('https://graph.microsoft.com/v1.0/users/' + email + '/photo/$value', {
        headers: { Authorization: 'Bearer ' + token },
        responseType: "arraybuffer"
    });
    console.log(email);
    console.log(response);
    if (response.data!=null)
    {
    const avatar = btoa(String.fromCharCode.apply(null, new Uint8Array(response.data)));
    var src = "data:image/jpeg;base64, " + avatar;
    console.log("INN "+css);
    $("#user_pic_" + tag).html('<img style="'+css+'" src="' + src + '"/>');
    }
    else
    {
        console.log("IN");
        $("#user_pic_" + tag).html('<div style="width:50px;height:50px;background-color:red;border-radius:50%"></div>');
    }
}
} catch (err) {
console.log(err);
$("#user_pic_" + tag).html('<div class="portal_col9" style="width:40px;height:40px;border-radius:50%"></div>');
}
}





function submitWelcomeSearch()
{
	var type_s=$("#search_options").val();
	var str = $("#search_input"). val();
	var human=($("#search_human").is(":checked"))?"HUMAN":"ALL";

	
    window.location.href = "/SEARCH/PARAMS/"+type_s+"/"+str.replace("/","___")+"/"+human;
    return false;
}
var current_menu=-1;
function toggleMenu(num,max)
{
    for (i=1;i<=max;++i)
    {
        $(".menu"+i).removeClass("w3-show");
        $(".menu"+i).addClass("w3-hide");
    }
    if (current_menu!=num){
    $(".menu"+num).addClass("w3-show");
    current_menu=num;
    $(".menu"+num).removeClass("w3-hide");
    }else current_menu=-1;
    
}

function changeOwner(step_id, global_id, project_tag, workflow_hash) {
    try {
        showLoader();
        var urlv = '/JSON/PROJECT/' + project_tag + '/API_USER/PARAMS/OWNER/GLOBAL_ID/' + global_id + '/STEP/' + step_id + "/WORKFLOW/" + workflow_hash;
        $.ajax({
            type: "GET",
            url: urlv,
            error: (error) => {
                console.log(error);
                hideLoader();
            },
            success: (result) => {
                showToastMsg(result.message);
                hideLoader();
                location.reload();
            }
        });
    } catch (error) {
        console.log(error);
        hideLoader();
    }
}



function getAccessToken() {
    return Object.keys(localStorage).filter(x => localStorage.getItem(x).includes('tokenType'));
}

function validateTissue(tag, is_human=true)
{try{
	console.log(tag);
	var value=$("#tsval_input_"+tag).val();
	console.log(value);

		var urlv='/JSON/SEARCH_TISSUE/'+value;
		
		$.ajax({
			type:"GET",
			url: urlv,
	
			error: function() {  $('#status_${TAG}').html('<p>An error has occurred</p>');},
			success: function(data) {
			var result=jQuery.parseJSON(data);	
			var n=0;
            $("#tsval_sel_" + tag).empty();
			$.each(result,function(geneid,dataT){++n;});
			$.each(result,function(geneid,dataT){
				var txt=dataT.TISSUE_NAME;
				
				var option = $('<option></option>').attr("value", dataT.TISSUE_NAME).text(txt);
				if (n==1)option.attr("selected","selected");
				$("#tsval_sel_"+tag).append(option);
			});
			$("#tsval_sel_"+tag).css("display","block");
			
			
			$("#tsval_input_"+tag).css("display","none");
			$("#tsval_val_"+tag).css("display","none");

			},
			type: 'GET'
		});///END AJAX
	}catch(err) {
	$('#status_${TAG}').html('<p>An error has occurred</p>');
	}

}


function validateCompany(tag, is_human=true)
{try{
	console.log(tag);
	var value=$("#companyval_input_"+tag).val();
	console.log(value);

		var urlv='/JSON/SEARCH_COMPANY/'+value;
		
		$.ajax({
			type:"GET",
			url: urlv,
	
			error: function() {  $('#company_status_'+tag).html('<p>An error has occurred</p>');},
			success: function(result) {
			
			var n=0;
            $("#companyval_sel_" + tag).empty();
			$.each(result,function(company,dataT){++n;});
			$.each(result,function(company,dataT){
				var txt=company;
				
				var option = $('<option></option>').attr("value", company).text(txt);
				if (n==1)option.attr("selected","selected");
				$("#companyval_sel_"+tag).append(option);
			});
			$("#companyval_sel_"+tag).css("display","block");
			
			
			$("#companyval_input_"+tag).css("display","none");
			$("#companyval_val_"+tag).css("display","none");
            $("#cancel_company_" + tag).css("display", "block");
			},
			type: 'GET'
		});///END AJAX
	}catch(err) {
	$('#company_status_'+tag).html('<p>An error has occurred</p>');
	}

}

function cancelCompanyValidate(tag) {
    $("#cancel_company_" + tag).css("display", "none");
    $("#companyval_sel_" + tag).css("display", "none");
    $("#companyval_input_" + tag).css("display", "inline-block");
    $("#companyval_val_" + tag).css("display", "inline-block");
}


function validateNews(tag) {
    try {
        showLoader();
        var value = $("#newsval_input_" + tag).val();
        if (!value) {
            hideLoader();
            setTimeout(() => {
                $('#news_status_' + tag).css('display', 'inline-block');
                $('#news_status_' + tag).html('Input field should not be empty');
            }, 1000);
            return;
        } else {
            $('#news_status_' + tag).css('display', 'none');
        }
        var urlv = '/JSON/SEARCH_NEWS/' + value;
        
        $.ajax({
            type: "GET",
            url: urlv,
            error: function () {
                $('#news_status_' + tag).html('<p>Unable to search the database</p>');
                $('#news_status_' + tag).css('display', 'inline-block');
                hideLoader();
            },
            success: function (data) {
                try {
                    if (data && Object.keys(data).length > 0) {
                        var result = data;
                        var n = 0;
                        $("#newsval_sel_" + tag).html("");
                        $.each(result, function (news_hash, dataT) { ++n; });
                        $.each(result, function (news_hash, dataT) {
                            console.log(news_hash);
                            console.log(dataT);
                            if (news_hash == '') return true;

                            var txt = dataT.Title +" ("+dataT.Source+")";
                            var option = $('<option></option>').attr("value", news_hash).text(txt);
                            if (n == 1) option.attr("selected", "selected");
                            $("#newsval_sel_" + tag).append(option);
                        });
                        $("#cancel_news_" + tag).css("display", "inline-block");
                        $("#newsval_sel_" + tag).css("display", "inline-block");
                        $("#newsval_input_" + tag).css("display", "none");
                        $("#newsval_val_" + tag).css("display", "none");
                    } else {
                        $('#news_status_' + tag).css('display', 'inline-block');
                        $('#news_status_' + tag).html('No news found');
                    }
                } catch (err) {
                    $('#news_status_' + tag).css('display', 'inline-block');
                    $('#news_status_' + tag).html('An error has occurred');
                }
                hideLoader();
            }
        });
    } catch (err) {
        $('#news_status_' + tag).html('An error has occurred');
        $('#news_status_' + tag).css('display', 'inline-block');
        hideLoader();
    }
}

function cancelNewsValidate(tag) {
    $("#cancel_news_" + tag).css("display", "none");
    $("#newsval_sel_" + tag).css("display", "none");
    $("#newsval_input_" + tag).css("display", "inline-block");
    $("#newsval_val_" + tag).css("display", "inline-block");
}

function validateGene(tag, is_human = true) {
    try {
        showLoader();
        var value = $("#gnval_input_" + tag).val();
        if (!value) {
            hideLoader();
            setTimeout(() => {
                $('#gn_status_' + tag).css('display', 'inline-block');
                $('#gn_status_' + tag).html('Input field should not be empty');
            }, 1000);
            return;
        } else {
            $('#gn_status_' + tag).css('display', 'none');
        }
        var urlv = '/JSON/SEARCH_GENE/' + value;
        if (is_human) urlv += '/PARAMS/HUMAN';
        $("#gnval_sel_" + tag).empty();
        $.ajax({
            type: "GET",
            url: urlv,
            error: function () {
                $('#gn_status_' + tag).html('<p>Unable to search the database</p>');
                $('#gn_status_' + tag).css('display', 'inline-block');
                hideLoader();
            },
            success: function (data) {
                try {
                    
                    if (data && Object.keys(data).length > 0) {
                        var result = data;
                        var n = 0;

                        $.each(result, function (geneid, dataT) { ++n; });
                        $.each(result, function (geneid, dataT) {
                            if (geneid == '') return true;
                            var txt = dataT.Symbol + " " + dataT["Gene Name"];
                            txt += " / " + dataT.Organism;
                            var option = $('<option></option>').attr("value", geneid).text(txt);
                            if (n == 1) option.attr("selected", "selected");
                            $("#gnval_sel_" + tag).append(option);
                        });
                        $("#cancel_gn_" + tag).css("display", "inline-block");
                        $("#gnval_sel_" + tag).css("display", "inline-block");
                        $("#gnval_input_" + tag).css("display", "none");
                        $("#gnval_val_" + tag).css("display", "none");
                    } else {
                        $('#gn_status_' + tag).css('display', 'inline-block');
                        $('#gn_status_' + tag).html('No genes found');
                    }
                } catch (err) {
                    $('#gn_status_' + tag).css('display', 'inline-block');
                    $('#gn_status_' + tag).html('An error has occurred');
                }
                hideLoader();
            }
        });
    } catch (err) {
        $('#gn_status_' + tag).html('An error has occurred');
        $('#gn_status_' + tag).css('display', 'inline-block');
        hideLoader();
    }
}

function cancelGeneValidate(tag) {
    $("#cancel_gn_" + tag).css("display", "none");
    $("#gnval_sel_" + tag).css("display", "none");
    $("#gnval_input_" + tag).css("display", "inline-block");
    $("#gnval_val_" + tag).css("display", "inline-block");
}

function cancelDiseaseValidate(tag) {
    $("#cancel_ds_" + tag).css("display", "none");
    $("#dsval_sel_" + tag).css("display", "none");
    $("#dsval_input_" + tag).css("display", "inline-block");
    $("#dsval_val_" + tag).css("display", "inline-block");
}


function validateDisease(tag, is_human=true){
    try{
        console.log(tag);
        var value=$("#dsval_input_"+tag).val();        
		console.log(value);

        var urlv='/JSON/SEARCH_DISEASE/'+value;
		if(is_human)urlv+='/PARAMS/HUMAN';        
        $("#dsval_sel_" + tag).empty();
		$.ajax({
			type:"GET",			
            url: urlv,            
			error: function(){$('#status_'+tag).html('<p>An error has occurred</p>');                         
            },
			success: function(data) {                
                console.log(data);
                try{
                    console.log('TRY');
                    if (data=='[]'){
                        console.log('no data');
                        $('#status_'+tag).html('No results');                        
                        return true;
                    }
                    else {                        
                        $('#status_'+tag).css('display','none');                        
                        // var result=jQuery.parseJSON(data);	
                        var result=data;                        
                        var n=0;
                        $.each(result,function(geneid,dataT){++n;});
                        $.each(result,function(geneid,dataT){
    				        var txt=dataT['Disease ID']+" "+dataT['Disease Name'];                            
                            var option = $('<option></option>').attr("value", geneid).text(txt);
                            if (n==1)option.attr("selected","selected");
                            $("#dsval_sel_"+tag).append(option);
			            });
                    $("#cancel_ds_" + tag).css("display", "inline-block");
                    $("#dsval_sel_"+tag).css("display","block");
                    $("#dsval_input_"+tag).css("display","none");
                    $("#dsval_val_"+tag).css("display","none");
                    }
                }
                catch(err){
                    console.log('err', err);
                    $('#status_'+tag).html('An error has occurred');$('#status_'+tag).css('display','block');
                }
			},
			type: 'GET'
		});///END AJAX
	}
    catch(err) {
        console.log('err', err);
	    $('#status_'+tag).html('<p>An error has occurred</p>');
	}

}

function validateDrug(tag) {
  try {
    console.log(tag);
    var value = $("#dgval_input_" + tag).val();
    console.log(value);
    var urlv = "/JSON/SEARCH_DRUG/" + value;
    $("#dgval_sel_" + tag).empty();
    if (!value) {
    hideLoader();
    setTimeout(() => {
        $("#dg_status_" + tag).css("display", "inline-block");
        $("#dg_status_" + tag).html("Input field should not be empty");
    }, 1000);
    return;
    } else {
    $("#dg_status_" + tag).css("display", "none");
    }
    $("#dgval_sel_" + tag).empty();
    $.ajax({
      type: "GET",
      url: urlv,
      error: function() {
        $("#status_" + tag).html("<p>An error has occurred</p>");
      },
      success: function(result) {
        try {
          var n = 0;
          //   var result = jQuery.parseJSON(data);
          $.each(result, function(geneid, dataT) {
            ++n;
          });
          if (n) {
            $.each(result, function(geneid, dataT) {
              var txt = dataT["Drug Name"];
              var id = dataT["Primary Name"];
              console.log("id", id);
              var option = $("<option></option>")
                .attr("value", id)
                .text(txt);
              if (n == 1) option.attr("selected", "selected");
              $("#dgval_sel_" + tag).append(option);
            });
            $("#cancel_dg_" + tag).css("display", "inline-block");
            $("#dgval_sel_" + tag).css("display", "block");
            $("#dgval_input_" + tag).css("display", "none");
            $("#dgval_val_" + tag).css("display", "none");
          } else {
            $("#dg_status_" + tag).css("display", "inline-block");
            $("#dg_status_" + tag).html("No drugs found");
          }
        } catch (err) {
          console.log("err", err);
          $("#dg_status_" + tag).html("An error has occurred");
          $("#dg_status_" + tag).css("display", "block");
        }
      },
      type: "GET"
    }); ///END AJAX
  } catch (err) {
    console.log("err", err);
    $("#dg_status_" + tag).html("<p>An error has occurred</p>");
  }
}

function cancelDrugValidate(tag) {
    $("#cancel_dg_" + tag).css("display", "none");
    $("#dgval_sel_" + tag).css("display", "none");
    $("#dgval_input_" + tag).css("display", "inline-block");
    $("#dgval_val_" + tag).css("display", "inline-block");
}

function showLoader() {    
    $("div.spinner").addClass("show");        
}

function hideLoader() {
    $("div.spinner").removeClass("show");
}


function validateCompound(tag) {
  try {
    showLoader();
    var value = $("#cpdval_input_" + tag).val();    
    if (!value) {
      hideLoader();
      setTimeout(() => {
        $("#cpd_status_" + tag).css("display", "inline-block");
        $("#cpd_status_" + tag).html("Input field should not be empty");
      }, 1000);
      return;
    } else {
      $("#cpd_status_" + tag).css("display", "none");
    }
    var urlv = "/JSON/SEARCH_COMPOUND/" + value;
    console.log("VALUE:" + value);
    console.log(urlv);
    $("#cpdval_sel_" + tag).empty();

    $.ajax({
      type: "GET",
      url: urlv,
      error: function() {
        $("#cpd_status_" + tag).html("<p>Unable to search the database</p>");
        $("#cpd_status_" + tag).css("display", "inline-block");
        hideLoader();
      },
      success: function(data) {
        try {
          var n = 0;
          var result = jQuery.parseJSON(data);          
          $.each(result, function(cltrid, dataT) {
            ++n;
          });
          if (n) {
            $.each(result, function(cltrid, dataT) {
              if (cltrid == "") return true;
              var option = $("<option></option>")
                .attr("value", cltrid)
                .text(dataT["Compound Name"]);
              if (n == 1) option.attr("selected", "selected");
              $("#cpdval_sel_" + tag).append(option);
            });
            $("#cancel_cpd_" + tag).css("display", "inline-block");
            $("#cpdval_sel_" + tag).css("display", "inline-block");
            $("#cpdval_input_" + tag).css("display", "none");
            $("#cpdval_val_" + tag).css("display", "none");
          } else {
            $("#cpd_status_" + tag).css("display", "inline-block");
            $("#cpd_status_" + tag).html("No compounds found");
          }
        } catch (err) {
          console.log(err);
          $("#cpd_status_" + tag).css("display", "inline-block");
          $("#cpd_status_" + tag).html("An error has occurred");
        }
        hideLoader();
      }
    });
  } catch (err) {
    console.log(err);
    $("#cpd_status_" + tag).html("An error has occurred");
    $("#cpd_status_" + tag).css("display", "inline-block");
    hideLoader();
  }
}

function cancelCompoundValidate(tag) {
    $("#cancel_cpd_" + tag).css("display", "none");
    $("#cpdval_sel_" + tag).css("display", "none");
    $("#cpdval_input_" + tag).css("display", "inline-block");
    $("#cpdval_val_" + tag).css("display", "inline-block");
}


function validateClinical(tag) {
    try {
        showLoader();
        var value = $("#clinicalval_input_" + tag).val();
        if (!value) {
            hideLoader();
            setTimeout(() => {
                $('#clinical_status_' + tag).css('display', 'inline-block');
                $('#clinical_status_' + tag).html('Input field should not be empty');
            }, 1000);
            return;
        } else {
            $('#clinical_status_' + tag).css('display', 'none');
        }
        $("#clinicalval_sel_" + tag).empty();
        var urlv = '/JSON/SEARCH_CLINICAL/' + value;
        console.log("VALUE:"+value);
        console.log(urlv);
        $.ajax({
            type: "GET",
            url: urlv,
            error: function () {
                $('#clinical_status_' + tag).html('<p>Unable to search the database</p>');
                $('#clinical_status_' + tag).css('display', 'inline-block');
                hideLoader();
            },
            success: function (data) {
                
                try {
                    if (data && Object.keys(data).length > 0) {
                        var result = data;
                        var n = 0;
                        $.each(result, function (cltrid, dataT) { ++n; });
                        $.each(result, function (cltrid, dataT) {
                            if (cltrid == '') return true;
                           
                            var option = $('<option></option>').attr("value", cltrid).text(cltrid);
                            if (n == 1) option.attr("selected", "selected");
                            $("#clinicalval_sel_" + tag).append(option);
                        });
                        $("#cancel_clinical_" + tag).css("display", "inline-block");
                        $("#clinicalval_sel_" + tag).css("display", "inline-block");
                        $("#clinicalval_input_" + tag).css("display", "none");
                        $("#clinicalval_val_" + tag).css("display", "none");
                    } else {
                        $('#clinical_status_' + tag).css('display', 'inline-block');
                        $('#clinical_status_' + tag).html('No clinical trial found');
                    }
                } catch (err) {
                    console.log(err);
                    $('#clinical_status_' + tag).css('display', 'inline-block');
                    $('#clinical_status_' + tag).html('An error has occurred');
                }
                hideLoader();
            }
        });
    } catch (err) {
        console.log(err);
        $('#clinical_status_' + tag).html('An error has occurred');
        $('#clinical_status_' + tag).css('display', 'inline-block');
        hideLoader();
    }
}

function cancelClinicalValidate(tag) {
    $("#cancel_clinical_" + tag).css("display", "none");
    $("#clinicalval_sel_" + tag).css("display", "none");
    $("#clinicalval_input_" + tag).css("display", "inline-block");
    $("#clinicalval_val_" + tag).css("display", "inline-block");
}


function updDataToProject(PRID,DATA_TYPE,DATA_VALUE,STATUS,ID)
{
    var urlv='/JSON/PROJECT/'+PRID+'/NEW_DATA_REVIEW/PARAMS/'+DATA_TYPE+"/"+DATA_VALUE+"/"+STATUS;
    try{
        
            $.ajax({
                type:"GET",
                url: urlv,
        
                error: function() {  $('#status_${TAG}').html('<p>An error has occurred</p>');},
                success: function(result) {
                    if ("status" in result && result.status=="Success"){$("#"+ID).css("background-color","grey");
                if (STATUS==1)  {$("#"+ID+"_up").addClass("thumb_up_gr_sm");$("#"+ID+"_up").removeClass("thumb_up_grey_sm");$("#"+ID+"_down").css("display","none");}
                else  {$("#"+ID+"_down").addClass("thumb_down_red_sm");$("#"+ID+"_down").removeClass("thumb_down_grey_sm");$("#"+ID+"_up").css("display","none");}
                    }
                },
                type: 'GET'
            });///END AJAX
        }catch(err) {
        $('#status_${TAG}').html('<p>An error has occurred</p>');
        }

}
function validateUser(tag)
{try{
	console.log(tag);
	var value=$("#usval_input_"+tag).val();
	console.log(value);

		var urlv='/JSON/USER_VALIDATE/PARAMS/1/'+value;
        console.log(urlv);
		$("#usval_sel_" + tag).empty();
		$.ajax({
			type:"GET",
			url: urlv,
	
			error: function() {  $('#status_${TAG}').html('<p>An error has occurred</p>');},
			success: function(result) {
            	
            var n=0;
            console.log(result);
			$.each(result,function(geneid,dataT){++n;});
			$.each(result,function(geneid,dataT){
				var txt=dataT.FirstName+" "+dataT.LastName;
				
				var option = $('<option></option>').attr("value", dataT.SystemID).text(txt);
				if (n==1)option.attr("selected","selected");
				$("#usval_sel_"+tag).append(option);
			});
			$("#usval_sel_"+tag).css("display","block");
			
			
			$("#usval_input_"+tag).css("display","none");
			$("#usval_val_"+tag).css("display","none");

			},
			type: 'GET'
		});///END AJAX
	}catch(err) {
	$('#status_${TAG}').html('<p>An error has occurred</p>');
	}

}
function showDialog(url)
{
	$.ajax({
		type: 'POST',
		// make sure you respect the same origin policy with this url:
		// http://en.wikipedia.org/wiki/Same_origin_policy
		url: url,
		//data:{ "PER_PAGE":per_page,'PAGE':page},
		success: function(data){
			
			//var v=jQuery.parseJSON(data);
            $("#msg_div").html(data.code);
            if ('title' in data)$("#msg_div").attr('title',data.title);
			$( '#msg_div' ).dialog({width: '70%'})
			
		},
		error:function(data){$("#msg_div").html("An issue occured.");$( '#msg_div' ).dialog({width: '70%'})}
			});///END AJAX
	
}


function loadData(urlv,data_v)
{
	
    try{
	$.ajax({
	   type:"GET",
	   url: urlv,

	   error: function() {  $('#'+div_name).html('<p>An error has occurred</p>');return false;},
	   success: function(data) {
		
        data_v= JSON.parse(data);
        console.log(data);
		},
	   
	});///END AJAX
    data_v= false;
}catch(err) {
}

}



function loadModule(div_name,urlv)
{	
    $('#'+div_name).html('<p>Query running</p>');
    try{
	$.ajax({
	   type:"GET",
	   url: urlv,

	   error: function(e) {          
        $('#'+div_name).html('<p>An error has occurred</p>');return false;
        },
	   success: function(data) {        
		var result=jQuery.parseJSON(data);	        
		$("#"+div_name).html(result.code);
		},
	   
	});///END AJAX
}catch(err) {
$('#'+div_name).html('<p>An error has occurred</p>');
return false;
}
return true;
}



$(document).ready(function(){

	// initialize tooltip
	$( ".helptt span, .helptt div" ).uitooltip({
		track:true,
		delay: {show: 0, hide: 2000},
		placement:'bottom',
		open: function( event, ui ) {
		var id = this.id;
	  
		$.ajax({
		 url:$(this).attr("data-link"),
		 type:'post',
		 success: function(data){
		  var result=jQuery.parseJSON(data);
		 // Setting content option
		 //console.log();
		 $("#"+id).parent().uitooltip('option','content',result.code);
		 
		}
	   });
		}
	  });
	
	$(".helptt span, .helptt div").mouseout(function(){
	  // re-initializing tooltip
	  
	  $(this).attr('title','Please wait...');
	  $(this).uitooltip();
	  $('.ui-tooltip').hide();
	});

	  
}); 
      
function togglePubView(idv,id)
{
    var toggle=$(id).hasClass("plus");
    if (toggle)
    {
        $(id).removeClass("plus");
        $(id).addClass("minus");
            $("#"+idv).css("display",'table-row');
        
    }
    else 
    {
        $(id).removeClass("minus");
        $(id).addClass("plus");
            $("#"+idv).css("display",'none');
        
    }
}
	 

	

function RNAtoDNA(str)
{
return	str.replace(/U/g,'T');
}

function genReverseComplement(str)
{
 var rev='';
 var len=str.length;
 for (i=len;i>=0;--i)
 {
	 if (str.charAt(i)=="T")rev+="A";
	 else if (str.charAt(i)=="A")rev+="T";
	 else if (str.charAt(i)=="U")rev+="A";
	 else if (str.charAt(i)=="G")rev+="C";
	 else if (str.charAt(i)=="C")rev+="G";
 }
 return rev;

}
function getIndicesOf(searchStr, str, caseSensitive) {
    var searchStrLen = searchStr.length;
    if (searchStrLen == 0) {
        return [];
    }
    var startIndex = 0, index, indices = [];
    if (!caseSensitive) {
        str = str.toLowerCase();
        searchStr = searchStr.toLowerCase();
    }
    while ((index = str.indexOf(searchStr, startIndex)) > -1) {
        indices.push(index);
        startIndex = index + searchStrLen;
    }
    return indices;
}

function togglePlusView(id)
		{
			var toggle=$('#'+id).hasClass("plus");
			if (toggle)
			{
				$('#'+id).removeClass("plus");
				$('#'+id).addClass("minus");
					$('#'+id+"_view").css("display",'table-row');
				
			}
			else 
			{
				$('#'+id).removeClass("minus");
				$('#'+id).addClass("plus");
					$('#'+id+"_view").css("display",'none');
				
			}
        }
        function toggleTreeView(id,start,end,level,tree_id)
		{
			
			var toggle=$(id).hasClass("plus");
			if (toggle)
			{
				$(id).removeClass("plus");
				$(id).addClass("minus");
				for (var i=start;i<=end;++i)
				{
					if ($("#"+tree_id+"_"+i).hasClass("LEV"+(level+1)))
					$("#"+tree_id+"_"+i).css("display",'table-row');
				}
			}
			else 
			{
				$(id).removeClass("minus");
				$(id).addClass("plus");
				for (var i=start+1;i<=end;++i)
				{
					$("#"+tree_id+"_"+i).css("display",'none');
				}
			}
        }
        function relocate(path)
        {
            location.assign(path);
        }
function saveAs(uri, filename) {

	var link = document.createElement('a');
	
	if (typeof link.download === 'string') {
	
		link.href = uri;
		link.download = filename;
	
		//Firefox requires the link to be in the body
		document.body.appendChild(link);
	
		//simulate click
		link.click();
	
		//remove the link when done
		document.body.removeChild(link);
	
	} else {
	
		window.open(uri);
	
	}
	}

	function exportToImage(id,fname)
{
    $(".ui-dialog").addClass("w3-hide");
    $(".w3-main").addClass("w3-main-img");
    $("#body_top").addClass("w3-hide");
    $("#navbar").addClass("w3-hide");
    $("nav").addClass("w3-hide");
    $(".w3-container").addClass("w3-hide");
    $(".w3-opts-right").addClass("w3-hide");
    $(".ui-widget-overlay").addClass("w3-hide");
    $("mgt-login").addClass("w3-hide");
    $("#navbar_menu").addClass("w3-hide");
$("#"+id).removeClass("w3-hide");

$("#"+id+" .w3-container").removeClass("w3-hide");
$("#"+id+" div").removeClass("w3-hide");
window.scrollTo(0,0); html2canvas(document.body,
    {
        height: $("#"+id).height()+100,
        windowHeight: $("#"+id).height()+100,
        scrollY: -window.scrollY
    }).then(function(canvas) {
        //document.body.appendChild(canvas);  
        saveAs(canvas.toDataURL(), fname );  
   });
   $("#navbar_menu").removeClass("w3-hide");
   $(".w3-main").removeClass("w3-main-img");
   $("nav").removeClass("w3-hide");
    $("#body_top").removeClass("w3-hide");
    $("#navbar").removeClass("w3-hide");
    $(".w3-container").removeClass("w3-hide");
    $(".w3-opts-right").removeClass("w3-hide");
    $(".ui-dialog").removeClass("w3-hide");
    $(".ui-widget-overlay").removeClass("w3-hide");
    $("mgt-login").removeClass("w3-hide");

}




function getCompoundImage(info,div_name,width=600,parent_height=-1,max_width=600)
{
    console.log(div_name+"|"+width+"|"+parent_height+"|"+max_width);
    var div=$("#"+div_name);
    var data_t='{\
        "structures": [\
            {"structure": "'+info.replaceAll("\\","\\\\")+'"}\
        ],\
        "display":  {\
            "include": ["structureData", "image","logP","elementalAnalysis","hbda"],\
            "parameters": {\
                "structureData": "cdx",\
                "image": {"width": '+width+', "height": '+width+'}\
            }\
        }\
    }\
    ';
    $.ajax({
        url:"",
        type:"POST",
        data:data_t,
        contentType:"application/json",
        dataType:"json",
        
              // make sure you respect the same origin policy with this url:
              // http://en.wikipedia.org/wiki/Same_origin_policy
              
              //data:{ "PER_PAGE":per_page,'PAGE':page},
              success: function(data){
                  
                  div.html('<img src="data:image/png;base64, '+data.data[0].image.image+'" style="width:'+width+'px;max-width:'+max_width+'px" />'); 
                 if(parent_height!=-1)
                 {
                   div.css("top",((parent_height-data.data[0].image.height)/2)+"px");
                 }
                 return data;
      
      
              },
              error: function(xhr){
                console.log('Request Status: ' + xhr.status + ' Status Text: ' + xhr.statusText + ' ' + xhr.responseText);
                }
          
        
      })
}

function makeDistroChart(dataset, xGroup, yValue) {
    /*
     * dataset = the csv file
     * xGroup = the name of the column to group by
     * yValue = the column to use as the values for the chart
     *
     * */

    var chart = {};

    var colorFunct = d3.scale.category10(); //function () {return 'lightgrey';};

    function formatAsFloat(d) {
        if (d % 1 !== 0) {
            return d3.format(".2f")(d);
        } else {
            return d3.format(".0f")(d);
        }

    }

    function logFormatNumber(d) {
        var x = Math.log(d) / Math.log(10) + 1e-6;
        return Math.abs(x - Math.floor(x)) < 0.6 ? formatAsFloat(d) : "";
    }

    chart.yFormatter = formatAsFloat;

    chart.data = dataset;

    //Data management
    chart.xGroup = xGroup;
    chart.yValue = yValue;
    chart.groupObjs = {}; //The data organized by grouping and sorted as well as any metadata for the groups
    chart.objs = {mainDiv: null, chartDiv: null, g: null, xAxis: null, yAxis: null};


    function updateColorFunction(colorOptions) {
        /*
         * Takes either a list of colors, a function or an object with the mapping already in place
         * */
        if (typeof colorOptions == 'function') {
            return colorOptions
        } else if (Array.isArray(colorOptions)) {
            //  If an array is provided, map it to the domain
            var colorMap = {}, cColor = 0;
            for (var cName in chart.groupObjs) {
                colorMap[cName] = colorOptions[cColor];
                cColor = (cColor + 1) % colorOptions.length;
            }
            return function (group) {
                return colorMap[group];
            }
        } else if (typeof colorOptions == 'object') {
            // if an object is provided, assume it maps to  the colors
            return function (group) {
                return colorOptions[group];
            }
        }
    }

    function updateGroupWidth(boxWidth) {
        // Takes the boxWidth size (as percentage of possible width) and returns the actual pixel width to use
        var boxSize = {left: null, right: null, middle: null};
        var width = chart.xScale.rangeBand() * (boxWidth / 100);
        var padding = (chart.xScale.rangeBand() - width) / 2;
        boxSize.middle = chart.xScale.rangeBand() / 2;
        boxSize.left = padding;
        boxSize.right = boxSize.left + width;
        return boxSize;
    }

    function tooltipHover(name, metrics) {
        var tooltipString = "Group: " + name;
        tooltipString += "<br\>Max: " + formatAsFloat(metrics.max, 0.1);
        tooltipString += "<br\>Q3: " + formatAsFloat(metrics.quartile3);
        tooltipString += "<br\>Median: " + formatAsFloat(metrics.median);
        tooltipString += "<br\>Q1: " + formatAsFloat(metrics.quartile1);
        tooltipString += "<br\>Min: " + formatAsFloat(metrics.min);
        return function () {
            chart.objs.tooltip.transition().duration(200).style("opacity", 0.9);
            chart.objs.tooltip.html(tooltipString)
        };
    }

    function prepareData() {
        /*
         * Takes the dataset that is an array of objects and groups the yValues by xGroups and then sorts it
         * Returns the groupObj
         * */

        function calcMetrics(values) {

            var metrics = { //These are the original non–scaled values
                max: null,
                upperOuterFence: null,
                upperInnerFence: null,
                quartile3: null,
                median: null,
                mean: null,
                iqr: null,
                quartile1: null,
                lowerInnerFence: null,
                lowerOuterFence: null,
                min: null
            };

            metrics.min = d3.min(values);
            metrics.quartile1 = d3.quantile(values, 0.25);
            metrics.median = d3.median(values);
            metrics.mean = d3.mean(values);
            metrics.quartile3 = d3.quantile(values, 0.75);
            metrics.max = d3.max(values);
            metrics.iqr = metrics.quartile3 - metrics.quartile1;

            //The inner fences are the closest value to the IQR without going past it (assumes sorted lists)
            var LIF = metrics.quartile1 - (1.5 * metrics.iqr);
            var UIF = metrics.quartile3 + (1.5 * metrics.iqr);
            for (var i = 0; i <= values.length; i++) {
                if (values[i] < LIF) {
                    continue;
                }
                if (!metrics.lowerInnerFence && values[i] >= LIF) {
                    metrics.lowerInnerFence = values[i];
                    continue;
                }
                if (values[i] > UIF) {
                    metrics.upperInnerFence = values[i - 1];
                    break;
                }
            }

            metrics.lowerOuterFence = metrics.quartile1 - (3 * metrics.iqr);
            metrics.upperOuterFence = metrics.quartile3 + (3 * metrics.iqr);
            if (!metrics.lowerInnerFence) {
                metrics.lowerInnerFence = metrics.min;
            }
            if (!metrics.upperInnerFence) {
                metrics.upperInnerFence = metrics.max;
            }
            return metrics
        }

        var current_x = null;
        var current_y = null;
        var current_row;

        //Group the values
        for (current_row = 0; current_row < chart.data.length; current_row++) {
            current_x = chart.data[current_row][chart.xGroup];
            current_y = chart.data[current_row][chart.yValue];
            if (chart.groupObjs.hasOwnProperty(current_x)) {
                chart.groupObjs[current_x].values.push(current_y);
            } else {
                chart.groupObjs[current_x] = {};
                chart.groupObjs[current_x].values = [current_y];
            }
        }

        var cName;
        // Sort them
        for (cName in chart.groupObjs) {
            chart.groupObjs[cName].values.sort(d3.ascending);
            chart.groupObjs[cName].metrics = {};
            chart.groupObjs[cName].metrics = calcMetrics(chart.groupObjs[cName].values);

        }
    }

    prepareData();

    chart.update = function () {

        if (!chart.objs.g) {
            return false;
        }

        // Update chart size
        chart.width = parseInt(chart.objs.chartDiv.style("width"), 10) - (chart.margin.left + chart.margin.right);
        chart.height = parseInt(chart.objs.chartDiv.style("height"), 10) - (chart.margin.top + chart.margin.bottom);
        chart.xScale.rangeBands([0, chart.width]);
        chart.yScale.range([chart.height, 0]);

        //Update axes
        chart.objs.g.select('.x.axis').attr("transform", "translate(0," + chart.height + ")").call(chart.objs.xAxis)
            .selectAll("text")
            .attr("y", 5)
            .attr("x", -5)
            .attr("transform", "rotate(-45)")
            .style("text-anchor", "end");
        chart.objs.g.select('.x.axis .label').attr("x", chart.width / 2);
        chart.objs.g.select('.y.axis').call(chart.objs.yAxis.innerTickSize(-chart.width));
        chart.objs.g.select('.y.axis .label').attr("x", -chart.height / 2);
        chart.objs.chartDiv.select('svg').attr("width", chart.width + (chart.margin.left + chart.margin.right)).attr("height", chart.height + (chart.margin.top + chart.margin.bottom));

        return chart;
    };

    chart.bind = function (selector, chartOptions) {
        
        /*
         * Setup chart and connect it to the correct div
         *
         * Selector is the id to attach the chart to
         * chartOptions = list of chart options
         *   scale = linear (vs log)
         *   chartSize
         *   – chart_width = 800
         *   – chart_height = 400
         *   margin = {top: 15, right: 60, bottom: 30, left: 50};
         *   constrainExtremes True/False, if true max is then the max of the lower fences
         *   axisLabels = Labels for the chart
         *
         */

        //Get base data
        function getBaseData() {
            console.log(chartOptions);
            if (chartOptions && chartOptions.margin) {
                chart.margin = margin;
            } else {
                chart.margin = {top: 15, right: 40, bottom: 200, left: 120};
            }
            if (chartOptions && chartOptions.chartSize) {
                chart.divWidth = chartOptions.chartSize.width;
                chart.divHeight = chartOptions.chartSize.height;
            } else {
                chart.divWidth = 800;
                chart.divHeight = 400;
            }

            chart.width = chart.divWidth - chart.margin.left - chart.margin.right;
            chart.height = chart.divHeight - chart.margin.top - chart.margin.bottom;

            if (chartOptions && chartOptions.axisLabels) {

                chart.xAxisLable = chartOptions.axisLabels.xAxis;
                chart.yAxisLable = chartOptions.axisLabels.yAxis;
            } else {
                chart.xAxisLable = xGroup;
                chart.yAxisLable = yValue;
            }
            if (chartOptions && chartOptions.scale === 'log') {
                console.log("LOG");
                chart.yScale = d3.scale.log();
                chart.yFormatter = logFormatNumber;
            } else {
                console.log("LINEAR");
                chart.yScale = d3.scale.linear();
            }


            if (chartOptions && chartOptions.constrainExtremes === true) {
                var fences = [];
                for (var cName in chart.groupObjs) {
                    fences.push(chart.groupObjs[cName].metrics.lowerInnerFence);
                    fences.push(chart.groupObjs[cName].metrics.upperInnerFence);
                }
                chart.range = d3.extent(fences);

            } else {
                chart.range = d3.extent(chart.data, function (d) {
                    return d[chart.yValue];
                });
            }

            // Take the options colors argument and update the colors function
            if (chartOptions && chartOptions.colors) {
                colorFunct = updateColorFunction(chartOptions.colors);
            }


            chart.yScale.range([chart.height, 0]).domain(chart.range).clamp(true);
            console.log(chart.range);
            // Get x range
            chart.xScale = d3.scale.ordinal().domain(Object.keys(chart.groupObjs)).rangeBands([0, chart.width]);
            //Build Axes
            chart.objs.yAxis = d3.svg.axis()
                .scale(chart.yScale)
                .orient("left")
                .tickFormat(chart.yFormatter)
                .outerTickSize(0)
                .innerTickSize(-chart.width + (chart.margin.right + chart.margin.left));
            chart.objs.xAxis = d3.svg.axis().scale(chart.xScale).orient("bottom").tickSize(5);

        }
console.log("START");
        getBaseData();
        console.log("END");
        chart.objs.mainDiv = d3.select(selector)
            .style("max-width", chart.divWidth + "px");
        // Add all the divs to make it centered and responsive
        chart.objs.mainDiv.append("div")
            .attr("class", "inner-wrapper")
            .style("padding-bottom", (chart.divHeight / chart.divWidth) * 100 + "%")
            .append("div").attr("class", "outer-box")
            .append("div").attr("class", "inner-box");
        // Capture the inner div for the chart (where the chart actually is)
        chart.chartSelector = selector + " .inner-box";
        chart.objs.chartDiv = d3.select(chart.chartSelector);
        d3.select(window).on('resize.' + chart.chartSelector, chart.update);


        // Create the svg
        chart.objs.g = chart.objs.chartDiv.append("svg")
            .attr("class", "chart-area")
            .attr("width", chart.width + (chart.margin.left + chart.margin.right))
            .attr("height", chart.height + (chart.margin.top + chart.margin.bottom))
            .append("g")
            .attr("transform", "translate(" + chart.margin.left + "," + chart.margin.top + ")");

        chart.objs.axes = chart.objs.g.append("g").attr("class", "axis");
        // Show axis
        chart.objs.axes.append("g")
            .attr("class", "x axis")
            .attr("transform", "translate(0," + chart.height + ")")
            .call(chart.objs.xAxis);

        chart.objs.axes.append("g")
            .attr("class", "y axis")
            .call(chart.objs.yAxis)
            .append("text")
            .attr("class", "label")
            .attr("transform", "rotate(-90)")
            .attr("y", -42)
            .attr("x", -chart.height / 2)
            .attr("dy", ".71em")
            .style("text-anchor", "middle")
            .text(chart.yAxisLable);


        //Add the tooltip div
        chart.objs.tooltip = chart.objs.mainDiv.append('div').attr('class', 'tooltip');
        // Add hover tooltip
        for (var cName in chart.groupObjs) {
            //Add mouseover
            chart.groupObjs[cName].g = chart.objs.g.append("g").attr("class", "group");
            chart.groupObjs[cName].g.on("mouseover", function () {
                chart.objs.tooltip.style("display", null).style("left", (d3.event.pageX-200) + "px").style("top", (d3.event.pageY - 28) + "px");
            }).on("mouseout", function () {
                chart.objs.tooltip.style("display", "none");
            }).on("mousemove", tooltipHover(cName, chart.groupObjs[cName].metrics))
        }
        chart.update();

        return chart;
    };

    chart.renderViolinPlot = function (chartOptions) {
        /*
         * Options
         *  - showArea True/False (default True)
         *  - showLine True/False (default True)
         *  - resolution, number of bins
         *  - boxWidth (wider or not)
         */
        chart.violinPlots = {};
        chart.violinPlots.plots = {};
        chart.violinPlots.violinOptions = chartOptions;
        var vOpts = chart.violinPlots.violinOptions;

        // Violin Calculations
        chart.violinPlots.calculateNumBins = function (cGroup) {
            var iqr;
            if (chart.boxPlots) {
                iqr = chart.groupObjs[cGroup].metrics.iqr
            } else {
                var quartile1 = d3.quantile(chart.groupObjs[cGroup].values, 0.25);
                var quartile3 = d3.quantile(chart.groupObjs[cGroup].values, 0.75);
                iqr = quartile3 - quartile1;
            }
            return Math.max(Math.round(2 * (iqr / Math.pow(chart.groupObjs[cGroup].values.length, 1 / 3))), 50)
        };

        function prepareViolin() {
            /*
             * Takes the structured data and calculates the box plot numbers
             * */

            var cName;
            for (cName in chart.groupObjs) {
                chart.groupObjs[cName].violin = {};
                chart.groupObjs[cName].violin.objs = {};
                chart.groupObjs[cName].violin.histogramFunct = d3.layout.histogram().frequency(1);
            }

        }

        prepareViolin();

        chart.violinPlots.change = function (updateOptions) {
            /*
            * Same options as on renderViolin
             */
            if (updateOptions) {
                for (var key in updateOptions) {
                    vOpts[key] = updateOptions[key]
                }
            }

            mapObjects(true);
            chart.violinPlots.update()
        };


        chart.violinPlots.update = function () {
            var cName, cViolinPlot;

            for (cName in chart.groupObjs) {
                cViolinPlot = chart.groupObjs[cName].violin;

                if (vOpts && vOpts.resolution) {
                    cViolinPlot.histogramFunct.bins(vOpts.resolution);
                } else {
                    cViolinPlot.histogramFunct.bins(chart.violinPlots.calculateNumBins(cName));
                }
                cViolinPlot.histogramData = cViolinPlot.histogramFunct(chart.groupObjs[cName].values);

                // Get the box size
                var groupWidth = {left: null, right: null, middle: null};
                if (vOpts && vOpts.violinWidth) {
                    groupWidth = updateGroupWidth(vOpts.violinWidth)
                } else {
                    groupWidth = updateGroupWidth(100)
                }

                var leftBound = chart.xScale(cName) + groupWidth.left;
                var rightBound = chart.xScale(cName) + groupWidth.right;
                var width = (rightBound - leftBound) / 2;

                var xV = chart.yScale.copy();
                var yV = d3.scale.linear()
                    .range([width, 0])
                    .domain([0, 0.5+Math.max(chart.range[1], d3.max(cViolinPlot.histogramData, function (d) {
                        return d.y;
                    }))])
                    .clamp(true);

                var area = d3.svg.area()
                    .interpolate('basis')
                    .x(function (d) {
                        return xV(d.x);
                    })
                    .y0(width)
                    .y1(function (d) {
                        return yV(d.y);
                    });

                var line = d3.svg.line()
                    .interpolate('basis')
                    .x(function (d) {
                        return xV(d.x);
                    })
                    .y(function (d) {
                        return yV(d.y);
                    });

                if (cViolinPlot.objs.left.area) {
                    cViolinPlot.objs.left.area
                        .datum(cViolinPlot.histogramData)
                        .attr("d", area);
                }

                if (cViolinPlot.objs.left.line) {
                    cViolinPlot.objs.left.line
                        .datum(cViolinPlot.histogramData)
                        .attr("d", line);
                }

                if (cViolinPlot.objs.right.area) {
                    cViolinPlot.objs.right.area
                        .datum(cViolinPlot.histogramData)
                        .attr("d", area);
                }
                if (cViolinPlot.objs.right.line) {
                    cViolinPlot.objs.right.line
                        .datum(cViolinPlot.histogramData)
                        .attr("d", line);
                }

                cViolinPlot.objs.left.g.attr("transform", "rotate(90,0,0)   translate(0,-" + leftBound + ")  scale(1,-1)");
                cViolinPlot.objs.right.g.attr("transform", "rotate(90,0,0)  translate(0,-" + rightBound + ")");
            }
        };

        function mapObjects(clear) {

            var cName, cViolinPlot;

            if (vOpts && vOpts.colors) {
                chart.violinPlots.color = updateColorFunction(vOpts.colors);
            } else {
                chart.violinPlots.color = colorFunct
            }

            for (cName in chart.groupObjs) {
                cViolinPlot = chart.groupObjs[cName].violin;

                if (clear) {
                    cViolinPlot.objs.g.remove()
                }

                cViolinPlot.objs.g = chart.groupObjs[cName].g.append("g").attr("class", "violin-plot");
                cViolinPlot.objs.left = {area: null, line: null, g: null};
                cViolinPlot.objs.right = {area: null, line: null, g: null};

                cViolinPlot.objs.left.g = cViolinPlot.objs.g.append("g");
                cViolinPlot.objs.right.g = cViolinPlot.objs.g.append("g");

                if (!vOpts || (vOpts && vOpts.showArea !== false)) {
                    cViolinPlot.objs.left.area = cViolinPlot.objs.left.g.append("path")
                        .attr("class", "area")
                        .style("fill", chart.violinPlots.color(cName));
                    cViolinPlot.objs.right.area = cViolinPlot.objs.right.g.append("path")
                        .attr("class", "area")
                        .style("fill", chart.violinPlots.color(cName));
                }

                if (!vOpts || (vOpts && vOpts.showLine !== false)) {
                    cViolinPlot.objs.left.line = cViolinPlot.objs.left.g.append("path")
                        .attr("class", "line")
                        .attr("fill", 'none')
                        .style("stroke", chart.violinPlots.color(cName));
                    cViolinPlot.objs.right.line = cViolinPlot.objs.right.g.append("path")
                        .attr("class", "line")
                        .attr("fill", 'none')
                        .style("stroke", chart.violinPlots.color(cName));
                }
            }

        }

        mapObjects();

        d3.select(window).on('resize.' + chart.chartSelector + '.violinPlot', chart.violinPlots.update);
        //Update the divs with the proper values
        chart.violinPlots.update();
        return chart.violinPlots;
    };

    chart.renderBoxPlot = function (chartOptions) {
        chart.boxPlots = {};
        chart.boxPlots.chartOptions = chartOptions;
        var bOpts = chart.boxPlots.chartOptions;

        /*
         * options:
         *   showOutliers: True/False (default True) - this shouldn't  affect the min/max
         *   showWhiskers: True/False (default True)
         *   whiskersRatio: (default standard=iqr*1.5), other options, minmax, (future?: std)
         *   showBox: True/False (default True)
         *   showMedian: True/False  (default True)
         *   showMean: True/False (default False)
         *   outlierScatter: True/False (default False) (not fully implimented)
         *   boxWidth (not implimented) what percent of the bin should the box take up
         */

        //Create boxPlots
        for (var cName in chart.groupObjs) {
            chart.groupObjs[cName].boxPlot = {};
            chart.groupObjs[cName].boxPlot.objs = {};
        }
        function calcOutliers(obj, values, metrics) {
            /*
             * Create lists of the outliers for each content group
             */

            var cExtremes = [];
            var cOutliers = [];
            var cOut, idx;
            for (idx = 0; idx <= values.length; idx++) {
                cOut = {value: values[idx]};

                if (cOut.value < metrics.lowerInnerFence) {
                    if (cOut.value < metrics.lowerOuterFence) {
                        cExtremes.push(cOut);
                    } else {
                        cOutliers.push(cOut);
                    }
                } else if (cOut.value > metrics.upperInnerFence) {
                    if (cOut.value > metrics.upperOuterFence) {
                        cExtremes.push(cOut);
                    } else {
                        cOutliers.push(cOut);
                    }
                }
            }
            obj.outliers = cOutliers;
            obj.extremes = cExtremes;
        }

        function calcAllOutliers() {
            if (!bOpts || (bOpts && bOpts.showOutliers !== false)) {
                for (var cName in chart.groupObjs) {
                    calcOutliers(chart.groupObjs[cName].boxPlot.objs, chart.groupObjs[cName].values, chart.groupObjs[cName].metrics);
                }
            }
        }

        calcAllOutliers();

        chart.boxPlots.change = function (updateOptions) {
            if (updateOptions) {
                for (var key in updateOptions) {
                    bOpts[key] = updateOptions[key]
                }
            }
            mapObjects(true);
			chart.boxPlots.update()

        };

        chart.boxPlots.update = function () {
            var cName, cBoxPlot;

            for (cName in chart.groupObjs) {
                cBoxPlot = chart.groupObjs[cName].boxPlot;

                // Get the box size
                var groupWidth = {left: null, right: null, middle: null};
                if (bOpts && bOpts.boxWidth) {
                    groupWidth = updateGroupWidth(bOpts.boxWidth)
                } else {
                    groupWidth = updateGroupWidth(30)
                }
                var leftBound = chart.xScale(cName) + groupWidth.left;
                var rightBound = chart.xScale(cName) + groupWidth.right;
                var middle = chart.xScale(cName) + groupWidth.middle;

                var sMetrics = {}; //temp var for scaled (plottable) metric values
                for (var attr in chart.groupObjs[cName].metrics) {
                    sMetrics[attr] = null;
                    sMetrics[attr] = chart.yScale(chart.groupObjs[cName].metrics[attr]);
                }

                //// Box
                if (cBoxPlot.objs.box) {
                    cBoxPlot.objs.box
                        .attr("x", leftBound)
                        .attr('width', rightBound - leftBound)
                        .attr("y", sMetrics.quartile3)
                        .attr("rx", 1)
                        .attr("ry", 1)
                        .attr("height", -sMetrics.quartile3 + sMetrics.quartile1)
                }
                //// Lines
                if (cBoxPlot.objs.upperWhisker) {
                    cBoxPlot.objs.upperWhisker.fence
                        .attr("x1", leftBound)
                        .attr("x2", rightBound)
                        .attr('y1', sMetrics.upperInnerFence)
                        .attr("y2", sMetrics.upperInnerFence);
                    cBoxPlot.objs.upperWhisker.line
                        .attr("x1", middle)
                        .attr("x2", middle)
                        .attr('y1', sMetrics.quartile3)
                        .attr("y2", sMetrics.upperInnerFence);

                    cBoxPlot.objs.lowerWhisker.fence
                        .attr("x1", leftBound)
                        .attr("x2", rightBound)
                        .attr('y1', sMetrics.lowerInnerFence)
                        .attr("y2", sMetrics.lowerInnerFence);
                    cBoxPlot.objs.lowerWhisker.line
                        .attr("x1", middle)
                        .attr("x2", middle)
                        .attr('y1', sMetrics.quartile1)
                        .attr("y2", sMetrics.lowerInnerFence);
                }
                //// Median
                if (cBoxPlot.objs.median) {
                    cBoxPlot.objs.median.line
                        .attr("x1", leftBound)
                        .attr("x2", rightBound)
                        .attr('y1', sMetrics.median)
                        .attr("y2", sMetrics.median);
                    cBoxPlot.objs.median.circle
                        .attr("cx", middle)
                        .attr("cy", sMetrics.median)
                }

                //// Mean
                if (cBoxPlot.objs.mean) {
                    cBoxPlot.objs.mean.line
                        .attr("x1", leftBound)
                        .attr("x2", rightBound)
                        .attr('y1', sMetrics.mean)
                        .attr("y2", sMetrics.mean);
                    cBoxPlot.objs.mean.circle
                        .attr("cx", middle)
                        .attr("cy", sMetrics.mean);
                }
                //// Outliers
                var pt;
                if (cBoxPlot.objs.outliers) {
                    for (pt in cBoxPlot.objs.outliers) {
                        cBoxPlot.objs.outliers[pt].point
                            .attr("cx", middle/*+scatter()*/)
                            .attr("cy", chart.yScale(cBoxPlot.objs.outliers[pt].value));
                    }
                }
                if (cBoxPlot.objs.extremes) {
                    for (pt in cBoxPlot.objs.extremes) {
                        cBoxPlot.objs.extremes[pt].point
                            .attr("cx", middle/*+scatter()*/)
                            .attr("cy", chart.yScale(cBoxPlot.objs.extremes[pt].value));
                    }
                }
            }
        };

        function mapObjects(clear) {
            // Map everything to divs
            var cName, cBoxPlot;

            if (bOpts && bOpts.colors) {
                chart.boxPlots.colorFunct = updateColorFunction(bOpts.colors);
            } else {
                chart.boxPlots.colorFunct = colorFunct
            }

            for (cName in  chart.groupObjs) {
                cBoxPlot = chart.groupObjs[cName].boxPlot;

                if (clear) {
                    cBoxPlot.objs.g.remove()
                }

                cBoxPlot.objs.g = chart.groupObjs[cName].g.append("g").attr("class", "box-plot");

                //Plot Box (default show)
                if (!bOpts || (bOpts && bOpts.showBox !== false)) {
                    cBoxPlot.objs.box = cBoxPlot.objs.g.append("rect")
                        .attr("class", "box")
                        .style("fill", chart.boxPlots.colorFunct(cName));
                }

                //Plot Median (default show)
                if (!bOpts || (bOpts && bOpts.showMedian !== false)) {
                    cBoxPlot.objs.median = {line: null, circle: null};
                    cBoxPlot.objs.median.line = cBoxPlot.objs.g.append("line")
                        .attr("class", "median");
                    cBoxPlot.objs.median.circle = cBoxPlot.objs.g.append("circle")
                        .attr("class", "median")
                        .attr('r', 3)
                        .style("fill", chart.boxPlots.colorFunct(cName));
                }

                // Plot Mean (default no plot)
                if (bOpts && bOpts.showMean) {
                    cBoxPlot.objs.mean = {line: null, circle: null};
                    cBoxPlot.objs.mean.line = cBoxPlot.objs.g.append("line")
                        .attr("class", "mean");
                    cBoxPlot.objs.mean.circle = cBoxPlot.objs.g.append("circle")
                        .attr("class", "mean")
                        .attr('r', 3)
                        .style("fill", chart.boxPlots.colorFunct(cName));
                }

                //Plot Whiskers (default show)
                if (!bOpts || (bOpts && bOpts.showWhiskers !== false)) {
                    cBoxPlot.objs.upperWhisker = {fence: null, line: null};
                    cBoxPlot.objs.lowerWhisker = {fence: null, line: null};
                    cBoxPlot.objs.upperWhisker.fence = cBoxPlot.objs.g.append("line")
                        .attr("class", "upper whisker")
                        .style("stroke", chart.boxPlots.colorFunct(cName));
                    cBoxPlot.objs.upperWhisker.line = cBoxPlot.objs.g.append("line")
                        .attr("class", "upper whisker")
                        .style("stroke", chart.boxPlots.colorFunct(cName));

                    cBoxPlot.objs.lowerWhisker.fence = cBoxPlot.objs.g.append("line")
                        .attr("class", "lower whisker")
                        .style("stroke", chart.boxPlots.colorFunct(cName));
                    cBoxPlot.objs.lowerWhisker.line = cBoxPlot.objs.g.append("line")
                        .attr("class", "lower whisker")
                        .style("stroke", chart.boxPlots.colorFunct(cName));
                }

                // Plot outliers (default show)
                //var scatter = function() {
                //    var range = chartObj.xScale.rangeBand()/3;
                //    return Math.floor(Math.random() * range)-range/2;
                //}
                if (!bOpts || (bOpts && bOpts.showOutliers !== false)) {
                    if (!cBoxPlot.objs.outliers) calcAllOutliers();
                    var pt;
                    if (cBoxPlot.objs.outliers.length) {
                        var outDiv = cBoxPlot.objs.g.append("g").attr("class", "boxplot outliers");
                        for (pt in cBoxPlot.objs.outliers) {
                            cBoxPlot.objs.outliers[pt].point = outDiv.append("circle")
                                .attr("class", "outlier")
                                .attr('r', 2)
                                .style("fill", chart.boxPlots.colorFunct(cName));
                        }
                    }

                    if (cBoxPlot.objs.extremes.length) {
                        var extDiv = cBoxPlot.objs.g.append("g").attr("class", "boxplot extremes");
                        for (pt in cBoxPlot.objs.extremes) {
                            cBoxPlot.objs.extremes[pt].point = extDiv.append("circle")
                                .attr("class", "extreme")
                                .attr('r', 2)
                                .style("stroke", chart.boxPlots.colorFunct(cName));
                        }
                    }
                }


            }
        }

        mapObjects();

        d3.select(window).on('resize.' + chart.chartSelector + '.boxPlot', chart.boxPlots.update);
        //Update the divs with the proper values
        chart.boxPlots.update();

        return chart.boxPlots;

    };

    return chart;
}


$('#start_form').bind("submit",function(){
    submitWelcomeSearch();
    return false;
});
function changeHeadSearchPlace(div)
{
   console.log(div+" "+$(div).val());
   $("#news_search_help").css("display","none");
    if ($(div).val()=="GENOMIC_SEARCH/GENE_ANY"){$("#search_input").attr("placeholder", "Gene ID: 1017 ; Ensembl ID: ENSG00000123374.11 / ENST00000266970.9 ; RefSeq: NM_001798.5; Symbol: CDK2 ; Name: Cyclin Dependent kinase 2 (slower)");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="GENOMIC_SEARCH/GENE_SYMBOL"){$("#search_input").attr("placeholder", " CDK2 ; CDK4");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="GENOMIC_SEARCH/GENE_NAME"){$("#search_input").attr("placeholder", " cyclin-dependent kinase 2");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="GENOMIC_SEARCH/GENE_NCBI"){$("#search_input").attr("placeholder", "NCBI Gene ID: 1017");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="GENOMIC_SEARCH/GENE_ENS"){$("#search_input").attr("placeholder", " Ensembl ID: ENSG00000123374.11");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="GENOMIC_SEARCH/GENE_TR"){$("#search_input").attr("placeholder", " Ensembl ID: ENST00000266970.9 ; RefSeq: NM_001798.5");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="GENOMIC_SEARCH/SNP"){$("#search_input").attr("placeholder", " rs699");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="PROTEIN_SEARCH/PROT_ANY"){$("#search_input").attr("placeholder", "CDK2_HUMAN ; P24941 ; cyclin-dependent kinase 2 ; Protein kinase ");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="PROTEIN_SEARCH/PROT_NAME"){$("#search_input").attr("placeholder", "cyclin-dependent kinase 2");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="PROTEIN_SEARCH/PROT_ID"){$("#search_input").attr("placeholder", "CDK2_HUMAN ");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="PROTEIN_SEARCH/PROT_AC"){$("#search_input").attr("placeholder", " P24941");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="PROTEIN_SEARCH/PROT_SEQ"){$("#search_input").attr("placeholder", "P24941-1 ; Isoform of P24941");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="PROTEIN_SEARCH/PROT_DOM"){$("#search_input").attr("placeholder", "Protein kinase");$("#search_human").removeAttr("disabled");}

    else if ($(div).val()=="PW_REAC"){$("#search_input").attr("placeholder", "S Phase"); $("#search_human").attr("disabled", true);}

    else if ($(div).val()=="PW_REAC_ID"){$("#search_input").attr("placeholder", "R-HSA-69242"); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="PUBLICATION_SEARCH/PUBLI_ANY"){$("#search_input").attr("placeholder", "Title: Higher order genomic organization ; Author: Ghule ; DOI: 10.1002/jcp.26741  ; Institution:  Departments of Orthopedic Surgery ; PubmedID: 29744889 "); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="PUBLICATION_SEARCH/PUBLI_TITLE"){$("#search_input").attr("placeholder", " Higher order genomic organization "); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="PUBLICATION_SEARCH/PUBLI_AUTHOR"){$("#search_input").attr("placeholder", "Format: LAST_NAME, FIRST_NAME ; Example: Desaphy, Jeremy"); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="PUBLICATION_SEARCH/PUBLI_INSTIT"){$("#search_input").attr("placeholder", "Departments of Orthopedic Surgery and Biochemistry & Molecular Biology, Mayo Clinic, Rochester, Minnesota."); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="PUBLICATION_SEARCH/PUBLI_PMID"){$("#search_input").attr("placeholder", "29744889"); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="PUBLICATION_SEARCH/PUBLI_DOI"){$("#search_input").attr("placeholder", "10.1002/jcp.26741"); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="PUBLICATION_SEARCH/PUBLI_ORCID"){$("#search_input").attr("placeholder", "0000-0002-6860-0825"); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="PUBLICATION_SEARCH/PUBLI_PUBMED"){$("#search_input").attr("placeholder", "Perform a pubmed search, with the added BioRels functionalities"); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="DISEASE_SEARCH/DISEASE_ANY"){$("#search_input").attr("placeholder", "diabetes mellitus ; MONDO_0005015"); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="DISEASE_SEARCH/DISEASE_ID"){$("#search_input").attr("placeholder", "MONDO_0005015"); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="DISEASE_SEARCH/DISEASE_NAME"){$("#search_input").attr("placeholder", "diabetes mellitus"); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="COMPOUND_SEARCH/COMPOUND_ANY"){$("#search_input").attr("placeholder", "CHEMBL6852 ; SCHEMBL12346;"); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="COMPOUND_SEARCH/COMPOUND_NAME"){$("#search_input").attr("placeholder", "Omeprazole ; acetaminophen ;  "); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="COMPOUND_SEARCH/COMPOUND_SMILES"){$("#search_input").attr("placeholder", "O=S(c1[nH]c2c([n]1)cc(cc2)OC)Cc1[n]cc(c(c1C)OC)C - (Exact search only)"); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="COMPOUND_SEARCH/COMPOUND_INCHI"){$("#search_input").attr("placeholder", "InChI=1S/C12H11NO4/c1-2-17-12(16)10-6-8-5-7(11(14)15)3-4-9(8)13-10/h3-6,13H,2H2,1H3,(H,14,15) ; CAVYPAYXEMVXMS-UHFFFAOYSA-N"); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="TISSUE_SEARCH/TISSUE_ANY"){$("#search_input").attr("placeholder", "Liver ; islet of Langerhans ; UBERON_0000006 "); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="TISSUE_SEARCH/TISSUE_NAME"){$("#search_input").attr("placeholder", "Liver ; islet of Langerhans  "); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="TISSUE_SEARCH/TISSUE_ID"){$("#search_input").attr("placeholder", " UBERON_0000006 "); $("#search_human").attr("disabled", true);}
    else if ($(div).val()=="TISSUE_SEARCH/CELL_LINE"){$("#search_input").attr("placeholder", "CVCL_B0T9 ; #132 PC3-1-SC-E8 ; Hybridoma ");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="ASSAY_SEARCH/ASSAY_ANY"){$("#search_input").attr("placeholder", "Selectivity for CDK4 over CDK2 ; CHEMBL1002022");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="ASSAY_SEARCH/ASSAY_ANY"){$("#search_input").attr("placeholder", "Selectivity for CDK4 over CDK2 ; CHEMBL1002022");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="NEWS_SEARCH/NEWS_COMPLEX"){$("#search_input").attr("placeholder", "TYPE = VALUE AND ...  TYPE can be GENE, DISEASE, DRUG, CLINICAL_TRIAL, COMPANY, TITLE");$("#search_human").removeAttr("disabled");$("#news_search_help").css("display","block");}
    else if ($(div).val()=="CLINICAL_SEARCH/CLINICAL_ANY"){$("#search_input").attr("placeholder", "NCT06005792 ; Randomized double-blind dose study of XmAb27564 with plaque psoriasis ");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="CLINICAL_SEARCH/CLINICAL_ID"){$("#search_input").attr("placeholder", "NCT06005792");$("#search_human").removeAttr("disabled");}
    else if ($(div).val()=="CLINICAL_SEARCH/CLINICAL_TITLE"){$("#search_input").attr("placeholder", " Randomized double-blind dose study of XmAb27564 with plaque psoriasis ");$("#search_human").removeAttr("disabled");}

}

function showMenu(menu_name,id,max)
		{
            for (i=1;i<=max;++i)
            {
            $("#"+menu_name+"_tab_"+i).removeClass("active");
            $("#"+menu_name+"_view_"+i).css("display","none");
            }
			$("#"+menu_name+"_tab_"+id).addClass("active");
            $("#"+menu_name+"_view_"+id).css("display","block");		    
        }

// Toggle between showing and hiding the sidebar, and add overlay effect
function w3_open() {
  if ($("#navbar").css("display") === 'block') {
    $("#navbar").css("display","none");
    $("#myOverlay").css("display","none");
  } else {
    $("#navbar").css("display","block")
    $("#myOverlay").css("display","block");
  }
}

function loadViewInTable(line,path)
	{
		$.ajax({
			type: 'POST',
			// make sure you respect the same origin policy with this url:
			// http://en.wikipedia.org/wiki/Same_origin_policy
			url: path,
			data:{ "WIDTH":$(line).css("width")},
			success: function(data){
				
				var v=jQuery.parseJSON(data);
				console.log(v);
				$("#alignment").remove();
				$('<tr id="alignment"><td colspan="9">'+v.code+'</td></tr>').insertAfter(line);
			},
			error:function(data){$("#info").html("An issue occured.");}
		});///END AJAX
	}

// Close the sidebar with the close button
function w3_close() {
    $("#navbar").css("display","none");
    $("#myOverlay").css("display","none");
}

var AA_3_1={"ALA":"A","GLY":"G","ILE":"I","LEU":"L","PRO":"P","VAL":"V","PHE":"F","TRP":"W","TYR":"Y","ASP":"D","GLU":"E","ARG":"R","HIS":"H","LYS":"K","SER":"S","THR":"T","CYS":"C","MET":"M","ASN":"N","GLN":"Q"};
var DOM_TYPE_IMG={"CHAIN":["C","#a5a5a5"],"REPEAT":["R","#ed7d31"],"DOMAIN":["D","#70ad47"],"REGION":["G","#843c0c"]};



var catgroups=["#e6194b",
"#3cb44b",
"#4363d8",
"#f58231",
"#911eb4",
"#9a6324",
"#800000",
"#000075",
"#808000",
"#fabed4"
];

var hexgroups=["#ff0000",
"#fe4400",
"#f86600",
"#ee8200",
"#df9b00",
"#cdb200",
"#b6c700",
"#98db00",
"#6fed00",
"#00ff00"
];



function runTranscriptSearch(info)
{
    seq='';
    n_query=0;n_found=0;
    text_info='';
    $.each(info.SEQUENCE.SEQUENCE, function(index,value){if (value['NUCL']!='')seq+=value['NUCL'];else seq+=' ';});
    if ($("#trans_seq_search").length==0)return true;
    console.log("SEARCH");
    console.log(info);
	search=$("#trans_seq_search").val().toUpperCase().split("\n");
	indices=[];
	def_level=$("#search_format").children("option:selected").val();
	

    delim='';
    if ($("#search_delimited").children("option:selected").val()=='tab')delim="\t";
    else if ($("#search_delimited").children("option:selected").val()=='comma')delim=",";
    else if ($("#search_delimited").children("option:selected").val()=='semicolon')delim=";";
    else if ($("#search_delimited").children("option:selected").val()=='space')delim=" ";


	
	
	$.each(search, function(index,value){
		if(value=='')return true;
		is_cat=false;
        tab_spl=value.split(delim);
        ++n_query;
        if (def_level==1)
        {
            seq_to_test=value;
        }
        else if (def_level==2)
        {
            seq_to_test=tab_spl[0];
        }
		else if (def_level==3)
		{
            seq_to_test=tab_spl[0];
			tag=tab_spl[2];
			var reg = /^\d*\.?\d+$/;
			console.log("VALUE:"+value);
			console.log("REGEX:"+tag+" "+reg.test(tag));
			if(!reg.test(tag))	is_cat=true;
		}
        else if (def_level==4)
        {
           
            indices.push([-1,index,-1,-1]);
            text_info+='<li>'+value+"</li>";
            return true; 
           

        }
        seq_to_test=RNAtoDNA(seq_to_test);
            
        str_n=['A','T','C','G']
        
        len_str=seq_to_test.length;
        var found=false;
        var t=getIndicesOf(seq_to_test,seq.replace(" ",""));
        if (t.length>0) {found=true;indices.push([t,index,true,0]);}
        
        t=getIndicesOf(genReverseComplement(seq_to_test),seq.replace(" ",""));
        if (t.length>0){found=true;indices.push([t,index,false,0]);}
        console.log(seq_to_test+" FOUND: "+found);
        if (!found)
        {
            console.log("RUN 1 mm:"+seq_to_test);
        for (i_pos=0;i_pos<len_str;++i_pos)
            {
                $.each(str_n,function(index_dummy,nucl){
                    if (seq_to_test.substr(i_pos,1)==nucl)return true;
                    new_l=seq_to_test.substr(0,i_pos)+nucl+seq_to_test.substr(i_pos+1);
                    console.log("\t"+seq_to_test+"\t"+i_pos+"\t"+new_l);
                    var t=getIndicesOf(new_l,seq.replace(" ",""));
                    if (t.length>0){found=true; indices.push([t,index,true,1]);}
                    
                    t=getIndicesOf(genReverseComplement(new_l),seq.replace(" ",""));
                    if (t.length>0){found=true;indices.push([t,index,false,1]);}
                });
            }
        }
        if (found==false)
        {
            indices.push([-1,index,-1,-1]);
            text_info+='<li>'+value+"</li>";
        }
        else
        {
            n_found++;
        }

        
		
		
		
	});
    str_info=n_found+'/'+n_query+' found';
    console.log(text_info);
    if (text_info!='')
    {
        str_info+='<br/>List of queries not found:<ul>'+text_info+"</ul>";
    }
    $("#text_info").html(str_info);
	var n_cat=-1;
	if (is_cat)
	{
		
		$.each(search, function(index,value){
			tag=value.split("\t")[2];
			if (tag==undefined)return true;
			if (list_cat.indexOf(tag)==-1) { list_cat.push(tag);}
		});
		
	}
    
	
}

function genTranscriptSimpleView(view,info)
{
	runTranscriptSearch(info);
	
	
console.log("Search simple view");
console.log("Search list:");
console.log(search);
	
	
	console.log("DEF LEVEL:"+def_level);
	var len=seq.length;
	var str='<div>Simplified view:</div><div style="height:16px"><div style="position:relative">';var sum=0;
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
			str+='exon_even_sim';
			str+="' style='font-size: 0.7em;font-style: italic;position:absolute;display: inline-table;left:"+left+"%;width:"+width+"%;'></div>";
			is_first=false;
	});
	//console.log(sum);
	str+='</div></div>'; str+='<div style="height:16px"><div style="position:relative;">';
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
	str+='</div></div>';
	
		console.log(indices);
		if (def_level<=2)
		{
			if (def_level>=3)str+='<div style="width:100%;text-align:center;font-size:0.6em;font-style:italic">Top 10%</div>';
	str+='<div style="height:16px;position:relative;">';
	$.each(indices,function(index,info_res)
	{
        if (info_res[0]==-1)return true;
		var index_search=info_res[1];
		var sense=info_res[2];
		console.log("INDEX SEARCH:"+index_search+" -> SEQ: "+search[index_search]+" IS SENSE:"+sense);
		$.each(info_res[0], function(index_s,pos)
		{
			console.log("POSITION:"+pos);
			var left=parseFloat((pos)*100/len).toFixed(2);
			var width=Math.max(parseFloat((search[index_search].length/len)).toFixed(2),2);
			var name="search_"+index_search+"_"+index;
						var entry="entry_"+index_search+"_"+pos;
			str+="<div  id='"+name+"' onclick='scrollToAnchor(\""+entry+"\")'  class='exon_odd' style='background-color:"+((sense==true)?"grey":"green")+";position:absolute;display: inline-table;left:"+left+"%;width:"+width+"px; '></div>";
		});
					//	console.log (index+" "+search[index]+" "+list_search);
	});
	}
	else
	{
		var hash={};

        delim='';
        if ($("#search_delimited").children("option:selected").val()=='tab')delim="\t";
        else if ($("#search_delimited").children("option:selected").val()=='comma')delim=",";
        else if ($("#search_delimited").children("option:selected").val()=='semicolon')delim=";";
        else if ($("#search_delimited").children("option:selected").val()=='space')delim=" ";
    
        def_level=$("#search_format").children("option:selected").val();
		$.each(search, function(index,value){
            console.log("TEST:"+index+"  "+value);
			var tabs=value.split(delim);
			
            if (def_level==3)
            {
			var val=parseFloat(tabs[2]);
			if (!(val in hash)){hash[val]=[index];}
			else hash[val].push(index);
            }
            else if (def_level==4)
            {
                var val=parseFloat(tabs[1]);
                if (!(val in hash)){hash[val]=[index];}
                else hash[val].push(index);
            }
		});
		console.log("hash");
		console.log(hash);
	
		var order=Object.keys(hash).sort(function(a, b) {
            return a - b;
          }).reverse();
		var size=search.length;
		var top10=Math.ceil(size/10);
		var top20=Math.ceil(size/5);
		
		var n=0,nblock=0;;
		console.log("ORDER:");
		console.log(order);
		if (!is_cat)
		{
			if (def_level>=3)str+='<div style="width:100%;text-align:center;font-size:0.6em;font-style:italic">Top 10%</div>';
	str+='<div style="height:16px;position:relative;">';
		$.each(order,function(index_order,potency_value){
			console.log("POTENCY : "+potency_value)
			$.each(hash[potency_value],function(index_hash,search_index){
				$.each(indices,function(index,info_res)
				{
                    if (info_res[0]==-1)return true;
					var index_search=info_res[1];
					if (index_search!=search_index)return true;
					
					var sense=info_res[2];
					console.log("INDEX SEARCH:"+index_search+" -> SEQ: "+search[index_search]+" IS SENSE:"+sense);
					$.each(info_res[0], function(index_s,pos)
					{
						console.log("POSITION:"+pos);
						var left=parseFloat((pos)*100/len).toFixed(2);
						var width=Math.max(parseFloat((search[index_search].length/len)).toFixed(2),2);
						var color;
						if (is_cat)
						{
							color=catgroups[list_cat.indexOf(potency_value)];
						}
						else color=hexgroups[Math.floor(potency_value/10)];
						var name="search_"+index_search+"_"+index;
						var entry="entry_"+index_search+"_"+pos;
						str+="<div id='"+name+"' onclick='scrollToAnchor(\""+entry+"\")' class='exon_odd' data-value='"+potency_value+"' style='cursor:pointer;background-color:"+color+";position:absolute;display: inline-table;left:"+left+"%;width:"+width+"px; '></div>";
						

					});
					++n;
				});
				if (n>=top10 && nblock==0)
				{
					str+='</div><div style="width:100%;text-align:center;font-size:0.6em;font-style:italic">Top 10-20%</div><div style="height:16px;position:relative;">';++nblock;
				}
				if (n>=top20 && nblock==1)
				{
					str+='</div><div style="width:100%;text-align:center;font-size:0.6em;font-style:italic">Others</div><div style="height:16px;position:relative;">';++nblock;
				}
				
			});
			
		});
		}
		else
		{
			$.each(list_cat,function(index,value){
			if (value==undefined)return true;
			str+='<div style="width:100%;text-align:center;font-size:0.6em;font-style:italic">'+value+'</div><div style="height:16px;position:relative;">';
			console.log("QC:"+value+"-"+index);
				$.each(indices,function(index_p,info_res)
				{
                    if (info_res[0]==-1)return true;
					var index_search=info_res[1];
					
					var tabs=search[index_search].split("\t");
					
					if (tabs[2]!=value)return true;
					console.log(tabs);
					console.log(index_search);
					$.each(info_res[0], function(index_s,pos)
					{
						console.log("POSITION:"+pos);
						var left=parseFloat((pos)*100/len).toFixed(2);
						var width=Math.max(parseFloat((search[index_search].length/len)).toFixed(2),2);
						var color;
							color=catgroups[index];
						
						var name="search_"+index_search+"_"+index;
						var entry="entry_"+index_search+"_"+pos;
						str+="<div id='"+name+"' onclick='scrollToAnchor(\""+entry+"\")' class='exon_odd' data-value='"+value
						+"' style='cursor:pointer;background-color:"+color+";position:absolute;display: inline-table;left:"+left+"%;width:"+width+"px; '></div>";
						

					});
					++n;
				});
				str+='</div></div>';
				
			
			
		});
		}

		// $.each(indices,function(index,info_res)
		// {
		// 	var index_search=info_res[1];
		// 	var sense=info_res[2];
		// 	console.log("INDEX SEARCH:"+index_search+" -> SEQ: "+search[index_search]+" IS SENSE:"+sense);
		// 	$.each(info_res[0], function(index_s,pos)
		// 	{
		// 		console.log("POSITION:"+pos);
		// 		var left=parseFloat((pos)*100/len).toFixed(2);
		// 		var width=Math.max(parseFloat((search[index_search].length/len)).toFixed(2),2);
		// 		str+="<div class='exon_odd' style='background-color:"+((sense==true)?"grey":"green")+";position:absolute;display: inline-table;left:"+left+"%;width:"+width+"px; '></div>";
		// 	});
		// 				//	console.log (index+" "+search[index]+" "+list_search);
		// });
	}
	if (is_cat)
	{
		str+='<table style="margin-bottom:10px;width:100%;text-align:center"><tr><th style="text-align:left">Legend:</th>';
		$.each(list_cat,function(index,value)
		{
			if (value==undefined)return true;
			console.log("TEST" +value+" "+index);
			str+='<td style="padding:5px;color:'+catgroups[index]+'">'+value+"</td>";
		});
		str+='</tr></table>';
	}
	
					str+="</div>";

	console.log("VIEW");
	console.log(indices);
	$("#"+view).html(str);
}


function processTranscriptView(view,search_view,info,GENE_ID)
{
    console.log("PROCESS");
    console.log(info);
	
	list_tooltips=[];
	list_tooltips.length=0;
	list_tooltips.push("A nucleotide is missing when compared to the DNA");var n_ttl=0;

	$("#"+view).css("width",$(".w3-main").width()*0.95);
	var ini_width=$(".w3-main").width()*0.95;

	
	var dw=$("#"+view).width();	/// Width of div
	var fs = 16;					/// fs and fc set for Courier New
	var fc = 1.61;
	//var ratio=9.592307;
	var ratio=9.601907;
	var cpl = Math.floor(Math.floor(fc*dw / fs )/10)*10; /// Character per line.
	$("#"+view).css("width",(ratio*cpl+5)+"px");
	$("#search_view").css("width",(ratio*cpl+5)+"px");
    $("#text_info").css("width",(ratio*cpl+5)+"px");
	$("#trsq_options").css("width",(ratio*cpl+5)+"px");
	var max_width=cpl*fs/fc;
	var seq='';
	var str='';
	

    console.log("############ SIMPLE VIEW ############");
	genTranscriptSimpleView(search_view,info);
	console.log("############ END SIMPLE VIEW ############");
    delim='';
    if ($("#search_delimited").children("option:selected").val()=='tab')delim="\t";
    else if ($("#search_delimited").children("option:selected").val()=='comma')delim=",";
    else if ($("#search_delimited").children("option:selected").val()=='semicolon')delim=";";
    else if ($("#search_delimited").children("option:selected").val()=='space')delim=" ";

    def_level=$("#search_format").children("option:selected").val();

	$.each(info.SEQUENCE.SEQUENCE, function(index,value)
	{
		
		if (value['NUCL']!=null)seq+=value['NUCL'];
		else seq+=' ';
		
	});
	
	var tot_line=Math.ceil(seq.length/cpl);
	var debug=true;
	
	var match_rank=[];
	var next_line=[];
	for(pos=0; pos<seq.length;++pos)match_rank[pos]=[-1];
	if (debug)
    {
        console.log("LENGTH: "+seq.length+" ; CHAR PER LINE: "+cpl+" ; N LINES: "+tot_line+" MAX WIDTH:"+max_width);
        
    }
	var current_start=-1,current_search_len=-1,current_search_strand='+';
	for(n_line=1;n_line<=tot_line;++n_line)
	{
		var start_pos=(n_line-1)*cpl;
		var end_pos=start_pos+cpl;
        var seq_end_pos=end_pos;
        if (seq_end_pos>seq.length)seq_end_pos=seq.length;

		if (debug)console.log("LINE "+n_line+" ["+start_pos+" - "+end_pos+"] /" +current_start);
		str+='<div style="margin-bottom:50px"><div class="matches" style="position:relative;height:$PREVWpx">';
			
				
	if ((typeof search !== "undefined") &&search[0] !=""){
					var match_height=0;
					
					if (next_line.length>0)
					{
						var rank=-1;
						$.each(next_line,function(index,next_linev){
							++rank;
							str+='<div style="position:absolute;margin-bottom:1px;top:'+(1+rank*5)+'px;px;left:0px;height:3px;background-color:'+next_linev[2]+';width:'+((next_linev[0]-start_pos)*ratio)+'px"></div>';
							for (kpos=pos;kpos<=next_linev[0];++kpos)
							match_rank[kpos].push(rank);

						});
						next_line=[];
					}
			for(pos=start_pos; pos<end_pos;++pos)
			{
					
					$.each(indices,function(index,list_search)
					{
                        if (list_search[0]==-1)return true;
						if ($.inArray(pos,list_search[0])!=-1)
						{
							var index_search=list_search[1];
							var sense=list_search[2];
							console.log("WHOLE ALIGNMENT - START "+pos);
							current_start=pos;
							current_search_strand='+';
                            
							console.log("ENTRY:"+search[index_search]);
							pos_search=search[index_search].indexOf(delim);
							if (sense)color="grey";
							else color="orange";
							name='';
							console.log("prev "+color);
							console.log(pos_search);
							var seq_s=search[index_search];
                            console.log("POS SEARCH : "+pos_search);
                            var tabs=search[index_search].split(delim);
                            current_search_len=tabs[0].length;
                            
							if (pos_search!=-1)
							
							{

								
                                seq_s='';
                                seq_s=tabs[0];
                                console.log("SEQUENCE:"+seq_s);
//								current_search_len=pos_search;
                                if (def_level<=3)name=tabs[1];
                                else name=tabs[0];
								if (def_level==3)
								{
									if (is_cat)
										{
											color=catgroups[list_cat.indexOf(tabs[2])];
										}
										else {
									var act_val=Math.floor(parseFloat(tabs[2])/10);
									if (act_val in hexgroups)	color=hexgroups[act_val];
									else color="grey";
									}
								}else if (def_level==4)
								{
									if (is_cat)
										{
											color=catgroups[list_cat.indexOf(tabs[1])];
										}
										else {
									var act_val=Math.floor(parseFloat(tabs[1])/10);
									if (act_val in hexgroups)	color=hexgroups[act_val];
									else color="grey";
									}
                                    console.log("ACTIVITY:"+tabs[1]+" "+act_val+" "+color);
								}
							}
							
							console.log(name+" "+color+" LENGTH:"+seq_s.length+" "+current_search_len);
							var rank=-1;
							for (kpos=pos;kpos<=pos+current_search_len;++kpos)
							$.each(match_rank[kpos],function(idkpos,vkpos)
							{
								
								rank=Math.max(rank,vkpos);
							});
													
							var entry="entry_"+index_search+"_"+pos;
							rank+=1;
							console.log("RANK "+rank);
							var top_height=0;
							if (name!='')top_height+=6;
							str+='<div ';
							if (seq_s.length==19 || seq_s.length==21)str+=' onclick="showDialog(\'/CONTENT/GENEID/'+GENE_ID+'/OLIGO/'+seq_s+'\')" ';
							str+='  id="'+entry+'" style="position:absolute;';
							if (seq_s.length==19 || seq_s.length==21)str+=' cursor:pointer;';
							str+=' margin-bottom:1px;top:'+(1+rank*(5+top_height))+'px;left:'+((pos-start_pos)*ratio)+'px;height:3px;background-color:'+color+';width:'+(ratio*Math.min(current_search_len,end_pos-pos))+'px"></div>';
							if (name!='')str+='<div style="position:absolute;margin-bottom:1px;top:'+(rank*(5+top_height)-10)+'px;left:'+((pos-start_pos)*ratio+(ratio*Math.min(current_search_len,end_pos-pos)))+'px;">'+name+'</div>';
							
							for (kpos=pos;kpos<=pos+current_search_len;++kpos)
							if (kpos<seq_end_pos)
                            {
                                console.log("TESTL:");
                                console.log(match_rank);
                                console.log(kpos+" "+seq_end_pos);
                                console.log(rank);
                                match_rank[kpos].push(rank);
                            }
							match_height=Math.max(match_height,6+rank*(5+top_height));
							console.log("MATCH HEIGHT:"+match_height);
							if (pos+current_search_len>=seq_end_pos)
							{
								next_line.push([pos+current_search_len,(1+rank*5),color]);
							}

							//str+='<span style="background-color:grey">';
						}
					});
					
					
				
				
			}
			console.log("FINAL MATCH HEIGHT"+match_height);
			str=str.replace("$PREVW",match_height);
		}
str+='</div>';
		str+='<div class="seq">'+seq.substr(start_pos,cpl).replace(" ",'<span style="border:1px solid black" class="ttl" data-pos="0"> </span>');
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
  $('[data-toggle="tooltip"]').uitooltip()
})
	
		var n_step=0;
		$.each(list_align,function(index,id_div){
			if ($("#prot_"+id_div).prop("checked")==false)return true;
			n_step+=15;
			str+='<div class="seq" style="position:relative; top:25px">';
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
					str+='<div class="ttl" title="test" style="display:inline;text-decoration:unset" data-pos="'+t+'">-</div>';
					
				}else  if (pos <seq.length) str+='.';
			}
			str+='</div>';
			
	}
		str+="</div>";



	}

	$("#"+view).html(str);
	console.log($('.ttl').uitooltip({open: function (event, ui) {
        ui.tooltip.css("width", "fit-content");
    },hide: {
        effect: "slideDown",
        delay: 2000
      },track: true,
	content: function () {
		
		var snp_i=info.SNP.LIST[$(this).data('pos')];
	console.log("POS:"+$(this).data('pos'));
	var str='<div  style="min-width:250px !important">';
	$.each(snp_i, function(rsid, value)
	{
		str+='<span class="bold">Variant ID:</span><a href="/GENEID/'+GENE_ID+'/MUTATION/'+rsid+'">'+rsid+'</a></span>';
		$.each(value, function(index,infos)
		{
			str+="<br/>DNA:"+index+" ("+info.SNP.TYPES[infos[0]]+") <br/><span style='padding-left:10px'>&#x27A1 Transcript:"+infos[2]+" ("+info.SNP.IMPACT[infos[1]]+")</span>";
		});
		str+='<br/>';
	});

        return str;
    }}));
}





function getToken() {
    let isTokenExists = Object.keys(localStorage).filter(x => localStorage.getItem(x).includes('tokenType'));
    if (isTokenExists.length > 0) {
        let token = JSON.parse(localStorage[isTokenExists[0]]).secret;
        return token;
    } else {
        return '';
    }
}

	
// 	async function getUserPicture(email, tag) {
//     try {
//         let isTokenExists = Object.keys(localStorage).filter(x => localStorage.getItem(x).includes('tokenType'));
//         if (isTokenExists.length > 0) {
//             let token = JSON.parse(localStorage[isTokenExists[0]]).secret;
//             const response = await axios('https://graph.microsoft.com/v1.0/users/' + email + '/photo/$value', {
//                 headers: { Authorization: 'Bearer ' + token },
//                 responseType: "arraybuffer"
//             });
//             console.log(email);
//             console.log(response);
//             if (response.data!=null)
//             {
//             const avatar = btoa(String.fromCharCode.apply(null, new Uint8Array(response.data)));
//             var src = "data:image/jpeg;base64, " + avatar;
//             $("#user_pic_" + tag).html('<img src="' + src + '"/>');
//             }
//             else
//             {
//                 console.log("IN");
//                 $("#user_pic_" + tag).html('<div style="width:50px;height:50px;background-color:red;border-radius:50%"></div>');
//             }
//         }
//     } catch (err) {
//         console.log(err);
//         $("#user_pic_" + tag).html('<div class="portal_col9" style="width:50px;height:50px;border-radius:50%"></div>');
//     }
// }

function ucfirst(str) {

    //Get the first character of the string.
    var firstChar = str.charAt(0);

    //Convert the first character to uppercase.
    firstChar = firstChar.toUpperCase();

    //Remove the original uncapitalized first character.
    var strWithoutFirstChar = str.slice(1);

    //Add the capitalized character to the start of the string.
    var newString = firstChar + strWithoutFirstChar;

    //Return it
    return newString;

}


function align_items(render_id) {
    var main = document.getElementById(render_id);

    [].map.call(main.children, Object).sort(function (a, b) {
        return +a.id.match(/\d+/) - +b.id.match(/\d+/);
    }).forEach(function (elem) {
        main.appendChild(elem);
    });
}


async function getDetailedUserJSON(users) {
    let token = getToken();

    if (token) {
        const userData = users.map(async (user) => {
            const [avatar_response, response] = await Promise.all([
                await getUserImage(user, token),
                await getUserInfo(user, token)
            ]);
            const avatar = btoa(String.fromCharCode.apply(null, new Uint8Array(avatar_response.data)));
            return {
                profile_image: avatar,
                mail: response.data.mail,
                displayName: response.data.displayName,
                givenName: response.data.givenName,
                surname: response.data.surname,
                jobTitle: response.data.jobTitle,
            }
        });
        const usersData = await Promise.all(userData);
        return usersData;
    }

}

async function getUserInfo(user, token) {
    return new Promise((resolve, reject) => {
        axios(ms_graph_url + `users/` + user, {
            headers: { Authorization: 'Bearer ' + token }
        }).then((response) => {
            resolve(response);
        });
    });
}

async function getUserImage(user, token) {
    return new Promise((resolve, reject) => {
        axios(ms_graph_url + 'users/' + user + '/photo/$value', {
            headers: { Authorization: 'Bearer ' + token },
            responseType: "arraybuffer"
        }).then((response) => {
            resolve(response);
        });
    });
}
    
function copyToClipboard(text) {
    var textArea = document.createElement( "textarea" );
    textArea.value = text;
    document.body.appendChild( textArea );       
    textArea.select();
 
    try {
       var successful = document.execCommand( 'copy' );
       alert("Copied the text: " +text);
    } catch (err) {
        alert("Failed to copy " +text);
    }    
    document.body.removeChild( textArea );
 
    // Alert the copied text
    
  }