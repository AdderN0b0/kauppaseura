<?php
/**
 * Central JSON-LD graph for public pages.
 *
 * No SEO plugin is active in the audited installation. Keep every custom
 * schema type in this file so Organization, WebSite, WebPage, BreadcrumbList,
 * BlogPosting, FAQPage, and Event are not emitted by separate components.
 *
 * @package Lakeuden_Kauppaseura
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine whether a value is unsuitable as a factual schema property.
 *
 * @param mixed $value Candidate value.
 * @return bool
 */
function lakeuden_kauppaseura_schema_value_is_unknown( $value ) {
	if ( ! is_scalar( $value ) ) {
		return true;
	}

	$value = trim( wp_strip_all_tags( (string) $value ) );
	if ( '' === $value ) {
		return true;
	}

	return (bool) preg_match(
		'/\b(?:vahvistetaan|lisätään|ennen\s+julkaisua)\b|\[(?:[^\]]*vahvistetaan|[^\]]*lisätään|[^\]]*ennen\s+julkaisua)[^\]]*\]/iu',
		$value
	);
}

/**
 * Return one event meta value, optionally from a non-persistent test fixture.
 *
 * @param int                 $post_id   Event post ID.
 * @param string              $key       Meta key.
 * @param array<string,mixed> $overrides Test-only override values.
 * @return mixed
 */
function lakeuden_kauppaseura_event_schema_meta( $post_id, $key, $overrides = array() ) {
	if ( array_key_exists( $key, $overrides ) ) {
		return $overrides[ $key ];
	}

	return get_post_meta( $post_id, $key, true );
}

/**
 * Build a timezone-aware schema date without inventing a missing time.
 *
 * @param string $date YYYY-MM-DD.
 * @param string $time Optional HH:MM.
 * @return string
 */
function lakeuden_kauppaseura_schema_datetime( $date, $time = '' ) {
	$date = trim( (string) $date );
	$time = trim( (string) $time );

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		return '';
	}

	if ( ! preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
		return $date;
	}

	try {
		$timezone = new DateTimeZone( 'Europe/Helsinki' );
		$value    = new DateTimeImmutable( $date . ' ' . $time, $timezone );
		return $value->format( DATE_W3C );
	} catch ( Exception $exception ) {
		return $date;
	}
}

/**
 * Convert the stored event state to a Schema.org eventStatus URL.
 *
 * @param int                 $post_id   Event post ID.
 * @param array<string,mixed> $overrides Test-only override values.
 * @return string
 */
function lakeuden_kauppaseura_event_schema_status( $post_id, $overrides = array() ) {
	$cancelled = lakeuden_kauppaseura_event_schema_meta( $post_id, '_lks_event_cancelled', $overrides );
	$status    = strtolower( trim( (string) lakeuden_kauppaseura_event_schema_meta( $post_id, '_lks_event_status', $overrides ) ) );

	if ( in_array( strtolower( trim( (string) $cancelled ) ), array( '1', 'true', 'yes', 'on', 'kyllä' ), true ) ) {
		return 'https://schema.org/EventCancelled';
	}

	$mapping = array(
		'cancelled'   => 'https://schema.org/EventCancelled',
		'canceled'    => 'https://schema.org/EventCancelled',
		'peruttu'     => 'https://schema.org/EventCancelled',
		'postponed'   => 'https://schema.org/EventPostponed',
		'lykätty'     => 'https://schema.org/EventPostponed',
		'rescheduled' => 'https://schema.org/EventRescheduled',
		'siirretty'   => 'https://schema.org/EventRescheduled',
	);

	return $mapping[ $status ] ?? 'https://schema.org/EventScheduled';
}

/**
 * Return confirmed event location and attendance mode properties.
 *
 * @param int                 $post_id   Event post ID.
 * @param array<string,mixed> $overrides Test-only override values.
 * @return array<string,mixed>
 */
