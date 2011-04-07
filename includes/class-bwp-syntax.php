<?php
/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
 
if (!class_exists('BWP_FRAMEWORK'))
	require_once('class-bwp-framework.php');

class BWP_SYNTAX extends BWP_FRAMEWORK {

	/**
	 * This holds the alias for the GeSHi keywords. You can use html instead of html4strict, js instead of javascript, etc.
	 */
	var $lang_alias = array();

	/**
	 * The shortcode tag used by this plugin.
	 */	
	var $shortcode_tag;
	
	/**
	 * Hold GeSHi object
	 */	
	var $geshi;

	/**
	 * Constructor
	 *
	 * @param	array	$args	The alias array, you can filter this using WordPress add_filter function to define your own.
	 */	
	function __construct($args = array(), $version = '1.0.1')
	{
		// Plugin's title
		$this->plugin_title = 'BetterWP Syntax';
		// Plugin's version
		$this->set_version($version);
		// Basic version checking
		if (!$this->check_required_versions())
			return;

		// The default options
		$options = array(
			'only_singular' => 'yes',
			'enable_pre'	=> 'yes',
			'enable_shortcode' => '',
			'enable_lines'	=> 'yes',
			'enable_toggle' => 'yes',
			'enable_code_tag' => '',
			'enable_nested_remove'	=> '',
			'enable_comment'=> 'yes',
			'enable_credit'	=> '',
			'enable_selective' => 'yes',
			'input_lines'				=> 15, 
			'input_line_height'			=> 16.8,
			'input_width'				=> 0,
			'input_tab'					=> 4,
			'select_output_method'		=> 'table',
			'select_style_method'		=> 'lang',
			'select_source_position'	=> 'overlay',
			'select_default_language'	=> 'noparse',
			'select_output_style'		=> 'classes'
		);

		/* BWP FRAMEWORK CODE */
		$this->build_properties('BWP_SYNTAX', 'bwp-syntax', $options, 'BetterWP Syntax', dirname(dirname(__FILE__)) . '/bwp-syntax.php', 'http://betterwp.net/wordpress-plugins/bwp-syntax/', false);
		$this->add_option_key('BWP_SYNTAX_OPTION_GENERAL', 'bwp_syntax_general', __('General Options', 'bwp-syntax'));
		$this->add_option_key('BWP_SYNTAX_OPTION_THEME', 'bwp_syntax_theme', __('Theme Options', 'bwp-syntax'));
		/* BWP FRAMEWORK CODE */

		$defaults = array(
			'php'	=> 'php',
			'html' 	=> 'html4strict',
			'java'	=> 'java',
			'js' 	=> 'javascript',
			'css' 	=> 'css',
			'perl'	=> 'perl',
			'py'	=> 'python',
			'ruby'	=> 'ruby',
			'c'		=> 'c',
			'vb'	=> 'vb',
			'xml'	=> 'xml'
		);

		$this->shortcode_tag = apply_filters('bwp_syntax_shortcode_tag', 'code');
		$this->lang_alias = wp_parse_args($args, $defaults);
		$this->add_allowed_tags();

		if (is_admin())
		{
			$this->add_editor_button();
			$this->add_shortcode();			
		}
	}

	function add_hooks()
	{
		// For html related shortcode, parse shortcode before it got wpautoped 
		if ($this->options['enable_shortcode'] == 'yes')
		{
			if ($this->options['enable_nested_remove'] == 'yes')
			{
				// Format content to avoid nesting problems
				add_filter('the_content', array($this, 'pre_format_content'), 6);
				add_filter('comment_text', array($this, 'pre_format_content'), 6);
			}
			// Parse the shortcode, @see $this->shortcode_tag
			add_filter('the_content', array($this, 'pre_parse_shortcodes'), 7);
			// Bring back square bracket, after the normal do_shortcode hook
			add_filter('the_content', array($this, 'after_parse_shortcodes'), 12);
		}
		// Parse <pre> tag
		if ($this->options['enable_pre'] == 'yes')
		{
			add_filter('the_content', array($this, 'parse_block_code'), 1000);
			if ($this->options['enable_comment'] == 'yes')
				add_filter('comment_text', array($this, 'parse_block_code'), 1000);
		}
		// Parse <code> tag
		if ($this->options['enable_code_tag'] == 'yes')
		{
			add_filter('the_content', array($this, 'parse_inline_code'), 1000);
			if ($this->options['enable_comment'] == 'yes')
				add_filter('comment_text', array($this, 'parse_inline_code'), 1000);
		}
	}
	
