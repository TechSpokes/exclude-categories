<?php

namespace TechSpokes\ExcludeCategories;

use WP_Query;

/**
 * Class Plugin
 *
 * @package TechSpokes\ExcludeCategories
 */
class Plugin {

	const SETTING_EXCLUDE_CATEGORIES_BLOG   = 'ts_exclude_categories_blog';
	const SETTING_EXCLUDE_CATEGORIES_SEARCH = 'ts_exclude_categories_search';
	const SETTING_EXCLUDE_CATEGORIES_FEED   = 'ts_exclude_categories_feed';

	/**
	 * @var \TechSpokes\ExcludeCategories\Plugin $instance
	 */
	protected static $instance;

	/**
	 * Initialize the plugin.
	 *
	 * @return \TechSpokes\ExcludeCategories\Plugin
	 */
	public static function getInstance(): Plugin {
		if ( ! ( self::$instance instanceof Plugin ) ) {
			self::setInstance( new self() );
		}

		return self::$instance;
	}

	/**
	 * Set the plugin instance.
	 *
	 * @param \TechSpokes\ExcludeCategories\Plugin $instance
	 */
	protected static function setInstance( Plugin $instance ) {
		self::$instance = $instance;
	}

	/**
	 * Plugin constructor.
	 */
	protected function __construct() {
		// register settings
		add_action( 'init', array( $this, 'register_settings' ), 10, 0 );
		// admin UI
		add_action( 'admin_menu', array( $this, 'add_settings_ui' ), 10, 0 );
		// exclude categories
		add_action( 'pre_get_posts', array( $this, 'exclude_categories' ), 10, 1 );
	}

	/**
	 * Registers settings in WordPress.
	 */
	public function register_settings() {
		//register settings
		foreach (
			array(
				self::SETTING_EXCLUDE_CATEGORIES_BLOG,
				self::SETTING_EXCLUDE_CATEGORIES_SEARCH,
				self::SETTING_EXCLUDE_CATEGORIES_FEED,
			) as $setting
		) {
			register_setting( 'reading', $setting, array( $this, 'sanitize_setting' ) );
		}
	}

	/**
	 * Sanitizes settings.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function sanitize_setting( string $value = '' ): string {
		//regex value to keep only numbers and commas
		$value = preg_replace( '/[^0-9,]/', '', sanitize_text_field( $value ) );
		//explode value into array and map to absint
		$value = array_map( 'absint', explode( ',', $value ) );
		//filter empty values and remove duplicates
		$value = array_filter( array_unique( $value ) );

		//implode array back to string and return
		return implode( ',', $value );
	}

	/**
	 * Adds settings UI to WordPress.
	 */
	public function add_settings_ui() {

		// add settings section
		add_settings_section(
			'ts-exclude-categories',
			__( 'Exclude categories', 'techspokes-exclude-categories' ),
			'__return_empty_string',
			'reading'
		);

		//add settings field to exclude categories on main blog page
		add_settings_field(
			'techspokes-exclude-categories-blog',
			__( 'Blog page', 'techspokes-exclude-categories' ),
			array( $this, 'settings_field' ),
			'reading',
			'ts-exclude-categories',
			array(
				'label_for'        => $this->sanitize_html_id( self::SETTING_EXCLUDE_CATEGORIES_BLOG ),
				'description'      => __( 'Enter comma separated IDs of categories to exclude from blog page.', 'techspokes-exclude-categories' ),
				'input_attributes' => array(
					'id'    => $this->sanitize_html_id( self::SETTING_EXCLUDE_CATEGORIES_BLOG ),
					'name'  => self::SETTING_EXCLUDE_CATEGORIES_BLOG,
					'type'  => 'text',
					'value' => strval( get_option( self::SETTING_EXCLUDE_CATEGORIES_BLOG, '' ) ),
					'class' => 'widefat',
				),
			)
		);

		//add settings field to exclude categories on search results page
		add_settings_field(
			'techspokes-exclude-categories-search',
			__( 'Search results page', 'techspokes-exclude-categories' ),
			array( $this, 'settings_field' ),
			'reading',
			'ts-exclude-categories',
			array(
				'label_for'        => $this->sanitize_html_id( self::SETTING_EXCLUDE_CATEGORIES_SEARCH ),
				'description'      => __( 'Enter comma separated IDs of categories to exclude from search results page.', 'techspokes-exclude-categories' ),
				'input_attributes' => array(
					'id'    => $this->sanitize_html_id( self::SETTING_EXCLUDE_CATEGORIES_SEARCH ),
					'name'  => self::SETTING_EXCLUDE_CATEGORIES_SEARCH,
					'type'  => 'text',
					'value' => strval( get_option( self::SETTING_EXCLUDE_CATEGORIES_SEARCH, '' ) ),
					'class' => 'widefat',
				),
			)
		);

		//add setting field to exclude the categories in the feed
		add_settings_field(
			'techspokes-exclude-categories-feed',
			__( 'Feed', 'techspokes-exclude-categories' ),
			array( $this, 'settings_field' ),
			'reading',
			'ts-exclude-categories',
			array(
				'label_for'        => $this->sanitize_html_id( self::SETTING_EXCLUDE_CATEGORIES_FEED ),
				'description'      => __( 'Enter comma separated IDs of categories to exclude from feed.', 'techspokes-exclude-categories' ),
				'input_attributes' => array(
					'id'    => $this->sanitize_html_id( self::SETTING_EXCLUDE_CATEGORIES_FEED ),
					'name'  => self::SETTING_EXCLUDE_CATEGORIES_FEED,
					'type'  => 'text',
					'value' => strval( get_option( self::SETTING_EXCLUDE_CATEGORIES_FEED, '' ) ),
					'class' => 'widefat',
				),
			)
		);
	}

