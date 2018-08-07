<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 17.05.16
 */

namespace AmoCrm\Models;

use AmoCrm\AmoCrmApi;

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../AmoCrmApi.php');

class BaseModel
{
    protected $type;

    public $id = 0;
    public $responsible_user_id;
    public $custom_fields;

    protected $data;

    /**
     * @var array массив кастомных полей вида ['ИМЯ_ПОЛЯ' => 'ЗНАЧЕНИЕ']
     */
    public $customFields = [];


    public function __construct($id = 0)
    {
        $this->api = new AmoCrmApi(AMOCRM_SUBDOMAIN, AMOCRM_USER_LOGIN, AMOCRM_USER_HASH);
        $this->responsible_user_id = AMOCRM_RESPONSIBLE;

        $this->load($id);
    }


    public function load($id)
    {
        if (!$id) {
            return;
        }

        if (!$this->type) {
            // TODO: генерирование ошибки или запись ошибки
            return;
        }

        $this->data = $this->api->getEntities($this->type, ['id' => $id])[0];
        if (!$this->data) {

            return;
        }

        $this->id = $id;

        $this->responsible_user_id = isset($this->data['responsible_user_id']) ? $this->data['responsible_user_id']
            : $this->responsible_user_id;

        $this->custom_fields = isset($this->data['custom_fields']) ? $this->data['custom_fields'] : $this->custom_fields;
    }


    public function get($params = [])
    {
        return $this->api->getEntities($this->type, $params);
    }


    /**
     * Добавляем
     */
    public function add()
    {
        $res = $this->getIdsFromResponse($this->api->addEntities($this->type, [$this->getData()]));
        $this->id = isset($res[0]) ? $res[0] : 0;

        return $this->id;
    }


    public function update($data = [])
    {
        $data = $data ? $data : $this->data;

        $data['id'] = $this->id;
        $data['last_modified'] = time();

        return $this->api->updateEntities($this->type, [$data]);
    }


    private function getIdsFromResponse($response)
    {
        $ids = array();
        foreach ($response as $v) {
            if (is_array($v)) {
                $ids[] = $v['id'];
            }
        }

        return $ids;
    }


    protected function getData()
    {
        $data['responsible_user_id'] = $this->responsible_user_id ? $this->responsible_user_id
            : defined(AMOCRM_RESPONSIBLE) ? AMOCRM_RESPONSIBLE : 0;

        foreach ($this->customFields as $fieldName => $value) {
            if ($id = $this->api->getFieldId($fieldName)) {
                $data['custom_fields'][] = array(
                    'id' => $id,
                    'values' => [['value' => $value]]
                );
            }
        }

        return $data;
    }


    public function getType()
    {
        return $this->type;
    }
}
