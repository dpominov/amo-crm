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

    private $cookieFile = __DIR__ . '/cookie.txt';

    private $hash;

    public static $authCache = [];


    private function __construct()
    {
        $this->hash = self::$config['domain'] . self::$config['login'] . self::$config['hash'];
        $this->cookieFile = self::$config['cookieFile'] ?? $this->cookieFile;

        $this->auth();
    }


    /**
     * @return ApiClient
     * @throws Exception
     */
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


    /**
     * @param $config
     * @throws Exception
     */
    public static function setConfig($config)
    {
        if ($error = self::validateConfig($config)) {
            throw new Exception($error);
        }

        self::$config = $config;
    }


    /**
     * @param $config
     * @return string
     */
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
     *
     * TODO: переделать на curl_setopt_array, разделить get и post запросы
     *
     * @param $link
     * @param null $type
     * @param null $set
     * @return array|mixed
     * @throws Exception
     */
    private function runCurl($link, $type = null, $set = null, $modifiedSince = null)
    {
        $curl = curl_init(); #Сохраняем дескриптор сеанса cURL
        #Устанавливаем необходимые опции для сеанса cURL
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($curl, CURLOPT_URL, 'https://' . self::$config['domain'] . '.amocrm.ru/' . $link);

        $headers = [];
        if ('POST' == $type) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
                'USER_LOGIN' => self::$config['login'],
                'USER_HASH' => self::$config['hash'],
            ]));
        } elseif ('CUSTOMREQUEST' == $type) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($set));
            $headers[] = 'Content-Type: application/json';
        }

        if ($modifiedSince) {
            $dataTime = ((int)$modifiedSince ? '@' : '') . $modifiedSince;
            $headers[] = 'If-Modified-Since: ' . (new \DateTime($dataTime))->format(\DateTime::RFC1123);
        }

        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookieFile);
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
     * @throws Exception
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

            throw new Exception('Ошибка: "' . (isset($errors[$code]) ? $errors[$code] : 'Undescribed error') . "\n"
                . (isset($out['response']['error']) ? $out['response']['error'] : '') . "
			Link=$link
            type=$type
            data=" . print_r($set, true), $code);
        }
    }


    /**
     * авторизация пользователя
     *
     * TODO: добавить инвалидацию кеша через 15 минут
     *
     * @return mixed
     * @throws Exception
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
     *
     * @param string $type тип сущности
     * @param array $data
     * @param string $subType
     * @return array
     * @throws Exception
     */
    public function getEntities($type, $data = [], $subType = '', $modifiedSince = null)
    {
        $subTypeQuery = $subType ? "/$subType" : '';
        $queryParams = $data ? '?' . http_build_query($data) : '';
        $response = $this->runCurl("api/v2/{$type}{$subTypeQuery}" . $queryParams, null, null, $modifiedSince);

        return $type == 'account' ? $response : ($response['_embedded']['items'] ?? []);
    }


    /**
     * @deprecated
     *
     * @param string $type тип сущности
     * @param $data
     * @param string $subType
     * @return array
     * @throws Exception
     */
    public function getEntitiesOld($type, $data, $subType = 'list')
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
     * @throws Exception
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
     * @throws Exception
     */
    public function updateEntities($type, $entities)
    {
        $set['request'][$type]['update'] = $entities;
        $response = $this->runCurl("private/api/v2/json/$type/set", 'CUSTOMREQUEST', $set);

        return $response['response'][$type]['update'];
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


    /**
     * Получение файла по заданному урлу с куками авторизации
     *
     * @param string $link относительная ссылка на файл
     * @return mixed
     * @throws Exception
     */
    public function getFileByCurl($link)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, 'https://' . self::$config['domain'] . '.amocrm.ru/' . $link);

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        $out = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($code != 200 && $code != 204) {
            throw new Exception("Ошибка загрузки файла код: $code Link=$link");
        }

        return $out;
    }
}
