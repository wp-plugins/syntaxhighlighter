<?php /*

**************************************************************************

Plugin Name:  SyntaxHighlighter Evolved
Plugin URI:   http://www.viper007bond.com/wordpress-plugins/syntaxhighlighter/
Version:      3.0.0
Description:  Easily post syntax-highlighted code to your site without having to modify the code at all. Uses Alex Gorbatchev's <a href="http://alexgorbatchev.com/wiki/SyntaxHighlighter">SyntaxHighlighter</a> v2.0.320 and some code by <a href="http://wordpress.com/">Andrew Ozz of Automattic</a>.
Author:       Viper007Bond
Author URI:   http://www.viper007bond.com/

**************************************************************************

Thanks to:

* Alex Gorbatchev for writing such an awesome Javascript-powered synatax
  highlighter script

* Andrew Ozz for writing the TinyMCE plugin

**************************************************************************/

class SyntaxHighlighter {
	// All of these variables are private. Filters are provided for things that can be modified.
	var $pluginver            = '3.0.0';  // Plugin version
	var $agshver              = '3.0.83'; // Alex Gorbatchev's SyntaxHighlighter version
	var $settings             = array();  // Contains the user's settings
	var $defaultsettings      = array();  // Contains the default settings
	var $brushes              = array();  // Array of aliases => brushes
	var $shortcodes           = array();  // Array of shortcodes to use
	var $themes               = array();  // Array of themes
	var $usedbrushes          = array();  // Stores used brushes so we know what to output
	var $encoded              = false;    // Used to mark that a character encode took place
	var $codeformat           = false;    // If set, SyntaxHighlighter::get_code_format() will return this value
	var $content_save_pre_ran = false;    // It's possible for the "content_save_pre" filter to run multiple times, so keep track

