# Contributing to Laravel OfficeGuy Package

Thank you for considering contributing to the Laravel OfficeGuy package! This document outlines the process for contributing.

## How to Contribute

### Reporting Bugs

If you find a bug, please create an issue on GitHub with:
- A clear, descriptive title
- Steps to reproduce the issue
- Expected behavior
- Actual behavior
- Laravel and PHP versions
- Any relevant logs or error messages

### Suggesting Features

Feature suggestions are welcome! Please create an issue with:
- A clear description of the feature
- Use cases and benefits
- Potential implementation approach
- Any examples from other packages

### Submitting Pull Requests

1. **Fork the repository**
2. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

3. **Make your changes**
   - Follow PSR-12 coding standards
   - Add tests for new features
   - Update documentation as needed
   - Ensure all tests pass

4. **Commit your changes**
   ```bash
   git commit -m "Add feature: description"
   ```

5. **Push to your fork**
   ```bash
   git push origin feature/your-feature-name
   ```

6. **Create a Pull Request**
   - Provide a clear description
   - Reference any related issues
   - Ensure CI checks pass

## Development Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/nm-digitalhub/laravel-officeguy.git
   cd laravel-officeguy
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Copy environment file:
   ```bash
   cp .env.example .env
   ```

4. Configure your test credentials in `.env`

## Coding Standards

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
- Use type hints for all parameters and return types
- Document all public methods with PHPDoc
- Keep methods focused and single-purpose
- Write descriptive variable and method names

## Testing

- Write tests for all new features
- Ensure existing tests pass
- Aim for high code coverage
- Run tests before submitting:
  ```bash
  composer test
  ```

## Documentation

- Update README.md for new features
- Add examples in documentation
- Keep inline documentation up to date
- Update CHANGELOG.md

## Code Review Process

1. All submissions require review
2. Maintainers will review within 1-2 weeks
3. Address review comments
4. Once approved, changes will be merged

## Community Guidelines

- Be respectful and inclusive
- Help others when possible
- Provide constructive feedback
- Follow the code of conduct

## Questions?

If you have questions, feel free to:
- Open a GitHub issue
- Email: info@nm-digitalhub.com

Thank you for contributing!
