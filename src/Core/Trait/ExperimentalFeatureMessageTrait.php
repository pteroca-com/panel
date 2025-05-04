<?php

namespace App\Core\Trait;

trait ExperimentalFeatureMessageTrait
{
    private function getExperimentalFeatureMessage(): string
    {
        return sprintf(
            '<br><span class="text-warning"><i class="fa fa-triangle-exclamation"></i> %s</span>',
            $this->translator->trans('pteroca.system.experimental_feature'),
        );
    }
}
