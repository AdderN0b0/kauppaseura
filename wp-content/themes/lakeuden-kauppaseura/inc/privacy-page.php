<?php
/**
 * Implementation-specific Finnish privacy notice.
 *
 * Keep this text aligned with the integrations audited in
 * docs/PRIVACY_AND_STRUCTURED_DATA.md. Do not add a service here merely
 * because it may be used later.
 *
 * @package Lakeuden_Kauppaseura
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the privacy page.
 *
 * TODO(lks-privacy-legal-review): The association must approve the controller
 * details, legal bases, retention criteria, processors, and transfer wording
 * before the production membership form is enabled.
 *
 * @return string
 */
function lakeuden_kauppaseura_render_privacy_page() {
	$email        = sanitize_email( lakeuden_kauppaseura_copy( 'contact_email' ) );
	$phone        = lakeuden_kauppaseura_copy( 'contact_phone' );
	$phone_link   = preg_replace( '/[^\d+]/', '', lakeuden_kauppaseura_copy( 'contact_phone_link' ) );
	$address      = lakeuden_kauppaseura_copy( 'contact_address' );
	$updated      = lakeuden_kauppaseura_copy( 'privacy_updated' );
	$privacy_url  = lakeuden_kauppaseura_canonical_url();

	ob_start();
	?>
	<div id="main" class="lks-page lks-privacy-page" role="main">
		<section class="lks-subpage-hero" aria-labelledby="lks-privacy-title">
			<div class="lks-page-shell">
				<p class="lks-kicker lks-kicker--light"><?php echo esc_html( lakeuden_kauppaseura_copy( 'privacy_hero_kicker' ) ); ?></p>
				<h1 id="lks-privacy-title"><?php echo esc_html( lakeuden_kauppaseura_copy( 'privacy_hero_title' ) ); ?></h1>
				<p><?php echo esc_html( lakeuden_kauppaseura_copy( 'privacy_hero_text' ) ); ?></p>
			</div>
		</section>

		<section class="lks-page-section lks-privacy-content" aria-labelledby="lks-privacy-controller">
			<div class="lks-page-shell lks-reading-width">
				<p class="lks-privacy-updated" data-lks-legal-review="required"><strong><?php echo esc_html( $updated ); ?></strong></p>

				<h2 id="lks-privacy-controller">1. Rekisterinpitäjä ja yhteystiedot</h2>
				<p><strong>Lakeuden Kauppaseura ry</strong><br /><?php echo nl2br( esc_html( $address ) ); ?></p>
				<p>
					Tietosuojaa sekä tietojen tarkastamista, oikaisemista ja poistamista koskevat pyynnöt:
					<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>,
					<a href="<?php echo esc_url( 'tel:' . $phone_link ); ?>"><?php echo esc_html( $phone ); ?></a>.
				</p>

				<h2>2. Mitä tietoja käsitellään</h2>
				<h3>Jäsenyyskiinnostus</h3>
				<p>
					WordPress-versiossa on WPForms Lite -lomake jäsenyydestä kiinnostuneille. Lomakkeella voidaan kerätä nimi,
					sähköpostiosoite, vapaaehtoinen puhelinnumero, organisaatio, rooli, kunta, vapaamuotoinen kiinnostuksen
					kuvaus, toivottu yhteydenottotapa, tieto tietosuojaselosteeseen tutustumisesta sekä erillinen vapaaehtoinen
					viestintäsuostumus.
				</p>
				<div class="lks-privacy-callout">
					<p>
						Nykyisessä GitHub Pages -julkaisussa jäsenyyslomake on korvattu yhteydenottolinkillä. Staattinen sivusto
						ei lähetä eikä tallenna jäsenyyslomakkeen tietoja. WordPress-lomake pidetään tuotannossa poissa käytöstä,
						kunnes vastaanottaja, sähköpostin toimitus, säilytys ja testilähetys on vahvistettu.
					</p>
				</div>

				<h3>Tapahtumiin ilmoittautuminen</h3>
				<p>
					Sivusto ei ylläpidä omaa tapahtumailmoittautumisrekisteriä. Jos tapahtumasivulla käytetään ulkoista
					ilmoittautumislinkkiä, tiedot annetaan kyseisen palvelun omalla sivulla. Ulkoinen palvelu kertoo ennen
					lähetystä omasta rekisterinpitäjästään, kerättävistä tiedoista, säilytyksestä ja mahdollisista siirroista.
					Lakeuden Kauppaseura käsittelee palvelusta saamiaan osallistujatietoja vain tapahtuman järjestämiseen.
				</p>

				<h3>Tapahtumailmoitusten tilaus</h3>
				<p>
					Sivustolla ei ole tapahtumakohtaista ilmoitus- tai muistutuslomaketta eikä ilmoitustilaajien rekisteriä.
				</p>

				<h3>Sähköposti- ja puhelinyhteydenotot</h3>
				<p>
					Käsittelemme lähettäjän yhteystiedot ja viestin sisällön, kun henkilö ottaa yhdistykseen yhteyttä
					sähköpostilla tai puhelimitse.
				</p>

				<h2>3. Käyttötarkoitukset ja oikeusperusteet</h2>
				<ul>
					<li>
						Jäsenyyskiinnostuksen käsittelyn tarkoitus on vastata henkilön pyyntöön, arvioida jäsenyyden
						edellytyksiä ja hoitaa mahdollisen jäsenyyssuhteen valmistelua. Käsittely perustuu henkilön pyynnöstä
						tehtäviin toimiin sekä yhdistyksen oikeutettuun etuun hoitaa jäsenhankintaa ja yhteydenottoja.
					</li>
					<li>
						Tapahtuman osallistujatietoja käytetään tapahtuman järjestämiseen, osallistujaviestintään ja
						tapahtumaan liittyvien velvoitteiden hoitamiseen. Ulkoisen ilmoittautumispalvelun oikeusperusteet
						kuvataan sen omassa tietosuojaselosteessa.
					</li>
					<li>
						Muihin yhteydenottoihin vastaaminen perustuu yhdistyksen oikeutettuun etuun hoitaa toimintaansa ja
						yhteydenpitoaan.
					</li>
					<li>
						<strong>Vapaaehtoinen toiminta- ja tapahtumaviestintä perustuu erilliseen suostumukseen.</strong>
						Suostumuksen antaminen ei ole jäsenyyskyselyn tai tapahtumailmoittautumisen edellytys, ja sen voi
						peruuttaa milloin tahansa ottamalla yhteyttä rekisterinpitäjään.
					</li>
				</ul>

				<h2>4. Tietolähteet</h2>
				<p>
					Tiedot saadaan henkilöltä itseltään lomakkeella, sähköpostilla, puhelimitse tai ulkoisessa
					ilmoittautumispalvelussa. Emme täydennä jäsenyyskiinnostuksen tietoja ulkopuolisista markkinointi- tai
					profilointirekistereistä.
				</p>

				<h2>5. Vastaanottajat ja palveluntarjoajat</h2>
				<ul>
					<li>
						Tietoja käsittelevät vain ne Lakeuden Kauppaseuran hallituksen tai toiminnan henkilöt, jotka tarvitsevat
						niitä jäsenyysasian, tapahtuman tai yhteydenoton hoitamiseen.
					</li>
					<li>
						Julkinen sivusto toimitetaan GitHub Pages -palvelusta. GitHub kirjaa sivukäynnin IP-osoitteen
						tietoturvatarkoituksiin.
					</li>
					<li>
						Sisällönhallinta on yhdistyksen paikallisessa WordPress-ympäristössä. WPForms Lite ei tässä
						kokoonpanossa tallenna lomakevastauksia WordPressiin, eikä WPForms Lite Connect -varmuuskopiointia ole
						otettu käyttöön.
					</li>
					<li>
						Tuotannon sähköpostipalvelua ei ole vielä valittu. Jäsenyyslomaketta ei saa ottaa tuotantokäyttöön
						ennen kuin toimituspalvelu ja vastaanottaja on vahvistettu ja lisätty tähän selosteeseen.
					</li>
					<li>
						WPFormsin oma roskapostisuoja on käytössä WordPress-esikatselussa. Ulkoista reCAPTCHA-, hCaptcha- tai
						Turnstile-palvelua ei ole määritetty.
					</li>
					<li>
						Instagram-sisältö noudetaan palvelimelta ja kuvat tallennetaan staattiseen julkaisuun. Sivulla ei ole
						Instagramin tai Facebookin upotusskriptejä. Sosiaalisen median linkin avaamiseen sovelletaan kyseisen
						palvelun tietosuojakäytäntöä.
					</li>
				</ul>
				<p>
					Sivustolla ei ole analytiikkaa, mainonnan seurantaa, uutiskirjepalvelua tai automaattista
					markkinointijärjestelmää.
				</p>

				<h2>6. Tietojen siirrot Euroopan talousalueen ulkopuolelle</h2>
				<p>
					GitHub voi käsitellä sivuston teknisiä käyttötietoja Euroopan talousalueella, Yhdysvalloissa ja muissa
					toimintamaissaan. GitHub kertoo käyttävänsä kansainvälisissä siirroissa muun muassa EU:n
					vakiolausekkeita ja EU–Yhdysvallat-tietosuojakehystä. Mahdollisen ulkoisen tapahtumapalvelun siirrot
					tarkistetaan palvelun omasta selosteesta ennen käyttöönottoa. WordPress-lomakkeen tietoja ei siirretä
					ulkopuoliseen varmuuskopio- tai roskapostipalveluun tässä kokoonpanossa.
				</p>

				<h2>7. Säilytysajat</h2>
				<ul>
					<li>
						Nykyinen staattinen jäsenyyssivu ei tallenna lomaketietoja. Kun tuotantolomake myöhemmin otetaan
						käyttöön, WPForms Lite lähettää vastauksen sähköpostiin eikä tallenna sitä WordPressiin.
					</li>
					<li>
						Jäsenyys- ja muut yhteydenotot säilytetään vain asian käsittelyn ja tarpeellisen seurannan ajan ja
						poistetaan, kun käsittelylle ei enää ole käyttötarkoitusta tai lakisääteistä perustetta.
					</li>
					<li>
						Vapaaehtoiseen viestintään liittyvä suostumus säilytetään, kunnes se peruutetaan tai viestinnän
						tarkoitus päättyy.
					</li>
					<li>
						Tapahtuman osallistujatietojen säilytysaika ilmoitetaan käytettävän ilmoittautumispalvelun
						tietosuojaselosteessa ja vahvistetaan tapahtumakohtaisesti.
					</li>
					<li>
						GitHub määrittää GitHub Pages -palvelun tietoturvalokien säilytyksen omassa tietosuojakäytännössään.
					</li>
				</ul>
				<p data-lks-legal-review="required">
					Yhdistyksen on ennen jäsenyyslomakkeen tuotantokäyttöä hyväksyttävä käytännön poistovastuu ja arvioitava,
					tarvitaanko yllä olevien säilytysperusteiden lisäksi kiinteitä määräaikoja.
				</p>

				<h2>8. Tietoturvan periaatteet</h2>
				<p>
					Tietoja kerätään vain tarpeelliseen tarkoitukseen. Julkinen sivusto käyttää HTTPS-yhteyttä. WordPress ja
					sen tietokanta eivät ole julkisen GitHub Pages -sivuston yhteydessä. Pääsy WordPressiin, sähköpostiin ja
					mahdollisiin ilmoittautumistietoihin rajataan tehtävän perusteella, ja palvelut, ohjelmistot sekä
					käyttöoikeudet pidetään ajan tasalla. Luottamuksellisia tai tarpeettomia henkilötietoja ei julkaista
					staattisessa viennissä.
				</p>

				<h2>9. Rekisteröidyn oikeudet</h2>
				<p>
					Soveltuvissa tilanteissa henkilöllä on oikeus saada tietoa käsittelystä, tarkastaa omat tietonsa, pyytää
					virheellisten tietojen oikaisua tai tietojen poistamista, rajoittaa tai vastustaa käsittelyä, saada
					suostumukseen tai sopimukseen perustuvat tiedot siirretyksi sekä peruuttaa suostumus. Kaikki oikeudet
					eivät sovellu jokaiseen käsittelytilanteeseen.
				</p>
				<p>
					Lähetä tarkastus-, oikaisu- tai poistopyyntö osoitteeseen
					<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>. Kerro
					pyynnössä, mitä yhteydenottoa, jäsenyysasiaa tai tapahtumaa pyyntö koskee. Rekisterinpitäjä voi pyytää
					tarpeellisia lisätietoja henkilöllisyyden varmistamiseksi.
				</p>

				<h2>10. Oikeus tehdä valitus</h2>
				<p>
					Jos henkilö katsoo, että henkilötietoja on käsitelty lainvastaisesti, hänellä on oikeus tehdä ilmoitus
					Tietosuojavaltuutetun toimistolle. Ensin kannattaa olla yhteydessä Lakeuden Kauppaseuraan, jotta asia
					voidaan selvittää.
				</p>
				<p>
					<a href="https://tietosuoja.fi/ilmoitus-tietosuojavaltuutetulle" target="_blank" rel="noopener noreferrer">
						Ilmoitus Tietosuojavaltuutetun toimistolle
						<span class="screen-reader-text">(avautuu uuteen välilehteen)</span>
					</a><br />
					Postiosoite: PL 800, 00531 Helsinki<br />
					Puhelinvaihde: 029 566 6700
				</p>

				<h2>11. Evästeet ja muutokset</h2>
				<p>
					Nykyinen staattinen sivusto ei aseta Lakeuden Kauppaseuran omia evästeitä eikä käytä
					suostumusta edellyttävää analytiikkaa tai seurantaa. Siksi sivustolle ei ole lisätty evästebanneria.
					WordPressin välttämättömät kirjautumisevästeet koskevat vain ylläpitäjiä paikallisessa
					sisällönhallintaympäristössä.
				</p>
				<p>
					Seloste päivitetään ennen kuin käyttöön otetaan uusi julkinen lomake, sähköpostipalvelu,
					roskapostipalvelu, analytiikka, upotus, uutiskirje tai muu henkilötietoja käsittelevä integraatio.
					Ajantasainen seloste on osoitteessa
					<a href="<?php echo esc_url( $privacy_url ); ?>"><?php echo esc_html( $privacy_url ); ?></a>.
				</p>
			</div>
		</section>
	</div>
	<?php

	return (string) preg_replace( '/>\s+</', '><', (string) ob_get_clean() );
}
add_shortcode( 'lks_privacy_page', 'lakeuden_kauppaseura_render_privacy_page' );
