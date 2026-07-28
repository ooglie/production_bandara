# B2C Registration Date of Birth

## Behaviour

- Adds a required Date of birth field to public B2C registration.
- Rejects missing dates, future dates, and dates before 1900-01-01.
- Explicitly creates public registrations as `customer_type = b2c`.
- Existing users remain valid because the database column is nullable.
- B2B and staff creation flows are unchanged.

## Install

Extract this package into the Laravel project root, then run:

```bash
php artisan migrate
php artisan optimize:clear
php artisan test --filter=B2CRegistrationDateOfBirthTest
php artisan test
```

No npm build is required because the field uses existing Tailwind classes.

## Database

Adds nullable `users.date_of_birth` as a DATE column. It is required only for new public B2C registration requests.
