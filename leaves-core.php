<?php
/*
Plugin Name: Leaves
Plugin URI: http://www.sudowned.com/leaves
Description: Leaves is a plugin that turns ordinary blog posts into beautiful e-books.
Version: 0.2
Author: Sudowned (Winfield Trail)
Author URI: http://www.sudowned.com
License: Free software. If you really want, I like links to my book (http://www.bookofjarrah.com)
*/

//options
add_option("leaves_categories","");
add_option("leaves_chapterwait_text","The next chapter is not yet available.");

//post hooks
add_action('wp_footer', 'leavesDo');
add_action('wp_head', 'leavesCss');

//administration hooks
add_action('admin_menu', 'leavesAdminMenu');

//begin lib (this is going to get spun off into its own file eventually

function leavesAdminMenu(){
	add_submenu_page('options-general.php',"Leaves","Leaves","manage_options","leaves","leavesDrawMenu");
}

function leavesDrawMenu(){

	$args=array(
	  'orderby' => 'name',
	  'order' => 'ASC',
	  'hide_empty' => 0
	  );
	  
	$categories=get_categories($args);
	
	  
	$leaves_chapterwait_text = get_option('leaves_chapterwait_text');
	$leaves_finalpage_content = get_option('leaves_finalpage_content');
	$cats = get_option("leaves_categories");
	
	if ($_POST[leaves_categories]){
	
		$cats = implode(" ",$_POST[leaves_categories]);
		update_option("leaves_categories",$cats);
		
	}
	
	if ($_POST[leaves_chapterwait_text]){
		update_option('leaves_chapterwait_text',$_POST[leaves_chapterwait_text]);
		$leaves_chapterwait_text = $_POST[leaves_chapterwait_text];
	}
	
	if ($_POST[leaves_finalpage_content]){
		update_option('leaves_finalpage_content',$_POST[leaves_finalpage_content]);
		$leaves_finalpage_content = $_POST[leaves_finalpage_content];
	}
	
	echo("<style type='text/css'>
		.leaves-admin {padding: 5px; margin-bottom: 10px; width: 500px; background-color: rgba(20,200,20,0.2);}
		.leaves-admin ul {margin-left: 10px;}
		textarea {width: 350px; height: 120px;}
	</style>");
	echo("<h2>".__('Leaves Configuration', 'leaves')."</h2>
	
	
		<form name='leaves-settings' method='post' action=''>
		<div class='leaves-admin'>
		<h3>Categories</h3>
		Leaves will bookify all posts in these categories.<br>
		
		<ul>");
		
		foreach($categories as $category) { 
		    echo ('
		    <li>
		    	<input type="checkbox" name="leaves_categories[]" value="'.$category->cat_ID.'" '.leavesCatChecked($category->cat_ID).'>&nbsp;'.$category->name.' ('.$category->count.' posts)
		    </li> '
		    );
		} 
		
		echo("</ul>
		</div>
		
		<div class='leaves-admin'>
			<h3>Chapter-ends</h3>
			At the end of each chapter there is a link to the next chapter, unless there is no following chapter. Customize that chapterwait text here.
			<br><br>
			<textarea name='leaves_chapterwait_text'>".stripslashes($leaves_chapterwait_text)."</textarea>
		</div>
		
		<div class='leaves-admin'>
			<h3>Last page content</h3>
			You may add content to be placed on the final page, immediately before the Next Chapter button. HTML/JavaScript is okay, PHP won't work.
			<br><br>
			<textarea name='leaves_finalpage_content'>".stripslashes($leaves_finalpage_content)."</textarea>
		</div>
		
		
		<p class='submit'>
		<input type=\"submit\" name=\"Submit\" class=\"button-primary\" value=\"Save\" />
		</p>
		
		
		</form>
	
	");
}

function leavesCatChecked($id){
$cats = explode(" ",get_option("leaves_categories"));
if (in_array($id,$cats)){
	return "checked='true'";
}
}

function leavesDo(){
	if (is_single()){
		$cats = explode(" ",get_option("leaves_categories"));
		if (in_category($cats)){
			echo(leavesMarkup().leavesScripts());
		}
	}
}

function leavesCss(){
	echo("
		<link href='".plugins_url()."/leaves/leaves.css' type='text/css' rel='stylesheet'/>
	");
}

function leavesMarkup(){
	$leaves_chapterwait_text = stripslashes(get_option('leaves_chapterwait_text'));
	$leaves_finalpage_content= stripslashes(get_option('leaves_finalpage_content'));
	$chapternum = get_post_custom();
	$chaptertitle = get_the_title();
	$chapternumeral = romanNumerals($chapternum['chapternum'][0]);
	if (!$chapternumeral) {
		$chapternumeral = "Prelude";
	} else {
		$chapternumeral = "Chapter ".$chapternumeral;
	}
	ob_start();
	next_post_link('%link','%title',TRUE,'');
	$nextpostlink = ob_get_contents();
	ob_end_clean();
	
	if (!$nextpostlink) {
		$nextpostlink = "<i style='color: gray'>$leaves_chapterwait_text</i>";
	}

	$markup="
		<div class='pagehelp-holder'>
				<div class='pagehelp-button'>
				</div>
				<div class='pagehelp-body'>
					<div class='pagehelp-bg'>
						<span>Flip pages via scrollwheel, arrow keys, or clicking!</span>
						<img src='/theme/help.png'>
					</div>
					<img src='/theme/tooltip-pointer.png' class='pagehelp-pointer'>
					<img src='/theme/helpicon-hover.png' style='display: none;'>
				</div>
				
				
				
			</div>
			
			
			<div style='display: none;'>
				<span id='chapterheader'>
					$chapternumeral
				</span>
				<span id='chaptertitle'>
					$chaptertitle
				</span>
				
				<span id='LeavesFinalPageContent'>
					$leaves_finalpage_content
				</span>
				
				<span id='chapterlink'>
					Next chapter:<br>
					$nextpostlink
				</span>
</div>
	";

	return $markup;
}

function leavesScripts(){
	$scripts = "<script type='text/javascript' src='/wp-content/leaves/leaves.js'></script>";
			
	return $scripts;
}
?>