	function enqueue_media()
	{
		global $wp_styles;
		// Enqueue needed media, only if needed
		if ((!is_admin() && 'yes' != $this->options['enable_selective']) || ('yes' == $this->options['only_singular'] && is_singular()) || (empty($this->options['only_singular']) && !is_admin()) || $this->is_admin_page())
		{
			if ($this->options['select_style_method'] == 'lang')
			{
				if ('classes' == $this->options['select_output_style'])
					wp_enqueue_style('bwp-syntax', apply_filters('bwp_syntax_lang_style', BWP_SYNTAX_CSS . '/bwp-syntax.css'));
				else
					wp_enqueue_style('bwp-syntax', apply_filters('bwp_syntax_lang_style', BWP_SYNTAX_CSS . '/bwp-syntax-inline.css'));
			}
			else
				wp_enqueue_style('bwp-syntax', apply_filters('bwp_syntax_global_style', BWP_SYNTAX_CSS . '/bwp-syntax-global.css'));
			wp_enqueue_script('bwp-syntax-js', BWP_SYNTAX_JS . '/bwp-syntax.js', array('jquery'));
		}		
	}

	/**
	 * Build the Menus
	 */
	function build_menus()
	{
		add_menu_page(__('Better WordPress Syntax', 'bwp-syntax'), 'BWP Syntax', BWP_SYNTAX_CAPABILITY, BWP_SYNTAX_OPTION_GENERAL, array($this, 'build_option_pages'), BWP_SYNTAX_IMAGES . '/icon_menu.png');
		// Sub menus
		add_submenu_page(BWP_SYNTAX_OPTION_GENERAL, __('BWP Syntax General Options', 'bwp-syntax'), __('General Options', 'bwp-syntax'), BWP_SYNTAX_CAPABILITY, BWP_SYNTAX_OPTION_GENERAL, array($this, 'build_option_pages'));
		add_submenu_page(BWP_SYNTAX_OPTION_GENERAL, __('BWP Syntax Theme Options', 'bwp-syntax'), __('Theme Options', 'bwp-syntax'), BWP_SYNTAX_CAPABILITY, BWP_SYNTAX_OPTION_THEME, array($this, 'build_option_pages'));
	}

