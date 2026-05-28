# Branding

Project display name: `Offorest`.

## Logo asset

Main logo is stored at:

```text
public/images/offorest-logo.jpg
```

It is used by:

- `resources/views/components/application-logo.blade.php`
- `resources/views/livewire/pages/auth/login.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/guest.blade.php`

## App name

The app name is configured by `APP_NAME`.

Local/default values:

```env
APP_NAME=Offorest
```

When deploying, set the same value on the server `.env`, then clear config cache:

```bash
php artisan config:clear
php artisan optimize:clear
```

If the server does not run Vite build, upload `public/images/offorest-logo.jpg` and the generated `public/build` directory.
