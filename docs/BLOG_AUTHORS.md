# Blogikirjoittajat ja kuvat

Blogikirjoittajia hallitaan WordPressissa **Artikkelit → Kirjoittajat**
-näkymässä. Kirjoittaja on `lks_author`-taksonomian termi, ja kuva tallennetaan
termin `lks_author_photo_id`-metatietoon. Artikkeliin valitaan yksi tai useampi
kirjoittaja tavallisesta Artikkeli-editorista.

## Nimen tai kuvan muuttaminen

1. Käynnistä paikallinen WordPress.
2. Avaa **Artikkelit → Kirjoittajat**.
3. Avaa oikea kirjoittaja.
4. Korjaa nimi tarvittaessa.
5. Valitse **Kirjoittajan kuva** mediakirjastosta.
6. Lisää mediakirjastossa kuvan vaihtoehtoiseksi tekstiksi henkilön nimi.
7. Tallenna ja esikatsele artikkeli, johon kirjoittaja on liitetty.

Jos kuvaa ei ole, julkinen sivu näyttää neutraalin nimikirjainkuvakkeen. Kuvaa
ei pidä korvata satunnaisella tai henkilöllisyydeltään epävarmalla kuvalla.

## Tarkistetun alkuaineiston tuonti

Nykyisten kuuden kirjoittajan tarkistetut kuvat voi liittää idempotentilla
komennolla:

```powershell
php tools/import-blog-author-portraits.php --confirm-publication-permission --liisa-file="C:\polku\liisa-ojala.png"
```

Liisa Ojalan kuva annetaan paikallisena tiedostona, koska se on yhdistyksen
edustajan itse toimittama kuva eikä verkkosivulta kopioitava aineisto. Työkalu
ei tallenna paikallista absoluuttista polkua WordPressiin.

Työkalu:

- korjaa vanhan `Anssi Murto` -termin muotoon **Anssi Murtonen**;
- käyttää Heikki Kankaan nykyistä blogikuvaa;
- käyttää Maarit Siikin ja Paula Takamaan jo tuotuja hallituskuvia;
- tuo Anssi Murtosen kuvan Pohjanmaan Kokoomuksen julkaisusta;
- tuo Martti Kaunismäen kuvan Aluetaito Oy:n yhteystietosivulta;
- tuo käyttäjän toimittaman Liisa Ojalan kuvan;
- asettaa jokaiselle kuvalle henkilön nimen vaihtoehtoiseksi tekstiksi;
- tallentaa lähde- ja käyttöoikeusmuistion yksityiseen liitemetaan;
- säilyttää myöhemmin WordPressissa käsin valitun kuvan.

`--confirm-publication-permission` tarkoittaa, että yhdistys on tarkistanut
sekä henkilön suostumuksen että valokuvan uudelleenjulkaisuoikeuden. Pelkkä
kuvan julkinen saatavuus verkossa ei ole uudelleenjulkaisulupa. Säilytä
alkuperäiset kuvat ja lupatiedot yhdistyksen yksityisessä media-arkistossa.
`wp-content/uploads/` on tarkoituksella Git-versionhallinnan ulkopuolella.

Staattista HTML:ää ei muokata käsin. Vie ja tarkista sivusto vasta WordPress-
muutosten jälkeen:

```powershell
php tools/export-static.php
php tools/validate-static.php
```

**Do not manually edit exported index.html files. They are regenerated from WordPress and will be overwritten.**
