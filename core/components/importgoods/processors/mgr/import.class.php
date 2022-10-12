<?php

require_once MODX_CORE_PATH . 'model/modx/modprocessor.class.php';
require_once MODX_CORE_PATH . 'model/modx/processors/resource/create.class.php';

class ImportGoodsProcessor extends modProcessor
{
    public function process()
    {
        if (empty($this->getProperty('csv_file'))) {
            return $this->failure();
        } else {
            $key = 'article';
            $update = true;

            $csv_file = MODX_BASE_PATH . $this->getProperty('csv_file');
            $pid = $this->getProperty('pid');
            $published = $this->getProperty('published');
            
            if (!file_exists($csv_file) || ($handle = fopen($csv_file, "r")) === FALSE || !$this->modx->getObject('modResource', $pid)) {
                return $this->failure();
            } else {
                $rows = $created = $updated = $errors = 0;
                while (($csv = fgetcsv($handle, null, ";")) !== FALSE) {
                    if($csv[0] == 'id') continue;
                    /*
                    0 => id
                    1 => Title
                    2 => Brand
                    3 => Price
                    4 => Sizes
                    5 => Description
                    6 => Images
                    */
                    $rows++;
                    $sizes = explode('||', $csv[4]);
                    $gallery = explode('||', $csv[6]);
                    $tpl = $this->modx->getOption('ms2_template_product_default', null, 1);
                    $vendor_name = $csv[2];
                    if (!$vendor = $this->modx->getObject('msVendor', array('name' => $vendor_name))) {
                        $vendor = $this->modx->newObject('msVendor');
                        $vendor->set('name', $vendor_name);
                        $vendor->save();
                    }
                    $data = array(
                        'class_key' => 'msProduct',
                        'context_key' => 'web',
                        'parent' => $pid,
                        'template' => $tpl,
                        'published' => $published,
                        'article' => md5($csv[0]),
                        'pagetitle' => $csv[1],
                        'vendor' => $vendor->get('id'),
                        'price' => $csv[3],
                        'content' => str_replace('‘', '', $csv[5]),
                        'size' => $sizes,
                        'alias' => $csv[0],
                    );

                    // Duplicate check
                    $q = $this->modx->newQuery($data['class_key']);
                    $q->select($data['class_key'] . '.id');
                    $q->innerJoin('msProductData', 'Data', $data['class_key'] . '.id = Data.id');
                    $is_product = true;
                    $tmp = $this->modx->getFields($data['class_key']);
                    $q->where(array('Data.' . 'article' => $data['article']));
                    $q->prepare();
                    if ($exists = $this->modx->getObject($data['class_key'], $q)) {
                        $action = 'update';
                        $data['id'] = $exists->id;
                    } else {
                        $action = 'create';
                    }
                
                    // Create or update resource
                    /** @var modProcessorResponse $response */
                    $response = $this->modx->runProcessor('resource/' . $action, $data);
                    if ($response->isError()) {
                        $this->modx->log(1, "Error on $action: \n" . print_r($response->getAllErrors(), 1));
                        $errors++;
                    } else {
                        if ($action == 'update') {
                            $updated++;
                        } else {
                            $created++;
                        }
                
                        $resource = $response->getObject();
                
                        // Process gallery images, if exists
                        if (!empty($gallery)) {
                            foreach ($gallery as $v) {
                                if (empty($v)) {
                                    continue;
                                }
                                $image = pathinfo($csv_file, PATHINFO_DIRNAME) . '/imgs/' . $v;
                                if (file_exists($image)) {
                                    $response = $this->modx->runProcessor(
                                        'gallery/upload',
                                        array('id' => $resource['id'], 'name' => $v, 'file' => $image),
                                        array('processors_path' => MODX_CORE_PATH . 'components/minishop2/processors/mgr/')
                                    );
                                    unlink($image);
                                }
                            }
                        }
                    }
                }
                return $this->success("<p>Всего обработано $rows строк!</p><p>Добавлено новых товаров: $created</p><p>Обновлено товаров: $updated</p><p>Строк с ошибками: $errors</p>");
            }
        }
    }
}

return 'ImportGoodsProcessor';
