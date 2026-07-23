# Repository and publication architecture

## Source of truth

The public website is authored in the local WordPress installation:

- page, post, event, attachment metadata, and editable copy live in the
  WordPress database;
- presentation and rendering logic live in the custom theme and plugins on
  `main`;
- the static website on `gh-pages` is generated from rendered WordPress output.

**Do not manually edit exported index.html files. They are regenerated from WordPress and will be overwritten.**

## Branch and checkout layout

| Branch | Purpose |
| --- | --- |
| `main` | Maintainable WordPress theme, plugins, tools, publishing scripts, documentation, and public root artwork |
| `gh-pages` | Validated generated HTML and downloaded public assets only |
| `backup/static-main-20260723` | Recovery reference for the final pre-migration static-only commit |

The Local WordPress document root is the `main` working tree. The publication
checkout is expected at:

```text
deliverables/lakeuden-kauppaseura-offline
```

That checkout must be clean, use the same `origin`, and have `gh-pages`
checked out. The generated build is written to:

```text
deliverables/lakeuden-kauppaseura-build
```

The whole `deliverables/` tree is ignored by `main`.

## What may be edited

Edit only site-owned source:

- `wp-content/themes/lakeuden-kauppaseura/`
- `wp-content/plugins/lks-events-manager/`
- `wp-content/plugins/lks-blog/`
- `wp-content/plugins/lks-instagram-feed/`
- `wp-content/mu-plugins/lks-instagram-feed-loader.php`
- `tools/*.php`
- publication scripts and documentation

Content editors may also edit WordPress pages, posts, events, media metadata,
and the site-specific copy settings in the WordPress administration area.

Do not edit generated HTML, downloaded export assets, WordPress core, or
third-party plugin/theme code as project source.

## Local WordPress setup

1. Create or restore a Local site with the hostname:

   ```text
   lakeuden-kauppaseura.local
   ```

2. Install WordPress and the Twenty Twenty-Five parent theme. The audited
   environment uses WordPress 7.0.2 and Twenty Twenty-Five 1.5.
3. Place this repository at the WordPress document root so the tracked
   `wp-content` source overlays the Local installation.
4. Restore the WordPress database and `wp-content/uploads/` from a private,
   access-controlled operational backup.
5. Activate the Lakeuden Kauppaseura child theme and the custom plugins.
6. Enter service credentials, such as an Instagram access token, through the
   WordPress administration UI. Never commit them.

WPForms Lite 1.10.1.1 is installed in the audited Local environment, but no
public exported page uses it. It is not copied into source control.

## Uploaded-media policy

The live `wp-content/uploads/` directory is not tracked on `main`.

Reasons:

- WordPress creates several derivative sizes for each original;
- plugin cache data may be written below uploads;
- future uploads may not be approved for public source control;
- attachment alt text, captions, and relationships live in the database.

Theme-owned assets needed by code are tracked inside the child theme. The
exporter downloads only assets referenced by public rendered pages into
`gh-pages`. Database and media disaster-recovery backups must be stored outside
Git in a private, access-controlled backup location.

The responsive homepage hero and favicon outputs are tracked. The optional
`tools/generate-remediation-images.php` tool needs its approved source artwork
from the private uploads backup if those outputs must be regenerated.

## Export and validation

Start Local, then run:

```powershell
php tools/export-static.php
php tools/validate-static.php
```

The default exporter output and validator target are
`deliverables/lakeuden-kauppaseura-build`.

For an explicit validation target on Windows, resolve it first:

```powershell
$target = (Resolve-Path .\deliverables\lakeuden-kauppaseura-build).Path
php tools/validate-static.php $target
```

## Publishing GitHub Pages

Prerequisites:

- source `main` is clean;
- publication checkout is clean and on `gh-pages`;
- both checkouts use the same GitHub `origin`;
- Git user name and email are configured in the publication checkout;
- PHP is available on `PATH` or through the Local installation;
- Local WordPress is running.

Run:

```powershell
.\publish-github-pages.cmd -Message "Update generated GitHub Pages site"
```

The publisher:

1. checks both Git working trees and branch/remote relationships;
2. checks that WordPress responds;
3. exports into the ignored build directory;
4. runs `php tools/validate-static.php`;
5. stops without touching `gh-pages` if export or validation fails;
6. replaces only generated files in the publication checkout;
7. validates the copied publication tree;
8. commits only when output changed;
9. pushes `gh-pages`.

The export manifest deliberately excludes a generated timestamp. This keeps
the build deterministic enough that an unchanged WordPress render does not
create a timestamp-only publication commit. Instagram CDN assets are keyed by
their stable media path because signed query parameters and edge hostnames
rotate without changing the public image.

The validator compares the sitemap with the canonical URLs found in indexable
HTML instead of assuming a fixed page count.

## GitHub repository setting

GitHub Pages must be configured to deploy:

```text
Deploy from a branch
Branch: gh-pages
Folder: / (root)
```

The default repository branch remains `main`.

During the one-time migration, publish `gh-pages` and switch this setting
before replacing the old static-only remote `main`. This ordering keeps the
existing Pages site available throughout the cutover.

## Recovery

Export or validation failure requires no rollback: the publisher has not yet
touched the publication checkout.

If copying or the second validation fails, the remote site is unchanged.
Inspect the publication checkout before restoring its generated files:

```powershell
git -C deliverables/lakeuden-kauppaseura-offline status
```

If a publication commit succeeds but its push fails, fix connectivity and run:

```powershell
git -C deliverables/lakeuden-kauppaseura-offline push origin gh-pages
```

If a bad publication was pushed, revert it without rewriting history:

```powershell
git -C deliverables/lakeuden-kauppaseura-offline revert <bad-commit>
git -C deliverables/lakeuden-kauppaseura-offline push origin gh-pages
```

The pre-migration publication is permanently referenced by:

```text
backup/static-main-20260723
c474e091ddff5a3d9886a90f5f8249b42b9a20e8
```

Do not force-push during normal recovery.
