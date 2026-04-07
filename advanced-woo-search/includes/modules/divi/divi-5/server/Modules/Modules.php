<?php
/**
 * Divi 5 module bootstrap.
 */

namespace AWS\Divi5\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

require_once __DIR__ . '/AwsSearch/AwsSearch.php';

use AWS\Divi5\Modules\AwsSearch\AwsSearch;
use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * Register AWS Divi 5 module dependency.
 *
 * @param object $dependency_tree Dependency tree object.
 * @return void
 */
function aws_pro_divi5_register_modules( $dependency_tree ) {
	if ( ! interface_exists( DependencyInterface::class ) || ! class_exists( ModuleRegistration::class ) ) {
		return;
	}

	if ( ! class_exists( AwsSearch::class ) ) {
		return;
	}

	$dependency_tree->add_dependency( new AwsSearch() );
}

add_action( 'divi_module_library_modules_dependency_tree', __NAMESPACE__ . '\\aws_pro_divi5_register_modules' );
