# Saavutettavuuden julkaisutarkistus

Sivuston saavutettavuus toteutetaan WordPress-lähteessä. Vietyjä
`index.html`-tiedostoja ei korjata käsin.

## Kuvien vaihtoehtoiset tekstit

1. Avaa kuva WordPressin Mediakirjastossa.
2. Kirjoita **Vaihtoehtoinen teksti** -kenttään lyhyt kuvaus siitä, mitä
   kävijän on olennaista tietää kuvasta.
3. Älä aloita tekstillä ”kuva” tai ”kuva henkilöstä”.
4. Älä käytä tiedostonimeä.
5. Jätä vaihtoehtoinen teksti tyhjäksi vain, jos kuva on aidosti koriste.
   Teeman koodissa koristekuva merkitään lisäksi `aria-hidden="true"`-tilaan.

Blogi- ja tapahtuma-artikkelien pääkuvat käyttävät ensisijaisesti
Mediakirjaston vaihtoehtoista tekstiä. Jos se puuttuu tai vastaa tiedostonimeä,
teema muodostaa turvallisen tekstin artikkelin tai tapahtuman nimestä.

Kirjoittajien, hallituksen jäsenten ja jäsenkokemusten muotokuvat käyttävät
Mediakirjaston vaihtoehtoista tekstiä. Nimestä ja roolista muodostetaan
varateksti vain, jos mediakirjaston tieto puuttuu. Nimikirjainkuvake on
koristeellinen, koska henkilön nimi näkyy sen vieressä.

Instagram-kuvan vaihtoehtoinen teksti muodostuu julkaisun kuvatekstistä.
Jos kuvateksti puuttuu, käytetään yleistä suomenkielistä
Instagram-julkaisu- tai Instagram-video-tekstiä.

## Ennen julkaisua

- Jokaisella sivulla on yksi sisältöä kuvaava H1.
- Sivun otsikot etenevät H1–H2–H3 ilman tasojen ohittamista.
- Jokaisen uuden sisältökuvan vaihtoehtoinen teksti tarkistetaan
  Mediakirjastossa.
- Lomake kokeillaan tyhjänä, virheellisellä sähköpostilla ja hyväksytyillä
  tiedoilla.
- Mobiilivalikko kokeillaan näppäimistöllä: avaus, Sarkain,
  Vaihto+Sarkain ja Escape.
- Kaikki linkit, painikkeet ja lomakekentät näyttävät selvän
  näppäimistökohdistuksen.
- Sivua kokeillaan käyttöjärjestelmän vähennetyn liikkeen asetuksella.
- Tekstin ja taustan kontrasti tarkistetaan, jos teeman värejä muutetaan.

## Automaattinen tarkistus

Käynnistä paikallinen WordPress ja aja:

```powershell
php tools/export-static.php
php tools/validate-static.php
powershell.exe -NoProfile -ExecutionPolicy Bypass -File tools/test-accessibility-browser.ps1
```

Staattinen tarkistin ilmoittaa muun muassa puuttuvan suomen kielen, H1- ja
otsikkovirheet, nimeämättömät linkit ja painikkeet, puuttuvat lomaketunnisteet,
virheelliset `time`-elementit, tiedostoniminä käytetyt vaihtoehtoiset tekstit
sekä tyhjät alt-tekstit, joita ei ole merkitty koristeellisiksi.

Tarkistin toimii myös julkaisun sisältöporttina. Se voi siksi epäonnistua
saavutettavuusvirheiden lisäksi keskeneräisiin jäsenyys-, hallitus- tai
jäsenkokemustietoihin. Kaikki virheet on ratkaistava ennen tuotantojulkaisua.

Selainkoe tarvitsee käynnissä olevan paikallisen WordPressin ja Microsoft
Edgen. Se tarkistaa mobiilivalikon näppäimistökäytön ja kohdistuksen palautuksen,
lomakkeen virhe- ja onnistumistilat, vähennetyn liikkeen, kosketuskohteet,
artikkelinavigoinnin sekä tapahtumalinkit.
