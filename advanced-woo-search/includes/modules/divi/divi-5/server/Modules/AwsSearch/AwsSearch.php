<?php
/**
 * Divi 5 AWS Search module.
 */

namespace AWS\Divi5\Modules\AwsSearch;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

if ( ! interface_exists( DependencyInterface::class ) || ! class_exists( ModuleRegistration::class ) ) {
	return;
}

class AwsSearch implements DependencyInterface {

	/**
	 * Register the module metadata with Divi 5.
	 */
	public function load() {
		$module_json_folder_path = dirname( __DIR__, 3 ) . '/visual-builder/src/modules/aws-search';

		add_action(
			'init',
			function() use ( $module_json_folder_path ) {
				ModuleRegistration::register_module(
					$module_json_folder_path,
					array(
						'render_callback' => array( __CLASS__, 'render_callback' ),
					)
				);
			}
		);
	}

	/**
	 * Render callback.
	 *
	 * @param array $attrs Module attributes.
	 * @return string
	 */
	public static function render_callback( $attrs ) {
		if ( ! function_exists( 'aws_get_search_form' ) ) {
			return '';
		}

		$form_id     = self::get_attr_value( $attrs, 'form_id', '1' );
		$placeholder = self::get_attr_value( $attrs, 'placeholder', '' );
		$args        = array(
			'id' => $form_id ? $form_id : '1',
		);

		if ( '' !== $placeholder ) {
			$args['placeholder'] = $placeholder;
		}

		return aws_get_search_form( false, $args );
	}

	/**
	 * Extract scalar values from Divi 5 attrs.
	 *
	 * @param array  $attrs Module attrs.
	 * @param string $key Attr key.
	 * @param string $default Fallback value.
	 * @return string
	 */
	private static function get_attr_value( $attrs, $key, $default = '' ) {
		if ( ! isset( $attrs[ $key ] ) ) {
			return $default;
		}

		$value = $attrs[ $key ];

		if ( is_scalar( $value ) ) {
			return (string) $value;
		}

		if ( isset( $value['innerContent']['desktop']['value'] ) && is_scalar( $value['innerContent']['desktop']['value'] ) ) {
			return (string) $value['innerContent']['desktop']['value'];
		}

		if ( isset( $value['desktop']['value'] ) && is_scalar( $value['desktop']['value'] ) ) {
			return (string) $value['desktop']['value'];
		}

		if ( isset( $value['value'] ) && is_scalar( $value['value'] ) ) {
			return (string) $value['value'];
		}

		return $default;
	}
}
