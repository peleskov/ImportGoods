<?php
/**/
/* For use only in Console */
/**/

class ImportGoods
{
    private $pid;
    private $modx;
    private $imgs_path;

    public function __construct($imgs_path, $pid, $modx)
    {
        $this->modx =& $modx;
        $this->pid = $pid;
        $this->imgs_path = $imgs_path;
    }
    
    
    public function createCategory($data)
    {
        /* Cоздадим категории*/
        $modx = $this->modx;
        $modx->error->reset();
        $crumbs = json_decode($data, 1);
        $parent_id = $this->pid;
        if (count($crumbs) > 0) {
            foreach ($crumbs as $crumb) {
                if ($crumb == '') continue;
                if (!$category = $modx->getObject('modResource', ['pagetitle' => $crumb, 'parent' => $parent_id])) {
                    $alias_cat = $modx->filterPathSegment($crumb);
                    $alias_count = $modx->getCount('modResource', ['alias' => $alias_cat]);
                    if ($alias_count > 0) {
                        $alias_cat = $modx->filterPathSegment($crumb . '_' . rand());
                    }
                    $cat_title = htmlspecialchars(substr(strip_tags($crumb), 0, 150));
                    $cat_title = preg_replace('/[^a-zA-Zа-яА-Я0-9 ?.,!|]/u', '', $cat_title);
                    $params = [
                        'class_key' => 'msCategory',
                        'pagetitle' => $cat_title,
                        'parent' => $parent_id,
                        'template' => $modx->getOption('ms2_template_category_default', null, 1),
                        'published' => true,
                        'alias' => $alias_cat,
                    ];
                    $category = $modx->newObject('modResource', $params);
                    if (!$category->save()) {
                        $modx->log(1, "Error on save catyegory $crumb: \n" . print_r($category->getAllErrors(), 1));
                        break;
                    };
                }
                $parent_id = $category->get('id');
            }            
        }
        return $parent_id;
    }

    public function createVendor($data)
    {
        $modx = $this->modx;
        $modx->error->reset();
        $vendor_id = 0;
        $data = ucfirst($data);
        if (!$vendor = $modx->getObject('msVendor', array('name' => $data))) {
            $vendor = $modx->newObject('msVendor');
            $vendor->set('name', $data);
            if(!$vendor->save()){
                $modx->log(1, "Error on save vendor $data: \n" . print_r($vendor->getAllErrors(), 1));
            } else $vendor_id = $vendor->get('id');
        } else $vendor_id = $vendor->get('id');
        return $vendor_id;
    }
    
    public function createResource($data)
    {
        $modx = $this->modx;
        $modx->error->reset();
        // Duplicate check
        $q = $modx->newQuery($data['class_key']);
        $q->select($data['class_key'] . '.id');
        $q->innerJoin('msProductData', 'Data', $data['class_key'] . '.id = Data.id');
        $q->where(array('Data.' . 'article' => $data['article']));
        $q->prepare();
        if ($exists = $modx->getObject($data['class_key'], $q)) {
            $action = 'update';
            $data['id'] = $exists->id;
        } else {
            $action = 'create';
        }
        
        // Create or update resource
        $response = $modx->runProcessor('resource/' . $action, $data);
        return ['res' => $response, 'action' => $action];
    }
    
    public function createGallery($data, $res_id)
    {
        $modx = $this->modx;
        $modx->error->reset();
        foreach ($data as $v) {
            if (empty($v)) {
                continue;
            }
            $image = $this->imgs_path . $v;
            if (file_exists($image)) {
                $response_gal = $modx->runProcessor(
                    'gallery/upload',
                    array('id' => $res_id, 'name' => $v, 'file' => $image),
                    array('processors_path' => MODX_CORE_PATH . 'components/minishop2/processors/mgr/')
                );
                if ($response_gal->isError()) {
                    $err = $response_gal->getAllErrors();
                    //$modx->log(1, "Error on gallery/upload img: $image: \n" . print_r($err, 1));
                }
                unlink($image);
            }
        }
        
        return true;
    }