	/**
	 * Build the option pages
	 *
	 * Utilizes BWP Option Page Builder (@see BWP_OPTION_PAGE)
	 */	
	function build_option_pages()
	{
		global $allowedtags;

		if (!current_user_can(BWP_SYNTAX_CAPABILITY))
			wp_die(__('You do not have sufficient permissions to access this page.'));

		// Init the class
		$page = $_GET['page'];		
		$bwp_option_page = new BWP_OPTION_PAGE($page);
		
		$options = array();

if (!empty($page))
{	
	if ($page == BWP_SYNTAX_OPTION_GENERAL)	
	{		
		$bwp_option_page->set_current_tab(1);

		// Option Structures - Form
		$form = array(
			'items'			=> array('heading', 'section', 'checkbox', 'checkbox', 'select', 'heading', 'checkbox', 'checkbox', 'heading', 'checkbox', 'checkbox'),
			'item_labels'	=> array
			(
				__('Parse Options', 'bwp-syntax'),
				__('This plugin will parse', 'bwp-syntax'),
				__('Search and remove nested shortcodes?', 'bwp-syntax'),
				__('Parse code inside comments?', 'bwp-syntax'),
				__('Default lang if none specified', 'bwp-syntax'),
				__('Display Options', 'bwp-syntax'),
				__('Show line numbers?', 'bwp-syntax'),
				__('Hide the codeblock by default?', 'bwp-syntax'),
				__('Miscellaneous', 'bwp-syntax'),
				__('Load only on single pages?', 'bwp-syntax'),
				__('Give the author credits?', 'bwp-syntax')
			),
			'item_names'	=> array('h1', 'sec1', 'cb10', 'cb5', 'select_default_language', 'h3', 'cb2', 'cb9', 'h2', 'cb1', 'cb6'),
			'sec1' => array(
				array('checkbox', 'name' => 'cb7'),
				array('checkbox', 'name' => 'cb4'),
				array('checkbox', 'name' => 'cb8')
			),
			'heading'		=> array(
				'h1' => sprintf(__('<em>Choose what type of code parsing methods you want. By default this plugin will not parse codes inside the famous shortcode <code>[code]</code>, you can ask the plugin to do so, but it is <strong>not recommended</strong>; Even if you choose to do so, the plugin will only parse for your post content, not comment text. The reasons are described in this <a href="%s#notes" target="_blank">note</a>.</em>', 'bwp-syntax'), BWP_SYNTAX_PLUGIN_URL),
				'h2' => __('<em>Other options that fit nowhere.</em>', 'bwp-syntax'),
				'h3' => __('<em>Some general display options.</em>', 'bwp-syntax')
			),
			'checkbox'		=> array
			(
				'cb1' => array(__('This plugin will only load if is_singular() returns true.', 'bwp-syntax') => 'only_singular'),
				'cb2' => array(__('Show the line number next to each line of code.', 'bwp-syntax') => 'enable_lines'),
				'cb3' => array(__('Add <code>inline="yes"</code> to the shortcode and this plugin will output codes inside <code>&lt;code&gt;</code> tag.', 'bwp-syntax') => 'enable_inline'),
				'cb4' => array(__('codes inside <code>&lt;code&gt;</code> tags.', 'bwp-syntax') => 'enable_code_tag'),
				'cb5' => array(__('<strong>Warning:</strong> This might cause performance issues if there are too many comments with codes inside. You can use a cache plugin to deal with such problem. This option might be improved in the future, though.', 'bwp-syntax') => 'enable_comment'),
				'cb6' => array(__('A link to this plugin\'s official page will be visible to your visitors. The link is added next to the view source icon. Thank you!', 'bwp-syntax') => 'enable_credit'),
				'cb7' => array(__('codes inside <code>&lt;pre&gt;</code> tags.', 'bwp-syntax') => 'enable_pre'),
				'cb8' => array(__('codes inside <code>[code]</code> shortcodes.', 'bwp-syntax') => 'enable_shortcode'),
				'cb9' => array(__('Outputs are hidden by default to decrease post\'s length. Visitors will then click on folded codeblocks to toggle.', 'bwp-syntax') => 'enable_toggle'),
				'cb10' => array(__('If you choose to parse both <code>&lt;pre&gt;</code> or <code>&lt;code&gt;</code> with <code>[code]</code>, you should enable this to avoid nesting problem, such as <code>&lt;pre&gt;[code][/code]&lt;/pre&gt;</code>. This might cause some extremely long codeblock to fail to load. Follow the link in the note above for more details.', 'bwp-syntax') => 'enable_nested_remove')
			),
			'select'		=> array
			(
				'select_default_language' => array()
			)
		);

		$temp = array(__('No syntax highlighting', 'bwp-syntax') => 'noparse');
		$form['select']['select_default_language'] = array_merge($temp, array_flip($this->lang_alias));

		// Get the default options
		$options = $bwp_option_page->get_options(array('only_singular', 'enable_lines', 'enable_toggle', 'enable_code_tag', 'enable_comment', 'enable_credit', 'select_default_language', 'enable_pre', 'enable_shortcode', 'enable_nested_remove'), $this->options);

		// Get option from the database
		$options = $bwp_option_page->get_db_options($page, $options);
		$option_formats = array();
	}
	else if ($page == BWP_SYNTAX_OPTION_THEME)
	{
		$bwp_option_page->set_current_tab(2);

		$form = array(
			'items'			=> array('input', 'input', 'input', 'input', 'select', 'select', 'select', 'select', 'checkbox', 'heading', 'textarea', 'select', 'heading', 'input'),
			'item_labels'	=> array
			(				
				__('Show a vertical scrollbar when there are more than', 'bwp-syntax'),
				__('Each line will be', 'bwp-syntax'),
				__('Each tab will equal', 'bwp-syntax'),
				__('A code block will be', 'bwp-syntax'),
				__('Output a code block as', 'bwp-syntax'),				
				__('Style a code block', 'bwp-syntax'),
				__('Style a code block using', 'bwp-syntax'),
				__('Show the toolbar', 'bwp-syntax'),
				__('Load CSS, JS only when needed?', 'bwp-syntax'),
				__('Preview your preference', 'bwp-syntax'),
				__('Type some codes here', 'bwp-syntax'),
				__('Choose a language', 'bwp-syntax'),
				__('Generate language-based css', 'bwp-syntax'),
				__('Type in your desired language', 'bwp-syntax')
			),
			'item_names'	=> array('input_lines', 'input_line_height', 'input_tab', 'input_width', 'select_output_method', 'select_style_method', 'select_output_style', 'select_source_position', 'cb1', 'h1', 'preview_code', 'select_preview_lang', 'h2', 'input_language'),
			'heading'		=> array
			(
				'h1'	=> __('This section allows you to preview how the codeblock will look. It might not be accurate since the styles of the admin page and your actual page differ but generally it should be the same. Note that this generates codeblock basing on your preference.', 'bwp-syntax'),
				'h2'	=> sprintf(__('This section allows you to generate styles for custom languages. Note that if you choose to style globally or use inline styles, simply ignore this. Visit <a href="%s#notes">here</a> for more details.', 'bwp-syntax'), BWP_SYNTAX_PLUGIN_URL)
			),
			'checkbox'		=> array
			(
				'cb1' => array(__('This is only useful when you do not use any minify or cache plugin.', 'bwp-syntax') => 'enable_selective')
			),
			'select'		=> array
			(
				'select_output_method' => array(
					__('Table', 'bwp-syntax') => 'table',
					__('Ordered List', 'bwp-syntax') => 'list'
		 		),
				'select_output_style' => array(
					__('Inline styles', 'bwp-syntax') => 'inline',
					__('CSS classes', 'bwp-syntax') => 'classes'
		 		),
				'select_style_method' => array(
					__('Based on the language', 'bwp-syntax') => 'lang',
					__('Globally', 'bwp-syntax') => 'global'
		 		),
				'select_source_position' => array(
					__('Overlay', 'bwp-syntax') => 'overlay',
					__('Before Codeblock', 'bwp-syntax') => 'before',
					__('After Codeblock', 'bwp-syntax') => 'after',
					__('Do not show', 'bwp-syntax') => 'hide'
		 		),
				'select_preview_lang' => array()
			),
			'input'			=> array
			(
				'input_lines' => array('size' => 5, 'label' => __('lines of codes. Type 0 for no vertical bar.', 'bwp-syntax')),
				'input_line_height' => array('size' => 5, 'label' => __('pxs high. This should be the line-height you define in CSS (plus any margin or padding).', 'bwp-syntax')),
				'input_tab' => array('size' => 5, 'label' => __('spaces. This is used by the Ordered List output method.', 'bwp-syntax')),
				'input_width' => array('size' => 5, 'label' => __('pxs wide. By default (0), the code block will stretch to its container. If you choose to output the code block as an Ordered List, this should be wider than 500 pixels.', 'bwp-syntax')),
				'input_language' => array('size' => 10, 'label' => __('You need to specify the correct language name defined within GeSHi. Look for the language name inside geshi.php, near line 1442', 'bwp-syntax'))
			),
			'textarea'		=> array
			(
				'preview_code' => array('cols' => 60, 'rows' => 5)
			),
			'container'		=> array
			(
				'select_output_method' => __('<em><strong>Note:</strong> "Table" means the output consists of a table with one row and two columns. The first column will hold line numbers while the second column will hold the code. "Ordered List" will generate each line of code in a list item. The advantage of this is you can have a different color for each line but in exchange for more complicated styling and some weird browser behaviours. I suggest using the preview feature below to experiment with each method. For more information, please consult the <a href="http://qbnz.com/highlighter/geshi-doc.html#line-numbers" target="_blank">GeSHi documentation</a>.</em>', 'bwp-syntax'),
				'select_output_style' => __('<em><strong>Note:</strong> If you choose to style globally, this plugin will style your codeblocks the same regardless of languages. Styling globally might save you some kbs if you need tons of languages for your blog. Also, if you choose to style based on languages and do not want to use CSS, you can use inline styles, which also saves you some kbs (only a simple CSS file is needed). It is recommended that, however, you use CSS classes.</em>', 'bwp-syntax'),
				'select_source_position' => __('<em><strong>Note:</strong> The toolbar consists of a view source icon and a credit link if enabled. The view source button allows visitors to copy preformatted code with tabs and spaces untouched.</em>', 'bwp-syntax'),
				'select_preview_lang' => '',
				'input_language' => ''
			)
		);

		$form['select']['select_preview_lang'] = array_flip($this->lang_alias);
		
		// Some customized script to add before we show the form
		// This action is independent of the option page plugin and can just do anything the plugin author wants
		if (isset($_POST['submit_' . $bwp_option_page->get_form_name()]))
		{
			check_admin_referer($page);
			// preview a snipplet of code
			if (!empty($_POST['preview_code']) && !empty($_POST['select_preview_lang']))
			{
				$form['container']['select_preview_lang'] = '<h3>Your preview code:</h3>' . $this->bwp_geshi_parser(array('lang' => stripslashes($_POST['select_preview_lang'])), wp_kses(stripslashes($_POST['preview_code']), $allowedtags));
			}
			// generate a style for a specific language
			if (!empty($_POST['input_language']))
			{
				$geshi = $this->load_geshi();
				$geshi->set_language($_POST['input_language']);
				$styles = str_replace("\n", " ", $geshi->get_stylesheet());
				$form['container']['input_language'] = '<h3>Your generated styles:</h3>' . $this->bwp_geshi_parser(array('lang' => 'css'), $styles);
			}
		}

		// Get the default options
		$options = $bwp_option_page->get_options(array('input_lines', 'input_line_height', 'input_width', 'select_output_method', 'select_output_style', 'select_style_method', 'select_source_position', 'input_tab', 'enable_selective'), $this->options);		

		// Get option from the database
		$options = $bwp_option_page->get_db_options($page, $options);

		$option_formats = array('input_lines' => 'int', 'input_line_height' => 'float', 'input_width' => 'int', 'input_tab' => 'int');
	}
}

		// Get option from user input
		if (isset($_POST['submit_' . $bwp_option_page->get_form_name()]) && isset($options) && is_array($options))
		{
			check_admin_referer($page);
			foreach ($options as $key => &$option)
			{
				if (isset($_POST[$key]))
					$bwp_option_page->format_field($key, $option_formats);
				if (!isset($_POST[$key]))
					$option = '';
				else if (isset($option_formats[$key]) && 0 == $_POST[$key] && 'int' == $option_formats[$key])
					$option = 0;
				else if (isset($option_formats[$key]) && empty($_POST[$key]) && 'int' == $option_formats[$key])
					$option = $this->options_default[$key];
				else if (!empty($_POST[$key])) // should add more validation here though
					$option = trim(stripslashes($_POST[$key]));
				else
					$option = '';
			}
			update_option($page, $options);
		}
		// Assign the form and option array
		$bwp_option_page->init($form, $options, $this->form_tabs);

		// Build the option page	
		echo $bwp_option_page->generate_html_form();
	}

