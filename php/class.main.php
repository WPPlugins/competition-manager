<?php

class Competition {

	var $id;
	var $competition;
	var $hasVoted;
	
	var $intro;
	var $image;
	var $question;
	var $allow_users_to_add;
	var $active;
	var $timestamp;
	
	var $votedFor;
	
	var $start;
	var $endDate;
	

	function Competition($id = 0, $before_title = false, $after_title = false)
	{

		global $wpdb, $table_prefix;
		if ($id == 0)
			$competition = $wpdb->get_row("SELECT * FROM {$table_prefix}competitionQ WHERE current = 1 LIMIT 1");
		else
			$competition = $wpdb->get_row("SELECT * FROM {$table_prefix}competitionQ WHERE idQ = {$id} LIMIT 1");

    if ($competition)
    {
			$this->id = (int) $competition->idQ;
			
			 //if user's code specified no id, then we're working with the current competition
			$this->isCurrent = ($id == 0);

			$this->hasVoted = $this->hasVoted();
			
			//use isset in case it reverted to a SQL query
			if (!$this->hasVoted && isset($_COOKIE['Competition_'.$this->id]))
			   $this->votedFor = $_COOKIE['Competition_'.$this->id];
			else
			   $this->votedFor = false;
			
			$this->before_title = ($before_title) ? $before_title : '<h4 class="Comp_question">';
			$this->after_title  = ($after_title) ? $after_title : '</h4>';
			$this->image = $competition->image;
			$this->intro = stripslashes($competition->intro);
			$this->question = stripslashes($competition->question);
			$this->allow_users_to_add = (bool) $competition->allowusers;
			$this->active = (int) $competition->active;
			$this->added = (int) $competition->added;
			
			$start = $competition->start;
    	$this->start = strtotime($start);
    	
    	$end = $competition->end;
    	$this->endDate = strtotime($end);
    }
    // there is no competition with 'current' status
    else
    {
        $this->id = false;
        _e("<!-- Competition Manager has no active competitions -->", 'Competition');
    }
	}
			
	function hasVoted()
	{
	    if (!$this->id) return false;
        global $wpdb, $table_prefix;
                       
        if (isset($_COOKIE['Competition_'.$this->id])){
            return true;
        }
       
        return false;
	}
	
	function getAnswers()
	{
	  if (!$this->id) return false;

		global $wpdb, $table_prefix;
		return $wpdb->get_results("SELECT * FROM {$table_prefix}competitionC WHERE idQ = {$this->id}");	
	}

	// displays the vote interface of a competition
	function display ($showVoteScreen = true)
	{
	    if (!$this->id) return false;
		  $output = '';
		  
      if($this->image != '') $output .= "<img src='./wp-content/plugins/competition-manager/images/".$this->image."' alt='$this->image' />";
    
      $output .= "<p>{$this->intro}</p>";

	    $output .=  "\n\t\t{$this->before_title}{$this->question}{$this->after_title}";
            
      if($this->active == 0 || $this->endDate < time())
      {
        return $output."<p>".get_option('competition_closed')."</p>";
      }

			if ($showVoteScreen && !$this->hasVoted)
				$output .= $this->showVoteScreen();
			else
				$output .= $this->showResults();
		
	
		return $output;
	}
	
	function showVoteScreen()
	{
	    if (!$this->id) return false;

		$output = '';

		$answers = $this->getAnswers();

		if ($answers)
		{
		
			$output .= "\n\t\t<form action='".get_bloginfo('wpurl')."/wp-content/plugins/competition-manager/competition.php' onsubmit='return comp_Vote(this, $this->id)'>";
		
			$output .= "\n\t\t<ul style=\"margin-bottom:10px;\">";

			foreach ($answers as $answer)
			{
			    $word = stripslashes($answer->answer);
			
			    $output .= "\n\t\t\t<li>";
			    $output .= "\n\t\t\t\t\t<input type='radio' id='dem-choice-{$answer->idC}' value='{$answer->idC}' name='dem_competition_{$this->id}' />";
			    $output .= "\n\t\t\t\t\t<label for='dem-choice-{$answer->idC}'>".htmlspecialchars(stripslashes($answer->choice))."</label>";
			    $output .= "\n\t\t\t</li>";
			}
			
			$output .= "\n\t\t</ul>";
			
			$output .= '<p class="errorComp" id="errorComp_'.$this->id.'"></p>';
			
			$output .= '<p><label>Email: </label><input type="text" name="emailAddr" id="emailAddr_'.$this->id.'" value="" /></p>';
			$output .= '<p><label>2+2 = </label><input type="text" name="verif" id="verif_'.$this->id.'" value="" size="3" /></p>';
      
      if(get_option('competition_allow_contact')==1)
      {
        $output .= '<p><label><input type="checkbox" name="allowContact" id="allowContact_'.$this->id.'" value="1" />'.get_option('Competition_contact').'</strong></label></p>';
			}
			$output .= "\n\t\t\t<input type='hidden' name='competition_id' value='{$this->id}' />";
			$output .= "\n\t\t\t<input type='hidden' name='competition_action' value='vote' />";
			$output .= "\n\t\t\t<input type='submit' class='comp-vote-button' value='".__('Vote', 'Competition')."' />";
			
			$output .= "\n\t\t</form>";
			
		}
		return $output;
	}
	
	function showResults ($voted_for_aid = -1)
	{
	  if (!$this->id) return false;

		$output = '';

		$answers = $this->getAnswers();

		$max = 0;
		$total = 0;
		foreach ($answers as $answer)
		{
			$total += $answer->votes;
			if ($max < $answer->votes)
				$max = $answer->votes;
		}

		$output .= "\n\t\t<ul>";
				
		$from_total = get_option('democracy_graph_from_total');
		
		// only show the 'a guest has added an answer' text if it's happened..
		$has_added_by = false;
		
    $output .=  '<li class="thanks">'.get_option('Competition_thanks').'</li>';

		$output .= "\n\t\t</ul>";
		$output .= "\n\t\t<em>".__('Started: ').date(get_settings('date_format'), $this->start).'</em>';
    $output .= "\n\t\t<em>".__('End: ').date(get_settings('date_format'), $this->endDate).'</em>';
		
		return $output;
	}	
	
	function addVote($answer, $email, $contact)
	{
	  if (!$this->id) return false;

		global $wpdb, $table_prefix;

		// only shown if they're manipulating URLs
		if ($this->hasVoted)
			return false;
		
		$answer = (int) $answer;
    $wpdb->query("UPDATE {$table_prefix}competitionC SET votes = (votes+1) WHERE idQ = {$this->id} AND idC = {$answer} LIMIT 1");

    $correct = $wpdb->get_var("SELECT idC FROM {$table_prefix}competitionC WHERE correct = 1 AND idQ = {$this->id}");

    $playerId = $wpdb->get_var("
      SELECT idP FROM {$table_prefix}competitionP 
      WHERE email = '{$email}'
      ;");

    if($playerId == '')
    {
        $wpdb->query("INSERT INTO {$table_prefix}competitionP (email, allowContact) VALUES('{$email}', '{$contact}');");
        $playerId = $wpdb->insert_id;
    }

    $wpdb->query("INSERT INTO {$table_prefix}competitionA (idQ, idC, idP) VALUES('{$this->id}', '{$answer}', '{$playerId}')");

		$cookie_last = get_option('competition_cookie_days') * 24 * 60 * 60;
	  setcookie("Competition_{$this->id}", $answer,  time()+$cookie_last, '/');
		$this->hasVoted = true;
		$this->votedFor = $answer;

	}
	
}
