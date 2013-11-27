<?php 
/*
Plugin Name: fr-texturize
Plugin URI: http://hupkens.be/fr-texturize
Description: A quick and dirty plugin to get Wordpress to use proper French typography
Version: 1.0
Author: Thomas Hupkens
Author URI: http://hupkens.be
License: GPL2
*/


foreach ( array ( 'bloginfo','comment_text','comment_author','link_name','link_description',
				  'link_notes','list_cats','single_post_title','single_cat_title','single_tag_title',
				  'single_month_title','term_description','term_name','the_content','the_excerpt',
				  'the_title','nav_menu_attr_title','nav_menu_description','widget_title','wp_title' ) as $target ){
	remove_filter( $target, 'wptexturize' );
	add_filter( $target, 'fr_texturize');
}

function fr_texturize($text){
	$default_no_texturize_tags = array('pre', 'code', 'kbd', 'style', 'script', 'tt');
	$default_no_texturize_shortcodes = array('code');
	
	$static = array(
		'---' => '&#8212;',
		' -- ' => ' &#8212; ',
		'--' => '&#8211;',
		' - ' => ' &#8211; ',
		// 'xn&#8211;' => 'xn--',
		'...' => '&#8230;',
		'``' => '&#171;~',
		'\'\'' => '~&#187;',
		' (tm)' => ' &#8482;',
	
		/* Remplacements supplémentaires */
		'(c)' => '&#169;',	
		'(r)' => '&#174;',

		"&nbsp;" => "~",
		"&raquo;" => "&#187;",
		"&laquo;" => "&#171;",
		"&rdquo;" => "&#8221;",
		"&ldquo;" => "&#8220;",
		"&deg;" => "&#176;",

		" " => "~",
		"»" => "&#187;",
		"«" => "&#171;",
		"”" => "&#8221;",
		"“" => "&#8220;",
		"°" => "&#176;"
	);

	$dynamic = array(
		/* Wordpress replacements */
		'/\'(\d\d(?:&#8217;|\')?s)/' => '&#8217;$1', // '99's
		'/\'(\d)/'                   => '&#8217;$1', // '99
		'/(\s|\A|[([{<]|")\'/'       => '$1&#8216;', // opening single quote, even after (, {, <, [
		'/(\d)"/'                    => '$1&#8243;', // 9" (double prime)
		'/(\d)\'/'                   => '$1&#8242;', // 9' (prime)
		'/(\S)\'([^\'\s])/'          => '$1&#8217;$2', // apostrophe in a word
		'/(\s|\A|[([{<])"(?!\s)/'    => '$1&#171;~$2', // opening double quote, even after (, {, <, [
		'/"(\s|\S|\Z)/'              => '~&#187;$1', // closing double quote
		'/\'([\s.]|\Z)/'             => '&#8217;$1', // closing single quote
		'/\b(\d+)x(\d+)\b/'          => '$1&#215;$2', // 9x9 (times)
		
		/* Advances replacements. Inpired by typographie_fr_dist() from SPIP CMS */
		'/((?:^|[^\#0-9a-zA-Z\&])[\#0-9a-zA-Z]*)\;/S' => '\1~;',
		'/&#187;| --?,|(?::| %)(?:\W|$)/S' => '~\0',
		'/([^[<(!?.])([\!\?][!?\.]*)/i' => '\1~\2',
		'/&#171;|(?:M(?:M?\.|mes?|r\.?)|[MnN]&#176;) /S' => '\0~',
		'/ *~+ */' => '~',
		',(http|https|ftp|mailto)~((://[^"\'\s\[\]\}\)<>]+)~([?]))?,S' => '\1\3\4',
		'/~+/' => '&nbsp;'
	);


	$dynamic_characters = array_keys( $dynamic );
	$dynamic_replacements = array_values( $dynamic );
	
	$no_texturize_tags = '(' . implode('|', apply_filters('no_texturize_tags', $default_no_texturize_tags) ) . ')';
	$no_texturize_shortcodes = '(' . implode('|', apply_filters('no_texturize_shortcodes', $default_no_texturize_shortcodes) ) . ')';

	$no_texturize_tags_stack = array();
	$no_texturize_shortcodes_stack = array();
	
	$textarr = preg_split('/(<.*>|\[.*\])/Us', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

	foreach ( $textarr as &$curl ) {
		if ( empty( $curl ) )
			continue;

		// Only call _wptexturize_pushpop_element if first char is correct tag opening
		$first = $curl[0];
		if ( '<' === $first ) {
			_wptexturize_pushpop_element($curl, $no_texturize_tags_stack, $no_texturize_tags, '<', '>');
		} elseif ( '[' === $first ) {
			_wptexturize_pushpop_element($curl, $no_texturize_shortcodes_stack, $no_texturize_shortcodes, '[', ']');
		} elseif ( empty($no_texturize_shortcodes_stack) && empty($no_texturize_tags_stack) ) {
			

			// This is not a tag, nor is the texturization disabled static strings
			$curl = str_replace(array_keys($static), array_values($static), $curl);
			//$curl = str_replace($static_characters, $static_replacements, $curl);
			$curl = preg_replace($dynamic_characters, $dynamic_replacements, $curl);
		}
		$curl = preg_replace('/&([^#])(?![a-zA-Z1-4]{1,8};)/', '&#038;$1', $curl);
	}
	return implode( '', $textarr );
}
