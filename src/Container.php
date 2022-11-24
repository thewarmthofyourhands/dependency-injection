<?php

declare(strict_types=1);

namespace Eva\DependencyInjection;

use Eva\DependencyInjection\ParameterBag\ParameterBag;
use Eva\DependencyInjection\ParameterBag\ParameterBagInterface;

class Container implements ContainerInterface
{
    protected ParameterBagInterface $parameterBag;
    protected array $services = [];
    protected array $definitions = [];
    protected array $aliases = [];

    public function __construct(ParameterBagInterface $parameterBag = null)
    {
        $this->parameterBag = $parameterBag ?? new ParameterBag();
    }

    public function getAll(): array
    {
        return [$this->services, $this->aliases];
    }

    public function getParameterBag(): ParameterBagInterface
    {
        return $this->parameterBag;
    }

    public function getParameter(string $name): array|bool|string|int|float|\UnitEnum|null
    {
        return $this->parameterBag->get($name);
    }

    public function hasParameter(string $name): bool
    {
        return $this->parameterBag->has($name);
    }

    public function setParameter(string $name, array|bool|string|int|float|\UnitEnum|null $value): void
    {
        $this->parameterBag->set($name, $value);
    }

    public function setAliases(array $aliases): void
    {
        $this->aliases = $aliases + $this->aliases;
    }

    public function setDefinitions(array $definitions): void
    {
        $this->definitions = $definitions + $this->definitions;
    }

    public function compile(): void
    {
        $this->parameterBag->resolve();
    }

    public function get(string $id): mixed
    {
        return $this->services[$id]
            ?? $this->services[$id = $this->aliases[$id] ?? $id]
            ?? ([$this, 'make'])($id);
    }

    public function set(string $id, null|object $service): void
    {
        if (isset($this->services[$id])) {
            throw new \Exception(sprintf('The "%s" service is already initialized, you cannot replace it.', $id));
        }

        $this->services[$id] = $service;
    }

    public function has(string $id): bool
    {
        if (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        if (isset($this->services[$id])) {
            return true;
        }

        return false;
    }

    public function make(string $id): null|object
    {
        /* @var Definition $definition */
        $definition = $this->definitions[$this->aliases[$id] ?? $id] ?? null;

        if (null === $definition) {
            return null;
        }

        $serviceClass = $definition->getClass();
        $ref = new \ReflectionClass($serviceClass);
        $constructParameters = $ref->getConstructor()?->getParameters() ?? [];
        $args = [];

        foreach ($constructParameters as $constructParameter) {
            if (isset($definition->getArguments()[$constructParameter->getName()])) {
                $argId = trim($definition->getArguments()[$constructParameter->getName()], '%');

                if ($parameter = $this->getParameter($argId)) {
                    $args[] = $parameter;
                } else {
                    $args[] = $this->get($argId);
                }

                continue;
            }

            $argType = $constructParameter->getType()->getName();
            $serviceId = $this->aliases[$argType] ?? null;

            if ($arg = $this->get($serviceId ?? $argType)) {
                $args[] = $arg;
            }
        }

        $serviceObject = new $serviceClass(...$args);
        $this->set($serviceClass, $serviceObject);

        foreach ($definition->getCalls() as $call) {
            $args = [];

            if (is_array($call)) {
                $method = key($call);
                $argId = trim(current($call), '%');

                if ($parameter = $this->getParameter($argId)) {
                    $args[] = $parameter;
                } else {
                    $args[] = $this->get($argId);
                }
            } else {
                $method = $call;
                $callsArgs = $ref->getMethod($method)?->getParameters();

                foreach ($callsArgs as $callsArg) {
                    $args[] = $this->get($callsArg->getType()->getName());
                }
            }

            $serviceObject->{$method}(...$args);
        }

        return $serviceObject;
    }
}
