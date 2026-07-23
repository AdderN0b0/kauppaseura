# Push source and publish GitHub Pages

The repository uses separate branches:

- `main`: maintainable WordPress source.
- `gh-pages`: validated generated website.

**Do not manually edit exported index.html files. They are regenerated from WordPress and will be overwritten.**

## Source changes

Work from the Local WordPress document root. Review and commit only the
site-owned files allowed by `.gitignore`:

```powershell
git status
git add <intended-source-files>
git commit -m "Describe the source change"
git push origin main
```

Do not commit WordPress core, `wp-config.php`, uploads, Local configuration,
caches, backups, generated builds, or QA screenshots.

## Publish

Start the Local site and confirm this URL loads:

```text
http://lakeuden-kauppaseura.local/
```

Then run:

```powershell
.\publish-github-pages.cmd -Message "Update generated GitHub Pages site"
```

The publisher exports and validates WordPress before touching the clean
`gh-pages` checkout at:

```text
deliverables\lakeuden-kauppaseura-offline
```

If validation fails, nothing is copied or pushed. If generated output is
unchanged, no commit is created.

Public site:

```text
https://addern0b0.github.io/kauppaseura/
```

Detailed setup and recovery instructions are in
`docs/REPOSITORY_ARCHITECTURE.md`.
