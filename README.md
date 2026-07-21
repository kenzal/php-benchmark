# PHP Expression Benchmark Utility

A lightweight benchmarking utility for measuring PHP callback execution time in milliseconds.

[![Tests](https://github.com/kenzal/php-benchmark/actions/workflows/tests.yml/badge.svg)](https://github.com/kenzal/php-benchmark/actions/workflows/tests.yml)
[![Code Style](https://github.com/kenzal/php-benchmark/actions/workflows/code-style.yml/badge.svg)](https://github.com/kenzal/php-benchmark/actions/workflows/code-style.yml)
[![PHP Version](https://img.shields.io/badge/php-8.1%20%7C%208.3%20%7C%208.4%20%7C%208.5-blue.svg)](https://php.net)
[![Packagist Version](https://img.shields.io/packagist/v/kenzal/benchmark)](https://packagist.org/packages/kenzal/benchmark)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## Installation

```bash
composer require --dev kenzal/php-benchmark
```

## Usage

`Benchmark` can be used statically (recommended) or through an instance.

### Static usage

```php
use Kenzal\Utility\Benchmark;

$result = Benchmark::mark(fn () => expensiveOperation(), 'operation-key');
$lastDuration = Benchmark::last('operation-key');
$averageDuration = Benchmark::avg('operation-key');
$history = Benchmark::history('operation-key');
Benchmark::clear('operation-key');
```

### Instance usage

```php
use Kenzal\Utility\Benchmark;

$benchmark = new Benchmark();
$result = $benchmark->mark(fn () => expensiveOperation(), 'operation-key');
$lastDuration = $benchmark->last('operation-key');
$averageDuration = $benchmark->avg('operation-key');
$history = $benchmark->history('operation-key');
$benchmark->clear('operation-key');
```

## Working with keys and history

Each call to `mark()` stores a duration under the given key.

```php
Benchmark::mark(fn () => usleep(1000), 'db');
Benchmark::mark(fn () => usleep(1500), 'db');
Benchmark::mark(fn () => usleep(500), 'api');

$dbHistory = Benchmark::history('db');      // [x, y]
$apiHistory = Benchmark::history('api');    // [z]
$all = Benchmark::history(['db', 'api']);   // ['db' => [...], 'api' => [...]]
```

If no key is provided, durations are stored using an internal default key and can still be read with:

```php
Benchmark::history();
Benchmark::last();
Benchmark::avg();
```

You can also average only the most recent entries for a key:

```php
$avgLast10 = Benchmark::avg('db', 10);
```

To reset history:

```php
Benchmark::clear('db'); // clear a specific key
Benchmark::clear();     // clear the default key history
Benchmark::fresh();     // clear all keys
```

## API

- `mark(callable $callback, ?string $key = null, bool $dump = false): mixed`
- `last(?string $key = null): ?float`
- `avg(?string $key = null, ?int $last = null): ?float`
- `history(string|list<string>|null $key = null): array`
- `clear(?string $key = null): void`
- `fresh(): void`

## Development

Run the full project checks:

```bash
composer run test
```

If you're using Herd, run the Herd-specific pipeline:

```bash
composer run herd:test
```
