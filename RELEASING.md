# Releasing gembapay-php

The PHP SDK is published on **Packagist** (`gembapay/gembapay-php`) and auto-updates from
this repository's **git tags**. To ship a new version:

1. Make the code change in `src/` (e.g. `src/GembaPay.php`).
2. Bump the version constant — `src/GembaPay.php`:
   ```php
   private const VERSION = 'X.Y.Z';
   ```
   (`composer.json` intentionally has **no** `version` field — Packagist derives the version
   from the git tag, so the tag is the single source of truth.)
3. Sanity-check:
   ```bash
   php -l src/GembaPay.php
   composer validate --strict
   ```
4. Commit, tag `vX.Y.Z`, and push:
   ```bash
   git add -A
   git commit -m "…"
   git tag vX.Y.Z
   git push origin main --tags
   ```
5. Packagist auto-updates within a minute or two (GitHub webhook). If it lags, log in at
   packagist.org → the package page → **Update**.

## Webhook signing contract (must not regress)

The GembaPay backend signs each webhook as a **bare HMAC-SHA256 hex string** — **no
`sha256=` prefix** — computed over the **raw request body**. So `verifyWebhook()` must:

```php
$expected = hash_hmac('sha256', $rawBody, $secret);   // bare hex, no prefix
return hash_equals($expected, (string) $signature);
```

Prefixing `sha256=` (as pre-1.0.1 releases did) makes the SDK reject every genuine webhook.
Always verify against the **raw body** (`file_get_contents('php://input')`), never a
re-encoded copy.

## Where this lives

- Server working copy (push from here): `/gembapay.com/gembapay-php`
- Sibling packages on the same server:
  - npm SDK — `/gembapay.com/gitrepo/packages/npm` (github.com/ivanovslavy/gembapay + `npm publish`)
  - WooCommerce plugin — `/gembapay.com/gitrepo/packages/woocommerce` (+ wordpress.org SVN working copy at `~/wp-svn-gembapay`)
