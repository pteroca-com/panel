<?php

namespace App\Core\Trait;

trait ProductCrudControllerTrait
{
    private function getNodesChoices(): array
    {
        try {
            $nodes = $this->pterodactylService->getApi()->nodes->all()->toArray();
            $locations = [];
            $choices = [];

            foreach ($nodes as $node) {
                if (empty($locations[$node->location_id])) {
                    $locations[$node->location_id] = $this->pterodactylService
                        ->getApi()
                        ->locations
                        ->get($node->location_id);
                }
                $choices[$locations[$node->location_id]->short][$node->name] = $node->id;
            }

            return $choices;
        } catch (\Exception $exception) {
            $this->flashMessages[] = $exception->getMessage();
            return [];
        }
    }

    private function getNestsChoices(): array
    {
        try {
            $nests = $this->pterodactylService->getApi()->nests->all()->toArray();
            $choices = [];

            foreach ($nests as $nest) {
                $choices[$nest->name] = $nest->id;
            }

            return $choices;
        } catch (\Exception $exception) {
            $this->flashMessages[] = $exception->getMessage();
            return [];
        }
    }

    private function getEggsChoices(array $nests): array
    {
        try {
            $choices = [];
            foreach ($nests as $nestId) {
                $eggs = $this->pterodactylService->getApi()->nest_eggs->all($nestId)->toArray();
                foreach ($eggs as $egg) {
                    $choices[$egg->name] = $egg->id;
                }
            }

            return $choices;
        } catch (\Exception $exception) {
            $this->flashMessages[] = $exception->getMessage();
            return [];
        }
    }

    private function getEggsConfigurationFromRequest(): array
    {
        $requestData = $this->requestStack->getCurrentRequest()->request->all();
        return $requestData['eggs_configuration'] ?? [];
    }
}
