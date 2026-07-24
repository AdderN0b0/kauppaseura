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
 * Return the marker used for membership information awaiting confirmation.
 *
 * @return string
 */
function lakeuden_kauppaseura_membership_placeholder() {
	return '[VAHVISTETAAN]';
}

/**
 * Return the canonical membership-fact schema.
 *
 * The label, display order, edit control and default value for each fact live
 * here so the admin screen and visitor-facing membership section cannot drift.
 *
 * @return array<string,array<string,mixed>>
 */
function lakeuden_kauppaseura_membership_facts_schema() {
	$placeholder = lakeuden_kauppaseura_membership_placeholder();

	return array(
		'membership_annual_fee' => array(
			'label'           => 'Vuosittainen jäsenmaksu',
			'default'         => $placeholder,
			'launch_required' => true,
			'todo'            => 'TODO(lks-membership-launch): Vahvista vuosittainen jäsenmaksu.',
		),
		'membership_joining_fee' => array(
			'label'           => 'Liittymismaksu',
			'default'         => $placeholder,
			'launch_required' => true,
			'todo'            => 'TODO(lks-membership-launch): Vahvista liittymismaksu tai ettei sitä peritä.',
		),
		'membership_type' => array(
			'label'           => 'Jäsenyyden muoto',
			'default'         => 'Varsinaiseksi jäseneksi voidaan valita henkilö, rekisteröity yritys tai muu oikeuskelpoinen yhteisö.',
			'launch_required' => true,
		),
		'membership_eligibility' => array(
			'label'           => 'Kuka voi hakea jäseneksi',
			'type'            => 'textarea',
			'rows'            => 5,
			'default'         => 'Lakeuden Kauppaseuran jäseneksi voidaan hyväksyä hyvämaineinen henkilö, rekisteröity yritys tai muu oikeuskelpoinen yhteisö, joka on kiinnostunut alueen elinvoimasta ja yhteiskunnallisista asioista sekä hyväksyy yhdistyksen tarkoituksen ja säännöt.',
			'launch_required' => true,
		),
		'membership_nomination' => array(
			'label'           => 'Tarvitaanko nykyisen jäsenen esitys',
			'type'            => 'textarea',
			'rows'            => 4,
			'default'         => 'Kyllä. Seuran nykyinen jäsen tekee jäsenesityksen. Jos et tunne vielä seuran jäsentä, ota silti yhteyttä ja kysy, miten voit edetä jäsenyysasiassa.',
			'launch_required' => true,
		),
		'membership_includes' => array(
			'label'           => 'Mitä jäsenyyteen kuuluu',
			'type'            => 'textarea',
			'rows'            => 5,
			'display'         => 'list',
			'help'            => 'Kirjoita yksi jäsenyyteen kuuluva asia kullekin riville.',
			'default'         => "Saat tietoa seuran tapahtumista ja ajankohtaisista keskusteluista.\nTapaat alueen yrittäjiä, asiantuntijoita ja muita elinvoimasta kiinnostuneita.\nVoit ehdottaa keskusteluaiheita, yritysvierailuja ja muuta toimintaa.",
			'launch_required' => true,
		),
		'membership_annual_events' => array(
			'label'           => 'Tapahtumia vuodessa noin',
			'default'         => $placeholder,
			'launch_required' => true,
			'todo'            => 'TODO(lks-membership-launch): Vahvista tapahtumien arvioitu vuosimäärä.',
		),
		'membership_extra_event_fees' => array(
			'label'           => 'Tapahtumien mahdolliset lisämaksut',
			'type'            => 'textarea',
			'rows'            => 3,
			'default'         => $placeholder,
			'launch_required' => true,
			'todo'            => 'TODO(lks-membership-launch): Vahvista, voidaanko tapahtumista periä erillisiä maksuja.',
		),
		'membership_nonmember_events' => array(
			'label'           => 'Tapahtumat muille kuin jäsenille',
			'type'            => 'textarea',
			'rows'            => 3,
			'default'         => $placeholder,
			'launch_required' => true,
			'todo'            => 'TODO(lks-membership-launch): Vahvista, ovatko valitut tapahtumat avoimia myös muille kuin jäsenille.',
		),
		'membership_processing_time' => array(
			'label'           => 'Hakemuksen tavallinen käsittelyaika',
			'default'         => $placeholder,
			'launch_required' => true,
			'todo'            => 'TODO(lks-membership-launch): Vahvista hakemuksen tavallinen käsittelyaika.',
		),
		'membership_approval_process' => array(
			'label'           => 'Hyväksymisprosessi',
			'type'            => 'textarea',
			'rows'            => 3,
			'default'         => 'Seuran hallitus käsittelee ja hyväksyy uudet varsinaiset jäsenet.',
			'launch_required' => true,
		),
		'membership_cancellation' => array(
			'label'           => 'Jäsenyyden päättäminen',
			'type'            => 'textarea',
			'rows'            => 4,
			'default'         => $placeholder,
			'launch_required' => true,
			'todo'            => 'TODO(lks-membership-launch): Vahvista jäsenyyden päättämisen ilmoitustapa ja aikataulu.',
		),
		'membership_contact_person' => array(
			'label'           => 'Jäsenyysasioiden yhteyshenkilö',
			'default'         => $placeholder,
			'launch_required' => true,
			'todo'            => 'TODO(lks-membership-launch): Vahvista jäsenyysasioiden yhteyshenkilön nimi ja tarvittaessa rooli.',
		),
		'membership_contact_email' => array(
			'label'           => 'Jäsenyysasioiden sähköposti',
			'type'            => 'email_or_placeholder',
			'help'            => 'Syötä julkinen jäsenyyssähköposti tai jätä [VAHVISTETAAN], kunnes osoite on vahvistettu.',
			'default'         => $placeholder,
			'launch_required' => true,
			'todo'            => 'TODO(lks-membership-launch): Vahvista julkinen jäsenyysasioiden sähköpostiosoite.',
		),
	);
}

