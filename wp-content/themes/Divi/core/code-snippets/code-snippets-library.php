<?php
/**
 * Code Snippets Library API.
 *
 * @package Divi
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gets the terms list and processes it into desired format.
 *
 * @since 4.19.0
 *
 * @param string $tax_name Term Name.
 *
 * @return array $terms_by_id
 */
function et_code_snippets_library_get_processed_terms( $tax_name ) {
	$terms       = get_terms( $tax_name, [ 'hide_empty' => false ] );
	$terms_by_id = [];

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return [];
	}

	foreach ( $terms as $term ) {
		$term_id = $term->term_id;

		$terms_by_id[ $term_id ]['id']    = $term_id;
		$terms_by_id[ $term_id ]['name']  = $term->name;
		$terms_by_id[ $term_id ]['slug']  = $term->slug;
		$terms_by_id[ $term_id ]['count'] = $term->count;
	}

	return $terms_by_id;
}

/**
 * Processes item taxonomies for inclusion in the theme builder library UI items data.
 *
 * @since 4.19.0
 *
 * @param WP_POST $post          Unprocessed item.
 * @param object  $item          Currently processing item.
 * @param int     $index         The item's index position.
 * @param array[] $item_terms    Processed items.
 * @param string  $taxonomy_name Item name.
 * @param string  $type          Item type.
 *
 * @return void
 */
function et_code_snippets_library_process_item_taxonomy( $post, $item, $index, &$item_terms, $taxonomy_name, $type ) {
	$terms = wp_get_post_terms( $post->ID, $taxonomy_name );

	if ( ! $terms ) {
		if ( 'category' === $type ) {
			$item->category_slug = 'uncategorized';
		}

		return;
	}

	foreach ( $terms as $term ) {
		$term_name = et_core_intentionally_unescaped( $term->name, 'react_jsx' );

		if ( ! isset( $item_terms[ $term->term_id ] ) ) {
			$item_terms[ $term->term_id ] = array(
				'id'    => $term->term_id,
				'name'  => $term_name,
				'slug'  => $term->slug,
				'items' => array(),
			);
		}

		$item_terms[ $term->term_id ]['items'][] = $index;

		if ( 'category' === $type ) {
			$item->categories[] = $term_name;
		} else {
			$item->tags[] = $term_name;
		}

		$item->{$type . '_ids'}[] = $term->term_id;

		if ( ! isset( $item->{$type . '_slug'} ) ) {
			$item->{$type . '_slug'} = $term->slug;
		}

		$id = get_post_meta( $post->ID, "_primary_{$taxonomy_name}", true );

		if ( $id ) {
			// $id is a string, $term->term_id is an int.
			if ( $id === $term->term_id ) {
				// This is the primary term (used in the item URL).
				$item->{$type . '_slug'} = $term->slug;
			}
		}
	}
}

/**
 * Generates items data for the Code Snippets library UI.
 *
 * @param string $code_snippet_type Item type.
 *
 * @since 4.19.0
 *
 * @return array $data
 */
