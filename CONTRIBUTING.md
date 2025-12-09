# Contributing to PHP Profiler

Thank you for considering contributing to PHP Profiler! This document provides guidelines for contributing to the project.

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code. Please be respectful and constructive in all interactions.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates. When creating a bug report, include:

- **Clear description** of the issue
- **Steps to reproduce** the behavior
- **Expected behavior** vs. actual behavior
- **Code example** that demonstrates the issue
- **Environment details** (PHP version, OS, package version)

Use the bug report template when creating an issue.

### Suggesting Features

Feature suggestions are welcome! Please:

- Check existing feature requests first
- Provide a clear use case for the feature
- Explain why the feature would be useful
- Consider proposing an API design

Use the feature request template when creating an issue.

### Pull Requests

1. **Fork** the repository
2. **Create a branch** from `main` for your changes
3. **Write code** following the project's coding standards
4. **Add tests** for new functionality
5. **Update documentation** as needed
6. **Run quality checks** before submitting
7. **Submit a pull request** with a clear description

## Development Setup

```bash
# Clone your fork
git clone https://github.com/YOUR-USERNAME/php-profiler.git
cd php-profiler

# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyze

# Check code style
composer sniff

# Auto-fix code style
composer fix

# Run all checks
composer check
```

## Coding Standards

### PHP Version

- Target PHP 8.2+
- Use modern PHP features (enums, match expressions, readonly, etc.)

### Code Style

- Follow PSR-12 coding standard
- Use strict types: `declare(strict_types=1);`
- Use typed properties and return types
- Add trailing commas in multi-line arrays and function calls

### Static Analysis

- Code must pass PHPStan level 9 without errors
- No suppression of errors without justification

### Testing

- Write tests for all new functionality
- Maintain or improve code coverage
- Tests must pass before merging
- Use descriptive test names: `testMethodNameScenarioExpectedBehavior()`

### Documentation

- Add PHPDoc only when PHP's type system is insufficient
- Update README.md for new features
- Update CHANGELOG.md following Keep a Changelog format
- Provide code examples for new features

## Commit Messages

- Use clear, descriptive commit messages
- Start with a verb in present tense (e.g., "Add", "Fix", "Update")
- Reference issue numbers when applicable

Examples:
```
Add percentile calculation to MetricsCollector

Fix memory leak in Timer class (#42)

Update README with sampling mode examples
```

## Branch Naming

Use descriptive branch names:
- `feature/add-redis-storage`
- `fix/timer-memory-leak`
- `docs/update-readme`

## Release Process

Maintainers handle releases following semantic versioning:

- **Major (x.0.0)**: Breaking changes
- **Minor (1.x.0)**: New features, backwards-compatible
- **Patch (1.0.x)**: Bug fixes, backwards-compatible

## Questions?

Feel free to open an issue for questions or reach out to the maintainers.

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
