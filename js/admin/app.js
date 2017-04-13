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
				var err = JSON.parse(jqXHR.getResponseHeader('APP-ERROR') || '{}');
				if(err.message) {
					jqXHR.error = err.message;
					jqXHR.errorCode = err.code;
					opts.error(jqXHR, 'custom');
				} else {
					try {
						if(opts.target) {
							$(opts.target).html(data);
						}
						success(data, textStatus, jqXHR);
					} catch(e) {
						if(e.exType) {
							jqXHR.error = e.exMessage;
							jqXHR.errorCode = '';
							opts.error(jqXHR, 'custom');
						} else {
							throw e;
						}
					}
				}
			}
		}
		opts.error = function(jqXHR, textStatus, errorThrown) {
			var rs = error(jqXHR, textStatus, errorThrown);
			if(rs == undefined || rs) {
				// textStatus: "timeout", "error", "abort", and "parsererror", 'custom', 'except'
				switch(textStatus) {
					case 'custom':
						app.popover(
							(jqXHR.errorCode? ('[' + jqXHR.errorCode + ']: '): '') + 
							(jqXHR.error || '请求错误。'),
							'danger'
						);
						break;
					case 'except':
						app.popover('请求异常。', 'danger');
						break;
					case 'timeout':
						app.popover('请求超时，请检查网络是否正常。', 'danger');
						break;
					case 'abort':
						break;
					default:
						var err = JSON.parse(jqXHR.getResponseHeader('APP-ERROR') || '{}');
						if(err.message) {
							app.popover(err.message, 'danger');
						} else {
							app.popover((errorThrown? ('[' + errorThrown + ']: '): '') + textStatus, 'danger');
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
		}

		app.Loading.show();
		opts.url = 'admin/login-auth';
		return $.ajax(opts);
	}

	app.submitForm = function(form, opts) {
		var rs = (opts.checkValidity || $.noop).call($(form));
		if((rs === undefined || rs) && app.checkValidity(form)) {
			opts.form = form;
			return app.ajax(opts);
		} else {
			return false;
		}
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

	app.popover = function(message, type) {
		App.Notific.popover(type || 'info', message);
	}

	app.alert = function(message, fn) {
		app.Notific.alert(message);
		fn && fn();
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