function et_code_snippets_library_get_library_items_data( $code_snippet_type ) {
	$_                       = ET_Core_Data_Utils::instance();
	$code_snippet_items      = ET_Builder_Post_Type_Code_Snippet::instance();
	$code_snippet_tags       = ET_Builder_Post_Taxonomy_LayoutTag::instance();
	$code_snippet_categories = ET_Builder_Post_Taxonomy_LayoutCategory::instance();
	$code_snippet_types      = ET_Builder_Post_Taxonomy_CodeSnippetType::instance();

	$item_categories = [];
	$item_tags       = [];
	$items           = [];
	$index           = 0;

	$posts = $code_snippet_items
		->query()
		->run(
			array(
				'post_status' => array( 'publish', 'trash' ),
				'orderby'     => 'name',
				'fields'      => 'ids',
			)
		);

	$posts = is_array( $posts ) ? $posts : array( $posts );

	foreach ( $posts as $post_id ) {
		$post = get_post( $post_id );
		$item = new stdClass();

		setup_postdata( $post );

		$item->id    = $post->ID;
		$item->index = $index;
		$item->date  = $post->post_date;
		$types       = wp_get_post_terms( $item->id, $code_snippet_types->name );

		if ( ! $types ) {
			continue;
		}

		$item->type = $types[0]->slug;

		if ( $code_snippet_type !== $item->type ) {
			continue;
		}

		$title = html_entity_decode( $post->post_title );

		// check if current user can edit library item.
		$can_edit_post = current_user_can( 'edit_post', $item->id );

		if ( $title ) {
			// Remove periods since we use dot notation to retrieve translation.
			$title = str_replace( '.', '', $title );

			$item->name = et_core_intentionally_unescaped( $title, 'react_jsx' );
		}

		$item->slug = $post->post_name;
		$item->url  = esc_url( wp_make_link_relative( get_permalink( $post ) ) );

		$item->short_name   = '';
		$item->is_default   = get_post_meta( $item->id, '_et_default', true );
		$item->description  = '';
		$item->is_favorite  = $code_snippet_items->is_favorite( $item->id );
		$item->isTrash      = 'trash' === $post->post_status; // phpcs:ignore ET.Sniffs.ValidVariableName.UsedPropertyNotSnakeCase -- This is valid format for the property in the Cloud App.
		$item->isReadOnly   = ! $can_edit_post; // phpcs:ignore ET.Sniffs.ValidVariableName.UsedPropertyNotSnakeCase -- This is valid format for the property in the Cloud App.
		$item->categories   = array();
		$item->category_ids = array();
		$item->tags         = array();
		$item->tag_ids      = array();

		et_theme_builder_library_process_item_taxonomy(
			$post,
			$item,
			$index,
			$item_categories,
			$code_snippet_categories->name,
			'category'
		);

		et_theme_builder_library_process_item_taxonomy(
			$post,
			$item,
			$index,
			$item_tags,
			$code_snippet_tags->name,
			'tag'
		);

		wp_reset_postdata();

		$items[] = $item;

		$index++;
	}

	return [
		'categories' => et_theme_builder_library_get_processed_terms( $code_snippet_categories->name ),
		'tags'       => et_theme_builder_library_get_processed_terms( $code_snippet_tags->name ),
		'items'      => $items,
	];
}

/**
 * Sanitize txonomies.
 *
 * @since 4.19.0
 *
 * @param array $taxonomies Array of id for categories and tags.
 *
 * @return array Sanitized value.
 */
function et_code_snippets_library_sanitize_taxonomies( $taxonomies ) {
	if ( empty( $taxonomies ) ) {
		return array();
	}

	return array_unique(
		array_map( 'intval', $taxonomies )
	);
}

/**
 * Get all terms of an item and merge any newly passed IDs with the list.
 *
 * @since 4.19.0
 *
 * @param string $new_terms_list List of new terms.
 * @param array  $taxonomies Taxonomies.
 * @param string $taxonomy_name Taxonomy name.
 *
 * @return array
 */
function et_code_snippets_library_create_and_get_all_item_terms( $new_terms_list, $taxonomies, $taxonomy_name ) {
	$new_names_array = explode( ',', $new_terms_list );

	foreach ( $new_names_array as $new_name ) {
		if ( '' !== $new_name ) {
			$new_term = wp_insert_term( $new_name, $taxonomy_name );

			if ( ! is_wp_error( $new_term ) ) {
				$taxonomies[] = $new_term['term_id'];
			} elseif (
					! empty( $new_term->error_data ) &&
					! empty( $new_term->error_data['term_exists'] )
				) {
				$taxonomies[] = $new_term->error_data['term_exists'];
			}
		}
	}

	return $taxonomies;
}

/**
 * Update the Code Snippets library item. Following updates supported:
 * - Rename
 * - Toggle Favorite status
 * - Delete
 * - Delete Permanently
 * - Restore
 *
 * @since 4.19.0
 *
 * @param array $payload Array with the id and update details.
 *
 * @return array Updated item details
 */
