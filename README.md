# XORU Radiator Pro

The application now uses Symfony 7.4, Twig, Symfony Security, and Doctrine DBAL. It keeps the existing MySQL tables and password hashes.

## XAMPP setup

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Import `database/xoru_dator2.sql` into MySQL if the `xoru_dator2` database has not already been created.
3. From this directory, run `composer install`.
4. Copy `.env` to `.env.local` and set `DATABASE_URL` to match your local MySQL credentials. The default assumes the XAMPP `root` account has no password. Set a unique `APP_SECRET` in `.env.local` before deployment.
5. Configure Apache's document root for this site as `public/`, enable `mod_rewrite`, and allow `.htaccess` overrides. The rewrite file in `public/` sends Symfony routes to its front controller.

For a quick local run, use:

```powershell
C:\xampp3\php\php.exe -S 127.0.0.1:8000 -t public public/router.php
```

Then open `http://127.0.0.1:8000`. MySQL must be running for the homepage and account data to load. The Google and Facebook login screens in the original app were placeholders requiring OAuth credentials; this migration keeps email/password login only.