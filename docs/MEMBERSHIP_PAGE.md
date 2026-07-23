# Jäseneksi page

## Architecture

The `/jaseneksi/` route is a WordPress page whose content is:

```text
[lks_membership_page]
```

The shortcode, reusable membership-fact renderer, FAQ, and form fallback are
implemented in:

```text
wp-content/themes/lakeuden-kauppaseura/inc/membership-page.php
```

Page copy uses the existing `lakeuden_kauppaseura_page_copy` option. In
WordPress administration, open **Sivujen tekstit → Jäseneksi**. Practical
membership facts are intentionally not repeated there; edit them under
**Sivujen tekstit → Jäsenyystiedot**.

The three testimonial cards are structured WordPress records under
**Jäsenkokemukset** and are rendered by:

```text
wp-content/themes/lakeuden-kauppaseura/inc/people.php
```

Replacement instructions are in `docs/PEOPLE_COMPONENTS.md`.

The page is created and the existing WPForms form is configured by:

```powershell
php tools/apply-membership-page.php
```

The script stores the actual WPForms post ID in `join_form_id`. The theme does
not depend on a hardcoded form ID.

## Form dependency and delivery safety

The audited site already uses WPForms Lite 1.10.1.1, so no new form plugin was
added. The configured form contains:

- name and email;
- optional telephone;
- organization, role, and municipality;
- a free-text interest field;
- preferred contact method;
- required privacy consent;
- separate optional communications consent.

WPForms provides accessible labels, required markers, field validation,
confirmation state, AJAX submission, and its built-in modern anti-spam token.

WPForms Lite does not store entries locally. Its own administration notice
warns that an email-delivery failure can lose an entry when Entry Backups are
not active. For that reason, the production form is hidden behind the
`join_form_ready` setting. It is rendered automatically in a Local,
development, or staging environment, but not in production until the setting
is enabled.

Before enabling **Lomake on tuotantovalmis**:

1. Confirm the notification recipient in **WPForms → Kaikki lomakkeet → Liity
   mukaan → Asetukset → Ilmoitukset**.
2. Configure authenticated SMTP or another verified WordPress mail transport.
3. Send a test through a non-production environment and confirm both the
   Finnish success state and receipt in the intended mailbox.
4. Decide whether to enable WPForms Lite Connect Entry Backups or upgrade to a
   WPForms edition that stores entries locally. If Lite Connect is used,
   review its remote processing and one-year backup expiry in the privacy
   notice first.
5. Confirm the anti-spam option and test an invalid submission.
6. Complete the privacy retention and access text under **Sivujen tekstit →
   Tietosuoja**.
7. Enable **Sivujen tekstit → Jäseneksi → Lomake on tuotantovalmis**.

Until then, visitors see an explicit contact fallback. The fallback contains
no form and never pretends that a message was submitted.

## Static GitHub Pages behavior

GitHub Pages cannot execute WordPress or WPForms. During export,
`tools/export-static.php` removes the dynamic form between its source markers
before collecting assets and reveals the safe preview fallback. The validator
requires `/jaseneksi/index.html` to contain exactly one visible fallback and
no live form.

**Do not manually edit exported index.html files. They are regenerated from WordPress and will be overwritten.**

## Full-content and form blockers

Temporary testimonial cards remain visible in the development preview so their
layout can be reviewed. The static validator blocks production publication
until every placeholder has been replaced.

The complete page and production form still require:

- every `[VAHVISTETAAN]` membership fact is confirmed;
- the privacy retention/access field is confirmed;
- all three testimonial records use approved names, quotes, and any optional
  organization, professional role, portrait, and profile link;
- the form-delivery checklist above is complete.

Developer markers:

```powershell
rg "TODO\(lks-membership-(launch|privacy)\)|TODO\(lks-people-launch\)" wp-content/themes/lakeuden-kauppaseura
```

Run the preview and validator with:

```powershell
php tools/export-static.php
php tools/validate-static.php
```

The validator must report zero unresolved rendered fields, zero temporary
rendered testimonials, and zero unpublished placeholders before publication.
