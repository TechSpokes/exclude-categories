<?php
/**
 * Created by PhpStorm.
 * User: serge
 * Date: 22/11/2018
 * Time: 18:03
 */

namespace TechSpokes\ExcludeCategories;

/**
 * Class Plugin
 *
 * @package TechSpokes\ExcludeCategories
 */
class Plugin {

	/**
	 * @var \TechSpokes\ExcludeCategories\Plugin $instance
	 */
	protected static $instance;

	/**
	 * @return \TechSpokes\ExcludeCategories\Plugin
	 */
	public static function getInstance() {

		if ( ! ( self::$instance instanceof Plugin ) ) {
			self::setInstance( new self() );
		}

		return self::$instance;
	}

	/**
	 * @param \TechSpokes\ExcludeCategories\Plugin $instance
	 */
	protected static function setInstance( Plugin $instance ) {

		self::$instance = $instance;
	}

	/**
	 * Loads plugin.
	 */
	public static function load() {

		self::getInstance();
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

		// blog
		register_setting(
			'reading',
			'ts_exclude_categories_blog',
			array(
				'sanitize_callback' => array( $this, 'sanitize_setting' )
			)
		);

		// feed
		register_setting(
			'reading',
			'ts_exclude_categories_feed',
			array(
				'sanitize_callback' => array( $this, 'sanitize_setting' )
			)
		);
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public function sanitize_setting( $value = '' ) {

		return trim( preg_replace( '/[,]{2,}/', ',', preg_replace( '/[^0-9,]/', '', sanitize_text_field( $value ) ) ), ',' );
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

		// blog
		add_settings_field(
			'techspokes-exclude-categories-blog',
			__( 'Blog page', 'techspokes-exclude-categories' ),
			array( $this, 'settings_field' ),
			'reading',
			'ts-exclude-categories',
			array(
				'label_for'        => 'ts-exclude-categories-blog',
				'description'      => __( 'Enter comma separated IDs of categories to exclude from blog page.', 'techspokes-exclude-categories' ),
				'input_attributes' => array(
					'id'    => 'ts-exclude-categories-blog',
					'name'  => 'ts_exclude_categories_blog',
					'type'  => 'text',
					'value' => get_option( 'ts_exclude_categories_blog' ),
					'class' => 'widefat'
				)
			)
		);

		// feed
		add_settings_field(
			'techspokes-exclude-categories-feed',
			__( 'Feed', 'techspokes-exclude-categories' ),
			array( $this, 'settings_field' ),
			'reading',
			'ts-exclude-categories',
			array(
				'label_for'        => 'ts-exclude-categories-feed',
				'description'      => __( 'Enter comma separated IDs of categories to exclude from feed.', 'techspokes-exclude-categories' ),
				'input_attributes' => array(
					'id'    => 'ts-exclude-categories-feed',
					'name'  => 'ts_exclude_categories_feed',
					'type'  => 'text',
					'value' => get_option( 'ts_exclude_categories_feed' ),
					'class' => 'widefat'
				)
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
		printf(
			'<p><input %1$s /></p>',
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
	public function exclude_categories( \WP_Query &$query ) {

		if ( $query->is_main_query() && ! $query->is_admin ) {
			if ( $query->is_home() && ! $query->is_feed() ) {
				$excluded = array_filter( array_map( 'absint', explode( ',', get_option( 'ts_exclude_categories_blog' ) ) ) );
				if ( ! empty( $excluded ) ) {
					$query->set( 'category__not_in', array_unique( array_merge(
						(array) $query->get( 'category__not_in' ),
						$excluded
					) ) );
				}
			} elseif ( $query->is_home() && $query->is_feed() ) {
				$excluded = array_filter( array_map( 'absint', explode( ',', get_option( 'ts_exclude_categories_feed' ) ) ) );
				if ( ! empty( $excluded ) ) {
					$query->set( 'category__not_in', array_unique( array_merge(
						(array) $query->get( 'category__not_in' ),
						$excluded
					) ) );
				}
			}
		}
	}
}