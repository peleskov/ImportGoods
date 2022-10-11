ImportGoods.page.Home = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        components: [{
            xtype: 'importgoods-panel-home',
            renderTo: 'importgoods-panel-home-div'
        }]
    });
    ImportGoods.page.Home.superclass.constructor.call(this, config);
};
Ext.extend(ImportGoods.page.Home, MODx.Component);
Ext.reg('importgoods-page-home', ImportGoods.page.Home);