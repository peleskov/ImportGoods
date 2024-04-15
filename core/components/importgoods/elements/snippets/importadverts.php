<?php
define('MODX_API_MODE', true);
require_once($_SERVER['DOCUMENT_ROOT'] . '/index.php');
$modx->initialize('web');
if ($modx->getService('error', 'error.modError')) {
    $modx->error->reset();
}
session_start();


class ImportAdverts
{
    private $pid;
    private $modx;
    private $imgs_path;
    private $phpThumb;

    public function __construct($imgs_path, $pid, $modx)
    {
        $this->modx = &$modx;
        $this->pid = $pid;
        $this->imgs_path = $imgs_path;
        $this->phpThumb = $this->modx->getService('modphpthumb', 'modPhpThumb', $this->modx->getOption('core_path') . 'model/phpthumb/', array());
    }


    public function createUser($data)
    {
        $modx = $this->modx;
        $modx->error->reset();
        $name = trim($data['fullname']);
        $email = trim($data['email']);
        if (
            empty($name) ||
            empty($email) ||
            $name == '' ||
            $email == '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            return false;
        }
        $images = explode('||', $data['images']);

        if (!$user = $modx->getObject('modUser', array('username' => $email))) {
            $user = $modx->newObject('modUser');
            $user->set('username', $email);
            $user->set('password', $this->_generatePassword(8));
            $user->set('active', true);
        }
        if (!$profile = $user->getOne('Profile')) {
            $profile = $modx->newObject('modUserProfile');
        }

        $profile->set('email', $email);
        $profile->set('fullname', $name);
        $profile->set('mobilephone', $data['phone']);

        $user->addOne($profile);

        if (!$user->save()) {
            return false;
        } else {
            $member = $modx->newObject('modUserGroupMember');
            $member->set('user_group', $data['group_id']);
            $member->set('member', $user->get('id'));
            $member->set('role', $data['role_id']);
            $member->save();

            if (!empty($images[0])) {
                $this->_createAvatar($user, $images[0]);
            }
        }

        return $user->get('id');
    }
    
    function _generatePassword($length = 12)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
    
    public function createAdvert($data)
    {
        $AdvertBoard = $this->modx->getService('AdvertBoard', 'AdvertBoard', $this->modx->getOption('core_path') . 'components/advertboard/model/');
        $modx = $this->modx;
        $modx->error->reset();

        $images = explode('||', $data['images']);
        unset($images[0]);
        unset($data['images']);
        $action = 'update';
        if (!$advert = $modx->getObject('Advert', ['hash' => $data['hash']])) {
            $advert = $modx->newObject('Advert');
            $action = 'create';
        }
        foreach ($data as $key => $val) {
            $advert->set($key, $val);
        }
        $advert->set('created', time());
        $advert->set('updated', time());
        $advert->set('status', 0);
        if (!$advert->save()) {
            return false;
        }

        if (!empty($images)) {
            $this->_createImages($advert, $images);
        }
        return $action;
    }

    public function _createAvatar($user, $image)
    {
        $phpThumb = $this->phpThumb;
        $profile = $user->getOne('Profile');
        $avatars_path = $this->modx->getOption('assets_path') . 'images/avatars/';
        $path_img = $this->imgs_path . $image;
        $extPhoto = mb_strtolower(pathinfo($path_img, PATHINFO_EXTENSION));
        if (
            file_exists($path_img) && in_array($extPhoto, array('jpg', 'png', 'jpeg'))
        ) {
            array_map("unlink", glob($avatars_path . 'user' . $user->get('id') . '_*'));
            $newPhotoName = 'user' . $user->get('id') . '_' . rand() . '.' . $extPhoto;
            $newPhotoPath = $avatars_path . $newPhotoName;
            if (copy($path_img, $newPhotoPath)) {
                $phpThumb->setSourceFilename($newPhotoPath);
                $phpThumb->setParameter('w', 220);
                $phpThumb->setParameter('h', 220);
                $phpThumb->setParameter('zc', 1);
                $phpThumb->setParameter('q', 80);
                $newPhoto = str_replace($this->modx->getOption('base_path'), '', $newPhotoPath);
                if ($phpThumb->GenerateThumbnail()) {
                    if ($phpThumb->RenderToFile($newPhotoPath)) {
                        $profile->set('photo', $newPhoto);
                        $profile->save();
                    }
                }
            }
        }
        return;
    }

    public function _createImages($advert, $images)
    {
        $phpThumb = $this->phpThumb;
        $images_path = $this->modx->getOption('assets_path') . 'images/adverts/';
        $new_imgs = [];
        foreach ($images as $image) {
            $path_img = $this->imgs_path . $image;
            $extPhoto = mb_strtolower(pathinfo($path_img, PATHINFO_EXTENSION));
            if (
                file_exists($path_img) && in_array($extPhoto, array('jpg', 'png', 'jpeg'))
            ) {
                $newPhotoPath = $images_path . $image;
                if (copy($path_img, $newPhotoPath)) {
                    $phpThumb->setSourceFilename($newPhotoPath);
                    $phpThumb->setParameter('w', 260);
                    $phpThumb->setParameter('h', 180);
                    $phpThumb->setParameter('zc', 1);
                    $phpThumb->setParameter('q', 80);
                    $newPhoto = str_replace($this->modx->getOption('base_path'), '', $newPhotoPath);
                    if ($phpThumb->GenerateThumbnail()) if ($phpThumb->RenderToFile($newPhotoPath)) {
                        $new_imgs[] = array(
                            'name' => $image,
                            'name_original' => $image,
                            'path' => $newPhoto,
                            'url' => $newPhoto,
                            'full_url' => $this->modx->getOption('site_url') . $newPhoto,
                            'size' => 0,
                            'extension' => $extPhoto
                        );
                    }
                }
            }
        }
        if ($new_imgs) {
            $advert->set('images', json_encode($new_imgs, true));
            $advert->save();
        }

        return;
    }
}



