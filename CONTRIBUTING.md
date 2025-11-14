# Contributing to Laravel Telebirr Package

Thank you for your interest in contributing to Laravel Telebirr! This document provides guidelines and information for contributors.

## Code of Conduct

This project follows Laravel's Code of Conduct. By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates. When reporting a bug, include:

- A clear title and description
- As much relevant information as possible
- Steps to reproduce the issue
- Expected vs actual behavior
- Your Laravel and PHP versions
- A code sample or executable test case demonstrating the expected behavior

### Suggesting Features

We welcome feature suggestions! Please provide:

- A clear description of the feature
- Rationale for why this feature would be beneficial
- Examples of how it would be used
- Sketch implementation if possible

### Pull Requests

We actively welcome pull requests. Here's how to contribute:

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- A Laravel test application

### Local Development

1. **Fork the repository** on GitHub

2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/your-username/laravel-telebirr.git
   cd laravel-telebirr
   ```

3. **Install dependencies**:
   ```bash
   composer install
   ```

4. **Create a test Laravel application** (optional for integration testing):
   ```bash
   # In a separate directory
   composer create-project laravel/laravel test-app
   cd test-app
   # Configure the package by adding the local path to composer.json
   # "repositories": [{"type": "path", "url": "../laravel-telebirr"}]
   ```

5. **Run tests**:
   ```bash
   vendor/bin/phpunit
   ```

## Development Workflow

### 1. Choose an Issue

- Check the [Issues](../../issues) page for something to work on
- Look for issues labeled `good first issue` or `help wanted`
- Comment on the issue to let others know you're working on it

### 2. Create a Feature Branch

Always work on a feature branch, never directly on `main`:

```bash
git checkout -b feature/your-feature-name
# or for bug fixes:
git checkout -b fix/issue-number-description
```

### 3. Make Your Changes

- Write clean, readable, and well-documented code
- Follow Laravel and PSR coding standards
- Add tests for new features or bug fixes
- Ensure existing tests still pass
- Update documentation as needed

### 4. Coding Standards

This project follows:

- **PHP Standards Recommendations** ([PSR-1](https://www.php-fig.org/psr/psr-1/), [PSR-4](https://www.php-fig.org/psr/psr-4/), [PSR-12](https://www.php-fig.org/psr/psr-12/))
- **Laravel Code Style** - Use `php artisan about` command for Laravel conventions
- **Documentation Standards** - Use PHPDoc for all classes, methods, and properties

### 5. Testing

- Write **unit tests** for isolated functionality
- Write **feature tests** for integration scenarios
- Ensure all tests pass: `vendor/bin/phpunit`
- Aim for high code coverage
- Test in PHP 8.1, 8.2, and 8.3
- Test in Laravel 9, 10, and 11

### 6. Commit Guidelines

- Use clear, descriptive commit messages
- Follow conventional commit format when possible:
  ```
  feat: add new payment verification endpoint
  fix: resolve RSA signature validation bug
  docs: update installation instructions
  test: add regression tests for webhook handling
  ref: improve event dispatcher performance
  ```
- Keep commits focused on single changes
- Squash related commits before merging

### 7. Submit Pull Request

Before submitting:

1. **Rebase** onto the latest main branch:
   ```bash
   git fetch upstream
   git rebase upstream/main
   ```

2. **Run all tests** to ensure everything works

3. **Update documentation** if needed

4. **Create the pull request**:
   - Use a clear title describing the change
   - Provide a detailed description of what was changed and why
   - Reference any related issues
   - Include screenshots or examples if relevant

## Release Process

This project uses [semantic versioning](https://semver.org/):

- **MAJOR** version for incompatible API changes
- **MINOR** version for backwards-compatible functionality additions
- **PATCH** version for backwards-compatible bug fixes

## Security Vulnerabilities

If you discover a security vulnerability, please email security@example.com instead of using the public issue tracker.

## Getting Help

- 📚 **Documentation**: [Package Documentation](https://laravel-telebirr.com)
- 💬 **Discussions**: [GitHub Discussions](../../discussions)
- 🆘 **Issues**: [GitHub Issues](../../issues)
- 🐛 **Bugs**: [Report a Bug](../../issues/new?template=bug_report.md)

## Recognition

Contributors are recognized in:
- This file
- Package README
- GitHub contributors
- Future changelog entries

Thank you for helping make Laravel Telebirr better! 🚀
