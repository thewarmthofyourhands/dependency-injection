<?php

declare(strict_types=1);

namespace Eva\DependencyInjection;

interface ContainerConfiguratorInterface
{
    public function import(string $path, string $type = null): void;
    public function importPackage(string $path, string $type = null): void;
    public function getParameters(): array;
    public function getPackages(): array;
    public function getServices(): array;
    public function getServiceProviders(): array;
}
