<?php
$_lang['importgoods'] = 'Импорт товаров';
$_lang['importgoods_menu_desc'] = 'Импорт товаров из csv файла в каталог minishop2.';
$_lang['importgoods_import'] = 'Импорт';
$_lang['importgoods_import_intro_msg'] = '<ul style="list-style: disc;">
<li>Каждая строка должна содержать информацию об одном товаре;</li>
<li>Разделитель свойств товара - точка с запятой;</li>
<li>Разделитель размеров и картинок - две вертикальные линии;</li>
<li>Фотографии разместите в папке images, рядом с csv файлом;</li>
<li>Что бы перенести товары из одной категории в другую, просто загрузите еще раз тот же csv файл и укажите новый ID родительского ресурса;</li>
<li>Идентификатор уникальности товара article = md5(id);</li>
<li>Формат данных:<br>
id;Crumbs;Title;Brand;Price;Sizes;Params;Description;Images<br>
Пример: <i>good_1;Одежда||Мужская||Куртки;Пуховик короткий, молочный;Hugo Boss;35000;XS||S||M||L;Состав: кирпич, Стирка: руками;Пуховик из бумаги и картона;img_1.jpg||img_2.jpg||img_3.jpg;</i></li>
</ul>';
$_lang['importgoods_import_file'] = 'Укажите файл csv';
$_lang['importgoods_import_pid'] = 'Укажите ID родительского ресурса (Категории товаров)';
$_lang['importgoods_import_start'] = 'Импортировать';
$_lang['importgoods_import_err_csv'] = 'Укажите верный файл';
$_lang['importgoods_import_start'] = 'Начать импорт';
$_lang['importgoods_item_published'] = 'Публиковать';

$_lang['importgoods_import_err_form'] = 'Проверьте правильность заполнения полей формы и попробуйте еще раз!';
