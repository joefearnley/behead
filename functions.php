<?php
/**
 * Behead Theme Functions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme setup function
 */
function behead_theme_setup() {
    // Add theme support for various WordPress features
    add_theme_support('post-thumbnails');

    add_theme_support( 'post-formats',  array( 'aside', 'gallery', 'quote', 'image', 'video' ) );
}

add_action('after_setup_theme', 'behead_theme_setup');

/**
 * Debugging function to dump variable and stop execution.
 *
 * @param mixed $var Variable to dump.
 */
function dd($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    die();
}

/**
 * Filter to restrict access to the REST API.
 *
 * This function checks the incoming request's host against a list of allowed domains.
 * If the request is not from an allowed domain, it returns a 403 Forbidden error.
 *
 * @param WP_Error|array $errors The errors to filter.
 * @return WP_Error|array The filtered errors or a 403 error if access is denied.
 */
function behead_filter_incoming_connections( $errors ){
    // Allow access only from specific domains
    $allowed_domains = [
        'localhost:8000',
        'https://mmdoomsday-site.jfrnly.dev/',
    ];

    $request_server = $_SERVER['HTTP_HOST'];

    if( ! in_array( $request_server, $allowed_domains ) )
        return new WP_Error( 'forbidden_access', 'Access denied', array( 'status' => 403 ) );

    return $errors;
}

/**
 * Grab the level details for a sepcific Megaman Maker level.
 *
 * @param array $data Options for the function.
 * @return string|null Post title for the latest, * or null if none.
 */
function behead_get_mmm_level_details($data) {
    $levelId = $data['level_id'];

    // Validate the level ID
    if ( ! is_numeric( $levelId ) || $levelId <= 0 ) {
        return new WP_Error( 'invalid_level_id', 'Invalid level ID provided', array( 'status' => 400 ) );
    }

    // get the level data from the Mega Maban Maker API
    $megaManMakerResponse = wp_remote_get( 'https://api.megamanmaker.com/level/' . $levelId );
    $body = wp_remote_retrieve_body( $megaManMakerResponse );
    $megaManMakerData = json_decode( $body, true );

    if ( ! $megaManMakerData || ! isset( $megaManMakerData['id'] ) ) {
        return new WP_Error( 'no_level_found', 'No level found with the provided ID', array( 'status' => 404 ) );
    }

    // now get the level data from the WordPress database
    $levels = get_posts(array(
        'posts_per_page' => 1,
        'post_type' => 'levels',
        'meta_key' => 'mmm_level_id',
        'meta_value' => $levelId
    ));

    $levelData = $levels ? $levels[0] : null;

    if ( ! $levelData ) {
        return new WP_Error( 'no_level_post', 'No level post found for the provided ID', array( 'status' => 404 ) );
    }

    $megaManMakerData['post_title'] = $levelData->post_title;
    $megaManMakerData['post_id'] = $levelData->ID;

    $postThumbnail = get_the_post_thumbnail_url( $levelData->ID, 'full' );
    $megaManMakerData['thumbnail_url'] = $postThumbnail ? $postThumbnail : null;

    wp_send_json( $megaManMakerData , 200 );
}

// register the route for the REST API
// example usage: /wp-json/mmdd/v1/mmm-level-details/12345
add_action( 'rest_api_init', function () {
    header( 'Access-Control-Allow-Origin: *' );
    header( 'Access-Control-Allow-Methods: GET' );

    register_rest_route( 'mmdd/v1',
        '/mmm-level-details/(?P<level_id>\d+)', array( 
            'methods' => 'GET',
            'callback' => 'behead_get_mmm_level_details',
        ),  );
} );

// Filter to restrict access to the REST API
add_filter( 'rest_authentication_errors', 'behead_filter_incoming_connections' );
