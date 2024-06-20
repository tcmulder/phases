<?php

Class Workflows {

	private static $initiated = false;

	/**
	 * Kick off first things.
	 */
	
	public static function init() {
		register_activation_hook( WORKFLOWS_PLUGIN_FILE, array( 'Workflows', 'workflows_activation' ) );
		self::init_hooks();
	}

	/**
	 * Attach methods to hooks.
	 */
	
	public static function init_hooks() {
		
		add_action( 'init', array( 'Workflows', 'create_workflows_taxonomy' ) );
		add_action( 'init', array( 'Workflows', 'create_workflows_taxonomy' ) );
		add_action( 'admin_menu', array( 'Workflows', 'add_admin_menu' ) );
		add_action( 'admin_init', array( 'Workflows', 'workflows_settings_init' ) );
		add_action( 'restrict_manage_posts', array( 'Workflows', 'posts_filter_dropdown' ) );
		add_action( 'admin_enqueue_scripts', array( 'Workflows', 'enqueue_scripts' ) );
		add_action( 'admin_head', array( 'Workflows', 'apply_admin_styles') );
		add_action( 'update_option_workflows_settings', array( 'Workflows', 'save_settings' ), 10, 2 );

		$workflow_post_types = self::get_workflows_post_types();
		if ( ! empty( $workflow_post_types ) ) {
			foreach ( $workflow_post_types as $type ) {
				add_action( 'manage_edit-' . $type . '_columns', array( 'Workflows', 'column_add' ) );
        		add_filter( 'manage_' . $type . '_posts_custom_column', array( 'Workflows', 'column_value' ), 10, 3 );
			}
		}
 
	}

	/**
	 * Run on activation
	 */
	
	public static function workflows_activation() {
		// default to enabling workflows for 'page' post types the first time plugin runs
		$options = get_option( 'workflows_settings' );
		if ( ! $options ) {
			update_option( 'workflows_settings', array( 'post_types' => array( 'page' => 'page' ) ) );
		}
	}

	/**
	 * Get post types two which workflows should apply
	 */
	public static function get_workflows_post_types() {
		
		// prep to store array of post types to show workflows on
		$post_types_array = array();

		// get the workflow post type settings and loop through them
		$options = get_option( 'workflows_settings' );
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
	 * Get terms related to workflow stages
	 *
	 * @param array $args
	 * @return void
	 */
	public static function get_workflow_stages( $args = array() ) {

		$defaults = array( 
			'taxonomy' => 'workflows',
			'hide_empty' => 0,
		);
		$query_args = wp_parse_args( $args, $defaults );
		$terms = get_terms( $query_args );
		return ( $terms ? $terms : array() );

	}

	/**
	 * Get metadata for a given stage
	 *
	 * @param obj $term
	 * @return void
	 */
	public static function get_workflow_stage_meta( $term ) {
		$meta_from_desc = maybe_unserialize( $term->description );
		$defaults = array( 'color' => '#cccccc' );
		$meta = wp_parse_args( $meta_from_desc, $defaults );
		return $meta;
	}

	/**
	 * Check if a post type has workflows enabled
	 */
	public static function has_workflows( $post_type ) {
		$post_types = self::get_workflows_post_types();
		return in_array( $post_type, $post_types );
	}

	/**
	 * Create taxonomy for workflow stages.
	 */
	
	public static function create_workflows_taxonomy() {

		$options = get_option( 'workflows_settings' );
		$post_types = self::get_workflows_post_types();

		register_taxonomy(
			'workflows',
			$post_types,
			array(
				'labels' => array(
					'name'                       => 'Workflows', 'Taxonomy General Name', 'workflows',
					'singular_name'              => 'Workflow', 'Taxonomy Singular Name', 'workflows',
					'menu_name'                  => 'Workflows', 'workflows',
					'all_items'                  => 'All Items', 'workflows',
					'parent_item'                => 'Parent Item', 'workflows',
					'parent_item_colon'          => 'Parent Item:', 'workflows',
					'new_item_name'              => 'New Item Name', 'workflows',
					'add_new_item'               => 'Add New Item', 'workflows',
					'edit_item'                  => 'Edit Item', 'workflows',
					'update_item'                => 'Update Item', 'workflows',
					'separate_items_with_commas' => 'Separate items with commas', 'workflows',
					'search_items'               => 'Search Items', 'workflows',
					'add_or_remove_items'        => 'Add or remove items', 'workflows',
					'choose_from_most_used'      => 'Choose from the most used items', 'workflows',
					'not_found'                  => 'Not Found', 'workflows',
				),
				'meta_box_cb'       => array ( 'Workflows', 'group_meta_box' ),
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
	 * Create the settings page for the plugin
	 */

	// add a menu link inside the Settings menu
	public static function add_admin_menu(  ) {

		add_submenu_page(
			'options-general.php',
			__( 'Workflows', 'workflows' ),
			__( '🥝 Workflows', 'workflows' ),
			'manage_options',
			'workflows_admin',
			function() {
				ob_start();
					settings_fields( 'pluginPage' );
					do_settings_sections( 'pluginPage' );
					submit_button();
				$form_html = ob_get_clean();
				printf(
					'<form class="workflows" action="options.php" method="post"><h1>%s</h1>%s</form>',
					__( 'Workflow Options', 'workflows' ),
					$form_html,
				);
			}
		);

	}

	// establish option page setting sections/fields
	public static function workflows_settings_init(  ) {

		// register the settings option storage location
		register_setting( 'pluginPage', 'workflows_settings' );

		// enable addition/removal of workflow stages
		add_settings_section(
			'workflows_admin_stages_section',
			__( 'Workflow Stages', 'workflows' ),
			function() {
				printf( '<p>%s</p>', __( 'Add or remove stages in your workflow.', 'workflows' ) );
			},
			'pluginPage'
		);
		add_settings_field(
			'workflows_admin_stages_fields',
			__( 'Manage Stages:', 'workflows' ),
			function() {
				$terms = self::get_workflow_stages();
				$stages_html = array();
				foreach ( $terms as $term ) {
					$meta = self::get_workflow_stage_meta( $term );
					$stages_html[] = sprintf(
						'<div class="workflows__stage">
							<input name="workflows_settings[stages][%s][id]" value="%s" type="text" />
							<input name="workflows_settings[stages][%s][name]" value="%s" type="text" />
							<span class="workflows__color">
								<input name="workflows_settings[stages][%s][color]" value="%s" data-default-color="#cccccc" class="workflows-color" type="text" />
							</span>
							<button class="workflows__remove button-secondary" aria-label="%s" title="%s" type="button">✕</button>
						</div>',
						$term->term_id,
						$term->term_id,
						$term->term_id,
						esc_html( $term->name ),
						$term->term_id,
						$meta['color'],
						__( 'Remove', 'workflows' ),
						__( 'Remove', 'workflows' )
					);
				}
				printf(
					'<fieldset>%s <button class="workflows__add button-secondary" type="button">%s</button></fieldset>',
					implode( "\n", $stages_html ),
					__( 'Add Workflow Stage', 'workflows' )
				);
			},
			'pluginPage',
			'workflows_admin_stages_section'
		);

		// create selection of post types where layouts will appear
		add_settings_section(
			'workflows_admin_post_type_section',
			__( 'Enable for Post Types', 'workflows' ),
			function() {
				printf( '<p>%s</p>', __( 'Select which post types will use workflows.', 'workflows' ) );
			},
			'pluginPage'
		);
		add_settings_field(
			'workflows_admin_post_type_fields',
			__( 'Show on post types:', 'workflows' ),
			function() {
				// grab the plugin options
				$options = get_option( 'workflows_settings' );
				// get all post types that are publicly available
				$post_types = get_post_types( array( 'public' => true ) );
				// create checkboxs listing the post types for which workflows should appear
				$fields = array();
				foreach ( $post_types as $key => $name ) {
					// media is a special type we'll exclude from workflows automatically
					if ( 'attachment' == $key ) {
						continue;
					}
					$type_obj = get_post_type_object( $name );
					$fields[] = sprintf(
						'<p><label for="%s"><input type="checkbox" id="%s" name="%s" value="%s" %s>%s</label></p>',
						$key,
						$key,
						'workflows_settings[post_types][' . $key . ']',
						$key,
						checked( $options['post_types'][$key] ?? '', $key, false ),
						$type_obj->labels->name
					);
				}
				printf( '<fieldset>%s<fieldset>', implode( "\n", $fields ) );
			},
			'pluginPage',
			'workflows_admin_post_type_section'
		);

		// turn on/off debug mode
		add_settings_section(
			'workflows_admin_debug_section',
			__( 'Enable Debugging', 'workflows' ),
			function() {
				$options = get_option( 'workflows_settings' );
				printf(
					'<fieldset><label><input type="checkbox" name="%s" value="on"%s /> %s</label><fieldset>',
					'workflows_settings[debug]',
					( $options['debug'] ?? 'off' === 'on' ? ' checked' : '' ),
					__( 'Enable Debug Mode', 'workflows' )
				);
			},
			'pluginPage'
		);

	}

	// handle settings save
	public static function save_settings( $old_value, $value ) {

		// detach the hook so it doesn't run twice
		remove_action( 'update_option_workflows_settings', array( 'Workflows', 'save_settings' ), 10, 2 );

		// extract stages information and save as taxonomy rather than option field
		$old_term_ids = self::get_workflow_stages( array( 'fields' => 'ids' ) );
		$new_terms = $value['stages'] ?? array();
		foreach( $new_terms as $new_term ) {
			$new_id = $new_term['id'] ?? 0;
			$new_name = ( $new_term['name'] ? $new_term['name'] : __( 'Not Defined', 'workflows' ) );
			$new_value = serialize( array( 'color' => $new_term['color'] ?? '#cccccc' ) );
			$matching_index = array_search( $new_id, $old_term_ids );
			if ( false !== $matching_index ) {
				wp_update_term( $new_id, 'workflows', array( 'name' => $new_name, 'description' => $new_value ) );
				unset( $old_term_ids[$matching_index] );
			} else {
				wp_insert_term( $new_name, 'workflows', array( 'name' => $new_name, 'description' => $new_value ) );
			}
		}
		// get rid of any old terms that don't appear in the new terms list
		if ( ! empty( $old_term_ids ) ) {
			foreach( $old_term_ids as $old_term_id ) {
				wp_delete_term( $old_term_id, 'workflows' );
			}
		}
		unset( $value['stages'] );

		// update the settings value sans stages
		update_option( 'workflows_settings', array( ...$value ) );

		// reattach the hook
		add_action( 'update_option_workflows_settings', array( 'Workflows', 'save_settings' ), 10, 2 );
	}

	/**
	 * Create admin column header
	 */
	public static function column_add($cols) {
		$cols['workflows'] = sprintf(
			'<abbr style="cursor:help;" title="%s">%s</abbr>',
			__( 'Current Workflow Stage', 'workflows' ),
			__( 'Stage', 'workflows' )
		);
		return $cols;
	}

	/**
	 * Create admin column values
	 *
	 * @param string $column_name
	 * @param int $id
	 * @return void
	 */
	public static function column_value( $column_name, $id ) {
		if ( 'workflows' === $column_name ) {
			$terms = wp_get_post_terms( $id, 'workflows' );
			if ( ! empty( $terms ) ) {
				$terms_html = array_map( function( $term ) use ( $id ) {
					return sprintf(
						'<a href="%sedit.php?workflows=%s&post_type=%s" class="workflows-value--%s">%s</a>',
						get_admin_url(),
						$term->slug,
						get_post_type( $id ),
						$term->slug,
						$term->name
					);
				}, $terms );
				echo implode( ', ', $terms_html );
			}
		}
	}

	/**
	 * Adds a dropdown that allows filtering on the posts SEO Quality.
	 *
	 * @return void
	 */
	public static function posts_filter_dropdown() {
		
		$post_type = sanitize_title( $_GET['post_type'] ?? '' );
		
		if ( $post_type && self::has_workflows( $post_type ) ) {

			$options = array();
			
			$terms = self::get_workflow_stages();

			foreach ( $terms as $term ) {
				$is_selected = $_GET['workflows'] ?? '' === $term->slug ? ' selected' : '';
				$options[] = sprintf(
					'<option value="%s"%s>%s</option>',
					$term->slug,
					$is_selected,
					$term->name
				);
			}

			if ( $options ) {
				printf(
					'<label class="screen-reader-text" for="workflows-filter">%s</label><select name="workflows"><option value>%s</option>%s</select>',
					esc_html__( 'Filter by Workflow Stage', 'workflows' ),
					__( 'All Stages', 'workflows' ),
					implode( "\n", $options ),
				);
			}

		}
		

	}

	/**
	 * Apply CSS styles to admin header
	 *
	 * @return void
	 */
	public static function apply_admin_styles() {
		
		// load scripts for post type lists
		$screen = get_current_screen();
		if ( 'edit' === $screen->base ) {
			$post_types = self::get_workflows_post_types();
			if ( in_array( $screen->post_type, $post_types ) ) {

				// load the stylesheet
				wp_enqueue_style( 'workflows-post-list-styles', WORKFLOWS_PLUGIN_URI . 'assets/workflows-post-list.css', null, WORKFLOWS_VERSION, 'screen' );
				
				// set the colors for each stage
				$terms = self::get_workflow_stages();
				$styles = array();
				foreach( $terms as $term ) {
					$meta = self::get_workflow_stage_meta( $term );
					$styles[] = sprintf(
						'.striped tr:has(.workflows-value--%s){background-color:%s;--workflows-color:%s;}',
						$term->slug,
						$meta['color'],
						$meta['color']
					);
				}
				if ( ! empty( $styles ) ) {
					printf( '<style id="workflows-admin-styles">%s</style>', implode( "\n", $styles ) );
				}
			}
		}
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @param string $hook_suffix
	 * @return void
	 */
	public static function enqueue_scripts( $hook_suffix ) {
		
		// load scripts for plugin settings page
		if ( "settings_page_workflows_admin" === $hook_suffix ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'workflows-settings-scripts', WORKFLOWS_PLUGIN_URI . 'assets/workflows-settings.js', array( 'jquery', 'wp-color-picker' ), WORKFLOWS_VERSION, true );
			wp_enqueue_style( 'workflows-settings-styles', WORKFLOWS_PLUGIN_URI . 'assets/workflows-settings.css', null, WORKFLOWS_VERSION, 'screen' );
		}

	}

}
