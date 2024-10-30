<?php
/*
Plugin Name: Cookie Wise
Plugin URI: http://jeroensmeets.net/wordpress/cookiewise/
Description: Show your visitors a message, telling them about the way your site uses cookies, and giving them an option to accept or deny cookie use.
Version: 1.1.4
Author: Jeroen Smeets
Author URI: http://jeroensmeets.net/
License: GPL2
*/

add_action('init', 'cw_cookiecheck');
function cw_cookiecheck() {

	$_cw_p = pathinfo($_SERVER['REQUEST_URI']);
	$_cw_p = trailingslashit($_cw_p['dirname']);

	// user accepts or rejects cookies
	if ((array_key_exists('_cookiewise', $_GET)) && (array_key_exists('_cwnonce', $_GET))) {

		// check nonce for accept
		if (('accept' == $_GET['_cookiewise']) && (wp_verify_nonce($_GET['_cwnonce'], 'cookiewise_accept_cookies'))) {
			// TODO: make expiration time a setting
			// set cookie for 30 days
			setcookie('cw_accept_cookies', 'accept', time()+60*60*24*30, '/');
			header("Location: " . $_cw_p);
			exit;
		}

		// check nonce for deny
		if (('reject' == $_GET['_cookiewise']) && (wp_verify_nonce($_GET['_cwnonce'], 'cookiewise_reject_cookies'))) {
			// TODO: make expiration time a setting
			// set cookie for 30 days
			setcookie('cw_reject_cookies', 'reject', time()+60*60*24*30, '/');
			header("Location: " . $_cw_p);
			exit;
		}
	}
}

// run cw_init() as late as possible
add_action('init', 'cw_init', PHP_INT_MAX);
function cw_init() {

	// TODO: don't add statusbar for logged in users
	//	if (is_user_logged_in()) {
	//		return;
	//	}

	$_cookievalue = ( isset( $_COOKIE['cw_accept_cookies'] ) ) ? $_COOKIE['cw_accept_cookies'] : false;

	// cookies accepted
	if ('accept' == $_cookievalue) {
		return;
	}

	// disable plugins that set cookies as they have not been accepted

	// disable Google Analytics by Yoast
	remove_action( 'wp_head', array( 'GA_Filter', 'spool_analytics' ), 2 );

	// disable ShareThis
	remove_action('init', 'st_request_handler', 9999);
	remove_action('wp_head', 'st_widget_head');

	// cookies rejected, so no need to display statusbar
	// TODO: make the above decision a setting?
	if ('reject' == $_cookievalue) {
		return;
	}

	// add stylesheet to head
	add_action('wp_head', 'cw_addhead');

	// add html to footer
	add_action('get_footer', 'cw_addhtml');
}

function cw_get_options() {

	$options = get_option('cookiewise');

	$defaults = array(
		'weusecookies'		=> 'We use cookies on our site',
		'accept'			=> 'accept',
		'reject'			=> 'reject',
		'readmoretxt'		=> 'Would you like more information?',
		'readmorelinktxt'	=> 'click here',
		'readmorepageid'	=> 0,
		'txtcolor'			=> 'white',
		'linkcolor'			=> 'white',
		'bgcolor'			=> '#444'
	);
	return wp_parse_args($options, $defaults);	
}

function cw_addhead() {

	$_cw_options		= cw_get_options();

	$_displayposition	= ( isset( $_cw_options['screenposition'] ) ) ? $_cw_options['screenposition'] : false;
	$_positioncss		= ('screentop' == $_displayposition) ? 'top' : 'bottom';

?>
    <style type="text/css" media="screen">
<?php
	if ( 'screentop' == $_displayposition ) {
?>
        html                    { margin-top: 28px !important; }
        * html body             { margin-top: 28px !important; }
<?php
	}
?>
        #cw_statusbar           { position: fixed; <?php echo $_positioncss; ?>: 0px; left: 0px; width: 100%; height: 28px; padding: 0px; 
                                  z-index: 9999; background-color: <?php echo $_cw_options['bgcolor']; ?>; font-size: 13px; }
        #cw_statusbar ul        { position: absolute; left: 50%; margin-left: -350px; width: 700px; margin-top: 6px; }
        #cw_statusbar ul li     { display: inline; margin-left: 10px; color: <?php echo $_cw_options['txtcolor']; ?> }
        #cw_statusbar ul li a   { color: <?php echo $_cw_options['linkcolor']; ?>; }
    </style>
<?php
}

