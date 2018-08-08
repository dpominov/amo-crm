<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 07.08.18
 */

require('vendor/autoload.php');

$config = require('config.php');

\AmoCrm\ApiClient::setConfig($config);
(new \AmoCrm\ListenerWorker())->run();
