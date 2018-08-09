<?php
/**
 * Author: Dmitrii Pominov ( DPominov@gmail.com )
 * Date: 09.08.18
 */

namespace AmoCrm\Models;

class Field extends BaseModel
{
    const TYPE_TEXT = 1;
    const TYPE_NUMERIC = 2;
    const TYPE_CHECKBOX = 3;
    const TYPE_SELECT = 4;
    const TYPE_MULTISELECT = 5;
    const TYPE_DATE = 6;
    const TYPE_URL = 7;
    const TYPE_MULTITEXT = 8;
    const TYPE_TEXTAREA = 9;
    const TYPE_RADIOBUTTON = 10;
    const TYPE_STREETADDRESS = 11;
    const TYPE_SMART_ADDRESS = 13;
    const TYPE_BIRTHDAY = 14;
    const TYPE_LEGAL_ENTITY = 15;
    const TYPE_ITEMS = 16;

    const STANDARD_PHONE = 'Телефон';
    const STANDARD_EMAIL = 'Email';
    const STANDARD_IM = 'Мгн. сообщения';

    const ENUM_WORK = 'WORK';
    const ENUM_WORKDD = 'WORKDD';
    const ENUM_MOB = 'MOB';
    const ENUM_FAX = 'FAX';
    const ENUM_HOME = 'HOME';
    const ENUM_OTHER = 'OTHER';
    const ENUM_PRIV = 'PRIV';
    const ENUM_SKYPE = 'SKYPE';
    const ENUM_ICQ = 'ICQ';
    const ENUM_JABBER = 'JABBER';
    const ENUM_GTALK = 'GTALK';
    const ENUM_MSN = 'MSN';

    protected $type = 'fields';
}
