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
    /**
     * @var Account
     */
    private $account;


    public function __construct($id = 0)
    {
        parent::__construct(ApiClient::instance(), $id);
        $this->account = Account::instance();
    }


    protected function getApiClient()
    {
        return ApiClient::instance();
    }

    public function update()
    {
        $this['last_modified'] = time();

        parent::update();
    }


    public function getCustomFieldByName($fieldName)
    {
        if (empty($this['custom_fields'])) {
            return null;
        }

        $key = array_search($fieldName, array_column($this['custom_fields'], 'name'));

        if ($key === false) {
            return null;
        }

        $values = $this['custom_fields'][$key]['values'];

        return count($values) == 1 ? $values[0] : $values;
    }


    public function setCustomFieldByName($fieldName, $value, $enum = '')
    {
        if (!$fieldInfo = $this->getCustomFieldInfoByName($fieldName)) {
            return false;
        }

        if (!$values = $this->getValues($fieldInfo, $value, $enum)) {
            return false;
        }

        if ($key = array_search($fieldInfo['id'], array_column($this['custom_fields'], 'id'))) {
            $this['custom_fields'][$key]['values'] = $values;
        } else {
            $this['custom_fields'][] = ['id' => $fieldInfo['id'], 'values' => $values];
        }

        return true;
    }


    private function getCustomFieldInfoByName($fieldName)
    {
        $customFields = $this->account->getCustomFields($this->type);
        // TODO: проверку на дубли имен
        $key = array_search($fieldName, array_column($customFields, 'name'));

        return $key ? $customFields[$key] : null;
    }


    private function getValues($fieldInfo, $value, $enum = '')
    {
        $values = [];
        switch ($fieldInfo['field_type']) {
            case Field::TYPE_CHECKBOX:
                $values[] = [
                    "value" => (int)boolval($value)
                ];
                break;

            case Field::TYPE_SELECT:
                $enumId = array_search($value, $fieldInfo['enums']);

                if ($enumId) {
                    $values[] = [
                        'value' => $enumId
                    ];
                }
                break;

            case Field::TYPE_MULTISELECT:
                if (is_array($value)) {
                    foreach ($value as $val) {
                        $enumId = array_search($val, $fieldInfo['enums']);

                        if ($enumId) {
                            $values[] = $enumId;
                        }
                    }
                }
                break;

            case Field::TYPE_MULTITEXT:
                if (is_array($value)) {
                    foreach ($value as $key => $val) {
                        $enumId = array_search($key, $fieldInfo['enums']);

                        if ($enumId) {
                            $values[] = [
                                'value' => $val,
                                'enum' => $enumId
                            ];
                        }
                    }
                } else {
                    if ($enum && $enumId = array_search($enum, $fieldInfo['enums'])) {
                        $values[] = [
                            'value' => $value,
                            'enum' => $enumId
                        ];
                    } elseif (!$enum && (Field::STANDARD_PHONE == $fieldInfo['name'] || Field::STANDARD_EMAIL == $fieldInfo['name'])) {
                        // для стандартных полей Телефон и Эмейл можно не передавать enum, по умолчанию ставим WORK
                        $values[] = [
                            'value' => $value,
                            'enum' => Field::ENUM_WORK
                        ];
                    }
                }
                break;

            default:
                // TODO: сделать обработку остальных типов полей отличных от текста (Юр. лицо, Адрес и т.п.)
                $values[] = [
                    'value' => $value
                ];
        }

        return $values;
    }
}
