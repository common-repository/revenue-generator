<?php
/**
 * This file runs if someone clicks the delete link for deactivated Revenue Generator plugin in WordPress admin area.
 *
 * 1. Delete options data used in plugin.
 * 2. Delete meta data added on supported posts / pages and categories for internal use.
 * 3. Delete data of custom post type created by the plugin.
 *
 * @package revenue-generator
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

/**
 * 1. Delete options data used in plugin.
 *
 * All common options used by plugin should be added here for deletion.
 */
$lp_rg_global_options = [
	'lp_rg_version',
	'lp_rg_global_options',
	'lp_rg_merchant_credentials',
	'lp_rg_settings_options',
	'lp_rgv2_global_options',
	'lp_rgv2_just_onboarded',
	'lp_rgv2_credentials',
	'lp_rgv2_settings_options',
	'lp_rgv2_key',
];

foreach ( $lp_rg_global_options as $lp_rg_global_option ) {
	// Delete plugin option.
	delete_option( $lp_rg_global_option );
}

/**
 * 2. Delete meta data added on supported posts / pages and categories for internal use.
 *
 * Remove paywall identification data on posts and pages and categories.
 */
delete_post_meta_by_key( '_rg_has_paywall' );

// Get all terms having meta key _rg_has_paywall.
$args = [
	'hide_empty' => false,
	'meta_query' => [
		[
			'key'     => '_rg_has_paywall',
			'compare' => '=',
		],
	],
];

$rg_terms = get_terms( 'category', $args );
if ( ! empty( $rg_terms ) ) {
	// Delete all term meta added by Revenue Generator.
	foreach ( $rg_terms as $rg_term ) {
		delete_term_meta( $rg_term->term_id, '_rg_has_paywall' );
	}
}

/**
 * 3.Delete data of custom post type created by the plugin.
 *
 * Get all paywalls, passes and subscriptions for deletion.
 */
$paywall_args = [
	'post_type'      => [ 'rg_paywall' ],
	'posts_per_page' => 100,
	'no_found_rows'  => true,
	'post_status'    => [ 'any' ],
];

$paywall_query = new WP_Query( $paywall_args );

// Don't keep paywall info upon deletion.
while ( $paywall_query->have_posts() ) {
	// Get custom post data created by plugin and delete it.
	$paywall_query->the_post();
	$rg_paywall_id = get_the_ID();
	wp_delete_post( $rg_paywall_id, true );
}

wp_reset_postdata();

/**
 * 4.Get all Contributions for deletion.
 */
$contribution_args = [
	'post_type'      => [ 'rg_contribution' ],
	'posts_per_page' => 100,
	'no_found_rows'  => true,
	'post_status'    => [ 'any' ],
];

$contribution_query = new WP_Query( $contribution_args );

// Don't keep contribution info upon deletion.
while ( $contribution_query->have_posts() ) {
	// Get custom post data created by plugin and delete it.
	$contribution_query->the_post();
	$rg_contribution_id = get_the_ID();
	wp_delete_post( $rg_contribution_id, true );
}

wp_reset_postdata();

// Get all active Time Passes and Subscriptions and set to draft, make sure to check user access based on these options.
$pass_sub_args = [
	'post_type'      => [ 'rg_pass', 'rg_subscription' ],
	'posts_per_page' => 100,
	'no_found_rows'  => true,
	'post_status'    => [ 'any' ],
];

$pass_sub_query = new WP_Query( $pass_sub_args );

while ( $pass_sub_query->have_posts() ) {
	// Get custom post data created by plugin and delete it.
	$pass_sub_query->the_post();
	$rg_pass_sub_id = get_the_ID();
	wp_delete_post( $rg_pass_sub_id, true );
}

wp_reset_postdata();
