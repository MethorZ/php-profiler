# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-10-22

### Changed
- PHP requirement raised to `~8.4.27 || ~8.5.12` (releases patched for php2shell: CVE-2026-109618, CVE-2026-109619). PHP 8.2 and 8.3 are no longer supported.
- Updated development dependencies: phpunit/phpunit ^13.4, phpstan/phpstan ^2.3 (from 1.x), squizlabs/php_codesniffer ^4.0.1 (from 3.x), slevomat/coding-standard ^8.31.
- PHPStan analyses against the runtime PHP version (removed the `phpVersion: 80200` pin).
- `MetricsComparator`, `MetricsCollector` and `ConsoleFormatter` throw `ProfilingException` for non-numeric metric values instead of a PHP `TypeError`. Numeric strings are still accepted.

## [1.0.0] - 2025-12-10

### Added
- Initial release v1.0.0
- **Core Profiling**:
  - `OperationProfiler` for timing operations with checkpoint support
  - `Timer` abstraction for precise timing measurements
  - `MetricBag` for storing and accessing metrics
  - Memory profiling (current, peak, delta)
  - Global enable/disable for zero production overhead

- **Advanced Features**:
  - `MetricsCollector` with percentile calculations (P50, P75, P90, P95, P99)
  - `MetricsComparator` for before/after performance analysis
  - `SamplingProfiler` for production use with configurable sampling rates
  - `ContextAwareProfiler` for environment-based auto-configuration
  - `PerformanceMonitor` for threshold-based warnings

- **Storage & Analysis**:
  - Persistent storage interface with `FileStorage` and `MemoryStorage` implementations
  - MySQL query analyzer with `MysqlQueryExplainer`
  - Automatic EXPLAIN for slow queries with optimization suggestions

- **Integration**:
  - `ProfilesOperations` trait for easy repository/service integration
  - `ConsoleFormatter` and `JsonFormatter` for output formatting
  - GitHub Actions CI/CD workflows
  - Comprehensive test coverage (unit + integration)

- **Quality Assurance**:
  - PHPStan level 9 static analysis
  - PSR-12 code style compliance
  - PHP 8.2+ support with modern features