    public function createOptions($data, $res_id, $res_parent, $category_opts='')
    {
        $modx = $this->modx;
        $modx->error->reset();
        foreach ($data as $option) {
            if (!empty($option['title']) && $option['title'] != '') {
                if(!empty($category_opts)) $option['group'] = $category_opts;
                if(!empty($option['group']) && $option['group'] != ''){
                    $cat_id = 0;
                    if(!$cat = $modx->getObject('modCategory', array('category' => $option['group']))){
                        $cat = $modx->newObject('modCategory');
                        $cat->set('parent', 0);
                        $cat->set('category', $option['group']);
                        $cat->set('rank', 0);
                        if($cat->save()){
                            $cat_id = $cat->get('id');
                        }
                    } else {
                        $cat_id = $cat->get('id');
                    }
                }

                $key = $this->translit($option['title']);
                if (!$opt = $modx->getObject('msOption', array('key' => $key))) {
                    $opt = $modx->newObject('msOption');
                    $opt->set('key', $key);
                    $opt->set('caption', $option['title']);
                    $opt->set('type', 'textfield');
                    $opt->set('description', '');
                    $opt->set('measure_unit', '');
                    $opt->set('category', $cat_id);
                    if (!$opt->save()) {
                        $modx->log(1, "Can not save option: " . $option['title']);
                        continue;
                    }
                }

                if (!$cat_opt = $modx->getObject('msCategoryOption', array('option_id' => $opt->get('id'), 'category_id' => $res_parent))) {
                    $cat_opt = $modx->newObject('msCategoryOption');
                    $cat_opt->set('option_id', $opt->get('id'));
                    $cat_opt->set('category_id', $res_parent);
                    $cat_opt->set('active', 1);
                    $cat_opt->set('value', '');
                    if (!$cat_opt->save()) {
                        $modx->log(1, "Can not save category option: " . $option['title'] . " product id: " . $res_id . " product parent: " . $res_parent);
                    }
                }

                if ($res_opt = $modx->getObject('msProductOption', array('product_id' => $res_id, 'key' => $key))) {
                    $q = $modx->newQuery('msProductOption');
                    $q->command('UPDATE');
                    $q->where(array('product_id' => $res_id, 'key' => $key));
                    $q->set(array('value' => '"' . $option['value'] . '"'));
                    $q->prepare();
                    $q->stmt->execute();
                }else {
                    $table = $modx->getTableName('msProductOption');
                    $val = $option['value'];
                    if (!is_int($option['value'])) {
                        $val = '"' . $option['value'] . '"';
                    } 
                    $sql = "INSERT INTO {$table} (`product_id`,`key`,`value`) VALUES ({$res_id}, \"{$key}\", {$val});";
                    $stmt = $modx->prepare($sql);
                    $stmt->execute();
                }
            }
        }
        
        return true;
    }

    public function translit($value)
    {
        $converter = array(
            'а' => 'a',    'б' => 'b',    'в' => 'v',    'г' => 'g',    'д' => 'd',
            'е' => 'e',    'ё' => 'e',    'ж' => 'zh',   'з' => 'z',    'и' => 'i',
            'й' => 'y',    'к' => 'k',    'л' => 'l',    'м' => 'm',    'н' => 'n',
            'о' => 'o',    'п' => 'p',    'р' => 'r',    'с' => 's',    'т' => 't',
            'у' => 'u',    'ф' => 'f',    'х' => 'h',    'ц' => 'c',    'ч' => 'ch',
            'ш' => 'sh',   'щ' => 'sch',  'ь' => '',     'ы' => 'y',    'ъ' => '',
            'э' => 'e',    'ю' => 'yu',   'я' => 'ya',
        );

        $value = mb_strtolower($value);
        $value = strtr($value, $converter);
        $value = mb_ereg_replace('[^-0-9a-z]', '_', $value);
        $value = mb_ereg_replace('[-]+', '_', $value);
        $value = trim($value, '-');

        return $value;
    }
    
    
}



$csv_file = MODX_ASSETS_PATH . 'import/results_mebel_v_kazani.csv';
$imgs_path = pathinfo($csv_file, PATHINFO_DIRNAME) . '/images/';
$pid = 0;
$published = 1;
$category_opts = '';

if (empty($csv_file) || !file_exists($csv_file) || ($handle = fopen($csv_file, "r")) === FALSE) {
    die('Укажите существующий корректный csv файл с товарами!');
}

if ($pid == 0 || !$modx->getObject('modResource', $pid)) {
    die('Укажите ID существующего родительского ресурса!');
}


$start_offset = 0;
$time_out = 0 * 1000;
$step = 5;
if(isset($_SESSION['import']) && $_SESSION['import'] != '') {
    $offset = $_SESSION['import'];
} else {
    $offset = $start_offset;
    $_SESSION['import_time_start'] = microtime(true);
    $_SESSION['import_created'] = 0;
    $_SESSION['import_updated'] = 0;
    $_SESSION['import_errors'] = 0;
}

