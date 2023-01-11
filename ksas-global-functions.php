<?php
/**
 * KSAS Global Functions
 *
 * @package     KSAS Global Functions
 * @author      KSAS Communications
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: KSAS Global Functions
 * Plugin URI: https://github.com/ksascomm/plugin_global_functions
 * Description: This plugin should be network activated. Provides functions to change "Posts" labels to "News", sets up walker for sidebar menus, sets available blocks, removes unwanted widgets, and more.
 * Version: 3.0
 * Author: KSAS Communications
 * Author URI:  https://krieger.jhu.edu
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

/*****************TABLE OF CONTENTS***************
	1.0 Remove Unwanted Widgets
	2.0 Change Posts Labels to News
	3.0 Miscellaneous functions
		3.1 Obfuscate Email address email_munge($string);
		3.2 Create Title for <head> section
	4.0 Navigation and Menus
		4.1 Walker class for tertiary/sidebar links
	5.0 Global Shortcodes
		5.1 Custom Menu
	6.0 Editor
		6.1 Restrict majority of blocks to non-admins
		6.2 Allow uploads on custom post types
	7.0 Login Screen
	8.0 Toolbar Changes
		8.1 Remove comments node
	9.0 Image Accessibility
	10.0 Security
/*************************************
 * 1.0 Remove unwanted widgets
 */
function unregister_default_wp_widgets() {
	unregister_widget( 'WP_Widget_Pages' );
	unregister_widget( 'WP_Widget_Calendar' );
	unregister_widget( 'WP_Widget_Archives' );
	unregister_widget( 'WP_Widget_Meta' );
	unregister_widget( 'WP_Widget_Categories' );
	unregister_widget( 'WP_Widget_Recent_Comments' );
	unregister_widget( 'WP_Widget_RSS' );
	unregister_widget( 'WP_Widget_Tag_Cloud' );
}
add_action( 'widgets_init', 'unregister_default_wp_widgets', 1 );


/*************************************
 * 2.0 Change "Posts" to "News" in admin
 */

/**
 * Change Post Label
 */
function change_post_label() {
	global $menu;
	global $submenu;
	$menu[5][0]                 = 'News';
	$submenu['edit.php'][5][0]  = 'News';
	$submenu['edit.php'][10][0] = 'Add News';
	$submenu['edit.php'][16][0] = 'News Tags';
	echo '';
}

/**
 * Change Post Object
 */
function change_post_object() {
	global $wp_post_types;
	$labels                     = &$wp_post_types['post']->labels;
	$labels->name               = 'News';
	$labels->singular_name      = 'News';
	$labels->add_new            = 'Add News';
	$labels->add_new_item       = 'Add News';
	$labels->edit_item          = 'Edit News';
	$labels->new_item           = 'News';
	$labels->view_item          = 'View News';
	$labels->search_items       = 'Search News';
	$labels->not_found          = 'No News found';
	$labels->not_found_in_trash = 'No News found in Trash';
	$labels->all_items          = 'All News';
	$labels->menu_name          = 'News';
	$labels->name_admin_bar     = 'News';
}
add_action( 'init', 'change_post_object' );
add_action( 'admin_menu', 'change_post_label' );

/*************************************
 * 3.0 Miscellaneous functions
 */

/**
 * Obfuscate Email Address
 */
function email_munge( $string ) {
	$ascii_string = '';
	foreach ( str_split( $string ) as $char ) {
		$ascii_string .= '&#' . ord( $char ) . ';';
	}
	return $ascii_string;
}

/*************************************
 * 4.0 Navigation & Menu Functions
 */

/**
 * Menu Walker for Tertiary/Sidebar links.
 * Limits sidebar links to just that parent section.
 */
