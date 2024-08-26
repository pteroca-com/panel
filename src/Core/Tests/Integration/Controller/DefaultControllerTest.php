<?php

namespace App\Core\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndexRedirectsToLogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertResponseRedirects($client->getContainer()->get('router')->generate('app_login'));

        $client->followRedirect();

        $this->assertSelectorExists('input[id="username"]');
    }
}
