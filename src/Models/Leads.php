<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 17.05.16
 */

namespace AmoCrm\Models;

class Leads extends BaseModel
{
    protected $type = 'leads';

    public $name;
    public $status_id;
    public $price;


    protected function getData()
    {
        $data = [
            'name' => $this->name,
            'status_id' => $this->status_id,
            'price' => $this->price,
        ];

        return array_merge($data, parent::getData());
    }
}
