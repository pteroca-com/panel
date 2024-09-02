<?php

namespace App\Core\Tests\Unit\Service\Mailer;

use App\Core\Enum\SettingEnum;
use App\Core\Service\Mailer\MailerService;
use App\Core\Service\SettingService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MailerServiceTest extends TestCase
{
    public function testSendEmail(): void
    {
        $twig = $this->createMock(Environment::class);
        $settingsService = $this->createMock(SettingService::class);
        $mailer = $this->createMock(MailerInterface::class);

        $settingsService->method('getSetting')
            ->will($this->returnValueMap([
                [SettingEnum::EMAIL_SMTP_SERVER->value, 'smtp.example.com'],
                [SettingEnum::EMAIL_SMTP_PORT->value, '587'],
                [SettingEnum::EMAIL_SMTP_USERNAME->value, 'user@example.com'],
                [SettingEnum::EMAIL_SMTP_PASSWORD->value, 'password'],
                [SettingEnum::LOGO->value, 'logo.png'],
            ]));

        $twig->method('render')
            ->with('email/template.html.twig', $this->anything())
            ->willReturn('<html>Email Content</html>');

        $mailerService = new MailerService($twig, $settingsService);

        $reflection = new \ReflectionClass($mailerService);
        $property = $reflection->getProperty('mailer');
        $property->setAccessible(true);
        $property->setValue($mailerService, $mailer);
        $property = $reflection->getProperty('from');
        $property->setAccessible(true);
        $property->setValue($mailerService, 'no-reply@example.com');


        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return $email->getFrom()[0]->getAddress() === 'no-reply@example.com'
                    && $email->getTo()[0]->getAddress() === 'test@example.com'
                    && $email->getSubject() === 'Test Subject'
                    && $email->getHtmlBody() === '<html>Email Content</html>';
            }));

        $mailerService->sendEmail(
            'test@example.com',
            'Test Subject',
            'email/template.html.twig',
            ['title' => 'Test Email']
        );
    }
}
