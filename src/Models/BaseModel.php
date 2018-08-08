<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 17.05.16
 */

namespace AmoCrm\Models;

use AmoCrm\ApiClient;
use BaseDataModel\BaseDataModel;

class BaseModel extends BaseDataModel
{
    public $custom_fields;

    /**
     * @var array массив кастомных полей вида ['ИМЯ_ПОЛЯ' => 'ЗНАЧЕНИЕ']
     */
    public $customFields = [];


    public function __construct($id = 0)
    {
        parent::__construct(ApiClient::instance(), $id);
    }


    public function update()
    {
        $this['last_modified'] = time();

        parent::update();
    }


    public function getData()
    {
        foreach ($this->customFields as $fieldName => $value) {
            if ($id = $this->dataProvider->getFieldId($fieldName)) {
                $data['custom_fields'][] = [
                    'id' => $id,
                    'values' => [['value' => $value]]
                ];
            }
        }

        return $data;
    }


    public function setData(array $data)
    {
        parent::setData($data);

        $this->custom_fields = $this['custom_fields'] ?? $this->custom_fields;
    }
}
