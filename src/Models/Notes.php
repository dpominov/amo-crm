<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 17.05.16
 */

namespace AmoCrm\Models;

/**
 * Class Notes
 * @package AmoCrm\Models
 *
 * @property int $element_id id контакта или сделки
 * @property int $element_type тип привязываемого елемента
 * @property int $note_type тип примечания
 * @property string $text текст события
 */
class Notes extends BaseModel
{
    const ELEMENT_TYPE_CONTACT = 1;
    const ELEMENT_TYPE_LEAD = 2;
    const ELEMENT_TYPE_COMPANY = 3;
    const ELEMENT_TYPE_TASK = 4;
    const ELEMENT_TYPE_CUSTOMER = 12;

    const TYPE_DEAL_CREATED = 1;
    const TYPE_CONTACT_CREATED = 2;
    const TYPE_DEAL_STATUS_CHANGED = 3;
    const TYPE_COMMON = 4; // Обычное примечание
    const TYPE_ATTACHMENT = 5;
    const TYPE_CALL = 6;
    const TYPE_EMAIL_MESSAGE = 7;
    const TYPE_EMAIL_ATTACHMENT = 8;
    const TYPE_EXTERNAL_ATTACH = 9;
    const TYPE_CALL_IN = 10;
    const TYPE_CALL_OUT = 11;
    const TYPE_COMPANY_CREATED = 12;
    const TYPE_TASK_RESULT = 13;
    const TYPE_CHAT = 17;
    const TYPE_MAX_SYSTEM = 99;
    const TYPE_DROPBOX = 101;
    const TYPE_SMS_IN = 102;
    const TYPE_SMS_OUT = 103;

    // путь по которому доступны для скачивания файлы примечания
    const DOWNLOAD_PATH = 'download/';

    protected $type = 'notes';


    public function __construct(int $id = 0)
    {
        $this['note_type'] = self::TYPE_COMMON;

        parent::__construct($id);
    }


    /**
     * Получение файла прикрепленного к примечанию
     *
     * @return mixed|null
     * @throws \AmoCrm\Exception
     */
    public function getAttachmentFile()
    {
        if (!$this['attachment']) {
            return null;
        }

        return $this->getApiClient()->getFileByCurl(self::DOWNLOAD_PATH . $this['attachment']);
    }
}
