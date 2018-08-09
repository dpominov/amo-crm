<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 22.11.14
 */

namespace AmoCrm;

use BaseDataModel\BaseDataModelInterface;
use BaseDataModel\DataProviderInterface;

/**
 * Class AmoCrmApi
 */
class ApiClient implements DataProviderInterface
{
    private static $config;
    private static $instance;

    private $hash;

    /**
     * @var array для хранениея ID полей [Имя поля => id, ]
     */
    public static $fields = [];
    public static $authCache = [];


    private function __construct()
    {
        $this->hash = self::$config['domain'] . self::$config['login'] . self::$config['hash'];

        $this->auth();
        $this->loadField();
    }


    public static function instance()
    {
        if (!self::$instance) {
            if ($error = self::validateConfig(self::$config)) {
                throw new Exception($error);
            }

            self::$instance = new self();
        }

        return self::$instance;
    }


    public static function setConfig($config)
    {
        if ($error = self::validateConfig($config)) {
            throw new Exception($error);
        }

        self::$config = $config;
    }


    private static function validateConfig($config)
    {
        if (empty($config['domain'])) {
            return 'Domain required';
        }

        if (empty($config['login'])) {
            return 'Login required';
        }

        if (empty($config['hash'])) {
            return 'Api hash required';
        }

        return '';
    }


    public function add(BaseDataModelInterface $entity)
    {
        $res = $this->getIdsFromResponse($this->addEntities($entity->getType(), [$entity->getData()]));

        return $res[0] ?? 0;
    }


    public function update(BaseDataModelInterface $entity, $id)
    {
        $this->updateEntities($entity->getType(), [$entity->getData()]);
    }


    public function get(BaseDataModelInterface $entity, $id)
    {
        $res = $this->getEntities($entity->getType(), ['id' => $id]);

        return $res[0] ?? [];
    }


    public function delete(BaseDataModelInterface $entity, $id)
    {

    }

    public function getHash()
    {
        return $this->hash;
    }


