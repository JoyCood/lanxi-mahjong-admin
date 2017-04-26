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

	win.on('hashchange', function() {
		var hash    = (location.hash || '').substr(1).split('/');
		var action  = (hash[0] || 'index') + 'Action';
		var params  = hash.slice(1);
		if(window.Controller) {
			if(action in window.Controller) {
				window.Controller[action].apply(window.Controller, params);
			} else {
				(window.Controller.indexAction || $.noop).call(window.Controller, params);
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
	});
});

