<?php

namespace App\Core\Enum;

enum LogActionEnum: string
{
   case LOGIN = 'login';

   case LOGOUT = 'logout';

   case CREATE_PAYMENT = 'create_payment';

   case BOUGHT_BALANCE = 'bought_balance';

   case BOUGHT_SERVER = 'bought_server';

   case RENEW_SERVER = 'renew_server';

   case ENTITY_ADD = 'entity_add';

   case ENTITY_EDIT = 'entity_edit';

   case ENTITY_DELETE = 'entity_delete';

   case USER_REGISTERED = 'user_registered';

   case USER_VERIFY_EMAIL = 'user_verify_email';
}