$csv_file = $modx->getOption('assets_path') . 'import/kartografiya/kartografiya_results_weblancer_net1.csv';
$imgs_path = pathinfo($csv_file, PATHINFO_DIRNAME) . '/images/';
$pid = 9;
$published = 1;
$thumbW = 260;
$thumbH = 180;
$thumbZC = 1;
$thumbQ = 80;

if (empty($csv_file) || !file_exists($csv_file) || ($handle = fopen($csv_file, "r")) === FALSE) {
    die('Укажите существующий корректный csv файл с товарами!');
}

if ($pid == 0 || !$modx->getObject('modResource', $pid)) {
    die('Укажите ID существующего родительского ресурса!');
}

$start_offset = 0;
$time_out = 1 * 1000;
$step = 5;
if (isset($_SESSION['import_limit']) && $_SESSION['import_limit'] > 0) {
    $offset = $_SESSION['import_limit'];
} else {
    $offset = $start_offset;
    $_SESSION['import_time_start'] = microtime(true);
    $_SESSION['import_created'] = 0;
    $_SESSION['import_updated'] = 0;
    $_SESSION['import_errors'] = 0;
}

$file = file($csv_file);
$total = count($file);
$creator = new ImportAdverts($imgs_path, $pid, $modx);

$rows = 0;
$limit = $offset + $step;
while (($csv = fgetcsv($handle, null, ";")) !== FALSE && $rows < $limit) {
    $_SESSION['requests'] += 1;

    if ($rows < $offset || $csv[0] == 'id') {
        $rows += 1;
        continue;
    } else $rows += 1;
    /*
       0 item_id,
       1 item_name,
       2 item_price,
       3 item_email,
       4 item_phone,
       5 item_desc,
       6 item_images,
    */
    if (!$user_id  = $creator->createUser([
        'fullname' => $csv[1],
        'email' => $csv[3],
        'phone' => $csv[4],
        'group_id' => 2,
        'role_id' => 1,
        'images' => $csv[6],
    ])) continue;

    $title = $csv[1];
    $pattern = '/^<p><b>(.*?)<\/b><\/p>/';
    if (!empty($csv[5] && $csv[5] != '')) {
        if (preg_match($pattern, $csv[5], $matches)) {
            $title =  $matches[1];
        }
    }

    if ($advert = $creator->createAdvert([
        'user_id' => $user_id,
        'hash' => $csv[0],
        'title' => $title,
        'price' => $csv[2],
        'old_price' => 0,
        'pid' => $pid,
        'content' => $csv[5],
        'images' => $csv[6],
    ])) {
        switch ($advert) {
            case 'update':
                $_SESSION['import_updated'] += 1;
                break;
            case 'create':
                $_SESSION['import_created'] += 1;
                break;
        }
    } else $_SESSION['import_errors'] += 1;


    usleep($time_out);
}


$_SESSION['import_limit'] = $limit;
if ($_SESSION['import_limit'] >= $total) {
    $sucsess = 100;
    $empty = $total - $_SESSION['import_created'] - $_SESSION['import_updated'] - $_SESSION['import_errors'];
    echo '<p>Всего обработано ' . $total . ' строк!</p><p>Добавлено новых: ' . $_SESSION['import_created'] . '</p><p>Обновлено: ' . $_SESSION['import_updated'] . '</p><p>Строк с ошибками: ' . $_SESSION['import_errors'] . '</p><p>Пустых строк: ' . $empty . '</p>';
    unset($_SESSION['import_limit']);
    unset($_SESSION['import_time_start']);
    unset($_SESSION['import_created']);
    unset($_SESSION['import_updated']);
    unset($_SESSION['import_errors']);
    echo '<p>' . date('Y-m-d H:i:s') . '</p>';
    session_write_close();
    die('Finish!!!');
} else {
    $sucsess = round($_SESSION['import_limit'] / $total, 2) * 100;
    header("Refresh:0");
}
$br = (php_sapi_name() === "cli") ? "\n" : "<br>";
for ($i = 0; $i <= 100; $i++) {
    if ($i <= $sucsess) {
        print '=';
    } else {
        print '_';
    }
}
$current = $_SESSION['import_limit'] ?
    $_SESSION['import_limit'] : ($sucsess == 100 ? $total : 0);
$time_script = round((microtime(true) - $_SESSION['import_time_start']) / 60, 0);
print $br;
print $sucsess . '% (' . $current . ')' . $br . 'Время выполнения скрипта: ' . $time_script . ' мин' . $br;
