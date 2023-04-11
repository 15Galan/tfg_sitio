<?php
function my_theme_enqueue_styles() { 
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );

function load_my_scripts() {
    wp_enqueue_script( 'my_js', get_theme_file_uri( 'js/lab.js'), array('jquery') );

    wp_localize_script( 'my_js', 'ajax_var', array(
        'url'    => admin_url( 'admin-ajax.php' ),
        'nonce'  => wp_create_nonce( 'my-ajax-nonce' ),
        'action' => 'start-lab'
    ) );
}
add_action( 'wp_enqueue_scripts', 'load_my_scripts' );

// Register Custom Post Type
function lab_post_type() {

	$labels = array(
		'name'                  => _x( 'Laboratorios', 'Post Type General Name', 'labgalan_domain' ),
		'singular_name'         => _x( 'Laboratorio', 'Post Type Singular Name', 'labgalan_domain' ),
		'menu_name'             => __( 'Laboratorios', 'labgalan_domain' ),
		'name_admin_bar'        => __( 'Laboratorio', 'labgalan_domain' ),
		'archives'              => __( 'Item Archives', 'labgalan_domain' ),
		'attributes'            => __( 'Item Attributes', 'labgalan_domain' ),
		'parent_item_colon'     => __( 'Parent Item:', 'labgalan_domain' ),
		'all_items'             => __( 'All Items', 'labgalan_domain' ),
		'add_new_item'          => __( 'Add New Item', 'labgalan_domain' ),
		'add_new'               => __( 'Add New', 'labgalan_domain' ),
		'new_item'              => __( 'New Item', 'labgalan_domain' ),
		'edit_item'             => __( 'Edit Item', 'labgalan_domain' ),
		'update_item'           => __( 'Update Item', 'labgalan_domain' ),
		'view_item'             => __( 'View Item', 'labgalan_domain' ),
		'view_items'            => __( 'View Items', 'labgalan_domain' ),
		'search_items'          => __( 'Search Item', 'labgalan_domain' ),
		'not_found'             => __( 'Not found', 'labgalan_domain' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'labgalan_domain' ),
		'featured_image'        => __( 'Featured Image', 'labgalan_domain' ),
		'set_featured_image'    => __( 'Set featured image', 'labgalan_domain' ),
		'remove_featured_image' => __( 'Remove featured image', 'labgalan_domain' ),
		'use_featured_image'    => __( 'Use as featured image', 'labgalan_domain' ),
		'insert_into_item'      => __( 'Insert into item', 'labgalan_domain' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'labgalan_domain' ),
		'items_list'            => __( 'Items list', 'labgalan_domain' ),
		'items_list_navigation' => __( 'Items list navigation', 'labgalan_domain' ),
		'filter_items_list'     => __( 'Filter items list', 'labgalan_domain' ),
	);
	$args = array(
		'label'                 => __( 'Laboratorio', 'labgalan_domain' ),
		'description'           => __( 'Tipo de entrada para la creación de un laboratorio', 'labgalan_domain' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'custom-fields' ),
		'taxonomies'            => array( 'post_tag' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'menu_icon'             => 'dashicons-welcome-learn-more',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
	);
	register_post_type( 'lab', $args );

}
add_action( 'init', 'lab_post_type', 0 );


function run_dockerfile() {
    // Check for nonce security
    $nonce = sanitize_text_field( $_POST['nonce'] );

    if ( ! wp_verify_nonce( $nonce, 'my-ajax-nonce' ) ) {
        die ('¡Nonce inválido!');
    }

    echo nl2br("Laboratorio iniciado (pero esta vez es un 'ls' en el servidor).\n\n");
    echo nl2br(shell_exec('ls'));

    // $args = array(
    //     'post_type' => 'evento',
    //     'meta_query' => array(
    //         array(
    //             'key'     => 'destacado',
    //             'value'   => 1,
    //             'compare' => '=',
    //         ),
    //     ),
    // );
    // $query = new WP_Query( $args );

    // if ( $query->have_posts() ) {
    //     echo '<ul>';
    //     while ($query->have_posts()) {
    //         $query->the_post();
    //         echo '<li>' . get_the_title() . '</li>';
    //     }
    //     echo '</ul>';
    // }

    wp_die();
}
add_action( 'wp_ajax_nopriv_start-lab', 'run_dockerfile' );
add_action( 'wp_ajax_start-lab', 'run_dockerfile' );
