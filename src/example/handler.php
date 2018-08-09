<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 10.05.15
 */

require(__DIR__ . '/../../vendor/autoload.php');
$config = require('config.php');

\AmoCrm\ApiClient::setConfig($config);

$lead = new \AmoCrm\Models\Leads(3499865);

var_dump($lead->getData());

