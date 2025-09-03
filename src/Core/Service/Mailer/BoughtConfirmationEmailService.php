<?php

namespace App\Core\Service\Mailer;

use App\Core\Contract\UserInterface;
use App\Core\Entity\Product;
use App\Core\Entity\Server;
use App\Core\Exception\Email\ProductPriceNotFoundException;
use App\Core\Message\SendEmailMessage;
use App\Core\Service\Email\EmailContextBuilderService;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BoughtConfirmationEmailService
{
    public function __construct(
        private readonly EmailContextBuilderService $emailContextBuilder,
        private readonly TranslatorInterface $translator,
        private readonly MessageBusInterface $messageBus,
    ) {}

    public function sendBoughtConfirmationEmail(
        UserInterface $user,
        Server $server,
        Product $product,
        int $priceId,
        string $pterodactylAccountUsername,
    ): void {
        $price = $product->findPriceById($priceId);

        if ($price === null) {
            throw new ProductPriceNotFoundException($priceId, $product->getId());
        }

        $context = $this->emailContextBuilder->buildPurchaseContext(
            $user,
            $server,
            $product,
            $price,
            $pterodactylAccountUsername
        );

        $emailMessage = new SendEmailMessage(
            $user->getEmail(),
            $this->translator->trans('pteroca.email.store.subject'),
            'email/purchased_product.html.twig',
            $context->toArray()
        );

        $this->messageBus->dispatch($emailMessage);
    }

    public function sendRenewConfirmationEmail(
        UserInterface $user,
        Server $server,
        string $pterodactylAccountUsername
    ): void {
        $serverProduct = $server->getServerProduct();
        $selectedPrice = $serverProduct->getSelectedPrice();

        if ($selectedPrice === null) {
            throw new ProductPriceNotFoundException(0, $serverProduct->getId());
        }

        $context = $this->emailContextBuilder->buildRenewalContext(
            $user,
            $server,
            $serverProduct,
            $selectedPrice,
            $pterodactylAccountUsername
        );

        $emailMessage = new SendEmailMessage(
            $user->getEmail(),
            $this->translator->trans('pteroca.email.renew.subject'),
            'email/renew_product.html.twig',
            $context->toArray()
        );

        $this->messageBus->dispatch($emailMessage);
    }
}
