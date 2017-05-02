var App = function() {
	var app = new Object();

	app.ajax = function(opts) {
		var success 	= opts.success || $.noop;
		var error	 	= opts.error || $.noop;
		var complete	= opts.complete || $.noop;
		var form		= opts.form? $(opts.form): null;
		if(form) {
			opts.data = form.serialize();
			if(!opts.url) {
				opts.url = form.attr('action');
			}
			if(!opts.type) {
				opts.type = (form.attr('method') || 'post').toLowerCase();
			}
			if(!opts.target) {
				opts.target = form.attr('target');
			}
			if(!opts.dataType) {
				opts.dataType = form.attr('data-type') || 'json';
			}
			form.find('button').each(function() {
				var disabled = this.disabled;
				$(this).attr('app-disabled', disabled? 'on': '')
					.prop('disabled', true);
			});
		}
		if(opts.data) {
			if(typeof opts.data == 'string') {
				opts.data += '&__url=' + encodeURIComponent(location.href);
			} else {
				opts.data.__url = location.href;
			}
		}

		opts.success = function(data, textStatus, jqXHR) {
			if(jqXHR.getResponseHeader('APP-STATE') !== 'APP') {
				opts.error(jqXHR, 'except');
			} else {
				try {
					if(opts.target) {
						$(opts.target).html(data);
					}
					success(data, textStatus, jqXHR);
				} catch(e) {
					opts.error(jqXHR, 'custom', e);
				}
			}
		}
		opts.error = function(jqXHR, textStatus, errorThrown) {
			if(opts.target) {
				$(opts.target).html(jqXHR.responseText);
				return;
			}
			var rs = error(jqXHR, textStatus, errorThrown);
			if(rs == undefined || rs) {
				// textStatus: "timeout", "error", "abort", and "parsererror", 'custom', 'except'
				switch(textStatus) {
					case 'custom':
						app.Notific.alert(
							// (jqXHR.errorCode? ('[' + jqXHR.errorCode + ']: '): '') + 
							(errorThrown || '请求错误。')
						);
						break;
					case 'except':
						app.Notific.alert('请求异常。');
						break;
					case 'timeout':
						app.Notific.alert('请求超时，请检查网络是否正常。');
						break;
					case 'abort':
						break;
					default:
						var err = {
							'code': jqXHR.getResponseHeader('APP-CODE'),
							'message': JSON.parse(jqXHR.getResponseHeader('APP-ERROR') || '')
						}
						if(err.message) {
							app.Notific.alert(err.message);
						} else {
							app.Notific.alert((errorThrown? ('[' + errorThrown + ']: '): '') + textStatus);
						}
						break;
				}
			}
		}

		opts.complete = function(jqXHR, textStatus) {
			app.Loading.hide();
			if(form) {
				form.find('button').each(function() {
					var button = $(this);
					var disabled = button.attr('app-disabled');
					this.disabled = disabled? true: false;
					button.removeAttr('app-disabled');
				});
			}
			complete(jqXHR, textStatus);
		}

		app.Loading.show();
		return $.ajax(opts);
	}

	app.submitForm = function(form, opts) {
		if(app.checkValidity(form)) {
			var rs = (opts.checkValidity || $.noop).call($(form));
			if(rs === undefined || rs) {
				opts.form = form;
				return app.ajax(opts);
			} 
		}
		return false;
	}

	app.checkValidity = function(form) {
		var invalid = [];
		$('[app-required], [app-pattern]', form).each(function() {
			var element = $(this);
			var val = this.value;
			var required = this.getAttribute('app-required');
			var state = true;
			if(required) {
				switch(this.type) {
					case 'text':
					case 'number':
						this.value = val = $.trim(val);
					case 'textarea':
					case 'password':
						if(val.length == 0) {
							element.addClass('invalid required')
								.off('focus.acv')
								.on('focus.acv', function() {
									$(this).removeClass('invalid required').off('focus.acv');
								});
							invalid.push(element);
							state = false;
						}
						break;
				}
			}
			if(state) {
				var pattern = element.attr('app-pattern');
				switch(pattern) {
					default:
						pattern = new RegExp(pattern);
						if(!pattern.test(val)) {
							element.addClass('invalid')
								.off('focus.acv')
								.on('focus.acv', function() {
									$(this).removeClass('invalid required').off('focus.acv');
								});
							invalid.push(element);
						}
						break;
				}
			}
		});
		if(invalid.length) {
			app.checkValidity.invalid(invalid);
			return false;
		} else {
			return true;
		}
	}
	app.checkValidity.invalid = function(invalidElements, callback) {
		var element = invalidElements[0];
		callback = callback || function() {
			var o = $(element).offset();
			element.focus();
		}
		if(element.hasClass('required')) {
			var msg = element.attr('app-required-error') || element.attr('placeholder') || '请填写必要的字段。'
			app.alert(msg, callback);
		} else {
			app.alert(element.attr('app-format-error') || '请按格式填写相应的字段。', callback);
		}
	}

	app.except = function(message, type) {
		throw {
			'exType': type || 'custom',
			'exMessage': message || 'error'
		}
	}

	app.Loading = new function() {
		var timer, count = 1;
		var delay = 100;

		this.show = function() {
			count++;
			if(count == 1) {
				timer = setTimeout(function() {
					$('#app-loading').show();
				}, delay);
			}
		},

		this.hide = function() {
			count--;
			if(count <= 0) {
				clearTimeout(timer);
				count = 0;
				$('#app-loading').hide();
			}
		}
	}

	return app;
}();


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
	var _alert = function(type, msg) {
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

	this.popover = function(type, msg) {
		_alert(type, msg);
	}

	this.info = function(msg) {
		_alert('info', msg);
	}

	this.success = function(msg) {
		_alert('success', msg);
	}

	this.warning = function(msg) {
		_alert('warning', msg);
	}

	this.error = function(msg) {
		_alert('danger', msg);
	}

	this.alert = this.error;
}

