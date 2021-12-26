<?php
/**
 * I think this file is not used, and probably should be removed.
 *
 * @package lingotek-translation
 */

// phpcs:disable
if ( ! class_exists( 'WP_List_Table' ) ) {
	// since WP 3.1
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
*Requires and evaluates the FormatConverter.php before continuing. If it is not
*found then a fatal error is thrown
 *
*@author Unkown
*@link http://php.net/manual/en/function.include.php
*@see FormatConverter.php
*/
require_once 'FormatConverter.php';
settings_errors();

/**
 * $client is an API call for the client logged in. This gives the end-user the ability
 * to manipulate their documents
 *
 * @uses wp-lingotek/include/api.php/Lingotek_API
 * @deprecated since 24 May 2016 This was making an extra API call and making load
 * time very slow.
 */
//$client = new Lingotek_API();
//$docs = $client->get_documents(array('limit'=>5000));
//$communities = $client->get_communities();


/**
 * This class is used to prepare the import table that is displayed when users go
 * to the import tab.
 *
 * @author Unkown
 */
class Lingotek_Import_Table extends WP_List_Table {

	var $client               = null;
	var $doc_data             = array();
	var $projects             = array();
	var $import_status        = null;
	var $supported_extentions = array( 'json', 'xml' );
	var $document_count       = 0;
	/**
	 * Used to mark the number of successful imports on a bulk import
	 *
	 * @author TJ Murphy
	 */
	var $import_success_count = 0;
	/**
	 * Used to mark the number of failed imports on a bulk import
	 *
	 * @author TJ Murphy
	 */
	var $import_failure_count = 0;
	/**
	 * Used to gather the doc ids for documents that did not import as expected
	 *
	 * @author TJ Murphy
	 */
	var $unsuccessful_imports = array();

	/**
	 * Constructor
	 *
	 * @author Unkown and TJ Murphy
	 * @uses wp-lingotek/include/api.php/Lingotek_API
	 * @see Lingotek_Import_Table::get_docs()
	 * @see Lingotek_Import_Table::format_docs()
	 */
	function __construct() {
		$this->client = new Lingotek_API();

		$this->projects       = $this->get_projects( $this->client->get_communities() );
		$this->document_count = $this->client->get_document_count();
		/**
		 *Sets $import_success_count to ZERO
	 *
		 *@author TJ Murphy
		 */
		$this->import_success_count = 0;
		/**
		 *Sets $import_failure_count to ZERO
	 *
		 *@author TJ Murphy
		 */
		$this->import_failure_count = 0;
		/**
		 *Sets $unsuccessful_imports to a blank array
	 *
		 *@author TJ Murphy
		 */
		$this->unsuccessful_imports = array();
		/**
		 *This will get ALL docs for the client
	 *
		 *@author TJ Murphy
		 *@see Lingotek_Import_Table::get_docs()
		 *@see Lingotek_Import_Table::format_docs()
		 */
		$docs           = $this->get_docs( $this->client, $this->document_count );
		$this->doc_data = $this->format_docs( $docs );

		global $status, $page;
		parent::__construct(
			array(
				'singular' => 'post',
				'plural'   => 'posts',
				'ajax'     => false,
			)
		);
	}
	/**
	 * This function is to access the count of successful imports. It is used for both
	 * bulk and single imports. This is used for a few checks and a few messages that
	 * get displayed to the end-user after imports occur
	 *
	 * @author TJ Murphy
	 * @uses Lingotek_Import_Table::$import_success_count
	 * @return void
	 */
	function add_one_import_success_count() {
		$this->import_success_count++;
	}

	/**
	 * This function is to add the doc ids of the failed imports to an array so that
	 * they can be displayed to the end-user. This just gives them more information
	 * about the errors they may encounter. It also handles the counts for failed imports.
	 *
	 * @author TJ Murphy
	 * @param string $doc_id
	 * @return void
	 */
	function add_doc_id_to_unsuccessful_imports( $doc_id ) {
		array_push( $this->unsuccessful_imports, $doc_id );
		$this->import_failure_count++;
	}

	/**
	 * This function is to list the captured doc ids in a string separated by commas
	 * so it can be displayed for the end-user in a message.
	 *
	 * @author TJ Murphy
	 * @uses Lingotek_Import_Table::$unsuccessful_imports
	 * @return string $unsuccessful_imports_string this creates an HTML string that
	 * creates an unordered list of doc ids that failed to import
	 */
	function to_string_unsuccessful_imports() {
		$unsuccessful_imports_string = '<div><ul>';
		foreach ( $this->unsuccessful_imports as $doc_id ) {
			$unsuccessful_imports_string = $unsuccessful_imports_string . '<li>' . (string) $doc_id . '</li>';
		}
		$unsuccessful_imports_string = $unsuccessful_imports_string . '</ul></div>';
		return $unsuccessful_imports_string;
	}

	/**
	 * Calls the API to get all the projects from the TMS and show them for the end-user
	 *
	 * @author Unkown
	 * @uses wp-lingotek/include/api.php/Lingotek_API::get_projects()
	 * @param $communitites
	 * @return array $new_projects an array of projects with key/value of (project id, project title)
	 */
	function get_projects( $communities ) {
		$new_projects = array();
		foreach ( $communities->entities as $community ) {
			$projects = $this->client->get_projects( $community->properties->id )->entities;
			foreach ( $projects as $project ) {
				$new_projects[ $project->properties->id ] = $project->properties->title;
			}
		}

		return $new_projects;
	}

	/**
	 * Get a list of projects or use id to get the corresponding project
	 * extract doc data and insert into correct structure for rendering
	 *
	 * @author Unkown
	 * @uses Lingotek_Import_Table::get_option()
	 * @param object $docs list of files that need to be properly formatted to show
	 * in the table
	 * @return array $result an array of properly formatted objects to be put into
	 * the table
	 */
	function format_docs( $docs ) {
		$result           = array();
		$count            = 1;
		$resources        = get_option( 'lingotek_community_resources' );
		$projectInfoArray = $resources['projects'];

		foreach ( $docs as $doc ) {
			/**
			 *Convert date from unix time and properly format it
	   *
			 *@author Unkown
			 */
			$unix_upload_time = $doc->properties->upload_date / 1000;
			$upload_date_str  = gmdate( 'm/j/Y', $unix_upload_time );

			$project_name = $doc->properties->project_id;
			if ( array_key_exists( $doc->properties->project_id, $this->projects ) ) {
				$project_name = $this->projects[ $doc->properties->project_id ];
			}

			$doc_properties = array(
				'ID'           => $count,
				'title'        => $doc->properties->title,
				'extension'    => $doc->properties->extension,
				'locale'       => '<a title=' . $doc->entities[0]->properties->language . '>' . $doc->entities[0]->properties->code . '</a>',
				'upload_date'  => $upload_date_str,
				'project_name' => $project_name,
				'id'           => $doc->properties->id,
			);
			$result[]       = $doc_properties;
			$count++;
		}//end foreach
		return $result;
	}

	/**
	 * This function makes a query to get the documents
	 *
	 * @author Unkown
	 * @uses wp-lingotek/include/api.php/Lingotek_API::get_documents()
	 * @param Lingotek_API $client used to make the API calls to get the docs
	 * @param int          $per_page default = 10 limits the query to 10 results
	 * @param int          $page_number default = 1 helps to determine the offset
	 * (so we get the right x amount of documents presented on the current page)
	 * @return array $docs list of documents returned from the API call get_documents()
	 */
	function get_docs( $client, $per_page = 10, $page_number = 1 ) {
		$limit = $per_page;
		$docs  = $client->get_documents(
			array(
				'limit'  => $limit,
				'offset' => ( ( $page_number - 1 ) * $per_page ),
			)
		);
		return $docs;
	}

	/**
	 * Gets the columns for the table to be displayed properly
	 *
	 * @author Unkown
	 * @return array $columns the appropriate columns i18n ready
	 */
	function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'title'        => __( 'Title', 'wp-lingotek' ),
			'extension'    => __( 'Extension', 'wp-lingotek' ),
			'locale'       => __( 'Locale', 'wp-lingotek' ),
			'upload_date'  => __( 'Upload Date', 'wp-lingotek' ),
			'project_name' => __( 'Project Name', 'wp-lingotek' ),
			'id'           => __( 'ID', 'wp-lingotek' ),
		);
		return $columns;
	}

	/**
	 * This function prepares all the items to be displayed. It calls the get_columns()
	 * and other supporting funcitons.
	 *
	 * @author Unkown and TJ Murphy
	 * @uses Lingotek_Import_Table::get_columns()
	 * @uses Lingotek_Import_Table::process_actions()
	 * @uses Lingotek_Import_Table::get_sortable_columns()
	 * @uses Lingotek_Import_Table::usort_reorder()
	 * @see wp-admin/includes/class-wp-list-table.php/WP_List_Table::get_items_per_page()
	 * @see wp-admin/includes/class-wp-list-table.php/WP_List_Table::get_pagenum()
	 * @see wp-admin/includes/class-wp-list-table.php/WP_List_Table::set_pagination_args()
	 * @return void This just sets some variables within the object
	 */
	function prepare_items() {

		/**
		 *Constant to define how many items per page will show up.
	 *
		 *@author TJ Murphy
		 */
		define( 'ITEMS_PER_PAGE', 10 );

		$columns = $this->get_columns();
		$this->process_actions();
		$hidden = array();

		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		/**
		 *Sets the total_items (total number of documents calculated by us) and
		 *the per_page (how many documents appear on a page dtermined by us)
	 *
		 *@see wp-admin/includes/class-wp-list-table.php/WP_List_Table::set_pagination_args()
		 */
		$this->set_pagination_args(
			array(
				'total_items' => $this->document_count,
				'per_page'    => ITEMS_PER_PAGE,
			)
		);

		usort( $this->doc_data, array( &$this, 'usort_reorder' ) );

		/**
		 *This slices the documents for the client and only shows the appropriate number
		 *of documents based on the constant ITEMS_PER_PAGE. array_slice takes three
		 *parameters ($array, $offset, $length)
	 *
		 *@author TJ Murphy
		 *@link http://php.net/manual/en/function.array-slice.php
		 */
		$this->items = array_slice( $this->doc_data, ( ( ITEMS_PER_PAGE * $this->get_pagenum() ) - ITEMS_PER_PAGE ), ITEMS_PER_PAGE );
	}

	/**
	 * This sets the column default. The default for the switch statement shows the
	 * whole array for troubleshooting purposes
	 *
	 * @author Unkown
	 * @param array  $item
	 * @param string $column_name
	 * @return string $item[$column_name]
	 */
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'title':
			case 'extension':
			case 'locale':
			case 'upload_date':
			case 'project_name':
			case 'id':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Gets the sortable columns and returns the list
	 *
	 * @author Unkown
	 * @return array $sortable_columns
	 */
	function get_sortable_columns() {
		$sortable_columns = array(
			'title'        => array( 'title', false ),
			'extension'    => array( 'extension', false ),
			'locale'       => array( 'locale', false ),
			'upload_date'  => array( 'upload_date', false ),
			'project_name' => array( 'project_name', false ),
			'id'           => array( 'id', false ),
		);
		return $sortable_columns;
	}

	/**
	 * Sorts the documents by one of the columns. The default sort is with the Title
	 * and ascending order. The date sort has its own method to sort, but all other
	 * columns are sorted the same way.
	 *
	 * @author Unknown
	 * @return int $result direction of sort
	 * @todo it would be great to make the query that gets the documents do an Order By
	 * and then this would not even be necessary. It appears this is a limitation in
	 * the API.
	 */
	function usort_reorder( $a, $b ) {
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'title';
		$order   = ( ! empty( $_GET['order'] ) ) ? $_GET['order'] : 'asc';
		$result  = 0;
		if ( $orderby == 'upload_date' ) {
			$date_a = strtotime( $a['upload_date'] );
			$date_b = strtotime( $b['upload_date'] );
			$result = $date_a - $date_b;
		} else {
			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
		}

		return ( $order === 'asc' ) ? $result : -$result;
	}

	/**
	 * Sets the column Title to have the right information in that column. This includes
	 * the popup link to import the file.
	 *
	 * @author Unknown
	 * @see wp-lingotek/admin/string-actions.php/Lingotek_String_actions::row_actions()
	 * @return string to be put in the column when displayed
	 */
	function column_title( $item ) {
		$actions = array(
			'import' => sprintf(
				'<a href="?page=%s&action=%s&post=%s&count=%s&paged=%s">Import</a>',
				$_REQUEST['page'],
				'import',
				$item['id'],
				$this->importedCount,
				$this->get_pagenum()
			),
		);
		return sprintf( '%1$s %2$s', $item['title'], $this->row_actions( $actions ) );
	}

	/**
	 * This just gets the actions that are available for bulk actions
	 *
	 * @author Unknown
	 * @return array $actions available actions to be done on a bulk level
	 */
	function get_bulk_actions() {
		$actions = array(
			'import' => 'Import',
		);
		return $actions;
	}

	/**
	 * This function processes any actions (currently the only action is import).
	 * This includes bulk imports and individual imports. Then it sets the import as
	 * successful or unsuccessful.
	 *
	 * @author Unknown
	 * @uses Lingotek_Import_Table::add_one_import_success_count()
	 * @uses Lingotek_Import_Table::add_doc_id_to_unsuccessful_imports()
	 * @uses Lingotek_Import_Table::import()
	 * @uses Lingotek_Import_Table::$import_success_count
	 * @uses Lingotek_Import_Table::$import_status
	 * @return void this just sets certain variables in the object.
	 */
	public function process_actions() {
		$result = null;
		if ( 'import' === $this->current_action() ) {
			// Bulk action.
			if ( isset( $_POST['post'] ) ) {
				foreach ( $_POST['post'] as $doc_id ) {
					$result = $this->import( $doc_id );
					if ( $result != 0 ) {
						$this->add_one_import_success_count();
					} else {
						$this->add_doc_id_to_unsuccessful_imports( $doc_id );
					}
				}
			} else {
				// Single action.
				if ( isset( $_GET['action'] ) && $_GET['action'] == 'import' ) {
					$doc_id = $_GET['post'];
					$result = $this->import( $doc_id );
					if ( $result != 0 ) {
						$this->add_one_import_success_count();
					} else {
						$this->add_doc_id_to_unsuccessful_imports( $doc_id );
					}
				}
			}//end if

			/**
			*This checks the import_success_count to determine if the import status
			*wassuccessful or not. It used to just check if the $result was 0 or not.
			*This led to the last bulk import to determine the import status. Now if
			*any imports are successful it will mark the import status as successful.
	   *
			*@author TJ Murphy
			*@uses Lingotek_Import_Table::$import_success_count
			*@uses Lingotek_Import_Table::$import_status
			*/
			if ( $this->import_success_count > 0 ) {
				$this->import_status = 'success';
			} else {
				$this->import_status = 'failure';
			}
		}//end if
	}

	/**
	 * This converts the many objects and strings that are documents into a format
	 * that can be imported into WP. If $post_status is not set it gets set to draft.
	 * If the $post_type is not set then it gets set to post.
	 *
	 * @author Unknown
	 * @uses wp-lingotek/admin/import/StandardImportObject.php/StandardImportObject
	 * @uses wp-lingotek/admin/import/StandardImportObject.php/StandardImportObject::getTitle()
	 * @uses wp-lingotek/admin/import/StandardImportObject.php/StandardImportObject::getContent()
	 * @see wp-admin/includes/class-wp-screen.php/WP_Screen::get_option()
	 * @link https://developer.wordpress.org/reference/functions/wp_insert_post/
	 * @param StandardImportObject $object a document that needs to be converted to
	 * a standard object that can be imported into WP
	 * @return int|bool $result 0 or 1 to show if it was successful or not
	 */
	public function import_standard_object( StandardImportObject $object ) {
		if ( $object->hasError() ) {
			return 0;
		}

		$post_status = get_option( 'lingotek_import_prefs' )['import_post_status'];
		$post_type   = get_option( 'lingotek_import_prefs' )['import_type'];
		if ( ! isset( $post_status ) ) {
			$post_status = 'draft';
		}
		if ( ! isset( $post_type ) ) {
			$post_type = 'post';
		}

		$post_to_import = array(
			'post_title'    => $object->getTitle(),
			'post_content'  => $object->getContent(),
			// draft, published, etc.
			'post_status'   => $post_status,
			// page or post?
			'post_type'     => $post_type,
			'post_category' => array( 8, 39 ),
		);
		/**
		 *@link https://developer.wordpress.org/reference/functions/wp_insert_post/
		 */
		$result = wp_insert_post( $post_to_import );
		return $result;
	}

	/**
	 * This function makes the API calls that send a query and get the documents
	 *
	 * @author Unknown
	 * @uses Lingotek_Import_Table::$client
	 * @uses wp-lingotek/admin/import/FormatConverter.php/FormatConverter
	 * @uses wp-lingotek/admin/import/FormatConverter.php/FormatConverter::convert_to_standard()
	 * @uses wp-lingotek/admin/import/FormatConverter.php/FormatConverter::import_standard_object()
	 */
	public function import( $doc_id ) {
		$source_doc = $this->client->get_document( $doc_id );

		$content = $this->client->get_document_content( $doc_id );
		if ( $content == null ) {
			$content == __( 'There is no content to display', 'wp-lingotek' );
		}

		$format          = $source_doc->properties->extension;
		$formatConverter = new FormatConverter( $source_doc, $content, $format );
		$importObject    = $formatConverter->convert_to_standard();
		$response        = $this->import_standard_object( $importObject );
		return $response;
	}

	/**
	 * Prints the Column Checkbox
	 *
	 * @author Unknown
	 * @return string the HTML necessary to show the Checkbox
	 */
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="post[]" value="%s" />', $item['id'] );
	}

	/**
	 * This gets the import status
	 *
	 * @author Unknown
	 * @return string $this->import_status
	 */
	function get_import_status() {
		return $this->import_status;
	}
}
