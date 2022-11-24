<?php

declare(strict_types=1);

namespace Eva\DependencyInjection;

class Definition
{
    public function __construct(
        protected string $class,
        protected array $arguments = [],
        protected array $calls = [],
    ) {}

    public function getClass(): string
    {
        return $this->class;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getCalls(): array
    {
        return $this->calls;
    }

    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    public function setCalls(array $calls): void
    {
        $this->calls = $calls;
    }
}
