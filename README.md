# multi-menu

This repository now includes demo product pages.

## Running locally

Use PHP's built-in server to test:

```bash
php -S localhost:8000 -t public
```

After seeding data, open `http://localhost:8000/{slug}/product/{id}` in your browser (replace the placeholders with a valid company slug and product ID). The “Personalizar ingredientes” button links to `/{slug}/product/{id}/customize`, and both forms post to simple endpoints that print the submitted data.
