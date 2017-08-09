<?php

global $wpdb, $table_prefix;

if (!defined('ABSPATH'))
	die('no');

if ($wpdb->get_var("SHOW TABLES LIKE '{$table_prefix}competitionQ'") != $table_prefix."competitionQ")
{
	plug_install_competition();
}

if(isset($_POST['gen_options']))
{
  update_option('competition_cookie_days', $_POST['competition_cookie_days']);
  update_option('competition_allow_contact', $_POST['competition_allow_contact']);
  update_option('competition_picWidth', $_POST['competition_picWidth']);
  update_option('competition_thanks', $_POST['competition_thanks']);
  update_option('competition_closed', $_POST['competition_closed']);
  update_option('competition_contact', $_POST['competition_contact']);
}

if(isset($_GET['unlink']))
{
  unlink('../wp-content/plugins/competition-manager/images/'.$_GET['unlink']);
  $query = "UPDATE {$table_prefix}competitionQ SET image = '' WHERE idQ = {$_GET['idComp']}";  
	$wpdb->query($query);
}

$poll_id = isset($_GET['idComp']) ? (int) $_GET['idComp'] : false;

if (isset($_GET['delete_comp']))
{
    echo "\n\t<div class='updated'>";
    echo "\n\t\t<p>".__('The competition was deleted.', 'competition')."</p>";
    echo "\n\t</div>";
}

function editScreen($editing = false)
{
	global $wpdb, $table_prefix;
	
	$poll_id = $editing;

	$checked = ' checked="checked"';
	if ($editing)
	{
		$title = __('Edit Competition', 'competition');
		
		$poll = $wpdb->get_row("SELECT * FROM {$table_prefix}competitionQ WHERE idQ = {$poll_id} LIMIT 1");
		$answers = $wpdb->get_results("SELECT * FROM {$table_prefix}competitionC WHERE idQ = {$poll_id}");
		$intro = htmlspecialchars(stripslashes($poll->intro));
		$question = htmlspecialchars(stripslashes($poll->question));
		$start = date('d/m/Y', strtotime($poll->start));
		$end  = date('d/m/Y',  strtotime($poll->end));
    $image = $poll->image;
		$choices = array();
		foreach ($answers as $answer){
			$choices[] = array("old_answers[{$answer->idC}]", htmlspecialchars(stripslashes($answer->choice)), $answer->idC, $answer->correct);
		}
		$current = ($poll->current) ? $checked : '';
		
		$edit_inputs = "<input type='hidden' name='idComp' value='{$poll_id}' />";
		$edit_inputs .= "<input type='hidden' name='plug_edit_comp' value='1' />";
	}
	
	else
	{
		$title = 'Add a Competition';
		$question = '';
		
		$choices = array();
		for ($i = 0; $i < 4; $i++)
			$choices[] = array('answers[]', '');

		$current = $checked;
		$multiple = '';
		$allow_users = '';
		
		$edit_inputs = "<input type='hidden' name='plug_create_comp' value='1' />";
		
	}
	
	
	// ===============================================
	
	echo "\n\t<div class='wrap'>\n";
    	
    	// beginning of Add A Competition
		echo "\n\t\t<h2>{$title}</h2><div class=' addCompAdmin'>";    	
    	
    	
		echo "\n\t\t<form action='edit.php?page=competition' method='post' enctype='multipart/form-data'><div id='new-poll'>";
		    echo "<div class='inline-label'><ol>";
        
		    
		    echo "\n\t\t\t<li><label for='image'>Upload Image:</label>";
		    echo "\n\t\t\t<input type='file'  id='image-label' name='imageUpload' size=\"80\" /></li>";
		    
		    echo "\n\t\t\t<li><label for='intro' id='intro-label'>Text intro:</label>";
		    echo "\n\t\t\t<input type='text' id='intro' name='intro' size=\"80\" value=\"{$intro}\" /></li>";
		    
		    
		    
		    echo "\n\t\t\t<li><label for='question' id='the-question-label'>Question:</label>";
		    echo "\n\t\t\t<input type='text' id='question' name='question' size=\"80\" value=\"{$question}\" /></li>";
			echo "\n\t\t\t<li><label for='start-date'>Start Date:</label>";
		    echo "\n\t\t\t<input type='text'  id='start-date' class='date-pick' name='start' size=\"10\" value=\"{$start}\" /></li>";
			echo "\n\t\t\t<li><label for='end-date'>End Date:</label>";
		    echo "\n\t\t\t<input type='text' id='end-date' class='date-pick' name='end' size=\"10\" value=\"{$end}\" /></li>";
			echo "</ol></div>";
		    echo "\n\t\t\t<p><strong>Answers:</strong></p>";

		    echo "\n\t\t\t<ol id='comp-answers'>";
		    $i = 0;
	
		    	foreach ($choices as $choice)
		    	{
		    		echo "\n\t\t\t\t<li>";
		    			echo "<input type='text' name=\"{$choice[0]}\" value=\"{$choice[1]}\" />";
              echo '<label> <input type="radio" name="correct" value="'.(isset($choice[2]) ? $choice[2] : $i).'"';
              if($choice[3] == 1)
              {
                echo 'checked = "checked"';
              }
              echo ' /> Correct</label>';
		    		echo "\n\t\t\t\t</li>";
					$i++;
		    	}
		    	
		    	echo "\n\t\t\t\t<li id='answerButtons'>";
		    		echo "\n\t\t\t\t\t<button onclick='return addAnswer()'>Add an Answer</button> ";
		    		echo "\n\t\t\t\t\t<button id='remove-answer' onclick='return removeAnswer(this)'>Remove an Answer</button>";
		    	echo "\n\t\t\t\t</li>";

		    
		    echo "\n\t\t\t</ol>";
		    echo "<div id='imageEdit' style='";
        echo ($image != '' ? 'display:block': 'display:none');
        echo "'>";
        echo "<img src='../wp-content/plugins/competition-manager/images/{$image}' alt='' id='imageEdit' />
        <a href='".$_SERVER['REQUEST_URI']."&amp;unlink={$image}'>delete picture</a>
        </div>";
		    echo "\n\t\t\t<p style='clear:both'><strong>Options:</strong></p>";
		    echo "\n\t\t\t<ol class='poll-options'>";
		    echo "\n\t\t\t\t<li><input type='checkbox'{$current} name='current_comp' value='1' id='current_comp' /><label for='current_comp'>Make this the current Competition</label></li>";
	
		    echo '<li>'.$edit_inputs.'</li></ol>';
		
		    echo "\n\t\t\t<input type='submit' class='the-poll-submit' value='",__('Submit', 'democracy'),"' />";
		    
		
		echo "\n\t\t</div></div></form>";

    // .wrap
	echo "\n\t</div>\n";
	
	
}




