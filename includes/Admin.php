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
use WP_Post_Type;

class Admin {

	protected Repository $repository;

	private static ?self $instance = null;


	public const PARENT_PAGE = 'options-general.php';

	public const OPTION_KEY = 'augment-types';

	public const FEATURES = array(
		'archive',
		'excerpt',
		'expire',
		'thumbnail',
		'sort',
	);

	public const EXCLUDED_TYPES = array(
		'acf-field-group',
		'elementor_library',
	);


	private function __construct() {

		$this->repository = new Repository( new OptionHandler() );

	}


	protected function excluded_type( WP_Post_Type $type, array $excluded ): bool {

		return in_array( $type->name, array_merge( self::EXCLUDED_TYPES, $excluded ), true );

	}


	public static function instance(): self {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	public function init(): void {

		( new SubMenuPage( __( 'Augment Types', 'augment-types' ) ) )
			->parent( self::PARENT_PAGE )
			->slug( self::OPTION_KEY )
			->setup();

		foreach ( self::FEATURES as $feature ) {
			$method = $feature . '_box';
			/** @var OptionBox $box */
			$box = $this->$method();

			$this->repository->store( $box->get_config() );

			$box->location( self::OPTION_KEY )->create();
		}

		add_action( 'themeplate_settings_' . self::OPTION_KEY . '_advanced', array( $this, 'scripts' ) );
		add_action( 'themeplate_settings_' . self::OPTION_KEY . '_advanced', array( $this, 'styles' ) );

	}


	/** @return mixed */
	public function option( string $key ) {

		return $this->repository->retrieve( $key, self::OPTION_KEY );

	}


	public function scripts(): void {

		?>
<script>
	jQuery( '.augment-type-toggle' ).on( 'click', function() {
		var mode = jQuery( this ).data( 'toggle' );

		jQuery( this ).parents( '.fields-container' ).find( 'input[type=checkbox]' ).each( function() {
			this.checked = mode === 'all';
		} );
	} );
</script>
		<?php

	}


	public function styles(): void {

		?>
<style>
	[id^=augment-types_][id$=_enabled] {
		columns: 2;
		word-break: break-all;
	}

	.augment-types-toggles {
		display: flex;
		gap: 0.5rem;
		font-weight: 500;
	}

	.augment-types-legend {
		display: contents;
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
			'enabled' => array(
				'type'     => 'checklist',
				'options'  => $types,
				'multiple' => true,
				'default'  => array_keys( $types ),
			),
			'toggles' => array(
				'type'    => 'html',
				'options' => array( $this, 'toggles_html' ),
			),
		);

	}


	public function toggles_html(): string {

		ob_start();

		?>
<fieldset class="augment-types-toggles">
	<legend class="augment-types-legend"><?php esc_html_e( 'Select' ); ?>:</legend>
	<button type="button" class="button-link augment-type-toggle" data-toggle="all"><?php esc_html_e( 'All' ); ?></button>
	<span>|</span>
	<button type="button" class="button-link augment-type-toggle" data-toggle="none"><?php esc_html_e( 'None' ); ?></button>
</fieldset>
		<?php

		return ob_get_clean();

	}


	public function archive_box(): OptionBox {

		$title = __( 'Enable archiving on', 'augment-types' );
		$args  = array( 'data_prefix' => 'archive_' );
		$types = array_filter(
			get_post_types( Archive::TYPE_ARGS, 'objects' ),
			function ( $type ) {
				return ! $this->excluded_type( $type, Archive::EXCLUDED_TYPES );
			}
		);

		return ( new OptionBox( $title, $args ) )
			->fields( $this->checklist_field( $types ) );

	}


	public function excerpt_box(): OptionBox {

		$title = __( 'Enable exciting excerpts on', 'augment-types' );
		$args  = array( 'data_prefix' => 'excerpt_' );
		$types = array_filter(
			get_post_types( Excerpt::TYPE_ARGS, 'objects' ),
			function ( $type ) {
				if ( $this->excluded_type( $type, Excerpt::EXCLUDED_TYPES ) ) {
					return false;
				}

				return post_type_supports( $type->name, 'excerpt' );
			}
		);

		return ( new OptionBox( $title, $args ) )
			->fields( $this->checklist_field( $types ) );

	}


	public function expire_box(): OptionBox {

		$title = __( 'Enable expirator on', 'augment-types' );
		$args  = array( 'data_prefix' => 'expire_' );
		$types = array_filter(
			get_post_types( array(), 'objects' ),
			function ( $type ) {
				if ( $this->excluded_type( $type, array() ) ) {
					return false;
				}

				if ( in_array( $type->name, array( 'page', 'post' ), true ) ) {
					return true;
				}

				return ! $type->_builtin;
			}
		);

		return ( new OptionBox( $title, $args ) )
			->fields( $this->checklist_field( $types ) );

	}


	public function thumbnail_box(): OptionBox {

		$title = __( 'Enable thumbnail column on', 'augment-types' );
		$args  = array( 'data_prefix' => 'thumbnail_' );
		$types = array_filter(
			get_post_types( Feature::TYPE_ARGS, 'objects' ),
			function ( $type ) {
				if ( $this->excluded_type( $type, array() ) ) {
					return false;
				}

				return post_type_supports( $type->name, 'thumbnail' );
			}
		);

		return ( new OptionBox( $title, $args ) )
			->fields( $this->checklist_field( $types ) );

	}


	public function sort_box(): OptionBox {

		$title = __( 'Enable sorting on', 'augment-types' );
		$args  = array( 'data_prefix' => 'sort_' );
		$types = array_filter(
			get_post_types( Sort::TYPE_ARGS, 'objects' ),
			function ( $type ) {
				return ! $this->excluded_type( $type, Sort::EXCLUDED_TYPES );
			}
		);

		return ( new OptionBox( $title, $args ) )
			->fields( $this->checklist_field( $types ) );

	}

}
