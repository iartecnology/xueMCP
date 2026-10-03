<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WordPress Core Posts Helper
 */
class MCP_WP_Posts_API {

	public static function get_post( $args ) {
		$post_id = $args['id'] ?? null;
		$slug    = $args['slug'] ?? null;
		$type    = $args['post_type'] ?? 'any';

		if ( $post_id ) {
			$post = get_post( $post_id );
		} elseif ( $slug ) {
			$posts = get_posts( array(
				'name'        => $slug,
				'post_type'   => $type,
				'post_status' => 'publish',
				'numberposts' => 1
			) );
			$post = ! empty( $posts ) ? $posts[0] : null;
		} else {
			throw new Exception( "Missing 'id' or 'slug' parameter." );
		}

		if ( ! $post ) {
			throw new Exception( "Post not found." );
		}

		return $post;
	}

	public static function create_post( $args ) {
		$post_data = array(
			'post_title'   => $args['title'],
			'post_content' => $args['content'] ?? '',
			'post_status'  => $args['status'] ?? 'draft',
			'post_type'    => $args['post_type'] ?? 'post',
			'post_author'  => $args['author'] ?? get_current_user_id(),
		);

		if ( ! empty( $args['category'] ) ) {
			$cats = is_array( $args['category'] ) ? $args['category'] : array( $args['category'] );
			$post_data['post_category'] = array_map( 'intval', $cats );
		} elseif ( ! empty( $args['categories'] ) ) {
			$cats = is_array( $args['categories'] ) ? $args['categories'] : array( $args['categories'] );
			$post_data['post_category'] = array_map( 'intval', $cats );
		}

		if ( ! empty( $args['tags'] ) ) {
			$post_data['tags_input'] = $args['tags'];
		}

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			throw new Exception( $post_id->get_error_message() );
		}

		// Handle image if provided
		if ( ! empty( $args['featured_media'] ) ) {
			set_post_thumbnail( $post_id, intval( $args['featured_media'] ) );
		} elseif ( ! empty( $args['image_url'] ) ) {
			if ( class_exists( 'MCP_Listeo_API' ) ) {
				$image_id = MCP_Listeo_API::set_listing_thumbnail_from_url_public( $post_id, $args['image_url'] );
				if ( ! is_wp_error( $image_id ) && $image_id ) {
					set_post_thumbnail( $post_id, $image_id );
				}
			}
		}

		return array( 'id' => $post_id, 'link' => get_permalink( $post_id ) );
	}

    public static function search_posts( $args ) {
        $query_args = array(
            's'           => $args['s'] ?? '',
            'post_type'   => $args['post_type'] ?? 'post',
            'post_status' => 'publish',
            'posts_per_page' => $args['limit'] ?? 10,
        );

        $query = new WP_Query( $query_args );
        return $query->posts;
    }

    public static function update_post( $args ) {
        $post_id = $args['id'] ?? null;
        if ( ! $post_id ) {
            throw new Exception( "Missing 'id' parameter for update." );
        }

        $post_data = array( 'ID' => $post_id );
        if ( isset( $args['title'] ) ) $post_data['post_title'] = $args['title'];
        if ( isset( $args['content'] ) ) $post_data['post_content'] = $args['content'];
        if ( isset( $args['status'] ) ) $post_data['post_status'] = $args['status'];

        if ( ! empty( $args['category'] ) ) {
            $cats = is_array( $args['category'] ) ? $args['category'] : array( $args['category'] );
            $post_data['post_category'] = array_map( 'intval', $cats );
        } elseif ( ! empty( $args['categories'] ) ) {
            $cats = is_array( $args['categories'] ) ? $args['categories'] : array( $args['categories'] );
            $post_data['post_category'] = array_map( 'intval', $cats );
        }

        if ( ! empty( $args['tags'] ) ) {
            $post_data['tags_input'] = $args['tags'];
        }

        $updated_id = wp_update_post( $post_data, true );

        if ( is_wp_error( $updated_id ) ) {
            throw new Exception( $updated_id->get_error_message() );
        }

        if ( ! empty( $args['featured_media'] ) ) {
            set_post_thumbnail( $post_id, intval( $args['featured_media'] ) );
        } elseif ( ! empty( $args['image_url'] ) ) {
            if ( class_exists( 'MCP_Listeo_API' ) ) {
                $image_id = MCP_Listeo_API::set_listing_thumbnail_from_url_public( $post_id, $args['image_url'] );
                if ( ! is_wp_error( $image_id ) && $image_id ) {
                    set_post_thumbnail( $post_id, $image_id );
                }
            }
        }

        return array( 'id' => $updated_id, 'link' => get_permalink( $updated_id ) );
    }
}