// editing a specific poll
if ($poll_id && isset($_GET['edit_comp']))
{
	editScreen($poll_id);
}

// showing management page
else 
{


    $polls = $wpdb->get_results("
    SELECT *
    FROM {$table_prefix}competitionQ
    ");
	
	echo "\n\n\t<div class='wrap'>";
	echo "<p class='intro'>You can display a competition using the widget provided (go to design->widget and press 'add' on the Competition widget) or by placing the code [competition_id] (where 'id' is the id of the competition) in the text of your post</p>";
	echo "\n\t\t<h2>",'Manage Competition',"</h2>";
	//echo '<pre>'.print_r($polls, true).'</pre>';
	if ($polls)
	{
    	// extract the current poll id
    	$current = 0;
    	foreach ($polls as $poll)
    	{
    		if ($poll->current == "1")
    		{
    			$current = $poll->idQ;
    			break;
    		}
    	}

    	$votes_total = $wpdb->get_results("
    	 SELECT SUM(votes) as total_votes, idQ
    	 FROM {$table_prefix}competitionC
    	 GROUP BY idQ
    	", ARRAY_A);

       	
    	// indexed by poll id, contains total votes
    	$totalvotes = array();
    	if ($votes_total)
    		foreach ($votes_total as $poll_total)
    			$totalvotes[$poll_total['idQ']] = $poll_total['total_votes'];
    	if ($current == 0)
    	{
    	   echo "\n\t\t<div class='error'>";
    	       echo "\n\t\t\t<p>";
    	           echo "\n\t\t\t<strong>You have no current competition. The widget will appear empty.</strong>";
    	       echo "</p>\n";
    	   echo "\n\t\t</div>";
    	}
    	
    	echo "\n\t\t<table class='comp-list'>";
    	    echo "\n\t\t\t<tr>";
    	    	echo "\n\t\t\t\t<th scope='col'>ID</th>";
    	    	echo "\n\t\t\t\t<th scope='col'>Question</th>";
				echo "\n\t\t\t\t<th scope='col'>Start</th>";
				echo "\n\t\t\t\t<th scope='col'>End</th>";
    	    	echo "\n\t\t\t\t<th scope='col'>Total Player</th>";
    	    	echo "\n\t\t\t\t<th scope='col'>Winner</th>";
    	    	echo "\n\t\t\t\t<th scope='col'>Action</th>";
    	    echo "\n\t\t\t</tr>";
    	    
    	
    	$class = '';
    	
  	
    	foreach ($polls as $poll)
    	{
    	    $question = stripslashes($poll->question);
    	    
    	    $total = isset($totalvotes[$poll->idQ]) ? $totalvotes[$poll->idQ] : 0;
    	    
    	    // if there ever need to lots of classes involved
		    $classes = array();

    	    if ($class == '')
		    	$classes[] = 'alternate';
		    	
    	    if ($current == $poll->idQ)
    	    {
    	    	$total = "So far, {$total}";
    	    	$classes[] = 'active';
    	    }

    	    $class = (empty($classes)) ? '' : ' class="'.implode(' ', $classes).'"'; 
    	    $winner = $poll->winner;

  			$start = date('d/m/Y', strtotime($poll->start));
  			$end  = date('d/m/Y',  strtotime($poll->end));

    	    echo "\n\t\t\t<tr{$class}>";
    	    	echo "\n\t\t\t\t<td>{$poll->idQ}</td>";
    	    	echo "\n\t\t\t\t<td>{$question}</td>";
				echo "\n\t\t\t\t<td>{$start}</td>";
				echo "\n\t\t\t\t<td>{$end}</td>";
    	    	echo "\n\t\t\t\t<td>{$total}</td>";
    	    	echo "\n\t\t\t\t<td>{$winner}</td>";
    	    	
    	    	echo "\n\t\t\t\t<td>";
    	    		echo "\n\t\t\t\t\t<form action='' method='get'><div>";
               			echo "\n\t\t\t\t\t\t<input type='hidden' name='page' value='competition' />";
               			echo "\n\t\t\t\t\t\t<input type='hidden' name='idComp' value='{$poll->idQ}' />";
               			
                   if($current != $poll->idQ)
                    {
                      echo "\n\t\t\t\t\t\t<input type='submit' value='",__('Set as current', 'Competition'),"' name='plug_current' />";
                    }
                    else
                    {
                      echo "\n\t\t\t\t\t\t<input type='submit' value='",__('Unset as current', 'Competition'),"' name='plug_unset_current' />";
                    }
               			if($winner == 'none' || $winner = '')
                    {
                      
                 			if ($poll->active == 1)
                      {
                 				echo "\n\t\t\t\t\t\t<input type='submit' value='",__('Close', 'Competition'),"' name='plug_deactivate' />";
                 				if($totalvotes[$poll->idQ]>0){
                 				echo "\n\t\t\t\t\t\t<input type='submit' name='plug_pick_winner' value='Pick a Winner' onclick='return confirm(\"",__('You are about to Pick a winner. Once donne the competition will be deactivated.\n  \"Cancel\" to stop, \"OK\" to pick.', 'Competition'),"\");' />";
                 				}
                 			}else
                 				echo "\n\t\t\t\t\t\t<input type='submit' value='",__('Open', 'Competition'),"' name='plug_activateComp' />";
               			    
               			  echo "\n\t\t\t\t\t\t<input type='submit' value='edit' name='edit_comp' />";
                     }
                     else{
                        echo "\n\t\t\t\t\t\t<input type='submit' value='re-open' onclick='return confirm(\"",__('You are about to re-open this competition. This will delete the current winner and put back the compeition as active.\n  \"Cancel\" to stop, \"OK\" to delete.', 'Competition'),"\");' name='re-open' class='re-open' />";
                     }
               			
                		echo "\n\t\t\t\t\t\t<input type='submit' value='delete' onclick='return confirm(\"",__('You are about to delete this poll.\n  \"Cancel\" to stop, \"OK\" to delete.', 'Competition'),"\");' name='delete_comp' class='delete' />";
    	    		echo "\n\t\t\t\t\t</div></form>";
    	    	echo "\n\t\t\t\t</td>";
    	    echo "\n\t\t\t</tr>";
    	    
    	}
    	
    	echo "\n\t\t</table>";
    	// end of manager
    
    } 
    else 
	{
		echo "\n\t<div class='error'>";
			echo "\n\t\t<p>There are no competition in the database</p>";
		echo "\n\t</div>\n";
	}

	echo "\n\t</div>\n";



	editScreen(false);

  echo "\n\t<div class='wrap'>";
  echo "\n\t\t<h2>Mailing List</h2>";
  
  $mailingList = $wpdb->get_results("SELECT email, idP FROM {$table_prefix}competitionP WHERE allowContact = 1", ARRAY_A);
  if($mailingList != '')
  {
    echo "<ul><li><a href='javascript:;' id='check'>Select All</a></li><li><a href='javascript:;' id='uncheck'>Deselect All</a></li></ul>";
    
    echo "<ul class='checklist' id='checklist'>";
    
    foreach($mailingList as $array)
    {
      echo "<li><label><input type='checkbox' value='".$array['email']."' /> ".$array['email']."</label></li>";
    }
    echo "</ul>";
    
    echo "<h5>Click and press ctrl+c to copy all email address:</h5><textarea cols='50' rows='10' id='emailList'></textarea>";
  	echo "</div>";
  }
  else
  {
    echo "There is no participant willing to be contacted";
    echo "</div>";
  }
	echo "\n\t<div class='wrap'>";

	   // Beginning of General Options
	   echo "\n\t\t<h2>General Options</h2>";
	   	   
	   echo "\n\t\t<form action='edit.php?page=competition' method='post'>";
	   
        //cookie expiration time
	       echo "\n\t\t\t\t<p>";
	       echo "\n\t\t\t\t\t<input type='text' size='3' value='".get_option('Competition_cookie_days')."' name='competition_cookie_days' id='democracy_cookie_days' /> <label for='democracy_cookie_days'>Days cookies should last</label>";
	       echo "\n\t\t\t\t\t<em> The number of days cookies should last before they expire. Lower the number to allow people to vote again in x number of days. Default: 365.</em>";
	       echo "\n\t\t\t\t</p>";
	       
	       //Allow contact
	       echo "\n\t\t\t\t<p>";
	       echo "\n\t\t\t\t\t<input type='checkbox' ";
         if( get_option('Competition_allow_contact') == 1)
         {
            echo "checked = 'checked'";
         } 
         echo "value = '1' name='competition_allow_contact' id='competition_allow_contact' /> <label for='competition_allow_contact'>Allow user to choose if they want to be contacted for other promotional stuff. </label>";
	       echo "\n\t\t\t\t\t<em> If unticked no checkbox will be displayed on the front page and users will be added by default to the contact list.</em>";
	       echo "\n\t\t\t\t</p>";
     
        echo "\n\t\t\t\t<p>";
	       echo "\n\t\t\t\t\t<input type='text' size='5' value='".get_option('competition_picWidth')."' name='competition_picWidth' id='competition_picWidth' /> <label for='competition_picWidth'>Max width of a picture displayed in the Competition widget</label>";
	       echo "\n\t\t\t\t\t<em> If the picture uploaded is bigger than the specified width it will be croped.</em>";
	       echo "\n\t\t\t\t</p>";

        //thank you text
	       echo "\n\t\t\t\t<p>";
	       echo "\n\t\t\t\t\t<input type='text' size='40' value='".get_option('Competition_thanks')."' name='competition_thanks' id='competition_thanks' /> <label for='competition_thanks'>The text to display when someone submit his vote</label>";
	       echo "\n\t\t\t\t</p>";
	       
	       //competition closed
	       echo "\n\t\t\t\t<p>";
	       echo "\n\t\t\t\t\t<input type='text' size='40' value='".get_option('Competition_closed')."' name='competition_closed' id='competition_closed' /> <label for='competition_closed'>The text to display when the competition is closed</label>";
	       echo "\n\t\t\t\t</p>";
	       
	       //want to be put into the mailing list
	       echo "\n\t\t\t\t<p>";
	       echo "\n\t\t\t\t\t<input type='text' size='40' value='".get_option('Competition_contact')."' name='competition_contact' id='competition_contact' /> <label for='competition_contact'>The text to display if you allow user to choose if they want to be contacted or not</label>";
	       echo "\n\t\t\t\t</p>";

	       echo "\n\t\t\t\t<input type='submit' name='gen_options' class='the-poll-submit' value='Save Options' />";

	   echo "</form>";
	   

	   
   echo "</div>";

}

?>
