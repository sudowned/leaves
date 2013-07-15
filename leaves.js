<script>
					//begin leaves
					
					$(document).ready(function() {
									
							var postid = $('.post').attr('id').substring(5);
							
							var AfterHeight = $('#chapterlink').height() + $('#LeavesFinalPageContent').height();
							var nextlink = $('#chapterlink').remove();
							
							var LeavesFinalPageContent = $('#LeavesFinalPageContent').remove();
						
							//remove the more tag, it screws things up
							$('#more-'+postid).replaceWith('<hr>');
					
							var chapter = $('.entry-content').html();
							$('.entry-content').addClass('bookholder');
							$('.entry-content').html("<div id='pagemenu' style='height: 0px;'></div><div id='leaves-book' class='leaves-pages-rendering'><div id='b-load' class='b-load'></div></div> ");
							$('.entry-content').removeClass('entry-content').append(
								'<div class="leaves-load"><img src="/wp-content/plugins/leaves/load.gif"></div>'
							);
						
							// do some preprocessing up in this bitch
							chapter = chapter.replace(/<p>/g, "<br> ");
							chapter = chapter.replace(/<\/p>/g, "<br> ");
					
							chapterBits = chapter.split(" ");
							var textBuffer = "";
							var initial = false;
							var initialPos = false;
							var curPage = 1;
						
							//scrape out the chapter title and header
							var chapterheader = $('#chapterheader').html();
							var chaptertitle = $('#chaptertitle').html();
					
							var header = "<span id='chapterheaders'>"+chapterheader+"<br><span>"+chaptertitle+"</span></span>";
					
							$('#b-load').append("<div id='leaves-page-"+curPage+"' class='leaves-pages'>"+header+"<div id='inner-"+curPage+"'></div></div>");
						
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
								location.hash = '"blah"';
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
						
								if (i % 1 === 0) {  //change "1" to check bounds less frequently (saving CPU)
									var curHeight = $('#inner-'+curPage).innerHeight();
														
									if (curHeight > pageHeight){ //
										//alert(curHeight+" "+pageHeight+" "+curPage);
								
										var backbuffer = $('#inner-'+curPage).html();
										i--;
										$('#inner-'+curPage).html(backbuffer.substring(0,backbuffer.length-chapterBits[i].length-1));
										curPage++;
										$('#b-load').append("<div id='leaves-page-"+curPage+"' class='leaves-pages'><div id='inner-"+curPage+"'></div></div>");
									
										//advance the chapterBits pointer past any opening <br>s
										var brfound = true;
										while (brfound){
											if (chapterBits[i] != "<br>") {
												brfound = false;
											} else {
												i++;
											}
										}
									
									}
								}
						
									// some extra checks for the initial beautification
									if (curPage == 1 &&! initial) {
										//if (chapterBits[i].indexOf("<hr>") > -1) {
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
														var restorequote = "<span class='bigquote-pre'>“</span>";
													} else {
														var restorequote = "";
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
												chapterBits[i] = restorequote+"<img src='/wp-content/themes/leaves/glyphs/"+glyph+".png' class='glyph-pre'>"+chapterBits[i].substring(1);
											//}
										}
								
									}
								
								
									$('#inner-'+curPage).append(chapterBits[i]+" ");
							
									//textBuffer = textBuffer+chapterBits[i]+" ";
						
							}
					
							//$('.leaves-book').html(textBuffer);
							$('.leaves-pages-rendering').toggleClass('leaves-pages-rendering');
							$('.glyph-pre').toggleClass('glyph-pre').toggleClass('glyph');
							$('.bigquote-pre').toggleClass('bigquote-pre').toggleClass('bigquote');
							$('.leaves-load').remove();
					
					
						//add in the chapter linkage
						var curHeight = $('#inner-'+curPage).innerHeight();
					
						if (curHeight > AfterHeight) {
							curPage++;
							$('#b-load').append("<div id='leaves-page-"+curPage+"' class='leaves-pages'><div id='inner-"+curPage+"'></div></div>");
						}
						
						$(".leaves-pages").addClass('leaves-pages-complete');
						
						$("#inner-"+curPage).append(LeavesFinalPageContent);
						$("#inner-"+curPage).append(nextlink);
					
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
								$('#leaves-book').booklet("prev");
							} else {
								$('#leaves-book').booklet("next");
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
							$('.pagehelp-button').css('background-image',"url('/wp-content/plugins/leaves/helpicon-hover.png')");
						});
					
						$('.pagehelp-button').mouseout(function(){
							$('.pagehelp-body').hide();
							$('.pagehelp-button').css('background-image',"url('/wp-content/plugins/leaves/helpicon-reg.png')");
						});
					
					
					
					});
				</script>
