<?php

namespace App\Core\Tests\Unit\Service\Mailer;

use App\Core\Enum\SettingEnum;
use App\Core\Service\Mailer\MailerService;
use App\Core\Service\SettingService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
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
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $requestStack = $this->createMock(RequestStack::class);
        $defaultLogoPath = '/path/to/default/logo.png';

        $settingsService->method('getSetting')
            ->will($this->returnValueMap([
                [SettingEnum::EMAIL_SMTP_SERVER->value, 'smtp.example.com'],
                [SettingEnum::EMAIL_SMTP_PORT->value, '587'],
                [SettingEnum::EMAIL_SMTP_USERNAME->value, 'user@example.com'],
                [SettingEnum::EMAIL_SMTP_PASSWORD->value, 'password'],
                [SettingEnum::EMAIL_SMTP_FROM->value, 'no-reply@example.com'],
                [SettingEnum::LOGO->value, 'logo.png'],
            ]));

        $twig->method('render')
            ->with('email/template.html.twig', $this->anything())
            ->willReturn('<html>Email Content</html>');

        // Mock event dispatcher to return the event as-is
        $eventDispatcher->method('dispatch')
            ->willReturnArgument(0);

        // Mock request stack to return null (no current request in test)
        $requestStack->method('getCurrentRequest')
            ->willReturn(null);

        $mailerService = new MailerService(
            $twig,
            $settingsService,
            $defaultLogoPath,
            $eventDispatcher,
            $requestStack
        );

        $reflection = new \ReflectionClass($mailerService);
        $property = $reflection->getProperty('mailer');
        $property->setAccessible(true);
        $property->setValue($mailerService, $mailer);
        $property = $reflection->getProperty('from');
        $property->setAccessible(true);
        $property->setValue($mailerService, 'no-reply@example.com');
        $property = $reflection->getProperty('logo');
        $property->setAccessible(true);
        $property->setValue($mailerService, $defaultLogoPath);


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
