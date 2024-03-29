<?php


/**
 * Carga el estilo CSS del tema padre como una hoja de estilo del tema hijo.
 */
function my_theme_enqueue_styles() { 
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}

// Registrar la función anterior para que se ejecute en el evento 'wp_enqueue_scripts'.
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );


/**
 * Carga el archivo JS 'lab.js' y configura las variables necesarias para la
 * comunicación AJAX.
 */
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

// Registrar la función anterior para que se ejecute en el evento 'wp_enqueue_scripts'.
add_action( 'wp_enqueue_scripts', 'load_my_scripts' );


/**
 * Registra un nuevo tipo de publicación en el sitio WordPress para los
 * laboratorios y su documentación.
 */
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
		'supports'              => array( 'title', 'editor', 'custom-fields', 'thumbnail' ),
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

// Registrar la función anterior para que se ejecute en el evento 'init'.
add_action( 'init', 'lab_post_type', 0 );


/**
 * Inicia un laboratorio.
 */
function galanlab_start() {
    // Comprobación de nonce correcto
    $nonce = sanitize_text_field( $_POST['nonce'] );

    if ( ! wp_verify_nonce( $nonce, 'my-ajax-nonce' ) ) {
        galanlab_json_error( 'Se ha producido un error de seguridad!' );
    }

	// Comprobación de usuario sin otro laboratorio activo
	if ( galanlab_has_lab_running() ) {
		galanlab_json_error( 'Ya tienes corriendo un laboratorio' );
	}

	// Datos del laboratorio actual
	$url = wp_get_referer();
	$post_id = url_to_postid( $url );
	$docker_image = get_post_meta( $post_id, 'docker_image_name', true );

	// Comprobación de la existencia de laboratorio actual
	if ( empty( $docker_image ) ) {
		galanlab_json_error( 'Este laboratorio no existe' );
	}

	// Comprobación de la existencia de puertos disponibles
	$port = galanlab_get_available_port();

	if ( empty( $port ) ) {
		galanlab_json_error( 'No hay puertos disponibles' );
	}

	// Ejecución del laboratorio
	$running_id = galanlab_execute_instance( $port, $docker_image );

	if ( $running_id ) {
		$ip = galanlab_get_ip_of_container( $running_id );
		$now = new Datetime();
		$userId = get_current_user_id();
		$user_name = get_userdata( $userId )->user_login;
		// add_user_meta( $userId, '_lab_start_', $now );
		$labInfo = [
			'ip' => $ip,
			'port' => $port,
			'date' => $now,
			'post_id' => $post_id,
			'id' => $running_id,
		];
		add_user_meta( $userId, '_current_lab_info_', json_encode( $labInfo ) );

		galanlog_add_entry( $now, $user_name, '[INICIO]', $docker_image, $port );
		galanlab_json_success( "Laboratorio activo en $ip:$port." );
	}


	galanlog_add_entry( $now, $user_name, '[ERROR]', $docker_image, $port );
	galanlab_json_error( "Se ha producido un error al iniciar el laboratorio." );
}

// Registrar la función anterior para que se ejecute en el evento 'wp_ajax_start-lab'.
add_action( 'wp_ajax_start-lab', 'galanlab_start' );


/**
 * Detiene -y destruye- un laboratorio.
 */
function galanlab_stop() {
	// Comprobación de nonce correcto
    $nonce = sanitize_text_field( $_POST['nonce'] );

    if ( ! wp_verify_nonce( $nonce, 'my-ajax-nonce' ) ) {
        galanlab_json_error( 'Se ha producido un error de seguridad.' );
    }

	// Datos del laboratorio actual
	$url = wp_get_referer();
	$post_id = url_to_postid( $url );
	$current_lab = galanlab_has_lab_running();
	$lab_image = get_post_meta( $post_id, 'docker_image_name', true );

	// Comprobación de la existencia de laboratorio actual
	if ( $current_lab ) {
		if ( $current_lab['post_id'] == $post_id ) {
			$now = new Datetime();
			$userId = get_current_user_id();
			$user_name = get_userdata( $userId )->user_login;

			if( galanlab_stop_instance( $current_lab['id'] ) ) {
				delete_user_meta( get_current_user_id(), '_current_lab_info_' );

				galanlog_add_entry( $now, $user_name, '[DETUVO]', $lab_image, $current_lab['port'] );
				galanlab_json_success( 'Laboratorio detenido correctamente' );

			} else {
				galanlog_add_entry( $now, $userId, '[ERROR]', $lab_image, $current_lab['port'] );
				galanlab_json_error( 'No se ha podido detener el laboratorio' );
			}
		}
	}

	galanlab_json_success( 'Laboratorio detenido correctamente' );
}

