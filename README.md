# Lakeuden Kauppaseura

This repository stores the maintainable source for the Lakeuden Kauppaseura
WordPress site.

The source of truth is the local WordPress site and its database together with
the custom code on the `main` branch. GitHub Pages is a generated publication
artifact on the `gh-pages` branch.

> **Do not manually edit exported index.html files. They are regenerated from WordPress and will be overwritten.**

## Branches

- `main` contains the custom child theme, custom plugins, site tools,
  publishing scripts, documentation, and public root artwork.
- `gh-pages` contains only the validated static export and Git metadata.
- `backup/static-main-20260723` preserves the last static-only `main` commit
  from before the source migration.

Generated HTML must never be treated as authoritative source on `main`.

## Editable source

- `wp-content/themes/lakeuden-kauppaseura/`
- `wp-content/plugins/lks-events-manager/`
- `wp-content/plugins/lks-blog/`
- `wp-content/plugins/lks-instagram-feed/`
- `wp-content/mu-plugins/lks-instagram-feed-loader.php`
- `tools/*.php`
- `publish-github-pages.ps1`
- `publish-github-pages.cmd`
- repository documentation

WordPress core, `wp-config.php`, installed third-party packages, uploads,
caches, database files, generated builds, and QA screenshots are deliberately
ignored.

## Quick workflow

1. Open Local and start the `lakeuden-kauppaseura` site.
2. Confirm <http://lakeuden-kauppaseura.local/> loads.
3. Commit and push source changes to `main`.
4. From the WordPress document root, run:

   ```powershell
   .\publish-github-pages.cmd -Message "Update generated GitHub Pages site"
   ```

The publisher exports WordPress, validates the generated build, replaces only
the clean `gh-pages` publication checkout, commits changed output, and pushes
`gh-pages`.

See [docs/REPOSITORY_ARCHITECTURE.md](docs/REPOSITORY_ARCHITECTURE.md) for
setup, media policy, publication safety, and recovery instructions.
