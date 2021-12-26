<?php
/**
 * This is to plugin help page.
 *
 * @package location-weather
 */

namespace ShapedPlugin\Weather\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * The help page handler class.
 */
class Splw_Help {

	/**
	 * The instance of the class.
	 *
	 * @var object
	 */
	private static $_instance;

	/**
	 * The Constructor of the class.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'help_page' ), 100 );
	}

	/**
	 * The instance function of the class.
	 *
	 * @return object
	 */
	public static function getInstance() {
		if ( ! self::$_instance ) {
			self::$_instance = new Splw_Help();
		}

		return self::$_instance;
	}

	/**
	 * Add SubMenu Page
	 */
	public function help_page() {
		add_submenu_page( 'edit.php?post_type=location_weather', __( 'Location Weather Help', 'location-weather' ), __( 'Help', 'location-weather' ), 'manage_options', 'splw_help', array( $this, 'help_page_callback' ) );
	}

	/**
	 * Help Page Callback
	 */
	public function help_page_callback() {
		wp_enqueue_style( 'splw__admin-help', LOCATION_WEATHER_ASSETS . '/css/help-page.min.css', array(), LOCATION_WEATHER_VERSION );
		$add_shortcode_link = admin_url( 'post-new.php?post_type=location_weather' );
		?>
		<div class="sp-location-weather-help">
				<!-- Header section start -->
				<section class="splw__help header">
					<div class="header-area">
						<div class="container">
							<div class="header-logo">
								<img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/location-weather-logo.svg' ); ?>" alt="">
								<span><?php echo esc_html( LOCATION_WEATHER_VERSION ); ?></span>
							</div>
							<div class="header-content">
								<p>Thank you for installing Location Weather plugin! This video will help you get started with the plugin.</p>
							</div>
						</div>
					</div>
					<div class="video-area">
						<iframe width="560" height="315" src="https://www.youtube.com/embed/BT2ocvKV1uA" frameborder="0" title="location-weather" allowfullscreen=""></iframe>
					</div>
					<div class="content-area">
						<div class="container">
							<div class="content-button">
								<a href="<?php echo esc_url( $add_shortcode_link ); ?>">Start Managing Weather</a>
								<a href="https://docs.shapedplugin.com/docs/location-weather/overview/" target="_blank">Read Documentation</a>
							</div>
						</div>
					</div>
				</section>
				<!-- Header section end -->

				<!-- Upgrade section start -->
				<section class="splw__help upgrade">
					<div class="upgrade-area">
					<div class="upgrade-img"> 
					<img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/lw-icon.svg' ); ?>" alt="">
					</div>
						<h2>Upgrade To Unleash the Power of Location Weather Pro</h2> 
						<p>Get the most out of Location Weather by upgrading to unlock all of its powerful features. With Location Weather Pro, you can unlock amazing features like:</p>
					</div>
					<div class="upgrade-info">
						<div class="container">
							<div class="row">
								<div class="col-lg-6">
									<ul class="upgrade-list">
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">
										Fully responsive & mobile friendly.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Display weather for specific & visitors(auto) location.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Display weather by 4 location types: City name, City ID, ZIP, Coordinates(latitude & longitude).</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">16 days weather forecast with temperature, precipitation, wind velocity, humidity, pressure, and cloud Information.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Multiple weather widgets and forecasts on the same page.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Custom location name (override location name).</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Temperature unit (metric-°C, imperial-°F, and auto).</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Pressure unit (mb, kPa, inHg, psi, mmHg/Torr, kgf/cm, etc.).</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Wind speed unit (mph, m/s, kms, kts, etc.).</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Show/hide date and time format.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Show/hide weather condition icon.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Show/hide temperature and it's scale.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Display the highest and lowest temperature.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Real feel option.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Show/hide Weather description(conditions).</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Show/hide pressure, humidity, wind, etc.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Change weather icon color.</li>	
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Show/hide precipitation, wind gusts, UV Index, etc.</li>
									</ul>
								</div>
								<div class="col-lg-6">
									<ul class="upgrade-list">					
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Show/hide precipitation, wind gusts, UV Index, etc.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Show/hide visibility, sunrise, sunset, etc.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">HWeather attribution.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">4 Weather background types(solid, gradient, image, video)</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Widget text color, border, border-radius, etc.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Video and weather-based image overlay color options.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">HTML5 video(.mp4, .webm, .ogg) or YouTube background.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Weather-based different image backgrounds for 7 weather conditions.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Clean minimal background for flat UI design.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Unlimited colors for the background, weather icons, overlay, and text.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Advanced and easy API settings.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Widget ready.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Fully localized: 45 Languages supported.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Fully Translation ready with WPML, Polylang, Loco Translate, and more.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Multisite, RTL, and Accessibility ready.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">Compatible with themes and page builders like Elementor, Divi, WPBakery, and more.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt="">One To One customer support and regular updates.</li>
										<li><img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/checkmark.svg' ); ?>" alt=""><span>Not Happy? 100% No Questions Asked <a href="https://shapedplugin.com/refund-policy/" target="_blank">Refund Policy!</a></span></li>
									</ul>
								</div>
							</div>
						</div>
					</div>
					<div class="container">
						<div class="upgrade-pro">
							<div class="pro-content">
								<div class="pro-icon">
									<img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/lwp-icon.svg' ); ?>" alt="">
								</div>
								<div class="pro-text">
									<h2>Upgrade To Location Weather Pro, Best Value!</h2>
									<p>Boost sales of weather-dependent goods and services.</p>
								</div>
							</div>
							<div class="pro-btn">
								<a href="https://shapedplugin.com/plugin/location-weather-pro/?ref=1" target="_blank">Upgrade To Pro Now</a>
							</div>
						</div>
					</div>
				</section>
				<!-- Upgrade section end -->
				<!-- Testimonial section start -->
				<section class="splw__help testimonial">
					<div class="row">
						<div class="col-lg-6">
							<div class="testimonial-area">
								<div class="testimonial-content">
									<p>Awesome guys and Awesome plugin for geting different city weather updates easily. The plugin works great and is a simple weather app that does exactly what it is supposed to. Customer support is also top-notch and answered a question I had very quickly! Thanks.</p>
								</div>
								<div class="testimonial-info">
									<div class="img">
										<img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/Jeffrey-DiFilippo-min.jpeg' ); ?>" alt="">
									</div>
									<div class="info">
										<h3>Jeffrey DiFilippo</h3>
										<p>Web Developer</p>
										<div class="star">
										<i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="testimonial-area">
								<div class="testimonial-content">
									<p>The free trial worked great upon testing, but needed the advanced features and upgraded. At first the advanced features (i.e. auto location weather and multiple day forecast) did not work as advertised. The vendor’s support is great as they worked to get the problem resolved... I can recommend this plugin.</p>
								</div>
								<div class="testimonial-info">
									<div class="img">
										<img src="<?php echo esc_url( LOCATION_WEATHER_ASSETS . '/images/Dawie-Hanekom-min.png' ); ?>" alt="">
									</div>
									<div class="info">
										<h3>Dawie Hanekom</h3>
										<p>Managing Director, Newbe Marketing</p>
										<div class="star">
										<i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</section>
				<!-- Testimonial section end -->

		</div>
		<?php
	}

}
