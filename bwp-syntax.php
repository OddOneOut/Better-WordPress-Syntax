<?php
/*
Plugin Name: Better WordPress Syntax
Plugin URI: http://betterwp.net/wordpress-plugins/bwp-syntax/
Description: This plugin allows you to highlight code syntax in your posts. It is simple, lightweight, and very easy to use with plenty of options to choose. This plugin works with both editors and won't mangle your code format. This plugin utilizes the popular PHP syntax highlighting library - <a href="http://qbnz.com/highlighter/">GeSHi</a>. Some Icons by <a href="http://p.yusukekamiyamane.com/">Yusuke Kamiyamane</a>.
Version: 1.0.3
Text Domain: bwp-syntax
Domain Path: /languages/
Author: Khang Minh
Author URI: http://betterwp.net
License: GPLv3
*/

// Backend
add_action('admin_menu', 'bwp_syntax_init', 1);
add_action('admin_menu', 'bwp_syntax_init_admin', 1);
// Frontend
add_action('init', 'bwp_syntax_add_shortcode');
add_action('template_redirect', 'bwp_syntax_init', 11);

function bwp_syntax_init()
{
	global $bwp_syntax;
	$bwp_syntax->init();
}

function bwp_syntax_init_admin()
{
	global $bwp_syntax;
	$bwp_syntax->init_admin();
}

function bwp_syntax_add_shortcode()
{
	global $bwp_syntax;

	require_once('includes/class-bwp-syntax.php');
	// Use this filter to add more languages
	$args = apply_filters('bwp_syntax_languages', array());	
	$bwp_syntax = new BWP_SYNTAX($args);
	// get temporary options to see if shortcode is enabled
	// if so add this after do_shortcode to avoid problems
	$db_options = get_option('bwp_syntax_general');
	if ($db_options && 'yes' == $db_options['enable_shortcode'])
		add_filter('the_content', array($bwp_syntax, 'add_shortcode'), 12);
}
?>