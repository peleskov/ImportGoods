<?php
if (file_exists(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
} else {
    require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.core.php';
}
/** @noinspection PhpIncludeInspection */
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
/** @noinspection PhpIncludeInspection */
require_once MODX_CONNECTORS_PATH . 'index.php';
/** @var ImportGoods $ImportGoods */
$ImportGoods = $modx->getService('ImportGoods', 'ImportGoods', MODX_CORE_PATH . 'components/importgoods/model/');
$modx->lexicon->load('importgoods:default');

// handle request
$corePath = $modx->getOption('importgoods_core_path', null, $modx->getOption('core_path') . 'components/importgoods/');
$path = $modx->getOption('processorsPath', $ImportGoods->config, $corePath . 'processors/');
$modx->getRequest();

/** @var modConnectorRequest $request */
$request = $modx->request;
$request->handleRequest([
    'processors_path' => $path,
    'location' => '',
]);