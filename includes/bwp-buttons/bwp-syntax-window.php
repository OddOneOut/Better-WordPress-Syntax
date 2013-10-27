<?php
$test_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
if (file_exists($test_path . '/wp-load.php'))
	require_once($test_path . '/wp-load.php');
else if (file_exists(dirname($test_path) . '/wp-load.php'))
	require_once(dirname($test_path) . '/wp-load.php');
else
{
	echo 'Could not initialize WordPress environment (wp-config.php is missing).';
	exit;
}
// check for rights
if (!is_user_logged_in() || !current_user_can('edit_posts'))
	wp_die(__("Sorry, but you do not have the permission to view this file."));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>BWP Syntax</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') . '/' . WPINC; ?>/js/tinymce/tiny_mce_popup.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') . '/' . WPINC; ?>/js/tinymce/utils/form_utils.js"></script>
	<script language="javascript" type="text/javascript">

function htmlspecialchars(string, quote_style, charset, double_encode) {
    // http://kevin.vanzonneveld.net
    // +   original by: Mirek Slugen
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Nathan
    // +   bugfixed by: Arno
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Ratheous
    // +      input by: Mailfaker (http://www.weedem.fr/)
    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
    // +      input by: felix
    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // %        note 1: charset argument not supported
    // *     example 1: htmlspecialchars("<a href='test'>Test</a>", 'ENT_QUOTES');
    // *     returns 1: '&lt;a href=&#039;test&#039;&gt;Test&lt;/a&gt;'
    // *     example 2: htmlspecialchars("ab\"c'd", ['ENT_NOQUOTES', 'ENT_QUOTES']);
    // *     returns 2: 'ab"c&#039;d'
    // *     example 3: htmlspecialchars("my "&entity;" is still here", null, null, false);
    // *     returns 3: 'my &quot;&entity;&quot; is still here'

    var optTemp = 0, i = 0, noquotes= false;
    if (typeof quote_style === 'undefined' || quote_style === null) {
        quote_style = 2;
    }
    string = string.toString();
    if (double_encode !== false) { // Put this first to avoid double-encoding
        string = string.replace(/&/g, '&amp;');
    }
    string = string.replace(/</g, '&lt;').replace(/>/g, '&gt;');

    var OPTS = {
        'ENT_NOQUOTES': 0,
        'ENT_HTML_QUOTE_SINGLE' : 1,
        'ENT_HTML_QUOTE_DOUBLE' : 2,
        'ENT_COMPAT': 2,
        'ENT_QUOTES': 3,
        'ENT_IGNORE' : 4
    };
    if (quote_style === 0) {
        noquotes = true;
    }
    if (typeof quote_style !== 'number') { // Allow for a single string or an array of string flags
        quote_style = [].concat(quote_style);
        for (i=0; i < quote_style.length; i++) {
            // Resolve string input to bitwise e.g. 'PATHINFO_EXTENSION' becomes 4
            if (OPTS[quote_style[i]] === 0) {
                noquotes = true;
            }
            else if (OPTS[quote_style[i]]) {
                optTemp = optTemp | OPTS[quote_style[i]];
            }
        }
        quote_style = optTemp;
    }
    if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE) {
        string = string.replace(/'/g, '&#039;');
    }
    if (!noquotes) {
        string = string.replace(/"/g, '&quot;');
    }

    return string;
}

	function init() {
		tinyMCEPopup.resizeToInnerSize();
	}
	
	function insertBWPSyntax() {
		
		var tagtext;
		
		var selected = tinyMCE.activeEditor.selection.getContent();

		var code = htmlspecialchars(jQuery('#code').val(), 0);
		var lang = jQuery('#lang').val();
		var type = jQuery('#type').val();
		var shortcode_tag = jQuery('#shortcode_tag').val();
		var inlinetype = jQuery('#inlinetype').val();
		if (code == '') code = 'bwp_empty';
		if (lang == 'noparse')
			lang = ' parse="no"';
		else
			lang = ' lang="' + lang + '"';
		if (inlinetype == 'shortcode')
			return_code = '[' + shortcode_tag + lang + ' inline="yes"]' + code + '[/'  + shortcode_tag + ']';
		else if (inlinetype == 'tag')
			return_code = '<code' + lang + '>' + code + '</code>';
		else if (inlinetype == '')
		{
			if (type == 'shortcode')
				return_code = '[' + shortcode_tag + lang + ']<pre>' + code + '</pre>[/' + shortcode_tag + ']';
			else
				return_code = '<pre' + lang + '>' + code + '\n</pre>';
		}			

        if (window.tinyMCE) 
		{
			if (code == 'bwp_empty')
			{
				return_code = return_code.replace(code, selected);
			}
			window.tinyMCE.execInstanceCommand('content', 'mceReplaceContent', false, return_code);
			//Peforms a clean up of the current editor HTML. 
			//tinyMCEPopup.editor.execCommand('mceCleanup');
			//Repaints the editor. Sometimes the browser has graphic glitches. 
			tinyMCEPopup.editor.execCommand('mceRepaint');
			tinyMCEPopup.close();
		}
		
		return;
	}
	</script>
	<base target="_self" />
</head>
<body>
	<form action="#">
	
	<div class="panel_wrapper" style="border-top: 1px solid #919B9C; padding: 5px 10px;">
		<table border="0" cellpadding="4" cellspacing="0" width="100%">
          <tr>
            <td nowrap="nowrap"><?php _e('Code', 'bwp-syntax'); ?></td>
            <td>
				<textarea id="code" name="code" cols="50" rows="12"></textarea>
			</td>
          </tr>
         <tr>
            <td><?php _e('Language', 'bwp-syntax'); ?></td>
            <td>
<?php
	require_once(dirname(dirname(__FILE__)) . '/class-bwp-syntax.php');
	// Use this filter to add more languages
	$args = apply_filters('bwp_syntax_languages', array());
	$bwp_syntax = new BWP_SYNTAX($args);
	$bwp_syntax->init();
	$langs = $bwp_syntax->get_lang();
?>
				<input type="hidden" id="shortcode_tag" name="shortcode_tag" value="<?php echo $bwp_syntax->shortcode_tag; ?>" />
				<select id="lang">
<?php
	foreach ($langs as $alias => $lang)
	{
?>
					<option value="<?php echo $alias; ?>"><?php echo ucfirst($lang); ?></option>
<?php
	}
?>
				</select>
			</td>
          </tr>
		  <tr>
		  	<td><?php _e('Type', 'bwp-syntax'); ?></td>
			<td>				
				<select id="type">
					<option value="pre">Html &lt;pre&gt; tag</option>
					<option value="shortcode">Shortcode tag</option>					
				</select>
			</td>
		  </tr>
		  <tr>
		  	<td><?php _e('Inline', 'bwp-syntax'); ?></td>
			<td>				
				<select id="inlinetype">
					<option value="">&mdash; <?php _e('Select an inline mode', 'bwp-syntax'); ?> &mdash;</option>
					<option value="tag">Html &lt;code&gt; tag</option>
					<option value="shortcode">Shortcode</option>					
				</select>
			</td>
		  </tr>
        </table>
    </div>

	<div class="mceActionPanel clearfix">
			<input type="button" id="cancel" name="cancel" value="Cancel" onclick="tinyMCEPopup.close();" />
			<input type="submit" id="insert" name="insert" value="Insert" onclick="insertBWPSyntax();" />
	</div>

	</form>
</body>
</html>
