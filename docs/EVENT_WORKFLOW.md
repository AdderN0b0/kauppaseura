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
8. Valitse **Tapahtuma vaatii ilmoittautumisen** vain, jos osallistujan täytyy
   ilmoittautua etukäteen. Tavallisessa tapahtumassa valinta jätetään tyhjäksi.
9. Jos ilmoittautuminen on jo avattu, liitä ulkoisen palvelun osoite kohtaan
   **Ilmoittautumislinkki**.
10. Jos ilmoittautumisella on viimeinen päivä, valitse se kohdasta
   **Ilmoittautuminen päättyy**. Jos kohta jää tyhjäksi, linkki pysyy avoinna
   tapahtumapäivään asti.
11. Jos tapahtuma peruuntuu, valitse **Tapahtuma on peruttu**. Muuta
    teknistä tilaa ei tarvitse valita.
12. Esikatsele tapahtuma, valitse **Julkaise** tai **Päivitä**, ja tarkista
    tapahtumasivu.
13. Julkaise staattinen sivusto yhdellä komennolla:

    ```powershell
    .\publish-github-pages.cmd -Message "Päivitä tapahtumat"
    ```

## Mitä kävijä näkee

Julkinen tila päätellään aina tässä järjestyksessä:

1. Peruutusvalinta näyttää **Tapahtuma on peruttu** eikä ilmoittautumistoimintoa.
2. Mennyt tapahtumapäivä näyttää **Tapahtuma on päättynyt** ja tapahtuma näkyy
   menneissä tapahtumissa.
3. Jos ilmoittautumista ei vaadita, sivulla ei näytetä ilmoittautumislaatikkoa
   eikä ilmoittautumisen tilamerkintää.
4. Voimassa oleva ulkoinen linkki näyttää painikkeen **Ilmoittaudu
   tapahtumaan**.
5. Ohitettu määräpäivä näyttää **Ilmoittautuminen on päättynyt** ilman
   painiketta.
6. Kun ilmoittautuminen on vaadittu mutta ulkoista linkkiä ei vielä ole,
   näytetään **Ilmoittautumisohjeet julkaistaan myöhemmin** ilman painiketta.

Tavallinen ilmoittautumisen tekstikenttä on vain lisätietoa. Se ei muutu
linkiksi eikä lähetä tietoja.

## Tekniset kentät

Kolme ylläpitäjän uutta kenttää tallennetaan tapahtuman omaan metatietoon:

| Hallinnan kenttä | Post meta |
| --- | --- |
| Tapahtuma vaatii ilmoittautumisen | `_lks_event_registration_required` |
| Ilmoittautumislinkki | `_lks_event_registration_url` |
| Ilmoittautuminen päättyy | `_lks_event_registration_deadline` |
| Tapahtuma on peruttu | `_lks_event_cancelled` |

Versio 1.2 lisää vanhoille tapahtumille ilmoittautumisvalinnan kerran:
voimassa oleva ulkoinen ilmoittautumislinkki ottaa valinnan käyttöön, muut
tapahtumat jäävät pois käytöstä. Siirto ei muuta tapahtuman otsikkoa, kuvausta,
päivää tai muuta toimituksellista sisältöä.

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

Ensimmäinen tarkistus käyttää testitapauksia eikä luo tapahtumia tai muuta
niiden toimituksellista sisältöä. Pluginin ensimmäinen lataus voi täydentää
vanhoille tapahtumille yllä kuvatun yhden boolean-valinnan.

**Do not manually edit exported index.html files. They are regenerated from WordPress and will be overwritten.**
