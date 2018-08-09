<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 17.05.16
 */

namespace AmoCrm\Models;

/**
 * Class Leads
 * @package AmoCrm\Models
 *
 * @property string $name
 * @property int $status_id
 * @property int $sale
 */
class Leads extends BaseModel
{
    protected $type = 'leads';
}
