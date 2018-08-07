<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 10.05.15
 */
require_once(__DIR__ . '/../config.php');
require_once('AmoOrderFreeController.php');

error_reporting(-1);

$mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$product_name = $mysqli->real_escape_string(trim($_POST['product_name']));

if (!$mysqli->connect_errno) {
    $res = $mysqli->query("
        SELECT image, pc.category_id, company
        FROM oc_product AS p
        LEFT JOIN oc_product_description AS pd USING (product_id)
        LEFT JOIN oc_product_to_category AS pc ON p.product_id = pc.product_id AND pc.category_id !=0
        LEFT JOIN oc_upload_to_category AS uc ON uc.category_id = pc.category_id
        WHERE pd.name='$product_name';
    ");

    $row = $res->fetch_assoc();
}
$mysqli->close();


$amo = new \AmoCrm\AmoOrderFreeController();

if (!empty($row)) {
    $amo->company = $row['company'];
    $amo->photo = 'http://shop.laoo.ru/image/' . $row['image'];
    $amo->category = 'http://shop.laoo.ru/index.php?route=product/category&path=' . $row['category_id'];
}

$amo->name = trim($_POST['customer_name']);
$amo->phone = trim($_POST['customer_phone']);
$amo->email = trim($_POST['customer_email']);

$amo->photoSample = trim($_POST['product_image']);

$amo->run();