	// Initalize the plugin by registering the hooks
	function __construct() {
		if ( ! function_exists( 'esc_html' ) )
			return;

		// Load localization domain
		load_plugin_textdomain( 'syntaxhighlighter', false, '/syntaxhighlighter/localization' );

		// Register brush scripts
		wp_register_script( 'syntaxhighlighter-core',             plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shCore.js'),            array(),                         $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-as3',        plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushAS3.js'),        array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-bash',       plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushBash.js'),       array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-coldfusion', plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushColdFusion.js'), array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-cpp',        plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushCpp.js'),        array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-csharp',     plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushCSharp.js'),     array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-css',        plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushCss.js'),        array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-delphi',     plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushDelphi.js'),     array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-diff',       plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushDiff.js'),       array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-erlang',     plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushErlang.js'),     array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-groovy',     plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushGroovy.js'),     array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-java',       plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushJava.js'),       array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-javafx',     plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushJavaFX.js'),     array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-jscript',    plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushJScript.js'),    array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-perl',       plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushPerl.js'),       array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-php',        plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushPhp.js'),        array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-plain',      plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushPlain.js'),      array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-powershell', plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushPowerShell.js'), array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-python',     plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushPython.js'),     array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-ruby',       plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushRuby.js'),       array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-scala',      plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushScala.js'),      array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-sql',        plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushSql.js'),        array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-vb',         plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushVb.js'),         array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-xml',        plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/shBrushXml.js'),        array('syntaxhighlighter-core'), $this->agshver );

		// Register some popular third-party brushes
		wp_register_script( 'syntaxhighlighter-brush-clojure',    plugins_url('syntaxhighlighter/third-party-brushes/shBrushClojure.js'),          array('syntaxhighlighter-core'), '20090602'     );
		wp_register_script( 'syntaxhighlighter-brush-fsharp',     plugins_url('syntaxhighlighter/third-party-brushes/shBrushFSharp.js'),           array('syntaxhighlighter-core'), '20091003'     );
		wp_register_script( 'syntaxhighlighter-brush-latex',      plugins_url('syntaxhighlighter/third-party-brushes/shBrushLatex.js'),            array('syntaxhighlighter-core'), '20090613'     );
		wp_register_script( 'syntaxhighlighter-brush-matlabkey',  plugins_url('syntaxhighlighter/third-party-brushes/shBrushMatlabKey.js'),        array('syntaxhighlighter-core'), '20091209'     );
		wp_register_script( 'syntaxhighlighter-brush-objc',       plugins_url('syntaxhighlighter/third-party-brushes/shBrushObjC.js'),             array('syntaxhighlighter-core'), '20091207'     );
		wp_register_script( 'syntaxhighlighter-brush-r',          plugins_url('syntaxhighlighter/third-party-brushes/shBrushR.js'),                array('syntaxhighlighter-core'), '20100919'     );

		// Register theme stylesheets
		wp_register_style(  'syntaxhighlighter-core',             plugins_url('syntaxhighlighter/syntaxhighlighter3/styles/shCore.css'),            array(),                         $this->agshver );
		wp_register_style(  'syntaxhighlighter-theme-default',    plugins_url('syntaxhighlighter/syntaxhighlighter3/styles/shThemeDefault.css'),    array('syntaxhighlighter-core'), $this->agshver );
		wp_register_style(  'syntaxhighlighter-theme-django',     plugins_url('syntaxhighlighter/syntaxhighlighter3/styles/shThemeDjango.css'),     array('syntaxhighlighter-core'), $this->agshver );
		wp_register_style(  'syntaxhighlighter-theme-eclipse',    plugins_url('syntaxhighlighter/syntaxhighlighter3/styles/shThemeEclipse.css'),    array('syntaxhighlighter-core'), $this->agshver );
		wp_register_style(  'syntaxhighlighter-theme-emacs',      plugins_url('syntaxhighlighter/syntaxhighlighter3/styles/shThemeEmacs.css'),      array('syntaxhighlighter-core'), $this->agshver );
		wp_register_style(  'syntaxhighlighter-theme-fadetogrey', plugins_url('syntaxhighlighter/syntaxhighlighter3/styles/shThemeFadeToGrey.css'), array('syntaxhighlighter-core'), $this->agshver );
		wp_register_style(  'syntaxhighlighter-theme-midnight',   plugins_url('syntaxhighlighter/syntaxhighlighter3/styles/shThemeMidnight.css'),   array('syntaxhighlighter-core'), $this->agshver );
		wp_register_style(  'syntaxhighlighter-theme-rdark',      plugins_url('syntaxhighlighter/syntaxhighlighter3/styles/shThemeRDark.css'),      array('syntaxhighlighter-core'), $this->agshver );


		// Create array of default settings (you can use the filter to modify these)
		$this->defaultsettings = (array) apply_filters( 'syntaxhighlighter_defaultsettings', array(
			'theme'          => 'default',
			'loadallbrushes' => 0,
			'autolinks'      => 1,
			'classname'      => '',
			'collapse'       => 0,
			'firstline'      => 1,
			'gutter'         => 1,
			'htmlscript'     => 0,
			'light'          => 0,
			'padlinenumbers' => 'true',
			'smarttabs'      => 1,
			'tabsize'        => 4,
			'toolbar'        => 1,
			'wraplines'      => 1,
		) );

		// Create the settings array by merging the user's settings and the defaults
		$usersettings = (array) get_option('syntaxhighlighter_settings');
		$this->settings = wp_parse_args( $usersettings, $this->defaultsettings );


		// Display hooks
		add_filter( 'the_content',                        array(&$this, 'parse_shortcodes'),                              7 ); // Posts
		add_filter( 'comment_text',                       array(&$this, 'parse_shortcodes_comment'),                      7 ); // Comments
		add_filter( 'bp_get_the_topic_post_content',      array(&$this, 'parse_shortcodes'),                              7 ); // BuddyPress

		// Into the database
		add_filter( 'content_save_pre',                   array(&$this, 'encode_shortcode_contents_slashed_noquickedit'), 1 ); // Posts
		add_filter( 'pre_comment_content',                array(&$this, 'encode_shortcode_contents_slashed'),             1 ); // Comments
		add_filter( 'group_forum_post_text_before_save',  array(&$this, 'encode_shortcode_contents_slashed'),             1 ); // BuddyPress
		add_filter( 'group_forum_topic_text_before_save', array(&$this, 'encode_shortcode_contents_slashed'),             1 ); // BuddyPress

		// Out of the database for editing
		add_filter( 'the_editor_content',                 array(&$this, 'the_editor_content'),                            1 ); // Posts
		add_filter( 'comment_edit_pre',                   array(&$this, 'decode_shortcode_contents'),                     1 ); // Comments
		add_filter( 'bp_get_the_topic_text',              array(&$this, 'decode_shortcode_contents'),                     1 ); // BuddyPress
		add_filter( 'bp_get_the_topic_post_edit_text',    array(&$this, 'decode_shortcode_contents'),                     1 ); // BuddyPress

		// Outputting SyntaxHighlighter's JS and CSS
		add_action( 'wp_head',                            array(&$this, 'output_header_placeholder'),                     15 );
		add_action( 'admin_head',                         array(&$this, 'output_header_placeholder'),                     15 ); // For comments
		add_action( 'wp_footer',                          array(&$this, 'maybe_output_scripts'),                          15 );
		add_action( 'admin_footer',                       array(&$this, 'maybe_output_scripts'),                          15 ); // For comments

		// Admin hooks
		add_action( 'admin_init',                         array(&$this, 'register_setting') );
		add_action( 'admin_menu',                         array(&$this, 'register_settings_page') );
		add_action( 'admin_head',                         array(&$this, 'output_shortcodes_for_tinymce') );
		add_filter( 'mce_external_plugins',               array(&$this, 'add_tinymce_plugin') );
		add_filter( 'tiny_mce_version',                   array(&$this, 'break_tinymce_cache') );
		add_filter( 'save_post',                          array(&$this, 'mark_as_encoded'),                               10, 2 );
		add_filter( 'plugin_action_links',                array(&$this, 'settings_link'),                                 10, 2 );

		// Register widget hooks
		// Requires change added in WordPress 2.9
		if ( class_exists('WP_Embed') ) {
			add_filter( 'widget_text',                    array(&$this, 'widget_text_output'),                            7, 2 );
			add_filter( 'widget_update_callback',         array(&$this, 'widget_text_save'),                              1, 4 );
			add_filter( 'widget_form_callback',           array(&$this, 'widget_text_form'),                              1, 2 );
		}


		// Create list of brush aliases and map them to their real brushes
		// The key is the language alias
		// The value is the script handle suffix: syntaxhighlighter-brush-ThisBitHere  (your plugin needs to register the script itself)
		$this->brushes = (array) apply_filters( 'syntaxhighlighter_brushes', array(
			'as3'           => 'as3',
			'actionscript3' => 'as3',
			'bash'          => 'bash',
			'shell'         => 'bash',
			'coldfusion'    => 'coldfusion',
			'cf'            => 'coldfusion',
			'clojure'       => 'clojure',
			'clj'           => 'clojure',
			'cpp'           => 'cpp',
			'c'             => 'cpp',
			'c-sharp'       => 'csharp',
			'csharp'        => 'csharp',
			'css'           => 'css',
			'delphi'        => 'delphi',
			'pas'           => 'delphi',
			'pascal'        => 'delphi',
			'diff'          => 'diff',
			'patch'         => 'diff',
			'erl'           => 'erlang',
			'erlang'        => 'erlang',
			'fsharp'        => 'fsharp',
			'groovy'        => 'groovy',
			'java'          => 'java',
			'jfx'           => 'javafx',
			'javafx'        => 'javafx',
			'js'            => 'jscript',
			'jscript'       => 'jscript',
			'javascript'    => 'jscript',
			'latex'         => 'latex',
			'tex'           => 'latex',
			'matlab'        => 'matlabkey',
			'objc'          => 'objc',
			'obj-c'         => 'objc',
			'perl'          => 'perl',
			'pl'            => 'perl',
			'php'           => 'php',
			'plain'         => 'plain',
			'text'          => 'plain',
			'ps'            => 'powershell',
			'powershell'    => 'powershell',
			'py'            => 'python',
			'python'        => 'python',
			//'r'             => 'r', // Too short
			'splus'         => 'r',
			'rails'         => 'ruby',
			'rb'            => 'ruby',
			'ror'           => 'ruby',
			'ruby'          => 'ruby',
			'scala'         => 'scala',
			'sql'           => 'sql',
			'vb'            => 'vb',
			'vbnet'         => 'vb',
			'xml'           => 'xml',
			'xhtml'         => 'xml',
			'xslt'          => 'xml',
			'html'          => 'xml',
			'xhtml'         => 'xml',
		) );

		// Create a list of shortcodes to use. You can use the filter to add/remove ones.
		// If the language/lang parameter is left out, it's assumed the shortcode name is the language.
		// If that's invalid, then "plain" is used.
		$this->shortcodes = array( 'sourcecode', 'source', 'code' );
		$this->shortcodes = array_merge( $this->shortcodes, array_keys( $this->brushes ) );
		unset( $this->shortcodes[array_search( 'latex', $this->shortcodes )] ); // Remove "latex" shortcode (it'll collide)
		$this->shortcodes = (array) apply_filters( 'syntaxhighlighter_shortcodes', $this->shortcodes );

		// Create list of themes and their human readable names
		// Plugins can add to this list: http://www.viper007bond.com/wordpress-plugins/syntaxhighlighter/adding-a-new-theme/
		$this->themes = (array) apply_filters( 'syntaxhighlighter_themes', array(
			'default'    => __( 'Default',      'syntaxhighlighter' ),
			'django'     => __( 'Django',       'syntaxhighlighter' ),
			'eclipse'    => __( 'Eclipse',      'syntaxhighlighter' ),
			'emacs'      => __( 'Emacs',        'syntaxhighlighter' ),
			'fadetogrey' => __( 'Fade to Grey', 'syntaxhighlighter' ),
			'midnight'   => __( 'Midnight',     'syntaxhighlighter' ),
			'rdark'      => __( 'RDark',        'syntaxhighlighter' ),
			'none'       => __( '[None]',       'syntaxhighlighter' ),
		) );

		// Other special characters that need to be encoded before going into the database (namely to work around kses)
		$this->specialchars = (array) apply_filters( 'syntaxhighlighter_specialchars', array(
			'\0' => '&#92;&#48;',
		) );
	}


	// Register the settings page
	function register_settings_page() {
		add_options_page( __( 'SyntaxHighlighter Settings', 'syntaxhighlighter' ), __( 'SyntaxHighlighter', 'syntaxhighlighter' ), 'manage_options', 'syntaxhighlighter', array(&$this, 'settings_page') );
	}


	// Register the plugin's setting
	function register_setting() {
		register_setting( 'syntaxhighlighter_settings', 'syntaxhighlighter_settings', array(&$this, 'validate_settings') );
	}


	// Add the custom TinyMCE plugin which wraps plugin shortcodes in <pre> in TinyMCE
	function add_tinymce_plugin( $plugins ) {
		$plugins['syntaxhighlighter'] = plugins_url('syntaxhighlighter/syntaxhighlighter3_mce.js');
		return $plugins;
	}


	// Break the TinyMCE cache
	function break_tinymce_cache( $version ) {
		return $version . '-sh' . $this->pluginver;
	}


	// Add a "Settings" link to the plugins page
	function settings_link( $links, $file ) {
		static $this_plugin;
		
		if( empty($this_plugin) )
			$this_plugin = plugin_basename(__FILE__);

		if ( $file == $this_plugin )
			$links[] = '<a href="' . admin_url( 'options-general.php?page=syntaxhighlighter' ) . '">' . __( 'Settings', 'syntaxhighlighter' ) . '</a>';

		return $links;
	}


	// Output list of shortcode tags for the TinyMCE plugin
	function output_shortcodes_for_tinymce() {
		$shortcodes = array();

		foreach ( $this->shortcodes as $shortcode )
			$shortcodes[] = preg_quote( $shortcode );

		echo "<script type='text/javascript'>\n";
		echo "	var syntaxHLcodes = '" . implode( '|', $shortcodes ) . "';\n";
		echo "</script>\n";
	}


	// Additional CSS for the front end (to ensure themes don't screw too much with the code)
	function enforce_font_size() {
		echo '	<style type="text/css">.syntaxhighlighter { font-size: 12px !important; }</style>' . "\n";
	}


	// A filter function that runs do_shortcode() but only with this plugin's shortcodes
	function shortcode_hack( $content, $callback ) {
		global $shortcode_tags;

		// Backup current registered shortcodes and clear them all out
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();

		// Register all of this plugin's shortcodes
		foreach ( $this->shortcodes as $shortcode )
			add_shortcode( $shortcode, $callback );

		// Do the shortcodes (only this plugins's are registered)
		$content = do_shortcode( $content );

		// Put the original shortcodes back
		$shortcode_tags = $orig_shortcode_tags;

		return $content;
	}


	// The main filter for the post contents. The regular shortcode filter can't be used as it's post-wpautop().
	function parse_shortcodes( $content ) {
		return $this->shortcode_hack( $content, array(&$this, 'shortcode_callback') );
	}


	// HTML entity encode the contents of shortcodes
	function encode_shortcode_contents( $content ) {
		return $this->shortcode_hack( $content, array(&$this, 'encode_shortcode_contents_callback') );
	}


	// HTML entity encode the contents of shortcodes. Expects slashed content.
	function encode_shortcode_contents_slashed( $content ) {
		return addslashes( $this->encode_shortcode_contents( stripslashes( $content ) ) );
	}


	// HTML entity encode the contents of shortcodes. Expects slashed content. Aborts if AJAX.
	function encode_shortcode_contents_slashed_noquickedit( $content ) {

		// In certain weird circumstances, the content gets run through "content_save_pre" twice
		// Keep track and don't allow this filter to be run twice
		// I couldn't easily figure out why this happens and didn't bother looking into it further as this works fine
		if ( true == $this->content_save_pre_ran )
			return $content;
		$this->content_save_pre_ran = true;

		// Post quick edits aren't decoded for display, so we don't need to encode them (again)
		if ( !empty($_POST) && !empty($_POST['action']) && 'inline-save' == $_POST['action'] )
			return $content;

		return $this->encode_shortcode_contents_slashed( $content );
	}


	// HTML entity decode the contents of shortcodes
	function decode_shortcode_contents( $content ) {
		return $this->shortcode_hack( $content, array(&$this, 'decode_shortcode_contents_callback') );
	}


	// The callback function for SyntaxHighlighter::encode_shortcode_contents()
	function encode_shortcode_contents_callback( $atts, $code = '', $tag = false ) {
		$this->encoded = true;
		$code = str_replace( array_keys($this->specialchars), array_values($this->specialchars), htmlspecialchars( $code ) );
		return '[' . $tag . $this->atts2string( $atts ) . "]{$code}[/$tag]";
	}


	// The callback function for SyntaxHighlighter::decode_shortcode_contents()
	// Shortcode attribute values need to not be quoted with TinyMCE disabled for some reason (weird bug)
	function decode_shortcode_contents_callback( $atts, $code = '', $tag = false ) {
		$quotes = ( user_can_richedit() ) ? true : false;
		$code = str_replace(  array_values($this->specialchars), array_keys($this->specialchars), htmlspecialchars_decode( $code ) );
		return '[' . $tag . $this->atts2string( $atts, $quotes ) . "]{$code}[/$tag]";
	}


	// Dynamically format the post content for the edit form
	function the_editor_content( $content ) {
		global $post;

		// New code format (stored encoded in database)
		if ( 2 == $this->get_code_format( $post ) ) {
			// If TinyMCE is disabled or the HTML tab is set to be displayed first, we need to decode the HTML
			if ( !user_can_richedit() || 'html' == wp_default_editor() )
				$content = $this->decode_shortcode_contents( $content );
		}

		// Old code format (stored raw in database)
		else {
			// If TinyMCE is enabled and is set to be displayed first, we need to encode the HTML
			if ( user_can_richedit() && 'html' != wp_default_editor() )
				$content = $this->encode_shortcode_contents( $content );
		}

		return $content;
	}


	// Run SyntaxHighlighter::encode_shortcode_contents() on the contents of the text widget
	function widget_text_save( $instance, $new_instance, $old_instance, $widgetclass ) {
		if ( 'text' == $widgetclass->id_base ) {
			// Re-save the widget settings but this time with the shortcode contents encoded
			$new_instance['text'] = $this->encode_shortcode_contents( $new_instance['text'] );
			$instance = $widgetclass->update( $new_instance, $old_instance );

			// And flag it as encoded
			$instance['syntaxhighlighter_encoded'] = true;
		}

		return $instance;
	}


	// Run SyntaxHighlighter::decode_shortcode_contents_callback() on the contents of the text widget form
	function widget_text_form( $instance, $widgetclass ) {
		if ( 'text' == $widgetclass->id_base && !empty($instance['syntaxhighlighter_encoded']) ) {
			$instance['text'] = $this->shortcode_hack( $instance['text'], array(&$this, 'decode_shortcode_contents_callback') );
		}

		return $instance;
	}


	// Run SyntaxHighlighter::parse_shortcodes() on the contents of a text widget
	function widget_text_output( $content, $instance = false ) {
		$this->codeformat = ( false === $instance || empty($instance['syntaxhighlighter_encoded']) ) ? 1 : 2;
		$content = $this->parse_shortcodes( $content );
		$this->codeformat = false;

		return $content;
	}


	// Run SyntaxHighlighter::parse_shortcodes() on the contents of a comment
	function parse_shortcodes_comment( $content ) {
		$this->codeformat = 2;
		$content = $this->parse_shortcodes( $content );
		$this->codeformat = false;

		return $content;
	}


	// This function determines what version of SyntaxHighlighter was used when the post was written
	// This is because the code was stored differently for different versions of SyntaxHighlighter
	function get_code_format( $post ) {
		if ( false !== $this->codeformat )
			return $this->codeformat;

		if ( empty($post) )
			$post = new stdClass();

		if ( null !== $version = apply_filters( 'syntaxhighlighter_pre_getcodeformat', null, $post ) )
			return $version;

		$version = ( empty($post->ID) || get_post_meta( $post->ID, '_syntaxhighlighter_encoded', true ) || get_post_meta( $post->ID, 'syntaxhighlighter_encoded', true ) ) ? 2 : 1;

		return apply_filters( 'syntaxhighlighter_getcodeformat', $version, $post );
	}


	// Adds a post meta saying that HTML entities are encoded (for backwards compatibility)
	function mark_as_encoded( $post_ID, $post ) {
		if ( false == $this->encoded || 'revision' == $post->post_type )
			return;

		delete_post_meta( $post_ID, 'syntaxhighlighter_encoded' ); // Previously used
		add_post_meta( $post_ID, '_syntaxhighlighter_encoded', true, true );
	}


	// Transforms an attributes array into a 'key="value"' format (i.e. reverses the process)
	function atts2string( $atts, $quotes = true ) {
		if ( empty($atts) )
			return '';

		$atts = $this->attributefix( $atts );

		// Re-map [code="php"] style tags
		if ( isset($atts[0]) ) {
			if ( empty($atts['language']) )
				$atts['language'] = $atts[0];

			unset($atts[0]);
		}

		$strings = array();
		foreach ( $atts as $key => $value )
			$strings[] = ( $quotes ) ? $key . '="' . esc_attr( $value ) . '"' : $key . '=' . esc_attr( $value );

		return ' ' . implode( ' ', $strings );
	}


	// Simple function for escaping just single quotes (the original js_escape() escapes more than we need)
	function js_escape_singlequotes( $string ) {
		return str_replace( "'", "\'", $string );
	}


	// Output an anchor in the header for the Javascript to use
	// Might as well use it to output the version to help me debug people's sites
	function output_header_placeholder() {
		echo '<meta id="syntaxhighlighteranchor" name="syntaxhighlighter-version" content="' . esc_attr( $this->pluginver ) . '" />' . "\n";
	}


	// Output any needed scripts. This is meant for the footer.
	function maybe_output_scripts() {
		global $wp_styles;

		if ( 1 == $this->settings['loadallbrushes'] )
			$this->usedbrushes = array_flip( array_values( $this->brushes ) );

		if ( empty($this->usedbrushes) )
			return;

		$scripts = array();
		foreach ( $this->usedbrushes as $brush => $unused )
			$scripts[] = 'syntaxhighlighter-brush-' . strtolower( $brush );

		wp_print_scripts( $scripts );

		// Stylesheets can't be in the footer, so inject them via Javascript
		echo "<script type='text/javascript'>\n";
		echo "	(function(){\n";
		echo "		var corecss = document.createElement('link');\n";
		echo "		var themecss = document.createElement('link');\n";

		if ( !is_a($wp_styles, 'WP_Styles') )
			$wp_styles = new WP_Styles();

		$needcore = false;
		if ( 'none' == $this->settings['theme'] ) {
			$needcore = true;
		} else {
			$theme = ( !empty($this->themes[$this->settings['theme']]) ) ? strtolower($this->settings['theme']) : $this->defaultsettings['theme'];
			$theme = 'syntaxhighlighter-theme-' . $theme;

			// See if the requested theme has been registered
			if ( !empty($wp_styles) && !empty($wp_styles->registered) && !empty($wp_styles->registered[$theme]) && !empty($wp_styles->registered[$theme]->src) ) {

				// Users can register their own stylesheet and may opt to not load the core stylesheet if they wish for some reason
				if ( is_array($wp_styles->registered[$theme]->deps) && in_array( 'syntaxhighlighter-core', $wp_styles->registered[$theme]->deps ) )
					$needcore = true;
			}

			// Otherwise use the default theme
			else {
				$theme = 'syntaxhighlighter-theme-' . $this->defaultsettings['theme'];
				$needcore = true;
			}
		}

		if ( $needcore && !empty($wp_styles) && !empty($wp_styles->registered) && !empty($wp_styles->registered['syntaxhighlighter-core']) && !empty($wp_styles->registered['syntaxhighlighter-core']->src) ) :
			$corecssurl = add_query_arg( 'ver', $this->agshver, $wp_styles->registered['syntaxhighlighter-core']->src );
			$corecssurl = apply_filters( 'syntaxhighlighter_csscoreurl', $corecssurl );
?>
		var corecssurl = "<?php echo esc_js( $corecssurl ); ?>";
		if ( corecss.setAttribute ) {
				corecss.setAttribute( "rel", "stylesheet" );
				corecss.setAttribute( "type", "text/css" );
				corecss.setAttribute( "href", corecssurl );
		} else {
				corecss.rel = "stylesheet";
				corecss.href = corecssurl;
		}
		document.getElementsByTagName("head")[0].insertBefore( corecss, document.getElementById("syntaxhighlighteranchor") );
<?php
		endif; // Endif $needcore

		if ( 'none' != $this->settings['theme'] ) : ?>
		var themecssurl = "<?php echo esc_js( apply_filters( 'syntaxhighlighter_cssthemeurl', add_query_arg( 'ver', $this->agshver, $wp_styles->registered[$theme]->src ) ) ); ?>";
		if ( themecss.setAttribute ) {
				themecss.setAttribute( "rel", "stylesheet" );
				themecss.setAttribute( "type", "text/css" );
				themecss.setAttribute( "href", themecssurl );
		} else {
				themecss.rel = "stylesheet";
				themecss.href = themecssurl;
		}
		//document.getElementById("syntaxhighlighteranchor").appendChild(themecss);
		document.getElementsByTagName("head")[0].insertBefore( themecss, document.getElementById("syntaxhighlighteranchor") );
<?php
		endif; // Endif none != theme

		echo "	})();\n";

		echo "	SyntaxHighlighter.config.clipboardSwf = '" . esc_js( apply_filters( 'syntaxhighlighter_clipboardurl', plugins_url('syntaxhighlighter/syntaxhighlighter3/scripts/clipboard.swf') ) ) . "';\n";
		echo "	SyntaxHighlighter.config.strings.expandSource = '" . $this->js_escape_singlequotes( __( 'show source', 'syntaxhighlighter' ) ) . "';\n";
		echo "	SyntaxHighlighter.config.strings.viewSource = '" . $this->js_escape_singlequotes( __( 'view source', 'syntaxhighlighter' ) ) . "';\n";
		echo "	SyntaxHighlighter.config.strings.copyToClipboard = '" . $this->js_escape_singlequotes( __( 'copy to clipboard', 'syntaxhighlighter' ) ) . "';\n";
		echo "	SyntaxHighlighter.config.strings.copyToClipboardConfirmation = '" . $this->js_escape_singlequotes( __( 'The code is in your clipboard now', 'syntaxhighlighter' ) ) . "';\n";
		echo "	SyntaxHighlighter.config.strings.print = '" . $this->js_escape_singlequotes( __( 'print', 'syntaxhighlighter' ) ) . "';\n";
		echo "	SyntaxHighlighter.config.strings.help = '" . $this->js_escape_singlequotes( __( '?', 'syntaxhighlighter' ) ) . "';\n";
		echo "	SyntaxHighlighter.config.strings.alert = '" . $this->js_escape_singlequotes( __( 'SyntaxHighlighter\n\n', 'syntaxhighlighter' ) ) . "';\n";
		echo "	SyntaxHighlighter.config.strings.noBrush = '" . $this->js_escape_singlequotes( __( "Can't find brush for: ", 'syntaxhighlighter' ) ) . "';\n";
		echo "	SyntaxHighlighter.config.strings.brushNotHtmlScript = '" . $this->js_escape_singlequotes( __( "Brush wasn't configured for html-script option: ", 'syntaxhighlighter' ) ) . "';\n";

		if ( 1 != $this->settings['autolinks'] )
			echo "	SyntaxHighlighter.defaults['auto-links'] = false;\n";

		if ( !empty($this->settings['classname']) )
			echo "	SyntaxHighlighter.defaults['class-name'] = '" . $this->js_escape_singlequotes( $this->settings['classname'] ) . "';\n";

		if ( 1 == $this->settings['collapse'] )
			echo "	SyntaxHighlighter.defaults['collapse'] = true;\n";

		if ( 1 != $this->settings['firstline'] )
			echo "	SyntaxHighlighter.defaults['first-line'] = " . $this->settings['firstline'] . ";\n";

		if ( 1 != $this->settings['gutter'] )
			echo "	SyntaxHighlighter.defaults['gutter'] = false;\n";

		//if ( 1 == $this->settings['htmlscript'] )
		//	echo "	SyntaxHighlighter.defaults['html-script'] = true;\n";

		if ( 1 == $this->settings['light'] )
			echo "	SyntaxHighlighter.defaults['light'] = true;\n";

		if ( 'true' != $this->settings['padlinenumbers'] ) {
			echo "	SyntaxHighlighter.defaults['pad-line-numbers'] = ";
			if ( 'false' == $this->settings['padlinenumbers'] )
				echo 'false';
			else
				echo (int) $this->settings['padlinenumbers'];
			echo ";\n";
		}

		if ( 1 != $this->settings['smarttabs'] )
			echo "	SyntaxHighlighter.defaults['smart-tabs'] = false;\n";

		if ( 4 != $this->settings['tabsize'] )
			echo "	SyntaxHighlighter.defaults['tab-size'] = " . $this->settings['tabsize'] . ";\n";

		if ( 1 != $this->settings['toolbar'] )
			echo "	SyntaxHighlighter.defaults['toolbar'] = false;\n";

		if ( 1 != $this->settings['wraplines'] )
			echo "	SyntaxHighlighter.defaults['wrap-lines'] = false;\n";

?>	SyntaxHighlighter.all();
</script>
<?php
	}


	// No-name attribute fixing
	function attributefix( $atts = array() ) {
		if ( empty($atts[0]) )
			return $atts;

		// Quoted value
		if ( 0 !== preg_match( '#=("|\')(.*?)\1#', $atts[0], $match ) )
			$atts[0] = $match[2];

		// Unquoted value
		elseif ( '=' == substr( $atts[0], 0, 1 ) )
			$atts[0] = substr( $atts[0], 1 );

		return $atts;
	}


	// Shortcode handler for transforming the shortcodes to their final <pre>'s
	function shortcode_callback( $atts, $code = '', $tag = false ) {
		global $post;

		if ( false === $tag || empty($code) )
			return $code;

		// Avoid PHP notices
		if ( !isset($post) )
			$post = null;

		$code = apply_filters( 'syntaxhighlighter_precode', $code, $atts, $tag );

		// Error fixing for [tag="language"]
		if ( isset($atts[0]) ) {
			$atts = $this->attributefix( $atts );
			$atts['language'] = $atts[0];
			unset($atts[0]);
		}

		// Default out all of the available parameters to "false" (easy way to check if they're set or not)
		// Note this isn't the same as if the user passes the string "false" to the shortcode
		$atts = (array) apply_filters( 'syntaxhighlighter_shortcodeatts', shortcode_atts( array(
			'language'       => false,
			'lang'           => false,
			'type'           => false, // language alias
			'autolinks'      => false,
			'classname'      => false,
			'collapse'       => false,
			'firstline'      => false,
			'fontsize'       => false,
			'gutter'         => false,
			'highlight'      => false,
			'htmlscript'     => false,
			'light'          => false,
			'padlinenumbers' => false,
			'smarttabs'      => false,
			'tabsize'        => false,
			'title'          => false,
			'toolbar'        => false,
			'wraplines'      => false,
		), $atts ) );

		// Check for language shortcode tag such as [php]code[/php]
		if ( isset($this->brushes[$tag]) ) {
			$lang = $tag;
		}

		// If a valid tag is not used, it must be sourcecode/source/code
		else {
			$atts = $this->attributefix( $atts );

			// Check for the "language" attribute
			if ( false !== $atts['language'] )
				$lang = $atts['language'];

			// Check for the "lang" attribute
			elseif ( false !== $atts['lang'] )
				$lang = $atts['lang'];

			// Default to plain text
			else
				$lang = 'text';

			// All language aliases are lowercase
			$lang = strtolower( $lang );

			// Validate passed attribute
			if ( !isset($this->brushes[$lang]) )
				return $code;
		}

		// Switch from the alias to the real brush name (so custom aliases can be used)
		$lang = $this->brushes[$lang];

		// Register this brush as used so it's script will be outputted
		$this->usedbrushes[$lang] = true;

		$params = array();
		$params[] = "brush: $lang;";

		// Fix bug that prevents collapse from working if the toolbar is off
		if ( 'true' == $atts['collapse'] || '1' === $atts['collapse'] ) {
			$atts['toolbar'] = 'true';
			$atts['light'] = 'false';
		}

		// Parameter renaming (the shortcode API doesn't like parameter names with dashes)
		$rename_map = array(
			'autolinks'      => 'auto-links',
			'classname'      => 'class-name',
			'firstline'      => 'first-line',
			'fontsize'       => 'font-size',
			'htmlscript'     => 'html-script',
			'padlinenumbers' => 'pad-line-numbers',
			'smarttabs'      => 'smart-tabs',
			'tabsize'        => 'tab-size',
			'wraplines'      => 'wrap-lines',
		);

		// Allowed configuration parameters and their type
		// Use the proper names (see above)
		$allowed_atts = (array) apply_filters( 'syntaxhighlighter_allowedatts', array(
			'auto-links'       => 'boolean',
			'class-name'       => 'other',
			'collapse'         => 'boolean',
			'first-line'       => 'integer',
			'font-size'        => 'integer',
			'gutter'           => 'boolean',
			'highlight'        => 'other',
			'html-script'      => 'boolean',
			'light'            => 'boolean',
			'pad-line-numbers' => 'other',
			'smart-tabs'       => 'boolean',
			'tab-size'         => 'integer',
			'title'            => 'other',
			'toolbar'          => 'boolean',
			'wrap-lines'       => 'boolean',
		) );

		$title = '';

		// Sanitize configuration parameters and such
		foreach ( $atts as $key => $value ) {
			$key = strtolower( $key );

			// Put back parameter names that have been renamed for shortcode use
			if ( !empty($rename_map[$key]) )
				$key = $rename_map[$key];

			// This this parameter if it's unknown, not set, or the language which was already handled
			if ( empty($allowed_atts[$key]) || false === $value || in_array( $key, array( 'language', 'lang' ) ) )
				continue;

			// Sanitize values
			switch ( $allowed_atts[$key] ) {
				case 'boolean':
					$value = strtolower( $value );
					if ( 'true' === $value || '1' === $value || 'on' == $value )
						$value = 'true';
					elseif ( 'false' === $value || '0' === $value || 'off' == $value )
						$value = 'false';
					else
						continue 2; // Invalid value, ditch parameter
					break;

				// integer
				case 'integer':
					$value = (int) $value;
					break;
			}

			// Sanitize the "classname" parameter
			if ( 'classname' == $key )
				$value = trim( preg_replace( '/[^a-zA-Z0-9 _-]/i', '', $value ) );

			// Special sanitization for "pad-line-numbers"
			if ( 'pad-line-numbers' == $key ) {
				$value = strtolower( $value );
				if ( 'true' === $value || '1' === $value )
					$value = 'true';
				elseif ( 'false' === $value || '0' === $value )
					$value = 'false';
				else
					$value = (int) $value;
			}

			// Add % sign to "font-size"
			if ( 'font-size' == $key )
				$value = $value . '%';

			// If "html-script", then include the XML brush as it's needed
			if ( 'html-script' == $key && 'true' == $value )
				$this->usedbrushes['xml'] = true;

			// Sanitize row highlights
			if ( 'highlight' == $key ) {
				if ( false === strpos( $value, ',' ) ) {
					$value = (int) $value;
				} else {
					$lines = explode( ',', $value );
					$highlights = array();

					foreach ( $lines as $line ) {
						// Line range
						if ( false !== strpos( $line, '-' ) ) {
							list( $range_start, $range_end ) = array_map( 'intval', explode( '-', $line ) );
							if ( ! $range_start || ! $range_end || $range_end <= $range_start )
								continue;

							for ( $i = $range_start; $i <= $range_end; $i++ )
								$highlights[] = $i;
						} else {
							$highlights[] = (int) $line;
						}
					}

					natsort( $highlights );

					$value = implode( ',', $highlights );
				}

				if ( empty( $value ) )
					continue;

				// Wrap highlight in [ ]
				$params[] = "$key: [$value];";
				continue;
			}

			$params[] = "$key: $value;";

			// Set the title variable if the title parameter is set (but not for feeds)
			if ( 'title' == $key && ! is_feed() )
				$title = ' title="' . esc_attr( $value ) . '"';
		}

		$code = ( false === strpos( $code, '<' ) && false === strpos( $code, '>' ) && 2 == $this->get_code_format($post) ) ? strip_tags( $code ) : htmlspecialchars( $code );

		return apply_filters( 'syntaxhighlighter_htmlresult', '<pre class="' . esc_attr( implode( ' ', $params ) ) . '"' . $title . '>' . $code . '</pre>' );;
	}


	// Settings page
	function settings_page() { ?>

<script type="text/javascript">
// <![CDATA[
	jQuery(document).ready(function($) {
		// Confirm pressing of the "Reset to Defaults" button
		$("#syntaxhighlighter-defaults").click(function(){
			var areyousure = confirm("<?php echo esc_js( __( 'Are you sure you want to reset your settings to the defaults?', 'syntaxhighlighter' ) ); ?>");
			if ( true != areyousure ) return false;
		});
<?php if ( !empty( $_GET['defaults'] ) ) : ?>
		$("#message p strong").text("<?php echo esc_js( __( 'Settings reset to defaults.', 'syntaxhighlighter' ) ); ?>");
<?php endif; ?>
	});
// ]]>
</script>

<div class="wrap">
<?php if ( function_exists('screen_icon') ) screen_icon(); ?>
	<h2><?php _e( 'SyntaxHighlighter Settings', 'syntaxhighlighter' ); ?></h2>

	<form method="post" action="options.php">

	<?php settings_fields('syntaxhighlighter_settings'); ?>


	<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="syntaxhighlighter-theme"><?php _e( 'Color Theme', 'syntaxhighlighter' ); ?></label></th>
			<td>
				<select name="syntaxhighlighter_settings[theme]" id="syntaxhighlighter-theme" class="postform">
<?php
					foreach ( $this->themes as $theme => $name ) {
						echo '					<option value="' . esc_attr($theme) . '"';
						selected( $this->settings['theme'], $theme );
						echo '>' . htmlspecialchars($name) . "</option>\n";
					}
?>
				</select>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e( 'Load All Brushes', 'syntaxhighlighter' ); ?></th>
			<td>
				<fieldset>
					<legend class="hidden"><?php _e( 'Load All Brushes', 'syntaxhighlighter' ); ?></legend>
					<label for="syntaxhighlighter-loadallbrushes"><input name="syntaxhighlighter_settings[loadallbrushes]" type="checkbox" id="syntaxhighlighter-loadallbrushes" value="1" <?php checked( $this->settings['loadallbrushes'], 1 ); ?> /> <?php _e( 'Always load all language files (for directly using <code>&lt;pre&gt;</code> tags rather than shortcodes)<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;If left unchecked (default), then language files will only be loaded when needed', 'syntaxhighlighter' ); ?></label>
				</fieldset>
			</td>
		</tr>
	</table>

	<h3><?php _e( 'Defaults', 'syntaxhighlighter' ); ?></h3>

	<p><?php _e( 'All of the settings below can be configured on a per-code box basis, but you can control the defaults of all code boxes here.', 'syntaxhighlighter' ); ?></p>

	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e( 'Miscellaneous', 'syntaxhighlighter' ); ?></th>
			<td>
				<fieldset>
					<legend class="hidden"><?php _e( 'Miscellaneous', 'syntaxhighlighter' ); ?></legend>

					<label for="syntaxhighlighter-gutter"><input name="syntaxhighlighter_settings[gutter]" type="checkbox" id="syntaxhighlighter-gutter" value="1" <?php checked( $this->settings['gutter'], 1 ); ?> /> <?php _e( 'Display line numbers', 'syntaxhighlighter' ); ?></label><br />
					<label for="syntaxhighlighter-toolbar"><input name="syntaxhighlighter_settings[toolbar]" type="checkbox" id="syntaxhighlighter-toolbar" value="1" <?php checked( $this->settings['toolbar'], 1 ); ?> /> <?php _e( 'Display the toolbar', 'syntaxhighlighter' ); ?></label><br />
					<label for="syntaxhighlighter-autolinks"><input name="syntaxhighlighter_settings[autolinks]" type="checkbox" id="syntaxhighlighter-autolinks" value="1" <?php checked( $this->settings['autolinks'], 1 ); ?> /> <?php _e( 'Automatically make URLs clickable', 'syntaxhighlighter' ); ?></label><br />
					<label for="syntaxhighlighter-collapse"><input name="syntaxhighlighter_settings[collapse]" type="checkbox" id="syntaxhighlighter-collapse" value="1" <?php checked( $this->settings['collapse'], 1 ); ?> /> <?php _e( 'Collapse code boxes', 'syntaxhighlighter' ); ?></label><br />
					<label for="syntaxhighlighter-light"><input name="syntaxhighlighter_settings[light]" type="checkbox" id="syntaxhighlighter-light" value="1" <?php checked( $this->settings['light'], 1 ); ?> /> <?php _e( 'Use the light display mode, best for single lines of code', 'syntaxhighlighter' ); ?></label><br />
					<label for="syntaxhighlighter-smarttabs"><input name="syntaxhighlighter_settings[smarttabs]" type="checkbox" id="syntaxhighlighter-smarttabs" value="1" <?php checked( $this->settings['smarttabs'], 1 ); ?> /> <?php _e( 'Use smart tabs allowing tabs being used for alignment', 'syntaxhighlighter' ); ?></label><br />
					<label for="syntaxhighlighter-wraplines"><input name="syntaxhighlighter_settings[wraplines]" type="checkbox" id="syntaxhighlighter-wraplines" value="1" <?php checked( $this->settings['wraplines'], 1 ); ?> /> <?php _e( 'Wrap long lines (disabling this will make a scrollbar show instead)', 'syntaxhighlighter' ); ?></label><br />
					<!--<label for="syntaxhighlighter-htmlscript"><input name="syntaxhighlighter_settings[htmlscript]" type="checkbox" id="syntaxhighlighter-htmlscript" value="1" <?php checked( $this->settings['htmlscript'], 1 ); ?> /> <?php _e( 'Enable &quot;HTML script&quot; mode by default (see the bottom of this page for details). Checking this box is not recommended as this mode only works with certain languages.', 'syntaxhighlighter' ); ?></label>-->
				</fieldset>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="syntaxhighlighter-classname"><?php _e( 'Additional CSS Class(es)', 'syntaxhighlighter' ); ?></label></th>
			<td><input name="syntaxhighlighter_settings[classname]" type="text" id="syntaxhighlighter-classname" value="<?php echo esc_attr( $this->settings['classname'] ); ?>" class="regular-text" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="syntaxhighlighter-firstline"><?php _e( 'Starting Line Number', 'syntaxhighlighter' ); ?></label></th>
			<td><input name="syntaxhighlighter_settings[firstline]" type="text" id="syntaxhighlighter-firstline" value="<?php echo esc_attr( $this->settings['firstline'] ); ?>" class="small-text" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="syntaxhighlighter-padlinenumbers"><?php _e( 'Line Number Padding', 'syntaxhighlighter' ); ?></label></th>
			<td>
				<select name="syntaxhighlighter_settings[padlinenumbers]" id="syntaxhighlighter-padlinenumbers" class="postform">
<?php
					$linepaddings = array(
						'false' => __( 'Off', 'syntaxhighlighter' ),
						'true'  => __( 'Automatic', 'syntaxhighlighter' ),
						1       => 1,
						2       => 2,
						3       => 3,
						4       => 4,
						5       => 5,
					);

					foreach ( $linepaddings as $value => $name ) {
						echo '					<option value="' . esc_attr( $value ) . '"';
						selected( $this->settings['padlinenumbers'], $value );
						echo '>' . esc_html( $name ) . "</option>\n";
					}
?>
				</select>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="syntaxhighlighter-tabsize"><?php _e( 'Tab Size', 'syntaxhighlighter' ); ?></label></th>
			<td><input name="syntaxhighlighter_settings[tabsize]" type="text" id="syntaxhighlighter-tabsize" value="<?php echo esc_attr( $this->settings['tabsize'] ); ?>" class="small-text" /></td>
		</tr>
	</table>

	<p class="submit">
		<input type="submit" name="syntaxhighlighter-submit" class="button-primary" value="<?php _e( 'Save Changes') ?>" />
		<input type="submit" name="syntaxhighlighter-defaults" id="syntaxhighlighter-defaults" class="button-primary" value="<?php _e( 'Reset to Defaults', 'syntaxhighlighter' ) ?>" />
	</p>

	</form>

	<h3><?php _e( 'Preview', 'syntaxhighlighter' ); ?></h3>

	<p><?php _e( 'Click &quot;Save Changes&quot; to update this preview.', 'syntaxhighlighter' ); ?>

	<?php

		// Site owners may opt to disable the short tags, i.e. [php]
		$democode = apply_filters( 'syntaxhighlighter_democode', '[sourcecode language="php" htmlscript="true" highlight="12"]<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>PHP Code Example</title>
</head>
<body>
	<h1>' . __( 'PHP Code Example', 'syntaxhighlighter' ) . '</h1>

	<p><?php echo \'' . __( 'Hello World!', 'syntaxhighlighter' ) . '\'; ?></p>

	<p>' . __( 'This line is highlighted.', 'syntaxhighlighter' ) . '</p>

	<div class="foobar">
' . __( '		This	is	an
		example	of	smart
		tabs.', 'syntaxhighlighter' ) . '
	</div>

	<p><a href="http://wordpress.org/">WordPress</a></p>
</body>
</html>[/sourcecode]' );

		$this->codeformat = 1;
		echo $this->parse_shortcodes( $democode );
		$this->codeformat = false;

?>

	<h3 style="margin-top:30px"><?php _e( 'Shortcode Parameters', 'syntaxhighlighter' ); ?></h3>

	<p><?php printf( __( 'These are the parameters you can pass to the shortcode and what they do. For the booleans (i.e. on/off), pass %1$s/%2$s or %3$s/%4$s.', 'syntaxhighlighter' ), '<code>true</code>', '<code>1</code>', '<code>false</code>', '<code>0</code>' ); ?></p>

	<ul class="ul-disc">
		<li><?php printf( __( '%1$s or %2$s &#8212; The language syntax to highlight with. You can alternately just use that as the tag, such as <code>[php]code[/php]</code>. <a href="%3$s">Click here</a> for a list of valid tags (under &quot;aliases&quot;).', 'syntaxhighlighter' ), '<code>lang</code>', '<code>language</code>', 'http://alexgorbatchev.com/wiki/SyntaxHighlighter:Brushes' ); ?></li>
		<li><?php printf( __( '%s &#8212; Toggle automatic URL linking.', 'syntaxhighlighter' ), '<code>autolinks</code>' ); ?></li>
		<li><?php printf( __( '%s &#8212; Add an additional CSS class to the code box.', 'syntaxhighlighter' ), '<code>classname</code>' ); ?></li>
		<li><?php printf( __( '%s &#8212; Toggle collapsing the code box by default, requiring a click to expand it. Good for large code posts.', 'syntaxhighlighter' ), '<code>collapse</code>' ); ?></li>
		<li><?php printf( __( '%s &#8212; An interger specifying what number the first line should be (for the line numbering).', 'syntaxhighlighter' ), '<code>firstline</code>' ); ?></li>
		<li><?php printf( __( '%s &#8212; Toggle the left-side line numbering.', 'syntaxhighlighter' ), '<code>gutter</code>' ); ?></li>
		<li><?php printf( __( '%1$s &#8212; A comma-sperated list of line numbers to highlight. You can also specify a range. Example: %2$s', 'syntaxhighlighter' ), '<code>highlight</code>', '<code>2,5-10,12</code>' ); ?></li>
		<li><?php printf( __( "%s &#8212; Toggle highlighting any extra HTML/XML. Good for when you're mixing HTML/XML with another language, such as having PHP inside an HTML web page. The above preview has it enabled for example. This only works with certain languages.", 'syntaxhighlighter' ), '<code>htmlscript</code>' ); ?></li>
		<li><?php printf( __( '%s &#8212; Toggle light mode which disables the gutter and toolbar all at once.', 'syntaxhighlighter' ), '<code>light</code>' ); ?></li>
		<li><?php printf( __( '%s &#8212; Controls line number padding. Valid values are <code>false</code> (no padding), <code>true</code> (automatic padding), or an integer (forced padding).', 'syntaxhighlighter' ), '<code>padlinenumbers</code>' ); ?></li>
		<li><?php printf( __( '%1$s &#8212; Sets some text to show up before the code. Very useful when combined with the %2$s parameter.', 'syntaxhighlighter' ), '<code>title</code>', '<code>collapse</code>' ); ?></li>
		<li><?php printf( __( '%s &#8212; Toggle the toolbar containing the helpful buttons.', 'syntaxhighlighter' ), '<code>toolbar</code>' ); ?></li>
		<li><?php printf( __( '%s &#8212; Toggle line wrapping.', 'syntaxhighlighter' ), '<code>wraplines</code>' ); ?></li>
	</ul>

	<p><?php _e( 'Some example shortcodes:', 'syntaxhighlighter' ); ?></p>

	<ul class="ul-disc">
		<li><code>[php]<?php _e( 'your code here', 'syntaxhighlighter' ); ?>[/php]</code></li>
		<li><code>[css autolinks=&quot;false&quot; classname=&quot;myclass&quot; collapse=&quot;false&quot; firstline=&quot;1&quot; gutter=&quot;true&quot; highlight=&quot;1-3,6,9&quot; htmlscript=&quot;false&quot; light=&quot;false&quot; padlinenumbers=&quot;false&quot; smarttabs=&quot;true&quot; tabsize=&quot;4&quot; toolbar=&quot;true&quot;]<?php _e( 'your code here', 'syntaxhighlighter' ); ?>[/css]</code></li>
		<li><code>[code lang=&quot;js&quot;]<?php _e( 'your code here', 'syntaxhighlighter' ); ?>[/code]</code></li>
		<li><code>[sourcecode language=&quot;plain&quot;]<?php _e( 'your code here', 'syntaxhighlighter' ); ?>[/sourcecode]</code></li>
	</ul>

<?php $this->maybe_output_scripts(); ?>

</div>

<?php
	}


	// Validate the settings sent from the settings page
	function validate_settings( $settings ) {
		if ( !empty($_POST['syntaxhighlighter-defaults']) ) {
			$settings = $this->defaultsettings;
			$_REQUEST['_wp_http_referer'] = add_query_arg( 'defaults', 'true', $_REQUEST['_wp_http_referer'] );
		} else {
			$settings['theme']          = ( !empty($settings['theme']) && isset($this->themes[$settings['theme']]) ) ? strtolower($settings['theme']) : $this->defaultsettings['theme'];

			$settings['loadallbrushes'] = ( !empty($settings['loadallbrushes']) ) ? 1 : 0;
			$settings['autolinks']      = ( !empty($settings['autolinks']) )      ? 1 : 0;
			$settings['collapse']       = ( !empty($settings['collapse']) )       ? 1 : 0;
			$settings['gutter']         = ( !empty($settings['gutter']) )         ? 1 : 0;
			$settings['light']          = ( !empty($settings['light']) )          ? 1 : 0;
			$settings['smarttabs']      = ( !empty($settings['smarttabs']) )      ? 1 : 0;
			$settings['toolbar']        = ( !empty($settings['toolbar']) )        ? 1 : 0;
			$settings['wraplines']      = ( !empty($settings['wraplines']) )      ? 1 : 0;

			if ( 'true' != $settings['padlinenumbers'] && 'false' != $settings['padlinenumbers'] )
				$settings['padlinenumbers'] = (int) $settings['padlinenumbers'];

			$settings['classname']      = ( !empty($settings['classname']) )       ? preg_replace( '/[^ A-Za-z0-9_-]*/', '', $settings['classname'] ) : '';
			$settings['firstline']      = (int) ( !empty($settings['firstline']) ) ? $settings['firstline'] : $this->defaultsettings['firstline'];
			$settings['tabsize']        = (int) ( !empty($settings['tabsize']) )   ? $settings['tabsize']   : $this->defaultsettings['tabsize'];
		}

		return $settings;
	}


	// PHP4 compatibility
	function SyntaxHighlighter() {
		$this->__construct();
	}
}


// Start this plugin once all other plugins are fully loaded
add_action( 'init', 'SyntaxHighlighter', 5 );
function SyntaxHighlighter() {
	global $SyntaxHighlighter;
	$SyntaxHighlighter = new SyntaxHighlighter();
}

?>