function lakeuden_kauppaseura_event_schema_location( $post_id, $overrides = array() ) {
	$mode       = strtolower( trim( (string) lakeuden_kauppaseura_event_schema_meta( $post_id, '_lks_event_attendance_mode', $overrides ) ) );
	$place      = lakeuden_kauppaseura_event_schema_meta( $post_id, '_lks_event_place', $overrides );
	$city       = lakeuden_kauppaseura_event_schema_meta( $post_id, '_lks_event_city', $overrides );
	$online_url = lakeuden_kauppaseura_event_schema_meta( $post_id, '_lks_event_online_url', $overrides );
	$place      = lakeuden_kauppaseura_schema_value_is_unknown( $place ) ? '' : trim( wp_strip_all_tags( (string) $place ) );
	$city       = lakeuden_kauppaseura_schema_value_is_unknown( $city ) ? '' : trim( wp_strip_all_tags( (string) $city ) );
	$online_url = wp_http_validate_url( (string) $online_url ) ? esc_url_raw( (string) $online_url ) : '';

	if ( 'online' === $mode || ( $online_url && ! $place && ! $city ) ) {
		$result = array( 'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode' );
		if ( $online_url ) {
			$result['location'] = array(
				'@type' => 'VirtualLocation',
				'url'   => $online_url,
			);
		}
		return $result;
	}

	if ( 'mixed' === $mode || 'hybrid' === $mode ) {
		$result = array( 'eventAttendanceMode' => 'https://schema.org/MixedEventAttendanceMode' );
	} elseif ( $place || $city || 'offline' === $mode ) {
		$result = array( 'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode' );
	} else {
		return array();
	}

	if ( $place || $city ) {
		$location = array(
			'@type' => 'Place',
			'name'  => $place ?: $city,
		);
		if ( $city ) {
			$location['address'] = array(
				'@type'          => 'PostalAddress',
				'addressLocality' => $city,
				'addressCountry'  => 'FI',
			);
		}
		$result['location'] = $location;
	}

	if ( 'https://schema.org/MixedEventAttendanceMode' === $result['eventAttendanceMode'] && $online_url ) {
		$physical          = $result['location'] ?? null;
		$result['location'] = array_values(
			array_filter(
				array(
					$physical,
					array(
						'@type' => 'VirtualLocation',
						'url'   => $online_url,
					),
				)
			)
		);
	}

	return $result;
}

/**
 * Return an Offer only for useful, currently actionable registration data.
 *
 * @param int                 $post_id   Event post ID.
 * @param array<string,mixed> $overrides Test-only override values.
 * @return array<string,mixed>
 */
function lakeuden_kauppaseura_event_schema_offer( $post_id, $overrides = array() ) {
	$registration_url = lakeuden_kauppaseura_event_schema_meta( $post_id, '_lks_event_registration_url', $overrides );
	$registration_url = wp_http_validate_url( (string) $registration_url ) ? $registration_url : '';

	$status = lakeuden_kauppaseura_event_schema_status( $post_id, $overrides );
	$date   = (string) lakeuden_kauppaseura_event_schema_meta( $post_id, '_lks_event_date', $overrides );
	if (
		! $registration_url
		|| 'https://schema.org/EventCancelled' === $status
		|| ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) && $date < wp_date( 'Y-m-d' ) )
	) {
		return array();
	}

	$deadline = (string) lakeuden_kauppaseura_event_schema_meta( $post_id, '_lks_event_registration_deadline', $overrides );
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $deadline ) && $deadline < wp_date( 'Y-m-d' ) ) {
		return array();
	}

	$offer = array(
		'@type'        => 'Offer',
		'url'          => esc_url_raw( (string) $registration_url ),
		'availability' => 'https://schema.org/InStock',
	);
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $deadline ) ) {
		$offer['validThrough'] = lakeuden_kauppaseura_schema_datetime( $deadline, '23:59' );
	}

	$price = trim( (string) lakeuden_kauppaseura_event_schema_meta( $post_id, '_lks_event_price', $overrides ) );
	if ( preg_match( '/^(?:maksuton|ilmainen)$/iu', $price ) ) {
		$offer['price']         = 0;
		$offer['priceCurrency'] = 'EUR';
	} elseif ( preg_match( '/(\d+(?:[.,]\d{1,2})?)/u', $price, $matches ) ) {
		$offer['price']         = (float) str_replace( ',', '.', $matches[1] );
		$offer['priceCurrency'] = 'EUR';
	}

	return $offer;
}

