<?php

namespace App\Core\Service\System\WebConfigurator;

use App\Core\DTO\Action\Result\ConfiguratorVerificationResult;
use Exception;
use Symfony\Component\Mailer\Transport;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class EmailConnectionVerificationService
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    public function validateConnection(
        string $emailSmtpUsername,
        string $emailSmtpPassword,
        string $emailSmtpServer,
        string $emailSmtpPort,
    ): ConfiguratorVerificationResult
    {
        try {
            $dsn = sprintf(
                'smtp://%s:%s@%s:%s',
                urlencode($emailSmtpUsername),
                urlencode($emailSmtpPassword),
                $emailSmtpServer,
                $emailSmtpPort,
            );

            $transport = Transport::fromDsn($dsn);
            $transport->start();

            return new ConfiguratorVerificationResult(
                true,
                $this->translator->trans('pteroca.first_configuration.messages.email_smtp_connection_success'),
            );
        } catch (Exception) {
            return new ConfiguratorVerificationResult(
                false,
                $this->translator->trans('pteroca.first_configuration.messages.smtp_error'),
            );
        }
    }
}