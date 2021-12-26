<?php
/**
 *   Overrides uploads for Posts, Pages and Terms in order to display a modal and handle redirecting to bridge for payment.
 *   The base class contains better documentation on what each individual function means.
 */
class Lingotek_Professional_Translation_Workflow extends Lingotek_Workflow {


	private static $item_id_list = array();

	/**
	 *   Writes a modal to the output buffer that contains information about this workflow.
	 *
	 *   @param string $item_id The workflow id.
	 *   @param string $item_type
	 */
	public function lingotek_translation_professional_translation_info_modal( $item_id = null, $item_type = null ) {
		if ( self::is_allowed_user() ) {
			if ( ! self::$info_modal_launched ) {
				self::$info_modal_launched = true;

				wp_enqueue_script( 'lingotek_professional_workflow_defaults', LINGOTEK_URL . '/js/workflow/professional-workflow-defaults.js', array(), LINGOTEK_VERSION, false );

				$client = new Lingotek_API();

				$payment_info = $client->get_user_payment_information();

				$vars = array(
					'workflow_id'      => $this->workflow_id,
					'bridge_payment'   => BRIDGE_URL . '/#/payment/portal',
					'translation_icon' => LINGOTEK_URL . '/img/translation-logo.png',
					'loading_gif'      => LINGOTEK_URL . '/img/loading.gif',
					'icon_url'         => LINGOTEK_URL . '/img/lingotek-logo-white.png',
					'payment_info'     => $payment_info,
				);
				wp_localize_script( 'lingotek_professional_workflow_defaults', 'professional_vars', $vars );

				wp_enqueue_style( 'lingotek_professional_workflow_style', LINGOTEK_URL . '/css/workflow/professional-workflow-style.css', array(), LINGOTEK_VERSION );
				$args = array(
					'parent_elements' => $this->get_payment_portal_html(),
					'body'            =>
						"					
						<div class='Youve-Selected-Prof'>You've Selected Professional Translation</div><br>
							<div class='By-Selecting-this-op'>
								By selecting this option as your workflow you will be able to request professional translations through Lingotek’s Languages Services. 
								Please click the \"ADD PAYMENT METHOD\" button to use this service.
							</div><br><br><br>
							<div class='Note-By-using-the-'>
								Note: By using the 'Lingotek Professional Translation' workflow you will be asked to confirm payment before the professional translation request is processed. 
							</div>
							<button type='button' id='no-" . esc_attr( $this->workflow_id ) . "' class='background-test float-bottom-right-next-to-default'>
								<span class='ADD-PAYMENT-LATER'>LATER</span>
							</button>
							<button type='button' id='yes-" . esc_attr( $this->workflow_id ) . "' class='prefessional-action-button-default float-bottom-right'>
	                            <span class='ADD-PAYMENT'>ADD PAYMENT METHOD</span>
							</button>
						</div>
						",
					'id'              => $this->workflow_id,
				);
				$this->_echo_modal( $args );
			}//end if
		}//end if
	}

	/**
	 *   Writes a modal to the output buffer that tells the user how much it is going to cost.
	 *
	 *   @param string $item_id The workflow id.
	 *   @param string $wp_locale
	 *   @param string $item_type
	 */
	public function echo_posts_modal( $item_id = null, $wp_locale = null, $item_type = null ) {
		$this->add_item_id( $item_id, $wp_locale );
		if ( self::is_allowed_user() ) {
			if ( ! self::$post_modal_launched ) {
				self::$post_modal_launched = true;
				$this->echo_upload_warning_modal();
				$this->echo_request_modal( 'post' );
			}
		}
		$this->flush_list();
	}


	/**
	 *   Writes a modal to the output buffer that tells the user how much it is going to cost.
	 *
	 *   @param string $item_id The workflow id.
	 *   @param string $wp_locale
	 *   @param string $item_type
	 */
	public function echo_terms_modal( $item_id = null, $wp_locale = null, $item_type = null ) {
		$this->add_item_id( $item_id, $wp_locale );
		if ( self::is_allowed_user() ) {
			if ( ! self::$terms_modal_launched ) {
				self::$terms_modal_launched = true;
				$this->echo_upload_warning_modal();
				$this->echo_request_modal( 'term' );
			}
		}
		$this->flush_list();
	}

