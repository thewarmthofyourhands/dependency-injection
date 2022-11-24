<?php

declare(strict_types=1);

namespace Eva\DependencyInjection\ParameterBag;

class ParameterBag implements ParameterBagInterface
{
    protected array $parameters = [];

    public function __construct(array $parameters = [])
    {
        $this->add($parameters);
    }

    public function clear(): void
    {
        $this->parameters = [];
    }

    public function add(array $parameters): void
    {
        foreach ($parameters as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function all(): array
    {
        return $this->parameters;
    }

    public function get(string $name): array|bool|string|int|float|\UnitEnum|null
    {
        $parameters = $this->parameters;
        if (true === isset($parameters[$name])) {
            return $parameters[$name];
        }

        $result = $parameters[$name] ?? false;

        if (false === $result) {
            $findParam = explode('.', $name);
            $result = (static function (array $findParam) use ($parameters) {
                foreach ($findParam as $item) {
                    $parameters = $parameters[$item];
                }
                return $parameters;
            })($findParam);
        }

        return $result;
    }

    public function remove(string $name): void
    {
        unset($this->parameters[$name]);
    }

    public function set(string $name, \UnitEnum|float|int|bool|array|string|null $value): void
    {
        $this->parameters[$name] = $value;
    }

    public function has(string $name): bool
    {
        return isset($this->parameters[$name]);
    }


    public function resolve(): void
    {
        foreach ($this->parameters as $name => $value) {
            if (null !== $this->parameters[$name]) {
                $this->resolveValue($this->parameters[$name]);
            }
        }
    }

    protected function resolveValue(null|int|bool|float|string|array &$parameter)
    {
        $parameters = $this->parameters;

        if (is_string($parameter)) {
            $result = preg_replace_callback('/%%|%([^%\s]+)%/', function ($matches) use ($parameters) {
                $result = $parameters[$matches[1]] ?? false;

                if (false === $result) {
                    $findParam = explode('.', $matches[1]);
                    $result = (function (array $findParam) use ($parameters) {
                        foreach ($findParam as $item) {
                            $parameters = $parameters[$item];
                        }
                        return $parameters;
                    })($findParam);
                }

                $this->resolveValue($result);

                return $result;
            }, $parameter);

            $parameter = $result;
        }

        if (is_array($parameter)) {
            foreach ($parameter as &$value) {
                $this->resolveValue($value);
            }
        }
    }
}
