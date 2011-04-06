// Documentation : http://wiki.moxiecode.com/index.php/TinyMCE:Create_plugin/3.x#Creating_your_own_plugins

(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('BWPSyntax');
	
	tinymce.create('tinymce.plugins.BWPSyntax', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');

			ed.addCommand('mceBWPSyntax', function() {
				ed.windowManager.open({
					file : url + '/bwp-syntax-window.php',
					width : 420 + ed.getLang('BWPSyntax.delta_width', 0),
					height : 300 + ed.getLang('BWPSyntax.delta_height', 0),
					inline : 1
				}, {
					plugin_url : url // Plugin absolute URL
				});
			});

			// Register a button
			ed.addButton('BWPSyntax', {
				title : 'BWPSyntax.desc',
				cmd : 'mceBWPSyntax',
				image : url + '/button.png'
			});

			// Add a node change handler, selects the button in the UI when a pre is selected
			/*ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('BWPSyntax', n.nodeName == 'PRE');
			});*/
		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
					longname  : 'BWP Syntax',
					author 	  : 'Khang Minh',
					authorurl : 'http://betterwp.net',
					infourl   : 'http://betterwp.net/wordpress-plugins/bwp-syntax/',
					version   : "1.0.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('BWPSyntax', tinymce.plugins.BWPSyntax);
})();