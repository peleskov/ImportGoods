ImportGoods.panel.Home = function (config) {
    config = config || {};
    Ext.apply(config, {
        baseCls: 'modx-formpanel',
        layout: 'anchor',
        hideMode: 'offsets',
        items: [{
            html: '<h2>' + _('importgoods') + '</h2>',
            cls: '',
            style: { margin: '15px 0' }
        }, {
            xtype: 'modx-tabs',
            defaults: { border: false, autoHeight: true },
            border: true,
            hideMode: 'offsets',
            items: [{
                title: _('importgoods_import'),
                layout: 'anchor',
                items: [{
                    html: _('importgoods_menu_desc'),
                    cls: 'panel-desc',
                }, {
                    xtype: 'importgoods-import-form',
                    cls: 'main-wrapper',
                }]
            }]
        }]
    });
    ImportGoods.panel.Home.superclass.constructor.call(this, config);
};
Ext.extend(ImportGoods.panel.Home, MODx.Panel);
Ext.reg('importgoods-panel-home', ImportGoods.panel.Home);

ImportGoods.panel.createImport = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        url: ImportGoods.config.connectorUrl,
        baseParams: {
            action: 'mgr/import'
        },
        id: 'importgoods-import-form',
        width: '98%',
        items: [{
            xtype: 'modx-panel',
            title: _('importgoods.tab.input'),
            items: [{
                layout: 'form',
                width: '100%',
                autoHeight: true,
                border: true,
                buttonAlign: 'center',
                items: [{
                    xtype: 'panel',
                    border: false,
                    cls: 'main-wrapper',
                    layout: 'form',
                    items: [{
                        layout: 'form',
                        border: false,
                        anchor: "100%",
                        labelAlign: 'top',
                        items: [{
                            html: _('importgoods_import_intro_msg'),
                            border: false,
                        }, {
                            xtype: 'modx-combo-browser',
                            fieldLabel: _('importgoods_import_file'),
                            name: 'csv_file',
                            id: config.id + '-csv_file',
                            allowBlank: false,
                        }, {
                            xtype: 'numberfield',
                            fieldLabel: _('importgoods_import_pid'),
                            name: 'pid',
                            id: config.id + '-pid',
                            allowBlank: false,
                        }, {
                            xtype: 'xcheckbox',
                            fieldLabel: _('importgoods_item_published'),
                            name: 'published',
                            id: config.id + '-top',
                        }]
                    }]
                }]
            }],
            buttons: [{
                text: _('importgoods_import_start'),
                cls: 'primary-button',
                scope: this,
                handler: function () {
                    this.getForm().submit({
                        success: function (form, action) {
                            Ext.Msg.alert('Импорт завершен!', action.result.message);
                        },
                        failure: function (form, action) {
                            Ext.Msg.alert('Ошибка!', (action?.result?.message === undefined)? _('importgoods_import_err_form'):action.result.message);
                        }
                    });
                }
            }]
        }]
    });
    ImportGoods.panel.createImport.superclass.constructor.call(this, config);
};
Ext.extend(ImportGoods.panel.createImport, MODx.FormPanel);
Ext.reg('importgoods-import-form', ImportGoods.panel.createImport);