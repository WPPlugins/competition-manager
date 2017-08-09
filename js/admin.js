

jQuery(function()
{
	jQuery('.date-pick').datePicker({clickInput:true});
	jQuery('#start-date').bind(
		'dpClosed',
		function(e, selectedDates)
		{
			var d = selectedDates[0];
			if (d) {
				d = new Date(d);
				jQuery('#end-date').dpSetStartDate(d.addDays(1).asString());
			}
		}
	);
	jQuery('#end-date').bind(
		'dpClosed',
		function(e, selectedDates)
		{
			var d = selectedDates[0];
			if (d) {
				d = new Date(d);
				jQuery('#start-date').dpSetEndDate(d.addDays(-1).asString());
			}
		}
	);
	
	jQuery("#check").click(function()
  {
    var emailList = jQuery('#checklist :checkbox');
    var text = '';
    for(var i=0 ; i<emailList.length ; i++)
    {
      text += emailList[i].value+'; ';
    }
    
    jQuery('#checklist :checkbox').attr({checked:"checked"});
    jQuery('#emailList').text(text);
  });
  
	jQuery("#uncheck").click(function()
  {
    jQuery('#checklist :checkbox').removeAttr("checked");
    jQuery('#emailList').text('');
  });
  
  jQuery('#checklist :checkbox').click(function()
  {
    var emailList = jQuery('#checklist :checkbox');
    var text = '';
    for(var i=0 ; i<emailList.length ; i++)
    {
      if(emailList[i].checked)
      {
        text += emailList[i].value+'; ';
      }
    }
    jQuery('#emailList').text(text);
    
  });
  
  jQuery('#emailList').click(function()
  {
    jQuery('#emailList').select();
  });
	
});

var i = 4;
function addAnswer()
{
	btns = document.getElementById('answerButtons');
	oLI = document.createElement('li');
	oLI.innerHTML = "<input type='text' name='answers[]' /><label> <input type='radio' name='correct' value='"+i+"' /> Correct</label>";
	btns.parentNode.insertBefore(oLI, btns);
	oLI.firstChild.focus();
	
	document.getElementById('remove-answer').disabled = false;
	
	i++;
	
	return false;
}

function removeAnswer(that)
{
	oOL = document.getElementById('comp-answers')
	oLIs = oOL.getElementsByTagName('li');
	
	if (oLIs.length == 3){
		that.disabled = true;
		return false;
	}
	oOL.removeChild(oLIs[oLIs.length-2]);
	
	i--;
	
	return false;
}

function unlinkFile(file, id)
{
  jQuery.post('../wp-content/plugins/competition-manager/php/unlink.php', {file: file, pollId:id}, function() {
    jQuery('imageEdit').hide;
  });
}

