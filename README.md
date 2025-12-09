# PHP Profiler

[![CI](https://github.com/methorz/php-profiler/workflows/CI/badge.svg)](https://github.com/methorz/php-profiler/actions)
[![codecov](https://codecov.io/gh/methorz/php-profiler/branch/main/graph/badge.svg)](https://codecov.io/gh/methorz/php-profiler)
[![Latest Stable Version](https://poser.pugx.org/methorz/php-profiler/v/stable)](https://packagist.org/packages/methorz/php-profiler)
[![License](https://poser.pugx.org/methorz/php-profiler/license)](https://packagist.org/packages/methorz/php-profiler)

Request-scoped performance profiling library for PHP with detailed timing breakdowns, memory tracking, percentile metrics, and intelligent threshold monitoring.

## Features

- ⏱️ **Precise Timing** - Microsecond-precision operation timing with checkpoint support
- 💾 **Memory Profiling** - Track current, peak, and delta memory usage
- 📊 **Percentile Metrics** - P50, P75, P90, P95, P99 for statistical analysis
- 📈 **Comparative Analysis** - Before/after comparisons for optimization measurement
- 🎲 **Sampling Mode** - Reduce overhead with configurable sampling rates
- ⚠️ **Smart Thresholds** - Configurable warnings for slow operations and high memory
- 🔧 **Context-Aware** - Automatic configuration based on environment
- 💿 **Persistent Storage** - Store metrics for later analysis
- 🗄️ **Query Analysis** - MySQL EXPLAIN integration for slow queries
- 🎯 **Zero Production Overhead** - Can be completely disabled
- 🔌 **Easy Integration** - Simple trait for repository/service integration
- 📝 **Multiple Formats** - Console-friendly and JSON output

## Installation

```bash
composer require methorz/php-profiler
```

## Quick Start

### Basic Usage

```php
use MethorZ\Profiler\OperationProfiler;

$profiler = OperationProfiler::start('database_query');

$profiler->checkpoint('prep');
$params = $this->prepareParams($data);

$profiler->checkpoint('exec');
$result = $this->executeQuery($query, $params);

$profiler->checkpoint('hydrate');
$objects = $this->hydrate($result);

$metrics = $profiler->end();
```

### Repository Integration

```php
use MethorZ\Profiler\Concern\ProfilesOperations;

class TransactionRepository
{
    use ProfilesOperations;

    public function fetchTransactions(array $accounts): array
    {
        $profiler = $this->startProfiling('fetch_transactions');

        try {
            $profiler->checkpoint('prep');
            $query = $this->buildQuery($accounts);

            $profiler->checkpoint('exec');
            $result = $this->dal->execute($query);

            $profiler->checkpoint('hydrate');
            $transactions = $this->hydrate($result);

            $profiler->addCount('rows', count($transactions));
            return $transactions;
        } finally {
            $profiler->end();
        }
    }

    public function getLastQueryMetrics(): array
    {
        return $this->getLastProfilingMetrics();
    }
}
```

## Advanced Features

### 1. Context-Aware Profiling

Automatically adjusts behavior based on environment:

```php
use MethorZ\Profiler\ContextAwareProfiler;

// Auto-detects environment and configures appropriately
$profiler = ContextAwareProfiler::create();
$opProfiler = $profiler->start('operation');

// Production web: disabled
// Production CLI: 10% sampling
// Development: full profiling
```

### 2. Sampling Mode (Production Use)

Profile only a percentage of operations:

```php
use MethorZ\Profiler\SamplingProfiler;

$sampler = new SamplingProfiler(0.1); // 10% sampling
$profiler = $sampler->start('high_traffic_operation');
// ... operation
$metrics = $profiler->end();
```

### 3. Percentile Metrics

Statistical analysis of operation performance:

```php
use MethorZ\Profiler\MetricsCollector;

$collector = new MetricsCollector();

foreach ($batches as $batch) {
    $profiler = OperationProfiler::start('batch_process');
    // ... process batch
    $metrics = $profiler->end();
    $collector->record('batch_process', $metrics);
}

$aggregate = $collector->aggregate();
// Returns: ['percentiles' => ['p50' => 0.05, 'p95' => 0.15, 'p99' => 0.25, ...]]
```

### 4. Comparative Analysis

Measure optimization impact:

```php
use MethorZ\Profiler\Analysis\MetricsComparator;
use MethorZ\Profiler\Storage\FileStorage;

$storage = new FileStorage('./metrics');

// Before optimization
$profiler = OperationProfiler::start('slow_query');
$result = $this->oldImplementation();
$before = $profiler->end();
$storage->store('before', $before);

// After optimization
$profiler = OperationProfiler::start('slow_query');
$result = $this->newImplementation();
$after = $profiler->end();

$comparator = new MetricsComparator();
$comparison = $comparator->compare($before, $after);

// Returns detailed comparison with improvement/regression detection
echo $comparison['summary']['messages'][0];
// "Performance improvement: 43.2% faster (0.5s → 0.284s)"
```

### 5. Persistent Storage

Store metrics for later analysis:

```php
use MethorZ\Profiler\Storage\FileStorage;

$storage = new FileStorage('./profiling-data');

$profiler = OperationProfiler::start('operation');
// ... operation
$metrics = $profiler->end();

// Store metrics
$storage->store('optimization_v1', $metrics);

// Retrieve later
$historical = $storage->retrieve('optimization_v1');
```

### 6. MySQL Query Analysis

Automatic EXPLAIN for slow queries:

```php
use MethorZ\Profiler\Database\MysqlQueryExplainer;

$explainer = new MysqlQueryExplainer($pdo);

$profiler = OperationProfiler::start('complex_query');
$result = $this->executeQuery($query);
$metrics = $profiler->end();

if ($metrics['total'] > 1.0) { // Slow query
    $explain = $explainer->explain($query);

    if ($explainer->needsOptimization($explain)) {
        $suggestions = $explainer->getSuggestions($explain);
        // ['Full table scan on "users" - consider adding an index']
    }
}
```

### 7. Performance Monitoring with Thresholds

Automatic warnings for performance issues:

```php
use MethorZ\Profiler\PerformanceMonitor;

$monitor = PerformanceMonitor::create()
    ->setSlowOperationThreshold(1.0)    // 1 second
    ->setSlowPhaseThreshold(0.5)        // 500ms
    ->setHighMemoryThreshold(0.7)       // 70% of memory limit
    ->setHighRowCountThreshold(10000);  // 10k rows

$profiler = OperationProfiler::start('operation', $monitor);
// ... operation
$metrics = $profiler->end();

// Metrics include warnings if thresholds exceeded
if (isset($metrics['context']['warnings'])) {
    foreach ($metrics['context']['warnings'] as $warning) {
        error_log($warning);
    }
}
```

## Output Formatting

### Console Output

```php
use MethorZ\Profiler\Formatter\ConsoleFormatter;

$formatter = new ConsoleFormatter();
echo $formatter->format($metrics);
```

Output:
```
Operation: fetch_transactions
Total: 125.50ms
Phases:
  prep:      3.20ms (2.5%)
  exec:    100.10ms (79.7%)
  hydrate:  22.20ms (17.7%)
Memory: 45.2MB (peak: 48.7MB, Δ+3.5MB)
Counts: accounts: 150, rows: 2340
```

### JSON Export

```php
use MethorZ\Profiler\Formatter\JsonFormatter;

$formatter = new JsonFormatter(prettyPrint: true);
$json = $formatter->format($metrics);
file_put_contents('metrics.json', $json);
```

## Configuration

### Disable Profiling (Production)

```php
// Disable globally (zero overhead)
OperationProfiler::setEnabled(false);
```

### Environment-Based Configuration

```php
// .env file
APP_ENV=production  // Auto-disables for web, samples for CLI

// Override in code
$profiler = ContextAwareProfiler::create()
    ->forceEnable()  // Force enabled regardless of environment
    ->forceSamplingRate(0.05);  // 5% sampling
```

## Best Practices

### ✅ DO

- Profile at repository/port layer for database operations
- Use checkpoints to identify bottlenecks within operations
- Aggregate metrics for batch operations
- Use sampling mode in production
- Store metrics for trend analysis
- Compare before/after for optimization verification

### ❌ DON'T

- Profile every method call (too much overhead)
- Use for production monitoring without sampling (use proper APM tools)
- Store metrics in database (they're request-scoped)
- Profile trivial operations (<1ms)
- Forget to disable in production web requests

## Requirements

- PHP 8.2 or higher
- ext-json

## Testing

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Run all quality checks
composer check
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

MIT License. See [LICENSE](LICENSE) for details.

## Comparison to Other Tools

| Feature | php-profiler | OpenTelemetry | Prometheus | StatsD |
|---------|--------------|---------------|------------|--------|
| **Purpose** | Dev profiling | Distributed tracing | Time-series metrics | Real-time metrics |
| **Overhead** | Medium (dev) | Low (sampling) | Low | Very low |
| **Memory tracking** | ✅ Yes | ❌ No | ❌ No | ❌ No |
| **Percentiles** | ✅ Yes | ✅ Yes | ✅ Yes | ⚠️ Limited |
| **Sub-operations** | ✅ Checkpoints | ✅ Spans | ⚠️ Manual | ⚠️ Manual |
| **Comparison** | ✅ Built-in | ❌ No | ❌ No | ❌ No |
| **Production** | ⚠️ Sampling only | ✅ Yes | ✅ Yes | ✅ Yes |
| **Setup** | ✅ Simple | ⚠️ Medium | ✅ Simple | ✅ Simple |

**Use php-profiler for:** Development profiling, optimization measurement, debugging performance issues

**Use OpenTelemetry/Prometheus for:** Production monitoring, distributed tracing, long-term metrics storage

## Credits

Created by [Thorsten Merz](https://github.com/methorz)

## Links

- [GitHub Repository](https://github.com/methorz/php-profiler)
- [Packagist](https://packagist.org/packages/methorz/php-profiler)
- [Documentation](https://github.com/methorz/php-profiler/wiki)
- [Issue Tracker](https://github.com/methorz/php-profiler/issues)

