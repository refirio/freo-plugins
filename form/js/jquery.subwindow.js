(function($) {
	$.fn.subwindow = function(option) {
		var settings = $.extend({
			option: null,
			width: 400,
			height: 300,
			close: 'close',
			fade: 0
		}, option);

		$.fn.subwindow.settings = settings;

		if ($('div#subwindow').length == 0) {
			$('body').append('<div id="subwindow" style="display:none;"><div id="subwindow_overlay"></div><div id="subwindow_foundation"></div></div>');

			$(document).on('click', 'div#subwindow_overlay', function() {
				$.fn.subwindow.close();
			});
			$(document).on('click', 'div#subwindow_close', function() {
				$.fn.subwindow.close();
			});
		}

		$(document).on('click', this, function() {
			$.fn.subwindow.open(this.getAttribute('href'), this.getAttribute('title'), settings.option, settings.width, settings.height, settings.close, settings.fade);

			return false;
		});

		return this;
	};

	$.fn.subwindow.open = function(url, title, option, width, height, close, fade) {
		if (option == 'null') {
			option = $.fn.subwindow.settings.option;
		}
		if (width == undefined) {
			width = $.fn.subwindow.settings.width;
		}
		if (height == undefined) {
			height = $.fn.subwindow.settings.height;
		}
		if (title == undefined) {
			title = $.fn.subwindow.settings.title;
		}
		if (close == undefined) {
			close = $.fn.subwindow.settings.close;
		}
		if (fade == undefined) {
			fade = $.fn.subwindow.settings.fade;
		}

		$('div#subwindow_foundation').html('');

		title = title ? '<div id="subwindow_title">' + title + '</div>' : '';
		close = close ? '<div id="subwindow_close">' + close + '</div>' : '';

		var content = '';

		if (option) {
			$.ajax({
				type: 'get',
				url: url,
				async: false,
				cache: false,
				dataType: 'html',
				success: function(response)
				{
					$.each($(response).filter(option.filter), function() {
						var html = $(this).html();

						if (option.replace) {
							$.each(option.replace, function() {
								html = html.split(this.key).join(this.value);
							});
						}

						content = '<div id="subwindow_content" style="width:' + width + 'px;height:' + height + 'px;">' + html + '</div>';
					});
				}
			});
		} else {
			if (url.indexOf('?') >= 0) {
				url += '&';
			} else {
				url += '?';
			}
			url += '__subwindow=' + Math.random();

			content = '<iframe src="' + url + '" frameborder="0" width="' + width + '" height="' + height + '" name="subwindow_content" id="subwindow_content" style="display:block;"></iframe>';
		}

		$('div#subwindow_foundation').html(
			title + close + content
		).css({
			'marginTop': '-' + height / 2 + 'px',
			'marginLeft': '-' + width / 2 + 'px'
		});

		$('div#subwindow').fadeIn(fade);

		$.fn.subwindow.callback();
	};

	$.fn.subwindow.close = function() {
		$('div#subwindow').fadeOut($.fn.subwindow.settings.fade);
	};

	$.fn.subwindow.callback = function() {
	};

	$.subwindow = {
		init: function(option) {
			$.fn.subwindow(option);
		},
		open: function(url, title, option, width, height, close, fade) {
			$.fn.subwindow.open(url, title, option, width, height, close, fade);
		},
		close: function(option) {
			$.fn.subwindow.close();
		}
	};
})(jQuery);