function cw_addhtml() {

	$_cw_url		= $_SERVER["REQUEST_URI"];
	$_cw_url		.= (false === strpos($_cw_url, '?')) ? '?' : '&';
	$_cw_url_accept	= $_cw_url . '_cookiewise=accept&_cwnonce=' . wp_create_nonce('cookiewise_accept_cookies');
	$_cw_url_reject	= $_cw_url . '_cookiewise=reject&_cwnonce=' . wp_create_nonce('cookiewise_reject_cookies');

	$options = cw_get_options();
?>
		<div id='cw_statusbar'>
			<ul>
				<li><?php echo $options['weusecookies']; ?></li>
				<li><a href="<?php echo $_cw_url_accept; ?>"><?php echo $options['accept']; ?></a></li>
				<li><a href="<?php echo $_cw_url_reject; ?>"><?php echo $options['reject']; ?></a></li>
				<li><?php echo $options['readmoretxt']; ?></li>
<?php
	if ($_link = get_permalink($options['readmorepageid'])) {
?>
				<li><a href="<?php echo $_link; ?>"><?php echo $options['readmorelinktxt']; ?></a></li>
<?php
	} else {
?>
				<li><i>No valid page specified.</i></li>
<?php
	}
?>
			</ul>
		</div>
<?php
}

//////////////////////////////////////////////
// Add link to settings in 'Manage plugins' //
//////////////////////////////////////////////

add_filter('plugin_action_links', 'cw_add_settings_link', 10, 2);
function cw_add_settings_link($links, $file) {
	$plugin = basename(__FILE__);

	// create link
	if (basename($file) == $plugin) {
		$_settingslink = '<a href="options-general.php?page=' . $plugin . '">' . __('Settings') . '</a>';
		array_unshift($links, $_settingslink);
	}

	return $links;
}

//////////////////
// Settings API //
//////////////////

