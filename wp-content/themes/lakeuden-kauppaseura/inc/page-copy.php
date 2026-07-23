<?php
/**
 * Safe, page-specific copy controls for the WordPress admin.
 *
 * @package Lakeuden_Kauppaseura
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the editable page-copy schema.
 *
 * @return array<string,array<string,mixed>>
 */
function lakeuden_kauppaseura_page_copy_schema() {
	return array(
		'home'    => array(
			'title'       => 'Etusivu',
			'description' => 'Etusivun esittelytekstit. Tapahtuman, blogin ja Instagramin sisältö päivittyvät omista näkymistään.',
			'fields'      => array(
				'home_hero_kicker'       => array( 'label' => 'Pääkuvan pieni otsikko', 'default' => 'Lakeuden Kauppaseura ry' ),
				'home_hero_title'        => array( 'label' => 'Pääotsikko', 'type' => 'textarea', 'rows' => 2, 'default' => 'Verkosto Etelä-Pohjanmaan elinvoiman rakentajille.' ),
				'home_hero_lead'         => array( 'label' => 'Pääkuvan esittelyteksti', 'type' => 'textarea', 'default' => 'Lakeuden Kauppaseura kokoaa yrittäjät, asiantuntijat ja alueen kehittämisestä kiinnostuneet ihmiset keskusteluihin, yritysvierailuille ja uusiin yhteistyösuhteisiin.' ),
				'home_hero_event_link'   => array( 'label' => 'Tapahtumapainikkeen teksti', 'default' => 'Katso seuraava tapahtuma' ),
				'home_hero_member_link'  => array( 'label' => 'Jäsenyyslinkin teksti', 'default' => 'Näin liityt jäseneksi' ),
				'home_event_kicker'      => array( 'label' => 'Seuraava tapahtuma: pieni otsikko', 'default' => 'Seuraava kohtaaminen' ),
				'home_event_title'       => array( 'label' => 'Seuraava tapahtuma: otsikko', 'default' => 'Tule mukaan.' ),
				'home_event_link'        => array( 'label' => 'Tapahtumakortin linkki', 'default' => 'Katso tiedot' ),
				'home_event_empty'       => array( 'label' => 'Teksti, kun tulevia tapahtumia ei ole', 'default' => 'Tulevia tapahtumia ei ole juuri nyt.' ),
				'home_values_kicker'     => array( 'label' => 'Hyödyt: pieni otsikko', 'default' => 'Mitä saat toiminnasta' ),
				'home_values_title'      => array( 'label' => 'Hyödyt: otsikko', 'type' => 'textarea', 'rows' => 2, 'default' => 'Yhteyksiä, tietoa ja uusia näkökulmia.' ),
				'home_value_1_title'     => array( 'label' => 'Hyöty 1: otsikko', 'default' => 'Ajankohtaiset keskustelut' ),
				'home_value_1_text'      => array( 'label' => 'Hyöty 1: teksti', 'type' => 'textarea', 'default' => 'Kuulet alueen yrityksiin ja elinvoimaan vaikuttavista aiheista.' ),
				'home_value_2_title'     => array( 'label' => 'Hyöty 2: otsikko', 'default' => 'Uudet yhteydet' ),
				'home_value_2_text'      => array( 'label' => 'Hyöty 2: teksti', 'type' => 'textarea', 'default' => 'Tapaat yrittäjiä, asiantuntijoita ja muita Etelä-Pohjanmaan kehittämisestä kiinnostuneita.' ),
				'home_value_3_title'     => array( 'label' => 'Hyöty 3: otsikko', 'default' => 'Yritysvierailut ja kohtaamiset' ),
				'home_value_3_text'      => array( 'label' => 'Hyöty 3: teksti', 'type' => 'textarea', 'default' => 'Näet alueen toimintaa läheltä ja voit ehdottaa uusia aiheita sekä vierailukohteita.' ),
				'home_intro_kicker'      => array( 'label' => 'Kauppaseuran esittely: pieni otsikko', 'default' => 'Mikä Kauppaseura on' ),
				'home_intro_title'       => array( 'label' => 'Kauppaseuran esittely: otsikko', 'default' => 'Saman pöydän ääreen.' ),
				'home_intro_text'        => array( 'label' => 'Kauppaseuran esittely: teksti', 'type' => 'textarea', 'rows' => 5, 'default' => 'Lakeuden Kauppaseura on jäsenyhdistys ja keskusteluverkosto, joka edistää Etelä-Pohjanmaan elinvoimaa. Mukaan voidaan hyväksyä hyvämaineinen henkilö, rekisteröity yritys tai muu oikeuskelpoinen yhteisö, joka hyväksyy yhdistyksen tarkoituksen ja säännöt.' ),
				'home_intro_link'        => array( 'label' => 'Kauppaseuran esittely: linkki', 'default' => 'Lue jäsenyydestä' ),
				'home_blog_kicker'       => array( 'label' => 'Uusin blogi: pieni otsikko', 'default' => 'Uusin blogista' ),
				'home_blog_title'        => array( 'label' => 'Uusin blogi: otsikko', 'default' => 'Ajankohtainen puheenvuoro.' ),
				'home_blog_link'         => array( 'label' => 'Uusin blogi: linkki', 'default' => 'Lue kirjoitus' ),
				'home_instagram_title'   => array( 'label' => 'Instagram-osion otsikko', 'default' => 'Instagram' ),
				'home_instagram_handle'  => array( 'label' => 'Instagram-käyttäjänimi', 'default' => '@lakeudenkauppaseura' ),
				'home_join_kicker'       => array( 'label' => 'Sivun lopun jäsenyysosio: pieni otsikko', 'default' => 'Tervetuloa seuraan' ),
				'home_join_title'        => array( 'label' => 'Sivun lopun jäsenyysosio: otsikko', 'type' => 'textarea', 'rows' => 2, 'help' => 'Rivinvaihto näkyy otsikossa rivinvaihtona.', 'default' => "Tule mukaan.\nJatketaan keskustelua." ),
				'home_join_text'         => array( 'label' => 'Sivun lopun jäsenyysosio: teksti', 'type' => 'textarea', 'default' => 'Tutustu jäsenyyden hyötyihin ja vaiheisiin tai ota yhteyttä, jos haluat ehdottaa aihetta tai vierailukohdetta.' ),
				'home_join_button'       => array( 'label' => 'Sivun lopun jäsenyyspainike', 'default' => 'Näin liityt jäseneksi' ),
			),
		),
		'about'   => array(
			'title'       => 'Meistä',
			'description' => 'Yhdistyksen esittely, toimintatapa ja jäsenyyden yleiset ohjeet.',
			'fields'      => array(
				'about_hero_kicker'       => array( 'label' => 'Sivun pieni otsikko', 'default' => 'Meistä · Lakeuden Kauppaseura ry' ),
				'about_hero_title'        => array( 'label' => 'Pääotsikko', 'type' => 'textarea', 'rows' => 2, 'help' => 'Rivinvaihto näkyy otsikossa rivinvaihtona.', 'default' => "Lakeuden\nyhteinen pöytä." ),
				'about_hero_text_1'       => array( 'label' => 'Johdanto 1', 'type' => 'textarea', 'default' => 'Lakeuden Kauppaseura kokoaa yhteen Etelä-Pohjanmaan elinvoimasta kiinnostuneet ihmiset ja yritykset.' ),
				'about_hero_text_2'       => array( 'label' => 'Johdanto 2', 'type' => 'textarea', 'default' => 'Toiminnan ytimessä ovat verkostot, keskustelu ja ajankohtaiset aiheet.' ),
				'about_hero_link'         => array( 'label' => 'Johdannon linkki', 'default' => 'Tutustu toimintaamme' ),
				'about_purpose_kicker'    => array( 'label' => 'Tarkoitus: pieni otsikko', 'default' => 'Miksi olemme olemassa' ),
				'about_purpose_title'     => array( 'label' => 'Tarkoitus: otsikko', 'default' => 'Elinvoimaa rakennetaan yhdessä.' ),
				'about_purpose_text_1'    => array( 'label' => 'Tarkoitus: kappale 1', 'type' => 'textarea', 'default' => 'Yhdistyksen nimi on Lakeuden Kauppaseura ry ja kotipaikka on Seinäjoki.' ),
				'about_purpose_text_2'    => array( 'label' => 'Tarkoitus: kappale 2', 'type' => 'textarea', 'default' => 'Seuran tarkoituksena on jäsentensä kautta edistää ja vaikuttaa elinkeinoelämän yleisten toimintaedellytysten ja -ympäristön parantamiseen Etelä-Pohjanmaan alueella.' ),
				'about_purpose_text_3'    => array( 'label' => 'Tarkoitus: kappale 3', 'type' => 'textarea', 'default' => 'Seura toimii keskustelunavaajana ja aloitteentekijänä sekä toiminta-alueensa elinkeinoelämän yhtenä edunvalvojana.' ),
				'about_role_1_title'      => array( 'label' => 'Rooli 1: otsikko', 'default' => 'Keskustelunavaaja' ),
				'about_role_1_text'       => array( 'label' => 'Rooli 1: teksti', 'type' => 'textarea', 'default' => 'Nostamme pöydälle alueen ajankohtaiset aiheet ja erilaiset näkökulmat.' ),
				'about_role_2_title'      => array( 'label' => 'Rooli 2: otsikko', 'default' => 'Aloitteentekijä' ),
				'about_role_2_text'       => array( 'label' => 'Rooli 2: teksti', 'type' => 'textarea', 'default' => 'Luomme tilaa uusille ajatuksille, kohtaamisille ja yhteisille avauksille.' ),
				'about_role_3_title'      => array( 'label' => 'Rooli 3: otsikko', 'default' => 'Edunvalvoja' ),
				'about_role_3_text'       => array( 'label' => 'Rooli 3: teksti', 'type' => 'textarea', 'default' => 'Pidämme Etelä-Pohjanmaan elinkeinoelämän toimintaedellytyksiä esillä.' ),
				'about_action_kicker'     => array( 'label' => 'Toiminta: pieni otsikko', 'default' => 'Miten toimimme' ),
				'about_action_title'      => array( 'label' => 'Toiminta: otsikko', 'default' => 'Asiaa, kokemuksia ja hyvää seuraa.' ),
				'about_action_text'       => array( 'label' => 'Toiminta: esittelyteksti', 'type' => 'textarea', 'rows' => 5, 'default' => 'Järjestämme kokouksia, keskustelutilaisuuksia, koulutuksia ja tutustumiskäyntejä Suomessa ja kansainvälisesti. Lisäksi tuemme jäsenten hyvinvointia, yhteistä virkistäytymistä ja tiedon jakamista.' ),
				'about_action_items'      => array( 'label' => 'Toimintamuodot', 'type' => 'textarea', 'rows' => 5, 'help' => 'Kirjoita yksi kohta kullekin riville. Numerointi syntyy automaattisesti.', 'default' => "Kokoukset ja keskustelut\nKoulutukset\nTutustumiskäynnit\nHyvinvointi ja yhdessäolo" ),
				'about_member_kicker'     => array( 'label' => 'Jäsenyys: pieni otsikko', 'default' => 'Jäsenyys' ),
				'about_member_title'      => array( 'label' => 'Jäsenyys: otsikko', 'default' => 'Seura on ihmisiä varten.' ),
				'about_pin_kicker'        => array( 'label' => 'Jäsenmerkki: pieni otsikko', 'default' => 'Jäsenmerkki' ),
				'about_pin_title'         => array( 'label' => 'Jäsenmerkki: otsikko', 'default' => 'Merkki kuulumisesta' ),
				'about_pin_text'          => array( 'label' => 'Jäsenmerkki: esittelyteksti', 'type' => 'textarea', 'rows' => 4, 'default' => 'Jokainen uusi jäsen saa Lakeuden Kauppaseuran pinssin. LK viittaa seuran nimeen, ja alaosan säteittäinen peltokuvio kuvaa Etelä-Pohjanmaan lakeuksia.' ),
				'about_member_text'       => array( 'label' => 'Jäsenyys: esittelyteksti', 'type' => 'textarea', 'rows' => 6, 'default' => 'Lakeuden Kauppaseuran jäseneksi voidaan hyväksyä hyvämaineinen henkilö, rekisteröity yritys tai muu oikeuskelpoinen yhteisö, joka on kiinnostunut alueen elinvoimasta ja yhteiskunnallisista asioista sekä hyväksyy yhdistyksen tarkoituksen ja säännöt.' ),
				'about_member_benefits'   => array( 'label' => 'Jäsenyyden hyödyt', 'type' => 'textarea', 'rows' => 5, 'help' => 'Kirjoita yksi kohta kullekin riville.', 'default' => "Saat tietoa seuran tapahtumista ja ajankohtaisista keskusteluista.\nTapaat alueen yrittäjiä, asiantuntijoita ja muita elinvoimasta kiinnostuneita.\nVoit ehdottaa keskusteluaiheita, yritysvierailuja ja muuta toimintaa." ),
				'about_member_steps_title'=> array( 'label' => 'Jäsenyyden vaiheet: otsikko', 'default' => 'Näin jäsenyys etenee' ),
				'about_member_steps'      => array( 'label' => 'Jäsenyyden vaiheet', 'type' => 'textarea', 'rows' => 5, 'help' => 'Kirjoita yksi vaihe kullekin riville. Numerointi syntyy automaattisesti.', 'default' => "Ota yhteyttä ja kerro lyhyesti kiinnostuksestasi.\nSeuran nykyinen jäsen tekee jäsenesityksen.\nSeuran hallitus käsittelee ja hyväksyy uudet varsinaiset jäsenet." ),
				'about_member_note'       => array( 'label' => 'Jäsenyyden lisätieto', 'type' => 'textarea', 'default' => 'Etkö tunne vielä seuran jäsentä? Ota silti yhteyttä. Kerromme, miten voit tutustua toimintaan ja edetä jäsenyysasiassa.' ),
				'about_member_fee_note'   => array( 'label' => 'Jäsenmaksun lisätieto', 'type' => 'textarea', 'default' => 'Pyydä ajantasainen jäsenmaksu ja käsittelyaikataulu sähköpostilla.' ),
				'about_member_button'     => array( 'label' => 'Jäsenyyspainike', 'default' => 'Kysy jäsenyydestä' ),
				'about_closing_kicker'    => array( 'label' => 'Lopetus: pieni otsikko', 'default' => 'Katse eteenpäin' ),
				'about_closing_quote'     => array( 'label' => 'Lopetus: nosto', 'type' => 'textarea', 'rows' => 4, 'default' => 'Lakeuden Kauppaseura käy aktiivista keskustelua alueen elinkeinoelämän kanssa ja vahvistaa jatkuvasti mahdollisuuksiaan vaikuttaa.' ),
				'about_closing_link'      => array( 'label' => 'Lopetus: tapahtumalinkki', 'default' => 'Näe, missä tapaamme seuraavaksi' ),
			),
		),
		'events'  => array(
			'title'       => 'Tapahtumat',
			'description' => 'Tapahtumasivun yleiset esittelytekstit. Tapahtumien nimet, kuvaukset ja ajankohdat muokataan Tapahtumat-näkymässä.',
			'fields'      => array(
				'events_hero_kicker'      => array( 'label' => 'Sivun pieni otsikko', 'default' => 'Tapahtumat · Lakeuden Kauppaseura' ),
				'events_hero_title'       => array( 'label' => 'Pääotsikko', 'default' => 'Seura vailla vertaa.' ),
				'events_hero_text'        => array( 'label' => 'Johdanto', 'type' => 'textarea', 'default' => 'Ajankohtaisia puheenvuoroja, yritysvierailuja ja iltoja, joista lähdet uusien ajatusten ja tuttavuuksien kanssa.' ),
				'events_hero_button'      => array( 'label' => 'Johdannon painike', 'default' => 'Katso tulevat tapahtumat' ),
				'events_intro_kicker'     => array( 'label' => 'Esittely: pieni otsikko', 'default' => 'Kohtaamisia pitkin vuotta' ),
				'events_intro_title'      => array( 'label' => 'Esittely: otsikko', 'default' => 'Enemmän kuin merkintä kalenterissa.' ),
				'events_intro_text'       => array( 'label' => 'Esittely: teksti', 'type' => 'textarea', 'rows' => 5, 'default' => 'Tuomme yhteen alueen tekijät, kiinnostavat paikat ja puheenaiheet. Jokainen tapahtuma on mahdollisuus oppia jotakin uutta ja löytää ihminen, jonka kanssa keskustelu jatkuu vielä kotiinlähdön jälkeen.' ),
				'events_intro_types'      => array( 'label' => 'Tapahtumamuodot', 'type' => 'textarea', 'rows' => 4, 'help' => 'Kirjoita yksi kohta kullekin riville.', 'default' => "Yritysvierailut\nAjankohtaiset keskustelut\nYhteiset juhlat" ),
				'events_upcoming_kicker'  => array( 'label' => 'Tulevat: pieni otsikko', 'default' => 'Nähdään siellä' ),
				'events_upcoming_title'   => array( 'label' => 'Tulevat: otsikko', 'default' => 'Tulevat' ),
				'events_upcoming_note'    => array( 'label' => 'Tulevat: lisäteksti', 'default' => 'Poimi seuraava kohtaaminen kalenteriisi.' ),
				'events_upcoming_empty'   => array( 'label' => 'Teksti, kun tulevia tapahtumia ei ole', 'type' => 'textarea', 'default' => 'Tulevia tapahtumia ei ole juuri nyt. Seuraa Instagramiamme – julkaisemme uudet kohtaamiset siellä ensimmäisten joukossa.' ),
				'events_past_kicker'      => array( 'label' => 'Menneet: pieni otsikko', 'default' => 'Missä olemme tavanneet' ),
				'events_past_title'       => array( 'label' => 'Menneet: otsikko', 'default' => 'Menneet' ),
				'events_instagram_link'   => array( 'label' => 'Menneet: Instagram-linkki', 'default' => 'Lisää tunnelmia Instagramissa' ),
				'events_past_empty'       => array( 'label' => 'Teksti, kun menneitä tapahtumia ei ole', 'default' => 'Menneitä tapahtumia ei ole vielä lisätty.' ),
				'events_cta_kicker'       => array( 'label' => 'Lopetus: pieni otsikko', 'default' => 'Tervetuloa mukaan' ),
				'events_cta_title'        => array( 'label' => 'Lopetus: otsikko', 'type' => 'textarea', 'rows' => 2, 'default' => 'Seuraava hyvä keskustelu voi alkaa sinusta.' ),
				'events_cta_text'         => array( 'label' => 'Lopetus: teksti', 'type' => 'textarea', 'default' => 'Kiinnostuitko tapahtumista, jäsenyydestä tai haluatko ehdottaa vierailukohdetta? Laita meille viestiä.' ),
				'events_cta_button'       => array( 'label' => 'Lopetus: painike', 'default' => 'Ota yhteyttä' ),
			),
		),
		'blog'    => array(
			'title'       => 'Blogi',
			'description' => 'Blogisivun yleinen johdanto. Kirjoitukset lisätään ja muokataan Blogit-näkymässä.',
			'fields'      => array(
				'blog_hero_kicker' => array( 'label' => 'Sivun pieni otsikko', 'default' => 'Näkökulmia Lakeudelta' ),
				'blog_hero_title'  => array( 'label' => 'Pääotsikko', 'type' => 'textarea', 'rows' => 2, 'help' => 'Rivinvaihto näkyy otsikossa rivinvaihtona.', 'default' => "Ajatuksia,\njotka vievät eteenpäin." ),
				'blog_hero_text'   => array( 'label' => 'Johdanto', 'type' => 'textarea', 'default' => 'Puheenvuoroja yrittäjyydestä, alueen elinvoimasta ja asioista, joista Etelä-Pohjanmaalla kannattaa keskustella.' ),
				'blog_empty'       => array( 'label' => 'Teksti, kun kirjoituksia ei ole', 'default' => 'Ensimmäinen kirjoitus on tulossa pian.' ),
			),
		),
		'contact' => array(
			'title'       => 'Yhteystiedot',
			'description' => 'Yhteydenottosivun tekstit ja julkiset yhteystiedot.',
			'fields'      => array(
				'contact_hero_kicker'    => array( 'label' => 'Sivun pieni otsikko', 'default' => 'Ota yhteyttä · Lakeuden Kauppaseura' ),
				'contact_hero_title'     => array( 'label' => 'Pääotsikko', 'type' => 'textarea', 'rows' => 2, 'help' => 'Rivinvaihto näkyy otsikossa rivinvaihtona.', 'default' => "Pidetään\nyhteyttä." ),
				'contact_hero_text'      => array( 'label' => 'Johdanto', 'type' => 'textarea', 'help' => 'Rivinvaihdot näkyvät tekstissä.', 'default' => "Kiinnostuitko jäsenyydestä tai toiminnasta?\nLaita viestiä tai soita – jutellaan lisää." ),
				'contact_hero_link'      => array( 'label' => 'Johdannon linkki', 'default' => 'Katso yhteystiedot' ),
				'contact_details_kicker' => array( 'label' => 'Yhteystiedot: pieni otsikko', 'default' => 'Tavoitat meidät' ),
				'contact_details_title'  => array( 'label' => 'Yhteystiedot: otsikko', 'type' => 'textarea', 'rows' => 2, 'default' => 'Ota yhteyttä sähköpostilla tai puhelimitse.' ),
				'contact_details_text'   => array( 'label' => 'Yhteystiedot: esittelyteksti', 'type' => 'textarea', 'default' => 'Vastaamme mielellämme jäsenyyteen, toimintaan ja tapahtumiin liittyviin kysymyksiin.' ),
				'contact_email'          => array( 'label' => 'Sähköpostiosoite', 'type' => 'email', 'default' => 'Aunike62@gmail.com' ),
				'contact_phone'          => array( 'label' => 'Puhelinnumero', 'default' => '050 966 5627' ),
				'contact_phone_link'     => array( 'label' => 'Puhelinnumero linkkiä varten', 'help' => 'Käytä kansainvälistä muotoa ilman välilyöntejä, esimerkiksi +358509665627.', 'default' => '+358509665627' ),
				'contact_address'        => array( 'label' => 'Postiosoite', 'type' => 'textarea', 'rows' => 3, 'help' => 'Rivinvaihdot näkyvät osoitteessa.', 'default' => "Pölkkytie 13\nSeinäjoki" ),
				'contact_social_kicker'  => array( 'label' => 'Sosiaalinen media: pieni otsikko', 'default' => 'Seuraa toimintaa' ),
				'contact_social_title'   => array( 'label' => 'Sosiaalinen media: otsikko', 'default' => 'Nähdään myös somessa.' ),
			),
		),
		'privacy' => array(
			'title'       => 'Tietosuoja',
			'description' => 'Tietosuojaselosteen sisältö. Tarkista teksti aina, jos sivustolle lisätään uusia lomakkeita, seurantaa tai ulkoisia palveluita.',
			'fields'      => array(
				'privacy_hero_kicker'     => array( 'label' => 'Sivun pieni otsikko', 'default' => 'Tietosuoja' ),
				'privacy_hero_title'      => array( 'label' => 'Pääotsikko', 'default' => 'Tietosuojaseloste.' ),
				'privacy_hero_text'       => array( 'label' => 'Johdanto', 'type' => 'textarea', 'default' => 'Tällä sivulla kerromme, miten julkinen verkkosivusto toimii henkilötietojen näkökulmasta.' ),
				'privacy_owner_title'     => array( 'label' => 'Ylläpitäjä: otsikko', 'default' => 'Sivuston ylläpitäjä' ),
				'privacy_owner_text'      => array( 'label' => 'Ylläpitäjä: teksti', 'type' => 'textarea', 'rows' => 4, 'help' => 'Merkintä {email} korvataan automaattisesti Yhteystiedot-sivulle tallennetulla sähköpostilinkillä.', 'default' => 'Sivuston omistaja ja rekisterinpitäjä on Lakeuden Kauppaseura ry. Tietosuojaa koskevissa kysymyksissä voit ottaa yhteyttä osoitteeseen {email}.' ),
				'privacy_activity_title'  => array( 'label' => 'Sivuston toiminta: otsikko', 'default' => 'Mitä sivusto tekee' ),
				'privacy_activity_text_1' => array( 'label' => 'Sivuston toiminta: kappale 1', 'type' => 'textarea', 'rows' => 4, 'default' => 'Julkisella sivustolla ei ole käyttäjätilejä, analytiikkaa, seurantaskriptejä tai toimivaa yhteydenottolomaketta tämän selosteen päivityshetkellä. Sivusto ei itsessään kerää yhteydenottojen sisältöä.' ),
				'privacy_activity_text_2' => array( 'label' => 'Sivuston toiminta: kappale 2', 'type' => 'textarea', 'rows' => 4, 'default' => 'Kun otat yhteyttä sähköpostilla tai puhelimitse, yhteydenpito tapahtuu oman sähköposti- tai puhelinpalvelusi ja vastaanottajan palvelun kautta verkkosivuston ulkopuolella.' ),
				'privacy_external_title'  => array( 'label' => 'Ulkoiset palvelut: otsikko', 'default' => 'Ulkoiset palvelut' ),
				'privacy_external_text'   => array( 'label' => 'Ulkoiset palvelut: teksti', 'type' => 'textarea', 'rows' => 5, 'default' => 'Sivustolta on linkkejä Instagramiin, Facebookiin ja vanhan Wix-sivuston lähdeaineistoihin. Sivusto julkaistaan GitHub Pages -palvelussa. Nämä ulkoiset palvelut voivat käsitellä teknisiä tietoja omien ehtojensa ja tietosuojakäytäntöjensä mukaisesti, kun käytät niiden palveluja.' ),
				'privacy_updated'         => array( 'label' => 'Päivityspäivä', 'default' => 'Päivitetty 20.7.2026' ),
			),
		),
	);
}

