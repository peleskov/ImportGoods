<?php
/**/
/* For use only in Console */
/**/

/* Важно указать PARENT иначе удалит все рессурсы*/

$parent = 16;
$start_offset = 0;
$time_out = 0 * 1000;
$step = 10;
$remove_category = false;

if(isset($_SESSION['remover']) && $_SESSION['remover'] != '') {
    $offset = $_SESSION['remover'];
} else {
    $offset = $start_offset;
    $_SESSION['remover'] = $offset;
    $_SESSION['remover_time_start'] = microtime(true);
}


$parents = $modx->runSnippet('pdoResources', [
        'parents' => $parent,
        'depth' => 10,
        'limit' => 0,
        'where' => ['class_key' => 'msCategory'],
        'returnIds' => 1
    ]);
$pids = explode(',', $parents);
$pids[] = $parent;

$q = $modx->newQuery('msProduct');
$q->select('id');
if($remove_category){
    $q->where(['parent:IN' => $pids]);
} else {
    $q->where(['parent:IN' => $pids, 'class_key' => 'msProduct']);
}
$total = $modx->getCount('msProduct', $q);

$q->limit($step, $offset);
$resources = $modx->getIterator('msProduct', $q);
if($resources){
    foreach($resources as $res){
        $res->remove();
    }
}


$_SESSION['remover'] = $offset + $step;
if ($_SESSION['remover'] >= $total) {
    $sucsess = 100;
    $_SESSION['Console']['completed'] = true;
    unset($_SESSION['remover']);
    unset($_SESSION['remover_time_start']);
    echo '<p>' . date('Y-m-d H:i:s') . '</p>';
    die('Finish!!!');
} else {
    $sucsess = round($_SESSION['remover'] / $total, 2) * 100;
    $_SESSION['Console']['completed'] = false;
}
for ($i = 0; $i <= 100; $i++) {
    if ($i <= $sucsess) {
        print '=';
    } else {
        print '_';
    }
}
$current = $_SESSION['remover'] ?
    $_SESSION['remover'] : ($sucsess == 100 ? $total : 0);
$time_script = round((microtime(true) - $_SESSION['remover_time_start']) / 60, 0);
print "\n";
print $sucsess . '% (' . $current . ')' . "\n\n" . 'Время выполнения скрипта: ' . $time_script . ' мин';