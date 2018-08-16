<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 16.08.18
 */

namespace AmoCrm\Models;

use AmoCrm\Exception;

/**
 * Class Task Работа с задачами.
 *
 * Тип задачи можно задавать через имя типа, например:
 * $task['task_type'] = 'Звонок' или $task['task_type'] = 'Ваш тип задачи'
 * но также принимаются ID типа:
 * $task['task_type'] = 1
 *
 * Примечание: тип 'Звонок' в интерфейсе Амо называется 'Связаться с клиентом', но в задаче надо укзать именно 'Звонок'
 *
 * @package AmoCrm\Models
 *
 * @property int $task_type Тип задачи
 * @property int $element_id id контакта или сделки
 * @property int $element_type тип привязываемого елемента
 * @property int $complete_till_at Дата, до которой необходимо завершить задачу. timestamp
 * @property string $text текст задачи
 * @property string $created_at Дата создания данной задачи (необязательный параметр) timestamp
 * @property string $updated_at Дата последнего изменения данной задачи (необязательный параметр) timestamp
 * @property int $responsible_user_id Уникальный идентификатор ответственного пользователя
 * @property bool $is_completed Задача завершена или нет
 * @property int $created_by Уникальный идентификатор создателя задачи
 */
class Task extends BaseModel
{
    const ELEMENT_TYPE_CONTACT = 1;
    const ELEMENT_TYPE_LEAD = 2;
    const ELEMENT_TYPE_COMPANY = 3;
    const ELEMENT_TYPE_CUSTOMER = 12;

    protected $type = 'tasks';


    /**
     * @param $offset
     * @param $value
     * @throws Exception
     */
    public function offsetSet($offset, $value)
    {
        if ($offset == 'task_type') {
            $value = $this->getTaskTypeId($value);
        }

        parent::offsetSet($offset, $value);
    }


    /**
     * @param $offset
     * @return mixed|null
     * @throws Exception
     */
    public function offsetGet($offset)
    {
        $value = parent::offsetGet($offset);

        if ($offset == 'task_type') {
            $value = $this->getTaskTypeName($value);
        }

        return $value;
    }


    /**
     * @param $name
     * @return mixed
     * @throws Exception
     */
    private function getTaskTypeId($name)
    {
        if (!$taskTypes = $this->getAccount()->getTaskTypes()) {
            throw new Exception('В информации аккаунта нет типов задач!');
        }

        foreach ($taskTypes as $type) {
            if ($type['name'] == $name) {
                return $type['id'];
            }
        }

        return $name;
    }


    /**
     * @param $taskId
     * @return mixed
     * @throws Exception
     */
    private function getTaskTypeName($taskId)
    {
        if (!$taskTypes = $this->getAccount()->getTaskTypes()) {
            throw new Exception('В информации аккаунта нет типов задач!');
        }

        return $taskTypes[$taskId]['name'] ?? $taskId;
    }
}