function submenu_limit( $items, $args ) {

	if ( empty( $args->submenu ) ) {
		return $items;
	}

	$filter_object_list = wp_filter_object_list( $items, array( 'title' => $args->submenu ), 'and', 'ID' );
	$parent_id          = array_pop( $filter_object_list );
	$children           = submenu_get_children_ids( $parent_id, $items );

	foreach ( $items as $key => $item ) {

		if ( ! in_array( $item->ID, $children ) ) {
			unset( $items[ $key ] );
		}
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'submenu_limit', 10, 2 );

/**
 * Get Children IDs in submenu
 */
function submenu_get_children_ids( $id, $items ) {

	$ids = wp_filter_object_list( $items, array( 'menu_item_parent' => $id ), 'and', 'ID' );

	foreach ( $ids as $id ) {

			$ids = array_merge( $ids, submenu_get_children_ids( $id, $items ) );
	}

	return $ids;
}

/*************************************
 * 5.0 Shortcodes
 */

/**
 * Custom Menu Shortcode
 */
function ksas_custom_menu_shortcode( $attr ) {

	$args = shortcode_atts(
		array(
			'name'  => '',
			'class' => '',
		),
		$attr
	);

	return wp_nav_menu(
		array(
			'menu'       => $args['name'],
			'menu_class' => $args['class'],
			'echo'       => false,
		)
	);
}

add_shortcode( 'custommenu', 'ksas_custom_menu_shortcode' );

/*************************************
 * 6.0 Editor
 */

/**
 * Restrict majority of blocks to non-admins
 */
add_filter( 'allowed_block_types_all', 'restrict_blocks' );

function restrict_blocks( $allowed_blocks ) {
	if ( ! is_super_admin() ) {
			$allowed_blocks = array(
				'core/block',
				'core/image',
				'core/gallery',
				'core/paragraph',
				'core/heading',
				'core/list',
				'core/list-item',
				'core/quote',
				'core/button',
				'core/separator',
				'core/embed',
				'core/shortcode',
				'core/table',
				'core/media-text',
				'core/buttons',
				'core/columns',
				'core/column',
				'core/cover',
				'core/group',
				'core/spacer',
				'ksas-block/ksas-callouts',
				'tribe/event-datetime',
				'tribe/event-organizer',
				'tribe/event-venue',
				'tribe/event-website',
				'tribe/event-links',
				'pb/accordion-item',
			);
	}
	return $allowed_blocks;
}

/**
 * Update WP-Admin CSS: remove h1, h5, h6 heading block options
 *
 * @link https://davidwalsh.name/add-custom-css-wordpress-admin
 */
function admin_style() {
	if ( ! is_super_admin() ) {
		?>
	<style type="text/css">
		.components-button.components-dropdown-menu__menu-item.is-icon-only.has-icon[aria-label="Heading 1"], .components-button.components-dropdown-menu__menu-item.is-icon-only.has-icon[aria-label="Heading 5"], .components-button.components-dropdown-menu__menu-item.is-icon-only.has-icon[aria-label="Heading 6"] { 
			display: none;
		}
	</style>
		<?php
	}
}
add_action( 'admin_head', 'admin_style' );


/**
 * Allow file uploads on custom post types
 */
function ecpt_export_ui_scripts() {
	global $ecpt_options;
	?>
	 
		<script type="text/javascript">
		jQuery(document).ready(function($)
			{

				if($('.form-table .ecpt_upload_field').length > 0 ) {
					// Media Uploader
					window.formfield = '';

					$('.ecpt_upload_image_button').on('click', function() {
						var send_attachment_bkp = wp.media.editor.send.attachment;
						var button = $(this);

			wp.media.editor.send.attachment = function(props, attachment) {

				$(button).prev().prev().attr('src', attachment.url);
				$(button).prev().val(attachment.url);

				wp.media.editor.send.attachment = send_attachment_bkp;
			};

			wp.media.editor.open(button);

			return false;       
		});
						window.original_send_to_editor = window.send_to_editor;
						window.send_to_editor = function(html) {
							if (window.formfield) {
								imgurl = $('a','<div>'+html+'</div>').attr('href');
								window.formfield.val(imgurl);
								tb_remove();
							}
							else {
								window.original_send_to_editor(html);
							}
							window.formfield = '';
							window.imagefield = false;
						};
				}			
				// add new repeatable field
				$(".ecpt_add_new_field").on('click', function() {					
					var field = $(this).closest('td').find("div.ecpt_repeatable_wrapper:last").clone(true);
					var fieldLocation = $(this).closest('td').find('div.ecpt_repeatable_wrapper:last');
					// get the hidden field that has the name value
					var name_field = $("input.ecpt_repeatable_field_name", ".ecpt_field_type_repeatable:first");
					// set the base of the new field name
					var name = $(name_field).attr("id");
					// set the new field val to blank
					$('input', field).val("");

					// set up a count var
					var count = 0;
					$('.ecpt_repeatable_field').each(function() {
						count = count + 1;
					});
					name = name + '[' + count + ']';
					$('input', field).attr("name", name);
					$('input', field).attr("id", name);
					field.insertAfter(fieldLocation, $(this).closest('td'));

					return false;
				});

				// add new repeatable upload field
				$(".ecpt_add_new_upload_field").on('click', function() {	
					var container = $(this).closest('tr');
					var field = $(this).closest('td').find("div.ecpt_repeatable_upload_wrapper:last").clone(true);
					var fieldLocation = $(this).closest('td').find('div.ecpt_repeatable_upload_wrapper:last');
					// get the hidden field that has the name value
					var name_field = $("input.ecpt_repeatable_upload_field_name", container);
					// set the base of the new field name
					var name = $(name_field).attr("id");
					// set the new field val to blank
					$('input[type="text"]', field).val("");

					// set up a count var
					var count = 0;
					$('.ecpt_repeatable_upload_field', container).each(function() {
						count = count + 1;
					});
					name = name + '[' + count + ']';
					$('input', field).attr("name", name);
					$('input', field).attr("id", name);
					field.insertAfter(fieldLocation, $(this).closest('td'));

					return false;
				});

				// remove repeatable field
				$('.ecpt_remove_repeatable').on('click', function(e) {
					e.preventDefault();
					var field = $(this).parent();
					$('input', field).val("");
					field.remove();				
					return false;
				});

			});
	</script>
			<?php
}
if ( ( isset( $_GET['post'] ) && ( isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) ) || ( strstr( $_SERVER['REQUEST_URI'], 'wp-admin/post-new.php' ) ) ) {
	add_action( 'admin_head', 'ecpt_export_ui_scripts' );
}

/*************************************
 * 7.0 Login Screen
 */

/**
 * Show message if user is attempting to login by bypassing JHED
 */
function wps_login_message( $message ) {
	if ( empty( $message ) ) {
		return "<p class='message'><strong>NOTE:</strong> We are currently reconfiguring the JHED Authentication plugin. <br><br>Please <strong>DO NOT</strong> login with your JHED or click <em>Lost Your Password?</em> in the fields below. You will be able to log in with your JHED shortly. <br><br>If you need immediate edits to your website, please use our <a href='http://sites.krieger.jhu.edu/forms/request-service/'>web service request form</a> or email <a href='mailto:ksasweb@jhu.edu'>ksasweb@jhu.edu</a>.<br><br>Thank you for your patience. </p>";
	} else {
		return $message;
	}
}
add_filter( 'login_message', 'wps_login_message' );


/*************************************
 * 8.0 Toolbar Changes
 */

/**
 * Remove comments node
 */
function my_admin_bar_render() {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu( 'comments' );
}
	add_action( 'wp_before_admin_bar_render', 'my_admin_bar_render' );


/*************************************
 * 9.0 Image Accessibility
 */

/**
 * Images Without Alt Text
 */
function add_css_head() {
	if ( is_user_logged_in() ) :
		?>
		<style>
				img[alt=""], img:not([alt]) {
					border: 4px red dashed !important;
				}
				#slb_viewer_wrap .slb_theme_slb_baseline .slb_template_tag_item_content> img:not([alt]) {
					border: none !important;
				}
		</style>
		<?php
	endif;
}
	add_action( 'wp_head', 'add_css_head' );

/*************************************
 * 9.0 Security
 */

/**
 * Allow only selected, trusted domains in the Access-Control-Allow-Origin header
 */
	remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
	add_filter(
		'rest_pre_serve_request',
		function( $value ) {
			$origin = get_http_origin();
			if ( $origin && in_array(
				$origin,
				array(
					'https://krieger.jhu.edu',
				)
			) ) {
				header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
				header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
				header( 'Access-Control-Allow-Credentials: true' );
			}
			return $value;

		}
	);
	?>
