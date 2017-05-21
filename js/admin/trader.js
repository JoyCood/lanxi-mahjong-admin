var Trader = {};
;(function() {

Trader.indexAction = function() {
    this.listAction();
}

Trader.listAction = function(pn, query) {
    var params = [];
    if(pn = parseInt(pn, 10)) {
        params.push('pn=' + pn);
    }
    if(query) {
        params.push(query);
    }
    var url = location.pathname;
    App.ajax({
        url: url + '/list' + (params.length? '?' + params.join('&'): ''),
        target: '#main-body',
        success: function(response) {
            $(window).scrollTop(0);
        }
    });
}

Trader.formAction = function(id) {
    App.ajax({
        url: 'admin/trader/form?id=' + encodeURIComponent(id || ''),
        target: '#main-body',
        success: function(response) {
            $(window).scrollTop(0);
        }
    });
}

})();
