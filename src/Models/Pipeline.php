<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 18.05.16
 */

namespace AmoCrm\Models;

class Pipeline extends BaseModel
{
    protected $type = 'pipelines';


    public function get($id = null) {
        return $this->getApiClient()->getEntities($this->type);
    }
}
