<?php

namespace App\Core\Enum;

enum ServerLogActionEnum
{
   case CHANGE_DETAILS;
   case REINSTALL;
   case CHANGE_STARTUP_OPTION;
   case CHANGE_STARTUP_VARIABLE;
   case TOGGLE_AUTO_RENEWAL;
   case CREATE_BACKUP;
   case DELETE_BACKUP;
   case DOWNLOAD_BACKUP;
}