/**
 * Return one piece of editable copy, falling back to the current site text.
 *
 * @param string $key Copy key.
 * @return string
 */
function lakeuden_kauppaseura_copy( $key ) {
	static $saved = null;

	if ( null === $saved ) {
		$saved = get_option( 'lakeuden_kauppaseura_page_copy', array() );
		$saved = is_array( $saved ) ? $saved : array();
	}

	if ( array_key_exists( $key, $saved ) ) {
		return (string) $saved[ $key ];
	}

	foreach ( lakeuden_kauppaseura_page_copy_schema() as $section ) {
		if ( isset( $section['fields'][ $key ]['default'] ) ) {
			return (string) $section['fields'][ $key ]['default'];
		}
	}

	return '';
}

/**
 * Return a newline-separated admin field as a clean list.
 *
 * @param string $key Copy key.
 * @return string[]
 */
function lakeuden_kauppaseura_copy_list( $key ) {
	$items = preg_split( '/\R/u', lakeuden_kauppaseura_copy( $key ) );
	$items = is_array( $items ) ? array_map( 'trim', $items ) : array();

	return array_values( array_filter( $items, 'strlen' ) );
}

/**
 * Sanitize the copy settings according to the registered schema.
 *
 * @param mixed $input Submitted settings.
 * @return array<string,string>
 */
