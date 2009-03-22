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
	var $agshver     = '2.0.296'; // Alex Gorbatchev's SyntaxHighlighter version
	var $brushes     = array();   // Array of aliases => brushes
	var $themes      = array();   // Array of themes
	var $usedbrushes = array();   // Stores used brushes so we know what to output

	// Initalize the plugin by registering the hooks
	function __construct() {
		// Check WordPress version
		if ( !function_exists( 'plugins_url' ) ) return;

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
		add_filter( 'the_content',          array(&$this, 'parse_shortcodes'),          9 );
		add_action( 'wp_footer',            array(&$this, 'maybe_output_scripts'),      15 );
		add_filter( 'content_save_pre',     array(&$this, 'encode_shortcode_contents'), 1 );
		add_filter( 'the_editor_content',   array(&$this, 'decode_shortcode_contents'), 1 );
		add_filter( 'mce_external_plugins', array(&$this, 'add_tinymce_plugin') );

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





		// Temporary until user options are implemented
		wp_enqueue_style( 'syntaxhighlighter-theme-default' );
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
		return addslashes( $this->shortcode_hack( stripslashes( $content ), array(&$this, 'encode_shortcode_contents_callback') ) );
	}


	// HTML entity decode the contents of shortcodes, but only if TinyMCE is to be displayed first
	function decode_shortcode_contents( $content ) {
		if ( user_can_richedit() && 'html' != wp_default_editor() )
			return $content;

		return $this->shortcode_hack( $content, array(&$this, 'decode_shortcode_contents_callback') );
	}


	// The callback function for SyntaxHighlighter::encode_shortcode_contents()
	function encode_shortcode_contents_callback( $atts, $code = '', $tag = false ) {
		return '[' . $tag . $this->atts2string( $atts ) . ']' . htmlspecialchars( $code ) . "[/$tag]";
	}


	// The callback function for SyntaxHighlighter::decode_shortcode_contents()
	// Shortcode attribute values need to not be quoted for some reason (weird bug)
	function decode_shortcode_contents_callback( $atts, $code = '', $tag = false ) {
		$quotes = ( user_can_richedit() ) ? true : false;
		return '[' . $tag . $this->atts2string( $atts, $quotes ) . ']' . htmlspecialchars_decode( $code ) . "[/$tag]";
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

		$this->usedbrushes['xml'] = true;

		// fully escape all user parameters with attribute_escape() or whatever

		$content = '<pre class="brush: ' . $lang . ';">' . $code . '</pre>';


		return $content;
	}


	// PHP4 compatibility
	function SyntaxHighlighter() {
		$this->__construct();
	}
}

// Start this plugin once all other plugins are fully loaded
add_action( 'plugins_loaded', 'SyntaxHighlighter' ); function SyntaxHighlighter() { global $SyntaxHighlighter; $SyntaxHighlighter = new SyntaxHighlighter(); }

?>