	/**
	 *   Writes a modal to the output buffer that tells the user how much it is going to cost.
	 *
	 *   @param string $item_id The workflow id.
	 *   @param string $wp_locale
	 *   @param string $item_type
	 */
	public function echo_strings_modal( $item_id = null, $wp_locale = null, $item_type = null ) {
		$this->add_item_id( $item_id, $wp_locale );
		if ( self::is_allowed_user() ) {
			if ( ! self::$strings_modal_launched ) {
				self::$strings_modal_launched = true;
				$this->echo_upload_warning_modal();
				$this->echo_request_modal( 'string' );
			}
		}
		$this->flush_list();
	}

	/**
	 *   Writes a modal to the output buffer that tells the user if certain translations will
	 *   be overwritten by a given action.
	 */
	private function echo_upload_warning_modal() {
		$id   = $this->workflow_id . '-warning';
		$args = array(
			'header' => __( 'Confirm Document Upload', 'lingotek-translation' ),
			'body'   =>
				"
				<div id='warning-wrapper'>
					<div class='professional-upload-warning-header-container'>
						<span class='professional-upload-warning-header'>By uploading these changes, your professional translations may be lost.</span>
					</div>
	
					<div class='professional-upload-warning-body-container'>
						<span class='professional-upload-warning-body'>
							By editing this document and then re-uploading it to Lingotek any professional translations that you have not downloaded yet will be lost. Additionally, any professional translations that are in-progress for this document will be lost.
						</span>
					</div>
	
					<div class='professional-warning-checkbox-container'><input id='professional-warning-checkbox' type='checkbox' name='confirm-request' value='professional-warning-upload></div>
					<div class='professional-warning-checkbox-text-container'><span class='professional-warning-checkbox-text'> I understand that translations that have not been downloaded or that are in progress will be lost and money paid for that translation will be forfeited by re-uploading them to Lingotek.</span></div>
					
					<button type='button' id='cancel-warning-" . esc_attr( $id ) . "' class='professional-cancel-button float-bottom-right-next-to'>
						<span class='ADD-PAYMENT-LATER'>CANCEL</span>
					</button>
					<div class='float-bottom-right' style='bottom:5%;'><img id='professional-warning-loading-spinner' style='display:none;' src='" . esc_url_raw( LINGOTEK_URL ) . "/img/loading_mini.gif' /></div>
					<button type='button' id='ok-warning-" . esc_attr( $id ) . "' class='professional-okay-warning-button-disabled float-bottom-right' disabled='true'>
						<span id='professional-warning-okay-text' class='ADD-PAYMENT-DISABLED'>PROCEED</span>
					</button>
				
				</div>",
			'id'     => $id,
		);
		$this->_echo_modal( $args );
	}