function lakeuden_kauppaseura_sanitize_page_copy( $input ) {
	$input = is_array( $input ) ? $input : array();
	$clean = array();

	foreach ( lakeuden_kauppaseura_page_copy_schema() as $section ) {
		foreach ( $section['fields'] as $key => $field ) {
			$value = array_key_exists( $key, $input )
				? wp_unslash( $input[ $key ] )
				: ( $field['default'] ?? '' );
			if ( 'email' === ( $field['type'] ?? 'text' ) ) {
				$clean[ $key ] = sanitize_email( $value );
			} elseif ( 'textarea' === ( $field['type'] ?? 'text' ) ) {
				$clean[ $key ] = sanitize_textarea_field( $value );
			} else {
				$clean[ $key ] = sanitize_text_field( $value );
			}
		}
	}

	return $clean;
}

/**
 * Register the copy option and its admin menu.
 */
function lakeuden_kauppaseura_register_page_copy_setting() {
	register_setting(
		'lakeuden_kauppaseura_page_copy',
		'lakeuden_kauppaseura_page_copy',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'lakeuden_kauppaseura_sanitize_page_copy',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'lakeuden_kauppaseura_register_page_copy_setting' );

/**
 * Add the copy editor to the main WordPress admin menu.
 */
function lakeuden_kauppaseura_add_page_copy_menu() {
	add_menu_page(
		'Sivujen tekstit',
		'Sivujen tekstit',
		'manage_options',
		'lakeuden-kauppaseura-page-copy',
		'lakeuden_kauppaseura_render_page_copy_admin',
		'dashicons-edit-page',
		21
	);
}
add_action( 'admin_menu', 'lakeuden_kauppaseura_add_page_copy_menu' );

/**
 * Render the page-copy settings screen.
 */
function lakeuden_kauppaseura_render_page_copy_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$schema = lakeuden_kauppaseura_page_copy_schema();
	?>
	<div class="wrap lks-copy-admin">
		<h1>Sivujen tekstit</h1>
		<p class="description">Muokkaa sivujen yleisiä tekstejä tässä. Sivujen rakenne ja ulkoasu pysyvät suojattuina.</p>
		<div class="notice notice-info inline">
			<p><strong>Blogit:</strong> Blogit-valikossa. <strong>Tapahtumat:</strong> Tapahtumat-valikossa. Päivämäärät, laskurit ja julkaisumäärät muodostuvat automaattisesti.</p>
		</div>
		<nav class="nav-tab-wrapper lks-copy-admin__tabs" aria-label="Sivut">
			<?php foreach ( $schema as $slug => $section ) : ?>
				<a class="nav-tab" href="#<?php echo esc_attr( 'lks-copy-' . $slug ); ?>"><?php echo esc_html( $section['title'] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<form action="options.php" method="post">
			<?php settings_fields( 'lakeuden_kauppaseura_page_copy' ); ?>
			<div class="lks-copy-admin__sections">
				<?php foreach ( $schema as $slug => $section ) : ?>
					<section id="<?php echo esc_attr( 'lks-copy-' . $slug ); ?>" class="lks-copy-admin__section">
						<header>
							<h2><?php echo esc_html( $section['title'] ); ?></h2>
							<p><?php echo esc_html( $section['description'] ); ?></p>
						</header>
						<div class="lks-copy-admin__fields">
							<?php foreach ( $section['fields'] as $key => $field ) : ?>
								<?php $value = lakeuden_kauppaseura_copy( $key ); ?>
								<div class="lks-copy-admin__field">
									<label for="<?php echo esc_attr( 'lks-copy-' . $key ); ?>"><strong><?php echo esc_html( $field['label'] ); ?></strong></label>
									<?php if ( 'textarea' === ( $field['type'] ?? 'text' ) ) : ?>
										<textarea class="large-text" id="<?php echo esc_attr( 'lks-copy-' . $key ); ?>" name="lakeuden_kauppaseura_page_copy[<?php echo esc_attr( $key ); ?>]" rows="<?php echo esc_attr( (string) ( $field['rows'] ?? 3 ) ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
									<?php else : ?>
										<input class="regular-text" id="<?php echo esc_attr( 'lks-copy-' . $key ); ?>" name="lakeuden_kauppaseura_page_copy[<?php echo esc_attr( $key ); ?>]" type="<?php echo esc_attr( $field['type'] ?? 'text' ); ?>" value="<?php echo esc_attr( $value ); ?>" />
									<?php endif; ?>
									<?php if ( ! empty( $field['help'] ) ) : ?><p class="description"><?php echo esc_html( $field['help'] ); ?></p><?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endforeach; ?>
			</div>
			<?php submit_button( 'Tallenna tekstit' ); ?>
		</form>
	</div>
	<style>
		.lks-copy-admin { max-width: 1120px; }
		.lks-copy-admin__tabs { position: sticky; top: 32px; z-index: 20; padding-top: 8px; background: #f0f0f1; }
		.lks-copy-admin__section { scroll-margin-top: 100px; margin: 24px 0; padding: 24px; border: 1px solid #dcdcde; border-radius: 4px; background: #fff; }
		.lks-copy-admin__section > header { margin: -24px -24px 24px; padding: 20px 24px; border-bottom: 1px solid #dcdcde; background: #f6f7f7; }
		.lks-copy-admin__section > header h2 { margin: 0 0 6px; font-size: 20px; }
		.lks-copy-admin__section > header p { margin: 0; max-width: 760px; }
		.lks-copy-admin__fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 22px 28px; }
		.lks-copy-admin__field { display: flex; flex-direction: column; gap: 7px; }
		.lks-copy-admin__field .regular-text { width: 100%; max-width: none; }
		.lks-copy-admin__field textarea { min-height: 76px; resize: vertical; }
		@media (max-width: 782px) {
			.lks-copy-admin__tabs { top: 46px; overflow-x: auto; white-space: nowrap; }
			.lks-copy-admin__fields { grid-template-columns: 1fr; }
		}
	</style>
	<?php
}
