<?php

$bundles = [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Vich\UploaderBundle\VichUploaderBundle::class => ['all' => true],
    Symfony\UX\TwigComponent\TwigComponentBundle::class => ['all' => true],
    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => ['all' => true],
    App\Core\CoreBundle::class => ['all' => true],
];

if ($_ENV['APP_ENV'] === 'dev') {
    $bundles[Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class] = ['dev' => true, 'test' => true];
}

return $bundles;
