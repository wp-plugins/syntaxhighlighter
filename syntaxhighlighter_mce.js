/*
 * Syntax Highlighter shortcode plugin
 * Based on v20090208 from WordPress.com
 */

var syntaxHLcodes = 'sourcecode|source|code|bash|shell|c-sharp|csharp|cpp|c|css|delphi|pas|pascal|diff|patch|groovy|js|jscript|javascript|java|perl|pl|php|plain|text|py|python|rails|ror|ruby|scala|sql|vb|vbnet|xml|xhtml|xslt|html';

(function() {
	tinymce.create('tinymce.plugins.SyntaxHighlighterPlugin', {
		init : function(ed, url) {
			var t = this;

			ed.onBeforeSetContent.add(function(ed, o) {
				o.content = t._htmlToVisual(o.content);
			});
			
			ed.onPostProcess.add(function(ed, o) {
				if ( o.save ) {
					o.content = t._visualToHtml(o.content);
				}
			});
		},

		getInfo : function() {
			return {
				longname : 'SyntaxHighlighter Assister',
				author : 'Automattic',
				authorurl : 'http://wordpress.com/',
				infourl : 'http://wordpress.org/extend/plugins/syntaxhighlighter/',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		},

		// Private methods
		_visualToHtml : function(content) {
			content = tinymce.trim(content);	

			// 2 <br> get converted to \n\n and are needed to preserve the next <p>
			content = content.replace(new RegExp('(<pre>\\s*)?(\\[(' + syntaxHLcodes + ').*?\\][\\s\\S]*?\\[\\/\\3\\])(\\s*<\\/pre>)?', 'gi'), '$2<br /><br />');
			content = content.replace(/<\/pre>(<br \/><br \/>)?<pre>/gi, '\n');

			return content;
		},

		_htmlToVisual : function(content) {
			content = tinymce.trim(content);

			content = content.replace(new RegExp('(<p>\\s*)?(<pre>\\s*)?(\\[(' + syntaxHLcodes + ').*?\\][\\s\\S]*?\\[\\/\\4\\])(\\s*<\\/pre>)?(\\s*<\\/p>)?', 'gi'), '<pre>$3</pre>');
			content = content.replace(/<\/pre><pre>/gi,	'\n');

			// Remove anonymous, empty paragraphs.
			content = content.replace(/<p>(\s|&nbsp;)*<\/p>/mg, '');

			// Look for <p> <br> in [php], replace with <br />
			content = content.replace(/\[php[^\]]*\][\s\S]+?\[\/php\]/g, function(a) {
				return a.replace(/<br ?\/?>[\r\n]*/g, '<br />').replace(/<\/?p( [^>]*)?>[\r\n]*/g, '<br />');
			});

			return content;
		}
	});

	// Register plugin
	tinymce.PluginManager.add('syntaxhighlighter', tinymce.plugins.SyntaxHighlighterPlugin);
})();

function pre_wpautop2(content) {
	content = content.replace(/\[php[^\]]*\][\s\S]+?\[\/php\]/g, function(a) {
		return a.replace(/<br ?\/?>[\r\n]*/g, '<wp_temp>').replace(/<\/?p( [^>]*)?>[\r\n]*/g, '<wp_temp>');
	});

	content = content.replace(/<pre>\s*\[php/gi, '[php');
	content = content.replace(/\[\/php\]\s*<\/pre>/gi, '[/php]');

	content = this._pre_wpautop(content);

	content = content.replace(/\[php[^\]]*\][\s\S]+?\[\/php\]/g, function(a) {
		return a.replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>');
	});

	return content.replace(/<wp_temp>/g, '\n');
}

function wpautop2(content) {

	// js htmlspecialchars
	content = content.replace(/\[php[^\]]*\][\s\S]+?\[\/php\]/g, function(a) {
		return a.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	});

	return this._wpautop(content);
}

switchEditors._pre_wpautop = switchEditors.pre_wpautop;
switchEditors._wpautop = switchEditors.wpautop;
switchEditors.pre_wpautop = pre_wpautop2;
switchEditors.wpautop = wpautop2;