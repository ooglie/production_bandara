# B2C DOB visibility hotfix

This package is intentionally stored from the Laravel project root. It does not contain an extra wrapper directory.

## Install

From the Laravel project root:

```bash
unzip -o Bandara_b2c_registration_dob_visible_hotfix_20260727.zip -d .
php artisan migrate
php artisan optimize:clear
bash scripts/verify_b2c_dob_installation.sh
```

The DOB field must be present in:

```text
resources/views/auth/register.blade.php
```

The public route must render that view through:

```text
app/Http/Controllers/Auth/RegisteredUserController.php
```

If verification says the field is present but the browser does not show it, the browser is reaching another Laravel project copy or a stale PHP-FPM/opcode cache. Restart the local `php artisan serve`, MAMP/Apache, or PHP-FPM process and open the `/register` URL printed by `route:list`.
