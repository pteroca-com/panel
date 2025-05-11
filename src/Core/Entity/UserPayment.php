<?php

namespace App\Core\Entity;

use App\Core\Trait\PaymentEntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'payment')]
#[ORM\Entity(repositoryClass: "App\Core\Repository\PaymentRepository")]
#[ORM\HasLifecycleCallbacks]
class UserPayment
{
    use PaymentEntityTrait;
}