if ( !class_exists( 'CookieWise_Settings' ) ) {

	class CookieWise_Settings {

		public function __construct() {

			add_action( 'admin_init', array( &$this, 'init' ) );
			add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

		}
	
		public function init() {
			register_setting( 'Cookie_Wise', 'cookiewise', array( &$this, 'validate' ) );
	
			// settings for Last.fm
			add_settings_section( 'txt-section', 'Text' , array( &$this, 'section_txt' ), basename( __FILE__ ) );
	
			add_settings_field( 'weusecookies', 'We Use Cookies',  array( &$this, 'setting_weusecookies' ), basename( __FILE__ ), 'txt-section' );
			add_settings_field( 'accept', 'Accept (link)',  array( &$this, 'setting_accept' ), basename( __FILE__ ), 'txt-section' );
			add_settings_field( 'reject', 'Reject (link)',  array( &$this, 'setting_reject' ), basename( __FILE__ ), 'txt-section' );
			add_settings_field( 'readmoretxt', 'Read More',  array( &$this, 'setting_readmoretxt' ), basename( __FILE__ ), 'txt-section' );
			add_settings_field( 'readmorelinktxt', 'Link Text For Read More',  array( &$this, 'setting_readmorelinktxt' ), basename( __FILE__ ), 'txt-section' );
			add_settings_field( 'readmorepageid', 'Read More (page or post)',  array( &$this, 'setting_readmorepageid' ), basename( __FILE__ ), 'txt-section' );
	
			// settings for displaying
			add_settings_section( 'position-section', 'Positioning' , array( &$this, 'section_position' ), basename( __FILE__ ) );
			add_settings_field( 'screenposition', 'Position on screen',  array( &$this, 'setting_screenposition' ), basename( __FILE__ ), 'position-section' );
	
			// settings for displaying
			add_settings_section( 'colors-section', 'Colors' , array( &$this, 'section_colors' ), basename( __FILE__ ) );
	
			add_settings_field( 'txtcolor', 'Text Color',  array( &$this, 'setting_txtcolor' ), basename( __FILE__ ), 'colors-section' );
			add_settings_field( 'linkcolor', 'Link Color',  array( &$this, 'setting_linkcolor' ), basename( __FILE__ ), 'colors-section' );
			add_settings_field( 'bgcolor', 'Background Color',  array( &$this, 'setting_bgcolor' ), basename( __FILE__ ), 'colors-section' );
		}
	
		public function admin_menu() {
			if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
				return;
			}
	
			if (function_exists('add_options_page')) {
				add_options_page( 'Cookie Wise', 'Cookie Wise', 'manage_options', basename(__FILE__), array( &$this, 'showform' ) );
			}
		}
	
		function showform() {
			$options = get_option('cookiewise');
	?>
	        <div class="wrap">
	          <?php screen_icon("options-general"); ?>
	          <h2>Cookie Wise</h2>
	          <form action="options.php" method="post">
	            <?php settings_fields('Cookie_Wise'); ?>
	            <?php do_settings_sections(basename(__FILE__)); ?>
	            <p class="submit">
	              <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
	            </p>
	          </form>
	        </div> 
	<?php 
		}
	
		function validate($input) {
			return $input;
		}
	
		function section_txt() {
			// echo "Texts to use.";
		}
	
		function section_colors() {
			echo "<p>Overrule default colors -- use official stylesheet notation like <b>white</b> or <b>#001162</b></p>";
		}
	
		function section_position() {
			// echo "<p>Where on the screen should the cookie bar be displayed?</p>";
		}
	
		// setting fields for TEXTS
	
		function setting_weusecookies() {
			$options = cw_get_options('cookiewise');
			echo "<input id='plugin_weusecookies' name='cookiewise[weusecookies]' size='40' type='text' value='{$options['weusecookies']}' />";
		}
	
		function setting_accept() {
			$options = cw_get_options('cookiewise');
			echo "<input id='plugin_accept' name='cookiewise[accept]' size='40' type='text' value='{$options['accept']}' />";
		}
	
		function setting_reject() {
			$options = cw_get_options('cookiewise');
			echo "<input id='plugin_reject' name='cookiewise[reject]' size='40' type='text' value='{$options['reject']}' />";
		}
	
		function setting_readmoretxt() {
			$options = cw_get_options('cookiewise');
			echo "<input id='plugin_readmoretxt' name='cookiewise[readmoretxt]' size='40' type='text' value='{$options['readmoretxt']}' />";
		}
		
		function setting_readmorelinktxt() {
			$options = cw_get_options('cookiewise');
			echo "<input id='plugin_readmorelinktxt' name='cookiewise[readmorelinktxt]' size='40' type='text' value='{$options['readmorelinktxt']}' />";
		}
	
		function setting_readmorepageid() {
			$options = cw_get_options('cookiewise');
			$_pageid = $options['readmorepageid'];
	
			echo "<select id='plugin_readmorepageid' name='cookiewise[readmorepageid]'>\n";
			$_pages = get_pages();
			foreach($_pages as $_page) {
				$_selected = ($_pageid == $_page->ID) ? 'selected="selected"' : '';
				echo "<option value='" . $_page->ID . "' " . $_selected . ">" . $_page->post_title . "</option>\n";
			}
			echo "<option value='0'>-- blogposts --</option>\n";
			$_posts = get_posts();
			foreach($_posts as $_post) {
				$_selected = ($_pageid == $_post->ID) ? 'selected="selected"' : '';
				echo "<option value='" . $_post->ID . "' " . $_selected . ">" . mysql2date(get_option('date_format'), $_post->post_date) . ' &raquo; ' . $_post->post_title . "</option>\n";
			}		
			echo "</select>\n";
		}
	
		// setting fields for POSITION 
	
		function setting_screenposition() {
			$_positions = array(
				'screentop'		=> 'top of screen',
				'screenbottom'	=> 'bottom of screen'
			);
	
			$options = cw_get_options('cookiewise');
			$_currentposition = $options['screenposition'];
	
			echo "<select id='plugin_screenposition' name='cookiewise[screenposition]'>\n";
			foreach($_positions as $_positionname => $_positiondesc) {
				$selected = ($_positionname == $_currentposition) ? 'selected="selected"' : '';
				echo "<option value='" . $_positionname . "' " . $selected . ">" . $_positiondesc . "</option>\n";
			}
			echo "</select>\n";
		}
	
		// setting fields for COLORS
	
		function setting_txtcolor() {
			$options = cw_get_options('cookiewise');
			echo "<input id='plugin_txtcolor' name='cookiewise[txtcolor]' size='40' type='text' value='{$options['txtcolor']}' />";
		}
	
		function setting_linkcolor() {
			$options = cw_get_options('cookiewise');
			echo "<input id='plugin_linkcolor' name='cookiewise[linkcolor]' size='40' type='text' value='{$options['linkcolor']}' />";
		}
	
		function setting_bgcolor() {
			$options = cw_get_options('cookiewise');
			echo "<input id='plugin_bgcolor' name='cookiewise[bgcolor]' size='40' type='text' value='{$options['bgcolor']}' />";
		}
	
	} // END of class CookieWise

}

$_cookiewise_settings = new CookieWise_Settings();

?>