var Money = {};
;(function() {

Money.inpourAction = function() {
    App.alert('ok');
}

Money.indexAction = function() {
    this.listAction();
}

Money.listAction = function(pn, query) {
    var params = [];
    if(pn = parseInt(pn, 10)) {
        params.push('pn=' + pn);
    }
    if(query) {
        params.push(query);
    }
    var url = location.pathname.replace(/[^\/]*$/, '');
    App.ajax({
        url: url + 'list' + (params.length? '?' + params.join('&'): ''),
        target: '#main-body',
        success: function(response) {
            $(window).scrollTop(0);
        }
    });
}
Money.summaryAction = function(pn, query) {
    var params = [];
    if(pn = parseInt(pn, 10)) {
        params.push('pn=' + pn);
    }
    if(query) {
        params.push(query);
    }
    var url = location.pathname.replace(/[^\/]*$/, '');
    App.ajax({
        url: url + 'summary' + (params.length? '?' + params.join('&'): ''),
        target: '#main-body',
        success: function(response) {
            $(window).scrollTop(0);
        }
    });
}

Money.formAction = function(id) {
    App.ajax({
        url: 'admin/trader/form?id=' + encodeURIComponent(id || ''),
        target: '#main-body',
        success: function(response) {
            $(window).scrollTop(0);
        }
    });
}

})();
