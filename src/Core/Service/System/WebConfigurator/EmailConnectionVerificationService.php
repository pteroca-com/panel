<?php

namespace App\Core\Service\System\WebConfigurator;

use Exception;
use Symfony\Component\Mailer\Transport;

class EmailConnectionVerificationService extends AbstractVerificationService
{
    protected const REQUIRED_FIELDS = [
        'email_smtp_username',
        'email_smtp_password',
        'email_smtp_server',
        'email_smtp_port',
    ];

    public function validateConnection(array $data): bool
    {
        if (!$this->validateRequiredFields($data)) {
            return false;
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

            return true;
        } catch (Exception) {
            return false;
        }
    }
}