function et_code_snippets_library_update_item_data( $payload ) {
	if ( empty( $payload['item_id'] ) || empty( $payload['update_details'] ) ) {
		return false;
	}

	$update_details = $payload['update_details'];

	if ( empty( $update_details['updateType'] ) ) {
		return false;
	}

	$code_snippets_categories = ET_Builder_Post_Taxonomy_LayoutCategory::instance();
	$code_snippets_tags       = ET_Builder_Post_Taxonomy_LayoutTag::instance();
	$code_snippets_type       = ET_Builder_Post_Taxonomy_CodeSnippetType::instance();

	$new_id          = 0;
	$item_id         = absint( $payload['item_id'] );
	$item_update     = array( 'ID' => $item_id );
	$update_type     = sanitize_text_field( $update_details['updateType'] );
	$item_name       = isset( $update_details['itemName'] ) ? sanitize_text_field( $update_details['itemName'] ) : '';
	$favorite_status = isset( $update_details['favoriteStatus'] ) && ( 'on' === $update_details['favoriteStatus'] ) ? 'favorite' : '';
	$categories      = isset( $update_details['itemCategories'] ) ? et_code_snippets_library_sanitize_taxonomies( $update_details['itemCategories'] ) : array();
	$tags            = isset( $update_details['itemTags'] ) ? et_code_snippets_library_sanitize_taxonomies( $update_details['itemTags'] ) : array();
	$post_type       = get_post_type( $item_id );

	if ( ! empty( $update_details['newCategoryName'] ) ) {
		$categories = et_code_snippets_library_create_and_get_all_item_terms(
			$update_details['newCategoryName'],
			$categories,
			$code_snippets_categories->name
		);
	}

	if ( ! empty( $update_details['newTagName'] ) ) {
		$tags = et_code_snippets_library_create_and_get_all_item_terms(
			$update_details['newTagName'],
			$tags,
			$code_snippets_tags->name
		);
	}

	switch ( $update_type ) {
		case 'duplicate':
		case 'duplicate_and_delete':
			$is_item_from_cloud = isset( $update_details['content'] );
			$title              = sanitize_text_field( $update_details['itemName'] );

			if ( $is_item_from_cloud ) {
				$content         = isset( $update_details['content']['data'] ) ? $update_details['content']['data'] : '';
				$snippet_type    = isset( $update_details['content']['snippet_type'] ) ? sanitize_text_field( $update_details['content']['snippet_type'] ) : 'et_code_snippet_html_js';
				$favorite_status = isset( $update_details['favoriteStatus'] ) && 'on' === sanitize_text_field( $update_details['favoriteStatus'] ) ? 'favorite' : '';
			} else {
				$content      = get_the_content( null, false, $item_id );
				$snippet_type = wp_get_post_terms( $item_id, $code_snippets_type->name, array( 'fields' => 'names' ) );
				$snippet_type = is_wp_error( $snippet_type ) || '' === $snippet_type ? 'et_code_snippet_html_js' : sanitize_text_field( $snippet_type[0] );
			}

			$new_item = array(
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => ET_CODE_SNIPPET_POST_TYPE,
				'tax_input'    => array(),
			);

			$new_item['tax_input'][ $code_snippets_categories->name ] = $categories;
			$new_item['tax_input'][ $code_snippets_tags->name ]       = $tags;
			$new_item['tax_input'][ $code_snippets_type->name ]       = $snippet_type;

			$new_id = wp_insert_post( $new_item );
			break;
		case 'rename':
			if ( ! current_user_can( 'edit_post', $item_id ) || ET_CODE_SNIPPET_POST_TYPE !== $post_type ) {
				return;
			}

			if ( $item_name ) {
				$item_update['post_title'] = $item_name;
				wp_update_post( $item_update );
			}
			break;

		case 'toggle_fav':
			update_post_meta( $item_id, 'favorite_status', $favorite_status );
			break;

		case 'delete':
			if ( ! current_user_can( 'edit_post', $item_id ) || ET_CODE_SNIPPET_POST_TYPE !== $post_type ) {
				return;
			}

			wp_trash_post( $item_id );
			break;

		case 'delete_permanently':
			if ( ! current_user_can( 'edit_post', $item_id ) || ET_CODE_SNIPPET_POST_TYPE !== $post_type ) {
				return;
			}

			wp_delete_post( $item_id, true );
			break;

		case 'restore':
			if ( ! current_user_can( 'edit_post', $item_id ) || ET_CODE_SNIPPET_POST_TYPE !== $post_type ) {
				return;
			}

			$publish_fn = function() {
				return 'publish';
			};

			// wp_untrash_post() restores the post to `draft` by default, we have to set `publish` status via filter.
			add_filter( 'wp_untrash_post_status', $publish_fn );

			wp_untrash_post( $item_id );

			remove_filter( 'wp_untrash_post_status', $publish_fn );
			break;

		case 'edit_cats':
			wp_set_object_terms( $item_id, $categories, $code_snippets_categories->name );
			wp_set_object_terms( $item_id, $tags, $code_snippets_tags->name );
			break;
	}

	// Continue with additional data.

	$processed_new_categories = array();
	$processed_new_tags       = array();

	$updated_categories = get_terms(
		array(
			'taxonomy'   => $code_snippets_categories->name,
			'hide_empty' => false,
		)
	);

	$updated_tags = get_terms(
		array(
			'taxonomy'   => $code_snippets_tags->name,
			'hide_empty' => false,
		)
	);

	if ( ! empty( $updated_categories ) ) {
		foreach ( $updated_categories as $single_category ) {
			$processed_new_categories[] = array(
				'id'       => $single_category->term_id,
				'name'     => $single_category->name,
				'count'    => $single_category->count,
				'location' => 'local',
			);
		}
	}

	if ( ! empty( $updated_tags ) ) {
		foreach ( $updated_tags as $single_tag ) {
			$processed_new_tags[] = array(
				'id'       => $single_tag->term_id,
				'name'     => $single_tag->name,
				'count'    => $single_tag->count,
				'location' => 'local',
			);
		}
	}

	return array(
		'updatedItem'  => $item_id,
		'newItem'      => $new_id,
		'updateType'   => $update_type,
		'categories'   => $categories,
		'tags'         => $tags,
		'updatedTerms' => array(
			'categories' => $processed_new_categories,
			'tags'       => $processed_new_tags,
		),
	);
}

