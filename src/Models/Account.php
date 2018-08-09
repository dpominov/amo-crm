<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 08.08.18
 */

namespace AmoCrm\Models;

use AmoCrm\ApiClient;
use AmoCrm\Exception;

// TODO: отказаться от BaseModel в пользу трейта реализующего ArrayAccess и убрать костылиь - подобие синглтона
class Account extends BaseModel
{
    protected $type = 'account';

    private static $instance = [];


    public function __construct()
    {
        $hash = ApiClient::instance()->getHash();
        if (empty(self::$instance[$hash])) {
            $data = ApiClient::instance()->getEntities($this->type, ['with' => 'custom_fields']);
            if (!$data) {
                throw new Exception('Не удалось получить информацию по аккаунту');
            }

            $this->setData($data);
        } else {
            // костыль - подобие синглтона, так как наследуем публичный конструктор
            throw new Exception('Используйте метод instance() для получения экземпляра');
        }
    }


    public static function instance()
    {
        $hash = ApiClient::instance()->getHash();
        if (empty(self::$instance[$hash])) {
            self::$instance[$hash] = new self();
        }

        return self::$instance[$hash];
    }


    public function getCustomFields($type = '')
    {
        return $type ? $this['_embedded']['custom_fields'][$type] : $this['_embedded']['custom_fields'];
    }
}
