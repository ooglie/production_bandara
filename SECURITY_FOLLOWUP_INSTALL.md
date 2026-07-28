# Bandara security follow-up fix

This package fixes:

1. `SafeRedirect` accepting slash-plus-backslash redirect values.
2. Fresh SQLite test databases failing on MySQL-only `ALTER TABLE ... MODIFY` syntax.
3. The Composer lockfile remaining on security-vulnerable Laravel, Guzzle and Symfony versions.

## Install

Extract the ZIP into the Laravel project root and replace the matching files.

Then run:

```bash
chmod +x scripts/update_php_security_dependencies.sh
bash scripts/update_php_security_dependencies.sh
```

The script:

- requires PHP 8.5+;
- backs up `composer.lock`;
- performs a targeted dependency update;
- runs `composer audit --locked`;
- validates `composer.json` and `composer.lock`;
- clears Laravel caches;
- runs the full test suite.

Commit the newly generated `composer.lock` after the audit and tests pass.

## No database migration is required on an existing database

The changed migrations are historical compatibility corrections for clean test/fresh installations. Existing MySQL databases already have `customer_type = staff` support and should not be rolled back.