/**
 * Export the Code Snippets library item from cloud.
 *
 * @since 4.19.0
 *
 * @param array $cloud_content Optional cloud content.
 *
 * @return array
 */
function et_code_snippets_library_export_cloud_item( $cloud_content ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die();
	}

	$content                 = array( 'context' => 'et_code_snippet' );
	$content['snippet_type'] = $cloud_content['snippet_type'];
	$content['data']         = $cloud_content['data'];

	return $content;
}

/**
 * Export the Code Snippets library item local item.
 *
 * @since 4.19.0
 *
 * @param int $post_id Item ID.
 *
 * @return array
 */
function et_code_snippets_library_export_local_item( $post_id ) {
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die();
	}

	$snippet      = get_post( $post_id );
	$snippet_type = current( wp_get_post_terms( $post_id, 'et_code_snippet_type' ) );

	$content                 = array( 'context' => 'et_code_snippet' );
	$content['snippet_type'] = $snippet_type->slug;
	$content['data']         = $snippet->post_content;

	return $content;
}

/**
 * Export the Code Snippets library item directly.
 *
 * @since 4.19.0
 * @param array $direct_export Contain snippet-type and content.
 *
 * @return array
 */
function et_code_snippets_library_export_directly( $direct_export ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die();
	}

	if ( ! trim( $direct_export['content'] ) ) {
		return false;
	}

	$content                 = array( 'context' => 'et_code_snippet' );
	$content['snippet_type'] = $direct_export['snippet_type'];
	$content['data']         = stripslashes_deep( $direct_export['content'] );

	return $content;
}

/**
 * Export the Code Snippets library item.
 *
 * @since 4.19.0
 *
 * @param int   $id            Item ID.
 * @param array $cloud_content Optional cloud content.
 * @param array $direct_export Contain snippet-type and content.
 *
 * @return array
 */
