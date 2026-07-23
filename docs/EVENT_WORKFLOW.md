# Tapahtumien yksinkertainen julkaisu

Tapahtumien ylläpito on tarkoituksella pieni. WordPressissa ei ole omaa
ilmoittautumisjärjestelmää, osallistujarekisteriä, paikkamäärää, jonotuslistaa
eikä kalenterikytkentää. Ilmoittautuminen tapahtuu aina ylläpitäjän antamassa
ulkoisessa palvelussa.

## Tavallinen ylläpitäjän työnkulku

1. Käynnistä Lakeuden Kauppaseura Local-sovelluksessa.
2. Avaa WordPressin hallinnassa **Tapahtumat → Lisää uusi**.
3. Kirjoita tapahtuman nimi otsikkoon.
4. Kirjoita kuvaus tavallisella sisältöeditorilla.
5. Valitse halutessasi **Artikkelikuva**.
6. Täytä sivupalkin **Tapahtuman tiedot** -ruudusta vähintään **Päivä**.
7. Täytä tarvittaessa kellonaika, paikka, paikkakunta, yleisö, hinta ja lyhyt
   ilmoittautumisen lisätieto.
8. Jos ilmoittautuminen on jo avattu, liitä ulkoisen palvelun osoite kohtaan
   **Ilmoittautumislinkki**.
9. Jos ilmoittautumisella on viimeinen päivä, valitse se kohdasta
   **Ilmoittautuminen päättyy**. Jos kohta jää tyhjäksi, linkki pysyy avoinna
   tapahtumapäivään asti.
10. Jos tapahtuma peruuntuu, valitse **Tapahtuma on peruttu**. Muuta
    teknistä tilaa ei tarvitse valita.
11. Esikatsele tapahtuma, valitse **Julkaise** tai **Päivitä**, ja tarkista
    tapahtumasivu.
12. Julkaise staattinen sivusto yhdellä komennolla:

    ```powershell
    .\publish-github-pages.cmd -Message "Päivitä tapahtumat"
    ```

## Mitä kävijä näkee

Julkinen tila päätellään aina tässä järjestyksessä:

1. Peruutusvalinta näyttää **Tapahtuma on peruttu** eikä ilmoittautumistoimintoa.
2. Mennyt tapahtumapäivä näyttää **Tapahtuma on päättynyt** ja tapahtuma näkyy
   menneissä tapahtumissa.
3. Voimassa oleva ulkoinen linkki näyttää painikkeen **Ilmoittaudu
   tapahtumaan**.
4. Ohitettu määräpäivä näyttää **Ilmoittautuminen on päättynyt** ilman
   painiketta.
5. Ilman ulkoista linkkiä näytetään **Lisätiedot ja ilmoittautuminen julkaistaan
   myöhemmin**. Jos Sivujen tekstit -asetuksissa on julkinen yhdistyksen
   sähköposti, sivu näyttää lisäksi valmiiksi otsikoidun sähköpostilinkin.

Tavallinen ilmoittautumisen tekstikenttä on vain lisätietoa. Se ei muutu
linkiksi eikä lähetä tietoja.

## Tekniset kentät

Kolme ylläpitäjän uutta kenttää tallennetaan tapahtuman omaan metatietoon:

| Hallinnan kenttä | Post meta |
| --- | --- |
| Ilmoittautumislinkki | `_lks_event_registration_url` |
| Ilmoittautuminen päättyy | `_lks_event_registration_deadline` |
| Tapahtuma on peruttu | `_lks_event_cancelled` |

Vanha `_lks_event_status` säilyy tietokannassa vanhojen tietojen
yhteensopivuutta varten, mutta sitä ei näytetä tapahtumaeditorissa eikä käytetä
julkisen ilmoittautumistilan valintaan.

Event JSON-LD muodostetaan automaattisesti samoista tiedoista. Tuntemattomat
ajat, paikat, kuvat ja tarjoukset jätetään pois. Peruttu, mennyt tai
ilmoittautumiseltaan suljettu tapahtuma ei tuota Offer-tietoa.

## Tarkistus ilman tietokantamuutoksia

```powershell
php tools/validate-event-workflow.php
php tools/export-static.php
php tools/validate-static.php
php tools/validate-structured-data.php
```

Ensimmäinen tarkistus käyttää vain muistissa olevia testitapauksia eikä luo
tapahtumia tai muuta WordPressin sisältöä.

**Do not manually edit exported index.html files. They are regenerated from WordPress and will be overwritten.**
