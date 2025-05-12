<?php

namespace App\Core\Entity\Panel;

use App\Core\Trait\PaymentEntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'payment')]
#[ORM\Entity(repositoryClass: "App\Core\Repository\PaymentRepository")]
#[ORM\HasLifecycleCallbacks]
class UserPayment
{
    use PaymentEntityTrait;

    public function getAmountWithCurrency(): string
    {
        return sprintf('%0.2f %s', $this->getAmount(), strtoupper($this->getCurrency()));
    }

    public function getLastUpdate(): \DateTimeInterface
    {
        return $this->getUpdatedAt() ?? $this->getCreatedAt();
    }
}