	/**
	 *  Writes a modal to the output buffer that is launched when the user clicks on the 'request translation'
	 *  option.
	 *
	 *  @param string $type
	 */
	public function echo_request_modal( $type ) {
		if ( self::is_allowed_user() ) {
			$this->load_request_scripts_and_styles( $type );
			$id   = $this->workflow_id . '-request';
			$args = array(
				'header'          => __( 'Confirm Request Translation', 'lingotek-translation' ),
				'parent_elements' => "
										<img class='loading-element loading-translations-image' src='" . esc_url_raw( LINGOTEK_URL ) . "/img/translation-logo.png'/>
										<div class='loading-element loading-progress-bar-outer'>
											<div class='loading-element loading-progress-bar-inner'></div>
										</div>
										<span class='loading-element loading-progress-percent'>65%</span>
										<div class='loading-element Analyzing-translations'>Analyzing translations and gathering quotes...</div>
										",
				'body'            =>
					"
						<div id='wrapper'>
							<div id='content'><br>
								<div id='professional-table-content'>
									<span class='Congratulations-You payment-method-setup'>You have elected to use our Professional Translation Services.<br><br></span>
									<div class='You-can-now-connect payment-method-setup'>
										You can now connect with audiences around the globe using Lingotek's network of 5000+ professional, in-country, translators. Professional Translation ensures that your audiences will feel the sentiment of your content.
									</div>
									
									<span class='Congratulations-You payment-not-setup'>Welcome to Lingotek's Translation Quote Calculator<br><br></span>
									<div class='You-can-now-connect payment-not-setup'>
										Using the quote calculator below you can get an idea of how much your translations will cost. You will need to add a payment method to the Lingotek Secure Payment Portal In order to purchase these translations.
									</div>
									<br>
									<div class='minimum-per-language-warning minimum-warning'>
										<img  src='" . esc_url_raw( LINGOTEK_URL ) . "/img/minimum-warning.svg' />
										<span >*$59.99 minimum per language.</span>
										<span  ltk-data-tooltip='This minimum ensures that we can retain the best professional linguists for your translation job.'><img src='" . esc_url_raw( LINGOTEK_URL ) . "/img/minimum-help.svg' /></span>
									</div>
									<br class='payment-not-setup'>
									<span class='Translation-Request payment-not-setup'>Translation Quotes<br><br></span>
									<br class='payment-method-setup'>
									<span class='Translation-Request payment-method-setup'>Translation Request Summary<br><br></span>
									<div id='table-wrapper'>
										<table class='request-table'>
												<tr class='bordered-bottom'><th style='display: table-cell;' class='table-header'>Title</th><th style='display: table-cell;' class='table-header' id='words-column'>Words</th><th style='text-align:center;'><a href='#' id='next-language-set'> <img id='next-language-image' src='" . esc_url_raw( LINGOTEK_URL ) . "/img/right-arrow.svg' /> </a> </th><th class='table-header table-total invisible'>Total</th></tr>
										</table>
									</div>
								</div>
								<div id='professional-terms-and-conditions'>
									<span class='terms-and-conditions-header'>Lingotek Terms and Conditions</span><br><br>
									<div class='terms-and-conditions-content'>							
										<img id='terms-of-service-loading-ltk' src='" . esc_url_raw( LINGOTEK_URL ) . "/img/loading.gif'/>
									</div>
									<button type='button' id='close-terms-and-conditions'>
										<span class='CLOSE-TERMS-CONDITIONS'>CLOSE</span>
									</button>
								</div>
							</div>
							<div id='sidebar'>
								<span class='Payment-Method payment-method-setup'>Payment Method</span><br class='payment-method-setup'><br class='payment-method-setup'>
								<span class='payment-method-setup credit-card-header'>Credit Card</span><br class='payment-method-setup'><br class='payment-method-setup'>
								<div class='payment-method-setup professional-card-border'>
									<div class='payment-method-setup blue-radio-button-div'><img id='blue-radio-button' class='payment-method-setup' src='" . esc_url_raw( LINGOTEK_URL ) . "/img/blue-radio-button.svg'/></div>
									<div class='payment-method-setup credit-card-dots-div'><img id='credit-card-dots' class='payment-method-setup' src='" . esc_url_raw( LINGOTEK_URL ) . "/img/credit-dots.svg'/></div>
									<div class='payment-method-setup last-four-digits-div'><span id='last-four-digits' class='payment-method-setup'>XXXX</span></div>
									<div class='payment-method-setup credit-card-image-div'><img id='credit-card-image' class='payment-method-setup' src='" . Lingotek_Credit_Card_To_Path::get_cc_type_asset_url( Lingotek_Credit_Card_To_Path::get_default_cc_key() ) . "'/></div>
								</div>
								<br class='payment-method-setup'>
								<br class='payment-method-setup'>


								<span class='Payment-Method payment-not-setup'>Benefits of Professional Translation</span><br class='payment-not-setup'>
								<ul class='translation-summary-list payment-not-setup'>
									<li><span class='translation-summary-list-text'>Translations will be translated by professional in-country linguists.</span></li>
									<li><span class='translation-summary-list-text'>Receive your translations in just a few days.</span></li>
									<li><span class='translation-summary-list-text'>100% customer satisfaction guarantee.</span></li>
								</ul>
								<br class='payment-not-setup'>
								<span class='Translation-Request-Right payment-method-setup'>Translation Request Summary</span>
								<span class='Translation-Request-Right payment-not-setup'>Translation Quote Summary</span>
								<ul class='translation-summary-list translation-summary-list-ltk'>
									
								</ul>
								<div class='minimum-disclaimer'>*Minimum charge $59.99 per language</div>
								<br>
								<div style='float:right;'>
									<span class='request-total'>TOTAL: </span><span class='lingotek-total-amount'></span>
								</div>

								<div class='ltk-request-payment-disclaimer payment-method-setup'>
								Note: By clicking the 'Buy Now' button your translations will be requested and you will be charged for the amount shown above.
								</div>
								
								
								<br class='payment-not-setup'><br class='payment-not-setup'><br class='payment-not-setup'>
								<div class='disclaimer-request payment-not-setup'>
									Note: By clicking the 'Add Payment Method' button you will be redirected to the Lingotek Secure Payment Portal.
									Please note that none of the selections you have made in the table will be saved.
								</div>
								<br class='payment-method-setup'><br class='payment-method-setup'>

								<div class='delivery-estimation'>Estimated delivery: <span class='business-days'>3 - 5 business days.</span><span ltk-data-tooltip='This is an estimate, not a guaranteed delivery time.'><img src='" . esc_url_raw( LINGOTEK_URL ) . "/img/minimum-help.svg' /></span></div>
								<div class='request-checkbox payment-method-setup'><input id='accept-terms-and-conditions-input' type='checkbox' name='confirm-request' value='request-translation><span class='terms-conditions'> I agree to the <a id='terms-and-conditions-href' href='#'>Lingotek Terms and Conditions</a></span></div>
								<br class='payment-method-setup'>
								
								<button type='button' id='yes-" . esc_attr( $id ) . "-add-payment' class='professional-action-button float-center payment-not-setup' style='float:left'>
										<span id='professional-add-payment' class='ADD-PAYMENT payment-not-setup'>ADD PAYMENT METHOD</span>
								</button>
								<button type='button' id='yes-" . esc_attr( $id ) . "-buy-now' class='professional-action-button-disabled float-center payment-method-setup' style='float:left; bottom:-2%;' disabled='true'>
										<span id='professional-buy-now' class='ADD-PAYMENT-DISABLED payment-method-setup'>BUY NOW</span>
								</button>
							</div>
							<div id='cleared'></div>
						</div>


						<div id='requesting-translation-screen' class='requesting-element'>
									<img class='requesting-element payment-portal-image' src='" . esc_url_raw( LINGOTEK_URL ) . "/img/translation-logo.png'/>
									<img class='requesting-element payment-portal-loading' src='" . esc_url_raw( LINGOTEK_URL ) . "/img/loading.gif'/>
									<div class='requesting-element Analyzing-translations'>Requesting Translations</div>
						</div>

						<div id='requesting-translation-success-screen' class='professional-translation-request-success-element'>
							<img class='professional-translation-request-success-element payment-portal-image' src='" . esc_url_raw( LINGOTEK_URL ) . "/img/translation-logo.png'/>
							<img class='professional-translation-request-success-element green-check-success' src='" . esc_url_raw( LINGOTEK_URL ) . "/img/checkmark-green.svg'/>
							<div class='professional-translation-request-success-element professional-translation-view-invoice' style='height: 28px;'>Your translations have been successfully requested.</div>
							<div class='professional-translation-request-success-element professional-translation-view-invoice'>You will be receiving a confirmation email shortly.</div>
							<div class='professional-translation-request-success-element professional-translation-view-invoice'>Your credit card will be charged <span id='professional-translation-cost-success' class='professional-translation-request-success-element'></span></div>
							<button type='button' id='professional-post-transaction-button' class='professional-translation-request-success-element professional-view-invoice-btn float-center'>
									<span id='professional-post-transaction' class='ADD-PAYMENT professional-translation-request-success-element'>OK</span>
							</button>
						</div>



						<div id='requesting-translation-error-screen' class='professional-translation-request-error-element'>
							<img class='professional-translation-request-error-element payment-portal-image' src='" . esc_url_raw( LINGOTEK_URL ) . "/img/translation-logo.png'/>
							<img class='professional-translation-request-error-element green-check-success' src='" . esc_url_raw( LINGOTEK_URL ) . "/img/error.svg'/>
							<div id='error-requesting-translation-ltk' class='professional-translation-request-error-element professional-translation-view-invoice'>There was an error requesting your translation.</div>
							<div id='error-requesting-translation-ltk-2' class='professional-translation-request-error-element professional-translation-view-invoice'>Please refresh the page and try again.</div>
							<button type='button' id='professional-post-transaction-button' class='professional-translation-request-error-element professional-view-invoice-btn float-center'>
									<span id='professional-post-transaction-failure' class='ADD-PAYMENT professional-translation-request-error-element'>Ok</span>
							</button>
						</div>

						</div>
						" . $this->get_payment_portal_html() . '
						
						',
				'id'              => $id,
			);
			$this->_echo_modal( $args );
		}//end if
	}