// Registrar la función anterior para que se ejecute en el evento 'wp_ajax_stop-lab'.
add_action( 'wp_ajax_stop-lab', 'galanlab_stop');


/**
 * Determina un puerto disponible para un nuevo laboratorio.
 *
 * @return int	Un puerto disponible; 0 en caso contrario.
 */
function galanlab_get_available_port() {
	global $wpdb;	// Objeto de la base de datos de WordPress

	$allowed_ports = range(5555, 9999);		// Puertos permitidos del sistema

	// Consultar los puertos ocupados en la base de datos
	$busy_ports_rs = $wpdb->get_results(
		"SELECT meta_value->>\"$.port\" as port
			FROM {$wpdb->prefix}usermeta
				WHERE meta_key = '_current_lab_info_'
					ORDER BY port ASC", ARRAY_A
		);
	
	// Extraer los puertos ocupados del resultado de la consulta anterior
	$busy_ports = array_map(
			function ($row) {
				return (int)$row['port'];
			},
			$busy_ports_rs
		);
	
	$available_ports = array_diff( $allowed_ports, $busy_ports );

	// Comprobar si hay puertos disponibles
	if ( empty( $available_ports ) )
		return 0;

	$rand_key = array_rand( $available_ports );		// Puerto disponible aleatorio

	return $available_ports[$rand_key];
}


/**
 * Ejecuta el comando 'docker run' en el sistema para iniciar un contenedor.
 *
 * @param int		Puerto en el que ejecutar el contenedor.
 * @param string	Nombre de la imagen Docker a ejecutar (laboratorio).
 * 
 * @return string	ID del contenedor ejecutado; false si no se pudo ejecutar.
 */
function galanlab_execute_instance( $port, $docker_image ) {
	$aux_command = "";

	if ( $docker_image == '09_analisis-trafico' ) {
		exec( "docker run --rm -p $port:3000 -d $docker_image", $output, $return_var );

	} else {
		exec( "docker run --rm -p $port:22 -d $docker_image", $output, $return_var );
	}
	
	return galanlab_is_container_running( $output );
}


/**
 * Ejecuta el comando 'docker stop' en el sistema para detener un contenedor.
 *
 * @param string	ID del contenedor a detener.
 * 
 * @return string 	ID del contenedor detenido; false si no se pudo detener.
 */
function galanlab_stop_instance( $container_id ) {
	exec( "docker stop $container_id", $output, $return_var );

	return galanlab_is_container_stopped( $output );
}


/**
 * Comprueba si un contenedor está en ejecución analizando la salida del
 * comando 'docker run': si está activo, la salida devuelta es el ID del
 * contenedor y un salto de línea; en caso contrario, devuelve un error
 * de varias líneas.
 * 
 * @param bool|string[]	 	false si hay un error; el ID en caso contrario.
 */
function galanlab_is_container_running( $output ) {
	$num_lines = count( $output );

	return $num_lines > 1 ? false : $output[0];
}


/**
 * Comprueba si un contenedor está detenido analizando la salida del
 * comando 'docker stop': si está detenido, la salida devuelta es el ID del
 * contenedor y un salto de línea; en caso contrario, devuelve un error
 * de varias líneas.
 * 
 * @param bool|string[]	 	false si hay un error; el ID en caso contrario.
 */
function galanlab_is_container_stopped( $output ) {
    return true;
}


/**
 * Obtiene la dirección IP de un contenedor.
 * 
 * @param string	ID del contenedor.
 * 
 * @return string	Dirección IP del contenedor.
 */