    /**
     * Работа с Curl
     * @param $link
     * @param null $type
     * @param null $set
     * @return array|mixed
     * @throws Exception
     */
    private function runCurl($link, $type = null, $set = null)
    {
        $curl = curl_init(); #Сохраняем дескриптор сеанса cURL
        #Устанавливаем необходимые опции для сеанса cURL
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($curl, CURLOPT_URL, 'https://' . self::$config['domain'] . '.amocrm.ru/' . $link);

        if ('POST' == $type) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
                'USER_LOGIN' => self::$config['login'],
                'USER_HASH' => self::$config['hash'],
            ]));
        } elseif ('CUSTOMREQUEST' == $type) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($set));
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        $out = curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE); #Получим HTTP-код ответа сервера
        curl_close($curl); #Завершаем сеанс cURL

        $this->checkCurlResponse($code, $out, $link, $type, $set);

        // Данные получаем в формате JSON, поэтому, для получения читаемых данных, нам придётся перевести ответ в
        // формат, понятный PHP
        return $out ? json_decode($out, true) : $out;
    }


    /**
     * @param $code
     * @throws \Exception
     */
    private function checkCurlResponse($code, $out, $link, $type, $set)
    {
        $code = (int)$code;
        $errors = [
            301 => 'Moved permanently',
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable'
        ];

        #Если код ответа не равен 200 или 204 - выкидываем исключение
        if ($code != 200 && $code != 204) {
            $out = json_decode($out, true);

            throw new \Exception('Ошибка: "' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error') . "\n"
                . (isset($out['response']['error']) ? $out['response']['error'] : '') . "
			Link=$link
            type=$type
            data=" . print_r($set, true), $code);
        }
    }


    /**
     * авторизация пользователя
     * @return bool
     */
    public function auth()
    {
        if (empty(self::$authCache[$this->hash])) {
            $response = $this->runCurl('private/api/auth.php?type=json', 'POST');
            self::$authCache[$this->hash] = $response['response']['auth'] ?? false;
        }

        return self::$authCache[$this->hash];
    }


    /**
     * Получаем информацию об аккаунте
     */
    public function getAccountInfo()
    {
        return $this->runCurl('api/v2/account?with=custom_fields');
    }


    /**
     * @param string $query строка по которой искать контакт, обычно или телефон или эмейл
     * @return mixed
     */
    public function getContactByQuery($query)
    {
        $response = $this->runCurl('private/api/v2/json/contacts/list?query=' . $query);
        return isset($response['response']['contacts']) ? $response['response']['contacts'] : [];
    }


    /**
     * @param string $query строка по которой искать контакт, обычно или телефон или эмейл
     * @return mixed
     */
    public function getContact($id)
    {
        $response = $this->runCurl('private/api/v2/json/contacts/list?id=' . $id);
        return isset($response['response']['contacts'][0]) ? $response['response']['contacts'][0] : [];
    }


    /**
     *
     * @param string $type тип сущности
     * @param array $data
     * @param string $subType
     * @return array
     */
    public function getEntities($type, $data, $subType = 'list')
    {
        $response = $this->runCurl("private/api/v2/json/$type/$subType?" . http_build_query($data));

        $responseType = 'list' == $subType ? $type : $subType;
        return isset($response['response'][$responseType]) ? $response['response'][$responseType] : [];
    }


    /**
     * Добавляем сущность (контакт, сделку, задачу, событие)
     *
     * @param string $type тип сущности
     * @param array $entities данные (массив массивов)
     * @return mixed
     */
    public function addEntities($type, $entities)
    {
        $set['request'][$type]['add'] = $entities;
        $response = $this->runCurl("private/api/v2/json/$type/set", 'CUSTOMREQUEST', $set);

        return $response['response'][$type]['add'];
    }


    /**
     * Обновляем сущность (контакт, сделку, задачу, событие)
     *
     * @param string $type тип сущности
     * @param array $entities данные (массив массивов)
     * @return mixed
     */
    public function updateEntities($type, $entities)
    {
        $set['request'][$type]['update'] = $entities;
        $response = $this->runCurl("private/api/v2/json/$type/set", 'CUSTOMREQUEST', $set);

        return $response['response'][$type]['update'];
    }


    /**
     * Получаем информацию об полях
     * @return array полей вида [Имя поля => id, ]
     */
    public function getFields()
    {
        $account = $this->getAccountInfo();
        if (!isset($account['custom_fields']['contacts'])) {

            return [];
        }

        $fields = [];
        foreach ($account['custom_fields']['contacts'] as $field) {
            if (isset($field['id'])) {
                if (isset($field['code'])) {
                    $fields[$field['code']] = (int)$field['id'];
                } elseif (isset($field['name'])) {
                    $fields[$field['name']] = (int)$field['id'];
                }
            }
        }

        return $fields;
    }


    /**
     * Получение информации о контакте по его эмейлу или телефону
     * @param string|array $contacts телефон/ы и/или эмейл/ы по которым искать контакт
     * @return array информации о контакте
     */
    public function getContactByContacts($contacts)
    {
        if (!is_array($contacts)) {
            $contacts = array($contacts);
        }

        foreach ($contacts as $contact) {
            $response = $this->getContactByQuery($contact);

            if (isset($response[0])) {

                return $response[0];
            }
        }

        return [];
    }


    /**
     * Получаем информацию об аккаунте
     */
    private function loadField()
    {
        if (empty(self::$fields[$this->hash])) {
            self::$fields[$this->hash] = $this->getFields();

            if (!self::$fields[$this->hash]) {
                throw new \Exception('Невозможно получить ID полей');
            }
        }

        return self::$fields[$this->hash];
    }


    public function getFieldId($fieldName)
    {
        return isset(self::$fields[$this->hash][$fieldName]) ? self::$fields[$this->hash][$fieldName] : false;
    }


    private function getIdsFromResponse($response)
    {
        $ids = [];
        foreach ($response as $v) {
            if (is_array($v)) {
                $ids[] = $v['id'];
            }
        }

        return $ids;
    }
}
