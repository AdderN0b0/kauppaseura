# Membership information

## Source of truth

Membership facts use the existing `lakeuden_kauppaseura_page_copy` WordPress
option. Their canonical field definitions are in:

```text
wp-content/themes/lakeuden-kauppaseura/inc/page-copy.php
```

The Jäseneksi and Meistä pages read those fields through
`lakeuden_kauppaseura_membership_facts()`. Their shared HTML renderer is
`lakeuden_kauppaseura_render_membership_facts()`. Do not repeat membership
values in block templates, shortcode markup, documentation, or generated HTML.

No additional plugin is required for the facts.

## Editing in WordPress

1. Start the Local WordPress site.
2. Sign in to WordPress administration.
3. Open **Sivujen tekstit**.
4. Select **Jäsenyystiedot**.
5. Replace every `[VAHVISTETAAN]` value with confirmed information.
6. Save with **Tallenna tekstit**.
7. Review the dedicated page at `/jaseneksi/` and its contextual summary at
   `/meista/#jasenyys`.

The administration screen lists every unresolved field. The membership email
must be a public, valid email address before the call-to-action becomes an
email link; until then, the button safely points to the Yhteystiedot page.

## Full-content checklist

Confirm all of the following in WordPress:

- annual membership fee;
- joining fee or confirmation that none is charged;
- membership type;
- who may apply;
- current-member nomination requirement;
- what membership includes;
- approximate annual event count;
- whether selected events are open to non-members;
- approval process;

Run the existing export and validation process:

```powershell
.\publish-github-pages.cmd -Message "Update confirmed membership information"
```

Unresolved membership facts are omitted from the public Jäseneksi and Meistä
pages until they are confirmed. The publisher runs `tools/validate-static.php`
before touching `gh-pages`; validation fails if a placeholder leaks into any
generated HTML. A production-ready export must report:

```text
0 unresolved membership facts
0 unpublished placeholders
Validation passed with zero errors.
```

Developer TODO markers can be reviewed with:

```powershell
rg "TODO\(lks-membership-launch\)" wp-content/themes/lakeuden-kauppaseura/inc/page-copy.php
```

**Do not manually edit exported index.html files. They are regenerated from WordPress and will be overwritten.**