	/**
	 * Displays settings field.
	 *
	 * @param array $args
	 */
	public function settings_field( array $args ) {
		/**
		 * @var string $label_for
		 * @var string $description
		 * @var array  $input_attributes
		 */
		extract( $args, EXTR_OVERWRITE );
		array_walk( $input_attributes, function ( &$item, $key ) {
			$item = sprintf( '%1$s="%2$s"', $key, esc_attr( $item ) );
		} );
		/** @noinspection HtmlUnknownAttribute */
		printf(
			'<p><input %1$s/></p>',
			join( ' ', $input_attributes )
		);
		if ( ! empty( $description ) ) {
			printf(
				'<p class="%1$s">%2$s</p>',
				'description',
				$description
			);
		}
	}

	/**
	 * Adds category__not_in query parameter based on user settings.
	 *
	 * @param \WP_Query $query
	 */
	public function exclude_categories( WP_Query $query ) {
		//only modify main query if it is not in the admin area
		if ( $query->is_main_query() && ! $query->is_admin ) {
			//if query is for main blog page
			if ( $query->is_home() ) {
				//is query a feed?
				if ( $query->is_feed() ) {
					//get excluded categories from feed
					$excluded_categories = $this->get_excluded_categories( self::SETTING_EXCLUDE_CATEGORIES_FEED );
				} else {
					//get excluded categories from blog page
					$excluded_categories = $this->get_excluded_categories( self::SETTING_EXCLUDE_CATEGORIES_BLOG );
				}
			} elseif ( $query->is_search() ) {
				//get excluded categories from search results page
				$excluded_categories = $this->get_excluded_categories( self::SETTING_EXCLUDE_CATEGORIES_SEARCH );
			} else {
				//we have nothing to exclude
				$excluded_categories = array();
			}
			//if there are categories to exclude
			if ( ! empty( $excluded_categories ) ) {
				//add category__not_in to existing query parameter
				$query->set( 'category__not_in', array_filter( array_unique( array_merge(
					(array) $query->get( 'category__not_in', array() ),
					$excluded_categories
				) ) ) );
			}
		}
	}

	/**
	 * Sanitizes HTML ID attribute.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function sanitize_html_id( string $name ): string {
		//replace non literals or numbers with hyphens and return
		return preg_replace( '/[^a-z0-9]+/i', '-', $name );
	}

	/**
	 * Returns array excluded categories IDs.
	 *
	 * @param string $option
	 *
	 * @return array|int[]
	 */
	protected function get_excluded_categories( string $option ): array {
		//return array of excluded categories IDs
		return array_filter( array_unique( array_map(
			'absint',
			explode( ',', strval( get_option( $option, '' ) ) )
		) ) );
	}

	/**
	 * Checks if the provided value is empty.
	 *
	 * @param null $value
	 *
	 * @return bool
	 */
	protected function is_empty( $value = null ): bool {
		return empty( $value );
	}

}
