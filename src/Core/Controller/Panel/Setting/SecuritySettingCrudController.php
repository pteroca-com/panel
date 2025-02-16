<?php

namespace App\Core\Controller\Panel\Setting;

use App\Core\Enum\SettingContextEnum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class SecuritySettingCrudController extends AbstractSettingCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        $this->context = SettingContextEnum::SECURITY;

        return parent::configureCrud($crud);
    }
}
