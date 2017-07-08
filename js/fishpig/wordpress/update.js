/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */


var 	fishpig 		= fishpig 			|| {};
		fishpig.WP 	= fishpig.WP 	|| {};

fishpig.WP.Update = new Class.create({
	initialize: function() {
		if (!WP_VERSION_LATEST) {
			try {
				this.loadLatestVersion();
			}
			catch (e) {}
		}
		else {
			this.checkUpdate();
		}
	},
	loadLatestVersion: function() {
		this.ifm = new Element('iframe', {
			'id': 'wp-int-upgrade-frame',
			'style': 'display: none;',
			'src': WP_VERSION_LOOKUP_URL
		});

		$(document.body).insert(this.ifm);

		this.ifm.observe('load', function(event) {
			var versions = (this.ifm.contentDocument || this.ifm.contentWindow.document).getElementsByTagName('body')[0].getElementsByTagName('pre')[0].innerHTML;

			var json = versions.evalJSON(true);
			
			if (json.latest_version) {
				WP_VERSION_LATEST = json.latest_version;
			}
			
			this.checkUpdate();
		}.bindAsEventListener(this));
	},
	checkUpdate: function() {
		if (WP_VERSION_LATEST && WP_VERSION_CURRENT) {
			if (this.versionCompare(WP_VERSION_LATEST, WP_VERSION_CURRENT) === 1) {
				this.highlightNewVersion(WP_VERSION_LATEST);
			}
		}
	},
	highlightNewVersion: function(newVersion) {
		$('nav').select('a').each(function(elem) {
			if (elem.readAttribute('href').indexOf('/system_config/edit/section/wordpress') > 0) {
				elem.down('span').innerHTML += ' (1)';
			}
		});
		
		var version = $('wp-version');
		
		if (version) {
			version.update(newVersion);
			version.up('.wp-update-msg').show();
		}
	},
	versionCompare: function(left, right) {
		if (typeof left + typeof right != 'stringstring') {
			return false;
		}
		
		var a = left.split('.'), b = right.split('.'), i = 0, len = Math.max(a.length, b.length);

		for (; i < len; i++) {
			if ((a[i] && !b[i] && parseInt(a[i]) > 0) || (parseInt(a[i]) > parseInt(b[i]))) {
				return 1;
			} else if ((b[i] && !a[i] && parseInt(b[i]) > 0) || (parseInt(a[i]) < parseInt(b[i]))) {
				return -1;
			}
		}

		return 0;
	}
});
