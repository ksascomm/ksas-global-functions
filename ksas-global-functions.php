<?php
/**
 * KSAS Global Functions
 *
 * @package     KSAS_Global_Functions
 * @author      KSAS Communications
 * @license     GPL-2.0-or-later
 * @link        https://github.com/ksascomm/plugin_global_functions
 *
 * @wordpress-plugin
 * Plugin Name: KSAS Global Functions
 * Plugin URI:  https://github.com/ksascomm/plugin_global_functions
 * Description: Network activated plugin. Renames "Posts" to "News", manages sidebar walkers, restricts block types, and handles cache purging.
 * Version:     4.0
 * Author:      KSAS Communications
 * Author URI:  https://krieger.jhu.edu
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
==========================================================================
	1.0 Admin Labels & UI (Posts to News)
=============================================================================
*/

/**
 * Rename "Posts" menu to "News" in the admin sidebar.
 */
function ksas_change_post_label() {
	global $menu, $submenu;
	/**
	 * We must override these globals to rename core menu items.
	 * phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
	 */
	if ( isset( $menu[5] ) ) {
		$menu[5][0] = 'News'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
	if ( isset( $submenu['edit.php'] ) ) {
		$submenu['edit.php'][5][0]  = 'News';
		$submenu['edit.php'][10][0] = 'Add News';
		$submenu['edit.php'][16][0] = 'News Tags';
	}
	// phpcs:enable
}
add_action( 'admin_menu', 'ksas_change_post_label' );

/**
 * Rename Post object labels globally.
 */
function ksas_change_post_object() {
	global $wp_post_types;
	if ( ! isset( $wp_post_types['post'] ) ) {
		return;
	}
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
add_action( 'init', 'ksas_change_post_object' );

/*
==========================================================================
	2.0 Cache Purging (W3TC)
=============================================================================
*/

/**
 * Purge specific Event URLs in W3TC on Tribe Event update.
 *
 * @param int     $post_id The post ID.
 * @param WP_Post $post    The post object.
 */
function ksas_purge_w3tc_event_pages( $post_id, $post ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! function_exists( 'w3tc_pgcache_flush_url' ) ) {
		return;
	}

	$pages_to_purge = array(
		'https://anthropology.jhu.edu/events/',
		'https://arthist.jhu.edu/events/',
		'https://bio.jhu.edu/events/',
		'https://biophysics.jhu.edu/events/',
		'https://cbi.jhu.edu/events/',
		'https://chemistry.jhu.edu/events/',
		'https://classics.jhu.edu/events/',
		'https://cogsci.jhu.edu/events/',
		'https://compthoughtlit.jhu.edu/events/',
		'https://econ.jhu.edu/events/',
		'https://english.jhu.edu/events/',
		'https://eps.jhu.edu/events/',
		'https://history.jhu.edu/events/',
		'https://host.jhu.edu/events/',
		'https://krieger.jhu.edu/africana/events/',
		'https://krieger.jhu.edu/arrighi/events/',
		'https://krieger.jhu.edu/behaviorialbiology/events/',
		'https://krieger.jhu.edu/east-asian/events/',
		'https://krieger.jhu.edu/humanities-institute/events/',
		'https://krieger.jhu.edu/humanities-institute/events/jhu-humanities-events/',
		'https://krieger.jhu.edu/internationalstudies/events/',
		'https://krieger.jhu.edu/jewishstudies/events/',
		'https://krieger.jhu.edu/laclxs/events/',
		'https://krieger.jhu.edu/mbi/events/',
		'https://krieger.jhu.edu/modern-languages-literatures/events/',
		'https://krieger.jhu.edu/neuroscience/about/events/',
		'https://krieger.jhu.edu/publichealth/about/events/',
		'https://krieger.jhu.edu/ursca/about/events-calendar/',
		'https://krieger.jhu.edu/wgs/events/',
		'https://mathematics.jhu.edu/events/',
		'https://neareast.jhu.edu/events/',
		'https://pbs.jhu.edu/events/',
		'https://philosophy.jhu.edu/events/',
		'https://physics-astronomy.jhu.edu/events/',
		'https://politicalscience.jhu.edu/events/',
		'https://soc.jhu.edu/about/event-calendar/',
		'https://writingseminars.jhu.edu/events/',
	);

	foreach ( $pages_to_purge as $url ) {
		w3tc_pgcache_flush_url( $url );
	}
}
add_action( 'save_post_tribe_events', 'ksas_purge_w3tc_event_pages', 20, 2 );

/*
==========================================================================
	3.0 Navigation & Menu Functionality
=============================================================================
*/

/**
 * Filter sidebar links to show only current section children.
 *
 * @param array    $items The menu items.
 * @param stdClass $args  The menu arguments.
 * @return array
 */
function ksas_submenu_limit( $items, $args ) {
	if ( empty( $args->submenu ) ) {
		return $items;
	}

	$filter_object_list = wp_filter_object_list( $items, array( 'title' => $args->submenu ), 'and', 'ID' );
	$parent_id          = array_pop( $filter_object_list );
	$children           = ksas_get_menu_children_ids( $parent_id, $items );

	foreach ( $items as $key => $item ) {
		if ( ! in_array( $item->ID, $children, true ) ) {
			unset( $items[ $key ] );
		}
	}
	return $items;
}
add_filter( 'wp_nav_menu_objects', 'ksas_submenu_limit', 10, 2 );

/**
 * Get Children IDs in a recursive menu structure.
 *
 * @param int   $id    The parent ID.
 * @param array $items The full menu items array.
 * @return array
 */
function ksas_get_menu_children_ids( $id, $items ) {
	$ids = wp_filter_object_list( $items, array( 'menu_item_parent' => $id ), 'and', 'ID' );
	foreach ( $ids as $child_id ) {
		$ids = array_merge( $ids, ksas_get_menu_children_ids( $child_id, $items ) );
	}
	return $ids;
}

/*
==========================================================================
	4.0 Editor Restrictions & Admin Cleaning
=============================================================================
*/

/**
 * Restrict Block Types for Non-Super Admins.
 *
 * @param array $allowed_blocks The default allowed blocks.
 * @return array
 */
function ksas_restrict_blocks( $allowed_blocks ) {
	if ( ! is_super_admin() ) {
		return array(
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
add_filter( 'allowed_block_types_all', 'ksas_restrict_blocks' );

/**
 * Remove H1, H5, and H6 from the Heading block for non-super admins.
 *
 * @param array  $args       The block type arguments.
 * @param string $block_type The block type name.
 * @return array
 */
function ksas_limit_heading_levels( $args, $block_type ) {
	// If the user is a Super Admin, do nothing.
	if ( is_super_admin() ) {
		return $args;
	}

	// Only apply to the core Heading block.
	if ( 'core/heading' === $block_type ) {
		// Restrict available levels to 2, 3, and 4.
		$args['attributes']['levelOptions']['default'] = array( 2, 3, 4 );
	}

	return $args;
}
add_filter( 'register_block_type_args', 'ksas_limit_heading_levels', 10, 2 );

/**
 * Hide Sticky Options via CSS.
 */
function ksas_admin_styles() {
	if ( is_super_admin() ) {
		return;
	}
	?>
	<style type="text/css">
		/* Quick Edit: Hide Sticky */
		.quick-edit-row label.alignleft:last-child { display: none !important; }
		
		/* Sidebar: Hide Sticky */
		.edit-post-post-status .components-panel__row:has(label:contains("Stick to the top")) { display: none !important; }
	</style>
	<?php
}
add_action( 'admin_head', 'ksas_admin_styles' );
add_action( 'admin_print_styles', 'ksas_admin_styles' );

/**
 * JavaScript to remove Sticky Post checkbox in Block Editor.
 */
function ksas_hide_sticky_checkbox_js() {
	if ( is_super_admin() ) {
		return;
	}
	?>
	<script>
		(function($){
			const hideSticky = () => {
				$('.components-checkbox-control__label').each(function(){
					if ($(this).text().trim() === 'Stick to the top of the blog') {
						$(this).closest('.components-panel__row').hide();
					}
				});
			};
			$('body').on('DOMNodeInserted', '.edit-post-sidebar', hideSticky);
		})(jQuery);
	</script>
	<?php
}
add_action( 'admin_footer-post.php', 'ksas_hide_sticky_checkbox_js' );
add_action( 'admin_footer-post-new.php', 'ksas_hide_sticky_checkbox_js' );

/*
==========================================================================
	5.0 Widgets & Dashboard
=============================================================================
*/

/**
 * Remove unwanted default widgets.
 */
function ksas_unregister_widgets() {
	unregister_widget( 'WP_Widget_Pages' );
	unregister_widget( 'WP_Widget_Calendar' );
	unregister_widget( 'WP_Widget_Archives' );
	unregister_widget( 'WP_Widget_Meta' );
	unregister_widget( 'WP_Widget_Categories' );
	unregister_widget( 'WP_Widget_Recent_Comments' );
	unregister_widget( 'WP_Widget_RSS' );
	unregister_widget( 'WP_Widget_Tag_Cloud' );
}
add_action( 'widgets_init', 'ksas_unregister_widgets', 11 );

/**
 * Clean up Dashboard metaboxes.
 */
function ksas_remove_dashboard_widgets() {
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
	remove_meta_box( 'tribe_dashboard_widget', 'dashboard', 'side' );
	remove_meta_box( 'wpseo-dashboard-overview', 'dashboard', 'side' );
}
add_action( 'admin_init', 'ksas_remove_dashboard_widgets' );

/*
==========================================================================
	6.0 Security & Accessibility
=============================================================================
*/

/**
 * Highlight images missing ALT tags for logged-in users.
 */
function ksas_accessibility_alt_check() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	?>
	<style>
		img[alt=""], img:not([alt]) { border: 4px red dashed !important; }
		#slb_viewer_wrap img { border: none !important; }
	</style>
	<?php
}
add_action( 'wp_head', 'ksas_accessibility_alt_check' );

/**
 * REST API CORS Security filter.
 */
remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
add_filter(
	'rest_pre_serve_request',
	function ( $value ) {
		$origin          = get_http_origin();
		$allowed_origins = array( 'https://krieger.jhu.edu' );
		if ( $origin && in_array( $origin, $allowed_origins, true ) ) {
			header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
			header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
			header( 'Access-Control-Allow-Credentials: true' );
		}
		return $value;
	}
);

/*
==========================================================================
	7.0 Shortcodes & Helpers
=============================================================================
*/

/**
 * [custommenu name="Menu Name" class="my-class"] shortcode.
 */
add_shortcode(
	'custommenu',
	function ( $attr ) {
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
);

/**
 * Convert string to ASCII to prevent email scraping.
 *
 * @param string $string The email address.
 * @return string
 */
function ksas_email_munge( $string ) {
	$ascii_string = '';
	$chars        = str_split( $string );
	foreach ( $chars as $char ) {
		$ascii_string .= '&#' . ord( $char ) . ';';
	}
	return $ascii_string;
}

/**
 * Add a custom notice to the login screen.
 */
add_filter(
	'login_message',
	function ( $message ) {
		if ( empty( $message ) ) {
			return wp_kses_post( "<p class='message'><strong>NOTE:</strong> If you are seeing this message, it is probably in error or we are currently reconfiguring the JHED authentication plugin. <br><br><strong>DO NOT use this form to login with your JHED, or click the <em>Lost Your Password?</em> option below.</strong> <br><br>Please use our <a href='http://sites.krieger.jhu.edu/forms/request-service/'>web service request form</a> or email <a href='mailto:ksasweb@jhu.edu'>ksasweb@jhu.edu</a>.<br><br>Thank you. </p>" );
		}
		return $message;
	}
);

/**
 * Remove Comments node from the Admin Bar.
 */
add_action(
	'wp_before_admin_bar_render',
	function () {
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu( 'comments' );
	}
);