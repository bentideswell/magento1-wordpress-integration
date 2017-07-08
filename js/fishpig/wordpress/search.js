/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

var fishpig = fishpig || {}

fishpig.WP = fishpig.WP || {};

fishpig.WP.Search = {
	Form: Class.create({
		initialize: function(formId){
			this.form = $(formId);
			this.field = this.form.select('input.input-text').first();
			this.validator  = new Validation(this.form);
			this.form.observe('submit', this.onFormSubmit.bindAsEventListener(this));
			
			this.field.observe('focus', this.onFieldFocus.bindAsEventListener(this));
			this.field.observe('blur', this.onFieldBlur.bindAsEventListener(this));
			
			this.onFieldBlur();
		},
		onFieldFocus: function(event) {
			if (this.field.readAttribute('title') === this.field.getValue()) {
				this.field.setValue('');
			}
		},
		onFieldBlur: function(event) {
			if (this.field.getValue() === '') {
				this.field.setValue(this.field.readAttribute('title'));
			}
		},
		onFormSubmit: function(event) {
			Event.stop(event);
			
			if (this.validator.validate()) {
				var newFormAction = this.form.readAttribute('action') 
					+ encodeURIComponent(this.field.getValue().replace(' ', '+')) + '/';
				
				this.field.writeAttribute('disabled', 'disabled');
					this.form.writeAttribute('method', 'get')
						.writeAttribute('action', newFormAction)
						.submit();
				
				return true;
			}
			
			return false;
		}
	})
};

/**
 * Legacy
 */
var wpSearchForm = fishpig.WP.Search.Form;