/**
 * Build Event JSON-LD from normal editor content and confirmed custom fields.
 *
 * @param int                 $post_id   Event post ID.
 * @param array<string,mixed> $overrides Test-only non-persistent fixture data.
 * @return array<string,mixed>
 */
function lakeuden_kauppaseura_event_schema( $post_id, $overrides = array() ) {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	$date       = (string) lakeuden_kauppaseura_event_schema_meta( $post_id, '_lks_event_date', $overrides );
	$start_time = (string) lakeuden_kauppaseura_event_schema_meta( $post_id, '_lks_event_start_time', $overrides );
	if ( ! $start_time ) {
		$start_time = (string) lakeuden_kauppaseura_event_schema_meta( $post_id, '_lks_event_time', $overrides );
	}
	$start_date = lakeuden_kauppaseura_schema_datetime( $date, $start_time );
	if ( ! $start_date ) {
		return array();
	}

	$canonical  = lakeuden_kauppaseura_production_url( 'tapahtuma/' . $post->post_name . '/' );
	$description = has_excerpt( $post )
		? get_the_excerpt( $post )
		: wp_html_excerpt( trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) ) ), 300, '&hellip;' );
	$description = html_entity_decode( trim( (string) $description ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

	$schema = array(
		'@type'               => 'Event',
		'@id'                 => $canonical . '#event',
		'name'                => get_the_title( $post ),
		'description'         => $description ?: get_the_title( $post ),
		'startDate'           => $start_date,
		'eventStatus'         => lakeuden_kauppaseura_event_schema_status( $post_id, $overrides ),
		'organizer'           => array( '@id' => lakeuden_kauppaseura_production_base_url() . '#organization' ),
		'url'                 => $canonical,
		'mainEntityOfPage'    => array( '@id' => $canonical . '#webpage' ),
	);

	$end_date = (string) lakeuden_kauppaseura_event_schema_meta( $post_id, '_lks_event_end_date', $overrides );
	$end_time = (string) lakeuden_kauppaseura_event_schema_meta( $post_id, '_lks_event_end_time', $overrides );
	if ( ! $end_date && $end_time ) {
		$end_date = $date;
	}
	$end = lakeuden_kauppaseura_schema_datetime( $end_date, $end_time );
	if ( $end ) {
		$schema['endDate'] = $end;
	}

	$schema = array_merge( $schema, lakeuden_kauppaseura_event_schema_location( $post_id, $overrides ) );

	$image_id = get_post_thumbnail_id( $post );
	$image    = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
	if ( $image ) {
		$schema['image'] = array( lakeuden_kauppaseura_production_asset_url( $image ) );
	}

	$offer = lakeuden_kauppaseura_event_schema_offer( $post_id, $overrides );
	if ( $offer ) {
		$schema['offers'] = $offer;
	}

	if ( 'https://schema.org/EventRescheduled' === $schema['eventStatus'] ) {
		$previous = (string) lakeuden_kauppaseura_event_schema_meta( $post_id, '_lks_event_previous_start', $overrides );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}(?:T.*)?$/', $previous ) ) {
			$schema['previousStartDate'] = $previous;
		}
	}

	return $schema;
}

/**
 * Return Organization schema from public site settings.
 *
 * @return array<string,mixed>
 */
