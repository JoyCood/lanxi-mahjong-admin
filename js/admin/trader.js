var Trader = {};
;(function() {

Trader.indexAction = function() {
    this.listAction();
}

Trader.listAction = function() {
    App.ajax({
        url: 'admin/trader/list',
        target: '#main-body',
        success: function(response) {

        }
    });
}

Trader.formAction = function(id) {
    App.ajax({
        url: 'admin/trader/form?id=' + encodeURIComponent(id || ''),
        target: '#main-body',
        success: function(response) {

        }
    });
}

Trader.submit = function(form) {
    App.submitForm(form, {
        
    });
}
})();
