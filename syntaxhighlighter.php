<?php /*

**************************************************************************

Plugin Name:  SyntaxHighlighter
Plugin URI:   http://www.viper007bond.com/wordpress-plugins/syntaxhighlighter/
Version:      2.0.0 Alpha
Description:  Easily post code to your blog while still maintaining complete control over it's display. Uses Alex Gorbatchev's <a href="http://code.google.com/p/syntaxhighlighter/">SyntaxHighlighter</a> and code by <a href="http://automattic.com/">Automattic</a>.
Author:       Viper007Bond
Author URI:   http://www.viper007bond.com/

**************************************************************************

Thanks to:

* Alex Gorbatchev for writing such an awesome Javascript-powered synatax
  highlighter script

* Automattic for writing the TinyMCE plugin and azaozz for helping me
  expand it's capabilities for this plugin.

**************************************************************************/

class SyntaxHighlighter {
	// All of these variables are private. Filters are provided for things that can be modified.
	var $agshver         = '2.0.296'; // Alex Gorbatchev's SyntaxHighlighter version
	var $settings        = array();   // Contains the user's settings
	var $defaultsettings = array();   // Contains the default settings
	var $brushes         = array();   // Array of aliases => brushes
	var $themes          = array();   // Array of themes
	var $usedbrushes     = array();   // Stores used brushes so we know what to output
	var $encoded         = false;     // Used to mark that a character encode took place

