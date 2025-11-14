<?php
/**
 * Register Tema Story Block Patterns
 *
 * @package Placy
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Tema Story Chapter Pattern
 */
function placy_register_tema_story_patterns() {
    // Register pattern category
    register_block_pattern_category(
        'placy-tema-story',
        array(
            'label' => __( 'Tema Story', 'placy' ),
        )
    );

    // Register Chapter Pattern
    register_block_pattern(
        'placy/tema-story-chapter',
        array(
            'title'       => __( 'Tema Story Kapittel', 'placy' ),
            'description' => __( 'En ferdig mal for et tema story kapittel med heading, tekst og POI liste', 'placy' ),
            'categories'  => array( 'placy-tema-story' ),
            'keywords'    => array( 'tema', 'story', 'kapittel', 'chapter', 'poi' ),
            'content'     => '<!-- wp:placy/chapter-wrapper {"chapterId":"chapter-1"} -->
<!-- wp:heading {"level":2} -->
<h2>Kapittel Tittel</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Skriv kapittelets introduksjonstekst her. Dette er teksten som beskriver temaet for kapittelet og gir kontekst til POI-ene som følger.</p>
<!-- /wp:paragraph -->

<!-- wp:acf/poi-list /-->
<!-- /wp:placy/chapter-wrapper -->',
        )
    );

    // Register Complete Story Structure Pattern
    register_block_pattern(
        'placy/tema-story-structure',
        array(
            'title'       => __( 'Tema Story Struktur (3 kapitler)', 'placy' ),
            'description' => __( 'Komplett tema story struktur med 3 kapitler', 'placy' ),
            'categories'  => array( 'placy-tema-story' ),
            'keywords'    => array( 'tema', 'story', 'struktur', 'full' ),
            'content'     => '<!-- wp:placy/chapter-wrapper {"chapterId":"chapter-1"} -->
<!-- wp:heading {"level":2} -->
<h2>Kapittel 1: Tittel</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Kapittel 1 introduksjonstekst...</p>
<!-- /wp:paragraph -->

<!-- wp:acf/poi-list /-->
<!-- /wp:placy/chapter-wrapper -->

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:placy/chapter-wrapper {"chapterId":"chapter-2"} -->
<!-- wp:heading {"level":2} -->
<h2>Kapittel 2: Tittel</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Kapittel 2 introduksjonstekst...</p>
<!-- /wp:paragraph -->

<!-- wp:acf/poi-list /-->
<!-- /wp:placy/chapter-wrapper -->

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:placy/chapter-wrapper {"chapterId":"chapter-3"} -->
<!-- wp:heading {"level":2} -->
<h2>Kapittel 3: Tittel</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Kapittel 3 introduksjonstekst...</p>
<!-- /wp:paragraph -->

<!-- wp:acf/poi-list /-->
<!-- /wp:placy/chapter-wrapper -->',
        )
    );
}
add_action( 'init', 'placy_register_tema_story_patterns' );
