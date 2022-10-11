<?php

/**
 * The home manager controller for ImportGoods.
 *
 */
class ImportGoodsHomeManagerController extends modExtraManagerController
{
    /** @var ImportGoods $ImportGoods */
    public $ImportGoods;


    /**
     *
     */
    public function initialize()
    {
        $this->ImportGoods = $this->modx->getService('ImportGoods', 'ImportGoods', MODX_CORE_PATH . 'components/importgoods/model/');
        parent::initialize();
    }


    /**
     * @return array
     */
    public function getLanguageTopics()
    {
        return ['importgoods:default'];
    }


    /**
     * @return bool
     */
    public function checkPermissions()
    {
        return true;
    }


    /**
     * @return null|string
     */
    public function getPageTitle()
    {
        return $this->modx->lexicon('importgoods');
    }


    /**
     * @return void
     */
    public function loadCustomCssJs()
    {
        $this->addJavascript($this->ImportGoods->config['jsUrl'] . 'mgr/importgoods.js');
        $this->addJavascript($this->ImportGoods->config['jsUrl'] . 'mgr/widgets/home.panel.js');
        $this->addJavascript($this->ImportGoods->config['jsUrl'] . 'mgr/sections/home.js');

        $this->addHtml('<script type="text/javascript">
        ImportGoods.config = ' . json_encode($this->ImportGoods->config) . ';
        ImportGoods.config.connector_url = "' . $this->ImportGoods->config['connectorUrl'] . '";
        Ext.onReady(function() {MODx.load({ xtype: "importgoods-page-home"});});
        </script>');
    }


    /**
     * @return string
     */
    public function getTemplateFile()
    {
        $this->content .= '<div id="importgoods-panel-home-div"></div>';

        return '';
    }
}