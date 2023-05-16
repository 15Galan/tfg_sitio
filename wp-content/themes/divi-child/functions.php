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
        'start_action' => 'start-lab',
		'stop_action' => 'stop-lab'
    ) );

	$current_lab = galanlab_has_lab_running();
	wp_localize_script( 'my_js', 'current_lab', $current_lab ? : [] );
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


function galanlab_start() {
    // Check for nonce security
    $nonce = sanitize_text_field( $_POST['nonce'] );

    if ( ! wp_verify_nonce( $nonce, 'my-ajax-nonce' ) ) {
        galanlab_json_error( 'Se ha producido un error de seguridad!' );
    }

	if ( galanlab_has_lab_running() ) {
		galanlab_json_error( 'Ya tienes corriendo un laboratorio' );
	}

	/* Checking the lab */
	$url = wp_get_referer();
	$post_id = url_to_postid( $url );
	$docker_image = get_post_meta( $post_id, 'docker_image_name', true );

	if ( empty( $docker_image ) ) {
		galanlab_json_error( 'Este laboratorio no existe' );
	}

	$port = galanlab_get_available_port();
	if ( empty( $port ) ) {
		galanlab_json_error( 'No hay puertos disponibles' );
	}

	$running_id = galanlab_execute_instance( $port, $docker_image );
	// $running_id = 'kfaj8e8fkfs8sdf7sfd8s9sfd7adf8adsflkfjeieifksdfa89';

	if ( $running_id ) {
		$ip = galanlab_get_ip_of_container( $running_id );
		$now = new Datetime();
		$userId = get_current_user_id();
		// add_user_meta( $userId, '_lab_start_', $now );
		$labInfo = [
			'ip' => $ip,
			'port' => $port,
			'date' => $now,
			'post_id' => $post_id,
			'id' => $running_id,
		];
		add_user_meta( $userId, '_current_lab_info_', json_encode( $labInfo ) );
		galanlab_json_success( "Ya puedes probar el laboratorio accediendo al puerto $port de la ip $ip.
			Tienes una hora para resolverlo." );
	}

	galanlab_json_error( "Se ha producido un error al iniciar el laboratorio" );
}
// add_action( 'wp_ajax_nopriv_start-lab', 'galanlab_start' );
add_action( 'wp_ajax_start-lab', 'galanlab_start' );

function galanlab_stop() {
	// Check for nonce security
    $nonce = sanitize_text_field( $_POST['nonce'] );

    if ( ! wp_verify_nonce( $nonce, 'my-ajax-nonce' ) ) {
        galanlab_json_error( 'Se ha producido un error de seguridad!' );
    }

	/* Checking the lab */
	$url = wp_get_referer();
	$post_id = url_to_postid( $url );
	$current_lab = galanlab_has_lab_running();

	if ( $current_lab ) {
		if ( $current_lab['post_id'] == $post_id ) {
			if( galanlab_stop_instance( $current_lab['id'] ) ) {
				delete_user_meta( get_current_user_id(), '_current_lab_info_' );
				galanlab_json_success( 'Laboratorio parado correctamente' );
			} else {
				galanlab_json_error( 'No se ha podido parar el laboratorio' );
			}
		}
	}
	galanlab_json_success( 'Laboratorio parado correctamente' );
}
add_action( 'wp_ajax_stop-lab', 'galanlab_stop');


// function  galanlab_available_port() {
// 	$user_query = new WP_User_Query([
// 		'meta_key' => '_current_locked_port_'
// 	]);
	
// }

function galanlab_get_available_port() {
	global $wpdb;

	$allowed_ports = range(5555, 9999);

	/*
		SELECT CAST(TRIM(BOTH '\"' FROM (JSON_EXTRACT(meta_value, \"$.port\"))) AS UNSIGNED) as port
		FROM {$wpdb->prefix}usermeta
		WHERE meta_key = '_current_lab_info_'
		ORDER BY port ASC
	*/
	$busy_ports_rs = $wpdb->get_results( "SELECT meta_value->>\"$.port\" as port
		FROM {$wpdb->prefix}usermeta
		WHERE meta_key = '_current_lab_info_'
		ORDER BY port ASC", ARRAY_A );
	
	$busy_ports = array_map( function ($row) {
		return (int)$row['port'];
	}, $busy_ports_rs );

	$available_ports = array_diff( $allowed_ports, $busy_ports );
	if ( empty( $available_ports ) )
		return 0;
	$rand_key = array_rand( $available_ports );
	return $available_ports[$rand_key];
}

function galanlab_execute_instance( $port, $docker_image ) {
	exec( "docker run --rm -p $port:22 -d $docker_image", $output, $return_var );
	return galanlab_is_container_running( $output );
}

function galanlab_stop_instance( $container_id ) {
	exec( "docker exec $container_id stop", $output, $return_var );
	return galanlab_is_container_stopped( $output );
}

/**
 * It analyzes the output of the command and decide if it was right execution
 * @return bool|string false if not running, ID if running
 */
function galanlab_is_container_running( $output ) {
	// $lines_arr = preg_split( '/\n|\r/', $output );
	$num_lines = count( $output ); 
	return $num_lines > 1 ? false : $output[0];
}

function galanlab_is_container_stopped( $output ) {
	return true;
	// // $lines_arr = preg_split( '/\n|\r/', $output );
	// $num_lines = count( $output ); 
	// return $num_lines > 1 ? false : $output[0];
}

function galanlab_get_ip_of_container( $container_id ) {
	return '127.0.0.1';
}

function galanlab_has_lab_running( $user_id = false ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	$current_lab = get_user_meta( $user_id, '_current_lab_info_', true );
	$current_lab_data = json_decode( $current_lab, true );
	return empty( $current_lab_data ) ? false : $current_lab_data;
}

function galanlab_json_success( $message ) {
	wp_send_json_success( $message );
}

function galanlab_json_error( $message ) {
	wp_send_json_error( $message );
}

add_shortcode( 'lab_button', 'galanlab_button_shortcode' );
function galanlab_button_shortcode( $atts ) {
	$current_id = get_the_ID();
	$current_lab = galanlab_has_lab_running();
	$output = '';

	if ( ! $current_lab || $current_lab['post_id'] == $current_id ) {
		// Start button
		$output .= galanlab_draw_button_html( 'Empezar laboratorio', 'startlab-button', '#41E2BA' );
		
		// Stop button
		$output .= galanlab_draw_button_html( 'Terminar laboratorio', 'stoplab-button', '#E24100' );
	}

	$info_text = '';
	if ( $current_lab ) {
		$current_lab_post_id = $current_lab['post_id'];
		$link_to_current_lab = '<a href="' . get_the_permalink( $current_lab_post_id ) . '" title="' . get_the_title( $current_lab_post_id ) . '">' . get_the_title( $current_lab_post_id ) . '</a>';
		$link_html = $current_id == $current_lab['post_id'] ? '' : $link_to_current_lab;
		// Labs's info
		$info_text = "Ya tienes un laboratorio corriendo. Puedes conectarte al laboratorio $link_html en el puerto {$current_lab['port']} de la IP {$current_lab['ip']}.";
	}
	$output .= galanlab_draw_text( $info_text );

	return $output;
}

function galanlab_draw_button_html( $button_text, $id, $color ) {
	return do_shortcode( '[et_pb_button button_text="' . $button_text . '"
button_alignment="center" module_id="' . $id . '" _builder_version="4.21.0"
_module_preset="4ebb5d01-8f2a-40d2-99f6-c69d0bb85164" custom_button="on" button_text_size="14px"
button_text_color="#FFFFFF" button_bg_color="' . $color . '" button_border_width="0px" button_border_radius="0px"
button_letter_spacing="3px" button_font="|700||on|||||" button_icon="&#x45;||divi||400"
custom_margin="||||false|false" custom_margin_tablet="||||false|false" custom_margin_phone="||||false|false"
custom_margin_last_edited="on|phone" custom_padding="18px|30px|18px|30px|true|true"
custom_padding_tablet="16px|25px|16px|25px|true|true" custom_padding_phone="14px|20px|14px|20px|true|true"
custom_padding_last_edited="on|desktop" animation_style="slide" animation_direction="bottom" animation_duration="600ms"
animation_intensity_slide="10%" hover_enabled="0" button_text_size_tablet="" button_text_size_phone="12px"
button_text_size_last_edited="on|phone" locked="off"
global_colors_info="{%22gcid-98d90e25-2ae4-4d27-95a6-0d39ba22d29e%22:%91%93}" button_bg_color__hover="#FFFFFF"
button_bg_color__hover_enabled="on|hover" button_bg_enable_color__hover="on" button_text_color__hover_enabled="on|hover"
button_text_color__hover="' . $color . '" sticky_enabled="0"][/et_pb_button]' );
}

function galanlab_draw_text( $text ) {
	return do_shortcode( '[et_pb_text module_id="actionlab-output" _builder_version="4.21.0" _module_preset="default" text_text_color="#FFFFFF" text_orientation="center" global_colors_info="{}"]' .
		$text . '[/et_pb_text]' );
}
