$(document).on('ready', function() {
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

});
setTimeout(function() {
	App.Loading.hide();
}, 1000);