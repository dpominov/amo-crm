<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 17.05.16
 */

namespace AmoCrm\Models;

use AmoCrm\ApiClient;
use AmoCrm\Exception;
use BaseDataModel\BaseDataModel;

class BaseModel extends BaseDataModel
{
    public function __construct($id = 0)
    {
        parent::__construct(ApiClient::instance(), $id);
    }


    protected function getApiClient()
    {
        return ApiClient::instance();
    }


    protected function getAccount()
    {
        return Account::instance();
    }


    public function update()
    {
        $this['last_modified'] = $this['updated_at'] = time();

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

        $result = [];
        if (is_array($values)) {
            foreach ($values as $value) {
                if (!empty($value['enum'])) {
                    // TODO конвертировать id enum в имя
                    $result[$value['enum']] = $value['value'];
                } else {
                    $result[] = $value['value'];
                }
            }
        }

        return $result ?: $values;
    }


    public function setCustomFieldByName($fieldName, $value, $enum = '')
    {
        if (!$fieldInfo = $this->getCustomFieldInfoByName($fieldName)) {
            return false;
        }

        if (!$values = $this->getValues($fieldInfo, $value, $enum)) {
            return false;
        }

        if (!empty($this['custom_fields']) && $key = array_search($fieldInfo['id'], array_column($this['custom_fields'], 'id'))) {
            $this['custom_fields'][$key]['values'] = $values;
        } else {
            // так как $this не настоящий массив, а ArrayAccess то приходится делать обновление через переменную
            $customFields = $this['custom_fields'];
            $customFields[] = ['id' => $fieldInfo['id'], 'values' => $values];
            $this['custom_fields'] = $customFields;
        }

        return true;
    }


    /**
     * @param $fieldName
     * @return array|null
     * @throws Exception
     */
    private function getCustomFieldInfoByName($fieldName)
    {
        $customFields = $this->getAccount()->getCustomFields($this->type);

        $index = false;
        foreach ($customFields as $key => $field) {
            if ($field['name'] === $fieldName) {
                if ($index !== false) {
                    throw new Exception("Обнаружены дополнительные поля с совпадающим именем '$fieldName' ID=$index и ID=$key");
                }

                $index = $key;
            }
        }

        return $index !== false ? $customFields[$index] : null;
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
