$(document).on('ready', function() {
	$('#main-user-icon').on('click', function() {
		App.confirm('确定要出退登录吗？', function(state) {
			if(state) {
				App.ajax({
					url: 'admin/logout',
					type: 'post',
					dataType: 'json',
					success: function() {
						location.href = 'admin/';
					}
				});
			}
		});
	});
	$('#main-menu-toggle').on('click', function(e) {
		var aside = $('#main-aside');
		if(!aside.is(':visible')) {
			aside.addClass('visible').css('opacity');
			aside.addClass('slide');
			e.stopPropagation();
			$(document).one('click', function() {
				$('#main-aside').removeClass('visible slide');
			});
		}
	});
	$('#main-aside').on('click', function(e) {
		var tag = e.target.tagName;
		if(['NAV', 'ASIDE'].indexOf(tag) > -1) {
			e.stopPropagation();
		}
	});

	$(window).on('hashchange', function() {
		var hash    = (location.hash || '').substr(1).split('/');
		var action  = (hash[0] || 'index') + 'Action';
		var params  = hash.slice(1);
		if(window.Controller) {
			if(action in window.Controller) {
				window.Controller[action].call(window.Controller, params);
			} else {
				(window.Controller.index || $.noop).call(window.Controller, params);
			}
		}
	}).trigger('hashchange');
	App.hash = function(hash) {
		var loc = location.href;
		var url = loc.split('#')[0] + '#' + hash;
		if(loc == url) {
			$(window).trigger('hashchange');
		} else {
			location.href = url;
		}
	}
});

