<?php

namespace Spatie\Health\Checks;

use Cron\CronExpression;
use Illuminate\Console\Scheduling\ManagesFrequencies;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Spatie\Health\Enums\Status;

abstract class Check
{
    use ManagesFrequencies;
    use Macroable;

    protected string $expression = '* * * * *';

    protected ?string $name = null;

    protected ?string $label = null;

    /**
     * @var bool|callable(): bool
     */
    protected mixed $shouldRun = true;

    public function __construct()
    {
    }

    public static function new(): static
    {
        $instance = app(static::class);

        $instance->everyMinute();

        return $instance;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        if ($this->label) {
            return $this->label;
        }

        $name = $this->getName();

        return Str::of($name)->snake()->replace('_', ' ')->title();
    }

    public function getName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        $baseName = class_basename(static::class);

        return Str::of($baseName)->beforeLast('Check');
    }

    public function shouldRun(): bool
    {
        $shouldRun = is_callable($this->shouldRun) ? ($this->shouldRun)() : $this->shouldRun;

        if (! $shouldRun) {
            return false;
        }

        $date = Date::now();

        return (new CronExpression($this->expression))->isDue($date->toDateTimeString());
    }

    public function if(bool|callable $condition)
    {
        $this->shouldRun = $condition;

        return $this;
    }

    public function unless(bool|callable $condition)
    {
        $this->shouldRun = is_callable($condition) ?
            fn () => !$condition() :
            $condition;

        return $this;
    }

    abstract public function run(): Result;

    public function markAsCrashed(): Result
    {
        return new Result(Status::crashed());
    }

    public function onTerminate(mixed $request, mixed $response): void
    {
    }
}
