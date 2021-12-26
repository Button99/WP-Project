<?php

/**
 *   The base class that all specialized workflows can override.
 */
class Lingotek_Workflow {

	private $allowed_html = array(
		'div'    => array(
			'id'    => array(),
			'style' => array(),
			'class' => array(),
		),
		'hr'     => array(
			'width' => array(),
			'size'  => array(),
		),
		'h2'     => array(
			'style' => array(),
		),
		'br'     => array(
			'class' => array(),
		),
		'b'      => array(),
		'p'      => array(
			'id'    => array(),
			'style' => array(),
		),
		'a'      => array(
			'id'    => array(),
			'href'  => array(),
			'style' => array(),
			'class' => array(),
		),
		'img'    => array(
			'id'    => array(),
			'src'   => array(),
			'style' => array(),
			'class' => array(),
		),
		'span'   => array(
			'id'               => array(),
			'style'            => array(),
			'class'            => array(),
			'ltk-data-tooltip' => array(),
		),
		'button' => array(
			'type'     => array(),
			'style'    => array(),
			'class'    => array(),
			'id'       => array(),
			'disabled' => array(),
		),
		'table'  => array(
			'class' => array(),
		),
		'tbody'  => array(
			'class' => array(),
		),
		'thead'  => array(
			'class' => array(),
			'id'    => array(),
		),
		'tr'     => array(
			'class' => array(),
			'id'    => array(),
		),
		'th'     => array(
			'id'    => array(),
			'class' => array(),
			'style' => array(),
		),
		'td'     => array(
			'class' => array(),
		),
		'ul'     => array(
			'class' => array(),
		),
		'li'     => array(),
		'input'  => array(
			'id'    => array(),
			'type'  => array(),
			'name'  => array(),
			'value' => array(),
			'class' => array(),
		),
	);

	/**
	 *   If a modal has already been written to the output buffer then we don't
	 *   want to write it again.
	 *
	 *   @var boolean
	 */
	protected static $info_modal_launched = false;

	/**
	 *   If a modal has already been written to the output buffer then we don't
	 *   want to write it again.
	 *
	 *   @var boolean
	 */
	protected static $post_modal_launched = false;


	/**
	 *   If a modal has already been written to the output buffer then we don't
	 *   want to write it again.
	 *
	 * @var boolean
	 */
	protected static $strings_modal_launched = false;

	/**
	 *   If a modal has already been written to the output buffer then we don't
	 *   want to write it again.
	 *
	 *   @var boolean
	 */
	protected static $terms_modal_launched = false;

	/**
	 *   If a modal has already been written to the output buffer then we don't
	 *   want to write it again.
	 *
	 *   @var boolean
	 */
	protected static $loading_modal_launched = false;

	/**
	 * This flag can be set to keep track of whether
	 * the save post action has already occured (for example, if multiple workflow objects are being instantiated and
	 * you don't want them to do the same thing twice.)
	 *
	 * @var boolean
	 */
	protected static $save_post_hook_executed = false;

	/**
	 * This flag acts the same as the save_post_hook_executed flag but for terms.
	 *
	 * @var boolean
	 */
	protected static $save_terms_hook_executed = false;

	/**
	 * The workflow_id of the given object.
	 *
	 * @var string
	 */
	protected $workflow_id;

	/**
	 * Constructor.
	 *
	 * @param string $workflow_id
	 */
	public function __construct( $workflow_id = null ) {
		$this->workflow_id = $workflow_id;
	}


	/**
	 *   Writes a modal to the output buffer. This is called when the Translation > Settings > Defaults pages is loaded.
	 *   The modal should contain information about the workflow.
	 *   NOTE: The workflow-defaults.js file is already loaded and set up to launch this modal. The workflow_id embedded in the
	 *   html is used to identify the modal and show it to the user.
	 *
	 *   @param string $id the workflow id.
	 */
	public function lingotek_translation_professional_translation_info_modal( $item_id = null, $item_type = null ) {}

	/**
	 *   This method is called when the Posts or Pages columns are being rendered.
	 *
	 *   @param string $item_id
	 *   @param string $wp_locale
	 *   @param string $item_type
	 */
	public function echo_posts_modal( $item_id = null, $wp_locale = null, $item_type = null ) {}

	/**
	 *   This method is called when the Terms table is being rendered.
	 *
	 *  @param string $item_id
	 *  @param string $wp_locale
	 *  @param string $item_type
	 */
	public function echo_terms_modal( $item_id = null, $wp_locale = null, $item_type = null ) {}