function galanlab_get_ip_of_container( $container_id ) {
	// TODO: eliminar esta función.

	// La IP no es necesaria, ya que siempre será 'localhost';
	// lo único dinámico es el puerto mapeado al contenedor.

	return '127.0.0.1';
}


/**
 * Comprueba si un usuario de la página tiene un laboratorio en ejecución,
 * lo que evita que pueda encender otros laboratorios.
 */
function galanlab_has_lab_running( $user_id = false ) {
	// ID del usuario actual
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	// Datos del laboratorio en ejecución por el usuario actual
	$current_lab = get_user_meta( $user_id, '_current_lab_info_', true );
	$current_lab_data = json_decode( $current_lab, true );

	return empty( $current_lab_data ) ? false : $current_lab_data;
}


/**
 * Define un éxito en la respuesta JSON.
 */
function galanlab_json_success( $message ) {
	wp_send_json_success( $message );
}


/**
 * Define un error en la respuesta JSON.
 */
function galanlab_json_error( $message ) {
	wp_send_json_error( $message );
}


/**
 * Coloca en la página el botón correcto para iniciar o detener un laboratorio.
 * 
 * @param string	Texto del botón.
 * 
 * @return string	HTML del botón.
 */
function galanlab_button_shortcode( $atts ) {
	// Atributos del shortcode
	$current_id = get_the_ID();
	$current_lab = galanlab_has_lab_running();
	$output = '';

	if ( ! $current_lab || $current_lab['post_id'] == $current_id ) {

		// Botón para iniciar un laboratorio
		$output .= galanlab_draw_button_html(
			'Iniciar laboratorio', 'startlab-button', '#41E2BA'
		);
		
		// Botón para detener de un laboratorio
		$output .= galanlab_draw_button_html(
			'Detener laboratorio', 'stoplab-button', '#E24100'
		);
	}

	$info_text = '';

	if ( $current_lab ) {
		// Datos del laboratorio en ejecución
		$current_lab_post_id = $current_lab['post_id'];
		$link_to_current_lab = '<a href="' . get_the_permalink( $current_lab_post_id ) . '" title="' . get_the_title( $current_lab_post_id ) . '">' . get_the_title( $current_lab_post_id ) . '</a>';
		$link_html = $current_id == $current_lab['post_id'] ? '' : $link_to_current_lab;
		
		// Información sobre el laboratorio en ejecución
		$info_text = "Laboratorio $link_html activo en {$current_lab['ip']}:{$current_lab['port']}.";
	}
	
	$output .= galanlab_draw_text( $info_text );

	return $output;
}

// Registra el shortcode 'lab_button' para que se pueda usar en el editor de WordPress.
add_shortcode( 'lab_button', 'galanlab_button_shortcode' );


/**
 * Genera el HTML del botón del laboratorio.
 * 
 * @param string 	Texto en el botón.
 * @param string 	ID del botón.
 * @param string 	Color del botón.
 * 
 * @return string 	HTML del botón.
 */
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


/**
 * Genera el HTML del texto de la zona del botón del laboratorio.
 * 
 * @param string 	Texto en el botón.
 * 
 * @return string 	HTML del botón.
 */
function galanlab_draw_text( $text ) {
	return do_shortcode( '[et_pb_text module_id="actionlab-output" _builder_version="4.21.0" _module_preset="default" text_text_color="#FFFFFF" text_orientation="center" global_colors_info="{}"]' .
		$text . '[/et_pb_text]' );
}


/**
 * Comprueba que los campos de texto del registro (nombre y contraseña)
 * sean válidos para registrar un nuevo usuario.
 * 
 * @param object 	Resultado de la validación.
 * @param object 	Tag del campo a validar.
 * 
 * @return object	Resultado de la validación si no hay error;
 * 					error correspondiente en caso contrario.
 */
