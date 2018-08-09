<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 16.05.16
 */

namespace AmoCrm\Models;

/**
 * Class Contacts
 * @package AmoCrm\Models
 *
 * @property string $name
 * @property array $linked_leads_id
 * @property string $company_name
 */
class Contacts extends BaseModel
{
    protected $type = 'contacts';

    public $phone;
    public $email;
    public $position;
    public $web;
    public $im;


    public function getData()
    {
        $data = [];

        if ($id = $this->api->getFieldId('PHONE')) {
            $data['custom_fields'][] = [
                'id' => $id,
                'values' => [['value' => $this->phone, 'enum' => 'OTHER']]
            ];
        }

        if ($id = $this->api->getFieldId('EMAIL')) {
            $data['custom_fields'][] = [
                'id' => $id,
                'values' => [['value' => $this->email, 'enum' => 'WORK']]
            ];
        }

        if ($id = $this->api->getFieldId('POSITION')) {
            $data['custom_fields'][] = [
                'id' => $id,
                'values' => [['value' => $this->position]]
            ];
        }

        if ($id = $this->api->getFieldId('WEB')) {
            $data['custom_fields'][] = [
                'id' => $id,
                'values' => [['value' => $this->web]]
            ];
        }

        if ($id = $this->api->getFieldId('IM')) {
            $data['custom_fields'][] = [
                'id' => $id,
                'values' => [['value' => $this->im]]
            ];
        }

        return array_merge($data, parent::getData());
    }


    public function addLead($leadId)
    {
        $this->addLeads([$leadId]);
    }


    public function addLeads($leadsId)
    {
        if (!$leadsId) {

            return false;
        }

        return $this->update(
            ['linked_leads_id' => array_merge($this['linked_leads_id'], $leadsId)]
        );
    }


    public static function loadByPhone($phone)
    {
        if (!$phone) {

            return false;
        }

        $contact = new Contacts();
        $searchRes = $contact->getApiClient()->getEntities($contact->type, ['query' => $phone]);

        if (empty($searchRes[0])) {

            return false;
        } else {
            $contact->setData($searchRes[0]);
        }

        return $contact;
    }


    public function setLinkedLeadsId($leadsId)
    {
        $this->linked_leads_id = is_array($leadsId) ? $leadsId : [$leadsId];
    }


    public function getRelationLeads()
    {
        return $this->getApiClient()->getEntities($this->type, ['contacts_link' => [$this->id]], 'links');
    }
}