/**
 * Return the editable page-copy schema.
 *
 * @return array<string,array<string,mixed>>
 */
function lakeuden_kauppaseura_page_copy_schema() {
	$membership_fields = lakeuden_kauppaseura_membership_facts_schema();

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
				'about_hero_text_2'       => array( 'label' => 'Johdanto 2', 'type' => 'textarea', 'default' => 'Strategiset valintamme ovat jäsenlähtöisyys ja vaikuttaminen.' ),
				'about_hero_link'         => array( 'label' => 'Johdannon linkki', 'default' => 'Tutustu toimintaamme' ),
				'about_purpose_kicker'    => array( 'label' => 'Tarkoitus: pieni otsikko', 'default' => 'Miksi olemme olemassa' ),
				'about_purpose_title'     => array( 'label' => 'Tarkoitus: otsikko', 'default' => 'Elinvoimaa rakennetaan yhdessä.' ),
				'about_purpose_text_1'    => array( 'label' => 'Tarkoitus: kappale 1', 'type' => 'textarea', 'default' => 'Lakeuden Kauppaseura edistää alueensa elinvoimaa ja vaikuttaa merkittävästi sen kehitykseen.' ),
				'about_purpose_text_2'    => array( 'label' => 'Tarkoitus: kappale 2', 'type' => 'textarea', 'default' => 'Seuran tarkoituksena on jäsentensä kautta edistää ja vaikuttaa elinkeinoelämän yleisten toimintaedellytysten ja -ympäristön parantamiseen Etelä-Pohjanmaan alueella.' ),
				'about_purpose_text_3'    => array( 'label' => 'Tarkoitus: kappale 3', 'type' => 'textarea', 'default' => 'Seura toimii keskustelunavaajana ja aloitteentekijänä sekä toiminta-alueensa elinkeinoelämän yhtenä edunvalvojana.' ),
				'about_role_1_title'      => array( 'label' => 'Rooli 1: otsikko', 'default' => 'Keskustelunavaaja' ),
				'about_role_1_text'       => array( 'label' => 'Rooli 1: teksti', 'type' => 'textarea', 'default' => 'Nostamme pöydälle alueen ajankohtaiset aiheet ja erilaiset näkökulmat.' ),
				'about_role_2_title'      => array( 'label' => 'Rooli 2: otsikko', 'default' => 'Aloitteentekijä' ),
				'about_role_2_text'       => array( 'label' => 'Rooli 2: teksti', 'type' => 'textarea', 'default' => 'Luomme tilaa uusille ajatuksille, kohtaamisille ja yhteisille avauksille.' ),
				'about_role_3_title'      => array( 'label' => 'Rooli 3: otsikko', 'default' => 'Edunvalvoja' ),
				'about_role_3_text'       => array( 'label' => 'Rooli 3: teksti', 'type' => 'textarea', 'default' => 'Pidämme Etelä-Pohjanmaan elinkeinoelämän toimintaedellytyksiä esillä.' ),
				'about_strategy_kicker'   => array( 'label' => 'Strategia: pieni otsikko', 'default' => 'Strategia 2024–2026' ),
				'about_strategy_title'    => array( 'label' => 'Strategia: otsikko', 'type' => 'textarea', 'rows' => 2, 'default' => 'Yhteinen suunta elinvoiman vahvistamiseen.' ),
				'about_strategy_intro'    => array( 'label' => 'Strategia: johdanto', 'type' => 'textarea', 'rows' => 4, 'default' => 'Strategia ohjaa toimintaamme kahden valinnan kautta: tuotamme jäsenille arvoa ja vahvistamme aktiivisesti alueellista vaikuttamista.' ),
				'about_strategy_mission'  => array( 'label' => 'Strategia: missio', 'type' => 'textarea', 'rows' => 3, 'default' => 'Lakeuden Kauppaseura edistää alueensa elinvoimaa ja vaikuttaa merkittävästi sen kehitykseen.' ),
				'about_strategy_vision'   => array( 'label' => 'Strategia: visio', 'type' => 'textarea', 'rows' => 3, 'default' => 'Lakeuden Kauppaseura on alueellaan merkittävä elinvoimavaikuttaja.' ),
				'about_strategy_values'   => array(
					'label'   => 'Strategia: arvot',
					'type'    => 'textarea',
					'rows'    => 5,
					'help'    => 'Kirjoita yksi arvo kullekin riville.',
					'default' => "Yhteisöllisyys\nRehellisyys, laadukkuus ja luotettavuus\nPositiivisuus\nRatkaisuhakuisuus",
				),
				'about_strategy_member_title' => array( 'label' => 'Strateginen valinta 1: otsikko', 'default' => 'Jäsenlähtöisyys' ),
				'about_strategy_member_text'  => array( 'label' => 'Strateginen valinta 1: teksti', 'type' => 'textarea', 'rows' => 5, 'default' => 'Toiminta keskittyy jäsenten tarpeisiin ja toiveisiin. Kuuntelemme jäseniä aktiivisesti ja luomme toimintaa, joka tukee heidän kasvuaan ja menestystään.' ),
				'about_strategy_impact_title' => array( 'label' => 'Strateginen valinta 2: otsikko', 'default' => 'Vaikuttaminen' ),
				'about_strategy_impact_text'  => array( 'label' => 'Strateginen valinta 2: teksti', 'type' => 'textarea', 'rows' => 5, 'default' => 'Haluamme olla näkyvä ja aktiivinen toimija alueellamme. Tuomme jäsentemme näkemyksiä esiin ja edistämme alueen taloudellisia sekä yhteisöllisiä toimintaedellytyksiä.' ),
				'about_action_kicker'     => array( 'label' => 'Toiminta: pieni otsikko', 'default' => 'Miten toimimme' ),
				'about_action_title'      => array( 'label' => 'Toiminta: otsikko', 'default' => 'Asiaa, kokemuksia ja hyvää seuraa.' ),
				'about_action_text'       => array( 'label' => 'Toiminta: esittelyteksti', 'type' => 'textarea', 'rows' => 5, 'default' => 'Järjestämme kokouksia, keskustelutilaisuuksia, koulutuksia ja tutustumiskäyntejä Suomessa ja kansainvälisesti. Lisäksi tuemme jäsenten hyvinvointia, yhteistä virkistäytymistä ja tiedon jakamista. Tapahtumat suunnitellaan rennoiksi ja keskusteleviksi kohtaamisiksi, joissa jäsenet tutustuvat toisiinsa ja seuran toimintaan.' ),
				'about_action_items'      => array( 'label' => 'Toimintamuodot', 'type' => 'textarea', 'rows' => 5, 'help' => 'Kirjoita yksi kohta kullekin riville. Numerointi syntyy automaattisesti.', 'default' => "Kokoukset ja keskustelut\nKoulutukset\nTutustumiskäynnit\nHyvinvointi ja yhdessäolo" ),
				'about_board_enabled'     => array( 'label' => 'Näytä hallitus tuotannossa', 'type' => 'checkbox', 'default' => '0', 'help' => 'Ota käyttöön vasta, kun vähintään yksi Hallitus-valikon julkaistu henkilö sisältää hyväksytyn nimen ja hallitusroolin eikä hakasulkeissa olevaa paikkamerkkiä. Lyhyt esittely, organisaatio ja muotokuva ovat vapaaehtoisia.' ),
				'about_board_kicker'      => array( 'label' => 'Hallitus: pieni otsikko', 'default' => 'Hallitus ja vastuuhenkilöt' ),
				'about_board_title'       => array( 'label' => 'Hallitus: otsikko', 'type' => 'textarea', 'rows' => 2, 'default' => 'Lakeuden Kauppaseuran hallitus 2026–2027.' ),
				'about_board_intro'       => array( 'label' => 'Hallitus: johdanto', 'type' => 'textarea', 'rows' => 4, 'default' => 'Hallituksen jäsenillä on omat vastuualueensa johtamisesta ja taloudesta vaikuttamiseen, verkostoihin, viestintään ja tapahtumiin.' ),
				'about_board_note'        => array( 'label' => 'Hallitus: lisätieto', 'type' => 'textarea', 'rows' => 4, 'default' => 'Hallitus kokoontuu tarpeen mukaan noin 4–8 kertaa vuodessa. Strategiaa kehitetään hallituksen ja jäsenistöstä kootun tiimin yhteistyönä ja tarkistetaan vuosittain.' ),
				'about_board_responsibilities' => array( 'label' => 'Hallituksen vastuualueet', 'type' => 'textarea', 'rows' => 4, 'default' => 'Hallituksen vastuualueet kattavat vaikuttamisen ja kehityksen, yhteistyöverkostot, vaikuttajatiimin, viestinnän, sosiaalisen median sekä tapahtumat.' ),
				'about_member_kicker'     => array( 'label' => 'Jäsenyys: pieni otsikko', 'default' => 'Jäsenyys' ),
				'about_member_title'      => array( 'label' => 'Jäsenyys: otsikko', 'default' => 'Seura on ihmisiä varten.' ),
				'about_pin_kicker'        => array( 'label' => 'Jäsenmerkki: pieni otsikko', 'default' => 'Jäsenmerkki' ),
				'about_pin_title'         => array( 'label' => 'Jäsenmerkki: otsikko', 'default' => 'Merkki kuulumisesta' ),
				'about_pin_text'          => array( 'label' => 'Jäsenmerkki: esittelyteksti', 'type' => 'textarea', 'rows' => 4, 'default' => 'Jokainen uusi jäsen saa Lakeuden Kauppaseuran pinssin. LK viittaa seuran nimeen, ja alaosan säteittäinen peltokuvio kuvaa Etelä-Pohjanmaan lakeuksia.' ),
				'about_member_button'     => array( 'label' => 'Jäsenyyspainike', 'default' => 'Kysy jäsenyydestä' ),
				'about_closing_kicker'    => array( 'label' => 'Lopetus: pieni otsikko', 'default' => 'Katse eteenpäin' ),
				'about_closing_quote'     => array( 'label' => 'Lopetus: nosto', 'type' => 'textarea', 'rows' => 4, 'default' => 'Lakeuden Kauppaseura on alueellaan merkittävä elinvoimavaikuttaja.' ),
				'about_closing_link'      => array( 'label' => 'Lopetus: tapahtumalinkki', 'default' => 'Näe, missä tapaamme seuraavaksi' ),
			),
		),
		'join' => array(
			'title'       => 'Jäseneksi',
			'description' => 'Jäseneksi-sivun yleiset tekstit, hakulomakkeen kytkentä ja hakukonenäkyvyys. Jäsenkortit muokataan Jäsenkokemukset-valikossa ja käytännön jäsenyystiedot Jäsenyystiedot-välilehdellä.',
			'fields'      => array(
				'join_hero_kicker'          => array( 'label' => 'Pääkuvan pieni otsikko', 'default' => 'Jäseneksi · Lakeuden Kauppaseura' ),
				'join_hero_title'           => array( 'label' => 'Pääotsikko', 'type' => 'textarea', 'rows' => 2, 'default' => 'Liity verkostoon, joka rakentaa elinvoimaista Etelä-Pohjanmaata.' ),
				'join_hero_lead'            => array( 'label' => 'Johdanto', 'type' => 'textarea', 'rows' => 4, 'default' => 'Lakeuden Kauppaseura tuo saman pöydän ääreen alueen yrittäjät, asiantuntijat, vaikuttajat ja kehittäjät. Ilmaise kiinnostuksesi, niin jatkamme keskustelua henkilökohtaisesti.' ),
				'join_primary_cta'          => array( 'label' => 'Ensisijainen painike', 'default' => 'Ilmaise kiinnostuksesi' ),
				'join_secondary_cta'        => array( 'label' => 'Toissijainen painike', 'default' => 'Tutustu tapahtumiin' ),
				'join_audience_kicker'      => array( 'label' => 'Kenelle-osio: pieni otsikko', 'default' => 'Kenelle jäsenyys sopii?' ),
				'join_audience_title'       => array( 'label' => 'Kenelle-osio: otsikko', 'default' => 'Sinulle, joka haluat olla mukana alueen tulevaisuudessa.' ),
				'join_audience_items'       => array(
					'label'   => 'Kenelle jäsenyys sopii',
					'type'    => 'textarea',
					'rows'    => 5,
					'help'    => 'Kirjoita yksi ryhmä kullekin riville.',
					'default' => "Yrittäjille ja yritysten päätöksentekijöille\nAsiantuntijoille ja vaikuttajille\nAluekehityksen ammattilaisille\nEtelä-Pohjanmaan tulevaisuudesta kiinnostuneille",
				),
				'join_benefits_kicker'      => array( 'label' => 'Hyödyt: pieni otsikko', 'default' => 'Mitä jäsenyys tarjoaa?' ),
				'join_benefits_title'       => array( 'label' => 'Hyödyt: otsikko', 'default' => 'Ajatuksia, yhteyksiä ja mahdollisuuksia vaikuttaa.' ),
				'join_benefits_items'       => array(
					'label'   => 'Jäsenyyden hyödyt',
					'type'    => 'textarea',
					'rows'    => 6,
					'help'    => 'Kirjoita yksi hyöty kullekin riville.',
					'default' => "Ajankohtaisia keskusteluja\nHyödyllisiä kontakteja\nYritysvierailuja\nMahdollisuus nostaa aiheita keskusteluun\nYhteisö ja alueellinen vaikuttaminen",
				),
				'join_facts_kicker'         => array( 'label' => 'Käytännön tiedot: pieni otsikko', 'default' => 'Käytännön jäsenyystiedot' ),
				'join_facts_title'          => array( 'label' => 'Käytännön tiedot: otsikko', 'default' => 'Mitä jäsenyydestä on hyvä tietää.' ),
				'join_facts_cta'            => array( 'label' => 'Käytännön tiedot: lomakepainike', 'default' => 'Täytä jäsenhakulomake' ),
				'join_steps_kicker'         => array( 'label' => 'Liittymisen vaiheet: pieni otsikko', 'default' => 'Näin liittyminen etenee' ),
				'join_steps_title'          => array( 'label' => 'Liittymisen vaiheet: otsikko', 'default' => 'Kiinnostuksesta osaksi seuraa.' ),
				'join_step_1_title'         => array( 'label' => 'Vaihe 1: otsikko', 'default' => 'Ilmaise kiinnostuksesi' ),
				'join_step_1_text'          => array( 'label' => 'Vaihe 1: teksti', 'type' => 'textarea', 'default' => 'Täytä kiinnostuslomake tai ota yhteyttä jäsenyysasioissa.' ),
				'join_step_2_title'         => array( 'label' => 'Vaihe 2: otsikko', 'default' => 'Henkilökohtainen yhteydenotto' ),
				'join_step_2_text'          => array( 'label' => 'Vaihe 2: teksti', 'type' => 'textarea', 'default' => 'Käymme kanssasi läpi jäsenyyttä ja seuraavia vaiheita.' ),
				'join_step_3_title'         => array( 'label' => 'Vaihe 3: otsikko', 'default' => 'Mahdollinen jäsenesitys' ),
				'join_step_4_title'         => array( 'label' => 'Vaihe 4: otsikko', 'default' => 'Hallituksen käsittely' ),
				'join_step_5_title'         => array( 'label' => 'Vaihe 5: otsikko', 'default' => 'Päätös ja perehdytys' ),
				'join_step_5_text'          => array( 'label' => 'Vaihe 5: teksti', 'type' => 'textarea', 'default' => 'Saat tiedon päätöksestä ja ohjeet jäsenyyden aloitukseen.' ),
				'join_testimonials_enabled' => array( 'label' => 'Näytä jäsenkokemukset tuotannossa', 'type' => 'checkbox', 'default' => '0', 'help' => 'Ota käyttöön vasta, kun vähintään yksi Jäsenkokemukset-valikon julkaistu kortti on hyväksytty eikä sisällä hakasulkeissa olevaa paikkamerkkiä.' ),
				'join_testimonials_kicker'  => array( 'label' => 'Jäsenkokemukset: pieni otsikko', 'default' => 'Jäsenten kokemuksia' ),
				'join_testimonials_title'   => array( 'label' => 'Jäsenkokemukset: otsikko', 'default' => 'Miksi ihmiset tulevat mukaan.' ),
				'join_faq_kicker'           => array( 'label' => 'UKK: pieni otsikko', 'default' => 'Usein kysyttyä' ),
				'join_faq_title'            => array( 'label' => 'UKK: otsikko', 'default' => 'Kysymyksiä jäsenyydestä.' ),
				'join_form_kicker'          => array( 'label' => 'Lomake: pieni otsikko', 'default' => 'Ilmaise kiinnostuksesi' ),
				'join_form_title'           => array( 'label' => 'Lomake: otsikko', 'default' => 'Aloitetaan keskustelu.' ),
				'join_form_lead'            => array( 'label' => 'Lomake: johdanto', 'type' => 'textarea', 'rows' => 4, 'default' => 'Lähetä yhteystietosi ja kerro lyhyesti, mikä jäsenyydessä kiinnostaa. Otamme sinuun yhteyttä valitsemallasi tavalla.' ),
				'join_form_id'              => array( 'label' => 'WPForms-lomakkeen ID', 'type' => 'number', 'default' => '0', 'help' => 'Lomakkeen tunnus näkyy WPForms → Kaikki lomakkeet -näkymässä. Asennustyökalu täyttää tämän automaattisesti.' ),
				'join_form_ready'           => array( 'label' => 'Lomake on tuotantovalmis', 'type' => 'checkbox', 'default' => '0', 'help' => 'Ota käyttöön vasta, kun vastaanottaja, SMTP-toimitus, roskapostisuojaus, tietosuojateksti ja onnistunut testilähetys on tarkistettu.' ),
				'join_static_form_title'     => array( 'label' => 'Staattisen esikatselun ilmoitus: otsikko', 'default' => 'Kiinnostuslomake ei toimi tässä esikatselussa.' ),
				'join_static_form_text'      => array( 'label' => 'Staattisen esikatselun ilmoitus: teksti', 'type' => 'textarea', 'rows' => 4, 'default' => 'GitHub Pages ei käsittele WordPress-lomakkeita. Tästä näkymästä ei voi lähettää tietoja, joten viestiäsi ei voida vahingossa hukata.' ),
				'join_static_form_link'      => array( 'label' => 'Staattisen esikatselun ilmoitus: linkki', 'default' => 'Siirry yhteystietoihin' ),
				'join_meta_title'            => array( 'label' => 'SEO-otsikko', 'default' => 'Jäseneksi – Lakeuden Kauppaseura' ),
				'join_meta_description'      => array( 'label' => 'Metakuvaus', 'type' => 'textarea', 'rows' => 3, 'default' => 'Tutustu Lakeuden Kauppaseuran jäsenyyteen, sen hyötyihin, käytännön tietoihin ja liittymisen vaiheisiin. Ilmaise kiinnostuksesi verkostoon.' ),
			),
		),
		'membership' => array(
			'title'       => 'Jäsenyystiedot',
			'description' => 'Jäseneksi- ja Meistä-sivujen yhteiset käytännön tiedot. [VAHVISTETAAN]-merkityt tiedot pysyvät poissa julkisilta sivuilta, kunnes ne on vahvistettu.',
			'fields'      => $membership_fields,
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
			'description' => 'Sivun otsikkotekstit. Integraatioihin sidottu seloste ylläpidetään lähdekoodissa ja tarkistetaan docs/PRIVACY_AND_STRUCTURED_DATA.md-ohjeen mukaan aina, kun palvelut muuttuvat.',
			'fields'      => array(
				'privacy_hero_kicker' => array( 'label' => 'Sivun pieni otsikko', 'default' => 'Tietosuoja' ),
				'privacy_hero_title'  => array( 'label' => 'Pääotsikko', 'default' => 'Tietosuojaseloste.' ),
				'privacy_hero_text'   => array( 'label' => 'Johdanto', 'type' => 'textarea', 'default' => 'Tällä sivulla kerromme, miten Lakeuden Kauppaseura käsittelee henkilötietoja verkkosivulla, jäsenyysyhteydenotoissa ja tapahtumissa.' ),
				'privacy_updated'     => array( 'label' => 'Päivityspäivä', 'default' => 'Päivitetty 23.7.2026' ),
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
 * Check whether a reusable member-testimonial value is still temporary.
 *
 * @param string $value Field value.
 * @return bool
 */
function lakeuden_kauppaseura_join_value_is_placeholder( $value ) {
	return '' === trim( (string) $value ) || (bool) preg_match( '/\[(?:ESIMERKKI|VAHVISTETAAN)[^\]]*\]/u', (string) $value );
}

/**
 * Check whether a launch-required membership value is unresolved.
 *
 * @param string $value Field value.
 * @return bool
 */
function lakeuden_kauppaseura_membership_value_is_unresolved( $value ) {
	$value = trim( (string) $value );

	return '' === $value || str_contains( $value, lakeuden_kauppaseura_membership_placeholder() );
}

/**
 * Return membership facts with their saved values and presentation metadata.
 *
 * @return array<int,array<string,mixed>>
 */
function lakeuden_kauppaseura_membership_facts() {
	$facts = array();

	foreach ( lakeuden_kauppaseura_membership_facts_schema() as $key => $field ) {
		$value   = lakeuden_kauppaseura_copy( $key );
		$facts[] = array(
			'key'             => $key,
			'label'           => $field['label'],
			'value'           => $value,
			'display'         => $field['display'] ?? 'text',
			'launch_required' => ! empty( $field['launch_required'] ),
			'unresolved'      => lakeuden_kauppaseura_membership_value_is_unresolved( $value ),
		);
	}

	return $facts;
}

/**
 * Return launch-required membership fields still awaiting confirmation.
 *
 * @return array<string,string>
 */
function lakeuden_kauppaseura_unresolved_membership_facts() {
	$unresolved = array();

	foreach ( lakeuden_kauppaseura_membership_facts() as $fact ) {
		if ( $fact['launch_required'] && $fact['unresolved'] ) {
			$unresolved[ $fact['key'] ] = $fact['label'];
		}
	}

	return $unresolved;
}

/**
 * Return the membership CTA target.
 *
 * @return string
 */
function lakeuden_kauppaseura_membership_contact_url() {
	return home_url( '/yhteystiedot/' );
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
			} elseif ( 'email_or_placeholder' === ( $field['type'] ?? 'text' ) ) {
				$value = sanitize_text_field( $value );
				$clean[ $key ] = lakeuden_kauppaseura_membership_value_is_unresolved( $value )
					? lakeuden_kauppaseura_membership_placeholder()
					: sanitize_email( $value );
			} elseif ( 'url_or_placeholder' === ( $field['type'] ?? 'text' ) ) {
				$value = sanitize_text_field( $value );
				$clean[ $key ] = lakeuden_kauppaseura_join_value_is_placeholder( $value )
					? $value
					: esc_url_raw( $value );
			} elseif ( 'number' === ( $field['type'] ?? 'text' ) ) {
				$clean[ $key ] = (string) absint( $value );
			} elseif ( 'checkbox' === ( $field['type'] ?? 'text' ) ) {
				$clean[ $key ] = empty( $value ) ? '0' : '1';
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

	$schema                = lakeuden_kauppaseura_page_copy_schema();
	$unresolved_membership = lakeuden_kauppaseura_unresolved_membership_facts();
	?>
	<div class="wrap lks-copy-admin">
		<h1>Sivujen tekstit</h1>
		<p class="description">Muokkaa sivujen yleisiä tekstejä tässä. Sivujen rakenne ja ulkoasu pysyvät suojattuina.</p>
		<div class="notice notice-info inline">
			<p><strong>Blogit:</strong> Blogit-valikossa. <strong>Tapahtumat:</strong> Tapahtumat-valikossa. Päivämäärät, laskurit ja julkaisumäärät muodostuvat automaattisesti.</p>
		</div>
		<?php if ( $unresolved_membership ) : ?>
			<div class="notice notice-warning inline">
				<p><strong>Jäsenyystiedoissa on <?php echo esc_html( (string) count( $unresolved_membership ) ); ?> vahvistettavaa kohtaa.</strong> Nämä kohdat piilotetaan julkisilta sivuilta. Staattisen sivuston validointi estää julkaisun, jos paikkamerkkejä päätyy vientiin.</p>
				<ul class="ul-disc">
					<?php foreach ( $unresolved_membership as $label ) : ?><li><?php echo esc_html( $label ); ?></li><?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
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
								<?php $is_unresolved = ( ! empty( $field['launch_required'] ) && lakeuden_kauppaseura_membership_value_is_unresolved( $value ) ) || ( ! empty( $field['todo'] ) && lakeuden_kauppaseura_join_value_is_placeholder( $value ) ); ?>
								<div class="lks-copy-admin__field">
									<label for="<?php echo esc_attr( 'lks-copy-' . $key ); ?>"><strong><?php echo esc_html( $field['label'] ); ?></strong><?php if ( $is_unresolved ) : ?> <span class="lks-copy-admin__unresolved">[VAHVISTETAAN]</span><?php endif; ?></label>
									<?php if ( 'textarea' === ( $field['type'] ?? 'text' ) ) : ?>
										<textarea class="large-text" id="<?php echo esc_attr( 'lks-copy-' . $key ); ?>" name="lakeuden_kauppaseura_page_copy[<?php echo esc_attr( $key ); ?>]" rows="<?php echo esc_attr( (string) ( $field['rows'] ?? 3 ) ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
									<?php elseif ( 'checkbox' === ( $field['type'] ?? 'text' ) ) : ?>
										<input name="lakeuden_kauppaseura_page_copy[<?php echo esc_attr( $key ); ?>]" type="hidden" value="0" />
										<label><input id="<?php echo esc_attr( 'lks-copy-' . $key ); ?>" name="lakeuden_kauppaseura_page_copy[<?php echo esc_attr( $key ); ?>]" type="checkbox" value="1"<?php checked( '1', $value ); ?> /> Kyllä</label>
									<?php else : ?>
										<input class="regular-text" id="<?php echo esc_attr( 'lks-copy-' . $key ); ?>" name="lakeuden_kauppaseura_page_copy[<?php echo esc_attr( $key ); ?>]" type="<?php echo esc_attr( in_array( ( $field['type'] ?? 'text' ), array( 'email_or_placeholder', 'url_or_placeholder' ), true ) ? 'text' : ( $field['type'] ?? 'text' ) ); ?>"<?php if ( 'email_or_placeholder' === ( $field['type'] ?? 'text' ) ) : ?> inputmode="email"<?php elseif ( 'number' === ( $field['type'] ?? 'text' ) ) : ?> min="0" step="1"<?php endif; ?> value="<?php echo esc_attr( $value ); ?>" />
									<?php endif; ?>
									<?php if ( ! empty( $field['help'] ) ) : ?><p class="description"><?php echo esc_html( $field['help'] ); ?></p><?php endif; ?>
									<?php if ( $is_unresolved && ! empty( $field['todo'] ) ) : ?><p class="description"><code><?php echo esc_html( $field['todo'] ); ?></code></p><?php endif; ?>
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
		.lks-copy-admin__unresolved { color: #996800; font-size: 11px; font-weight: 700; }
		@media (max-width: 782px) {
			.lks-copy-admin__tabs { top: 46px; overflow-x: auto; white-space: nowrap; }
			.lks-copy-admin__fields { grid-template-columns: 1fr; }
		}
	</style>
	<?php
}
