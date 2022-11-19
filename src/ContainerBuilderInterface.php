<?php

declare(strict_types=1);

namespace Eva\DependencyInjection;

interface ContainerBuilderInterface
{
    public function build(ContainerInterface $container): void;
    public function addPackages(array $packages): void;
    public function addParameters(array $parameters): void;
    public function addServices(array $services): void;
    public function addServiceProviders(array $serviceProviders): void;
}
