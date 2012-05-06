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
	$cats = get_option("leaves_categories");
	
	if ($_POST[leaves_categories]){
	
		$cats = implode(" ",$_POST[leaves_categories]);
		update_option("leaves_categories",$cats);
		
	}
	
	if ($_POST[leaves_chapterwait_text]){
		update_option('leaves_chapterwait_text',$_POST[leaves_chapterwait_text]);
		$leaves_chapterwait_text = $_POST[leaves_chapterwait_text];
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
			<textarea name='leaves_chapterwait_text'>$leaves_chapterwait_text</textarea>
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
	$leaves_chapterwait_text = get_option('leaves_chapterwait_text');
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
				<span id='chapterlink'>
					Next chapter:<br>
					$nextpostlink
				</span>
</div>
	";

	return $markup;
}

function leavesScripts(){
	$scripts = "<script type='text/javascript' src='/theme/bdetect.js'></script>
				<script>
					//begin leaves
					
					$(document).ready(function() {
					
							if (BrowserDetect.browser == \"Firefox\" && BrowserDetect.version < 3.6) {throw new Error(\"Browser is too old for fanciness.  Sorry.\"); return;} else {
								console.log(\"looks like you're running \"+BrowserDetect.browser+\" \"+BrowserDetect.version);
							}
					
							var postid = $('.post').attr('id').substring(5);
						
							var nextlink = $('#chapterlink');
							$('#chapterlink').remove();
						
							//remove the more tag, it screws things up
							$('#more-'+postid).replaceWith('<hr>');
					
							var chapter = $('.entry-content').html();
							$('.entry-content').addClass('bookholder');
							$('.entry-content').html(\"<div id='pagemenu' style='height: 0px;'></div><div id='leaves-book' class='leaves-pages-rendering'><div id='b-load' class='b-load'></div></div> \");
							$('.entry-content').removeClass('entry-content').append(
								'<div class=\"leaves-load\"><img src=\"/theme/load.gif\"></div>'
							);
						
							// do some preprocessing up in this bitch
							chapter = chapter.replace(/<p>/g, \"<br> \");
							chapter = chapter.replace(/<\/p>/g, \"<br> \");
					
							chapterBits = chapter.split(\" \");
							var textBuffer = \"\";
							var initial = false;
							var initialPos = false;
							var curPage = 1;
						
							//scrape out the chapter title and header
							var chapterheader = $('#chapterheader').html();
							var chaptertitle = $('#chaptertitle').html();
					
							var header = \"<span id='chapterheaders'>\"+chapterheader+\"<br><span>\"+chaptertitle+\"</span></span>\";
					
							$('#b-load').append(\"<div id='leaves-page-\"+curPage+\"' class='leaves-pages'>\"+header+\"<div id='inner-\"+curPage+\"'></div></div>\");
						
							// now that one of our pages exists, we'll define our operating parameters
							// based on its CSS-styled dimensions
					
							pageHeight = 525;
					
							function isAlpha(val)
							{
								// True if val is a single alphabetic character.
								var re = /^([a-zA-Z])$/;
								return (re.test(val));
							}
					
							if ($.browser.safari){
								browser_treats_urls_like_safari_does = false;
								var last_location_hash = location.hash;
								location.hash = '\"blah\"';
								if (location.hash == '#%22blah%22')
								    browser_treats_urls_like_safari_does = true;
								location.hash = last_location_hash;
						
								if (browser_treats_urls_like_safari_does) {
									pageHeight = pageHeight - 40;
								}

							}
						
					
							var mozilla = $.browser.mozilla;
					
							// spool the text out into pages
							for (i = 0; i < chapterBits.length; i++) {
						
								if (i % 1 === 0) {  //change \"1\" to check bounds less frequently (saving CPU)
									var curHeight = $('#inner-'+curPage).innerHeight();
														
									if (curHeight > pageHeight){ //
										//alert(curHeight+\" \"+pageHeight+\" \"+curPage);
								
										var backbuffer = $('#inner-'+curPage).html();
										i--;
										$('#inner-'+curPage).html(backbuffer.substring(0,backbuffer.length-chapterBits[i].length-1));
										curPage++;
										$('#b-load').append(\"<div id='leaves-page-\"+curPage+\"' class='leaves-pages'><div id='inner-\"+curPage+\"'></div></div>\");
									
										//advance the chapterBits pointer past any opening <br>s
										var brfound = true;
										while (brfound){
											if (chapterBits[i] != \"<br>\") {
												brfound = false;
											} else {
												i++;
											}
										}
									
									}
								}
						
									// some extra checks for the initial beautification
									if (curPage == 1 &&! initial) {
										//if (chapterBits[i].indexOf(\"<hr>\") > -1) {
											initialPos = true;
										//}
								
										if (initialPos &&! initial){
											if (chapterBits[i].indexOf('<br') != -1){
												i++;
											}
											var alpha = false;
											var j = 0;
											while (!alpha) {
												if (isAlpha(chapterBits[i].substring(j,j+1))){
													if (chapterBits[i].indexOf('“') > -1){
														var restorequote = \"<span class='bigquote-pre'>“</span>\";
													} else {
														var restorequote = \"\";
													}
													chapterBits[i] = chapterBits[i].substring(j);
													alpha = true;
												
													}
												else {
												j++;}
											}
											//if (chapterBits[i].indexOf('<')  == -1 || chapterBits[i].indexOf('>')  == -1) {
												initial = true;
												var glyph = chapterBits[i].substring(0,1).toLowerCase();
												$('#inner-1').html('');
												chapterBits[i] = restorequote+\"<img src='".plugins_url()."/leaves/glyphs/\"+glyph+\".png' class='glyph-pre'>\"+chapterBits[i].substring(1);
											//}
										}
								
									}
								
								
									$('#inner-'+curPage).append(chapterBits[i]+\" \");
							
									//textBuffer = textBuffer+chapterBits[i]+\" \";
						
							}
					
							//$('.leaves-book').html(textBuffer);
							$('.leaves-pages-rendering').toggleClass('leaves-pages-rendering');
							$('.glyph-pre').toggleClass('glyph-pre').toggleClass('glyph');
							$('.bigquote-pre').toggleClass('bigquote-pre').toggleClass('bigquote');
							$('.leaves-load').remove();
					
					
						//add in the chapter linkage
						var curHeight = $('#inner-'+curPage).innerHeight();
					
						if (curHeight > 450) {
							curPage++;
							$('#b-load').append(\"<div id='leaves-page-\"+curPage+\"' class='leaves-pages'><div id='inner-\"+curPage+\"'></div></div>\");
						}
					
						$(\"#inner-\"+curPage).append(nextlink);
					
						//initialize book!
						//alert($('#leaves-book').html());
						//$('.leaves-pages').toggleClass('leaves-pages');
						$('#leaves-book').booklet({
							hash: true,
							keyboard: true,
							width: 776,
							height: 580,
							speed: 400,
							overlays: true,
							menu: '#pagemenu',
							pageSelector: true


				
						});
				
						//initialize mousewheel support
						$('#leaves-book').bind('mousewheel', function(event, delta){
							event.preventDefault();
							if (delta == 1) {
								$('#leaves-book').booklet(\"prev\");
							} else {
								$('#leaves-book').booklet(\"next\");
							}
						});
					
						//prepare the tooltips
					
						var tooltips = $('.pagehelp-holder');
						$('.pagehelp-holder').remove();
						$('#pagemenu').append(tooltips);
						$('.pagehelp-holder').toggleClass('pagehelp').toggleClass('pagehelp-holder');
						$('.pagehelp-body').hide();
					
						$('.pagehelp-button').mouseover(function(){
							$('.pagehelp-body').show();
							$('.pagehelp-button').css('background-image',\"url('/theme/helpicon-hover.png')\");
						});
					
						$('.pagehelp-button').mouseout(function(){
							$('.pagehelp-body').hide();
							$('.pagehelp-button').css('background-image',\"url('/theme/helpicon-reg.png')\");
						});
					
					
					
					});
				</script>";
			
	return $scripts;
}
?>