	/**
	 * This method is called when the Strings table is being rendered.
	 *
	 * @param string $item_id
	 * @param string $wp_locale
	 * @param string $item_type
	 * @return void
	 */
	public function echo_strings_modal( $item_id = null, $wp_locale = null, $item_type = null ) {}



	/**
	 * This method is called before a document is uploaded to Lingotek. If the workflow wants to perform an action
	 * before a document is uploaded to lingotek it can hook into this action.
	 *
	 * @param string $item_id
	 * @param string $type
	 * @return void
	 */
	public function pre_upload_to_lingotek( $item_id, $type ) {}

	/**
	 * Workflows have the option to hook into the save post action. This action is executed
	 * BEFORE a document is uploaded to TMS or changed. NOTE: The document may not be uploaded or saved. This
	 * method is called when the save post action is triggered.
	 *
	 * @return void
	 */
	public function save_post_hook() {}

	/**
	 * Workflows have the option to hook into the save term action. This action is executed
	 * BEFORE a document is uploaded to TMS or changed. NOTE: The document may not be uploaded or saved. This
	 * method is called when the save post action is triggered.
	 *
	 * @return void
	 */
	public function save_term_hook() {}

	/**
	 * If a workflow has a custom request procedure then it will not be requested during the standard request_translation
	 * or request_translations calls.
	 *
	 * @return boolean
	 */
	public function has_custom_request_procedure() {
		return false; }

	/**
	 * If a workflow wants to perform a custom request this method will be called after the has_custom_request_procedure() has
	 * been called if it returns TRUE. As of now this method (do_custom_request()) only gets called for single requests.
	 * bulk requests are ignored if any of the locale items are linked to a workflow that returns true on has_custom_request_procedure().
	 *
	 * @return void
	 */
	public function do_custom_request() {  }

	/**
	 * A workflow can override this method and return false if they do not want to support automatic upload upload.
	 *
	 * @return void
	 */
	public function auto_upload_allowed() {
		return true; }


	public function get_custom_in_progress_icon() {
		return false; }
	/**
	 *   This method acts as a template for building the modals. The arguments passed
	 *   are inserted into the html string and then echo'd. If any extra html elements
	 *   need to be added at a later date, they must be added to the $allowed_html array so
	 *   that they are not stripped away during the wp_kses() call.
	 *
	 *   @param array $args the arguments to populate the modal.
	 */
	protected function _echo_modal( $args ) {
		/**
		*   This allows us to use the 'display' CSS attribute. WP
		*   blacklists it by default.
		*/
		add_filter( 'safe_style_css', array( &$this, 'add_modal_styles' ) );

		$id              = isset( $args['id'] ) ? '-' . $args['id'] : '';
		$parent_elements = isset( $args['parent_elements'] ) ? $args['parent_elements'] : '';
		echo wp_kses(
			"<div id='modal-window-id" . esc_attr( $id ) . "' style='display:none;' >"
					 . wp_kses( $parent_elements, $this->allowed_html ) .
					 "<div id='modal-body" . esc_attr( $id ) . "' style='height:100%;position: relative;'>" . wp_kses( $args['body'], $this->allowed_html ) . '</div>' .
					// <br>
					// <a id='yes" . esc_attr( $id ) . "' class='lingotek-color dashicons' href='#' style='position:absolute; float:left; bottom:30px;'>Continue</a>
					// <div style='float:right; padding-right:55px;'>
					// <a id='no" . esc_attr( $id ) . "' class='lingotek-color dashicons' href='#' style='position:absolute; float:right; bottom:30px;'>Cancel</a>
					'</div>
					</div>',
			$this->allowed_html
		);
	}


	public function add_modal_styles() {
		$styles   = array();
		$styles[] = 'display';
		$styles[] = 'position';
		$styles[] = 'bottom';
		$styles[] = 'margin';
		$styles[] = 'height';
		$styles[] = 'padding-top';
		$styles[] = 'text-align';
		$styles[] = 'font-size';
		$styles[] = 'float';
		$styles[] = 'padding-right';
		$styles[] = 'line-height';
		$styles[] = 'width';
		$styles[] = 'color';
		$styles[] = 'button';
		$styles[] = 'font-style';
		$styles[] = 'font-family';
		$styles[] = 'font-size';
		$styles[] = 'font-weight';
		$styles[] = 'background-color';
		$styles[] = 'border-radius';
		$styles[] = 'background';
		return $styles;
	}
}
