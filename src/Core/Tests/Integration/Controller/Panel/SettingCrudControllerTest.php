<?php

namespace App\Core\Tests\Integration\Controller\Panel;

use App\Core\Controller\Panel\Setting\GeneralSettingCrudController;
use App\Core\Entity\Setting;
use App\Core\Entity\User;
use App\Core\Enum\SettingTypeEnum;
use App\Core\Tests\Integration\BaseTestCase;

class SettingCrudControllerTest extends BaseTestCase
{
    public function testAccessDeniedForNonAdminUser(): void
    {
        $user = $this->createTestUser(['ROLE_USER']);
        $this->client->loginUser($user);

        $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(\App\Core\Controller\Panel\Setting\GeneralSettingCrudController::class) . '&crudAction=new');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAccessGrantedForAdminUser(): void
    {
        $adminUser = $this->createTestUser(['ROLE_ADMIN']);
        $this->client->loginUser($adminUser);

        $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(GeneralSettingCrudController::class) . '&crudAction=new');
        $this->assertResponseIsSuccessful();
    }

    public function testCreateSetting(): void
    {
        $adminUser = $this->createTestUser(['ROLE_ADMIN']);
        $this->client->loginUser($adminUser);

        $crawler = $this->client->request('GET', '/panel?crudControllerFqcn=' . urlencode(\App\Core\Controller\Panel\Setting\GeneralSettingCrudController::class) . '&crudAction=new');
        $form = $crawler->selectButton('Add setting')->form([
            'Setting[name]' => 'test_setting',
            'Setting[type]' => SettingTypeEnum::TEXT->value,
            'Setting[value]' => 'Test Value',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/panel?crudAction=index&crudControllerFqcn=' . urlencode(\App\Core\Controller\Panel\Setting\GeneralSettingCrudController::class));

        $setting = $this->entityManager->getRepository(Setting::class)->findOneBy(['name' => 'test_setting']);
        $this->assertNotNull($setting);
        $this->assertEquals('test_setting', $setting->getName());
        $this->assertEquals('Test Value', $setting->getValue());
        $this->assertEquals(SettingTypeEnum::TEXT->value, $setting->getType());
    }

    public function testEditSetting(): void
    {
        $adminUser = $this->createTestUser(['ROLE_ADMIN']);
        $this->client->loginUser($adminUser);

        $setting = $this->createTestSetting();

        $crawler = $this->client->request('GET', '/panel?crudAction=edit&crudControllerFqcn=' . urlencode(\App\Core\Controller\Panel\Setting\GeneralSettingCrudController::class) . '&entityId=' . $setting->getId());
        $form = $crawler->selectButton('Save setting')->form([
            'Setting[value]' => 'Updated Value',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/panel?crudAction=index&crudControllerFqcn=' . urlencode(GeneralSettingCrudController::class) . '&entityId=' . $setting->getId());

        $updatedSetting = $this->entityManager->getRepository(Setting::class)->find($setting->getId());
        $this->assertEquals('Updated Value', $updatedSetting->getValue());
    }

    private function createTestUser(array $roles = ['ROLE_ADMIN'], string $email = 'admin@example.com'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword(
            $this->getContainer()->get('security.password_hasher')->hashPassword($user, 'password')
        );
        $user->setRoles($roles);
        $user->setName('Admin');
        $user->setSurname('User');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createTestSetting(): Setting
    {
        $setting = new Setting();
        $setting->setName('test_setting');
        $setting->setValue('Test Value');
        $setting->setType(SettingTypeEnum::TEXT->value);
        $this->entityManager->persist($setting);
        $this->entityManager->flush();

        return $setting;
    }
}
