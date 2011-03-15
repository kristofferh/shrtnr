var SHRTNR = (function(parent, $) {
	
	var self = parent || {};
	self.options = jQuery.extend({
		action: 'shrtnr', // used for WP ajax data
		editBtnText: 'Edit', // text of the edit button
		nonceField: '#shrtnr_nonce', // used to place error / info messages
		okayBtnText: 'Ok', // text of the okay button
		name: 'shrtnr', // base name of all plugin related elements
		postID: '#shrtnr-post-id', // id attribute of post
		previewField: '#shrntr-preview-', // id attribute of preview element (URL)
		settingsForm: '#shrtnr-settings', // id attribute of settings form
		urlField: '#shrtnr-url' // id of URL field (post / pages)
	}, shrtnr_data || {});

	self.init = function() {
		if($(self.options.urlField).length > 0) {
			this.editInPlace();
		}
		
		// settings module
		if(($(self.options.settingsForm).length > 0) && (typeof this.settings === 'object')) {
			this.settings.init();
		}

	};
	
	self.editInPlace = function() {
		var postID = $(self.options.postID).val();
		var url = $(self.options.urlField);
		var nonce = $(self.options.nonceField);
		var urlVal = url.val(); // value of the text-field
		// create new elements and hide text field
		var span = $('<span/>').addClass(self.options.name + '-editable').text(urlVal);
		var cancel = $('<a/>').attr('href', '#').text(self.options.cancelText).hide();
		url.before(span).hide();
		var editBtn = $('<button/>').addClass(self.options.name + '-edit' + ' button').text(self.options.editBtnText);
		var okayBtn = $('<button/>').addClass(self.options.name + '-okay' + ' button').text(self.options.okayBtnText).hide();
		url.after(editBtn);
		editBtn.after(okayBtn);
		okayBtn.after(cancel);
		
		var msg = $('<div/>').addClass(self.options.name + '-msg').hide();
		nonce.before(msg);
		// edit button click
		editBtn.bind('click', function(e) {
			e.preventDefault();
			// show hide stuff
			editBtn.hide();
			span.hide();
			url.show();
			okayBtn.show();
			cancel.show();
		});
		
		okayBtn.bind('click', function(e) {
			e.preventDefault();
			// show hide stuff
			editBtn.show();
			url.hide();
			okayBtn.hide();
			cancel.hide();
			
			// URL value
			self.options.url = url.val();
			
			self.options.postID = (postID) ? postID : '-1';
			
			// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			$.getJSON(ajaxurl, self.options, function(response) {
				if(response.code === 1) {
					msg.removeClass('error').addClass('updated').text(response.msg).hide();
					span.text(url.val());
				} else {
					msg.removeClass('updated').addClass('error').text(response.msg).fadeIn();
					url.val(span.text());
				}
			});
			span.show();
		});
		
		cancel.bind('click', function(e) {
			e.preventDefault();
			// set the textfield value back to the span value
			url.val(span.text());
			span.show();
			editBtn.show();
			url.hide();
			cancel.hide();
			okayBtn.hide();
		});
		
	};
	
	return parent;
	
	
})(SHRTNR || {}, jQuery);

jQuery(document).ready(function() {
	SHRTNR.init();
});
