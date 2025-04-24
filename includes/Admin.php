<?php

/**
 * @package Augment Types
 * @since 0.1.0
 */

namespace AugmentTypes;

use ThemePlate\Core\Repository;
use ThemePlate\Page\SubMenuPage;
use ThemePlate\Settings\OptionBox;
use ThemePlate\Settings\OptionHandler;

class Admin {

	protected Repository $repository;

	private static $instance;


	public const OPTION_KEY = 'augment-types';

	public const FEATURES = array(
		'archive',
		'excerpt',
		'expire',
		'thumbnail',
		'sort',
	);


	private function __construct() {

		$this->repository = new Repository( new OptionHandler() );

	}


	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	public function init(): void {

		( new SubMenuPage( __( 'Augment Types', 'augment-types' ) ) )
			->parent( 'options-general.php' )
			->slug( self::OPTION_KEY )
			->setup();

		foreach ( self::FEATURES as $feature ) {
			$method = $feature . '_box';
			/** @var OptionBox $box */
			$box = $this->$method();

			$this->repository->store( $box->get_config() );

			$box->location( self::OPTION_KEY )->create();
		}

		add_action( 'themeplate_settings_' . self::OPTION_KEY . '_advanced', array( $this, 'styles' ) );

	}


	/** @return mixed */
	public function option( string $key ) {

		return $this->repository->retrieve( $key, self::OPTION_KEY );

	}


	public function styles() {

		?>
<style>
	[id^=augment-types_][id$=_disabled] {
		columns: 2;
		word-break: break-all;
	}
</style>
		<?php

	}


	/**
	 * @param WP_Post_Type[] $options
	 * @return array<string, mixed>
	 */
	protected function checklist_field( array $options ): array {

		$types = array_column( $options, 'label', 'name' );

		return array(
			'disabled' => array(
				'type'     => 'checklist',
				'options'  => $types,
				'multiple' => true,
			),
		);

	}


	public function archive_box(): OptionBox {

		$title = __( 'Disable archiving on', 'augment-types' );
		$args  = array( 'data_prefix' => 'archive_' );
		$types = array_filter(
			get_post_types( array( 'public' => true ), 'objects' ),
			function ( $type ) {
				return ! in_array( $type->name, array( 'page', 'attachment' ), true );
			}
		);

		return ( new OptionBox( $title, $args ) )
			->fields( $this->checklist_field( $types ) );

	}


	public function excerpt_box(): OptionBox {

		$title = __( 'Disable exciting excerpts on', 'augment-types' );
		$args  = array( 'data_prefix' => 'excerpt_' );
		$types = array_filter(
			get_post_types( array( 'show_ui' => true ), 'objects' ),
			function ( $type ) {
				if ( in_array( $type->name, array( 'wp_block' ), true ) ) {
					return false;
				}

				return post_type_supports( $type->name, 'at-excerpt' );
			}
		);

		return ( new OptionBox( $title, $args ) )
			->fields( $this->checklist_field( $types ) );

	}


	public function expire_box(): OptionBox {

		$title = __( 'Disable expirator on', 'augment-types' );
		$args  = array( 'data_prefix' => 'expire_' );
		$types = array_filter(
			get_post_types( array(), 'objects' ),
			function ( $type ) {
				if ( in_array( $type->name, array( 'attachment', 'page', 'post' ), true ) ) {
					return true;
				}

				return ! $type->_builtin;
			}
		);

		return ( new OptionBox( $title, $args ) )
			->fields( $this->checklist_field( $types ) );

	}


	public function thumbnail_box(): OptionBox {

		$title = __( 'Disable thumbnail column on', 'augment-types' );
		$args  = array( 'data_prefix' => 'thumbnail_' );
		$types = array_filter(
			get_post_types( array( 'show_ui' => true ), 'objects' ),
			function ( $type ) {
				return post_type_supports( $type->name, 'thumbnail' );
			}
		);

		return ( new OptionBox( $title, $args ) )
			->fields( $this->checklist_field( $types ) );

	}


	public function sort_box(): OptionBox {

		$title = __( 'Disable sorting on', 'augment-types' );
		$args  = array( 'data_prefix' => 'sort_' );
		$types = array_filter(
			get_post_types( array( 'show_ui' => true ), 'objects' ),
			function ( $type ) {
				if ( in_array( $type->name, array( 'wp_block', 'wp_navigation' ), true ) ) {
					return false;
				}

				return ! in_array( $type->name, Sort::EXCLUDED_TYPES, true );
			}
		);

		return ( new OptionBox( $title, $args ) )
			->fields( $this->checklist_field( $types ) );

	}

}
