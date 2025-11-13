<?php

namespace App\Core\Service\Mailer;

use App\Core\Enum\SettingEnum;
use App\Core\Service\SettingService;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MailerService implements MailerServiceInterface
{
    private MailerInterface $mailer;

    private string $from = '';

    public function __construct(
        private readonly Environment $twig,
        private readonly SettingService $settingsService,
        private readonly string $defaultLogoPath,
        private readonly string $projectDir,
    ) {}

    public function sendEmail(string $to, string $subject, string $template, array $context): void
    {
        if (empty($this->mailer)) {
            $this->setMailer();
        }

        if (empty($context['title'])) {
            $context['title'] = $subject;
        }

        $logoPath = $this->resolveLogoPath();

        $email = (new Email())
            ->from($this->from)
            ->to($to)
            ->subject($subject)
            ->html($this->twig->render($template, $context))
            ->attachFromPath($logoPath, 'logo.png');

        $this->mailer->send($email);
    }

    private function setMailer(): void
    {
        $smtpServer = $this->settingsService->getSetting(SettingEnum::EMAIL_SMTP_SERVER->value);
        $smtpPort = $this->settingsService->getSetting(SettingEnum::EMAIL_SMTP_PORT->value);
        $smtpUsername = $this->settingsService->getSetting(SettingEnum::EMAIL_SMTP_USERNAME->value);
        $smtpPassword = $this->settingsService->getSetting(SettingEnum::EMAIL_SMTP_PASSWORD->value);
        $this->from = $this->settingsService->getSetting(SettingEnum::EMAIL_SMTP_FROM->value);

        $dsn = sprintf('smtp://%s:%s@%s:%d', $smtpUsername, $smtpPassword, $smtpServer, $smtpPort);
        $transport = Transport::fromDsn($dsn);
        $this->mailer = new Mailer($transport);
    }

    private function resolveLogoPath(): string
    {
        $logoFilename = $this->settingsService->getSetting(SettingEnum::LOGO->value);

        if (!empty($logoFilename)) {
            $customLogoPath = sprintf(
                '%s/public/uploads/settings/%s',
                $this->projectDir,
                $logoFilename
            );

            if (is_file($customLogoPath)) {
                return $customLogoPath;
            }
        }

        return $this->defaultLogoPath;
    }
}
