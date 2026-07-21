<?php

use Kenzal\Utility\Benchmark;

beforeEach(function (): void {
    $reflection = new ReflectionProperty(Benchmark::class, 'instances');
    $reflection->setValue(null, []);
});

it('returns the callback result from mark', function (): void {
    $result = Benchmark::mark(fn (): string => 'done', 'result-key');

    expect($result)->toBe('done');
});

it('stores benchmark durations and returns the latest value', function (): void {
    Benchmark::mark(fn () => usleep(1000), 'timing-key');
    Benchmark::mark(fn () => usleep(1000), 'timing-key');

    $history = Benchmark::history('timing-key');
    $last    = Benchmark::last('timing-key');

    expect($history)->toHaveCount(2)
        ->and($history[0])->toBeFloat()
        ->and($history[1])->toBeFloat()
        ->and($last)->toBe($history[1]);
});

it('uses the default key when none is provided', function (): void {
    Benchmark::mark(fn () => usleep(1000));

    expect(Benchmark::history())->toHaveCount(1)
        ->and(Benchmark::last())->not->toBeNull();
});

it('separates history by key and supports multi-key lookups', function (): void {
    Benchmark::mark(fn () => usleep(1000), 'alpha');
    Benchmark::mark(fn () => usleep(1000), 'beta');
    Benchmark::mark(fn () => usleep(1000), 'alpha');

    $histories = Benchmark::history(['alpha', 'beta', 'missing']);

    expect($histories['alpha'])->toHaveCount(2)
        ->and($histories['beta'])->toHaveCount(1)
        ->and($histories['missing'])->toBe([]);
});

it('supports instance calls through magic __call', function (): void {
    $benchmark = new Benchmark;

    $result = $benchmark->mark(fn (): int => 42, 'instance-key');

    expect($result)->toBe(42)
        ->and($benchmark->history('instance-key'))->toHaveCount(1)
        ->and($benchmark->last('instance-key'))->toBeFloat();
});

it('returns null for avg when there is no history', function (): void {
    expect(Benchmark::avg('missing'))->toBeNull()
        ->and(Benchmark::avg())->toBeNull();
});

it('returns null for avg when last is zero or negative', function (): void {
    Benchmark::mark(fn () => usleep(1000), 'avg-invalid-last');

    expect(Benchmark::avg('avg-invalid-last', 0))->toBeNull()
        ->and(Benchmark::avg('avg-invalid-last', -1))->toBeNull();
});

it('returns the average for all history by default', function (): void {
    Benchmark::mark(fn () => usleep(1000), 'avg-key');
    Benchmark::mark(fn () => usleep(2000), 'avg-key');
    Benchmark::mark(fn () => usleep(3000), 'avg-key');

    $history = Benchmark::history('avg-key');
    /** @var list<float> $history */
    $expected = array_sum($history) / count($history);

    expect(Benchmark::avg('avg-key'))->toBe($expected);
});

it('returns the average of the last N durations', function (): void {
    Benchmark::mark(fn () => usleep(1000), 'avg-last-key');
    Benchmark::mark(fn () => usleep(2000), 'avg-last-key');
    Benchmark::mark(fn () => usleep(3000), 'avg-last-key');

    $history = Benchmark::history('avg-last-key');
    /** @var list<float> $history */
    $lastTwo  = array_slice($history, -2);
    $expected = array_sum($lastTwo) / count($lastTwo);

    expect(Benchmark::avg('avg-last-key', 2))->toBe($expected);
});

it('averages up to the available history when last exceeds count', function (): void {
    Benchmark::mark(fn () => usleep(1000), 'avg-short-key');
    Benchmark::mark(fn () => usleep(2000), 'avg-short-key');

    $history = Benchmark::history('avg-short-key');
    /** @var list<float> $history */
    $expected = array_sum($history) / count($history);

    expect(Benchmark::avg('avg-short-key', 10))->toBe($expected);
});

it('clears history for a specific key', function (): void {
    Benchmark::mark(fn () => usleep(1000), 'clear-alpha');
    Benchmark::mark(fn () => usleep(1000), 'clear-beta');

    Benchmark::clear('clear-alpha');

    expect(Benchmark::history('clear-alpha'))->toBe([])
        ->and(Benchmark::last('clear-alpha'))->toBeNull()
        ->and(Benchmark::history('clear-beta'))->toHaveCount(1);
});

it('clears default-key history when clear is called without a key', function (): void {
    Benchmark::mark(fn () => usleep(1000));

    Benchmark::clear();

    expect(Benchmark::history())->toBe([])
        ->and(Benchmark::last())->toBeNull()
        ->and(Benchmark::avg())->toBeNull();
});

it('clears all history with fresh', function (): void {
    Benchmark::mark(fn () => usleep(1000), 'fresh-alpha');
    Benchmark::mark(fn () => usleep(1000), 'fresh-beta');
    Benchmark::mark(fn () => usleep(1000));

    Benchmark::fresh();

    expect(Benchmark::history('fresh-alpha'))->toBe([])
        ->and(Benchmark::history('fresh-beta'))->toBe([])
        ->and(Benchmark::history())->toBe([])
        ->and(Benchmark::last('fresh-alpha'))->toBeNull()
        ->and(Benchmark::last('fresh-beta'))->toBeNull()
        ->and(Benchmark::last())->toBeNull();
});

it('can mark with dump enabled', function (): void {
    $result = Benchmark::mark(fn (): string => 'done', 'dump-key', true);

    expect($result)->toBe('done')
        ->and(Benchmark::history('dump-key'))->toHaveCount(1);
});
