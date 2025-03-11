<?php

namespace App\Core\Service\System\WebConfigurator;

use App\Core\DTO\Action\Result\ConfiguratorVerificationResult;
use Exception;
use Symfony\Component\Mailer\Transport;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailConnectionVerificationService extends AbstractVerificationService
{
    protected const REQUIRED_FIELDS = [
        'email_smtp_username',
        'email_smtp_password',
        'email_smtp_server',
        'email_smtp_port',
    ];

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    public function validateConnection(array $data): ConfiguratorVerificationResult
    {
        if (!$this->validateRequiredFields($data)) {
            return new ConfiguratorVerificationResult(
                false,
                $this->translator->trans('pteroca.first_configuration.errors.missing_fields'),
            );
        }

        try {
            $dsn = sprintf(
                'smtp://%s:%s@%s:%s',
                urlencode($data['email_smtp_username']),
                urlencode($data['email_smtp_password']),
                $data['email_smtp_server'],
                $data['email_smtp_port']
            );

            $transport = Transport::fromDsn($dsn);
            $transport->start();

            return new ConfiguratorVerificationResult(true);
        } catch (Exception) {
            return new ConfiguratorVerificationResult(
                false,
                $this->translator->trans('pteroca.first_configuration.errors.smtp_error'),
            );
        }
    }
}