$file = file($csv_file);
$total = count($file);
$creator = new ImportGoods($imgs_path, $pid, $modx);

$curent_rows = 0;
while (($csv = fgetcsv($handle, null, ";")) !== FALSE && $curent_rows <= ($offset + $step)) {
    $curent_rows += 1;
    if($curent_rows < $offset || $csv[0] == 'id') continue;
    $pagetitle = htmlspecialchars(substr(strip_tags($csv[2]), 0, 150));
    if (empty($pagetitle)) continue;
    /*
       0 item_id,
       1 item_crumbs, - json !!!
       2 item_title,
       3 item_brand,
       4 item_price,
       5 item_sizes,
       6 item_params,
       7 item_desc,
       8 item_images,
    */
    $parent_id = $pid;
    if(!empty($csv[1]) && $csv[1] != ''){
        /* Cоздадим категории */
        $parent_id = $creator->createCategory($csv[1]);
    }

    $vendor_id = '';
    if(!empty($csv[3]) && $csv[3] != ''){
        /* Cоздадим производителя*/
        $vendor_id = $creator->createVendor($csv[3]);
    }
    $sizes = explode('||', $csv[5]);
    $product_data = array(
        'class_key' => 'msProduct',
        'context_key' => 'web',
        'parent' => $parent_id,
        'template' => $modx->getOption('ms2_template_product_default', null, 1),
        'published' => $published,
        'article' => md5($csv[0]),
        'pagetitle' => $pagetitle,
        'longtitle' => '',
        'vendor' => $vendor_id,
        'price' => $csv[4],
        'content' => str_replace('‘', '', $csv[7]),
        'size' => $sizes,
        'alias' => $csv[0],
    );    
    
    /* Cоздадим товар*/
    $response = $creator->createResource($product_data);
    if ($response['res']->isError()) {
        $_SESSION['import_errors'] += 1;
        $modx->log(1, "Error: \n" . print_r($response['res']->getAllErrors(), 1));
        continue;
    } else {
        if($response['action'] == 'update') $_SESSION['import_updated'] += 1;
        else $_SESSION['import_created'] += 1;
    }
    
    
    $resource = $response['res']->getObject();
    $res_parent = $resource['parent'];
    if(empty($res_parent) || $res_parent == 0){
        if($res = $modx->getObject('modResource', $resource['id'])){
            $res_parent = $res->get('parent');
        } else {
            $res_parent = 1;
        }
    }
    
    /* Добавим фото */
    $gallery = explode('||', $csv[8]);
    if(!empty($csv[8]) && $csv[8] != '' && is_array($gallery) && count($gallery) > 0){
        $creator->createGallery($gallery, $resource['id']);
    }
    
    /* Добавим опции */
    if(!empty($csv[6]) && $csv[6] != ''){
        $options = json_decode($csv[6], 1);
        if(json_last_error() != JSON_ERROR_NONE){
            $modx->log(1, "Error on json_decode product article: " . $csv[0]);
        } elseif(is_array($options) && count($options) > 0) {
            $creator->createOptions($options, $resource['id'], $res_parent, $category_opts);
        }
    }
    usleep($time_out);
}


$_SESSION['import'] = $offset + $step;
if ($_SESSION['import'] >= $total) {
    $sucsess = 100;
    $_SESSION['Console']['completed'] = true;
    echo '<p>Всего обработано ' . $total . ' строк!</p><p>Добавлено новых товаров: ' . $_SESSION['import_created'] . '</p><p>Обновлено товаров: ' . $_SESSION['import_updated'] . '</p><p>Строк с ошибками: ' . $_SESSION['import_errors'] . '</p>';
    unset($_SESSION['import']);
    unset($_SESSION['import_time_start']);
    unset($_SESSION['import_created']);
    unset($_SESSION['import_updated']);
    unset($_SESSION['import_errors']);
    echo '<p>' . date('Y-m-d H:i:s') . '</p>';
    die('Finish!!!');
} else {
    $sucsess = round($_SESSION['import'] / $total, 2) * 100;
    $_SESSION['Console']['completed'] = false;
}
for ($i = 0; $i <= 100; $i++) {
    if ($i <= $sucsess) {
        print '=';
    } else {
        print '_';
    }
}
$current = $_SESSION['import'] ?
    $_SESSION['import'] : ($sucsess == 100 ? $total : 0);
$time_script = round((microtime(true) - $_SESSION['import_time_start']) / 60, 0);
print "\n";
print $sucsess . '% (' . $current . ')' . "\n\n" . 'Время выполнения скрипта: ' . $time_script . ' мин';