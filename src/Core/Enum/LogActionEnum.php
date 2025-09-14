<?php

namespace App\Core\Enum;

enum LogActionEnum
{
   case LOGIN;
   case LOGOUT;
   case CREATE_PAYMENT;
   case BOUGHT_BALANCE;
   case BOUGHT_SERVER;
   case RENEW_SERVER;
   case ENTITY_ADD;
   case ENTITY_EDIT;
   case ENTITY_DELETE;
   case ENTITY_RESTORE;
   case USER_REGISTERED;
   case USER_VERIFY_EMAIL;
   case VOUCHER_REDEEMED;
}
