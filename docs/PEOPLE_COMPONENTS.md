# Board members and member testimonials

## What editors manage

The theme adds two simple, non-public WordPress content areas:

- **Hallitus** for eight board-member cards on the Meistä page;
- **Jäsenkokemukset** for the three testimonial cards on the Jäseneksi page.

These records are not separate public pages and are excluded from search. No
external plugin is required.

Each record uses familiar WordPress fields:

| WordPress field | Hallitus | Jäsenkokemukset |
| --- | --- | --- |
| Title | Name | Name |
| Content editor | Short introduction | Approved quote |
| Featured Image / Muotokuva | Portrait | Portrait |
| Compact details panel | Board role, organization/title, optional contacts, order | Organization, professional role, optional profile link, order |

The eight board names and responsibilities were restored from the former
`about_board_members` source default. No organization, introduction, portrait,
email, or telephone was inferred from those entries.

The components are implemented in
`wp-content/themes/lakeuden-kauppaseura/inc/people.php`. The theme queries
published records by their display order instead of hardcoding people in page
templates.

## Replacing a board placeholder

1. Start the Local WordPress site and sign in.
2. Open **Hallitus → Kaikki hallituksen jäsenet**.
3. Open a record marked **Kesken**.
4. Replace `[NIMI LISÄTÄÄN]` in the title with the approved public name.
5. Replace `[LYHYT ESITTELY LISÄTÄÄN]` in the content editor with a short
   approved introduction.
6. In **Hallituksen jäsenen tiedot**, replace the temporary board role and
   organization or professional title.
7. Set **Näyttöjärjestys**. The smallest number is shown first.
8. Optionally select a portrait in **Muotokuva**. The theme creates meaningful
   alternative text from the approved name, board role, and organization/title.
   If no portrait is selected, the site creates an accessible initials avatar
   automatically.
9. Add email or telephone only when the person has approved publication.
   Contact values remain hidden unless the corresponding **Näytä … julkisella
   sivulla** checkbox is selected.
10. Select **Päivitä** and preview `/meista/`.

Organization/title, portrait, email, and telephone may be left empty. The card
layout remains balanced.

## Replacing a testimonial placeholder

1. Open **Jäsenkokemukset → Kaikki jäsenkokemukset**.
2. Open a record marked **Kesken**.
3. Replace `[NIMI LISÄTÄÄN]` in the title.
4. Replace `[JÄSENEN KOMMENTTI LISÄTÄÄN ENNEN JULKAISUA]` in the content
   editor with a quote approved by that person.
5. Replace `[ORGANISAATIO LISÄTÄÄN]`, or leave the organization empty when it
   should not be shown.
6. Replace or remove the temporary professional role.
7. Optionally add an approved profile URL.
8. Set **Näyttöjärjestys**. The Jäseneksi page shows the first three published
   records.
9. Optionally select an approved portrait. Without one, the initials avatar is
   used.
10. Select **Päivitä** and preview `/jaseneksi/`.

## Development placeholders

Restore the eight editable board records and create the three testimonial
development records on a restored or fresh database:

```powershell
php tools/apply-people-placeholders.php
```

The command is idempotent. It reuses the first three unedited board placeholder
slots, creates the other five board records and three testimonial cards, and
never overwrites later administrator edits.

TODO marker:

```text
TODO(lks-people-launch)
```

The static validator rejects:

- every board or testimonial card still marked as temporary;
- any rendered `[... LISÄTÄÄN ...]` content;
- a Jäseneksi page without exactly three testimonial cards;
- a Meistä page without the board section and exactly eight board cards.

Run before publication:

```powershell
php tools/export-static.php
php tools/validate-static.php
```

Production publication must wait until all temporary people content has been
replaced. A missing portrait is not a launch blocker.

**Do not manually edit exported index.html files. They are regenerated from WordPress and will be overwritten.**
