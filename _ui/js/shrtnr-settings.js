SHRTNR.settings = (function(parent, $) {
	
	var self = parent.settings = parent.settings || {};
	
	self.init = function() {

		var settingsForm = $(parent.options.settingsForm),
			custom = $('#shrtnr-custom'),
			customTrigger = $('.shrtnr-domain'),
			index = 0;
		
		this.tabs(index);
			
		settingsForm.bind('change', function(e) {
			self.preview();
		});
		
		this.custom = false;

		if(customTrigger.filter(':checked').val() === 'custom') {
			custom.show();
			this.custom = true;
		} else {
			custom.hide();
		}
		
		customTrigger.bind('change', function(e) {
			if($(this).val() === 'custom') {
				self.custom = true;
				custom.fadeTo(100, 1, function(e) {
					custom.show();
				});
			} else {
				self.custom = false;
				custom.fadeTo(100, 0, function(e) {
					custom.hide();
				});
			}
		});
		
		this.preview();
	};
	
	self.preview = function() {
		
		var url,
			code = generateString(),
			previewCode = $(parent.options.previewField + 'code'),
			previewURL = $(parent.options.previewField + 'url');
		
		if(this.custom) {
			url = $('#shrtnr-custom-domain').val() + '/';
		} else {
			url = $('#shtrnr-regular-url').text() + '/';
		}
		previewURL.text(url);
		previewCode.text(code);
		
	};
	
	self.tabs = function(index) {
		// @TODO: this needs to be unstupified
		var triggers = $('#shrtnr-settings-tabs li a'),
			targets = $('.shrtnr-sections');
		
		targets.hide().eq(index).show();
		triggers.eq(index).addClass('active');
		
		triggers.bind('click', function(e) {
			e.preventDefault();
			triggers.removeClass();
			$(this).addClass('active');
			var i = triggers.index(this);
			targets.hide().eq(i).show();
		});
		
	};
	
	/**
	 * Private members
	 */
	var generateString = function() {
		// @TODO: set the variables in the options
		var chars,
			defaultLength = $('#shrtnr-default-length').val();
		// $length = $options['default_length'];
		// 
		// if($options['use_lower']) {
		chars = chars + "abcdefghijklmnopqrstuvwxyz";
		// }
		// if($options['use_upper']) {
		chars = chars + "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		// }
		// if($options['use_numbers']) {
		chars = chars + "0123456789";
		// }
		// 
		for (var i = 0, str = ''; i < defaultLength; i++) {
			str += chars[Math.floor(Math.random() * chars.length)];
		}
		
		return str;
	};
	
	return self;
	
})(SHRTNR || {}, jQuery);