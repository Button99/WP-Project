<?php
/**
 * Dulcet Theme Customizer.
 *
 * @package Dulcet
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function dulcet_customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	/*------------------------------------------------------------------------*/
	/*  Section: Theme Options
	/*------------------------------------------------------------------------*/

	$wp_customize->add_panel( 'dulcet_theme_options_panel' ,
			array(
				'priority'        => 30,
				'title'           => esc_html__( 'Theme Options', 'dulcet' ),
				'description'     => ''
			)
		);
		// section

		// index layout
		$wp_customize->add_section( 'front_page_layout' ,
			array(
				'priority'    => 3,
				'title'       => esc_html__( 'Layout', 'dulcet' ),
				'description' => '',
				'panel'       => 'dulcet_theme_options_panel',
			)
		);
		$wp_customize->add_setting( 'frontpage_layout',
			array(
				'sanitize_callback' => 'dulcet_sanitize_select',
				'default'           => 0,
			)
		);
		$wp_customize->add_control( 'frontpage_layout',
			array(
				'type'        => 'radio',
				'label'       => esc_html__('Frontpage Layout', 'dulcet'),
				'section'     => 'front_page_layout',
				'choices' => array(
					1  => esc_html__('Left Sidebar', 'dulcet'),
					0  => esc_html__('Right Sidebar', 'dulcet'),
				)
			)
		);


		$wp_customize->add_section( 'icons_color' ,
			array(
				'priority'    => 3,
				'title'       => esc_html__( 'Post Format Color', 'dulcet' ),
				'description' => '',
				'panel'       => 'dulcet_theme_options_panel',
			)
		);

			// post format color setting
			$wp_customize->add_setting( 'hide_post_format' , array(
				'default'     => 1,
				'sanitize_callback'	=> 'dulcet_sanitize_checkbox',
			) );

			$wp_customize->add_control( 'hide_post_format',
				array(
					'label'         => esc_html__( 'Hide Post Format Icons', 'dulcet' ),
					'section'    	=> 'icons_color',
					'type'    	 	=> 'checkbox',
					'description'   => esc_html__('Check this box to disable post format icon.', 'dulcet')
				)
			);

			$wp_customize->add_setting( 'standard_icon' , array(
				'default'     => '#000',
				'sanitize_callback'	=> 'dulcet_sanitize_hex_color',
			) );

			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'standard_icon', array(
				'label'      => esc_html__( 'Standard', 'dulcet' ),
				'section'    => 'icons_color',
				'settings'   => 'standard_icon',
			) ) );

			$wp_customize->add_setting( 'aside_icon' , array(
				'default'     => '#D56E6F',
				'sanitize_callback'	=> 'dulcet_sanitize_hex_color',
			) );

			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'aside_icon', array(
				'label'        => esc_html__( 'Aside', 'dulcet' ),
				'section'    => 'icons_color',
				'settings'   => 'aside_icon',
			) ) );

			$wp_customize->add_setting( 'image_icon' , array(
				'default'     => '#7baa74',
				'sanitize_callback'	=> 'dulcet_sanitize_hex_color',
			) );

			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'image_icon', array(
				'label'        => esc_html__( 'Image', 'dulcet' ),
				'section'    => 'icons_color',
				'settings'   => 'image_icon',
			) ) );
			$wp_customize->add_setting( 'video_icon' , array(
				'default'     => '#ff6600',
				'sanitize_callback'	=> 'dulcet_sanitize_hex_color',
			) );

			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'video_icon', array(
				'label'        => esc_html__( 'Video', 'dulcet' ),
				'section'    => 'icons_color',
				'settings'   => 'video_icon',
			) ) );

			$wp_customize->add_setting( 'quote_icon' , array(
				'default'     => '#9e9e9e',
				'sanitize_callback'	=> 'dulcet_sanitize_hex_color',
			) );

			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'quote_icon', array(
				'label'        => esc_html__( 'Quote', 'dulcet' ),
				'section'    => 'icons_color',
				'settings'   => 'quote_icon',
			) ) );

			$wp_customize->add_setting( 'link_icon' , array(
				'default'     => '#FF0006',
				'sanitize_callback'	=> 'dulcet_sanitize_hex_color',
			) );

			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'link_icon', array(
				'label'        => esc_html__( 'Link', 'dulcet' ),
				'section'    => 'icons_color',
				'settings'   => 'link_icon',
			) ) );

			$wp_customize->add_setting( 'chat_icon' , array(
				'default'     => '#24CEFF',
				'sanitize_callback'	=> 'dulcet_sanitize_hex_color',
			) );

			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'chat_icon', array(
				'label'        => esc_html__( 'Chat', 'dulcet' ),
				'section'    => 'icons_color',
				'settings'   => 'chat_icon',
			) ) );

			$wp_customize->add_setting( 'audio_icon' , array(
				'default'     => '#ba7cc0',
				'sanitize_callback'	=> 'dulcet_sanitize_hex_color',
			) );

			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'audio_icon', array(
				'label'        => esc_html__( 'Audio', 'dulcet' ),
				'section'    => 'icons_color',
				'settings'   => 'audio_icon',
			) ) );

			$wp_customize->add_setting( 'gallery_icon' , array(
				'default'     => '#ff9000',
				'sanitize_callback'	=> 'dulcet_sanitize_hex_color',
			) );

			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'gallery_icon', array(
				'label'        => esc_html__( 'Gallery', 'dulcet' ),
				'section'    => 'icons_color',
				'settings'   => 'gallery_icon',
			) ) );



			/* Page Footer
		    ----------------------------------------------------------------------*/
		    $wp_customize->add_section( 'page_footer_settings' ,
		        array(
		            'priority'    => 10,
		            'title'       => esc_html__( 'Footer', 'dulcet' ),
		            'description' => '',
		            'panel'       => 'dulcet_theme_options_panel',
		            //'active_callback'   => 'is_page', // function
		        )
		    );

		        // Features columns
		        $wp_customize->add_setting( 'footer_layout',
		            array(
		                'sanitize_callback' => 'dulcet_sanitize_number',
		                'default'           => 3,
		            )
		        );
		        $wp_customize->add_control( 'footer_layout',
		            array(
		                'type'        => 'select',
		                'label'       => esc_html__('Footer Layout', 'dulcet'),
		                'section'     => 'page_footer_settings',
		                'description' => esc_html__('Number footer columns to display.', 'dulcet'),
		                'choices' => array(
		                    4  => 4,
		                    3  => 3,
		                    2  => 2,
		                    1  => 1,
		                    0  => esc_html__('Disable footer widgets', 'dulcet'),
		                )
		            )
		        );


		        // Footer widgets background
		        $wp_customize->add_setting( 'footer_widgets_bg',
		            array(
		                'sanitize_callback' => 'dulcet_sanitize_hex_color',
		                'default'           => '',
		            )
		        );
		        $wp_customize->add_control( new WP_Customize_Color_Control(
		                $wp_customize,
		                'footer_widgets_bg',
		                array(
		                    'label'       => esc_html__('Footer widgets background color', 'dulcet'),
		                    'section'     => 'page_footer_settings',
		                )
		            )
		        );

		        // Footer widgets text color
		        $wp_customize->add_setting( 'footer_widgets_color',
		            array(
		                'sanitize_callback' => 'dulcet_sanitize_hex_color',
		                'default'           => '',
		            )
		        );
		        $wp_customize->add_control( new WP_Customize_Color_Control(
		                $wp_customize,
		                'footer_widgets_color',
		                array(
		                    'label'       => esc_html__('Footer widgets text color', 'dulcet'),
		                    'section'     => 'page_footer_settings',
		                )
		            )
		        );


				// Menu color
		        $wp_customize->add_setting( 'menu_color',
		            array(
		                'sanitize_callback' => 'dulcet_sanitize_hex_color',
		                'default'           => '#898989',
		            )
		        );
		        $wp_customize->add_control( new WP_Customize_Color_Control(
		                $wp_customize,
		                'menu_color',
		                array(
		                    'label'       => esc_html__('Menu link color', 'dulcet'),
		                    'section'     => 'colors',
		                )
		            )
		        );

				$wp_customize->add_setting( 'menu_hover_color',
		            array(
		                'sanitize_callback' => 'dulcet_sanitize_hex_color',
		                'default'           => '#000',
		            )
		        );
		        $wp_customize->add_control( new WP_Customize_Color_Control(
		                $wp_customize,
		                'menu_hover_color',
		                array(
		                    'label'       => esc_html__('Menu active/hover color', 'dulcet'),
		                    'section'     => 'colors',
		                )
		            )
		        );

				// Social color
				$wp_customize->add_setting( 'social_color',
		            array(
		                'sanitize_callback' => 'dulcet_sanitize_hex_color',
		                'default'           => '#898989',
		            )
		        );
		        $wp_customize->add_control( new WP_Customize_Color_Control(
		                $wp_customize,
		                'social_color',
		                array(
		                    'label'       => esc_html__('Header social color', 'dulcet'),
		                    'section'     => 'colors',
		                )
		            )
		        );

				$wp_customize->add_setting( 'social_hover_color',
		            array(
		                'sanitize_callback' => 'dulcet_sanitize_hex_color',
		                'default'           => '#000',
		            )
		        );
		        $wp_customize->add_control( new WP_Customize_Color_Control(
		                $wp_customize,
		                'social_hover_color',
		                array(
		                    'label'       => esc_html__('Header social hover color', 'dulcet'),
		                    'section'     => 'colors',
		                )
		            )
		        );




}
add_action( 'customize_register', 'dulcet_customize_register' );

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function dulcet_customize_preview_js() {
	wp_enqueue_script( 'dulcet_customizer', get_template_directory_uri() . '/assets/js/customizer.js', array( 'customize-preview' ), '20151215', true );
}
add_action( 'customize_preview_init', 'dulcet_customize_preview_js' );



/*------------------------------------------------------------------------*/
/*  Dulcet Sanitize Functions.
/*------------------------------------------------------------------------*/

function dulcet_sanitize_file_url( $file_url ) {
	$output = '';
	$filetype = wp_check_filetype( $file_url );
	if ( $filetype["ext"] ) {
		$output = esc_url( $file_url );
	}
	return $output;
}

function dulcet_sanitize_number( $input ) {
    return balanceTags( $input );
}

function dulcet_sanitize_select( $input, $setting ) {
	$input = sanitize_key( $input );
	$choices = $setting->manager->get_control( $setting->id )->choices;
	return ( array_key_exists( $input, $choices ) ? $input : $setting->default );
}

function dulcet_sanitize_hex_color( $color ) {
	if ( $color === '' ) {
		return '';
	}
	if ( preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
		return $color;
	}
	return null;
}
function dulcet_sanitize_checkbox( $checked ) {
    return ( ( isset( $checked ) && true == $checked ) ? true : false );
}

function dulcet_sanitize_text( $string ) {
	return wp_kses_post( balanceTags( $string ) );
}
