<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 08.05.16
 */

namespace AmoCrm;

/**
 * Class AmoCrm Класс работы с API AmoCrm. В основном создание контакта и сопутствующих задач, примечаний.
 * Создан на основе кривоватых примеров из документации. Требует дальнейшего рефакторинга и разделения работы с API и
 * конкретной задачи добавления контакта.
 */
class AmoCrmController
{
    /**
     * @var int время на которое нужно сдвинуть задачу относительно времени создания. В секундах
     */
    public $timeShiftForTask = 0;

    private $adminEmail;
    private $responsibleId;
    private $leadDefaultStatusId;

    // Контакт
    public $company = '';
    public $position = 'Покупатель';
    public $name = '';
    public $phone = '';
    public $email = '';

    // Сделка
    protected $note;
    protected $leadStatus;
    protected $leadFunnel;


    private $errorMessage = '';

    /**
     * @var int ID созданного контакта
     */
    private $contactId = 0;

    /**
     * @var array массив id сделок привязываемых к контакту.
     */
    private $leadsId = [];
    /**
     * @var bool включение тестового режима
     */
    public $test = false;

    /**
     * @var AmoCrmApi
     */
    private $api;


    public function run()
    {
        if (!$this->isValidForm()) {
            $this->display($this->errorMessage);
            exit;
        }

        try {

        } catch (Exception $e) {
            // уведомляем админа об ошибках
            Tools::sendEmail(AMOCRM_ADMIN_EMAIL, 'info@shop.laoo.ru', 'ERROR: ', $e->getMessage());
            $this->display($e->getMessage());
        }
    }


    /**
     * Если не указаны обязательные поля - уведомляем
     */
    private function isValidForm()
    {
        if (!$this->phone) {
            $this->errorMessage .= "Не заполнен телефон<br />\n";
        }

        if ($this->errorMessage) {
            $this->errorMessage .= "<a href=\"javascript:history.back()\">Вернитесь назад</a> и заполните поле(я)\n<br>";
        }

        return !$this->errorMessage;
    }


    public function display($content)
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        echo $content;
    }


    /**
     * Проверим есть ли такой контакт.
     * @param string|array $queries строка(и) для поиска контакта, обычно это телефон и/или эмейл
     * @return int ID существующего контакта
     */
    public function getContactId($queries)
    {
        $contact = $this->api->getContactByContacts($queries);

        $this->contactId = isset($response[0]['id']) ? $response[0]['id'] : 0;
        $this->responsibleId = isset($contact['responsible_user_id']) ? $contact['responsible_user_id']
            : $this->responsibleId;

        return $this->contactId;
    }


    /**
     * Создаем задачу к контакту или сделке
     * @param string $text текст задачи.
     * @param int $elementId id контакта или сделки
     * @param int $elementType Тип привязываемого елемента
     */
    public function addTasks($text, $elementId, $elementType = self::ELEMENT_TYPE_CONTACT)
    {
        $tasks = [
            [
                'element_id' => $elementId,
                'element_type' => $elementType,
                'task_type' => 3,
                'text' => $text,
                'responsible_user_id' => $this->responsibleId,
                'complete_till' => time() + $this->timeShiftForTask,
            ]
        ];


        $this->testEcho($this->getIdsFromResponse($this->api->addEntities('tasks', $tasks)), 'задач');
    }


    private function testEcho($ids, $msg)
    {
        if ($this->test) {
            echo "ID добавленных $msg: " . (is_array($ids) ? implode(', ', $ids) : $ids) . "<br>\n";
        }
    }
}
