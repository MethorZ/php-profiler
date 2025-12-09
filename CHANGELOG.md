# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

