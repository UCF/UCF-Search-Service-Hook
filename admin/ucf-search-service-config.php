<?php
/**
 * Handles plugin configuration
 */
if ( ! class_exists( 'UCF_Search_Service_Config' ) ) {
	class UCF_Search_Service_Config {
		public static
			$option_prefix   = 'ucf_search_service_',
			$option_defaults = array(
				'api_base_url' => 'https://search.cm.ucf.edu/api/v1/',
				'api_key'      => null,
				'update_desc'  => true,
				'update_profs' => true,
				'desc_type'    => null,
				'prof_type'    => null
			);

		/**
		 * Fetches the ProgramDescriptionTypes from the
		 * search service.
		 * @return array The description types
		 */
		public static function get_description_types() {
			$transient_name    = 'ucf_search_service_description_types';
			$transient_timeout = DAY_IN_SECONDS;
			$retval            = get_transient( $transient_name );

			if ( ! $retval ) {
				$base_url    = self::get_option_or_default( 'api_base_url' );
				$request_url = $base_url . 'descriptions/types/';

				$items = self::fetch_api_values( $request_url );

				if ( $items ) {
					$retval = array();

					foreach( $items as $item ) {
						$retval[$item->id] = $item->name;
					}

					set_transient( $transient_name, $retval, $transient_timeout );
				}
			}

			return $retval;
		}

		/**
		 * Fetches the ProgramProfileTypes from the
		 * search service.
		 * @return array The profile types
		 */
		public static function get_profile_types() {
			$transient_name    = 'ucf_search_service_profile_types';
			$transient_timeout = DAY_IN_SECONDS;
			$retval            = get_transient( $transient_name );

			if ( ! $retval ) {
				$base_url       = self::get_option_or_default( 'api_base_url' );
				$request_url = $base_url . 'profiles/types/';

				$items = self::fetch_api_values( $request_url );

				if ( $items ) {
					$retval = array();

					foreach( $items as $item ) {
						$retval[$item->id] = $item->name;
					}

					set_transient( $transient_name, $retval, $transient_timeout );
				}
			}

			return $retval;
		}

		/**
		 * Retrieves values via an HTTP request
		 * @param string $url | The url of the API endpoint
		 * @return mixed The returned value.
		 */
		private static function fetch_api_values( $url ) {
			$key = self::get_option_or_default( 'api_key' );

			$url .= '?' . http_build_query(
				array(
					'key' => $key
				)
			);

			$retval = false;
			$response = wp_remote_get( $url, array( 'timeout' => 5 ) );

			if ( is_array( $response ) && wp_remote_retrieve_response_code( $response ) < 400 ) {
				$retval = json_decode( wp_remote_retrieve_body( $response ) );
			}

			return $retval->results;
		}

		/**
		 * Creates options via the WP Options API that are utilized
		 * by the plugin. Intended to be run on plugin activation.
		 * @return void
		 */
		public static function add_options() {
			$defaults = self::$option_defaults;

			add_option( self::$option_prefix . 'api_base_url', $defaults['api_base_url'] );
			add_option( self::$option_prefix . 'api_key', $defaults['api_key'] );
			add_option( self::$option_prefix . 'update_desc', $defaults['update_desc'] );
			add_option( self::$option_prefix . 'update_prof', $defaults['update_prof'] );
			add_option( self::$option_prefix . 'desc_type', $defaults['desc_type'] );
			add_option( self::$option_prefix . 'prof_type', $defaults['prof_type'] );
		}

		/**
		 * Deletes options via the WP Options API that are utilized
		 * by the plugin. Intended to be run on plugin deactivation.
		 * @return void
		 */
		public static function delete_options() {
			delete_option( self::$option_prefix . 'api_base_url' );
			delete_option( self::$option_prefix . 'api_key' );
			delete_option( self::$option_prefix . 'update_desc' );
			delete_option( self::$option_prefix . 'update_prof' );
			delete_option( self::$option_prefix . 'desc_type' );
			delete_option( self::$option_prefix . 'prof_type' );
		}

		/**
		 * Returns a list of default plugin options. Applies any overriden
		 * default values set within the options page.
		 * @return array
		 */
		public static function get_option_defaults() {
			$defaults = self::$option_defaults;

			$configurable_defaults = array(
				'api_base_url' => get_option( self::$option_prefix . 'api_base_url', $defaults['api_base_url'] ),
				'api_key'      => get_option( self::$option_prefix . 'api_key', $defaults['api_key'] ),
				'update_desc'  => get_option( self::$option_prefix . 'update_desc', $defaults['update_desc'] ),
				'update_prof'  => get_option( self::$option_prefix . 'update_prof', $defaults['update_prof'] ),
				'desc_type'    => get_option( self::$option_prefix . 'desc_type', $defaults['desc_type'] ),
				'prof_type'    => get_option( self::$option_prefix . 'prof_type', $defaults['prof_type'] )
			);

			$defaults = array_merge( $defaults, $configurable_defaults );

			return $defaults;
		}

		/**
		 * Performs typecasting, sanitization, etc on an array of plugin options.
		 * @param array $list | Associative array of plugin options
		 * @return array
		 */
		public static function format_options( $list ) {
			foreach( $list as $key => $val ) {
				switch( $key ) {
					case 'update_desc':
					case 'update_prof':
						$list[$key] = filter_var( $val, FILTER_VALIDATE_BOOLEAN );
						break;
					case 'desc_type':
					case 'prof_type':
						$list[$key] = filter_var( $val, FILTER_VALIDATE_INT );
						break;
					case 'api_base_url':
						$list[$key] = trailingslashit( $val );
						break;
					default:
						break;
				}
			}

			return $list;
		}

		/**
		 * Applies formatting to a single option. Intended to be passed to the
		 * option_{$option} hook.
		 * @param mixed $value | The value to be formatted
		 * @param string $option_name | The name of the option to be formatted
		 * @return mixed
		 */
		public static function format_option( $value, $option_name ) {
			$option_formatted = self::format_options( array( $option_name => $value ) );
			return $option_formatted[$option_name];
		}

		/**
		 * Adds filters for plugin options that apply
		 * our formatting rules to option values.
		 * @return void
		 */
		public static function add_option_formatting_filters() {
			$defaults = self::$option_defaults;

			foreach( $defaults as $option => $default ) {
				add_filter( 'option_{$option}', array( 'UCF_Search_Service_Config', 'format_option' ), 10, 2 );
			}
		}

		/**
		 * Convenience method for returning an option from the WP Options API
		 * or a plugin option default.
		 * @param string $option_name | The name of the option to retrieve
		 * @return mixed
		 */
		public static function get_option_or_default( $option_name ) {
			// Handle $option_name passed in with or without self::$option_prefix applied:
			$option_name_no_prefix = str_replace( self::$option_prefix, '', $option_name );
			$option_name           = self::$option_prefix . $option_name_no_prefix;
			$defaults              = self::get_option_defaults();

			return get_option( $option_name, $defaults[$option_name_no_prefix] );
		}

		/**
		 * Initializes setting registration with the Settings API.
		 * @return void
		 */
		public static function settings_init() {
			$settings_slug = 'ucf_search_service';
			$defaults      = self::$option_defaults;
			$display_fn    = array( 'UCF_Search_Service_Config', 'display_settings_field' );

			// Register Settings
			foreach( $defaults as $option_name => $default ) {
				register_setting(
					$settings_slug,
					self::$option_prefix . $option_name
				);
			}

			// Register sections
			$general_section = 'ucf_search_service_general';

			add_settings_section(
				$general_section,
				'General Settings',
				'',
				$settings_slug
			);

			$description_section = 'ucf_search_service_description';

			add_settings_section(
				$description_section,
				'Description Updates',
				'',
				$settings_slug
			);

			$profile_section = 'ucf_search_service_profile';

			add_settings_section(
				$profile_section,
				'Profile Updates',
				'',
				$settings_slug
			);

			// Register fields
			add_settings_field(
				self::$option_prefix . 'api_base_url', // Setting name
				'Search Service Base URL', // Setting display name
				$display_fn, // Display function
				$settings_slug, // The settings page slug
				$general_section,
				array( // Additional arguments to pass to the display function
					'label_for'   => self::$option_prefix . 'api_base_url',
					'description' => 'The base url of the UCF Search Service API. Should end with `/api/v1/` with trailing slash.',
					'type'        => 'text'
				)
			);

			add_settings_field(
				self::$option_prefix . 'api_key', // Setting name
				'Search Service API Key', // Setting display name
				$display_fn, // Display function
				$settings_slug, // The settings page slug
				$general_section,
				array( // Additional arguments to pass to the display function
					'label_for'   => self::$option_prefix . 'api_key',
					'description' => 'The API key used to access the Search Service API. This is required for all calls.',
					'type'        => 'text'
				)
			);

			add_settings_field(
				self::$option_prefix . 'update_desc', // Setting name
				'Update Descriptions', // Setting display name
				$display_fn, // Display function
				$settings_slug, // The settings page slug
				$description_section,
				array( // Additional arguments to pass to the display function
					'label_for'   => self::$option_prefix . 'update_desc',
					'description' => 'When checked, descriptions will be written to the UCF Search Service on post save.',
					'type'        => 'checkbox'
				)
			);

			add_settings_field(
				self::$option_prefix . 'desc_type', // Setting name
				'Description Type', // Setting display name
				$display_fn, // Display function
				$settings_slug, // The settings page slug
				$description_section,
				array( // Additional arguments to pass to the display function
					'label_for'   => self::$option_prefix . 'desc_type',
					'description' => 'The description type to set when writing to the search service.',
					'type'        => 'select',
					'choices'     => self::get_description_types()
				)
			);

			add_settings_field(
				self::$option_prefix . 'update_prof', // Setting name
				'Update Profile URLs', // Setting display name
				$display_fn, // Display function
				$settings_slug, // The settings page slug
				$profile_section,
				array( // Additional arguments to pass to the display function
					'label_for'   => self::$option_prefix . 'update_prof',
					'description' => 'When checked, profile URLs will be written to the UCF Search Service on post save.',
					'type'        => 'checkbox'
				)
			);

			add_settings_field(
				self::$option_prefix . 'prof_type', // Setting name
				'Profile Type', // Setting display name
				$display_fn, // Display function
				$settings_slug, // The settings page slug
				$profile_section,
				array( // Additional arguments to pass to the display function
					'label_for'   => self::$option_prefix . 'prof_type',
					'description' => 'The profile type to set when writing to the search service.',
					'type'        => 'select',
					'choices'     => self::get_profile_types()
				)
			);
		}

		/**
		 * Displays an individual setting's field markup
		 * @param array $args | An assoc. array of arguments used to display the field.
		 * @return void | Echos the return html.
		 */
		public static function display_settings_field( $args ) {
			$option_name   = $args['label_for'];
			$description   = $args['description'];
			$field_type    = $args['type'];
			$current_value = self::get_option_or_default( $option_name );
			$choices       = isset( $args['choices'] ) ? $args['choices'] : null;
			$markup        = '';

			switch( $field_type ) {
				case 'checkbox':
					ob_start();
				?>
					<input type="checkbox" id="<?php echo $option_name; ?>" name="<?php echo $option_name; ?>" <?php echo ( $current_value === true ) ? 'checked' : ''; ?>>
					<p class="description">
						<?php echo $description; ?>
					</p>
				<?php
					$markup = ob_get_clean();
					break;
				case 'select':
					ob_start();
				?>
					<?php if ( $choices ) : ?>
					<select id="<?php echo $option_name; ?>" name="<?php echo $option_name; ?>">
						<?php foreach ( $choices as $value => $text ) : ?>
							<option value="<?php echo $value; ?>" <?php echo ( (int)$current_value === $value ) ? 'selected' : ''; ?>><?php echo $text; ?></option>
						<?php endforeach; ?>
					</select>
					<?php else: ?>
					<p style="color: #d54e21;">There was an error retrieving the choices for this field.</p>
					<?php endif; ?>
					<p class="description">
						<?php echo $description; ?>
					</p>
				<?php
					$markup = ob_get_clean();
					break;
				case 'text':
				default:
					ob_start();
				?>
					<input type="text" id="<?php echo $option_name; ?>" name="<?php echo $option_name; ?>" class="regular-text" value="<?php echo $current_value; ?>">
					<p class="description">
						<?php echo $description; ?>
					</p>
				<?php
					$markup = ob_get_clean();
					break;
			}

			echo $markup;
		}

		/**
		 * Register the settings page to display in the WordPress admin.
		 * @return void
		 */
		public static function add_options_page() {
			$page_title = 'UCF Search Service Settings';
			$menu_title = 'UCF Search Service Hook';
			$capability = 'manage_options';
			$menu_slug = 'ucf_search_service';
			$callback  = array( 'UCF_Search_Service_Config', 'options_page_html' );

			return add_options_page(
				$page_title,
				$menu_title,
				$capability,
				$menu_slug,
				$callback
			);
		}

		/**
		 * Displays the plugin's settings page form.
		 * @return void
		 */
		public static function options_page_html() {
			ob_start();
		?>
			<div class="wrap">
				<h1><?php echo get_admin_page_title(); ?></h1>
				<form method="post" action="options.php">
				<?php
					settings_fields( 'ucf_search_service' );
					do_settings_sections( 'ucf_search_service' );
					submit_button();
				?>
				</form>
			</div>
		<?php
			echo ob_get_clean();
		}
	}

	add_action( 'admin_init', array( 'UCF_Search_Service_Config', 'settings_init' ) );
	add_action( 'admin_menu', array( 'UCF_Search_Service_Config', 'add_options_page' ) );
	UCF_Search_Service_Config::add_option_formatting_filters();
}
?>
