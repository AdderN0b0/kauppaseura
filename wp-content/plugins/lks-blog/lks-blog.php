<?php
/**
 * Plugin Name: Lakeuden Kauppaseura – Blogi
 * Description: Reusable author profiles, an editorial blog archive, and single-article presentation for Lakeuden Kauppaseura.
 * Version: 1.0.0
 * Author: Lakeuden Kauppaseura
 * Text Domain: lks-blog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LKS_BLOG_VERSION', '1.0.0' );

/**
 * Present normal WordPress posts as a dedicated Blogit content type.
 *
 * Existing posts and URLs remain unchanged; only the editor-facing labels are
 * simplified.
 */
function lks_blog_relabel_post_type() {
	$post_type = get_post_type_object( 'post' );

	if ( ! $post_type ) {
		return;
	}

	$labels = array(
		'name'                     => 'Blogit',
		'singular_name'            => 'Blogi',
		'menu_name'                => 'Blogit',
		'name_admin_bar'           => 'Blogi',
		'add_new'                  => 'Lisää uusi blogi',
		'add_new_item'             => 'Lisää uusi blogi',
		'new_item'                 => 'Uusi blogi',
		'edit_item'                => 'Muokkaa blogia',
		'view_item'                => 'Näytä blogi',
		'view_items'               => 'Näytä blogit',
		'all_items'                => 'Kaikki blogit',
		'search_items'             => 'Etsi blogeja',
		'not_found'                => 'Blogeja ei löytynyt.',
		'not_found_in_trash'       => 'Roskakorissa ei ole blogeja.',
		'archives'                 => 'Blogiarkisto',
		'attributes'               => 'Blogin ominaisuudet',
		'insert_into_item'         => 'Lisää blogiin',
		'uploaded_to_this_item'    => 'Tähän blogiin ladatut',
		'filter_items_list'        => 'Suodata blogeja',
		'items_list_navigation'    => 'Blogilistan navigointi',
		'items_list'               => 'Blogilista',
		'item_published'           => 'Blogi julkaistu.',
		'item_published_privately' => 'Blogi julkaistu yksityisenä.',
		'item_reverted_to_draft'   => 'Blogi palautettu luonnokseksi.',
		'item_scheduled'           => 'Blogi ajastettu.',
		'item_updated'             => 'Blogi päivitetty.',
	);

	foreach ( $labels as $key => $value ) {
		$post_type->labels->{$key} = $value;
	}

	$post_type->label = 'Blogit';
}
add_action( 'init', 'lks_blog_relabel_post_type', 100 );

/**
 * Hide WordPress categories and tags from the blog editor.
 *
 * The Blogi category remains available internally for routing and old content,
 * but editors never need to choose it.
 *
 * @param array        $args        Taxonomy arguments.
 * @param string       $taxonomy    Taxonomy name.
 * @param array|string $object_type Object types.
 * @return array
 */
function lks_blog_hide_default_taxonomy_ui( $args, $taxonomy, $object_type ) {
	$object_types = (array) $object_type;

	if ( in_array( 'post', $object_types, true ) && in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
		$args['show_ui']           = false;
		$args['show_admin_column'] = false;
		$args['show_in_quick_edit'] = false;
		$args['show_in_rest']      = false;
		$args['meta_box_cb']       = false;
	}

	return $args;
}
add_filter( 'register_taxonomy_args', 'lks_blog_hide_default_taxonomy_ui', 10, 3 );

/**
 * Register reusable, multi-author profiles for normal WordPress posts.
 */
function lks_blog_register_author_taxonomy() {
	register_taxonomy(
		'lks_author',
		array( 'post' ),
		array(
			'labels'            => array(
				'name'              => 'Kirjoittajat',
				'singular_name'     => 'Kirjoittaja',
				'search_items'      => 'Etsi kirjoittajia',
				'all_items'         => 'Kaikki kirjoittajat',
				'edit_item'         => 'Muokkaa kirjoittajaa',
				'update_item'       => 'Päivitä kirjoittaja',
				'add_new_item'      => 'Lisää uusi kirjoittaja',
				'new_item_name'     => 'Kirjoittajan nimi',
				'menu_name'         => 'Kirjoittajat',
				'not_found'         => 'Kirjoittajia ei löytynyt.',
				'back_to_items'     => 'Takaisin kirjoittajiin',
			),
			'public'            => true,
			'publicly_queryable' => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'hierarchical'      => true,
			'rewrite'           => false,
			'capabilities'      => array(
				'manage_terms' => 'edit_posts',
				'edit_terms'   => 'edit_posts',
				'delete_terms' => 'edit_posts',
				'assign_terms' => 'edit_posts',
			),
		)
	);
}
add_action( 'init', 'lks_blog_register_author_taxonomy' );