function wpm_name_password_validation_filter( $result, $tag ) {
    $tag = new WPCF7_Shortcode( $tag );

	// Comprobar que las contraseñas coinciden
    if ( 'PASSWORD-CONFIRM' == $tag->name ) {
        $your_password         = isset( $_POST['PASSWORD'] ) ? trim( $_POST['PASSWORD'] ) : '';
        $your_password_confirm = isset( $_POST['PASSWORD-CONFIRM'] ) ? trim( $_POST['PASSWORD-CONFIRM'] ) : '';
        
		if ( $your_password != $your_password_confirm ) {
            $result->invalidate( $tag, "Verifica que las contraseñas sean iguales" );
        }
    }

	// Comprobar que el nombre de usuario no está registrado
	if ( 'nombre' == $tag->name ) {
		$your_name = isset( $_POST['nombre'] ) ? trim( $_POST['nombre'] ) : '';

		if ( username_exists( $your_name ) ) {
			$result->invalidate( $tag, "Este nombre de usuario ya está registrado" );
		}
	}

    return $result;
}

// Registrar el filtro para la validación del formulario de registro
add_filter( 'wpcf7_validate_text*', 'wpm_name_password_validation_filter', 20, 2 );


/**
 * Comprueba que el campo de correo del registro sea válido para
 * registrar un nuevo usuario.
 * 
 * @param object 	Resultado de la validación.
 * @param object 	Tag del campo a validar.
 * 
 * @return object	Resultado de la validación si no hay error;
 * 					error de correo repetido en caso contrario.
 */
function wpm_email_validation_filter( $result, $tag ) {
	$tag = new WPCF7_Shortcode( $tag );

	// Comprobar que el correo no está registrado:
	// este campo se trata a parte, porque es de tipo 'email*' y no ' texto*'
	if ( 'correo' == $tag->name ) {
		$your_email = isset( $_POST['correo'] ) ? trim( $_POST['correo'] ) : '';

		if ( email_exists( $your_email ) ) {
			$result->invalidate( $tag, "Este correo ya está registrado" );
		}
	}
	
	return $result;
}

// Registrar el filtro para la validación del formulario de registro
add_filter( 'wpcf7_validate_email*', 'wpm_email_validation_filter', 20, 2 );


/**
 * Crea un usuario a partir de los datos de un formulario de registro.
 * 
 * @param object 	Datos del formulario.
 * 
 * @return object	Datos del formulario.
 */
function create_user_from_registration( $cfdata ) {
    if ( !isset( $cfdata->posted_data ) && class_exists( 'WPCF7_Submission' ) ) {
        // Contact Form 7 version 3.9 removed $cfdata->posted_data and now
        // we have to retrieve it from an API
        $submission = WPCF7_Submission::get_instance();

        if ( $submission ) {
            $formdata = $submission->get_posted_data();
        }
    } elseif ( isset( $cfdata->posted_data ) ) {
        $formdata = $cfdata->posted_data;			// Versiones previas a 3.9 de Contact Form 7
    } else {
        return $cfdata;								// No se pueden obtener los datos
    }

    // Comprueba si el formulario es el de registro
    if ( $cfdata->title() == 'Registro') {
        $nombre = $formdata['nombre'];
        $username = $nombre;
        $password = $formdata['PASSWORD'];
        $email = $formdata['correo'];

		// Verificar si el usuario (o el correo) ya existe
        if ( !username_exists( $username )  && !email_exists( $email ) ) {
            $user_id = wp_create_user( $username, $password, $email );
            if ( !is_wp_error( $user_id ) ) {
                $userdata = [
                    'ID' => $user_id,
                ];

                $user['show_admin_bar_front'] = false;	// Ocultar la barra de administración al usuario
                
				// User Metadata
                foreach($usermeta as $key => $value) {
					update_user_meta( $user_id, $key, $value );
                }
            }
        }
    }
	
    return $cfdata;
}

// Registrar la función anterior para que se ejecute en el evento 'wpcf7_before_send_mail'.
// Este hook es propio de Contact Form 7.
add_action('wpcf7_before_send_mail', 'create_user_from_registration', 1);


/**
 * Redirige al usuario a la página inicial después de iniciar sesión.
 */
function galanlogin_redirect() {
	return '/';
}

// Registrar la función anterior para que se ejecute en el evento 'login_redirect'.
add_filter('login_redirect', 'galanlogin_redirect');