function et_code_snippets_library_export_item_data( $id, $cloud_content, $direct_export ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die();
	}

	if ( empty( $id ) ) {
		return false;
	}

	$id = absint( $id );

	if ( empty( $direct_export ) ) {
		$export_content = empty( $cloud_content ) ?
			et_code_snippets_library_export_local_item( $id )
			:
			et_code_snippets_library_export_cloud_item( $cloud_content );
	} else {
		$export_content = et_code_snippets_library_export_directly( $direct_export );
	}

	if ( empty( $export_content ) ) {
		return;
	}

	$transient = 'et_code_snippet_export_' . get_current_user_id() . '_' . $id;
	set_transient( $transient, $export_content, 60 * 60 * 24 );

	return $export_content;
}

/**
 * Import the Code Snippets library item.
 *
 * @since 4.19.0
 *
 * @return array
 */
function et_code_snippets_library_import_item_data() {
	if ( ! current_user_can( 'edit_posts' ) || ! isset( $_POST['fileData'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in `et_builder_security_check` before calling this function.
		return false;
	}

	// phpcs:disable ET.Sniffs.ValidatedSanitizedInput.InputNotSanitized -- wp_insert_post function does sanitization.
	$file_data    = $_POST['fileData']; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in `et_builder_security_check` before calling this function.
	$file_name    = sanitize_text_field( $file_data['title'] );
	$file_content = isset( $_POST['fileContent'] ) ? json_decode( stripslashes( $_POST['fileContent'] ), true ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in `et_builder_security_check` before calling this function.
	// phpcs:enable

	if ( ! isset( $file_data['type'] ) ) {
		return false;
	}

	$snippet = array(
		'item_name' => $file_name,
		'item_type' => $file_data['type'],
		'content'   => $file_content,
	);

	return et_code_snippets_save_to_local_library( $snippet );
}


/**
 * Remove, Rename or Add new Category/Tag into local library.
 *
 * @since 4.19.0
 *
 * @param array $payload Array with the update details.
 *
 * @return array
 */
function et_code_snippets_library_perform_terms_update( $payload ) {
	if ( ! current_user_can( 'manage_categories' ) ) {
		wp_die();
	}

	$new_terms = array();

	foreach ( $payload as $single_item ) {
		$filter_type = $single_item['filterType'];
		$taxonomy    = 'tags' === $single_item['filterType'] ? 'layout_tag' : 'layout_category';

		switch ( $single_item['updateType'] ) {
			case 'remove':
				$term_id = (int) $single_item['id'];
				wp_delete_term( $term_id, $taxonomy );
				break;
			case 'rename':
				$term_id  = (int) $single_item['id'];
				$new_name = (string) $single_item['newName'];

				if ( '' !== $new_name ) {
					$updated_term_data = wp_update_term( $term_id, $taxonomy, array( 'name' => $new_name ) );

					if ( ! is_wp_error( $updated_term_data ) ) {
						$new_terms[] = array(
							'name'     => $new_name,
							'id'       => $updated_term_data['term_id'],
							'location' => 'local',
						);
					}
				}
				break;
			case 'add':
				$term_name     = (string) $single_item['id'];
				$new_term_data = wp_insert_term( $term_name, $taxonomy );

				if ( ! is_wp_error( $new_term_data ) ) {
					$new_terms[] = array(
						'name'     => $term_name,
						'id'       => $new_term_data['term_id'],
						'location' => 'local',
					);
				}
				break;
		}
	}

	return array(
		'newFilters'        => $new_terms,
		'filterType'        => $filter_type,
		'localLibraryTerms' => [
			'layout_category' => et_theme_builder_library_get_processed_terms( 'layout_category' ),
			'layout_tag'      => et_theme_builder_library_get_processed_terms( 'layout_tag' ),
		],
	);
}

/**
 * Save a code snippet to local library.
 *
 * @param array $item Item data.
 */
function et_code_snippets_save_to_local_library( $item ) {
	$_         = et_();
	$item_name = sanitize_text_field( $_->array_get( $item, 'item_name', '' ) );
	$content   = $_->array_get( $item, 'content', '' );

	$post_id = wp_insert_post(
		array(
			'post_type'    => ET_CODE_SNIPPET_POST_TYPE,
			'post_status'  => 'publish',
			'post_title'   => $item_name,
			'post_content' => $content,
		)
	);

	et_local_library_set_item_taxonomy( $post_id, $item );

	return $post_id;
}