	/**
	 * This method will be used to roll out the Professional Translation workflow to a select number of users.
	 * The method will be called before anything is sent to the client.
	 */
	public static function is_allowed_user() {
		return true;
	}

	/**
	 * We store the document id's of the documents we have already disassociated
	 * so that we don't make redundant calls to disassociate a document.
	 *
	 * @var array
	 */
	private static $pre_uploaded = array();

	/**
	 * We disassociate the document before uploading to avoid automatic requesting of translations.
	 *
	 * @param string $item_id The workflow id.
	 * @param string $type
	 * @return void
	 */
	public function pre_upload_to_lingotek( $item_id, $type ) {
		$lgtm     = new Lingotek_Model();
		$document = $lgtm->get_group( $type, $item_id );
		if ( $document ) {
			if ( isset( self::$pre_uploaded[ $document->document_id ] ) ) {
				return; }
			self::$pre_uploaded[ $document->document_id ] = true;
			$document->delete();
		}
	}


	/**
	 * When a quick edit is performed it is done over AJAX. The front end is dependent upon many event
	 * listeners to keep track of what the user is clicking on and what should be shown in the modal. Because quick edit is
	 * done over AJAX, we send a page refresh so that our listeners can re-attach. This is not the preferred solution - feel free
	 * to change this (whether on the backend or front end) as a better solution appears.
	 *
	 * @return void
	 */
	public function save_post_hook() {
		if ( ! self::$save_post_hook_executed ) {
			self::$save_post_hook_executed = true;
			$post_vars                     = filter_input_array( INPUT_POST );

			if ( 'inline-save' === $post_vars['action'] ) {
				echo '<script>location.reload();</script>';
			}
		}
	}