/**
 * Add author profile fields to the taxonomy screen.
 */
function lks_blog_author_add_fields() {
	wp_nonce_field( 'lks_blog_save_author', 'lks_blog_author_nonce' );
	?>
	<div class="form-field">
		<label for="lks-author-title">Tehtävä tai kuvaus</label>
		<input id="lks-author-title" name="lks_author_title" type="text" value="" placeholder="Esim. yrittäjä ja ekonomi" />
		<p>Tämä näkyy kirjoittajan nimen alla. Kentän voi jättää tyhjäksi.</p>
	</div>
	<div class="form-field lks-author-photo-field">
		<label>Kirjoittajan kuva</label>
		<input class="lks-author-photo-id" name="lks_author_photo_id" type="hidden" value="" />
		<div class="lks-author-photo-preview"></div>
		<p>
			<button class="button lks-author-photo-choose" type="button">Valitse kuva</button>
			<button class="button-link-delete lks-author-photo-remove" type="button" hidden>Poista kuva</button>
		</p>
		<p>Kuva ladataan kerran ja sitä käytetään automaattisesti kaikissa tämän henkilön kirjoituksissa.</p>
	</div>
	<?php
}
add_action( 'lks_author_add_form_fields', 'lks_blog_author_add_fields' );

/**
 * Render author profile fields when editing an existing author.
 *
 * @param WP_Term $term Author term.
 */
