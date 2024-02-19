<?php

require_once MODX_CORE_PATH . 'model/modx/modprocessor.class.php';
require_once MODX_CORE_PATH . 'model/modx/processors/resource/create.class.php';
require_once MODX_CORE_PATH . 'model/modx/processors/resource/update.class.php';

class ImportGoodsProcessor extends modProcessor
{
    public function process()
    {
        $csv_file = MODX_BASE_PATH . $this->getProperty('csv_file');
        $pid = $this->getProperty('pid');
        $published = $this->getProperty('published');
        $key = 'article';
        $update = true;
        if (empty($this->getProperty('csv_file')) || !file_exists($csv_file) || ($handle = fopen($csv_file, "r")) === FALSE) {
            return $this->failure('Укажите существующий корректный csv файл с товарами!');
        }

        if (!$this->modx->getObject('modResource', $pid)) {
            return $this->failure('Укажите ID существующего родительского ресурса!');
        }

        $tpl_good = $this->modx->getOption('ms2_template_product_default', null, 1);
        $tpl_cat = $this->modx->getOption('ms2_template_category_default', null, 1);

        $rows = $created = $updated = $errors = 0;
        while (($csv = fgetcsv($handle, null, ";")) !== FALSE) {
            if ($csv[0] == 'id') continue;
            /*
                   0 item_id,
                   1 item_crumbs,
                   2 item_title,
                   3 item_brand,
                   4 item_price,
                   5 item_sizes,
                   6 item_params,
                   7 item_desc,
                   8 item_images,
                                        */


            /* Проверим каталоги и создадим если нет*/
            $crumbs = explode('||', $csv[1]);
            $parent = $pid;
            if (count($crumbs) > 0) {
                foreach ($crumbs as $crumb) {
                    if($crumb == '') continue;
                    if (!$category = $this->modx->getObject('modResource', ['pagetitle' => $crumb, 'parent' => $parent])) {
                        $alias_cat = $this->modx->filterPathSegment($crumb);
                        $alias_count = $this->modx->getCount('modResource', ['alias' => $alias_cat]);
                        if ($alias_count > 0) {
                            $alias_cat = $this->modx->filterPathSegment($crumb . '_' . rand());
                        }
                        $params = [
                            'class_key' => 'msCategory',
                            'pagetitle' => $crumb,
                            'parent' => $parent,
                            'template' => $tpl_cat,
                            'published' => true,
                            'alias' => $alias_cat,
                        ];
                        $category = $this->modx->newObject('modResource', $params);
                        if (!$category->save()) {
                            $this->modx->log(1, "Error on save catyegory $crumb: \n" . print_r($category->getAllErrors(), 1));
                            break;
                        };
                    }
                    $parent = $category->get('id');
                }
            }
            $rows++;
            $sizes = explode('||', $csv[5]);
            $gallery = explode('||', $csv[8]);
            $vendor_name = $csv[3];
            $this->modx->error->reset();
            if (!$vendor = $this->modx->getObject('msVendor', array('name' => $vendor_name))) {
                $vendor = $this->modx->newObject('msVendor');
                $vendor->set('name', $vendor_name);
                $vendor->save();
            }

            $data = array(
                'class_key' => 'msProduct',
                'context_key' => 'web',
                'parent' => $parent,
                'template' => $tpl_good,
                'published' => $published,
                'article' => md5($csv[0]),
                'pagetitle' => strip_tags($csv[2]),
                'longtitle' => $csv[2],
                'vendor' => $vendor->get('id'),
                'price' => $csv[4],
                'content' => str_replace('‘', '', $csv[7]),
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
                        $image = pathinfo($csv_file, PATHINFO_DIRNAME) . '/images/' . $v;
                        if (file_exists($image)) {
                            $response_gal = $this->modx->runProcessor(
                                'gallery/upload',
                                array('id' => $resource['id'], 'name' => $v, 'file' => $image),
                                array('processors_path' => MODX_CORE_PATH . 'components/minishop2/processors/mgr/')
                            );
                            if ($response_gal->isError()) {
                                $err = $response_gal->getAllErrors();
                                if( $err[0] == 'Такое изображение уже есть в галерее товара.') unlink($image);
                                else $this->modx->log(1, "Error on gallery/upload img: $image: \n" . print_r($err, 1));
                            } else {
                                unlink($image);
                            }
                        }
                    }
                }
            }
        }
        return $this->success("<p>Всего обработано $rows строк!</p><p>Добавлено новых товаров: $created</p><p>Обновлено товаров: $updated</p><p>Строк с ошибками: $errors</p>");
    }
}

return 'ImportGoodsProcessor';
