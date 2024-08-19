<?php

/**
 * Phases core class
 * 
 * @since 1.0.2
 */
Class Phases {

	/**
	 * Kicks off first things.
	 * 
	 * @since 1.0.0
	 * 
	 */
	public static function init() {
		self::init_hooks();
		register_activation_hook( PHASES_PLUGIN_FILE, array( 'Phases', 'phases_activation' ) );
	}

	/**
	 * Attaches methods to hooks.
	 * 
	 * @since 1.0.0
	 */
	public static function init_hooks() {
		
		add_action( 'init', array( 'Phases', 'create_phases_taxonomy' ) );
		add_action( 'admin_init', array( 'Phases', 'create_phases_terms' ) );
		add_action( 'admin_menu', array( 'Phases', 'add_admin_menu' ) );
		add_action( 'admin_init', array( 'Phases', 'phases_settings_init' ) );
		add_action( 'restrict_manage_posts', array( 'Phases', 'posts_filter_dropdown' ) );
		add_action( 'admin_enqueue_scripts', array( 'Phases', 'enqueue_scripts' ) );
		add_action( 'add_meta_boxes', array( 'Phases', 'phases_add_meta_box' ) );
		add_action( 'save_post', array( 'Phases', 'phases_save' ) );
		add_action( 'update_option_phases_settings', array( 'Phases', 'save_settings' ), 10, 2 );
		add_action( 'bulk_edit_custom_box', array( 'Phases', 'quick_and_bulk_field' ) );
        add_action( 'quick_edit_custom_box', array( 'Phases', 'quick_and_bulk_field' ) );
		add_filter( 'quick_edit_show_taxonomy', array( 'Phases', 'quick_and_bulk_mods' ), 10, 3 );

		// add columns only to post types that have phases activated
		$phase_post_types = self::get_phases_post_types();
		foreach ( $phase_post_types as $type ) {
			add_action( 'manage_edit-' . $type . '_columns', array( 'Phases', 'column_add' ) );
			add_filter( 'manage_' . $type . '_posts_custom_column', array( 'Phases', 'column_value' ), 10, 3 );
		}
 
	}

	/**
	 * Runs on activation to initialize the plugin settings.
	 * 
	 * @since 1.0.2
	 */
	public static function phases_activation() {

		// bail if we have settings already (i.e. this isn't the first activation but a reactivation)
		if ( get_option( 'phases_settings' ) ) {
			return;
		}
		// show ui on page post type by default
		update_option( 'phases_settings', array( 'post_types' => array( 'page' => 'page' ) ) );

	}

	/**
	 * Gets post types to which phases should apply.
	 * 
	 * @return array Array of post types for which phases is activated.
	 * @since 1.0.0
	 */
	public static function get_phases_post_types() {
		
		// prep to store array of post types to show phases on
		$post_types_array = array();

		// get the post types defined in settings and loop through them
		$settings = get_option( 'phases_settings' );
		if ( $settings && ! empty( $settings['post_types'] ?? array() ) ) {
			// create an indexical array of the post types
			$post_types_array = array_map( function( $post_type ) {
				return $post_type;
			}, $settings['post_types'] );
		}
		// return post types
		return $post_types_array;
	}

	/**
	 * Gets all phase terms.
	 *
	 * @param array $args Extra get_terms() query parameters.
	 * @return array Array of terms (empty if no terms are set).
	 * @since 1.0.0
	 */
	public static function get_phase_phases( $args = array() ) {

		// establish default query (i.e. just get all phase taxonomy terms)
		$defaults = array( 
			'taxonomy' => 'phases',
			'hide_empty' => 0,
		);
		// merge any custom query arguments
		$query_args = wp_parse_args( $args, $defaults );
		// get the terms and return them (or an empty array)
		$terms = get_terms( $query_args );
		return ( $terms ? $terms : array() );

	}

	/**
	 * Gets a single phase's data (parsed from its description).
	 *
	 * @param obj $term A WP_Term object to parse.
	 * @return array Array of phase data (or empty if no data).
	 * @since 1.0.2
	 */
	public static function get_phase_phase( $term ) {

		// start with no phase data
		$phase = array();
		// if we were passed a valid term
		if ( $term ) {
			// get data from the term's description
			$faux_meta = maybe_unserialize( $term->description );
			$faux_meta = wp_parse_args( $faux_meta, array( 'color' => '#cccccc' ) );
			// create our phase data
			$phase = array_merge( $faux_meta, array( 
				'id'   => (int) $term->term_id,
				'name' => sanitize_text_field( $term->name ),
				'slug' => sanitize_title( $term->slug ),
			) );
		}
		// return the phase data
		return $phase;
	}

	/**
	 * Gets a post's phase data for the currently-applied phase.
	 *
	 * @param int $post_id ID of the post to evaluate.
	 * @return array Array of phase data (or empty if no data).
	 * @since 1.0.0
	 */
	public static function get_post_phase( $post_id = 0 ) {
		
		// start with no phase data
		$phase = array();
		// get the first phase term and return its data
		$terms = wp_get_post_terms( $post_id, 'phases' );
		if ( $terms ) {
			$phase = self::get_phase_phase( $terms[0] );
		}
		return $phase;

	}

	/**
	 * Checks if a post type has phases enabled.
	 *
	 * @param string $post_type The post type to test.
	 * @return bool True if phases are enabled and false if not.
	 * @since 1.0.0
	 */
	public static function has_phases( $post_type ) {

		// get all post types on which phases are active
		$post_types = self::get_phases_post_types();
		// see if this post type is in the list
		return in_array( $post_type, $post_types );

	}

	/**
	 * Creates taxonomy for phases.
	 * 
	 * @since 1.0.0
	 */
	public static function create_phases_taxonomy() {

		// see if debug is on
		$settings = get_option( 'phases_settings' );
		$debug = ( $settings['debug'] ?? 'off' === 'on' );
		// get the post types for which the phases taxonomy is turned on
		$post_types = self::get_phases_post_types();

		register_taxonomy(
			'phases',
			$post_types,
			array(
				'labels' => array(
					'name'                       => _x( 'Phases', 'Taxonomy General Name', 'phases' ),
					'singular_name'              => _x( 'Phase', 'Taxonomy Singular Name', 'phases' ),
					'menu_name'                  => __( 'Phases', 'phases' ),
					'all_items'                  => __( 'All Items', 'phases' ),
					'parent_item'                => __( 'Parent Item', 'phases' ),
					'parent_item_colon'          => __( 'Parent Item:', 'phases' ),
					'new_item_name'              => __( 'New Item Name', 'phases' ),
					'add_new_item'               => __( 'Add New Item', 'phases' ),
					'edit_item'                  => __( 'Edit Item', 'phases' ),
					'update_item'                => __( 'Update Item', 'phases' ),
					'separate_items_with_commas' => __( 'Separate items with commas', 'phases' ),
					'search_items'               => __( 'Search Items', 'phases' ),
					'add_or_remove_items'        => __( 'Add or remove items', 'phases' ),
					'choose_from_most_used'      => __( 'Choose from the most used items', 'phases' ),
					'not_found'                  => __( 'Not Found', 'phases' ),
				),
				'public'            => $debug,
				'capabilities' => array(
					'manage__terms' => 'edit_posts',
					'edit_terms'    => 'manage_categories',
					'delete_terms'  => 'manage_categories',
					'assign_terms'  => 'edit_posts'
				)
			)
		);

	}

	/**
	 * Creates default terms for phases taxonomy if there are none
	 * 
	 * @since 1.0.2
	 */
	public static function create_phases_terms() {

		// if we have no phases then create defaults (usually only for initial activation)
		$have_phases = wp_count_terms( 'phases', array( 'hide_empty'=> false ) );
		if ( ! $have_phases ) {
			wp_insert_term( 'Done', 'phases', array( 'name' => 'Done', 'description' => serialize( array( 'color' => '#d9ead3' ) ) ) );
			wp_insert_term( 'Doing', 'phases', array( 'name' => 'Doing', 'description' => serialize( array( 'color' => '#fff3cc' ) ) ) );
			wp_insert_term( 'To Do', 'phases', array( 'name' => 'To Do', 'description' => serialize( array( 'color' => '#f5cbcc' ) ) ) );
		}

	}

	/**
	 * Creates a settings page within the Settings menu.
	 *
	 * @since 1.0.0
	 */
	public static function add_admin_menu(  ) {

		add_submenu_page(
			'options-general.php',
			__( 'Phases', 'phases' ),
			__( 'Phases', 'phases' ),
			'manage_options',
			'phases_admin',
			function() {
				echo '<form class="phases" action="options.php" method="post">';
				printf( '<h1>%s</h1>', esc_html__( 'Phase Options', 'phases' ) );
				settings_fields( 'pluginPage' );
				do_settings_sections( 'pluginPage' );
				submit_button();
				echo '</form>';
			}
		);

	}

	/**
	 * Establishes option page setting sections/fields.
	 *
	 * @since 1.0.0
	 */
	public static function phases_settings_init(  ) {

		// register the settings option storage location
		register_setting( 'pluginPage', 'phases_settings' );

		// allow user to add/remove phases
		add_settings_section(
			'phases_admin_phases_section',
			__( 'Phase Phases', 'phases' ),
			function() {
				printf( '<p>%s</p>', esc_html__( 'Add or remove phases in your phase.', 'phases' ) );
			},
			'pluginPage'
		);
		add_settings_field(
			'phases_admin_phases_fields',
			__( 'Manage Phases:', 'phases' ),
			function() {
				$terms = self::get_phase_phases();
				$phases_html = array();
				foreach ( $terms as $term ) {
					$phase = self::get_phase_phase( $term );
					$phases_html[] = sprintf(
						'<div class="phases__phase">
							<input name="phases_settings[phases][%s][id]" value="%s" type="hidden" />
							<input name="phases_settings[phases][%s][name]" value="%s" type="text" />
							<span class="phases__color">
								<input name="phases_settings[phases][%s][color]" value="%s" data-default-color="#cccccc" class="phases-color" type="text" />
							</span>
							<button class="phases__remove button-secondary" aria-label="%s" title="%s" type="button">✕</button>
						</div>',
						esc_html( $phase['id'] ),
						esc_html( $phase['id'] ),
						esc_html( $phase['id'] ),
						esc_html( $phase['name'] ),
						esc_html( $phase['id'] ),
						esc_html( $phase['color'] ),
						esc_html__( 'Remove', 'phases' ),
						esc_html__( 'Remove', 'phases' )
					);
				}
				$html = implode( "\n", $phases_html );
				printf(
					'<fieldset>%s <button class="phases__add button-secondary" type="button">%s</button></fieldset>',
					wp_kses( $html, array(
						'div' => array(
							'class' => array(),
						),
						'input' => array(
							'class' => array(),
							'name' => array(),
							'value' => array(),
							'type' => array(),
							'data-default-color' => array(),
						),
						'span' => array(
							'class' => array(),
						),
						'button' => array(
							'class' => array(),
							'aria-label' => array(),
							'title' => array(),
							'type' => array(),
						),
					) ),
					esc_html__( 'Add a Phase', 'phases' )
				);
			},
			'pluginPage',
			'phases_admin_phases_section'
		);

		// allow user to select post types on which to enable phases
		add_settings_section(
			'phases_admin_post_type_section',
			__( 'Enable for Post Types', 'phases' ),
			function() {
				printf( '<p>%s</p>', esc_html__( 'Select which post types will use phases.', 'phases' ) );
			},
			'pluginPage'
		);
		add_settings_field(
			'phases_admin_post_type_fields',
			__( 'Show on post types:', 'phases' ),
			function() {
				$settings = get_option( 'phases_settings' );
				$post_types = get_post_types( array( 'public' => true ) );
				$fields = array();
				foreach ( $post_types as $key => $name ) {
					// media is a special type we'll exclude from phases automatically
					if ( 'attachment' == $key ) {
						continue;
					}
					$type_obj = get_post_type_object( $name );
					$fields[] = sprintf(
						'<p><label for="%s"><input type="checkbox" id="%s" name="%s" value="%s" %s>%s</label></p>',
						$key,
						$key,
						'phases_settings[post_types][' . $key . ']',
						$key,
						checked( $settings['post_types'][$key] ?? '', $key, false ),
						$type_obj->labels->name
					);
				}
				$html = implode( "\n", $fields );
				printf( '<fieldset>%s<fieldset>',
					wp_kses ( $html, array(
						'p' => array(),
						'label' => array(
							'for' => array(),
						),
						'input' => array(
							'type' => array(),
							'id' => array(),
							'name' => array(),
							'value' => array(),
							'checked' => array(),
						),
					) )
				);
			},
			'pluginPage',
			'phases_admin_post_type_section'
		);

		// allow user to enable/disable notes
		add_settings_section(
			'phases_admin_notes_section',
			__( 'Notes', 'phases' ),
			function() {
				printf( '<p>%s</p>', esc_html__( 'Enable to support notes related to a post and the phase it\'s in.', 'phases' ) );
				$settings = get_option( 'phases_settings' );
				printf(
					'<fieldset>%s%s<fieldset>',
					sprintf(
						'<p><label><input type="checkbox" name="%s" value="on"%s /> %s</label></p>',
							'phases_settings[notes]',
						( $settings['notes'] ?? 'off' === 'on' ? ' checked' : '' ),
						esc_html__( 'Enable Notes', 'phases' )
					),
					sprintf(
						'<p><label><input type="checkbox" name="%s" value="on"%s /> %s</label></p>',
							'phases_settings[notes_in_column]',
						( $settings['notes_in_column'] ?? 'off' === 'on' ? ' checked' : '' ),
						esc_html__( 'Show notes in post list column', 'phases' )
					)
				);
			},
			'pluginPage'
		);

		// allow user to turn on/off debug mode
		add_settings_section(
			'phases_admin_debug_section',
			__( 'Debugging', 'phases' ),
			function() {
				$settings = get_option( 'phases_settings' );
				$enabled = $settings['debug'] ?? 'off' === 'on';
				printf(
					'<fieldset><label><input type="checkbox" name="%s" value="on"%s /> %s</label><fieldset>%s',
					'phases_settings[debug]',
					( $enabled ? ' checked' : '' ),
					esc_html__( 'Enable Debug Mode', 'phases' ),
					( $enabled ? '<code style="white-space:pre;display:block">' . esc_html( print_r( $settings, 1 ) ) . '</code>' : '' )
				);
			},
			'pluginPage'
		);

	}

	/**
	 * Handles settings save.
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $value The new option value.
	 * @since 1.0.0
	 */
	public static function save_settings( $old_value, $value ) {

		// detach the hook so it doesn't run twice
		remove_action( 'update_option_phases_settings', array( 'Phases', 'save_settings' ), 10, 2 );

		// extract phases information and save as taxonomy (rather than to the plugin settings or post meta)
		$old_term_ids = self::get_phase_phases( array( 'fields' => 'ids' ) );
		$new_terms = $value['phases'] ?? array();
		foreach( $new_terms as $new_term ) {
			$new_id = $new_term['id'] ?? 0;
			$new_name = ( $new_term['name'] ? $new_term['name'] : __( 'Not Defined', 'phases' ) );
			$new_value = serialize( array( 'color' => $new_term['color'] ?? '#cccccc' ) );
			$matching_index = array_search( $new_id, $old_term_ids );
			if ( false !== $matching_index ) {
				wp_update_term( $new_id, 'phases', array( 'name' => $new_name, 'description' => $new_value ) );
				unset( $old_term_ids[$matching_index] );
			} else {
				wp_insert_term( $new_name, 'phases', array( 'name' => $new_name, 'description' => $new_value ) );
			}
		}
		// get rid of any old terms that don't appear in the new terms list
		if ( ! empty( $old_term_ids ) ) {
			foreach( $old_term_ids as $old_term_id ) {
				wp_delete_term( $old_term_id, 'phases' );
			}
		}
		
		// update the settings value (without phases info since that's stored in our taxonomy)
		unset( $value['phases'] );
		update_option( 'phases_settings', $value );

		// reattach the hook
		add_action( 'update_option_phases_settings', array( 'Phases', 'save_settings' ), 10, 2 );
	}

	/**
	 * Creates admin column header.
	 */
	public static function column_add($cols) {

		// create a heading for the phases column with a tooltip
		$cols['phases'] = sprintf(
			'<abbr style="cursor:help;" title="%s">%s</abbr>',
			esc_html__( 'Current post phase', 'phases' ),
			esc_html__( 'Phase', 'phases' )
		);
		return $cols;

	}

	/**
	 * Creates admin column cell values.
	 *
	 * @param string $column_name Name of the column.
	 * @param int $id Post ID.
	 * @since 1.0.0
	 */
	public static function column_value( $column_name, $id ) {

		// if this is the phases column
		if ( 'phases' === $column_name ) {
			// show this post's phases (if any)
			$phase = self::get_post_phase( $id );
			if ( ! empty ( $phase ) ) {
				printf(
					'<a href="%sedit.php?phases=%s&post_type=%s" class="phases-value--%s">%s</a>',
					esc_html( get_admin_url() ),
					esc_html( $phase['slug'] ),
					get_post_type( $id ),
					esc_html( $phase['slug'] ),
					esc_html( $phase['name'] )
				);
			}
			// show this post's notes (if notes within columns is on)
			$notes = '';
			$settings = get_option( 'phases_settings' );
			if ( $settings['notes_in_column'] ?? 'off' === 'on' ) {
				$meta = get_post_meta( $id, 'phases_note', true );
				if ( $meta ) {
					$notes = printf(
						'<div class="phases-note">%s</div>',
						esc_html( sanitize_textarea_field( $meta ) )
					);
				}
			}
		}
	}

	/**
	 * Adds a dropdown that allows filtering by phase to admin columns.
	 *
	 * @param string $post_type The post type slug.
	 * @since 1.0.0
	 */
	public static function posts_filter_dropdown( $post_type ) {
		
		// if the current post type has phases enabled
		$post_type = sanitize_title( $post_type );
		if ( $post_type && self::has_phases( $post_type ) ) {
			// start with no options
			$options = array();
			// create options for each phase
			$terms = self::get_phase_phases();
			// get current filtered-to phase (if any) already selected from this dropdown
			$cur_slug = '';
			$query = get_queried_object();
			if ( $query && 'phases' === $query->taxonomy ) {
				$cur_slug = $query->slug;
			}
			foreach ( $terms as $term ) {
				$is_selected = $cur_slug === $term->slug ? ' selected' : '';
				$options[] = sprintf(
					'<option value="%s"%s>%s</option>',
					$term->slug,
					$is_selected,
					$term->name
				);
			}
			// if we have phase options then output a select box so the user can choose one
			if ( $options ) {
				$html = implode( "\n", $options );
				printf(
					'<label class="screen-reader-text" for="phases-filter">%s</label><select name="phases"><option value>%s</option>%s</select>',
					esc_html__( 'Filter by Phase Phase', 'phases' ),
					esc_html__( 'All Phases', 'phases' ),
					wp_kses( $html, array(
						'option' => array(
							'selected' => array(),
							'value' => array(),
						)
					) ),
				);
			}

		}

	}

	/**
	 * Adds metaboxes to block editor settings sidebar on post edit screen.
	 *
	 * @since 1.0.0
	 */
	public static function phases_add_meta_box() {

		// if this post type has phases activated
		if ( self::has_phases( get_post_type() ) ) {

			// get the chosen phase (if any)
			$post_phase = self::get_post_phase( get_the_id() );
			
			// set the label
			$label = sprintf(
				'<span class="phases-swatch" style="background-color:%s;">%s: %s</span>',
				empty( $post_phase ) ? 'transparent' : $post_phase['color'],
				esc_html__( 'Phase', 'phases' ),
				empty( $post_phase ) ? '' : $post_phase['name']
			);

			// generate select box options HTML
			$terms = self::get_phase_phases();
			$has_selected = false;
			$options = array();
			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$phase = self::get_phase_phase( $term );
					$selected = '';
					if ( isset( $post_phase['id'] ) && $post_phase['id'] === $phase['id'] ) {
						$selected = ' selected';
						$has_selected = true;
					}
					$options[] = sprintf(
						'<option value="%s" data-color="%s"%s>%s</option>',
						$phase['id'],
						$phase['color'],
						$selected,
						$phase['name']
					);
				}
			}
			array_unshift( $options, sprintf(
				'<option value="0" data-color=""%s>%s</option>',
				$has_selected ? '' : ' selected',
				esc_html__( 'None', 'phases' )
			) );

			// create the meta box
			add_meta_box(
				'phases_options',
				wp_kses( $label, array( 'span' => array( 'class' => array(), 'style' => array() ) ) ),
				function() use ( $options ) {
					$settings = get_option( 'phases_settings' );
					wp_nonce_field( 'phases_meta_box', 'phases_meta_box_nonce' );
					printf(
						'<p>%s:</p>',
						esc_html__( 'Set this post as', 'phases' )
					);
					$html = implode( "\n", $options );
					printf(
						'<select class="phases-phase-select" name="phases_phase_id">%s</select>',
						wp_kses( $html, array('option' => array(
							'value' => array(),
							'data-color' => array(),
							'selected' => array(),
						) ) )
					);
					if ( $settings['notes'] ?? 'off' === 'on' ) {
						$meta = get_post_meta( get_the_id(), 'phases_note', true );
						printf(
							'<label class="phases-notes"><span>%s:</span><textarea  name="phases_phase_note" rows="7">%s</textarea></label>',
							esc_html__( 'Notes', 'phases' ),
							esc_html( sanitize_textarea_field( $meta ) )
						);
					}
					printf(
						'<a href="%s" class="phases-manage-link">%s</a>',
						esc_url( get_admin_url( null, 'options-general.php?page=phases_admin' ) ),
						esc_html__( 'Manage phases', 'phases' )
					);
				},
				self::get_phases_post_types(),
				'side'
			);

		}

	}

	/**
     * Saves phase data when a post is saved.
     *
     * @param int $post_id Post ID.
     * @since 1.0.0
     */
    public static function phases_save( $post_id = 0 ) {

		// check nonce to determine what action we're safely performing
		$post_nonce = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ 'phases_meta_box_nonce' ] ?? '' ) ), 'phases_meta_box' ); // post save
		$quick_nonce = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ '_inline_edit' ] ?? '' ) ), 'inlineeditnonce' ); // quick save
		$bulk_nonce = wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ '_wpnonce' ] ?? '' ) ), 'bulk-posts' ); // bulk quick save
        
		// bail if our nonce isn't right
        if ( ! $post_nonce && ! $quick_nonce && ! $bulk_nonce ) {
            return;
        }

        // bail if autosaving
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // bail if this user can't edit this post tpe
		$post_type = $bulk_nonce ? sanitize_text_field( $_GET['post_type'] ) : sanitize_text_field( $_POST['post_type'] );
        if ( ! current_user_can( 'edit_' . $post_type, $post_id ) ) {
            return;
        }

        // get data and sanitize it (it's within $_GET for bulk or $_POST for quick edit)
		$new_phase_id_raw = ( $bulk_nonce ? sanitize_text_field( $_GET['phases_phase_id'] ?? 0 ) : sanitize_text_field( $_POST['phases_phase_id'] ?? 0 ) );
		$new_phase_id = $new_phase_id_raw ? $new_phase_id_raw : 0;

        // change the post's phase (unless we're to leave things unchanged)
		if ( 0 !== $new_phase_id ) {
			wp_set_object_terms( $post_id, array( (int) $new_phase_id ), 'phases', false );
		} else {
			wp_set_object_terms( $post_id, array(), 'phases', false );
		}

		// maybe update notes
		if ( isset( $_POST['phases_phase_note'] ) ) {
			update_post_meta( $post_id, 'phases_note', sanitize_textarea_field( $_POST['phases_phase_note'] ) );
		}

    }

	/**
	 * Enqueues scripts and styles.
	 *
	 * @param string $hook_suffix The current admin page.
	 * @since 1.0.0
	 */
	public static function enqueue_scripts( $hook_suffix ) {

		$screen = get_current_screen();
		$post_types = self::get_phases_post_types();

		// load scripts for block editor screen
		if ( 'post' === $screen->base && in_array( $screen->post_type, $post_types ) ) {
			wp_enqueue_style( 'phases-block-editor-styles', PHASES_PLUGIN_URI . 'assets/phases-block-editor.css', null, PHASES_VERSION, 'screen' );
			wp_enqueue_script( 'phases-block-editor-scripts', PHASES_PLUGIN_URI . 'assets/phases-block-editor.js', array( 'jquery' ), PHASES_VERSION, true );
		}		
		
		// load scripts for plugin settings screen
		if ( 'settings_page_phases_admin' === $hook_suffix ) {
			wp_enqueue_style( 'phases-settings-styles', PHASES_PLUGIN_URI . 'assets/phases-settings.css', array( 'wp-color-picker' ), PHASES_VERSION, 'screen' );
			wp_enqueue_script( 'phases-settings-scripts', PHASES_PLUGIN_URI . 'assets/phases-settings.js', array( 'jquery', 'wp-color-picker' ), PHASES_VERSION, true );
		}

		// load scripts for post type lists screen
		if ( 'edit' === $screen->base && in_array( $screen->post_type, $post_types ) ) {
			wp_enqueue_style( 'phases-post-list-styles', PHASES_PLUGIN_URI . 'assets/phases-post-list.css', null, PHASES_VERSION, 'screen' );
			// set visual colors for each phase
			$terms = self::get_phase_phases();
			$styles = array();
			foreach( $terms as $term ) {
				$phase = self::get_phase_phase( $term );
				$styles[] = sprintf(
					'.striped tr:has(.phases-value--%s){background-color:%s;--phases-color:%s;}',
					$phase['slug'],
					$phase['color'],
					$phase['color']
				);
			}
			if ( ! empty( $styles ) ) {
				$css = implode( "\n", $styles );
				printf( '<style id="phases-admin-styles">%s</style>', sanitize_text_field( $css ) );
			}
		}

	}

	/**
	 * Hide tag-like phases selector from quick/bulk edit since we use our own.
	 *
	 * @param bool $show Whether to show/hide this taxonomy.
	 * @param string $taxonomy_name Name of taxonomy being shown.
	 * @param obj $view Current view object.
	 * @since 1.0.0
	 */
	public static function quick_and_bulk_mods( $show, $taxonomy_name, $view ) {

		// hide the phases default editor
		if ( 'phases' === $taxonomy_name ) {
			return false;
		}
		return $show;
	
	}

	/**
     * Create custom quick/bulk edit box.
     *
     * @param string $column_name Custom column name.
     * @since 1.0.0
     */
    public static function quick_and_bulk_field( $column_name ) {

		// bail if this isn't the phases column
        if ($column_name !== 'phases') {
            return;
        }

		// get all the phase options and create a <select> box to choose them
		$phases = self::get_phase_phases();
		if ( ! empty( $phases ) ) {
			$options = sprintf(
				'<option disabled selected>%s</option><option value="">%s</option>',
				esc_html__( '(Unchanged)', 'phases' ),
				esc_html__( 'None', 'phases' )
			);
			foreach ( $phases as $phase ) {
				$options .= sprintf( '<option value="%s">%s</option>', $phase->term_id, $phase->name );
			}
			printf(
				'<fieldset class="inline-edit-col-right phases-quickedit">
					<div class="inline-edit-col">
						<div class="inline-edit-group">
							<label class="inline-edit-status alignleft">
								<span class="title">%s</span>
								<select type="text" name="phases_phase_id">%s</select>
							</label>
						</div>
					</div>
				</fieldset>',
				esc_html__( 'Phase', 'phases' ),
				wp_kses( $options, array(
					'option' => array(
						'disabled' => array(),
						'selected' => array(),
						'value' => array(),
					),
				) ),
			);
		}
    }

}
