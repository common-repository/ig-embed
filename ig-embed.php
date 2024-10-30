<?php
/*
Plugin Name: Instagram Embedding
Plugin URI: http://wp-plugins.in/instagram-embed
Description: One shortcode to embedding instagram images with full customize and unlimited colors, caption support, fully responsive and easy to use.
Version: 1.0.0
Author: Alobaidi
Author URI: http://wp-plugins.in
License: GPLv2 or later
*/

/*  Copyright 2015 Alobaidi (email: wp-plugins@outlook.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


function alobaidi_instagram_embedding_plugin_row_meta( $links, $file ) {

	if ( strpos( $file, 'ig-embed.php' ) !== false ) {
		
		$new_links = array(
						'<a href="http://wp-plugins.in/instagram-embed" target="_blank">Explanation of Use</a>',
						'<a href="https://profiles.wordpress.org/alobaidi#content-plugins" target="_blank">More Plugins</a>',
						'<a href="http://j.mp/ET_WPTime_ref_pl" target="_blank">Elegant Themes</a>'
					);
		
		$links = array_merge( $links, $new_links );
		
	}
	
	return $links;
	
}
add_filter( 'plugin_row_meta', 'alobaidi_instagram_embedding_plugin_row_meta', 10, 2 );


/* Include Instagram Embedding Styles */
function alobaidi_instagram_embedding_style(){
	wp_enqueue_style( 'alobaidi-instagram-embedding-fontello', plugins_url( '/css/fontello.css', __FILE__ ), false, null);
	wp_enqueue_style( 'alobaidi-instagram-embedding-style', plugins_url( '/css/instagram-embedding-style.css', __FILE__ ), false, null);
}
add_action('wp_enqueue_scripts', 'alobaidi_instagram_embedding_style');


/* Instagram Embedding Shortcode */
function alobaidi_instagram_embedding_shortcode( $atts, $content = null ){ // Shortcode Function Start
	
	extract(
		shortcode_atts(
			array(
				"url"			=>	"", // $url var, default is none
				"before"		=>	"", // $before var, default is none, option: "lightbox" to activate lightbox link
				"wrap_margin"	=>	"20", // $wrap_margin var, default is 20px for margin top and bottom only
				"wrap_bg"		=>	"#ffffff", // $wrap_bg var, default color is white #ffffff
				"color"			=>	"#3f729b", // $color var, default color is #3f729b
				"text_color"	=>	"#ffffff", // $text_color var, default color is white #ffffff
				"caption"		=>	"full", // $caption var, default full, options: false, excerpt, full
				"icon_size"		=>	"34", // $icon_size var, default is 34px
				"font_size"		=>	"14", // $font_size var, default is 14px
				"s"				=>	"f" // $s var, default is f
			),$atts
		)
	);
	
	if( !empty($url) and preg_match("/(instagram.com)/", $url) ){ // Check if correct instagram link

		if( !preg_match("/(http:)|(https:)/", $url) ){ // If instagram link without http://
			return '<p>Please enter instagram link with http:// or https://</p>';
			return false;
		}
	
		$instagram_api	= wp_remote_get("http://api.instagram.com/publicapi/oembed/?url=$url"); // Instagram API Link with $url var
		$retrieve		= wp_remote_retrieve_body( $instagram_api ); // Retrieve Body
		$response		= json_decode($retrieve); // JSON Response

		if( preg_match('/(No Media Match)|(No URL Match)+/', $retrieve) ){ // If deleted link or error link
			return '<p>Sorry! Maybe error link or deleted link.</p>';
			return false;
		}else{ // If not deleted link

			$auther_url		= $response->author_url; // Get Auther Link
			$auther_name	= $response->author_name; // Get Auther Name

			$parm_regex = array("/[^&?]*?=[^&?]*/", "/[(?)]/");
			$preg_replace = preg_replace($parm_regex, "", $url);
			$image_link = rtrim($preg_replace, '/').'/media?size=l';
			$thumbnail_url	= $image_link; // Get Image Link

			$get_caption	= $response->title; // Get Image Caption
			$emoji_regex = array(
								'/[\x{2300}-\x{2999}]/u',
								'/[\x{1F300}-\x{1F900}]/u',
								'/[\x{FEB0C}]/u',
								'/[\x{E022}]/u',
								'/[\x{E595}]/u',
								'/[\x{E6EC}]/u',
								'/[\x{2764}]/u'
							); // Array For Emoji Icons

			/* Caption */
			if( !empty($get_caption) ){
				$alt_caption = ' alt="'.preg_replace($emoji_regex, '', $get_caption).'"';
			}else{
				$alt_caption = null;
			}

			if( $caption == 'false' or empty($get_caption) ){
				$div_caption  = null;
			}

			else{

				$caption_strlen	= mb_strlen( utf8_decode($get_caption) ); // Count Characters Of Caption
				$clean_caption = preg_replace($emoji_regex, '', $get_caption); // Remove Emoji Icons

				if( $caption == 'excerpt' and $caption_strlen >= 40 ){
					$caption_text = mb_substr($clean_caption, 0, 40, 'utf-8').' <a title="Read More" class="read_more_caption" href="'.$url.'" target="_blank" style="color:'.$text_color.';">...</a>';
					$div_caption  = '<div class="instagram_image_caption" style="color:'.$text_color.';background-color: '.$color.';font-size:'.$font_size.'px;">'.$caption_text.'</div>';
				}

				else{
					$caption_text = $clean_caption;
					$div_caption  = '<div class="instagram_image_caption" style="color:'.$text_color.';background-color: '.$color.';font-size:'.$font_size.'px;">'.$caption_text.'</div>';
				}

			}
			
			/* Lightbox */
			if( $before == "lightbox" ){ // Check if lightbox is activate
				$a_start 	=	'<a rel="nofollow" class="instagram_before lightbox_true" href="'.$thumbnail_url.'">';
				$a_end 		=	'</a>';
			}
			elseif( !empty($before) ){ // Check if have link before image
				$a_start 	=	'<a rel="nofollow" class="instagram_before" href="'.$before.'">';
				$a_end 		=	'</a>';
			}
			else{
				$a_start 	=	null;
				$a_end 		=	null;
			}
			
			/* Result */
			
			if( $s == 't' or $s == 'T' ){ // if standard image
				return '<p><img class="standard-instagram-image aligncenter" src="'.$thumbnail_url.'"'.$alt_caption.'></p>';
				return false;
			}
			
			// if not standard image
			return '
				<div class="instagram_embedding_wrap" style="margin:'.$wrap_margin.'px 0px;border:1px solid '.$color.';background-color:'.$wrap_bg.';">
					<div class="instagram_embedding_content">
						<div class="instagram_embedding_header">
							<div class="instagram_embedding_icon" style="color: '.$color.' !important;font-size:'.$icon_size.'px;"></div>
							<a rel="nofollow" class="instagram_author_url" target="_blank" href="'.$auther_url.'" style="color:'.$color.';font-size:'.$font_size.'px;">By '.$auther_name.'</a>
						</div>
						'.$a_start.'
						<img id="instagram_image_link" class="instagram_image_link" src="'.$thumbnail_url.'"'.$alt_caption.'>
						'.$a_end.'
					</div>
					'.$div_caption.'
				</div>';
		} // End if deleted link or error link
	
	} // End if correct instagram link
	
	else{ // If error instagram link
		return '<p>Please enter correct instagram link.</p>';
	}
	
} // Shortcode Function End
add_shortcode("instagram_embedding", "alobaidi_instagram_embedding_shortcode"); // Add shortcode [instagram_embed url=""]

?>