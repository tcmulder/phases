<?php

Class Phases {

	/**
	 * Kicks off first things.
	 */
	public static function init() {
		self::init_hooks();
		register_activation_hook( PHASES_PLUGIN_FILE, array( 'Phases', 'phases_activation' ) );
	}

	/**
	 * Attaches methods to hooks.
	 */
	public static function init_hooks() {
		
		add_action( 'init', array( 'Phases', 'create_phases_taxonomy' ) );
		add_action( 'init', array( 'Phases', 'create_default_phases_terms' ) );
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

		$phase_post_types = self::get_phases_post_types();
		if ( ! empty( $phase_post_types ) ) {
			foreach ( $phase_post_types as $type ) {
				add_action( 'manage_edit-' . $type . '_columns', array( 'Phases', 'column_add' ) );
        		add_filter( 'manage_' . $type . '_posts_custom_column', array( 'Phases', 'column_value' ), 10, 3 );
			}
		}
 
	}

	/**
	 * Runs on activation to initialize the plugin settings
	 */
	public static function phases_activation() {
		// bail if we have settings already (i.e. this isn't the first activation but a reactivation)
		if ( get_option( 'phases_settings' ) ) {
			return;
		}
		// show ui on pages by default
		update_option( 'phases_settings', array( 'post_types' => array( 'page' => 'page' ), 'activation' => true ) );
	}

	/**
	 * Gets post types two which phases should apply.
	 */
	public static function get_phases_post_types() {
		
		// prep to store array of post types to show phases on
		$post_types_array = array();

		// get the phase post type settings and loop through them
		$options = get_option( 'phases_settings' );
		if ( $options && ! empty( $options['post_types'] ?? array() ) ) {
			// create an indexical array of the post types
			$post_types_array = array_map( function( $post_type ) {
				return $post_type;
			}, $options['post_types'] );
		}
		// return post types
		return $post_types_array;
	}

	/**
	 * Gets terms related to phase phases.
	 *
	 * @param array $args
	 * @return array
	 */
	public static function get_phase_phases( $args = array() ) {

		$defaults = array( 
			'taxonomy' => 'phases',
			'hide_empty' => 0,
		);
		$query_args = wp_parse_args( $args, $defaults );
		$terms = get_terms( $query_args );
		return ( $terms ? $terms : array() );

	}

	/**
	 * Gets a single phase's data.
	 *
	 * @param obj $term
	 * @return mixed
	 */
	public static function get_phase_phase( $term ) {
		$phase = false;
		if ( $term ) {
			$faux_meta = maybe_unserialize( $term->description );
			$faux_meta = wp_parse_args( $faux_meta, array( 'color' => '#cccccc' ) );
			$phase = array( 
				...$faux_meta,
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			); 
		}
		return $phase;
	}

	/**
	 * Gets currently-applied phase.
	 *
	 * @param int $post_id
	 * @return array
	 */
	public static function get_post_phase( $post_id = 0 ) {
		$phase = array();
		$terms = wp_get_post_terms( $post_id, 'phases' );
		if ( $terms ) {
			// note that only one phase can be selected at a time
			$phase = self::get_phase_phase( $terms[0] );
		}
		return $phase;
	}

	/**
	 * Checks if a post type has phases enabled.
	 *
	 * @param string $post_type
	 * @return bool
	 */
	public static function has_phases( $post_type ) {
		$post_types = self::get_phases_post_types();
		return in_array( $post_type, $post_types );
	}

	/**
	 * Creates taxonomy for phase phases.
	 */
	public static function create_phases_taxonomy() {

		$options = get_option( 'phases_settings' );
		$post_types = self::get_phases_post_types();

		register_taxonomy(
			'phases',
			$post_types,
			array(
				'labels' => array(
					'name'                       => 'Phases', 'Taxonomy General Name', 'phases',
					'singular_name'              => 'Phase', 'Taxonomy Singular Name', 'phases',
					'menu_name'                  => 'Phases', 'phases',
					'all_items'                  => 'All Items', 'phases',
					'parent_item'                => 'Parent Item', 'phases',
					'parent_item_colon'          => 'Parent Item:', 'phases',
					'new_item_name'              => 'New Item Name', 'phases',
					'add_new_item'               => 'Add New Item', 'phases',
					'edit_item'                  => 'Edit Item', 'phases',
					'update_item'                => 'Update Item', 'phases',
					'separate_items_with_commas' => 'Separate items with commas', 'phases',
					'search_items'               => 'Search Items', 'phases',
					'add_or_remove_items'        => 'Add or remove items', 'phases',
					'choose_from_most_used'      => 'Choose from the most used items', 'phases',
					'not_found'                  => 'Not Found', 'phases',
				),
				'meta_box_cb'       => array ( 'Phases', 'group_meta_box' ),
				'public'            => ( $options['debug'] ?? 'off' === 'on' ),
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
	 * Creates initial terms (if the plugin is being initialized for the first time)
	 */
	public static function create_default_phases_terms() {
		
		// if this is the initial activation
		$options = get_option( 'phases_settings' );
		if ( $options['activation'] ?? false ) {
			// set up to do / doing / done default terms
			wp_insert_term( 'Done', 'phases', array(
				'name' => 'Done',
				'description' => serialize( array(
					'color' => '#d9ead3'
				) )
			) );
			wp_insert_term( 'Doing', 'phases', array(
				'name' => 'Doing',
				'description' => serialize( array(
					'color' => '#fff3cc'
				) )
			) );
			wp_insert_term( 'To Do', 'phases', array(
				'name' => 'To Do',
				'description' => serialize( array(
					'color' => '#f5cbcc'
				) )
			) );
			// identify as no longer being initial activation
			unset( $options['activation'] );
			update_option( 'phases_settings', $options );
		}

	}

	/**
	 * Creates the settings page for the plugin.
	 */

	// add a menu link inside the Settings menu
	public static function add_admin_menu(  ) {

		add_submenu_page(
			'options-general.php',
			__( 'Phases', 'phases' ),
			__( '🥝 Phases', 'phases' ),
			'manage_options',
			'phases_admin',
			function() {
				ob_start();
					settings_fields( 'pluginPage' );
					do_settings_sections( 'pluginPage' );
					submit_button();
				$form_html = ob_get_clean();
				printf(
					'<form class="phases" action="options.php" method="post"><h1>%s</h1>%s</form>',
					__( 'Phase Options', 'phases' ),
					$form_html,
				);
			}
		);

	}

	// establish option page setting sections/fields
	public static function phases_settings_init(  ) {

		// register the settings option storage location
		register_setting( 'pluginPage', 'phases_settings' );

		// enable addition/removal of phase phases
		add_settings_section(
			'phases_admin_phases_section',
			__( 'Phase Phases', 'phases' ),
			function() {
				printf( '<p>%s</p>', __( 'Add or remove phases in your phase.', 'phases' ) );
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
						$phase['id'],
						$phase['id'],
						$phase['id'],
						esc_html( $phase['name'] ),
						$phase['id'],
						$phase['color'],
						__( 'Remove', 'phases' ),
						__( 'Remove', 'phases' )
					);
				}
				printf(
					'<fieldset>%s <button class="phases__add button-secondary" type="button">%s</button></fieldset>',
					implode( "\n", $phases_html ),
					__( 'Add a Phase', 'phases' )
				);
			},
			'pluginPage',
			'phases_admin_phases_section'
		);

		// create selection of post types where layouts will appear
		add_settings_section(
			'phases_admin_post_type_section',
			__( 'Enable for Post Types', 'phases' ),
			function() {
				printf( '<p>%s</p>', __( 'Select which post types will use phases.', 'phases' ) );
			},
			'pluginPage'
		);
		add_settings_field(
			'phases_admin_post_type_fields',
			__( 'Show on post types:', 'phases' ),
			function() {
				// grab the plugin options
				$options = get_option( 'phases_settings' );
				// get all post types that are publicly available
				$post_types = get_post_types( array( 'public' => true ) );
				// create checkboxs listing the post types for which phases should appear
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
						checked( $options['post_types'][$key] ?? '', $key, false ),
						$type_obj->labels->name
					);
				}
				printf( '<fieldset>%s<fieldset>', implode( "\n", $fields ) );
			},
			'pluginPage',
			'phases_admin_post_type_section'
		);

		// turn on/off debug mode
		add_settings_section(
			'phases_admin_debug_section',
			__( 'Enable Debugging', 'phases' ),
			function() {
				$options = get_option( 'phases_settings' );
				$debug_enabled = $options['debug'] ?? 'off' === 'on';
				printf(
					'<fieldset><label><input type="checkbox" name="%s" value="on"%s /> %s</label><fieldset>%s',
					'phases_settings[debug]',
					( $debug_enabled ? ' checked' : '' ),
					__( 'Enable Debug Mode', 'phases' ),
					( $debug_enabled ? '<code style="white-space:pre;display:block">' . print_r( $options, 1 ) . '</code>' : '' )
				);
			},
			'pluginPage'
		);

	}

	// handle settings save
	public static function save_settings( $old_value, $value ) {

		// detach the hook so it doesn't run twice
		remove_action( 'update_option_phases_settings', array( 'Phases', 'save_settings' ), 10, 2 );

		// extract phases information and save as taxonomy rather than option field
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
		unset( $value['phases'] );

		// update the settings value sans phases
		update_option( 'phases_settings', array( ...$value ) );

		// reattach the hook
		add_action( 'update_option_phases_settings', array( 'Phases', 'save_settings' ), 10, 2 );
	}

	/**
	 * Creates admin column header.
	 */
	public static function column_add($cols) {
		$cols['phases'] = sprintf(
			'<abbr style="cursor:help;" title="%s">%s</abbr>',
			__( 'Current Phase Phase', 'phases' ),
			__( 'Phase', 'phases' )
		);
		return $cols;
	}

	/**
	 * Creates admin column values.
	 *
	 * @param string $column_name
	 * @param int $id
	 * @return void
	 */
	public static function column_value( $column_name, $id ) {
		if ( 'phases' === $column_name ) {
			$phase = self::get_post_phase( $id );
			if ( ! empty ( $phase ) ) {
				printf(
					'<a href="%sedit.php?phases=%s&post_type=%s" class="phases-value--%s">%s</a>',
					get_admin_url(),
					$phase['slug'],
					get_post_type( $id ),
					$phase['slug'],
					$phase['name']
				);
			}
		}
	}

	/**
	 * Adds a dropdown that allows filtering on the posts current phase.
	 *
	 * @return void
	 */
	public static function posts_filter_dropdown() {
		
		$post_type = sanitize_title( $_GET['post_type'] ?? '' );
		
		if ( $post_type && self::has_phases( $post_type ) ) {

			$options = array();
			
			$terms = self::get_phase_phases();

			foreach ( $terms as $term ) {
				$is_selected = $_GET['phases'] ?? '' === $term->slug ? ' selected' : '';
				$options[] = sprintf(
					'<option value="%s"%s>%s</option>',
					$term->slug,
					$is_selected,
					$term->name
				);
			}

			if ( $options ) {
				printf(
					'<label class="screen-reader-text" for="phases-filter">%s</label><select name="phases"><option value>%s</option>%s</select>',
					esc_html__( 'Filter by Phase Phase', 'phases' ),
					__( 'All Phases', 'phases' ),
					implode( "\n", $options ),
				);
			}

		}
		

	}

	/**
	 * Adds metaboxes to block editor settings sidebar.
	 *
	 * @return void
	 */
	public static function phases_add_meta_box() {

		// if this post type has phases activated
		if ( self::has_phases( get_post_type() ) ) {

			// get the chosen phase (if any)
			$post_phase = self::get_post_phase( get_the_id() );
			
			// set the label
			$label = sprintf(
				'%s<span class="phases-swatch" style="background-color:%s;">%s</span>',
				__( 'Phase', 'phases' ),
				empty( $post_phase ) ? 'transparent' : $post_phase['color'],
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
				__( 'None', 'phases' )
			) );

			// create the meta box
			add_meta_box(
				'phases_options',
				$label,
				function() use ( $options ) {
					wp_nonce_field( 'phases_meta_box', 'phases_meta_box_nonce' );
					printf( '<select class="phases-phase-select" name="phases_phase_id">%s</select>', implode( "\n", $options ) );
					printf(
						'<a href="%s" class="phases-manage-link">%s</a>',
						esc_url( get_admin_url( null, 'options-general.php?page=phases_admin' ) ),
						__( 'Manage Phases', 'phases' )
					);
				},
				self::get_phases_post_types(),
				'side'
			);

		}

	}

	/**
     * Saves phase data when a post is saved
     *
     * @param int $post_id ID of the post e.g. '1'
     *
     * @return void
     */
    public static function phases_save( $post_id = 0 ) {

		$post_nonce = wp_verify_nonce( $_POST['phases_meta_box_nonce'] ?? '', 'phases_meta_box' ); // post save
		$quick_nonce = wp_verify_nonce( $_POST[ '_inline_edit' ] ?? '', 'inlineeditnonce' ); // quick save
		$bulk_nonce = wp_verify_nonce( $_GET[ '_wpnonce' ] ?? '', 'bulk-posts' ); // bulk quick save
        
		// bail if our nonce isn't right
        if ( ! $post_nonce && ! $quick_nonce && ! $bulk_nonce ) {
            return;
        }

        // bail if autosaving
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // bail if this user can't edit this post tpe
		$post_type = $bulk_nonce ? $_GET['post_type'] : $_POST['post_type'];
        if ( ! current_user_can( 'edit_' . $post_type, $post_id ) ) {
            return;
        }

        // get data and sanitize it
		$new_phase_id = ( $bulk_nonce ? $_GET['phases_phase_id'] ?? null : $_POST['phases_phase_id'] ?? null );

        // swap out the post's phase term (unless we're to leave things unchanged)
		if ( null !== $new_phase_id ) {
			wp_set_object_terms( $post_id, array( (int) $new_phase_id ), 'phases', false );
		}

    }

	public static function quick_and_bulk_save( $post_id ){

		$is_quick = wp_verify_nonce( $_POST[ '_inline_edit' ] ?? '', 'inlineeditnonce' );
		$is_bulk = wp_verify_nonce( $_GET[ '_wpnonce' ] ?? '', 'bulk-posts' );

		if ( ! $is_quick && ! $is_bulk ) {
			return;
		}



	}

	/**
	 * Enqueues scripts and styles.
	 *
	 * @param string $hook_suffix
	 * @return void
	 */
	public static function enqueue_scripts( $hook_suffix ) {

		$screen = get_current_screen();
		$post_types = self::get_phases_post_types();

		// load scripts for block editor screen
		if ( 'post' === $screen->base && in_array( $screen->post_type, $post_types ) ) {
			wp_enqueue_style( 'phases-block-editor-styles', PHASES_PLUGIN_URI . 'assets/phases-block-editor.css', null, PHASES_VERSION, 'screen' );
			wp_enqueue_script( 'phases-block-editor-scripts', PHASES_PLUGIN_URI . 'assets/phases-block-editor.js', array( 'jquery' ), PHASES_VERSION, true );
		}

		// load scripts for page lists
		
		
		// load scripts for plugin settings screen
		if ( 'settings_page_phases_admin' === $hook_suffix ) {
			wp_enqueue_style( 'phases-settings-styles', PHASES_PLUGIN_URI . 'assets/phases-settings.css', array( 'wp-color-picker' ), PHASES_VERSION, 'screen' );
			wp_enqueue_script( 'phases-settings-scripts', PHASES_PLUGIN_URI . 'assets/phases-settings.js', array( 'jquery', 'wp-color-picker' ), PHASES_VERSION, true );
		}

		// load scripts for post type lists
		if ( 'edit' === $screen->base && in_array( $screen->post_type, $post_types ) ) {
			// load the stylesheet
			wp_enqueue_style( 'phases-post-list-styles', PHASES_PLUGIN_URI . 'assets/phases-post-list.css', null, PHASES_VERSION, 'screen' );
			
			// set the colors for each phase
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
				printf( '<style id="phases-admin-styles">%s</style>', implode( "\n", $styles ) );
			}
		}

	}

	/**
     * Custom quick edit box.
     *
     * @param string $column_name Custom column name
     *
     */
    public static function quick_and_bulk_field( $column_name ) {
        if ($column_name !== 'phases') {
            return;
        }
		$phases = self::get_phase_phases();
		if ( ! empty( $phases ) ) {
			$options = sprintf(
				'<option disabled selected>%s</option><option value="">%s</option>',
				__( '(Unchanged)', 'phases' ),
				__( 'None', 'phases' )
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
				__( 'Phase', 'phases' ),
				$options,
			);
		}
    }

	
	public static function quick_and_bulk_mods( $show, $taxonomy_name, $view ) {

		if ( 'phases' === $taxonomy_name ) {
			return false;
		}
	
		return $show;
	
	}


}