	function get_lang()
	{
		return array_merge(array('noparse' => __('No syntax highlighting', 'bwp-syntax')), $this->lang_alias);
	}

	function add_tinymce_elements($initElem) 
	{
		$ext = 'pre[lang|inline|extra|toggle|start|parse|strict|style|width],code[lang|parse]';
		if (isset($initElem['extended_valid_elements']))
			$initElem['extended_valid_elements'] .= ',' . $ext;
		else 
			$initElem['extended_valid_elements'] = $ext;
 
		return $initElem;
	}

	function add_allowed_tags()
	{
		global $allowedposttags, $allowedtags;

		// WPMS compatible
		$allowedposttags['pre'] = array(
			'lang' => array(),
			'inline' => array(),
			'extra' => array(),
			'toggle' => array(),
			'start' => array(),
			'nested' => array(),
			'parse' => array(),
			'strict' => array(),
			'style' => array(),			
			'width' => array()
		);
		$allowedposttags['code'] = array(
			'lang' => array(),
			'parse' => array(),
			'style' => array()
		);

		$allowedtags['pre'] = array(
			'lang' => array(),
			'extra' => array(),
		);
		$allowedtags['code'] = array(
			'lang' => array()
		);
	}

	/**
	 * Add a button to the TinyMCE visual editor to simplify the process of posting code into post content.
	 * Added codes are automatically htmlspecialchars()ed to prevent things such as html tag from breaking page layout.
	 * Also, codes are wrapped inside a <pre> tag to preserve tabs and spaces.
	 */
	function add_editor_button()
	{
		// Don't bother doing this stuff if the current user lacks permissions
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) 
			return;		
		// Add only in Rich Editor mode
		if (get_user_option('rich_editing') == 'true')
		{			
			add_filter("mce_external_plugins", array($this, 'add_editor_plugin'));
			add_filter('mce_buttons', array($this, 'register_editor_button'));
			add_filter('tiny_mce_before_init', array($this, 'add_tinymce_elements'));
		}
	}

	/**
	 * Insert buttons
	 */
	function register_editor_button($buttons)
	{	
		array_push($buttons, "separator", 'BWPSyntax');	
		return $buttons;
	}

	/**
	 * Load the TinyMCE plugin: editor_plugin.js
	 */
	function add_editor_plugin($plugin_array)
	{
		$plugin_array['BWPSyntax'] = plugins_url('better-wordpress-syntax-based-on-geshi', dirname($this->plugin_file)) . '/includes/bwp-buttons/editor_plugin.js';
		return $plugin_array;
	}

	// @todo: add atts in future
	function dummy_geshi_parser($atts, $content = '')
	{
		return $content;
	}

	/**
	 * This is a hack function to override the behaviour of do_shortcode
	 */
	function add_shortcode($content = '')
	{
		// Filter to parse the code, we do this globally to strip out the shortcode properly when the plugin is not in use
		// Add the shortcode normally so it can be manipulated (e.g. stripped) later
		add_shortcode($this->shortcode_tag, array($this, 'dummy_geshi_parser'));
		return $content;
	}

	function build_atts_string($atts = array())
	{
		if (!is_array($atts)) return '';
		$atts_string = '';
		foreach ($atts as $key => $value)
		{
			$atts_string .= ' ' . $key . '="' . $value . '"';
		}
		return $atts_string;
	}

	/**
	 * Load GeSHi if needed
	 */
	function load_geshi()
	{
		// Include GeSHi, check if any other plugin also requires this library
		// Since GeSHi is a quite large library, only include this when something needs parsing				
		if (!class_exists('GeSHi'))
			include_once('geshi/geshi.php');

		if (!isset($this->geshi))
			$this->geshi = new GeSHi();					

		return $this->geshi;
	}

	/**
	 * The shortcode callback
	 */
	function bwp_geshi_parser($atts, $content = '')
	{
		global $comment;

		extract(shortcode_atts(array(
			'lang' 			=> $this->options['select_default_language'], // assume it is the default lang
			'toggle'		=> $this->options['enable_toggle'], // set to 'yes' or 'no'
			'start'			=> 1,
			'extra'			=> '', // string - list of lines to be double highlighted - e.g. 1,3,5
			'inline'		=> '', // set to 'yes'
			'nested'		=> '', // set to 'yes'
			'parse'			=> 'yes', // set to 'no'
			'strict'		=> '' // set to 'yes'
		), $atts));

		// If nested, return the codeblock as-is
		if ('yes' == $nested)
		{
			unset($atts['nested']);
			return (!empty($content)) ? '[' . $this->shortcode_tag . $this->build_atts_string($atts) . ']' . $content . '[/' . $this->shortcode_tag . ']' : '[' . $this->shortcode_tag . $this->build_atts_string($atts) . ']';
		}

		// Remove blank lines, top and bottom
		$content = trim($content, "\r\n\0");
		// Remove <pre></pre> tag from shortcode if first matches, don't do this for inline
		if ('yes' != $inline)
		{
			if (substr($content, 0, 5) == '<pre>')
			{
				$content = substr($content, 5);
			}
			if (substr($content, -6) == '</pre>')
			{
				$content = substr($content, 0, strlen($content)-6);
			}
			// Re-remove blank lines
			$content = trim($content, "\r\n\0");
		}
		// Decode content, for users using the visual editors
		$content = htmlspecialchars_decode($content);

		// if content is empty, no need to proceed
		if (empty($content))
			return;
		
		if ($inline == 'yes')
			$unparsed_return = "<code>%s</code>";
		else
			$unparsed_return = "<pre>%s</pre>";

		// Make sure no plain shortcode got out
		if (('yes' == $this->options['only_singular'] && !is_singular()) && !is_admin())
			return sprintf($unparsed_return, htmlspecialchars($content, ENT_NOQUOTES)); // update note

		// Don't parse code in admin except for viewing option page, don't parse shortcode when requested
		if ((is_admin() && !$this->is_admin_page()) || 'yes' != $parse)
			return sprintf($unparsed_return, htmlspecialchars($content, ENT_NOQUOTES)); // update note

		// Don't parse code for comments if option unchecked
		if ('yes' != $this->options['enable_comment'] && !empty($comment->comment_ID))
			return sprintf($unparsed_return, htmlspecialchars($content, ENT_NOQUOTES)); // update note
	
		// This language is not allowed
		if (empty($this->lang_alias[$lang]))
		{
			// If default language is no parse
			if ('noparse' == $this->options['select_default_language'])
				return sprintf($unparsed_return, htmlspecialchars($content, ENT_NOQUOTES)); // update note
			// If we are styling globally, assign the default language
			else if ('global' == $this->options['select_style_method'])
				$lang = $this->options['select_default_language'];
			else
				return sprintf($unparsed_return, htmlspecialchars($content, ENT_NOQUOTES)); // update note
		}
		else 
			$lang = $this->lang_alias[$lang];

		// Still can't find any lang, return empty
		if (empty($lang)) return;

		// Load the library
		$geshi = $this->load_geshi();
		$geshi->set_source($content);
		$geshi->set_language($lang);

		if ('yes' != $inline && ('lang' != $this->options['select_style_method'] || 'classes' == $this->options['select_output_style']))
			$geshi->enable_classes();
		else
		{
			$geshi->enable_classes(false);
			$geshi->set_line_style('', 'background-color: #f8f8f8;');
			$geshi->set_overall_style('');
		}

		// unconfigurable settings
		if ('yes' == $strict)
			$geshi->enable_strict_mode(GESHI_ALWAYS);
		else
			$geshi->enable_strict_mode(GESHI_NEVER);

		$geshi->enable_keyword_links(false);

		// Inline codes
		if ('yes' == $inline)
		{
			$geshi->set_header_type(GESHI_HEADER_NONE);
			$geshi->enable_line_numbers(GESHI_NO_LINE_NUMBERS);
			// Output result
			return '<code class="bwp-syntax-inline">' . $geshi->parse_code() . '</code>';
		}
		
		// Toggle?
		$toggle = ('yes' == $toggle) ? 'yes' : '';
		// Start at
		$start = (int) $start;
		$geshi->start_line_numbers_at($start);
		// Tab width
		$tab = (!empty($this->options['input_tab'])) ? $this->options['input_tab'] : $this->options_default['input_tab'];
		$geshi->set_tab_width($tab);
		// Highlight extra lines
		if (!empty($extra))
		{
			$extra = trim($extra);
			$extra = explode(',', $extra);
			if (isset($extra) && is_array($extra))
			{
				foreach ($extra as &$line)
				{
					$line = $line - $start + 1;
					if ($line < 0) $line = 0;
				}
				$geshi->highlight_lines_extra($extra);
			}
		}
		// Our source code
		$encoded_content = htmlspecialchars($content, ENT_NOQUOTES);

		// Direct styleing the wrapper
		// count the lines, this allows us to show a vertical scrollbar if necessary
		$lines 		= explode("\n", $content); 
		$num_lines 	= count($lines);
		$line_height = (empty($this->options['input_line_height'])) ? 17 : $this->options['input_line_height']; 
		$height 	= (empty($this->options['input_lines'])) ? 0 : $this->options['input_lines'];
		$height 	= ($height <= $num_lines && !empty($height)) ? ' height: ' . $height * $line_height . 'px;' : '';
		$width 		= (empty($this->options['input_width'])) ? '' : 'width: ' . $this->options['input_width'] . 'px;';
		$inline_stlyle = ('' != $height . $width) ? 'style="' . $height . $width . '"' : '';
		$toolbar_style = ('' != $height && 'yes' != $toggle) ? ' style="right: 15px;" ' : '';
		
		$styling_class = ' bwp-syntax-simple';
		
		$source_icon = ($this->options['select_source_position'] != 'hide') ? '<a href="javascript:;" class="bwp-syntax-source-switch" title="' . __('View Source Code', 'bwp-syntax') . '"></a>' : '';
		
		$credit_icon =($this->options['enable_credit'] == 'yes') ? '<a href="' . BWP_SYNTAX_PLUGIN_URL . '" class="bwp-syntax-copy-switch" title="' . __('Better WordPress Syntax Plugin', 'bwp-syntax') . '"></a>' : '';
		
		$toolbar = ('' != $source_icon . $credit_icon) ? '<div class="bwp-syntax-control">' . $source_icon . $credit_icon . '</div>' : '';
		// choose the right line style
		if ($this->options['select_output_method'] == 'list' && $this->options['enable_lines'])
		{
			$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS, 2);
			$geshi->set_header_type(GESHI_HEADER_DIV);
			$styling_class = ' bwp-syntax-advanced';
		}
		else
		{
			$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
			$geshi->set_header_type(GESHI_HEADER_PRE_TABLE);
		}

		if ('yes' != $this->options['enable_lines'])
		{
			$geshi->enable_line_numbers(GESHI_NO_LINE_NUMBERS);
			$styling_class .= ' bwp-syntax-no-lines';
		}

		$block_class = '';
		if ('yes' == $toggle)
		{
			$styling_class .= ' bwp-syntax-hidden';
			$block_class .= ' bwp-syntax-has-border';
		}				
			
		$output = '';
		$output .= "\n<div class=\"bwp-syntax-block clearfix$block_class\">";
		$output .= ('yes' == $toggle) ? '<div class="bwp-syntax-block-handle" style="height: ' . $line_height . 'px;"><a href="javascript:;" title="' . __('Click to toggle codeblock', 'bwp-syntax') . '">' . __('Click to toggle codeblock', 'bwp-syntax') . '</a></div>': '';
		$output .= ('overlay' == $this->options['select_source_position'] || 'hide' == $this->options['select_source_position']) ? "\n" . '<div class="bwp-syntax-toolbar"' . $toolbar_style . '>' . $toolbar . '</div>' : '';
		$output .= ($this->options['select_source_position'] == 'before') ? $toolbar : '';
		$output .= "\n<div class=\"bwp-syntax-wrapper clearfix$styling_class\"$inline_stlyle>";
		$output .= $geshi->parse_code();
		$output .= "</div>\n";
		$output .= ($this->options['select_source_position'] == 'after') ? $toolbar : '';
		$output .= ('hide' != $this->options['select_source_position']) ? '<div class="bwp-syntax-source"><pre class="no-parse">' . $encoded_content . '</pre></div>' : '';
		$output .= "</div>\n";		

		return $output;
	}

	/**
	 * Do the shortcode for this plugin before wpautop adds some weird things to the content of the shortcode.
	 *
	 * This utilizes the method stated by Viper007Bond 
	 * {@link http://www.viper007bond.com/2009/11/22/wordpress-code-earlier-shortcodes/}
	 * However, this method will render strip_shortcodes useless when you want to strip the Syntax shortcode for example.
	 * Therefore it is recommended to add the shortcode normally first, and then remove it later. (@see Constructor)
	 * Also note that this method can be applied to any shortcodes that have html content inside.
	 */
	function pre_do_shortcodes($content) 
	{
		global $shortcode_tags;

		// Backup current registered shortcodes and clear them all out
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();
		// Do the shortcode added by this plugin
		add_shortcode($this->shortcode_tag, array($this, 'bwp_geshi_parser'));
		$content = do_shortcode($content);
		// Put the original shortcodes back
		$shortcode_tags = $orig_shortcode_tags;

		return $content;
	}
	
	/**
	 * Attempt to remove shortcodes inside <code>, just in case the user messes up or when use in demonstration
	 */	
	function remove_code_shortcode($matches)
	{
		$return = str_replace(array('[', ']'), array('bwp_osbr', 'bwp_csbr'), $matches[0]);
		return $return;
	}

	/**
	 * This function attempts to format the text before it got parsed
	 */	
	function pre_format_content($content)
	{
		// [code] inside <pre>
		$content = preg_replace_callback("/<pre.*?>(?:.*?(\[\/" . $this->shortcode_tag . "\])+?.*?)<\/pre>/isu", array($this, 'remove_code_shortcode'), $content);
		// [code] inside <code>
		if ($this->options['enable_code_tag'] == 'yes')
		{
			$content = preg_replace_callback("/<code.*?>(?:.*?(\[\/" . $this->shortcode_tag . "\])+?.*?)<\/code>/isu", array($this, 'remove_code_shortcode'), $content);
		}
		return $content;
	}

	function pre_parse_shortcodes($content)
	{
		return $this->pre_do_shortcodes($content);
	}
	
	function after_parse_shortcodes($content)
	{
		$content = str_replace(array('bwp_osbr', 'bwp_csbr'), array('[', ']'), $content);
		return $content;
	}

	function geshi_highlight_inline($matches)
	{
		$atts = shortcode_parse_atts($matches[1]);
		$atts['inline'] = 'yes';
		if ('yes' != $this->options['enable_shortcode'])
			$matches[2] = preg_replace('/\[' . $this->shortcode_tag . '\s+(.*?)nested="yes"(.*?)]/i', '[' . $this->shortcode_tag . '\\1\\2]', $matches[2]); // note for improvement
		return $this->bwp_geshi_parser($atts, $matches[2]);
	}
	
	function parse_inline_code($content)
	{
		// This might need a better method
		$content = preg_replace_callback("/<code(\s+lang\s*=\s*(?:\"[^\"]*\"|'[^']*'|[^\"'<>\s]+))?>(.*?)<\/code>/isu", array($this, 'geshi_highlight_inline'), $content);
		return $content;
	}

	function geshi_highlight_block($matches)
	{
		$atts = shortcode_parse_atts($matches[1]);
		if ('yes' != $this->options['enable_shortcode'])
			$matches[2] = preg_replace('/\[' . $this->shortcode_tag . '\s+(.*?)nested="yes"(.*?)]/i', '[' . $this->shortcode_tag . '\\1\\2]', $matches[2]); // note for improvement
		return $this->bwp_geshi_parser($atts, $matches[2]);
	}
	
	function parse_block_code($content)
	{
		// This might need a better method
		$content = preg_replace_callback("/<pre((?:\s+(?:lang|inline|extra|toggle|start|nested|parse|strict)\s*=\s*(?:\"[^\"]*\"|'[^']*'|[^\"'<>\s]+))*)?>(.*?)<\/pre>/isu", array($this, 'geshi_highlight_block'), $content);
		return $content;
	}
}
?>