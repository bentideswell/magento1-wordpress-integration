/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

var fishpig = fishpig || {}

fishpig.WP = fishpig.WP || {};

fishpig.WP.Lightbox = Class.create({
	initialize: function(sel){
		this._images = new Array();
				
		$$(sel).each(function(i) {
			var anchor = i.up('a');
			
			if (anchor.readAttribute('href').match(/(jpg|png|gif)$/)) {
				this._images.push(i);
				
				i.observe('click', function(event) {
					Event.stop(event);
					
					this.launch(i);
				}.bind(this));
			}
		}.bind(this));
		
		this._setupGallery();
	},
	launch: function(i) {
		this.gallery.hide();
		this.gallery.wrapper.show();
		this.gallery.img.hide();
		this.gallery.img.writeAttribute('src', i.up('a').readAttribute('href'));
		
		Effect.Appear(this.gallery.shadow, {
			duration: 0.3,
			from: 0,
			to: 0.9,
			afterFinish: function() {
				Effect.Appear(this.gallery.inner, {
					duration: 0.5
				})
			}.bind(this)
		})	
	},
_setupGallery: function() {
		this.gallery = {
			wrapper: new Element('div', {id: 'fp-gallery', class: 'gallery'}),
			shadow: new Element('div', {class: 'shadow gallery-close'}),
			inner: new Element('div', {class: 'inner'}),
			img: new Element('img', {style: 'max-width: 100%;'}),
			close: new Element('span', {class: 'close gallery-close'}).update('Close'),
			build: function() {
				this.inner.insert(this.img);
				this.wrapper.hide().insert(this.shadow).insert(this.inner.insert(this.close));

				return this;
			},
			hide: function() {
				this.wrapper.hide();
				this.shadow.hide();
				this.inner.hide();
				
				return this;
			}
		};
			
		this.gallery.build();
		
		this.gallery.img.observe('load', function(event) {
			this.gallery.img.show();
			this.gallery.inner.show();
		}.bindAsEventListener(this));

		$$('body').first().insert(this.gallery.wrapper);

		this.gallery.wrapper.select('.gallery-close').invoke('observe', 'click', this.close.bindAsEventListener(this));
	},
	close: function(event) {
		Event.stop(event);
		window.location.hash = '';
		this.gallery.hide();
	}
});

	document.observe("dom:loaded", function() {
		new fishpig.WP.Lightbox('.post-list a img,.post-view a img');
	});
