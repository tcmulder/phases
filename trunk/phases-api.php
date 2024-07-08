<?php
/**
 * Debug
 */
add_action( 'wp_footer', 'debug' );
function debug() {
	$query = get_posts( array(
		'posts_per_page' => 999,
		'post_type' => array( 'post', 'page' ),
		'tax_query' => array(
			array(
				'taxonomy' => 'phases',
				'field' => 'slug',
				'terms' => 'test-1',

			),
		),
		// 'fields' => 'ids',
	) );
	$D=array_map(function($p){
		return array( $p->post_title, $p->ID );
	}, $query);$D=printf("<PRE>%s</PRE>",$D?print_r($D,1):'✋');

	$terms = get_terms( array( 
		'taxonomy' => 'phases',
		'hide_empty' => 0,
		'fields' => 'ids',
	) );
	$D=$terms;$D=printf("<PRE>%s</PRE>",$D?print_r($D,1):'✋');
}
