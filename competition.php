<?php
/*
Plugin Name: Competition Manager
Plugin URI: http://www.justsearching.co.uk/JustBlog/just-search-competition-manager-wordpress-plugin.html
Description: Allow you to display a competition on your blog. 
Version: 1.2.4
Author: JustSearch Development
Author URI: http://www.justsearching.co.uk
*/

require_once('php/class.main.php');

// make sure WP is loaded
$ajax = false;
if (!function_exists('add_action'))
{
	$ajax = true;
	require_once("../../../wp-config.php");
}

// user interaction
if(isset($_GET['comp_action']) && $ajax)
{
  
	$id = (int) $_GET['comp_id'];
	$competition = new Competition($id);
	
	
	$vote_id = (int) $_GET['comp_poll_'.$id];
	$email = $_GET['emailAddr'];
	$contact = $_GET['allowContact'];

  $first_time = $wpdb->get_var("
  SELECT email FROM {$table_prefix}competitionP 
  INNER JOIN {$table_prefix}competitionA ON {$table_prefix}competitionA.idP = {$table_prefix}competitionP.idP
  WHERE email = '{$email}' AND {$table_prefix}competitionA.idQ = {$id}
  ;");
  
  if($first_time != '')
  {
    echo "<li>We already have this email address as a participant</li>";
    exit();
  }
  
  //poll id and whether to set cookie
	$competition->addVote($vote_id, $email, $contact);

	if (!isset($_GET['comp_ajax']) && isset($_SERVER['HTTP_REFERER']))
	{
		header('Location: '.$_SERVER['HTTP_REFERER']);
	}

	echo $competition->showResults();
}


// adds Competition db tables and initializes itself
function plug_install_competition()
{
	global $table_prefix, $wpdb;

	// never used Competition before
  $first_time = $wpdb->get_var("SHOW TABLES LIKE '{$table_prefix}competitionQ'") != $table_prefix."competitionQ";

  $qry = "
CREATE TABLE {$table_prefix}competitionQ (
 idQ int(10) unsigned NOT NULL auto_increment,
 image varchar(200),
 intro text NOT NULL,
 question text NOT NULL,
 start date NOT NULL ,
 end date NOT NULL ,
 current tinyint(1) unsigned NOT NULL default '0',
 active tinyint(1) unsigned NOT NULL default '0',
 winner varchar(200) NOT NULL default 'none',
 PRIMARY KEY (idQ)
 );

CREATE TABLE {$table_prefix}competitionC (
  idC int(10) unsigned NOT NULL auto_increment,
  idQ int(10) unsigned NOT NULL default '0',
  choice text NOT NULL,
  votes mediumint(6) unsigned NOT NULL default '0',
  correct tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY (idC)
);

CREATE TABLE {$table_prefix}competitionP (
  idP int(11) NOT NULL auto_increment,
  email text NOT NULL,
  allowContact tinyint(1) unsigned NOT NULL default '1',
  PRIMARY KEY (idP)
);

CREATE TABLE {$table_prefix}competitionA (
  idQ int(11) NOT NULL default '0',
  idP int(11) NOT NULL default '0',
  idC int(10) unsigned NOT NULL
);";

  require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
  dbDelta($qry);

    add_option('competition_cookie_days','365');
    add_option('competition_allow_contact', '0');
    //max width of a picture
    add_option('competition_picWidth', '150');
    add_option('competition_title','Competition');
    add_option('competition_contact','I want to be contacted regarding other promotional offers from Name of your site');

	// add capability to use admin panel for editors and admins
	// if using the Role Manager plugin, this can be editable
	global $wp_roles;

	// only add roles if they don't exist yet
	if(!isset($wp_roles->roles['administrator']['capabilities']['competition_admin']))
	{
		$wp_roles->add_cap('editor', 'competition_admin');
		$wp_roles->add_cap('administrator', 'competition_admin');   
	}
}


// Adds the Competition Plugin tab to the admin navigation
function plug_add_pageComp()
{
	if(current_user_can('competition_admin'))
	{
		add_submenu_page('edit.php', 'Competition', 'Competition', 0, 'competition', 'plug_admin_pageComp');
	}
}


// shows the management html
function plug_admin_pageComp()
{
	//jal_comp_check_admin();
    include 'php/admin.php';
}

//adds the javascript for management page
function plug_admin_headComp()
{
    $url = get_bloginfo('wpurl');
    echo "\n\t<!-- Added By Competition Plugin. -->";
    //echo "\n\t<script type='text/javascript' src='{$url}/wp-content/plugins/competitionManager/js/jquery.js'></script>";
    echo "\n\t<script type='text/javascript' src='{$url}/wp-content/plugins/competition-manager/js/admin.js'></script>";
    echo "\n\t<script type='text/javascript' src='{$url}/wp-content/plugins/competition-manager/js/jquery.datePicker.js'></script>";
    echo "\n\t<script type='text/javascript' src='{$url}/wp-content/plugins/competition-manager/js/date.js'></script>";
    echo "\n\t<link rel='stylesheet' href='{$url}/wp-content/plugins/competition-manager/css/admin.css' type='text/css' />\n";
    echo "\n\t<link rel='stylesheet' href='{$url}/wp-content/plugins/competition-manager/css/datePicker.css' type='text/css' />\n";

}

// adds the styling and javascript to the WP head
function plug_add_jsComp()
{
    // if you're having problems with ajax loading, switch the commented lines below

    $jal_wp_url = get_bloginfo('wpurl') . "/";

    echo "\n\t<!-- Added By Competition Manager Plugin. Version Beta -->";
    echo "\n\t<script type='text/javascript' src='{$jal_wp_url}wp-content/plugins/competition-manager/js/competition.js'></script>";
    echo "\n\t<link rel='stylesheet' href='{$jal_wp_url}wp-content/plugins/competition-manager/css/basic.css' type='text/css' />";
    echo "\n\t<link rel='stylesheet' href='{$jal_wp_url}wp-content/plugins/competition-manager/css/style.css' type='text/css' />\n";
}

function plug_delete_comp()
{
    //jal_comp_check_adminComp();
    global $wpdb, $table_prefix;

    $id = (int) $_GET['idComp'];

    // Delete the poll question and its answers
    $wpdb->query("DELETE FROM {$table_prefix}competitionQ WHERE idQ = {$id} LIMIT 1");
    $wpdb->query("DELETE FROM {$table_prefix}competitionC WHERE idQ = {$id}");
    $wpdb->query("DELETE FROM {$table_prefix}competitionA WHERE qid = {$id}");
}

function plug_activate_comp() 
{
  //jal_comp_check_admin();
  global $wpdb, $table_prefix;
 
  $id = (int) $_GET['idComp'];
	//$wpdb->query("UPDATE {$table_prefix}competitionQ SET current = 0");
	$wpdb->query("UPDATE {$table_prefix}competitionQ SET active = 1 WHERE idQ = {$id} LIMIT 1");
}

function plug_current() 
{
  //jal_comp_check_admin();
  global $wpdb, $table_prefix;
 
  $id = (int) $_GET['idComp'];
	$wpdb->query("UPDATE {$table_prefix}competitionQ SET current = 0");
	$wpdb->query("UPDATE {$table_prefix}competitionQ SET current = 1 WHERE idQ = {$id} LIMIT 1");
}

function plug_unset_current() 
{
  //jal_comp_check_admin();
  global $wpdb, $table_prefix;
 
  $id = (int) $_GET['idComp'];

	$wpdb->query("UPDATE {$table_prefix}competitionQ SET current = 0 WHERE idQ = {$id} LIMIT 1");
}

function plug_reopen_comp() 
{
  //jal_comp_check_admin();
  global $wpdb, $table_prefix;
 
  $id = (int) $_GET['idComp'];
	//$wpdb->query("UPDATE {$table_prefix}competitionQ SET active = 0");
	$wpdb->query("UPDATE {$table_prefix}competitionQ SET active = 1, winner = 'none' WHERE idQ = {$id} LIMIT 1");
}

function plug_deactivate_comp() 
{
  //jal_comp_check_adminComp();
  global $wpdb, $table_prefix;

  $id = (int) $_GET['idComp'];
	$wpdb->query("UPDATE {$table_prefix}competitionQ SET active = 0 WHERE idQ = {$id} LIMIT 1");
}

function plug_create_comp()
{
	global $wpdb, $table_prefix;

	if(isset($_FILES) && !empty($_FILES)){
    
    require_once("php/class.FileUpload.php");
    
		$upload = new FileUpload();

    if(strlen($_FILES['imageUpload']['name'])>1)
    {
				$errors = $upload->uploadFile($_FILES['imageUpload']);
				if(strlen($errors)>1)
        {
					$arrErrors[] = $errors;
				}
        else
        {
					$name = explode('_',$fileArr);
					$name = $name[0];
					$image = $upload->getFilenames();
				}
		}
	}

	$intro = $wpdb->escape(trim($_POST['intro']));
	
	$competition = $wpdb->escape(trim($_POST['question']));
	$added = time();
	
	$start = $_POST['start'];
	list($day, $month, $year) = explode('/', $start);
	$start = "$year-$month-$day";
		
	$end = $_POST['end'];
	list($day, $month, $year) = explode('/', $end);
	$end = "$year-$month-$day";
	
	// options

	$current = (int) isset($_POST['current_comp']);

	if($current == 1)
  {
    //$active = 1;
    $wpdb->query("UPDATE {$table_prefix}competitionQ SET current = 0");
  }


	//$wpdb->show_errors();
	$wpdb->query("INSERT INTO {$table_prefix}competitionQ (intro, question, current, active, start, end, image) VALUES('{$intro}', '{$competition}', {$current}, 1, '{$start}', '{$end}', '{$image}')");

	$poll_id = $wpdb->insert_id;
	$answers = $_POST['answers'];
		
	foreach ($answers as $key=>$answer)
	{
		$answer = $wpdb->escape(trim($answer));
		
		// id, qid, answer, votes, added_by
		if (!empty($answer)){
		  if($key == $_POST['correct'])
      {
			   $sql_answers[] = "({$poll_id} , '{$answer}', 0, 1)";
			}
			else
			   $sql_answers[] = "({$poll_id} , '{$answer}', 0, 0)";
		}
	}
	
	if(!empty($sql_answers))
	{
		$values = implode(',', $sql_answers);
		$wpdb->query("INSERT INTO {$table_prefix}competitionC (idQ, choice, votes, correct) VALUES {$values}");
	}

  
  
}

function plug_edit_comp()
{
	global $wpdb, $table_prefix;
	
	$poll_id = (int) $_POST['idComp'];
	$intro = $wpdb->escape($_POST['intro']);
	$question = $wpdb->escape($_POST['question']);
	$current = (int) isset($_POST['current_comp']);
	
	if(isset($_FILES) && !empty($_FILES)){
    
    require_once("php/class.FileUpload.php");
    //echo 'test';
    
		$upload = new FileUpload();

    if(strlen($_FILES['imageUpload']['name'])>1)
    {
				$errors = $upload->uploadFile($_FILES['imageUpload']);
				if(strlen($errors)>1)
        {
					$arrErrors[] = $errors;
				}
        else
        {
					$name = explode('_',$fileArr);
					$name = $name[0];
					$image = $upload->getFilenames();
				}
		}
	}
	//echo $image;
	
	$start = $wpdb->escape($_POST['start']);
	list($day, $month, $year) = explode('/', $start);
	$start = "$year-$month-$day";
	
	$end = $wpdb->escape($_POST['end']);
	list($day, $month, $year) = explode('/', $end);
	$end = "$year-$month-$day";
  
  $query = "UPDATE {$table_prefix}competitionQ SET intro = '{$intro}', question = '{$question}', current = {$current}, start = '{$start}', end = '{$end}'";
  
  if($image != "")
  {
    $query .= ", image = '{$image}' ";
  }
  
  $query .= "WHERE idQ = {$poll_id}";
  
	$wpdb->query($query);

	$still_there = $_POST['old_answers'];
	// array of answer ids for answers that don't need to be updated
	$no_update = array();
	// array of answer ids for answers that were deleted
	$delete = array();
	
	$old_answers = $wpdb->get_results("SELECT * FROM {$table_prefix}competitionC WHERE idQ = {$poll_id}");
		
	foreach ($old_answers as $answer)
	{
		if(isset($still_there[$answer->idC]))
		{
			if(stripslashes($answer->choice) == $still_there[$answer->idC]){
				$no_update[] = $answer->idC;
			}
			else if(trim($still_there[$answer->idC]) == ''){
				$delete[] = $answer->idC;
			}
		}
		else
		{
			$delete[] = $answer->idC;
		}
	}
	
	if (!empty($delete))
	{
		$ids = implode(',', $delete);
		$wpdb->query("DELETE FROM {$table_prefix}competitionC WHERE idQ = {$poll_id} AND idC IN ({$ids}) LIMIT ".count($delete));
	}
 
	//Update old answers	
	foreach ($still_there as $aid => $newValue)
	{
		$aid = (int) $aid;
		if (in_array($aid, $no_update))
    {
			continue;
		}
		$newValue = $wpdb->escape($newValue);
		$wpdb->query("UPDATE {$table_prefix}competitionC SET choice = '{$newValue}' WHERE idQ = {$poll_id} AND idC = {$aid} LIMIT 1");
	}
	
	// Add new answers
	$inserts = array();
	if(isset($_POST['answers']))
	{
		
		foreach($_POST['answers'] as $aid => $newValue)
		{
			$answer = $wpdb->escape($newValue);
			$inserts[] = "({$poll_id}, '{$answer}', 0, 0)";
		}
				
		if(!empty($inserts))
    {
			$insert = implode(',', $inserts);
			$wpdb->query("INSERT INTO {$table_prefix}competitionC (idQ, choice, votes, correct) VALUES {$insert}");
		}
	}
	
	$wpdb->query("UPDATE {$table_prefix}competitionC SET correct = 0 WHERE idQ = {$poll_id}");
	$wpdb->query("UPDATE {$table_prefix}competitionC SET correct = 1 WHERE idC = {$_POST['correct']}");
}

function plug_pick_winner() 
{
  global $wpdb, $table_prefix;

  $idQ = $_GET['idComp'];

  $winner = $wpdb->get_var("
  SELECT email FROM {$table_prefix}competitionP
  INNER JOIN {$table_prefix}competitionA ON {$table_prefix}competitionA.idP = {$table_prefix}competitionP.idP
  INNER JOIN {$table_prefix}competitionC ON {$table_prefix}competitionC.idC = {$table_prefix}competitionA.idC
  WHERE {$table_prefix}competitionC.correct = 1 and {$table_prefix}competitionC.idQ = {$idQ} ORDER BY RAND() LIMIT 1
  ");
    
  $wpdb->query("UPDATE {$table_prefix}competitionQ SET winner = '{$winner}', active = 0 WHERE idQ = {$idQ}");
}

function check_adminComp() 
{
	if(!current_user_can('competition_admin'))
  {
  	echo "\n\t<div class='error'>\n\t\t<p>";
    _e('You do not have permission to access the Competition admin panel.', 'Competition');
  	echo "</p>\n\t</div>";
  	die;
  }
}

function filter_create_post_comp($content = '')
{
  var_dump(preg_match('(\[competition_[0-9]*\])', $content, $matches));
  if(preg_match('(\[competition_[0-9]*\])', $content, $matches))
  {
    preg_match('/[0-9]+/', $matches[0], $matches);
    
    $replace = '[competition_'.$matches[0].']';
    //echo $replace;
    $comp = new Competition($matches[0]);
    $competition = $comp->display();
    $content = str_replace($replace, '<div class="compPost">'.$competition.'</div>', $content);
  }
  return $content;
}

/*========================*/
// The following make competition plug-n-play
// by hooking into its various actions
/*========================*/

// installing Competition
add_filter('the_content', 'filter_create_post_comp');

add_action('activate_competition', 'plug_install_competition');

// javascript for main blog
add_action('wp_head', 'plug_add_jsComp');

 // add the management page to the admin nav bar
add_action('admin_menu', 'plug_add_pageComp');

// add javascript to admin area, only on compocracy admin page
if ($_REQUEST['page'] == "competition")
    add_action('admin_head', 'plug_admin_headComp');

// Add a new question and its answers via admin panel
if (isset($_POST['plug_create_comp']))
	//echo 'submited';
	 	
    add_action('init', 'plug_create_comp');

// Edit a question and its answers via admin panel
if (isset($_POST['plug_edit_comp']))
    add_action('init', 'plug_edit_comp');

// When user deletes a poll
if (isset($_GET['delete_comp']))
    add_action('init', 'plug_delete_comp');

// When user choose the curent competition
if (isset($_GET['plug_current']))
{
    add_action('init', 'plug_current');
}
if (isset($_GET['plug_unset_current']))
{
    add_action('init', 'plug_unset_current');
}
// When user activates a poll
if (isset($_GET['plug_activateComp']))
{
    add_action('init', 'plug_activate_comp');
}
// When user deactivates a poll
if (isset($_GET['plug_deactivate']))
    add_action('init', 'plug_deactivate_comp');

// When click on draw a winner
if (isset($_GET['plug_pick_winner']))
    add_action('init', 'plug_pick_winner');

// When click on re-open
if (isset($_GET['re-open']))
    add_action('init', 'plug_reopen_comp');


function widget_competition_register(){
	if ( function_exists('register_sidebar_widget') ) :
	function widget_competition($args) {
		extract($args);
		$options = get_option('competition_title');
		?>
			<?php echo $before_widget; ?>
				<?php echo $before_title . $options . $after_title; ?>
				<div id="Competition">
          <?php
            $comp = new Competition();
            echo $comp->display();
          ?>
        </div>
			<?php echo $after_widget; ?>
	<?php
	}

	function widget_competition_control() {
  	$options = $newoptions = get_option('competition_title');
  	if ( $_POST["competition-submit"] ) {
  		$newoptions = strip_tags(stripslashes($_POST["competition-title"]));
  	}
  	if ( $options != $newoptions ) {
  		$options = $newoptions;
  		update_option('competition_title', $options);
  	}
  	$title = attribute_escape($options);
  ?>
  			<p><label for="competition-title"><?php _e('Title:'); ?> <input class="widefat" id="competition-title" name="competition-title" type="text" value="<?php echo $title; ?>" /></label></p>
  			<input type="hidden" id="competition-submit" name="competition-submit" value="1" />
  <?php
  }

  	register_sidebar_widget('Competition Manager', 'widget_competition');
  	register_widget_control('Competition Manager', 'widget_competition_control', 0, 75, 'Competition');

  	endif;
}
add_action('init', 'widget_competition_register');

?>
