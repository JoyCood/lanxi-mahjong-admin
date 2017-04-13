App.Box = function() {
	var box = new Object();

	var createBox = function() {
		var div = $('#app-box');
		if(div.length == 0) {
			div = $([
				'<div id="app-box">',
					'<div id="app-box-content">',
						'<a id="app-box-close"><i class="fa fa-times"></i></a>',
					'</div>',
				'</div>'
			].join(''));
			div.appendTo(document.body);
			div.on('click', '#app-box-close', function() {
				close('close');
			}).on('click', 'button[app-state]', function() {
				var state = $(this).attr('app-state');
				close(state);
			});
		}
		return div;
	}

	box.open = function(src) {
		var div = createBox();
		box.state = true;
		$.ajax({
			url: src,
			dataType: 'html',
			success: function(response) {
				var context = $('<div id="app-box-body"></div>');
				context.html(response);
				context.appendTo('#app-box-content');
				div.show(0, function() {
					var content = context.children().eq(0);
					if(!content.attr('app-init')) {
						content.trigger('init');
						content.attr('app-init', 'on');
					}
					content.find('input[autofocus]').focus();
					content.trigger('active');
				});
			}
		});
	}

	var close = function(state) {
		var content = $('#app-box-body').children().eq(0);
		(state != undefined || state !== true) && content.trigger('close', [box, state]);
		if(box.state != false) {
			content.remove();
			$('#app-box-body').remove();
			createBox().hide();
		}
		box.state = true;
	}

	box.close = function(state) {
		if(state !== false) {
			close(state);
		}
	}

	return box;
}();

App.urlReplace = function(url, key, val) {
	var tmp   = url.split('?');
	var query = '?' + (tmp[1] || '');
	var reg   = new RegExp('([\?&])' + key + '=[^&]*');

	query = query.replace(reg, '');
	query = query + (query? '&': '?');
	if(key) {
		query += encodeURIComponent(key) + '=' + encodeURIComponent(val || '');
	}
	return url = tmp[0] + query;
}

App.PushBox = new function() {
	this.open = function(id, mod, text) {
		var params = [
			'id=' + encodeURIComponent(id),
			'mod=' + encodeURIComponent(mod),
			'text=' + encodeURIComponent(text)
		];
		App.Box.open('push.html?' + params.join('&'));
	}
}

App.Notific = new function() {
	var _box = function() {
		var box = $('#app-notify');
		if(box.length == 0) {
			box = $('<div id="app-notify"></div>');
			box.on('click', 'button.close', function() {
				$(this).closest('div.app-notify-item').trigger('removeItem');
			});
			box.appendTo(document.body);
		}
		return box;
	}
	var _type;
	var _message;
	var _alert = function(type, msg, callback) {
		var box   = _box();
		var items = box.children('div.alert');
		var item  = $('<div class="alert alert-' + type + ' alert-dismissible" role="alert">');
		item.html([
			'<div>',
				'<button type="button" class="close"><span>&times;</span></button>',
				msg,
			'</div>'
		].join(''));
		item.on('removeItem.anty', function() {
			var item = $(this);
			item.children().slideUp(100, function() {
				item.off('.anty').remove();
				item = null;
			});
		}).on('timer.anty', function() {
			var item = $(this);
			item.data('data-timer', setTimeout(function() {
				item.trigger('removeItem');
				item = null;
			}, 5000));
		}).on('mouseleave.anty', function() {
			$(this).trigger('timer');
		}).on('mouseenter.anty', function() {
			clearTimeout($(this).data('data-timer'));
		}).trigger('timer');
		if(items.length > 0) {
			items.eq(0).before(item);
			items.eq(1).remove();
		} else {
			item.appendTo(box);
		}
		item.css('opacity');
		item.addClass('app-notify-item');
	}

	this.popover = function(type, msg, callback) {
		_alert(type, msg, callback);
	}

	this.info = function(msg, callback) {
		_alert('info', msg, callback);
	}

	this.success = function(msg, callback) {
		_alert('success', msg, callback);
	}

	this.warning = function(msg, callback) {
		_alert('warning', msg, callback);
	}

	this.error = function(msg, callback) {
		_alert('danger', msg, callback);
	}

	this.alert = this.error;
}
