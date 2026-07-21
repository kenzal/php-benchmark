<?php

declare(strict_types=1);

namespace Kenzal\Utility;

/**
 * Benchmark
 *
 * A utility class for measuring and tracking execution time of code blocks.
 *
 * @phpstan-consistent-constructor
 *
 * @method static mixed                                  mark(callable $callback, ?string $key = null, bool $dump = false) Execute and benchmark a callback
 * @method static float|null                             last(?string $key = null)                                         Get the last recorded duration for a key
 * @method static list<float>|array<string, list<float>> history(string|list<string>|null $key = null)                     Get all recorded durations for one or multiple keys
 * @method static float|null                             avg(?string $key = null, ?int $last = null)                       Get the average duration for a key
 * @method static void                                   clear(?string $key = null)                                        Clear history for a key
 * @method static void                                   fresh()                                                           Clear all history
 */
class Benchmark
{
    /**
     * Default key used when no specific key is provided for benchmarking.
     */
    protected const NO_KEY = '1CdFPWg3E6v9jpQiAq7tf8';

    /**
     * Storage for benchmark durations, keyed by benchmark identifier.
     *
     * @var array<string, non-empty-list<float>>
     */
    protected array $durations = [];

    /**
     * Singleton instances keyed by class name.
     *
     * @var array<class-string, self>
     */
    private static array $instances = [];

    /**
     * Retrieve or create the singleton instance of the Benchmark class.
     *
     * @return static The singleton instance
     */
    public static function asSingleton(): static
    {
        $class = static::class;

        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static;
        }

        /** @var static $instance */
        $instance = self::$instances[$class];

        return $instance;
    }

    /**
     * Retrieve the average duration for a benchmark key.
     *
     * When $last is provided, only the last N durations are used. If fewer than N
     * durations exist, all available durations are used.
     *
     * @param   string|null  $key   The benchmark identifier (uses default if null)
     * @param   int|null     $last  Optional number of trailing durations to average
     * @return float|null  The average duration in milliseconds, or null when unavailable
     */
    protected function avg(?string $key = null, ?int $last = null): ?float
    {
        $key ??= self::NO_KEY;
        $history = $this->durations[$key] ?? [];
        if ($history === []) {
            return null;
        }

        if ($last !== null) {
            if ($last <= 0) {
                return null;
            }

            $history = array_slice($history, -$last);
        }

        return array_sum($history) / count($history);
    }

    /**
     * Clear benchmark history for a key.
     *
     * @param  string|null  $key  The benchmark identifier (uses default if null)
     */
    protected function clear(?string $key = null): void
    {
        $key ??= self::NO_KEY;

        unset($this->durations[$key]);
    }

    /**
     * Clear all benchmark history.
     */
    protected function fresh(): void
    {
        $this->durations = [];
    }

    /**
     * Retrieve the complete history of durations for one or multiple benchmark keys.
     *
     * When an array is provided, returns a keyed array with histories for each key.
     * When a string is provided, returns an array of durations for that key.
     *
     * @param   string|list<string>|null                $key  The benchmark identifier(s) (uses default if null)
     * @return list<float>|array<string, list<float>> Array of durations or keyed array of histories
     */
    protected function history(string|array|null $key = null): array
    {
        $key ??= self::NO_KEY;
        if (is_array($key)) {
            return $this->multiHistory($key);
        }

        return $this->durations[$key] ?? [];
    }

    /**
     * Retrieve the most recent duration for a given benchmark key.
     *
     * @param   string|null  $key  The benchmark identifier (uses default if null)
     * @return float|null  The last recorded duration in milliseconds, or null if none exists
     */
    protected function last(?string $key = null): ?float
    {
        $key ??= self::NO_KEY;

        if (!isset($this->durations[$key])) {
            return null;
        }

        $history = $this->durations[$key];

        return $history[array_key_last($history)];
    }

    /**
     * Execute a callback and record its execution time.
     *
     * The execution time is measured in milliseconds using high-resolution time.
     * Multiple executions with the same key will be stored in history.
     *
     * @param   callable     $callback  The function to execute and benchmark
     * @param   string|null  $key       Optional identifier for this benchmark (uses default if null)
     * @param   bool         $dump      Whether to dump the execution time to output
     * @return mixed       The return value from the callback
     */
    protected function mark(callable $callback, ?string $key = null, bool $dump = false): mixed
    {
        $key ??= self::NO_KEY;
        $start = hrtime(true);

        $result = $callback();

        $end = hrtime(true);

        // Convert nanoseconds to milliseconds
        $this->durations[$key][] = ($end - $start) / 1e6;

        if ($dump && function_exists('dump')) {
            dump('Execution time: '.number_format(end($this->durations[$key]), 4)." ms\n");
        }

        return $result;
    }

    /**
     * Retrieve histories for multiple benchmark keys at once.
     *
     * @param   list<string>                $keys  Array of benchmark identifiers
     * @return array<string, list<float>> Associative array mapping keys to their duration histories
     */
    protected function multiHistory(array $keys): array
    {
        $histories = [];

        foreach ($keys as $key) {
            $histories[$key] = $this->durations[$key] ?? [];
        }

        return $histories;
    }

    /**
     * Magic method to enable calling protected methods on an instance.
     *
     * Routes instance method calls to the protected methods.
     *
     * @param   string             $name       The method name
     * @param   array<int, mixed>  $arguments  The method arguments
     * @return mixed             The result of the method call
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->$name(...$arguments);
    }

    /**
     * Magic method to enable static method calls.
     *
     * Routes static method calls through the singleton instance,
     * allowing the class to be used statically while maintaining state.
     *
     * @param   string             $name       The method name
     * @param   array<int, mixed>  $arguments  The method arguments
     * @return mixed             The result of the method call
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        $static = static::asSingleton();

        return $static->$name(...$arguments);
    }
}