	/**
	 * When a quick edit is performed it is done over AJAX. The front end is dependent upon many event
	 * listeners to keep track of what the user is clicking on and what should be shown in the modal. Because quick edit is
	 * done over AJAX, we send a page refresh so that our listeners can re-attach. This is not the preferred solution - feel free
	 * to change this (whether on the backend or front end) as a better solution appears.
	 *
	 * @return void
	 */
	public function save_term_hook() {
		if ( ! self::$save_terms_hook_executed ) {
			self::$save_terms_hook_executed = true;
			$post_vars                      = filter_input_array( INPUT_POST );

			if ( 'inline-save-tax' === $post_vars['action'] ) {
				echo '<script>location.reload();</script>';
			}
		}
	}

	/**
	 * We request translations over Bridge.
	 *
	 * @return boolean
	 */
	public function has_custom_request_procedure() {
		return true; }

	/**
	 * We don't want documents being uploaded automatically because of the chance
	 * that a user may lose translations that they have paid for if they haven't finished or downloaded.
	 *
	 * @return bool
	 */
	public function auto_upload_allowed() {
		return false;
	}


	public function get_custom_in_progress_icon() {
		return '<div title="Professional translation in progress" class="lingotek-professional-icon"><img src="' . LINGOTEK_URL . '/img/human-translation.svg" /></div>';
	}

