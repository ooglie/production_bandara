#!/usr/bin/env bash
set -euo pipefail

if [[ ! -f artisan || ! -f resources/views/auth/register.blade.php ]]; then
  echo "Run this command from the Laravel project root." >&2
  exit 1
fi

echo "Project root: $(pwd)"
echo

echo "Registration route:"
php artisan route:list --name=register

echo
if grep -n "name=\"date_of_birth\"" resources/views/auth/register.blade.php; then
  echo "DOB field is present in the live registration Blade file."
else
  echo "DOB field is NOT present in resources/views/auth/register.blade.php." >&2
  exit 2
fi

echo
if grep -n "date_of_birth" app/Http/Controllers/Auth/RegisteredUserController.php >/dev/null; then
  echo "DOB validation/storage is present in RegisteredUserController."
else
  echo "DOB validation/storage is missing from RegisteredUserController." >&2
  exit 3
fi

echo
php artisan view:clear
php artisan optimize:clear

echo
printf '%s\n' "Verification completed." \
  "Open the exact /register URL shown by the route list." \
  "If the field still does not appear, restart the PHP web server/PHP-FPM because another project copy or opcode cache is being served."
