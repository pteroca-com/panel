<?php

namespace App\Core\Controller\Panel\Setting;

use App\Core\Enum\SettingContextEnum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class PterodactylSettingCrudController extends AbstractSettingCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        $this->context = SettingContextEnum::PTERODACTYL;

        return parent::configureCrud($crud);
    }
}
