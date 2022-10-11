<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    $dev = MODX_BASE_PATH . 'Extras/ImportGoods/';
    /** @var xPDOCacheManager $cache */
    $cache = $modx->getCacheManager();
    if (file_exists($dev) && $cache) {
        if (!is_link($dev . 'assets/components/importgoods')) {
            $cache->deleteTree(
                $dev . 'assets/components/importgoods/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_ASSETS_PATH . 'components/importgoods/', $dev . 'assets/components/importgoods');
        }
        if (!is_link($dev . 'core/components/importgoods')) {
            $cache->deleteTree(
                $dev . 'core/components/importgoods/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_CORE_PATH . 'components/importgoods/', $dev . 'core/components/importgoods');
        }
    }
}

return true;