App.Mask = new function() {
	var count  = 0;
	var create = function() {
		var mask = $('#app-mask');
		if(mask.length == 0) {
			mask = $('<div id="app-mask"></div>');
			mask.appendTo(document.body);
		}
		return mask;
	}

	this.show = function() {
		if(count == 0) {
			create().show();
		}
		count++;
	}

	this.hide = function() {
		count--;
		if(count <= 0) {
			count = 0;
			create().hide();
		}
	}
}

App.Dialog = function(options) {
	var inc = (App.Dialog.increment || 0) + 1;
	var dialog;
	var mask;

	var init = function(options) {
		mask   = options.mask? true: false;
		dialog = $([ 
			'<div id="app-dialog-' + inc + '" class="app-dialog">',
				'<div' + (options.id? ' id="' + options.id + '"': '') + ' class="app-dialog-container">',
					'<header class="app-dialog-header">',
						'<span class="app-dialog-title"></span>',
						'<a class="app-dialog-close" app-role="app-dialog-close" app-value="0">&times;</a>',
					'</header>',
					'<div class="app-dialog-body">',
						'<div class="app-dialog-content"></div>',
					'</div>',
					'<footer class="app-dialog-footer"></footer>',
				'</div>',
			'</div>'
		].join(''));
		dialog.on('transitionend', function() {
			if(!$(this).hasClass('open')) {
				$(this).css('display', '');
			}
		}).on('click', '[app-role="app-dialog-close"]', function() {
			close($(this).attr('app-value'));
		}).on('mousedown', 'span.app-dialog-title', function(e) {
			dialog.data('margin', {
				top: parseInt(dialog.children().css('margin-top')) || 0,
				left: parseInt(dialog.children().css('margin-left')) || 0
			});
			dialog.data('offset', {
				'x': e.pageX,
				'y': e.pageY
			});
			$(window).on('mousemove.admv', function(e) {
				var position = dialog.data('margin');
				var offset   = dialog.data('offset');
				var top      = e.pageY - offset.y;
				var left     = e.pageX - offset.x;
				dialog.children().css({
					marginTop: position.top + top,
					marginLeft: position.left + left
				});
				return false;
			}).on('mouseup.admv', function() {
				$(window).off('.admv');
			});
		}).on('dblclick', function() {
			dialog.children().css({
				'margin-left': '',
				'margin-top': ''
			});
		});

		title(options.title || '');

		dialog.appendTo(document.body);
	}

	var title = function(text) {
		dialog.find('span.app-dialog-title').html(text);
	}
	var close = function(val) {
		var rs = (options.close || $.noop)(parseInt(val, 10) || 0);
		if(rs || rs == undefined) {
			dialog.css('display', 'block').css('opacity');
			dialog.removeClass('open');
			$(document).off('keydown.adkp');
		}
		mask && App.Mask.hide();
	}
	var open = function() {
		dialog.css('display', 'block').css('opacity');
		dialog.addClass('open');
		$(document).on('keydown.adkp', function(e) {
			if(e.keyCode == 27) {
				dialog.find('a.app-dialog-close').trigger('click');
			}
		});
		mask && App.Mask.show();
	}

	this.title   = title;
	this.element = function() {
		return dialog;
	}
	this.content = function(content) {
		dialog.find('div.app-dialog-body').empty().append($(content));
	}
	this.footer = function(content) {
		if(content) {
			dialog.find('footer.app-dialog-footer').empty().append($(content)).show();
		} else {
			dialog.find('footer.app-dialog-footer').empty().hide();
		}
	}
	this.open = function() {
		open();
	}
	this.close = function() {
		close(1);
	}
	init.call(this, options || {});
	App.Dialog.increment = inc;
}

App.Alert = new function() {
	var dialog;

	var getDialog = function(opts) {
		if(!dialog) {
			dialog = new App.Dialog(opts);
		}
		return dialog;
	}
	var open = function(title, msg, callback) {
		var dialog = getDialog({
			mask: true,
			close: callback
		});
		dialog.title(title);
		dialog.content([
			'<div class="app-dialog-content">',
			msg,
			'</div>'
		].join(''));
		dialog.element().addClass('app-dialog-alert');
		// dialog.element().css('z-index', 99);
		dialog.open();

		return dialog;
	}

	this.info = function(msg, callback) {
		open('<i class="fa fa-info-circle text-info"></i> 提示信息', msg, callback).footer([
			'<button type="button" class="btn btn-primary" app-role="app-dialog-close" app-value="1">确定</button>'
		].join(''));
	}

	this.alert = function(msg, callback) {
		open('<i class="fa fa-warning text-warning"></i> 提示信息', msg, callback).footer([
			'<button type="button" class="btn btn-primary" app-role="app-dialog-close" app-value="1">确定</button>'
		].join(''));
	}
	this.confirm = function(msg, callback) {
		open('<i class="fa fa-question-circle text-primary"></i> 确认信息', msg, callback).footer([
			'<button type="button" class="btn btn-primary" app-role="app-dialog-close" app-value="1">确定</button>',
			'<button type="button" class="btn" app-role="app-dialog-close" app-value="0">取消</button>',
		].join(''));
	}
}
App.alert = App.Alert.alert;
App.info = App.Alert.info;
App.confirm = App.Alert.confirm;