function lakeuden_kauppaseura_organization_schema() {
	$base    = lakeuden_kauppaseura_production_base_url();
	$email   = sanitize_email( lakeuden_kauppaseura_copy( 'contact_email' ) );
	$phone   = preg_replace( '/[^\d+]/', '', lakeuden_kauppaseura_copy( 'contact_phone_link' ) );
	$address = preg_split( '/\R/u', trim( lakeuden_kauppaseura_copy( 'contact_address' ) ) ) ?: array();
	$street  = trim( (string) ( $address[0] ?? '' ) );
	$city    = trim( (string) ( $address[1] ?? '' ) );
	$schema  = array(
		'@type'      => 'Organization',
		'@id'        => $base . '#organization',
		'name'       => 'Lakeuden Kauppaseura ry',
		'url'        => $base,
		'logo'       => array(
			'@type' => 'ImageObject',
			'url'   => lakeuden_kauppaseura_production_url( 'wp-content/themes/lakeuden-kauppaseura/assets/lakeuden-kauppaseura-logo-clean.png' ),
		),
		'areaServed' => array(
			'@type' => 'AdministrativeArea',
			'name'  => 'Etelä-Pohjanmaa',
		),
		'sameAs'     => array(
			'https://www.instagram.com/lakeudenkauppaseura/',
			'https://www.facebook.com/kauppaseura',
		),
	);

	if ( $email ) {
		$schema['email'] = 'mailto:' . $email;
	}
	if ( $phone ) {
		$schema['telephone'] = $phone;
	}
	if ( ! lakeuden_kauppaseura_schema_value_is_unknown( $street ) || ! lakeuden_kauppaseura_schema_value_is_unknown( $city ) ) {
		$schema['address'] = array_filter(
			array(
				'@type'           => 'PostalAddress',
				'streetAddress'    => lakeuden_kauppaseura_schema_value_is_unknown( $street ) ? null : $street,
				'addressLocality'  => lakeuden_kauppaseura_schema_value_is_unknown( $city ) ? null : $city,
				'addressCountry'   => 'FI',
			),
			static fn( $value ) => null !== $value
		);
	}

	return $schema;
}

/**
 * Build ordered breadcrumb list items for the current request.
 *
 * @param string $canonical Current canonical URL.
 * @return array<int,array<string,mixed>>
 */
function lakeuden_kauppaseura_breadcrumb_items( $canonical ) {
	$base  = lakeuden_kauppaseura_production_base_url();
	$items = array(
		array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => 'Etusivu',
			'item'     => $base,
		),
	);

	if ( is_front_page() ) {
		return $items;
	}

	$post = get_queried_object();
	if ( $post instanceof WP_Post && 'post' === $post->post_type ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => 'Blogi',
			'item'     => lakeuden_kauppaseura_production_url( 'blogi/' ),
		);
	} elseif ( $post instanceof WP_Post && 'lks_event' === $post->post_type ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => 'Tapahtumat',
			'item'     => lakeuden_kauppaseura_production_url( 'tapahtumat/' ),
		);
	}

	$items[] = array(
		'@type'    => 'ListItem',
		'position' => count( $items ) + 1,
		'name'     => wp_get_document_title(),
		'item'     => $canonical,
	);

	return $items;
}

/**
 * Return confirmed membership FAQ entities.
 *
 * @return array<int,array<string,mixed>>
 */
function lakeuden_kauppaseura_membership_faq_schema_entities() {
	$questions = array(
		'Onko minun oltava yrittäjä?'          => array( 'membership_eligibility' ),
		'Voinko osallistua ennen liittymistä?' => array( 'membership_nonmember_events' ),
		'Tarvitsenko esittäjän?'                => array( 'membership_nomination' ),
		'Kuinka kauan käsittely kestää?'        => array( 'membership_processing_time' ),
	);
	$entities  = array();

	foreach ( $questions as $question => $keys ) {
		$fact = lakeuden_kauppaseura_membership_fact( $keys[0] );
		if ( ! $fact['unresolved'] ) {
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => $question,
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $fact['value'] ),
			);
		}
	}

	$annual_fee  = lakeuden_kauppaseura_membership_fact( 'membership_annual_fee' );
	$joining_fee = lakeuden_kauppaseura_membership_fact( 'membership_joining_fee' );
	if ( ! $annual_fee['unresolved'] && ! $joining_fee['unresolved'] ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => 'Mitä jäsenyys maksaa?',
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $annual_fee['label'] . ': ' . $annual_fee['value'] . '. ' . $joining_fee['label'] . ': ' . $joining_fee['value'] . '.',
			),
		);
	}

	$includes = lakeuden_kauppaseura_membership_fact( 'membership_includes' );
	$fees     = lakeuden_kauppaseura_membership_fact( 'membership_extra_event_fees' );
	if ( ! $includes['unresolved'] ) {
		$text = implode( ' ', lakeuden_kauppaseura_copy_list( 'membership_includes' ) );
		if ( ! $fees['unresolved'] ) {
			$text .= ' ' . $fees['label'] . ': ' . $fees['value'] . '.';
		}
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => 'Ovatko tapahtumat jäsenmaksun lisäksi maksullisia?',
			'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $text ),
		);
	}

	return $entities;
}

