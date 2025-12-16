<?php

/**

 * The template for displaying all pages.

 *

 * This is the template that displays all pages by default.

 * Please note that this is the WordPress construct of pages

 * and that other 'pages' on your WordPress site will use a

 * different template.

 *

 * @package GeneratePress

 * Template Name:Slippy Tool

 */



if ( ! defined( 'ABSPATH' ) ) {

	exit; // Exit if accessed directly.

}



global $wp_query;



$page_title = "Slippy Check - Road Conditions Reporter";



add_filter( 'wpseo_canonical', '__return_false' );

remove_action( 'template_redirect', 'redirect_canonical' );





$paragraph_text = "";





add_action('wp_enqueue_scripts', 'load_bootstrap_files',1005 );



function load_bootstrap_files() {

    wp_register_style('slippy-css', get_site_url() . '/css/style.css',array(), false,  'all');

	wp_enqueue_style('slippy-css');

	//'Newspaper','Newspaper-child','td-theme'

    wp_enqueue_style('leaflet-css',  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',array(), false, 'all');

	wp_enqueue_style('leaflet-css');

	wp_register_script( 'leaflet-js',"https://unpkg.com/leaflet@1.9.4/dist/leaflet.js", array('jquery'), '1.0', true );

	wp_enqueue_script('leaflet-js');

	wp_register_script( 'slippy-js',get_site_url() . '/js/app.js', array('jquery'), '1.0', true );

	wp_enqueue_script('slippy-js');



}



function insert_html_in_header() {

echo '

<link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="preconnect" href="//gstatic.com/" crossorigin>

<link rel="preconnect" href="//www.gstatic.com/" crossorigin>

<link rel="preconnect" href="//www.gstatic.com/" crossorigin>

<link rel="preconnect" href="//www.google.com/" crossorigin>

<link rel="preconnect" href="//google.com/" crossorigin>

<link rel="preconnect" href="//challenges.cloudflare.com/" crossorigin>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>';





}

add_action( 'wp_head', 'insert_html_in_header' );







function my_page_title() {

    global $page_title;

    return $page_title;

}

add_filter( 'pre_get_document_title', 'my_page_title' , 50);

add_filter( 'seopress_ai_openai_meta_title', 'my_page_title' , 10,2);

add_filter( 'wpseo_title', 'my_page_title', 10, 1 );

add_filter( 'wpseo_opengraph_title', 'my_page_title', 10, 1 );

add_filter( 'seopress_titles_title', 'my_page_title', 10, 1 );

$meta_description = "Let your neighbors know about slippery road and sidewalk conditions.";





/*

function prefix_filter_canonical_example( $canonical ) {

	global $bpm;



    $url = "https://playthetunes.com/metronome/";

		if(isset($genre)) $url .= $bpm."-bpm/";

		return '<link rel="canonical" href="'.$url.'" />';

}



add_filter('seopress_titles_canonical','prefix_filter_canonical_example');



*/





add_filter('wpseo_metadesc','custom_meta');

add_filter('seopress_ai_openai_meta_desc', 'custom_meta', 10, 2);

add_filter('seopress_titles_desc', 'custom_meta', 10, 2);

function custom_meta( $desc ){



    global $meta_description;



    return $meta_description;

}

add_filter('body_class', function ($classes) {

    $classes[] = 'slippy-tool-body';

    return $classes;

});

get_header();



?>



	<div id="primary" <?php generate_do_element_classes( 'content' ); ?>>

		<main id="main" <?php generate_do_element_classes( 'main' ); ?>>



    <div class="container ">



        <div class="search-bar">

            <input type="text" id="location-input" placeholder="Enter zip code or address">

            <button id="search-btn">Search</button>

            <button id="use-location-btn">Use My Location</button>

        </div>



        <div class="map-report-wrapper">

            <div class="map-container">

                <div id="map"></div>

                <div class="map-legend">

                    <h3>Map Key</h3>

                    <div class="legend-section">

                        <h4>Condition Types</h4>

                        <div class="legend-item">

                            <span class="legend-marker circle" style="background-color: #ef4444;"></span>

                            <span>🧊 Ice</span>

                        </div>

                        <div class="legend-item">

                            <span class="legend-marker circle" style="background-color: #3b82f6;"></span>

                            <span>🌨️ Slush</span>

                        </div>

                        <div class="legend-item">

                            <span class="legend-marker circle" style="background-color: #8b5cf6;"></span>

                            <span>❄️ Snow</span>

                        </div>

                        <div class="legend-item">

                            <span class="legend-marker circle" style="background-color: #f97316;"></span>

                            <span>💧 Water</span>

                        </div>

                    </div>

                    <div class="legend-section">

                        <h4>Location Types</h4>

                        <div class="legend-item">

                            <span class="legend-marker circle" style="background-color: #667eea;"></span>

                            <span>🛣️ Road</span>

                        </div>

                        <div class="legend-item">

                            <span class="legend-marker square" style="background-color: #667eea;"></span>

                            <span>🚶 Sidewalk</span>

                        </div>

                    </div>

                </div>

            </div>



            <div class="report-panel">

                <h2>Report a Condition</h2>

                <form id="report-form">

                    <div class="form-group">

                        <label>Location Type:</label>

                        <div class="location-buttons">

                            <button type="button" class="location-btn" data-location="road">🛣️ Road</button>

                            <button type="button" class="location-btn" data-location="sidewalk">🚶 Sidewalk</button>

                        </div>

                        <input type="hidden" id="selected-location" value="road">

                    </div>



                    <div class="form-group">

                        <label>Condition Type:</label>

                        <div class="condition-buttons">

                            <button type="button" class="condition-btn" data-condition="ice">🧊 Ice</button>

                            <button type="button" class="condition-btn" data-condition="slush">🌨️ Slush</button>

                            <button type="button" class="condition-btn" data-condition="snow">❄️ Snow</button>

                            <button type="button" class="condition-btn" data-condition="water">💧 Water</button>

                        </div>

                        <input type="hidden" id="selected-condition" required>

                    </div>



                    <div class="form-group">

                        <label for="submitter-name">Your Name (Optional, max 25 characters):</label>

                        <input type="text" id="submitter-name" placeholder="Enter your name" maxlength="25">

                    </div>



                    <div class="form-group">

                        <label>Click on the map to place a pin, then submit:</label>

                        <!-- Turnstile CAPTCHA (optional for development) -->

                        <div id="turnstile-widget" style="display: none;"></div>

                    </div>



                    <button type="submit" id="submit-btn" disabled>Submit Report</button>

                </form>

            </div>

        </div>



        <div class="reports-list">

            <h2>Recent Reports</h2>

            <div id="reports-container">

                <p class="loading">Loading reports...</p>

            </div>

        </div>



        <footer class="legal-disclaimer">

            <p><strong>Legal Disclaimer:</strong> SlippyCheck recommends that you assess risks when walking, driving, biking, scooting, or using any other mode of transportation based on all information available to you, including but not limited to weather conditions, local advisories, and your own observations. The information provided on this platform is user-generated and may not be complete, accurate, or current. SlippyCheck does not guarantee the accuracy, reliability, or completeness of any information posted by users. We do not have perfect information about the status of roads and sidewalks you may travel on. Use of this service is at your own risk. SlippyCheck, its operators, and contributors are not liable for any injuries, damages, or losses that may result from your use of or reliance on information provided through this platform. Always exercise caution and use your best judgment when making travel decisions.</p>

        </footer>

        

        <!-- Comment Modal -->

        <div id="comment-modal" class="modal">

            <div class="modal-content">

                <span class="close-modal">&times;</span>

                <h2>Comments</h2>

                <div id="comments-list"></div>

                <div class="comment-form-container">

                    <textarea id="comment-input" placeholder="Add an anonymous comment..." maxlength="500"></textarea>

                    <div class="comment-char-count"><span id="char-count">0</span>/500</div>

                    <div id="comment-turnstile-widget"></div>

                    <button id="submit-comment-btn" disabled>Post Comment</button>

                </div>

            </div>

        </div>

        </div>

    </div>











                    <?php

			/*if ( generate_has_default_loop() ) {

				while ( have_posts() ) :



					the_post();



					generate_do_template_part( 'page' );



				endwhile;

			}*/



			/**

			 * generate_after_main_content hook.

			 *

			 * @since 0.1

			 */

			do_action( 'generate_after_main_content' );

				?>





		</main>

	</div>



	<?php

	/**

	 * generate_after_primary_content_area hook.

	 *

	 * @since 2.0

	 */

	do_action( 'generate_after_primary_content_area' );



	generate_construct_sidebars();



	get_footer();

