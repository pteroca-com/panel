<?php

namespace App\Core\Service\Email;

use App\Core\Contract\UserInterface;
use App\Core\Entity\EmailLog;
use App\Core\Entity\Server;
use App\Core\Enum\EmailTypeEnum;
use App\Core\Repository\EmailLogRepository;

readonly class EmailNotificationService
{
    public function __construct(
        private EmailLogRepository $emailLogRepository,
    ) {}


    public function logEmailSent(
        UserInterface $user,
        EmailTypeEnum $emailType,
        ?Server $server = null,
        ?string $subject = null,
        ?array $metadata = null
    ): void {
        $emailLog = (new EmailLog())
            ->setUser($user)
            ->setEmailType($emailType)
            ->setEmailAddress($user->getEmail())
            ->setServer($server)
            ->setSubject($subject)
            ->setMetadata($metadata);

        $this->emailLogRepository->save($emailLog);
    }

    public function getLastEmailByType(UserInterface $user, EmailTypeEnum $emailType): ?EmailLog
    {
        return $this->emailLogRepository->findLastByUserAndType($user, $emailType);
    }
}