/**
 * Build the one JSON-LD graph for the current page.
 *
 * @param string                              $canonical  Current canonical URL.
 * @param string                              $description Meta description.
 * @param array{url:string,width:int,height:int,alt:string} $image Social image.
 * @return array<string,mixed>
 */
function lakeuden_kauppaseura_schema_graph( $canonical, $description, $image ) {
	$base        = lakeuden_kauppaseura_production_base_url();
	$webpage_id  = $canonical . '#webpage';
	$breadcrumb  = array(
		'@type'           => 'BreadcrumbList',
		'@id'             => $canonical . '#breadcrumb',
		'itemListElement' => lakeuden_kauppaseura_breadcrumb_items( $canonical ),
	);
	$webpage     = array(
		'@type'       => 'WebPage',
		'@id'         => $webpage_id,
		'url'         => $canonical,
		'name'        => wp_get_document_title(),
		'description' => $description,
		'inLanguage'  => 'fi-FI',
		'isPartOf'    => array( '@id' => $base . '#website' ),
		'about'       => array( '@id' => $base . '#organization' ),
		'breadcrumb'  => array( '@id' => $canonical . '#breadcrumb' ),
	);
	$graph       = array(
		lakeuden_kauppaseura_organization_schema(),
		array(
			'@type'      => 'WebSite',
			'@id'        => $base . '#website',
			'url'        => $base,
			'name'       => 'Lakeuden Kauppaseura',
			'inLanguage' => 'fi-FI',
			'publisher'  => array( '@id' => $base . '#organization' ),
		),
		$webpage,
		$breadcrumb,
	);

	if ( is_page( 'jaseneksi' ) ) {
		$faq = lakeuden_kauppaseura_membership_faq_schema_entities();
		if ( $faq ) {
			$graph[2]['@type']      = array( 'WebPage', 'FAQPage' );
			$graph[2]['mainEntity'] = $faq;
		}
	}

	if ( is_singular( 'post' ) ) {
		$post_id = get_queried_object_id();
		$authors = function_exists( 'lks_blog_get_authors' ) ? lks_blog_get_authors( $post_id ) : array();
		$names   = $authors ? wp_list_pluck( $authors, 'name' ) : array( get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ) );
		$article = array(
			'@type'            => 'BlogPosting',
			'@id'              => $canonical . '#article',
			'headline'         => get_the_title( $post_id ),
			'datePublished'    => get_the_date( DATE_W3C, $post_id ),
			'dateModified'     => get_the_modified_date( DATE_W3C, $post_id ),
			'author'           => array_map( static fn( $name ) => array( '@type' => 'Person', 'name' => $name ), array_values( array_filter( $names ) ) ),
			'image'            => array( $image['url'] ),
			'description'      => $description,
			'mainEntityOfPage' => array( '@id' => $webpage_id ),
			'publisher'        => array( '@id' => $base . '#organization' ),
		);
		$graph[] = $article;
		$graph[2]['mainEntity'] = array( '@id' => $canonical . '#article' );
	}

	if ( is_singular( 'lks_event' ) ) {
		$event = lakeuden_kauppaseura_event_schema( get_queried_object_id() );
		if ( $event ) {
			$graph[] = $event;
			$graph[2]['mainEntity'] = array( '@id' => $event['@id'] );
		}
	}

	return array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);
}
