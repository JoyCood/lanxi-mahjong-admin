$.fn.extend({
	scrollListPage: function(fn) {
		var element = $(this);
		if(!element.attr('scroll-init')) {
			var func = function() {
				if(element.length) {
					if(!element.data('loading')) {
						var doc          = document.body;
						var height       = doc.clientHeight;
						var scrollHeight = doc.scrollHeight;
						var scrollTop    = doc.scrollTop;
						var offset       = 100;
						if(scrollTop + height > scrollHeight - offset) {
							var href  = element.attr('data-src');
							if(href) {
								App.ajax({
									url: href,
									dataType: 'html',
									success: function(resp) {
										var table = $(resp);
										element.attr({
											'data-src': table.attr('data-src')
										});
										element.find('tbody:last').after(table.children('tbody'));
										element.children('tfoot').replaceWith(table.children('tfoot'));
										table = null;
									},
									complete: function() {
										console.log('complete');
										element.removeData('loading');
									}
								});
							}
							element.data('loading', true);
						}
					}
				} else {
					element.removeData('loading');
					element = null;
					$(window).off('scroll.slop', func);
				}
			}
			$(window).on('scroll.solp', func);
			element.attr('scroll-init', 'on');
		}
	}
});

$(document).on('ready', function() {
	var win = $(window);
	var doc = $(document);
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

	App.Loading.hide();
    win.on('scroll', function() {
        var scrollTop = document.body.scrollTop || document.documentElement.scrollTop;
        if(scrollTop > 60) {
            $('#to-top').show();
        } else {
            $('#to-top').hide();
        }
    });
    $('#to-top').on('click', function() {
        $(window).scrollTop(0);
    });

	win.on('hashchange', function() {
		var hash = location.hash;
		if(hash.indexOf('/menu/') > -1) {
			var menu = $('#main-menu');
			if(!menu.attr('app-init')) {
				menu.attr('app-init', 'on').on('click', function(e) {
					var target = e.target;
					if(target.id == 'main-menu' || target.id == 'main-menu-close') { //target.tagName == 'A') {
						history.back();
						return false;
					}
				}).on('webkitTransitionEnd', function() {
					if(!$(this).is('.on')) {
						$(this).css('display', 'none');
					}
				});
			}
			menu.css('display', 'block');
			menu.css('opacity');
			menu.addClass('on');
		} else {
			$('#main-menu').removeClass('on');
		}
	}).trigger('hashchange');

	doc.on('click', '[app-check]', function(e) {
		var radio = $(this);
		switch(radio.attr('app-check')) {
			case 'on':
				radio.closest('tr').find('input[app-check]').trigger('click');
				break;
			case 'off':
				e.preventDefault();
				break;
			case 'all':
				var table = radio.closest('table');
				var items = table.find('input[app-check="item"]');
				items.prop('checked', this.checked);
				break;
			case 'item':
				var table      = radio.closest('table');
				var items      = table.find('input[app-check="item"]');
				var target     = table.find('input[app-check="all"]');
				var itemsNum   = items.length;
				var checkedNum = items.filter(':checked').length;
				var rel        = table.attr('app-check-rel');
				if(checkedNum && checkedNum == itemsNum) {
					target.prop('checked', true);
				} else {
					target.prop('checked', false);
				}
				if(rel) {
					var btns = $(rel).find('button.btn, a.btn').not('[app-check-rel="off"]');
					if(checkedNum == 0) {
						btns.prop('disabled', true).addClass('disabled');
					} else {
						btns.prop('disabled', false).removeClass('disabled');
					}
				}
				break;
		}
		e.stopPropagation();
	}).on('click', '.disabled', function() {
		return false;
	}).on('click', '[app-action]', function() {
		var act = this.getAttribute('app-action');
		switch(act) {
			case 'back':
				if(!sessionStorage.getItem('start')) {
					sessionStorage.setItem('start', 'on');
					location.href = 'region/';
				} else {
					history.back();
				}
				break;
			case 'menu':
				var hash = location.hash || '';
				if(hash.indexOf('/menu/') == -1) {
					hash = hash.replace(/\/$/, '') + '/menu/';
				}
				location.hash = hash;
				break;
			case 'logout':
				App.ajax({
					url: 'region/logout',
					type: 'post',
					dataType: 'json',
					success: function() {
						location.href = 'region/login';
					}
				});
				break;
		}
	});
});

