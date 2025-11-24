<?php
/**
 * Bulk Update Descriptions for Google Points
 * 
 * One-time script to update editorial_text for 26 Google Points
 * Run once: php bulk-update-descriptions.php
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once '/Applications/MAMP/htdocs/placy/wp-load.php';

// Check if ACF is available
if (!function_exists('update_field')) {
    die("Error: Advanced Custom Fields is not active.\n");
}

// Descriptions data
$descriptions = [
    "ChIJJ0TgSJkxbUYR6lHWdmGiFrY" => [
        "name" => "AiSuma Restaurant",
        "description" => "Middelhavsinspirert grill og bistro med utsikt over Nidelva. Fokus på steak, sjømat og lokale råvarer i lune omgivelser."
    ],
    "ChIJt48wUgAxbUYRQD9ZY-s23d0" => [
        "name" => "Awake",
        "description" => "Inkluderende kaffebar drevet av 22B Kontaktsenter. God kaffe, smoothie bowls og hjemmebakt i moderne lokaler ved Trondheim Torg."
    ],
    "ChIJH2iWMJsxbUYRE8sFPMTIAEU" => [
        "name" => "Bula Neobistro",
        "description" => "Top Chef-vinner Reneé Fagerhøis kreative lekeplass. Overraskelsesmeny der junkfood møter fine dining i rock'n'roll-atmosfære."
    ],
    "ChIJs-DHJJ8xbUYRslPZTW18HJo" => [
        "name" => "Café Løkka",
        "description" => "Avslappet kafé på Brattøra med kaffe, lettere retter og uteservering. Populært møtested nær vannet."
    ],
    "ChIJ6wEGKboxbUYR1notF-LroXA" => [
        "name" => "Ciabatta Trondheim og Dråpen",
        "description" => "Tradisjonsrikt bakeri og kafé med ferske bakervarer, smørbrød og kaffe. Lokalt samlingspunkt i Kjøpmannsgata."
    ],
    "ChIJu4I8x5kxbUYR_tiYBcnkTUw" => [
        "name" => "Dromedar Kaffebar, Nordre",
        "description" => "Trondheims første kaffebar fra 1997, nå byens mest omsettende. Prisbelønte baristaer og håndbrygget kvalitetskaffe."
    ],
    "ChIJCbmu26ExbUYR4Ew-k7zQvl4" => [
        "name" => "Espresso House",
        "description" => "Skandinavisk kaffekjede med bredt utvalg av kaffedrikker og fristelser. Sentralt i Nordre gate."
    ],
    "ChIJa5PTYPQxbUYRMhl1-tTmyJs" => [
        "name" => "Godt Brød Solsiden",
        "description" => "Økologisk håndverksbakeri med ferske surdeigsbrød, kanelboller og påsmurte måltider. Bærekraft i hver bit."
    ],
    "ChIJD6jnjpsxbUYRnbiHT9RoYmk" => [
        "name" => "Godt Brød Thomas Angells gate",
        "description" => "Økologisk bakeri med lokale trønderske spesialiteter som aniskringler. Fersk bakst fra morgen til kveld."
    ],
    "ChIJhw9AeCYxbUYROiOq62E3Q4w" => [
        "name" => "Gola gelato & cafe",
        "description" => "Argentinsk-drevet iskremparadis med hjemmelaget gelato og autentiske empanadas. Byens beste is, laget med kjærlighet."
    ],
    "ChIJ_YwyOpsxbUYRWowb_RaKkf8" => [
        "name" => "Gubalari",
        "description" => "Autentisk norsk restaurant i Kjøpmannsgata med fokus på ferske, lokale råvarer og tradisjonelle smaker."
    ],
    "ChIJ10ho6psxbUYR7B0NX1iPIVs" => [
        "name" => "Jonathan Grill",
        "description" => "Britannia Hotels steakhouse med japanske bordgriller. Førsteklasses biff og sjømat i elegant atmosfære."
    ],
    "ChIJRblqOl8xbUYRS2hAYTe-4Vw" => [
        "name" => "Jordbærpikene Solsiden",
        "description" => "Koselig frokost- og lunsjkafé på Solsiden med hjemmebakt, salater og smoothies. Perfekt for en avslappet start på dagen."
    ],
    "ChIJy7Fqx5kxbUYRWIe2dgPwqyU" => [
        "name" => "Kalas & Canasta",
        "description" => "Sjarmerende restaurant på Bakklandet med sesongbasert meny. Lokale råvarer serveres i et intimt, hjemmekoselig miljø."
    ],
    "ChIJZTrmbJsxbUYR9gbf6Pm2AnQ" => [
        "name" => "Le Bistro",
        "description" => "Et lite stykke Frankrike midt i Munkegata. Klassisk bistro med førsteklasses lokale råvarer og utmerket vinkart."
    ],
    "ChIJlQAgdEUxbUYRnRN0kzX3Lck" => [
        "name" => "MANA Restaurant",
        "description" => "Uformell fine dining på Sluppen med fokus på sesongbaserte nordiske smaker og lokale råvarer."
    ],
    "ChIJzWWN3HYxbUYR3SHgHFgLD54" => [
        "name" => "Nabolaget Bagelri",
        "description" => "Hjemmelaget bagelparadis på Bakklandet. Ferskt bakt hver dag med kreative fyll – byens mest autentiske bagels."
    ],
    "ChIJ10ho6psxbUYRVaA4ZS-7h_4" => [
        "name" => "Palmehaven",
        "description" => "Britannias ikoniske spisesal fra 1918. Klassisk afternoon tea med levende pianospill i spektakulære omgivelser."
    ],
    "ChIJD_WLcZwxbUYRfOoISvT5WAc" => [
        "name" => "Restaurant Two Rooms and Kitchen",
        "description" => "Trondheims klassiske europeiske restaurant siden 2005. Mediterran-inspirert meny med lokale Trøndelag-råvarer."
    ],
    "ChIJE3Oml2ExbUYRffWXHKc80DQ" => [
        "name" => "Rive Gauche",
        "description" => "Fransk-inspirert restaurant på Øvre Bakklandet med romantisk atmosfære og utsikt over elven."
    ],
    "ChIJ2_w8aLAxbUYRD6HlfCSRPYQ" => [
        "name" => "Sabi Sushi Moholt",
        "description" => "Populær sushikjede med fersk fisk og japanske retter. Lokalt favorittsted for kvalitetssushi på Moholt."
    ],
    "ChIJw6111psxbUYRtljsA7ciqSM" => [
        "name" => "SELLANRAA Bok & Bar",
        "description" => "Kafé, bar og bokhandel i Litteraturhuset. Lokale sesongråvarer, prisbelønt kaffe og bøker fra gulv til tak."
    ],
    "ChIJab_zEdAxbUYRiMCFnG3IS34" => [
        "name" => "Speilsalen",
        "description" => "Britannias Michelin-restaurant med speildekket sal og kaviarbar. Norges fineste råvarer i en kulinarisk opplevelse."
    ],
    "ChIJSY81O1wxbUYRGf59dfwcssw" => [
        "name" => "Tollbua",
        "description" => "Christopher Davidsens gourmetbistro i gammel tollbod fra 1910. Lokale råvarer med internasjonale smaker, anbefalt i Michelin Guide."
    ],
    "ChIJNf1E-pwxbUYRqQ37umV_TYI" => [
        "name" => "Troll Restaurant",
        "description" => "Sjømatrestaurant og eventlokale ved Fosenkaia. Norsk kystmat i rustikke omgivelser med havneutsikt."
    ],
    "ChIJFSxqCp8xbUYRxlNF2ntkSWg" => [
        "name" => "Una pizzeria e bar",
        "description" => "Ekte italiensk pizzeria på Solsiden siden 2014. Vedfyrt pizza, pasta og avslappet stemning ved vannet."
    ]
];

echo "========================================\n";
echo "  Bulk Update Descriptions\n";
echo "========================================\n\n";

echo "Total descriptions to process: " . count($descriptions) . "\n\n";

$updated = 0;
$failed = 0;
$not_found = 0;

foreach ($descriptions as $place_id => $data) {
    // Find post by place_id
    $posts = get_posts([
        'post_type' => 'placy_google_point',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => 'google_place_id',
                'value' => $place_id,
                'compare' => '='
            ]
        ]
    ]);
    
    if (empty($posts)) {
        echo "✗ NOT FOUND: {$data['name']} (Place ID: {$place_id})\n";
        $not_found++;
        continue;
    }
    
    $post_id = $posts[0]->ID;
    
    // Update editorial_text field
    $result = update_field('editorial_text', $data['description'], $post_id);
    
    if ($result) {
        echo "✓ UPDATED: {$data['name']} (Post ID: {$post_id})\n";
        $updated++;
    } else {
        echo "✗ FAILED: {$data['name']} (Post ID: {$post_id})\n";
        $failed++;
    }
}

echo "\n========================================\n";
echo "  Summary\n";
echo "========================================\n\n";

echo "Total:     " . count($descriptions) . "\n";
echo "Updated:   {$updated} ✓\n";
echo "Failed:    {$failed}\n";
echo "Not found: {$not_found}\n\n";

if ($updated === count($descriptions)) {
    echo "✓ SUCCESS: All descriptions updated!\n";
} elseif ($updated > 0) {
    echo "⚠ PARTIAL: {$updated} of " . count($descriptions) . " updated.\n";
} else {
    echo "✗ ERROR: No descriptions were updated.\n";
}

echo "\n========================================\n";