	/**
	 * This loads the js and css files that handle the showing of the modals.
	 *
	 * @param string $type
	 * @return void
	 */
	private function load_request_scripts_and_styles( $type ) {
		add_thickbox();
		wp_enqueue_script( 'lingotek_professional_workflow', LINGOTEK_URL . '/js/workflow/professional-workflow.js', array(), LINGOTEK_VERSION, false );
		wp_enqueue_style( 'lingotek_professional_workflow_style', LINGOTEK_URL . '/css/workflow/professional-workflow-style.css', array(), LINGOTEK_VERSION );

		$client = new Lingotek_API();

		$language_mappings = $client->get_language_mappings();
		$locales_list      = PLL()->model->get_languages_list();

		$enabled_langs = array();
		foreach ( $locales_list as $locale ) {
			$lingotek_gmc_locale              = str_replace( '-', '_', $locale->lingotek_locale );
			$enabled_langs[ $locale->locale ] = array(
				'lingotek_locale' => $locale->lingotek_locale,
				'language'        => isset( $language_mappings[ $lingotek_gmc_locale ] )
					? $language_mappings[ $lingotek_gmc_locale ]['language_name']
					: null,
				'country_name'    => isset( $language_mappings[ $lingotek_gmc_locale ] )
					? $language_mappings[ $lingotek_gmc_locale ]['country_name']
					: null,
			);
		}

		$vars = array(
			'workflow_id'             => $this->workflow_id,
			'icon_url'                => LINGOTEK_URL . '/img/lingotek-logo-white.png',
			'question_mark_icon_url'  => LINGOTEK_URL . '/img/questionmark.svg',
			'bridge_payment_redirect' => BRIDGE_URL . '/#/payment/portal',
			'enabled_langs'           => $enabled_langs,
			'curr_item_type'          => $type,
			'cc_type_map'             => Lingotek_Credit_Card_To_Path::get_cc_map(),
			'default_cc_type'         => Lingotek_Credit_Card_To_Path::get_default_cc_key(),
			'nonce'                   => wp_create_nonce( 'lingotek_professional' ),
		);
		wp_localize_script( 'lingotek_professional_workflow', 'workflow_vars', $vars );
	}


	/**
	 * As the table is being built (for posts, pages, terms, etc.) we only want to attach listeners to locales that have the professional
	 * workflow enabled. This list is build as the table is being rendered.
	 *
	 * @param string $item_id
	 * @param string $wp_locale
	 * @return void
	 */
	private function add_item_id( $item_id, $wp_locale ) {
		self::$item_id_list[ $item_id ][] = $wp_locale;
	}

	/**
	 * Because a new workflow object is created every time a new locale is rendered on the table we can't preserve the list in a single object.
	 * Instead, we store that list statically and update the list that will eventually be sent to the front end everytime a workflow object is destroyed.
	 *
	 * @return void
	 */
	private function flush_list() {
		$vars = array(
			'ids' => self::$item_id_list,
		);
		wp_localize_script( 'lingotek_professional_workflow', 'item_ids', $vars );
	}

	/**
	 * Returns the HTML that displays the payment portal loading screen.
	 *
	 * @return string
	 */
	private function get_payment_portal_html() {
		return "
			<div id='payment-portal-wrapper' class='payment-portal-element'>
				<img class='payment-portal-element payment-portal-image' src='" . esc_url_raw( LINGOTEK_URL ) . "/img/translation-logo.png'/>
				<img class='payment-portal-element payment-portal-loading' src='" . esc_url_raw( LINGOTEK_URL ) . "/img/loading.gif'/>
				<span class='payment-portal-element You-are-now-being-re'>You are now being redirected to the Lingotek Secure Payment Portal...</span>
			</div>
		";
	}
}