/**
 * Reemplaza los campos de texto por campos de contraseña en el formulario de registro.
 * 
 * @param string 	Contenido del formulario.
 */
function galanlab_password_fields($content) {
	$content = str_replace('type="text" name="PASSWORD"', 'type="password" name="PASSWORD"', $content);
	$content = str_replace('type="text" name="PASSWORD-CONFIRM"', 'type="password" name="PASSWORD-CONFIRM"', $content);
	return $content;
}

// Registrar la función anterior para que se ejecute en el evento 'wpcf7_form_elements'.
// Este hook es propio de Contact Form 7.
add_filter('wpcf7_form_elements', 'galanlab_password_fields');


/**
 * Comprueba si un contenedor está en ejecución analizando la salida del
 * comando 'docker ps': si está activo, la salida devuelta es el ID del
 * contenedor y un salto de línea; en caso contrario, devuelve un error
 * de varias líneas.
 * 
 * @param bool|string[]	 	false si hay un error; el ID en caso contrario.
 */
function galanlab_is_container_running_docker ( $id ) {
	$command = "docker ps -q --filter \"id=$id\"";

	exec( $command, $output, $return_var );

	return count ( $output );
}


/**
 * Elimina un contenedor de la base de datos si este no está en ejecución.
 */
function fix_container_running_in_database () {
	$labInfo = galanlab_has_lab_running();

	if ( $labInfo ) {
		$running = galanlab_is_container_running_docker( $labInfo['id'] );
		
		if ( $running == 0) {
			delete_user_meta( get_current_user_id(), '_current_lab_info_' );
		}
	}
}

// Registrar la función anterior para que se ejecute en el evento 'init'.
add_action( 'init', 'fix_container_running_in_database' );


/**
 * Ordena los laboratorios por nombre en la página de laboratorios.
 * 
 * @param object 	Consulta de los laboratorios.
 */
function galanlab_sort($query) {
    if (is_post_type_archive('lab') && $query->is_main_query()) {
        $query->set('orderby', 'title');
        $query->set('order', 'ASC');
    }
}

// Registrar la función anterior para que se ejecute en el evento 'pre_get_posts'.
add_action('pre_get_posts', 'galanlab_sort');


/**
 * Almacena en un fichero de registro una acción realizada por un usuario.
 * 
 * @param string	Fecha y hora de la acción.
 * @param string	Usuario que realiza la acción.
 * @param string	Acción realizada.
 * @param string	ID del laboratorio.
 * @param string	Puerto del laboratorio.
 */
function galanlog_add_entry( $date, $user, $action, $lab_id , $lab_port ) {
	$log_dir = '/var/www/html/logs';
	$log_file = $log_dir . '/' . $date->format('Y-m-d') . '.log';
	$log_entry = $date->format('H:i:s') . ' | ' . $user . ' ' . $action . ' ' . $lab_id . ' en el puerto ' . $lab_port . ".\n";

	exec( "mkdir -p $log_dir ; chown 775 www-data:www-data $log_dir" );

	file_put_contents( $log_file, $log_entry, FILE_APPEND );
}


/**
 * Redirige al usuario a la página de inicio de sesión cuando cierra sesión.
 */
function galanlogout_error()
{
	$redir = home_url('/inicio-de-sesion/');
	return $redir;
}

// Registrar la función anterior para que se ejecute en el evento 'logout_redirect'.
add_filter('logout_redirect', 'galanlogout_error');


/**
 * Redirige al usuario a la página de inicio de sesión cuando falla el inicio de sesión.
 * 
 * @param string	Redirección por defecto.
 * @param string	Redirección solicitada.
 * @param string	Usuario que inicia sesión.
 */
function galanlogin_error($redirect_to, $requested_redirect_to, $user) {
	if ( array_key_exists('errors', $user) ) {
		wp_redirect( home_url('/inicio-de-sesion/?login=failed') );
		exit;
	}

	return $redirect_to;
}

// Registrar la función anterior para que se ejecute en el evento 'login_redirect'.
add_filter('login_redirect', 'galanlogin_error', 10, 3);
