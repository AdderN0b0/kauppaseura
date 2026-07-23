# Privacy and structured data

## Audited production integrations

The public site is currently a static GitHub Pages build generated from the
local WordPress site. The audit on 23 July 2026 found:

| Area | Actual implementation |
| --- | --- |
| Membership interest | WPForms Lite exists in local WordPress, but `join_form_ready` is disabled. Required enquiry processing is acknowledged separately from optional communications consent. The static site shows a contact fallback and submits nothing. |
| Form storage | WPForms Lite does not store entries locally. Lite Connect entry backups are not enabled. |
| Email delivery | The form recipient is `{admin_email}`, which currently resolves to a Local development address. No SMTP or other mail-delivery plugin is installed. |
| Spam protection | WPForms built-in anti-spam is configured in the reproducible form schema. No reCAPTCHA, hCaptcha, or Turnstile credentials are configured. |
| Event registration | No internal registration form or participant database. The audited event plugin currently stores descriptive registration text; future registration must use an external URL. |
| Event notifications | No event-detail notification form or notification subscriber list. |
| WordPress hosting | Local WordPress is the private editing environment and source of rendered content, not the public host. |
| Public hosting | GitHub Pages. GitHub documents that Pages logs visitor IP addresses for security. |
| Analytics | None. |
| Social media | Instagram content is fetched server-to-server and copied into the static build. There are no visitor-side Instagram or Facebook embed scripts. |
| Newsletter | None. The optional WPForms communications choice is not connected to a newsletter or marketing automation service. |

The public GitHub Pages response was also checked and did not set a cookie.
Because the site has no consent-based analytics, advertising, embeds, or
visitor forms in the static build, no cookie banner was added.

Provider and authority references:

- [GitHub Pages data collection](https://docs.github.com/en/pages/getting-started-with-github-pages/what-is-github-pages#data-collection)
- [GitHub privacy and international transfers](https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement)
- [Rights of a data subject](https://tietosuoja.fi/rekisteroidyn-oikeudet)
- [Submitting a matter to the Finnish Data Protection Ombudsman](https://tietosuoja.fi/ilmoitus-tietosuojavaltuutetulle)

## Privacy source and update rule

The `/tietosuoja/` page contains `[lks_privacy_page]`. Its integration-specific
renderer is:

```text
wp-content/themes/lakeuden-kauppaseura/inc/privacy-page.php
```

The page title, lead, update date, and public controller contact details use
the existing **Sivujen tekstit** option. Structural legal text stays in source
because it must change together with the implementation it describes.

The notice must be reviewed whenever a public form, event registration
provider, mail service, spam provider, analytics product, social embed,
newsletter, host, or international processor changes.

## Legal and production launch blockers

TODO(lks-privacy-legal-review): the association must approve the final notice.
This repository does not replace legal advice. Before enabling the WordPress
membership form, the association must:

1. Replace the Local `admin_email` recipient with an approved association
   mailbox and verify the WPForms notification recipient.
2. Select and test an authenticated production mail-delivery service, then add
   its legal entity, processing role, location, retention, subprocessors, and
   transfer mechanism to the notice.
3. Decide and document who may access membership enquiries and who deletes
   them.
4. Confirm whether the retention criteria in the notice require fixed
   organization-specific time limits.
5. Confirm how optional communications consent is recorded, withdrawn, and
   removed. It must remain optional.
6. Review any external event-registration provider before its first link is
   published and provide that provider's privacy notice next to the action.
7. Recheck GitHub's current data-processing and transfer terms.
8. Record board approval of the controller identity, legal bases, recipients,
   retention, security wording, rights procedure, and update date.

The production form must remain disabled until these items and the existing
delivery checklist pass.

## Central production URL

Canonical, Open Graph, sitemap, robots, schema, 404-page, validator, and
publisher output use:

```text
tools/site-config.json
```

The current value is the existing GitHub Pages project URL. During a custom
domain migration:

1. Change `productionUrl` once in `tools/site-config.json`.
2. Configure the custom domain and HTTPS in GitHub Pages.
3. Export and run both validators.
4. Search the generated build for the old hostname.
5. Publish only after canonical, Open Graph, sitemap, and JSON-LD URLs all use
   the new production domain.

Do not put credentials or Local filesystem paths in this public JSON file.

## JSON-LD graph

All custom JSON-LD is generated by:

```text
wp-content/themes/lakeuden-kauppaseura/inc/structured-data.php
```

No SEO plugin is active in the audited installation. One `@graph` script is
emitted per indexable page to prevent duplicate Organization or article data.

| Page | Types |
| --- | --- |
| Homepage | `Organization`, `WebSite`, `WebPage`, `BreadcrumbList` |
| Normal page and archive landing page | `Organization`, `WebSite`, `WebPage`, `BreadcrumbList` |
| Jäseneksi | Base types plus `FAQPage` on the same page node; unresolved facts are omitted |
| Blog article | Base types plus `BlogPosting` |
| Event page | Base types plus `Event` |

Event schema uses the normal post title, editor description, featured image,
and confirmed event meta. Missing optional values are omitted. A date without
a start time remains a date rather than inventing midnight. Time values use
the `Europe/Helsinki` timezone. Cancelled, postponed, and rescheduled values
map to Schema.org URLs; offers are removed from cancelled, past, or
registration-closed events.

`[VAHVISTETAAN]`, other launch placeholders, unconfirmed locations, arbitrary
performers, and meaningless offers are never emitted.

## Validation

With Local WordPress running:

```powershell
php tools/export-static.php
php tools/validate-static.php
php tools/validate-structured-data.php
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\tools\test-accessibility-browser.ps1
```

`validate-structured-data.php` does not modify the database. It tests
complete, incomplete, cancelled, postponed, and rescheduled event fixtures in
memory, then validates representative generated homepage, membership, blog,
events archive, and event pages.

The general static validator also requires:

- one centralized valid JSON-LD graph per indexable page;
- matching canonical and Open Graph URLs;
- canonical URLs under the configured production base;
- Organization, WebSite, WebPage, and BreadcrumbList;
- BlogPosting and Event required properties when those nodes are present;
- no Local URLs or unresolved placeholders in JSON-LD;
- no old GitHub hostname if `productionUrl` has been changed to another host.

**Do not manually edit exported index.html files. They are regenerated from WordPress and will be overwritten.**
