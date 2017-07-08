/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

var fishpig = fishpig || {}

fishpig.WP = fishpig.WP || {};

fishpig.WP.Comments = {};

fishpig.WP.Comments.Form = Class.create({
	initialize: function(formId, permalink){
		this.form = $(formId);
		this.validator  = new Validation(this.form);
		this.loading = $(formId + '-please-wait');
		this.form.observe('submit', this.submit.bindAsEventListener(this));
		this.permalink = permalink;
		
		if (this.permalink) {
			$$('a.comment-reply-link').each(function(elem, ind) {
				var m =elem.up('li').id.match(/comment-([0-9]{1,})/);
				
				if (m && typeof m[1] !== 'undefined') {
					elem.writeAttribute('comment_id', m[1]);
					
					elem.observe('click', function(event) {
						Event.stop(event);
						this.move(elem.readAttribute('comment_id'));
					}.bindAsEventListener(this));
				}
				else {
					elem.up('.reply').remove();
				}			
			}.bind(this));
		}
	},
	submit: function(event) {
		if (typeof Recaptcha != 'undefined') {
			$('recaptcha_response_field').addClassName('required-entry');
		}
		
		if(this.validator && this.validator.validate()){
			if (this.loading) {
				this.loading.setStyle({'display': 'block'});
			}
			
			this.ifm = new Element('iframe', {
				'id': this.form.id + '-iframe',
				 'name': this.form.id + '-iframe',
				 'style': 'display:none;'
			});
			
			this.form.writeAttribute('target', this.ifm.name)
				.insert({'bottom': this.ifm});
				
			this.ifm.observe('load', this.iframeOnLoad.bindAsEventListener(this));
			
			return true;
		}
		
		if (this.loading) {
			this.loading.setStyle({'display': 'none'});
		}

		Event.stop(event);				
		
		return false
	},
	iframeOnLoad: function(event) {
		var url = this.ifm.contentWindow.location.href;

		if (url.indexOf('#') > 0) {
			var commentId = url.substring(url.indexOf('#comment-')+9);

			if (this.permalink) {
				window.location.href = this.permalink + '?comment=' + commentId;
			}
			else {
				window.location.href = this.ifm.baseURI;
			}
		}
	},
	move: function(commentId) {
		var comment = $('comment-' + commentId.toString());
		
		if (comment) {
			$('comment_parent').setValue(commentId);
			comment.insert(this.form);
		}
	}
});

// Legacy
var wpCommentForm = fishpig.WP.Comments.Form;