function lks_blog_author_edit_fields( $term ) {
	$title    = (string) get_term_meta( $term->term_id, 'lks_author_title', true );
	$photo_id = absint( get_term_meta( $term->term_id, 'lks_author_photo_id', true ) );
	$photo    = $photo_id ? wp_get_attachment_image_url( $photo_id, 'thumbnail' ) : '';

	wp_nonce_field( 'lks_blog_save_author', 'lks_blog_author_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="lks-author-title">Tehtävä tai kuvaus</label></th>
		<td>
			<input id="lks-author-title" name="lks_author_title" type="text" value="<?php echo esc_attr( $title ); ?>" placeholder="Esim. yrittäjä ja ekonomi" />
			<p class="description">Tämä näkyy kirjoittajan nimen alla. Kentän voi jättää tyhjäksi.</p>
		</td>
	</tr>
	<tr class="form-field lks-author-photo-field">
		<th scope="row"><label>Kirjoittajan kuva</label></th>
		<td>
			<input class="lks-author-photo-id" name="lks_author_photo_id" type="hidden" value="<?php echo esc_attr( (string) $photo_id ); ?>" />
			<div class="lks-author-photo-preview"><?php if ( $photo ) : ?><img src="<?php echo esc_url( $photo ); ?>" alt="" /><?php endif; ?></div>
			<p>
				<button class="button lks-author-photo-choose" type="button">Valitse kuva</button>
				<button class="button-link-delete lks-author-photo-remove" type="button"<?php echo $photo ? '' : ' hidden'; ?>>Poista kuva</button>
			</p>
			<p class="description">Kuva ladataan kerran ja sitä käytetään automaattisesti kaikissa tämän henkilön kirjoituksissa.</p>
		</td>
	</tr>
	<?php
}
add_action( 'lks_author_edit_form_fields', 'lks_blog_author_edit_fields' );

/**
 * Save author profile fields.
 *
 * @param int $term_id Author term ID.
 */
function lks_blog_save_author_fields( $term_id ) {
	if ( ! isset( $_POST['lks_blog_author_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lks_blog_author_nonce'] ) ), 'lks_blog_save_author' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$title    = isset( $_POST['lks_author_title'] ) ? sanitize_text_field( wp_unslash( $_POST['lks_author_title'] ) ) : '';
	$photo_id = isset( $_POST['lks_author_photo_id'] ) ? absint( $_POST['lks_author_photo_id'] ) : 0;

	update_term_meta( $term_id, 'lks_author_title', $title );
	update_term_meta( $term_id, 'lks_author_photo_id', $photo_id );
}
add_action( 'created_lks_author', 'lks_blog_save_author_fields' );
add_action( 'edited_lks_author', 'lks_blog_save_author_fields' );

/**
 * Load the WordPress media picker on author profile screens.
 *
 * @param string $hook_suffix Current admin screen hook.
 */
function lks_blog_author_admin_assets( $hook_suffix ) {
	$screen = get_current_screen();

	if ( ! $screen || 'lks_author' !== $screen->taxonomy || ! in_array( $hook_suffix, array( 'edit-tags.php', 'term.php' ), true ) ) {
		return;
	}

	wp_enqueue_media();
	wp_add_inline_style(
		'common',
		'.lks-author-photo-preview img{display:block;width:112px;height:112px;margin:8px 0;border-radius:50%;object-fit:cover;background:#f0f0f1}.lks-author-photo-remove{margin-left:10px}'
	);
	$media_script = <<<'JS'
jQuery(function($){
	$(document).on('click','.lks-author-photo-choose',function(e){
		e.preventDefault();
		var field=$(this).closest('.lks-author-photo-field');
		var frame=wp.media({title:'Valitse kirjoittajan kuva',button:{text:'Käytä tätä kuvaa'},multiple:false,library:{type:'image'}});
		frame.on('select',function(){
			var image=frame.state().get('selection').first().toJSON();
			field.find('.lks-author-photo-id').val(image.id);
			field.find('.lks-author-photo-preview').html('<img src="'+(image.sizes.thumbnail?image.sizes.thumbnail.url:image.url)+'" alt="" />');
			field.find('.lks-author-photo-remove').prop('hidden',false);
		});
		frame.open();
	});
	$(document).on('click','.lks-author-photo-remove',function(e){
		e.preventDefault();
		var field=$(this).closest('.lks-author-photo-field');
		field.find('.lks-author-photo-id').val('');
		field.find('.lks-author-photo-preview').empty();
		$(this).prop('hidden',true);
	});
});
JS;
	wp_add_inline_script(
		'media-editor',
		$media_script
	);
}
add_action( 'admin_enqueue_scripts', 'lks_blog_author_admin_assets' );

/**
 * Return the Blogi category ID, creating it when needed.
 *
 * @return int
 */
function lks_blog_category_id() {
	$category = get_category_by_slug( 'blogi' );

	if ( $category ) {
		return (int) $category->term_id;
	}

	$result = wp_insert_term( 'Blogi', 'category', array( 'slug' => 'blogi' ) );
	return is_wp_error( $result ) ? 0 : (int) $result['term_id'];
}

/**
 * Make Blogi the automatic category for every newly created blog.
 */
function lks_blog_set_default_category() {
	$category_id = lks_blog_category_id();

	if ( $category_id && $category_id !== (int) get_option( 'default_category' ) ) {
		update_option( 'default_category', $category_id );
	}
}
add_action( 'admin_init', 'lks_blog_set_default_category' );

/**
 * Keep the Blogit menu focused on real blog posts.
 *
 * Legacy event copies stored as normal posts remain untouched but invisible
 * here; current events are managed in the separate Tapahtumat content type.
 *
 * @param WP_Query $query Admin list query.
 */
function lks_blog_filter_admin_list( $query ) {
	global $pagenow;

	if ( ! is_admin() || ! $query->is_main_query() || 'edit.php' !== $pagenow ) {
		return;
	}

	$post_type = $query->get( 'post_type' );
	if ( $post_type && 'post' !== $post_type ) {
		return;
	}

	$category_id = lks_blog_category_id();
	if ( $category_id ) {
		$query->set( 'cat', $category_id );
	}
}
add_action( 'pre_get_posts', 'lks_blog_filter_admin_list' );

/**
 * Make the blog list columns easier to understand.
 *
 * @param array<string,string> $columns List columns.
 * @return array<string,string>
 */
function lks_blog_simplify_admin_columns( $columns ) {
	unset( $columns['author'], $columns['categories'], $columns['tags'], $columns['comments'] );
	return $columns;
}
add_filter( 'manage_edit-post_columns', 'lks_blog_simplify_admin_columns' );

/**
 * Remove the category filter from the Blogit list; every visible item is a
 * blog, so the selector would only repeat the menu's purpose.
 *
 * @param bool   $disable   Whether the selector is disabled.
 * @param string $post_type Current post type.
 * @return bool
 */
function lks_blog_disable_category_dropdown( $disable, $post_type ) {
	return 'post' === $post_type ? true : $disable;
}
add_filter( 'disable_categories_dropdown', 'lks_blog_disable_category_dropdown', 10, 2 );

/**
 * Count posts in one blog list status.
 *
 * @param string   $status  Post status or "all".
 * @param int|null $author  Optional WordPress user ID.
 * @return int
 */
function lks_blog_admin_status_count( $status, $author = null ) {
	$statuses = 'all' === $status
		? array( 'publish', 'future', 'draft', 'pending', 'private' )
		: array( $status );

	$query_args = array(
		'post_type'              => 'post',
		'post_status'            => $statuses,
		'category__in'           => array( lks_blog_category_id() ),
		'posts_per_page'         => 1,
		'fields'                 => 'ids',
		'no_found_rows'          => false,
		'orderby'                => 'none',
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	);

	if ( $author ) {
		$query_args['author'] = $author;
	}

	$query = new WP_Query( $query_args );
	return (int) $query->found_posts;
}

/**
 * Correct the list-view totals so legacy event posts are not counted.
 *
 * @param array<string,string> $views Existing list views.
 * @return array<string,string>
 */
function lks_blog_filter_admin_views( $views ) {
	foreach ( $views as $key => $view ) {
		$status = 'all' === $key || 'mine' === $key ? 'all' : $key;
		$author = 'mine' === $key ? get_current_user_id() : null;
		$count  = lks_blog_admin_status_count( $status, $author );

		if ( 0 === $count && 'all' !== $key ) {
			unset( $views[ $key ] );
			continue;
		}

		$views[ $key ] = (string) preg_replace(
			'/\(\s*[\d\s,.]+\s*\)/u',
			'(' . number_format_i18n( $count ) . ')',
			$view
		);
	}

	return $views;
}
add_filter( 'views_edit-post', 'lks_blog_filter_admin_views' );

/**
 * Remove the unused category and tag controls from classic editor fallbacks.
 */
function lks_blog_remove_taxonomy_metaboxes() {
	remove_meta_box( 'categorydiv', 'post', 'side' );
	remove_meta_box( 'tagsdiv-post_tag', 'post', 'side' );
}
add_action( 'add_meta_boxes_post', 'lks_blog_remove_taxonomy_metaboxes', 100 );

/**
 * Add a compact publishing checklist to the post editor.
 */
function lks_blog_add_guide_metabox() {
	add_meta_box(
		'lks-blog-guide',
		'Blogikirjoituksen muistilista',
		'lks_blog_render_guide_metabox',
		'post',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'lks_blog_add_guide_metabox' );

/**
 * Render the publishing checklist.
 */
function lks_blog_render_guide_metabox() {
	?>
	<ol style="margin-left:1.2em">
		<li>Kirjoita otsikko ja sisältö.</li>
		<li>Lisää artikkelikuva kohdasta <strong>Artikkelikuva</strong>.</li>
		<li>Valitse yksi tai useampi <strong>Kirjoittaja</strong>.</li>
		<li>Julkaise. Uusin päiväys nousee automaattisesti ensimmäiseksi.</li>
	</ol>
	<p><a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=lks_author&post_type=post' ) ); ?>">Hallitse kirjoittajia ja heidän kuviaan →</a></p>
	<?php
}

/**
 * Return article authors in their selected taxonomy order.
 *
 * @param int $post_id Post ID.
 * @return WP_Term[]
 */
function lks_blog_get_authors( $post_id ) {
	$authors = wp_get_post_terms( $post_id, 'lks_author' );
	return is_wp_error( $authors ) ? array() : $authors;
}

/**
 * Render a compact author line used on article cards.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function lks_blog_render_author_line( $post_id ) {
	$authors = lks_blog_get_authors( $post_id );

	if ( ! $authors ) {
		return '';
	}

	$names = wp_list_pluck( $authors, 'name' );
	return '<span class="lks-blog-byline">' . esc_html( implode( ', ', $names ) ) . '</span>';
}

/**
 * Resolve an informative attachment alternative without using a filename.
 *
 * @param int    $attachment_id Attachment post ID.
 * @param string $fallback      Contextual Finnish fallback.
 * @return string
 */
function lks_blog_attachment_alt( $attachment_id, $fallback ) {
	if ( function_exists( 'lakeuden_kauppaseura_attachment_alt' ) ) {
		return lakeuden_kauppaseura_attachment_alt( $attachment_id, $fallback );
	}

	$alt = trim( wp_strip_all_tags( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );
	return $alt ?: $fallback;
}

/**
 * Return the featured-image alt used on a single article.
 *
 * @param WP_Post $post Article.
 * @return string
 */
function lks_blog_featured_image_alt( $post ) {
	return lks_blog_attachment_alt(
		get_post_thumbnail_id( $post ),
		sprintf( 'Kirjoituksen kuvitus: %s', get_the_title( $post ) )
	);
}

/**
 * Render full author cards for an article.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function lks_blog_render_author_cards( $post_id ) {
	$authors = lks_blog_get_authors( $post_id );

	if ( ! $authors ) {
		return '';
	}

	ob_start();
	?>
	<aside class="lks-article-authors" aria-labelledby="lks-article-authors-title">
		<p id="lks-article-authors-title" class="lks-kicker">Kirjoittajat</p>
		<div class="lks-article-authors__list">
			<?php foreach ( $authors as $author ) : ?>
				<?php
				$photo_id = absint( get_term_meta( $author->term_id, 'lks_author_photo_id', true ) );
				$title    = (string) get_term_meta( $author->term_id, 'lks_author_title', true );
				$initials = implode( '', array_map( static function ( $part ) { return mb_substr( $part, 0, 1 ); }, array_slice( preg_split( '/\s+/', $author->name ), 0, 2 ) ) );
				?>
				<div class="lks-author-card">
					<div class="lks-author-card__photo">
						<?php if ( $photo_id ) : ?>
							<?php echo wp_get_attachment_image( $photo_id, 'thumbnail', false, array( 'loading' => 'lazy', 'alt' => lks_blog_attachment_alt( $photo_id, $author->name ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<span aria-hidden="true"><?php echo esc_html( mb_strtoupper( $initials ) ); ?></span>
						<?php endif; ?>
					</div>
					<div>
						<strong><?php echo esc_html( $author->name ); ?></strong>
						<?php if ( $title ) : ?><span><?php echo esc_html( $title ); ?></span><?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</aside>
	<?php
	return (string) ob_get_clean();
}

/**
 * Render an unambiguous site/original publication line.
 *
 * @param WP_Post $post Article.
 * @return string
 */
function lks_blog_render_publication_line( $post ) {
	$original_date = (string) get_post_meta( $post->ID, '_lks_original_publication_date', true );
	$publication   = (string) get_post_meta( $post->ID, '_lks_original_publication_name', true );
	$site_date     = get_the_date( 'j.n.Y', $post );
	$site_machine  = get_the_date( 'c', $post );
	$html          = '<span>Julkaistu sivustolla <time datetime="' . esc_attr( $site_machine ) . '">' . esc_html( $site_date ) . '</time></span>';

	if ( ! $original_date ) {
		return $html;
	}

	$timestamp = strtotime( $original_date . ' 12:00:00' );
	$display   = $timestamp ? wp_date( 'j.n.Y', $timestamp ) : $original_date;
	$locations = array(
		'Järviseudun Sanomat' => 'Järviseudun Sanomissa',
		'Ilkka-Pohjalainen'   => 'Ilkka-Pohjalaisessa',
	);
	$source    = isset( $locations[ $publication ] ) ? ' ' . $locations[ $publication ] : '';

	return $html . '<span>Alun perin julkaistu' . esc_html( $source ) . ' <time datetime="' . esc_attr( $original_date ) . '">' . esc_html( $display ) . '</time></span>';
}

/**
 * Build an editorial card for a blog post.
 *
 * @param WP_Post $post    Blog post.
 * @param bool    $feature Whether to use the lead-card layout.
 * @return string
 */
function lks_blog_render_card( $post, $feature = false ) {
	$image   = get_the_post_thumbnail_url( $post, $feature ? 'large' : 'medium_large' );
	$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), $feature ? 34 : 23 );
	$class   = $feature ? 'lks-blog-card lks-blog-card--lead' : 'lks-blog-card';

	ob_start();
	?>
	<article class="<?php echo esc_attr( $class ); ?>">
		<a class="lks-blog-card__media<?php echo $image ? '' : ' is-empty'; ?>" href="<?php echo esc_url( get_permalink( $post ) ); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( $image ) : ?><?php echo get_the_post_thumbnail( $post, $feature ? 'large' : 'medium_large', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php endif; ?>
			<span class="lks-blog-card__arrow">↗</span>
		</a>
		<div class="lks-blog-card__content">
			<div class="lks-blog-card__meta">
				<?php echo lks_blog_render_publication_line( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo lks_blog_render_author_line( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<h2><a href="<?php echo esc_url( get_permalink( $post ) ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h2>
			<?php if ( $excerpt ) : ?><p><?php echo esc_html( $excerpt ); ?></p><?php endif; ?>
			<a class="lks-arrow-link" href="<?php echo esc_url( get_permalink( $post ) ); ?>">Lue kirjoitus <span aria-hidden="true">→</span></a>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}

/**
 * Read theme-managed page copy without making the plugin theme-dependent.
 *
 * @param string $key Copy key.
 * @param string $fallback Plugin fallback.
 * @return string
 */
function lks_blog_page_copy( $key, $fallback ) {
	return function_exists( 'lakeuden_kauppaseura_copy' )
		? lakeuden_kauppaseura_copy( $key )
		: $fallback;
}

/**
 * Render the Blogi page. Results are always ordered newest first.
 *
 * @return string
 */
function lks_blog_render_archive() {
	$paged = max( 1, absint( get_query_var( 'paged' ) ), absint( get_query_var( 'page' ) ) );
	$query = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'category_name'       => 'blogi',
			'posts_per_page'      => 9,
			'paged'               => $paged,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		)
	);

	ob_start();
	?>
	<div id="main" class="lks-blog-archive" role="main">
		<header class="lks-blog-archive__hero">
			<div class="lks-blog-shell lks-blog-archive__hero-grid">
				<div>
					<p class="lks-kicker lks-kicker--light"><?php echo esc_html( lks_blog_page_copy( 'blog_hero_kicker', 'Näkökulmia Lakeudelta' ) ); ?></p>
					<h1><?php echo nl2br( esc_html( lks_blog_page_copy( 'blog_hero_title', "Ajatuksia,\njotka vievät eteenpäin." ) ) ); ?></h1>
				</div>
				<div class="lks-blog-archive__intro">
					<p><?php echo esc_html( lks_blog_page_copy( 'blog_hero_text', 'Puheenvuoroja yrittäjyydestä, alueen elinvoimasta ja asioista, joista Etelä-Pohjanmaalla kannattaa keskustella.' ) ); ?></p>
					<span><?php echo esc_html( (string) $query->found_posts ); ?> kirjoitusta</span>
				</div>
			</div>
		</header>

		<section class="lks-blog-shell lks-blog-archive__content" aria-label="Blogikirjoitukset">
			<?php if ( $query->have_posts() ) : ?>
				<?php
				$posts = $query->posts;
				$lead  = array_shift( $posts );
				echo lks_blog_render_card( $lead, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
				<?php if ( $posts ) : ?>
					<div class="lks-blog-grid">
						<?php foreach ( $posts as $post ) : ?>
							<?php echo lks_blog_render_card( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php
				$pagination = paginate_links(
					array(
						'total'     => $query->max_num_pages,
						'current'   => $paged,
						'prev_text' => '← Uudemmat',
						'next_text' => 'Vanhemmat →',
						'type'      => 'list',
					)
				);
				?>
				<?php if ( $pagination ) : ?><nav class="lks-blog-pagination" aria-label="Blogikirjoitusten sivut"><?php echo wp_kses_post( $pagination ); ?></nav><?php endif; ?>
			<?php else : ?>
				<p class="lks-event-empty"><?php echo esc_html( lks_blog_page_copy( 'blog_empty', 'Ensimmäinen kirjoitus on tulossa pian.' ) ); ?></p>
			<?php endif; ?>
		</section>
	</div>
	<?php

	wp_reset_postdata();
	$html = (string) ob_get_clean();
	return (string) preg_replace( '/>\s+</', '><', $html );
}
add_shortcode( 'lks_blog_archive', 'lks_blog_render_archive' );

/**
 * Render a single editorial article.
 *
 * @return string
 */
function lks_blog_render_single() {
	$post = get_post();

	if ( ! $post || 'post' !== $post->post_type ) {
		return '';
	}

	$image      = get_the_post_thumbnail_url( $post, 'full' );
	$excerpt    = has_excerpt( $post ) ? get_the_excerpt( $post ) : '';
	$source_url = (string) get_post_meta( $post->ID, '_lks_wix_source_url', true );
	$content    = apply_filters( 'the_content', $post->post_content );

	$older = get_adjacent_post( true, '', true, 'category' );
	$newer = get_adjacent_post( true, '', false, 'category' );

	ob_start();
	?>
	<div id="main" class="lks-article" role="main">
		<header class="lks-article__header">
			<div class="lks-article-shell">
				<a class="lks-article__back" href="<?php echo esc_url( home_url( '/blogi/' ) ); ?>">← Kaikki kirjoitukset</a>
				<div class="lks-article__meta">
					<?php echo lks_blog_render_publication_line( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo lks_blog_render_author_line( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<h1><?php echo esc_html( get_the_title( $post ) ); ?></h1>
				<?php if ( $excerpt ) : ?><p class="lks-article__lead"><?php echo esc_html( $excerpt ); ?></p><?php endif; ?>
			</div>
		</header>

		<?php if ( $image ) : ?>
			<figure class="lks-article__hero-image">
				<?php echo get_the_post_thumbnail( $post, 'full', array( 'alt' => lks_blog_featured_image_alt( $post ), 'fetchpriority' => 'high', 'loading' => 'eager', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</figure>
		<?php endif; ?>

		<div class="lks-article-shell lks-article__layout">
			<div class="lks-article__body">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( $source_url ) : ?>
					<p class="lks-article__source"><a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener noreferrer">Lähde: alkuperäinen julkaisu Lakeuden Kauppaseuran aiemmalla sivustolla <span class="screen-reader-text">(avautuu uuteen välilehteen)</span></a></p>
				<?php endif; ?>
			</div>
			<?php echo lks_blog_render_author_cards( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>

		<?php if ( $newer || $older ) : ?>
			<nav class="lks-article-shell lks-article-nav" aria-label="Muut kirjoitukset">
				<?php if ( $newer ) : ?>
					<a href="<?php echo esc_url( get_permalink( $newer ) ); ?>"><span>Uudempi kirjoitus</span><strong>← <?php echo esc_html( get_the_title( $newer ) ); ?></strong></a>
				<?php else : ?><span></span><?php endif; ?>
				<?php if ( $older ) : ?>
					<a class="is-next" href="<?php echo esc_url( get_permalink( $older ) ); ?>"><span>Vanhempi kirjoitus</span><strong><?php echo esc_html( get_the_title( $older ) ); ?> →</strong></a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>
	</div>
	<?php
	$html = (string) ob_get_clean();
	return (string) preg_replace( '/>\s+</', '><', $html );
}
add_shortcode( 'lks_blog_single', 'lks_blog_render_single' );

/**
 * Create content structures on activation.
 */
function lks_blog_activate() {
	lks_blog_register_author_taxonomy();
	$category_id = lks_blog_category_id();
	if ( $category_id ) {
		update_option( 'default_category', $category_id );
	}
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'lks_blog_activate' );
