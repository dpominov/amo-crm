<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 16.05.16
 */

namespace AmoCrm\Models;

class Contacts extends BaseModel
{
    protected $type = 'contacts';

    public $name;
    public $phone;
    public $email;
    public $company_name;
    public $position;
    public $web;
    public $im;

    public $linked_leads_id = [];


    protected function getData()
    {
        $data = [
            'name' => $this->name,
            'linked_leads_id' => $this->linked_leads_id,
            'company_name' => $this->company_name,
        ];

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
            ['linked_leads_id' => array_merge($this->linked_leads_id, $leadsId)]
        );
    }


    public static function loadByPhone($phone)
    {
        if (!$phone) {

            return false;
        }

        $contact = new Contacts();
        $searchRes = $contact->api->getEntities($contact->type, ['query' => $phone]);

        if (empty($searchRes[0])) {

            return false;
        } else {
            $contact->data = $searchRes[0];
            $contact->id = isset($contact->data['id']) ? $contact->data['id'] : 0;
        }

        return $contact;
    }


    public function setLinkedLeadsId($leadsId)
    {
        $this->linked_leads_id = is_array($leadsId) ? $leadsId : [$leadsId];
    }


    public function getRelationLeads()
    {
        return $this->api->getEntities($this->type, ['contacts_link' => [$this->id]], 'links');
    }
}
