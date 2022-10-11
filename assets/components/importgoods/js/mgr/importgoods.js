var ImportGoods = function (config) {
    config = config || {};
    ImportGoods.superclass.constructor.call(this, config);
};
Ext.extend(ImportGoods, Ext.Component, {
    page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, view: {}, utils: {}
});
Ext.reg('importgoods', ImportGoods);

ImportGoods = new ImportGoods();