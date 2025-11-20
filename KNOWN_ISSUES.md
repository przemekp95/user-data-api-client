# Known Issues

## Development Tool Compatibility

### Abandoned Dependencies in Development Tools

Several development tools use abandoned libraries as dependencies, but these are intentionally kept for PHP 8.1 compatibility reasons:

#### `doctrine/annotations` (abandoned)
- **Used by**: `friendsofphp/php-cs-fixer` v3.8.0
- **Issue**: Library is abandoned with no suggested replacement
- **Rationale**: PHP-CS-Fixer v3.8.0 is the last version that supports PHP 8.0+, enabling class/method annotation processing in CI environments
- **Impact**: No functional issues - annotations work correctly
- **Recourse**: Upgrade to newer PHP versions (8.2+) to use PHP-CS-Fixer v3.11+ which uses `doctrine/annotations` replacement

#### `php-cs-fixer/diff` (abandoned)
- **Used by**: `friendsofphp/php-cs-fixer` v3.8.0
- **Issue**: Library is abandoned with no suggested replacement
- **Rationale**: Required for diff analysis in code formatting validation
- **Impact**: No functional issues - diff comparison works correctly
- **Recourse**: Newer PHP-CS-Fixer versions provide built-in alternatives

### PHP Version Constraints

#### Minimum PHP 8.1 Requirement
- **Constraint**: All dependencies require minimum PHP 8.1
- **Development Tools**: Limited to older versions due to PHP compatibility
- **Production Code**: Fully compatible and optimized for PHP 8.1+

### Decision Rationale

These known issues are **intentionally accepted** to provide a complete development environment while maintaining PHP 8.1+ compatibility:

1. **PHP 8.1+ is the target platform** for this project
2. **Complete CI/CD pipeline** requires static analysis, testing, and code formatting
3. **Alternative solutions**:
   - Skip development tools (breaks CI/CD)
   - Require PHP 8.2+ (excludes valid environments)
   - Use peer dependency conflicts (unstable)

The chosen approach provides **functional development tools** with transparent documentation of known issues, prioritizing **working environment over dependency perfection**.

### Migration Path

To eliminate these warnings:
1. Upgrade project to PHP 8.2+
2. Update development tools to latest versions
3. Remove abandoned dependency constraints

This balance enables immediate development with clear migration options for the future.
