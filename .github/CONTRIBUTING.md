# Contributing to Laravel Toon

Thank you for considering contributing to Laravel Toon! This document provides guidelines and instructions for contributing.

## Code of Conduct

Be respectful and considerate of others. We're all here to make this package better.

## How to Contribute

### Reporting Bugs

1. Check if the bug has already been reported in [Issues](https://github.com/digitalcorehub/laravel-toon/issues)
2. If not, create a new issue using the [Bug Report template](.github/ISSUE_TEMPLATE/bug_report.md)
3. Provide as much detail as possible

### Suggesting Features

1. Check if the feature has already been suggested
2. Create a new issue using the [Feature Request template](.github/ISSUE_TEMPLATE/feature_request.md)
3. Explain the use case and benefits

### Pull Requests

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Write or update tests
5. Ensure all tests pass (`vendor/bin/phpunit`)
6. Follow the code style (we use Laravel Pint)
7. Commit your changes (`git commit -m 'Add amazing feature'`)
8. Push to the branch (`git push origin feature/amazing-feature`)
9. Open a Pull Request

## Development Setup

```bash
# Clone the repository
git clone https://github.com/digitalcorehub/laravel-toon.git
cd laravel-toon

# Install dependencies
composer install

# Run tests
vendor/bin/phpunit
```

## Coding Standards

- Follow PSR-12 coding standards
- Use Laravel Pint for code formatting
- Write meaningful commit messages
- Add PHPDoc comments for public methods

## Testing

- All new features must include tests
- All bug fixes must include regression tests
- Aim for high test coverage
- Tests should be clear and well-documented

## Commit Messages

Use clear, descriptive commit messages:

- `feat: add new decode method`
- `fix: resolve nested array parsing issue`
- `docs: update README with examples`
- `test: add tests for edge cases`

## Questions?

Feel free to open an issue for any questions or concerns.

Thank you for contributing! 🎉

