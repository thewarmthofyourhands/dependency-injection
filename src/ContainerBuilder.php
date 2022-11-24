<?php

declare(strict_types=1);

namespace Eva\DependencyInjection;

use Psr\Container\ContainerInterface as PsrContainerInterface;

class ContainerBuilder implements ContainerBuilderInterface
{
    protected array $parameters = [];
    protected array $packages = [];
    protected array $services = [];
    protected array $serviceProviders = [];
    protected array $aliases = [];

    public function build(ContainerInterface $container): void
    {
        foreach ($this->parameters as $name => $value) {
            $container->setParameter($name, $value);
        }

        foreach ($this->packages as $name => $value) {
            $container->setParameter('package.' . $name, $value);
        }

        $container->compile();

        $container->setAliases($this->aliases);
        $container->setDefinitions($this->services);

        $container->setAliases([
            'container' => $container::class,
            ContainerInterface::class => $container::class,
            PsrContainerInterface::class => $container::class,
        ]);
        $container->set($container::class, $container);

        $container->setDefinitions($this->serviceProviders);

        foreach ($this->serviceProviders as $serviceProvider) {
            $container->make($serviceProvider->getClass());
        }
    }

    public function register(array $serviceConfig): void
    {
        if (isset($serviceConfig[1]['resource'])) {
            $resource = $serviceConfig[1]['resource'];
            $exclude = $serviceConfig[1]['exclude'] ?? [];

            if ([] !== $exclude) {
                $exclude = glob(getcwd() . $exclude, GLOB_BRACE);
            }

            $services = $this->getFiles($resource, $serviceConfig[0], $exclude);

            foreach ($services as $service) {
                $this->services[$service] = new Definition($service);
            }

            return;
        }

        if (is_array($serviceConfig[1])) {
            $class = $serviceConfig[1]['class'] ?? null;
            $arguments = $serviceConfig[1]['arguments'] ?? [];
            $calls = $serviceConfig[1]['calls'] ?? [];

            if (true === isset($serviceConfig[1]['parent'])) {
                $parentClass = $this->aliases[$serviceConfig[1]['parent']] ?? $serviceConfig[1]['parent'];
                $parentDefinitions = $this->services[$parentClass];
                $arguments = $parentDefinitions->getArguments() + $arguments;
                $calls = $parentDefinitions->getCalls() + $calls;
            }

            if (null !== $class) {
                $definition = new Definition(
                    $class,
                    $arguments,
                    $calls,
                );
            } else {
                $class = $this->aliases[$serviceConfig[0]] ?? $serviceConfig[0];
                $definition = $this->services[$class];
                $definition->setCalls($calls);
                $definition->setArguments($arguments);
                return;
            }
        } else {
            $definition = new Definition($serviceConfig[1]);
        }

        $this->services[$definition->getClass()] = $definition;

        if (is_string($serviceConfig[0])) {
            $aliases = [$serviceConfig[0]];
        } else {
            $aliases = $serviceConfig[0];
        }

        foreach ($aliases as $alias) {
            $this->aliases[$alias] = $definition->getClass();
        }
    }

    public function getFiles(string $path, string $namespace, array $exclude): array
    {
        $list = [];
        $recursiveDir = new \DirectoryIterator(getcwd() . $path);

        foreach ($recursiveDir as $item) {
            if ($item->isDir()) {
                if (false === in_array($item->getBasename(), ['.', '..'], true)) {
                    $recursiveDir = $this->getFiles(
                        $path . '/' . $item->getBasename(),
                        $namespace . '\\' . $item->getBasename(),
                        $exclude,
                    );
                    array_push($list, ...$recursiveDir);
                }
            } else {
                if ($item->getExtension() === 'php' && false === in_array($item->getRealPath(), $exclude, true)) {
                    $className = $item->getBasename('.php');
                    $class = $namespace . '\\' . $className;
                    $list[] = $class;
                }
            }
        }

        return $list;
    }

    public function addPackages(array $packages): void
    {
        $this->packages = $packages + $this->packages;
    }

    public function addParameters(array $parameters): void
    {
        $this->parameters = $parameters + $this->parameters;
    }

    public function addServiceProviders(array $serviceProviders): void
    {
        foreach ($serviceProviders as $serviceProvider) {
            $this->serviceProviders[$serviceProvider] = new Definition($serviceProvider);
        }
    }

    public function addServices(array $services): void
    {
        foreach ($services as $service) {
            $this->register($service);
        }
    }
}