	// Initalize the plugin by registering the hooks
	function __construct() {
		// Check WordPress version
		if ( !function_exists( 'plugins_url' ) ) return;

		// Load localization domain
		load_plugin_textdomain( 'syntaxhighlighter', FALSE, '/syntaxhighlighter/localization' );

		// Register brush scripts
		wp_register_script( 'syntaxhighlighter-core',             plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shCore.js'),            array(),                         $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-bash',       plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushBash.js'),       array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-csharp',     plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushCSharp.js'),     array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-cpp',        plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushCpp.js'),        array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-css',        plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushCss.js'),        array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-delphi',     plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushDelphi.js'),     array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-diff',       plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushDiff.js'),       array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-groovy',     plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushGroovy.js'),     array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-jscript',    plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushJScript.js'),    array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-java',       plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushJava.js'),       array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-perl',       plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushPerl.js'),       array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-php',        plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushPhp.js'),        array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-plain',      plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushPlain.js'),      array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-python',     plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushPython.js'),     array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-ruby',       plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushRuby.js'),       array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-scala',      plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushScala.js'),      array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-sql',        plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushSql.js'),        array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-vb',         plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushVb.js'),         array('syntaxhighlighter-core'), $this->agshver );
		wp_register_script( 'syntaxhighlighter-brush-xml',        plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/shBrushXml.js'),        array('syntaxhighlighter-core'), $this->agshver );

		// Register theme stylesheets and enqueue the core stylesheet
		// Stylesheets need to be in the <head>, so they can't be loaded on demand
		wp_enqueue_style(   'syntaxhighlighter-core',             plugins_url('/syntaxhighlighter/syntaxhighlighter/styles/shCore.css'),            array(),                         $this->agshver );
		wp_register_style(  'syntaxhighlighter-theme-default',    plugins_url('/syntaxhighlighter/syntaxhighlighter/styles/shThemeDefault.css'),    array('syntaxhighlighter-core'), $this->agshver );
		wp_register_style(  'syntaxhighlighter-theme-django',     plugins_url('/syntaxhighlighter/syntaxhighlighter/styles/shThemeDjango.css'),     array('syntaxhighlighter-core'), $this->agshver );
		wp_register_style(  'syntaxhighlighter-theme-emacs',      plugins_url('/syntaxhighlighter/syntaxhighlighter/styles/shThemeEmacs.css'),      array('syntaxhighlighter-core'), $this->agshver );
		wp_register_style(  'syntaxhighlighter-theme-fadetogrey', plugins_url('/syntaxhighlighter/syntaxhighlighter/styles/shThemeFadeToGrey.css'), array('syntaxhighlighter-core'), $this->agshver );
		wp_register_style(  'syntaxhighlighter-theme-midnight',   plugins_url('/syntaxhighlighter/syntaxhighlighter/styles/shThemeMidnight.css'),   array('syntaxhighlighter-core'), $this->agshver );
		wp_register_style(  'syntaxhighlighter-theme-rdark',      plugins_url('/syntaxhighlighter/syntaxhighlighter/styles/shThemeRDark.css'),      array('syntaxhighlighter-core'), $this->agshver );

		// Register hooks
		add_action( 'admin_menu',                   array(&$this, 'register_settings_page') );
		add_action( 'admin_post_syntaxhighlighter', array(&$this, 'save_settings') );
		add_filter( 'the_content',                  array(&$this, 'parse_shortcodes'),          9 );
		add_action( 'wp_footer',                    array(&$this, 'maybe_output_scripts'),      15 );
		add_filter( 'mce_external_plugins',         array(&$this, 'add_tinymce_plugin') );
		add_filter( 'the_editor_content',           array(&$this, 'decode_shortcode_contents'), 1 );
		add_filter( 'content_save_pre',             array(&$this, 'encode_shortcode_contents'), 1 );
		add_filter( 'save_post',                    array(&$this, 'mark_as_encoded'),           10, 2 );

		// Create list of brush aliases and map them to their real brushes
		$this->brushes = array(
			'bash'       => 'bash',
			'shell'      => 'bash',
			'c-sharp'    => 'csharp',
			'csharp'     => 'csharp',
			'cpp'        => 'cpp',
			'c'          => 'cpp',
			'css'        => 'css',
			'delphi'     => 'delphi',
			'pas'        => 'delphi',
			'pascal'     => 'delphi',
			'diff'       => 'diff',
			'patch'      => 'diff',
			'groovy'     => 'groovy',
			'js'         => 'jscript',
			'jscript'    => 'jscript',
			'javascript' => 'jscript',
			'java'       => 'java',
			'perl'       => 'perl',
			'pl'         => 'perl',
			'php'        => 'php',
			'plain'      => 'plain',
			'text'       => 'plain',
			'py'         => 'python',
			'python'     => 'python',
			'rails'      => 'ruby',
			'ror'        => 'ruby',
			'ruby'       => 'ruby',
			'scala'      => 'scala',
			'sql'        => 'sql',
			'vb'         => 'vb',
			'vbnet'      => 'vb',
			'xml'        => 'xml',
			'xhtml'      => 'xml',
			'xslt'       => 'xml',
			'html'       => 'xml',
			'xhtml'      => 'xml',
		);

		// Create list of themes and their human readable names
		// Plugins can add to this list as long as they also register a style with the handle "syntaxhighlighter-theme-THEMENAMEHERE"
		$this->themes = apply_filters( 'syntaxhighlighter_themes', array(
			'default'    => __( 'Default',      'syntaxhighlighter' ),
			'django'     => __( 'Django',       'syntaxhighlighter' ),
			'emacs'      => __( 'Emacs',        'syntaxhighlighter' ),
			'fadetogrey' => __( 'Fade to Grey', 'syntaxhighlighter' ),
			'midnight'   => __( 'Midnight',     'syntaxhighlighter' ),
			'rdark'      => __( 'RDark',        'syntaxhighlighter' ),
		) );

		// Create array of default settings (you can use the filter to modify these)
		$this->defaultsettings = apply_filters( 'syntaxhighlighter_defaultsettings', array(
			'theme'      => 'default',
			'autolinks'  => 1,
			'classname'  => '',
			'collapse'   => 0,
			'firstline'  => 1,
			'fontsize'   => 100,
			'gutter'     => 1,
			'htmlscript' => 0,
			'light'      => 0,
			'ruler'      => 0,
			'smarttabs'  => 1,
			'tabsize'    => 4,
			'toolbar'    => 1,
		) );

		// Setup the settings by using the default as a base and then adding in any changed values
		// This allows settings arrays from old versions to be used even though they are missing values
		$usersettings = (array) get_option('syntaxhighlighter_options');
		$this->settings = $this->defaultsettings;
		if ( $usersettings !== $this->defaultsettings ) {
			foreach ( (array) $usersettings as $key1 => $value1 ) {
				if ( is_array($value1) ) {
					foreach ( $value1 as $key2 => $value2 ) {
						$this->settings[$key1][$key2] = $value2;
					}
				} else {
					$this->settings[$key1] = $value1;
				}
			}
		}





		// Temporary until user options are implemented
		wp_enqueue_style( 'syntaxhighlighter-theme-default' );
	}


	// Register the settings page
	function register_settings_page() {
		add_options_page( __('SyntaxHighlighter Settings', 'syntaxhighlighter'), __('SyntaxHighlighter', 'syntaxhighlighter'), 'manage_options', 'syntaxhighlighter', array(&$this, 'settings_page') );
	}


	// Add the custom TinyMCE plugin which wraps plugin shortcodes in <pre> in TinyMCE
	function add_tinymce_plugin( $plugins ) {
		$plugins['syntaxhighlighter'] = plugins_url('/syntaxhighlighter/syntaxhighlighter_mce.js');
		return $plugins;
	}


	// A filter function that runs do_shortcode() but only with this plugin's shortcodes
	function shortcode_hack( $content, $callback ) {
		global $shortcode_tags;

		// Backup current registered shortcodes and clear them all out
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();

		// Register all of this plugin's shortcodes
		add_shortcode( 'sourcecode', $callback );
		add_shortcode( 'source', $callback );
		add_shortcode( 'code', $callback );
		foreach ( $this->brushes as $shortcode => $brush )
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


	// HTML entity encode the contents of shortcodes. Note this handles $_POST-sourced data, so it has to deal with slashes
	function encode_shortcode_contents( $content ) {
		$this->encoded = true;
		return addslashes( $this->shortcode_hack( stripslashes( $content ), array(&$this, 'encode_shortcode_contents_callback') ) );
	}


	// HTML entity decode the contents of shortcodes
	function decode_shortcode_contents( $content ) {
		// If TinyMCE is enabled and set to be displayed first, leave it encoded
		if ( user_can_richedit() && 'html' != wp_default_editor() )
			return $content;

		return $this->shortcode_hack( $content, array(&$this, 'decode_shortcode_contents_callback') );
	}


	// The callback function for SyntaxHighlighter::encode_shortcode_contents()
	function encode_shortcode_contents_callback( $atts, $code = '', $tag = false ) {
		return '[' . $tag . $this->atts2string( $atts ) . ']' . htmlspecialchars( $code ) . "[/$tag]";
	}


	// The callback function for SyntaxHighlighter::decode_shortcode_contents()
	// Shortcode attribute values need to not be quoted with TinyMCE disabled for some reason (weird bug)
	function decode_shortcode_contents_callback( $atts, $code = '', $tag = false ) {
		$quotes = ( user_can_richedit() ) ? true : false;
		return '[' . $tag . $this->atts2string( $atts, $quotes ) . ']' . htmlspecialchars_decode( $code ) . "[/$tag]";
	}


	// Adds a post meta saying that HTML entities are encoded (for backwards compatibility)
	function mark_as_encoded( $post_ID, $post ) {
		if ( false == $this->encoded || 'revision' == $post->post_type )
			return;

		add_post_meta( $post_ID, 'syntaxhighlighter_encoded', true, true );
	}


	// Transforms an attributes array into a 'key="value"' format (i.e. reverses the process)
	function atts2string( $atts, $quotes = true ) {
		if ( empty($atts) )
			return '';

		$atts = $this->attributefix( $atts );

		$strings = array();
		foreach ( $atts as $key => $value )
			$strings[] = ( $quotes ) ? $key . '="' . attribute_escape( $value ) . '"' : $key . '=' . attribute_escape( $value );

		return ' ' . implode( ' ', $strings );
	}


	// Simple function for escaping just single quotes (the original js_escape() escapes more than we need)
	function js_escape_singlequotes( $string ) {
		return str_replace( "'", "\'", $string );
	}


	// Output any needed scripts. This is meant for the footer.
	function maybe_output_scripts() {
		if ( empty($this->usedbrushes) )
			return;

		$scripts = array();
		foreach ( $this->usedbrushes as $brush => $unused )
			$scripts[] = 'syntaxhighlighter-brush-' . strtolower( $brush );

		wp_print_scripts( $scripts );

		?>
<script type="text/javascript">
	SyntaxHighlighter.config.clipboardSwf = '<?php echo js_escape( plugins_url('/syntaxhighlighter/syntaxhighlighter/scripts/clipboard.swf') ); ?>';
	SyntaxHighlighter.config.strings.expandSource = '<?php echo $this->js_escape_singlequotes( __( 'expand source', 'syntaxhighlighter' ) ); ?>';
	SyntaxHighlighter.config.strings.viewSource = '<?php echo $this->js_escape_singlequotes( __( 'view source', 'syntaxhighlighter' ) ); ?>';
	SyntaxHighlighter.config.strings.copyToClipboard = '<?php echo $this->js_escape_singlequotes( __( 'copy to clipboard', 'syntaxhighlighter' ) ); ?>';
	SyntaxHighlighter.config.strings.copyToClipboardConfirmation = '<?php echo $this->js_escape_singlequotes( __( 'The code is in your clipboard now', 'syntaxhighlighter' ) ); ?>';
	SyntaxHighlighter.config.strings.print = '<?php echo $this->js_escape_singlequotes( __( 'print', 'syntaxhighlighter' ) ); ?>';
	SyntaxHighlighter.config.strings.help = '<?php echo $this->js_escape_singlequotes( __( '?', 'syntaxhighlighter' ) ); ?>';
	SyntaxHighlighter.config.strings.alert = '<?php echo $this->js_escape_singlequotes( __( 'SyntaxHighlighter\n\n', 'syntaxhighlighter' ) ); ?>';
	SyntaxHighlighter.config.strings.noBrush = '<?php echo $this->js_escape_singlequotes( __( "Can't find brush for: ", 'syntaxhighlighter' ) ); ?>';
	SyntaxHighlighter.config.strings.brushNotHtmlScript = '<?php echo $this->js_escape_singlequotes( __( "Brush wasn't configured for html-script option: ", 'syntaxhighlighter' ) ); ?>';
	SyntaxHighlighter.all();
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

		// Error fixing for [tag="language"]
		if ( isset($atts[0]) ) {
			$atts = $this->attributefix( $atts );
			$atts['language'] = $atts[0];
			unset($atts[0]);
		}

		// Default out all of the available parameters to "false" (easy way to check if they're set or not)
		// Note this isn't the same as if the user passes the string "false" to the shortcode
		$atts = shortcode_atts(array(
			'language'    => false,
			'lang'        => false,
			'auto-links'  => false,
			'class-name'  => false,
			'collapse'    => false,
			'first-line'  => false,
			'gutter'      => false,
			'highlight'   => false,
			'html-script' => false,
			'light'       => false,
			'ruler'       => false,
			'toolbar'     => false,
		), $atts);


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

			// Validate passed attribute
			if ( !isset($this->brushes[$lang]) )
				return $code;
		}

		// Ensure lowercase
		$lang = strtolower( $lang );

		// Register this brush as used so it's script will be outputted
		$this->usedbrushes[$this->brushes[$lang]] = true;

		$params = array();
		$params[] = "brush: $lang;";

		foreach ( $atts as $key => $value ) {
			if ( false === $value || in_array( $key, array( 'language', 'lang' ) ) )
				continue;

			if ( $key == 'html-script' && ( 'true' == $value || '1' == $value ) )
				$this->usedbrushes['xml'] = true;

			if ( 'highlight' == $key )
				$params[] = "$key: [$value];";
			else
				$params[] = "$key: $value;";
		}

		$content  = '<pre class="' . attribute_escape( implode( ' ', $params ) ) . '">';
		$content .= ( get_post_meta( $post->ID, 'syntaxhighlighter_encoded', true ) ) ? $code : htmlspecialchars( $code );
		$content .= '</pre>';

		return $content;
	}


	// Settings page
	function settings_page() { ?>

<div class="wrap">
<?php if ( function_exists('screen_icon') ) screen_icon(); ?>
	<h2><?php _e( 'SyntaxHighlighter Settings', 'syntaxhighlighter' ); ?></h2>

	<form method="post" action="admin-post.php">

	<?php wp_nonce_field('syntaxhighlighter'); ?>

	<input type="hidden" name="action" value="syntaxhighlighter" />

	<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="syntaxhighlighter_theme"><?php _e('Color Theme', 'syntaxhighlighter'); ?></label></th>
			<td>
				<select name="syntaxhighlighter_theme" id="syntaxhighlighter_theme" class="postform">
<?php
					foreach ( $this->themes as $theme => $name ) {
						echo '					<option value="' . attribute_escape($theme) . '"';
						selected( $this->settings['theme'], $theme );
						echo '>' . htmlspecialchars($name) . "</option>\n";
					}
?>
				</select>
			</td>
		</tr>
	</table>

	<p><?php _e('All of the settings below can also be configured on a per-code box basis.', 'syntaxhighlighter'); ?></p>

	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php _e('Miscellaneous', 'syntaxhighlighter'); ?></th>
			<td>
				<fieldset>
					<legend class="hidden"><?php _e('Miscellaneous', 'syntaxhighlighter'); ?></legend>

					<label for="syntaxhighlighter_gutter"><input name="syntaxhighlighter_gutter" type="checkbox" id="syntaxhighlighter_gutter" value="1" <?php checked( $this->settings['gutter'], 1 ); ?> /> <?php _e('Display line numbers', 'syntaxhighlighter'); ?></label><br />
					<label for="syntaxhighlighter_toolbar"><input name="syntaxhighlighter_toolbar" type="checkbox" id="syntaxhighlighter_toolbar" value="1" <?php checked( $this->settings['toolbar'], 1 ); ?> /> <?php _e('Display the toolbar', 'syntaxhighlighter'); ?></label><br />
					<label for="syntaxhighlighter_autolinks"><input name="syntaxhighlighter_autolinks" type="checkbox" id="syntaxhighlighter_autolinks" value="1" <?php checked( $this->settings['autolinks'], 1 ); ?> /> <?php _e('Automatically make URLs clickable', 'syntaxhighlighter'); ?></label><br />
					<label for="syntaxhighlighter_collapse"><input name="syntaxhighlighter_collapse" type="checkbox" id="syntaxhighlighter_collapse" value="1" <?php checked( $this->settings['collapse'], 1 ); ?> /> <?php _e('Collapse code boxes', 'syntaxhighlighter'); ?></label><br />
					<label for="syntaxhighlighter_ruler"><input name="syntaxhighlighter_ruler" type="checkbox" id="syntaxhighlighter_ruler" value="1" <?php checked( $this->settings['ruler'], 1 ); ?> /> <?php _e('Show a ruler column along the top of the code box', 'syntaxhighlighter'); ?></label><br />
					<label for="syntaxhighlighter_light"><input name="syntaxhighlighter_light" type="checkbox" id="syntaxhighlighter_light" value="1" <?php checked( $this->settings['light'], 1 ); ?> /> <?php _e('Use the light display mode, best for single lines of code', 'syntaxhighlighter'); ?></label><br />
					<label for="syntaxhighlighter_smarttabs"><input name="syntaxhighlighter_smarttabs" type="checkbox" id="syntaxhighlighter_smarttabs" value="1" <?php checked( $this->settings['smarttabs'], 1 ); ?> /> <?php _e('Use smart tabs allowing tabs being used for alignment', 'syntaxhighlighter'); ?></label><br />
					<label for="syntaxhighlighter_htmlscript"><input name="syntaxhighlighter_htmlscript" type="checkbox" id="syntaxhighlighter_htmlscript" value="1" <?php checked( $this->settings['htmlscript'], 1 ); ?> /> <?php _e('If mixing HTML/XML code with other code, highlight both languages', 'syntaxhighlighter'); ?></label><br />
				</fieldset>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="syntaxhighlighter_classname"><?php _e('Additional CSS Class(es)', 'syntaxhighlighter'); ?></label></th>
			<td><input name="syntaxhighlighter_classname" type="text" id="syntaxhighlighter_classname" value="<?php echo attribute_escape( $this->settings['classname'] ); ?>" class="regular-text" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="syntaxhighlighter_firstline"><?php _e('Starting Line Number', 'syntaxhighlighter'); ?></label></th>
			<td><input name="syntaxhighlighter_firstline" type="text" id="syntaxhighlighter_firstline" value="<?php echo attribute_escape( $this->settings['firstline'] ); ?>" class="small-text" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="syntaxhighlighter_fontsize"><?php _e('Font Size (Percentage)', 'syntaxhighlighter'); ?></label></th>
			<td><input name="syntaxhighlighter_fontsize" type="text" id="syntaxhighlighter_fontsize" value="<?php echo attribute_escape( $this->settings['fontsize'] ); ?>" class="small-text" />%</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="syntaxhighlighter_tabsize"><?php _e('Tab Size', 'syntaxhighlighter'); ?></label></th>
			<td><input name="syntaxhighlighter_tabsize" type="text" id="syntaxhighlighter_tabsize" value="<?php echo attribute_escape( $this->settings['tabsize'] ); ?>" class="small-text" /></td>
		</tr>
	</table>

	<p class="submit">
		<input type="submit" name="Submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>

	</form>

</div>

<?php
	}


	// Handle the results of the settings page
	function save_settings() {
		echo 'Not coded yet!';
	}


	// PHP4 compatibility
	function SyntaxHighlighter() {
		$this->__construct();
	}
}

// Start this plugin once all other plugins are fully loaded
add_action( 'init', 'SyntaxHighlighter' ); function SyntaxHighlighter() { global $SyntaxHighlighter; $SyntaxHighlighter = new SyntaxHighlighter(); }

?>