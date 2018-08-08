<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 17.05.16
 */

namespace AmoCrm\Models;

class Notes extends BaseModel
{
    const ELEMENT_TYPE_CONTACT = 1;
    const ELEMENT_TYPE_LEAD = 2;

    protected $type = 'notes';

    /** @var int id контакта или сделки */
    public $element_id;

    /** @var  int тип привязываемого елемента */
    public $element_type;

    /** @var int тип примечания */
    public $note_type = 4; /* Обычное примечание */

    /** @var string текст задачи */
    public $text;


    public function getData()
    {
        $data = [
            'element_id' => $this->element_id,
            'element_type' => $this->element_type,
            'note_type' => $this->note_type,
            'text' => $this->text,
        ];

        return array_merge($data, parent::getData());
    }
}
