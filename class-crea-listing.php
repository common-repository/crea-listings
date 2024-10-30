<?php
/**
 * CREA Listings.
 *
 * @package   CREA Listings
 * @author    Sprytechies <contact@sprytechies.com>
 * @license   GPL-2.0+
 * @link      http://sprytechies.com
 * @copyright 2014 contact@sprytechies.com
 */

/**
 * Plugin class.
 *
 * @package CREA Listings
 * @author  Sprytechies <contact@sprytechies.com>
 */

require_once dirname(__FILE__).'/crea/phrets-1.0rc2.php';
require_once dirname(__FILE__).'/crea/phrets-wrapper.php';
require_once dirname(__FILE__).'/crea/pagination.php';

class CreaListing {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $version = '2.0.0';

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'crea-listings';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.0.0
	 */
        
        /**
         * Details regarding options and values
         */
        protected $option_name = 'crea-listing-values';
        protected $option_name_users = 'crea-user-list';
        protected $data = array(
            'fullname' => ' ',
            'username' => ' ',
            'password' => ' ',
            'number_of_listing' => 5
        );
        
        /**
         * CREA API details
         */
	    private $loginURL = 'http://data.crea.ca/Login.svc/Login' ;
		private $logFileLocation = 'log.txt';
		private $rets;
		private $log;
	    public $crea_properties = 'Crea Properties';        
	        
		public function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );		
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
                
        // For ajax call from frontend
        add_action('wp_ajax_pagination', array( $this, 'crea_pagination'));
        add_action('wp_ajax_nopriv_pagination', array( $this, 'crea_pagination'));
                
        add_action('wp_ajax_prop_details', array( $this, 'property_details'));
        add_action('wp_ajax_nopriv_prop_details', array( $this, 'property_details'));
                
		// Define custom functionality. Read more about actions and filters: http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		add_action( 'admin_init', array( $this, 'action_method_name' ) );
        add_action('my_new_event',array( $this, 'getpropertylist' ));
        wp_schedule_single_event(time()+10, 'my_new_event');              
               
        //Add cron for properties update
        add_action('hourlyupdatehook', array( $this, 'do_this_hourly' ) );
        add_action('dailyupdatehook', array( $this, 'do_this_daily' ) );
                
        //Remove category from posts display
        add_filter( 'pre_get_posts', array( $this, 'exclude_category' ) );
                
        //Check for database update
        add_action( 'plugins_loaded',  array( $this, 'crea_db_check' ));
                
        //Check for post update
        add_action( 'plugins_loaded',  array( $this, 'crea_post_check' ));

        //ajax call for admin backend
        add_action('wp_ajax_view_list', array( $this, 'view_list_callback'));
        add_action('wp_ajax_hide_list', array( $this, 'hide_list_callback'));
        add_action('wp_ajax_show_list', array( $this, 'show_list_callback'));                       
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}


	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {
		
                global $wpdb;
                $option_name = 'crea-listing-values';
                $data = array(
                    'fullname' => ' ',
                    'username' => ' ',
                    'password' => ' ',
                    'number_of_listing' => 5
                 );
                update_option($option_name, $data);
                
                $crea_tablename = $wpdb->prefix . "crea_properties";
                $crea_sql = "CREATE TABLE IF NOT EXISTS $crea_tablename (
		       				`id` int(11) NOT NULL AUTO_INCREMENT,
		       				`idproperty` int(16) DEFAULT NULL,
		       				`data` text,
		      				`lastupdated` datetime DEFAULT NULL,
                			`photo_flag` INT( 4 ) NOT NULL DEFAULT  '0',
                			`photo_data` TEXT NULL,
               				`username` VARCHAR( 64 ) NULL,
               				`url` VARCHAR( 256 ) NULL,
                			`post_status` TEXT NULL,
							PRIMARY KEY (`id`)
                			)ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4806;";                
                
                /* include dbdelta stuff */
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );	
                /* build the table using the variables above */
                dbDelta( $crea_sql );                
                //cron update
                wp_schedule_event( time(), 'hourly', 'hourlyupdatehook');
                wp_schedule_event( time(), 'daily', 'dailyupdatehook');               
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

                wp_clear_scheduled_hook('hourlyupdatehook');
                wp_clear_scheduled_hook('dailyupdatehook');
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
                
		$domain = $this->plugin_slug;
             	  $this->rets = new phRETS();
                  $this->rets->SetParam('catch_last_response', true);
				  $this->rets->SetParam('compression_enabled', true);
				  $this->rets->AddHeader('RETS-Version', 'RETS/1.7.2');
				  $this->rets->AddHeader('Accept', '/');		
				  $this->log = new Logging();
				  $this->log->lfile($this->logFileLocation);
                  $this->crea_insert_category();
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen->id == $this->plugin_screen_hook_suffix ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array(), $this->version );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		$screen = get_current_screen();
		if ( $screen->id == 'settings_page_crea-listing' ) {
		   wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), $this->version );
		   wp_enqueue_script( $this->plugin_slug .'-ajax-request', plugins_url( 'js/ajax-admin.js', __FILE__ ), array( 'jquery' ), 1.0,true );
           wp_localize_script( $this->plugin_slug .'-ajax-request', 'Viewajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );	
		}               
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'css/public.css', __FILE__ ), array(), $this->version );
        wp_enqueue_style( $this->plugin_slug . '-slider-styles', plugins_url( 'css/slider/flexslider.css', __FILE__ ), array(), $this->version );
	
	} 

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		  
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'js/public.js', __FILE__ ), array( 'jquery' ), $this->version,true );
        wp_enqueue_script( $this->plugin_slug .'-slider-script',  plugins_url( 'js/slider/jquery.flexslider.js', __FILE__ ), array( 'jquery' ),1.0 ,true);
		wp_enqueue_script( $this->plugin_slug .'-ajax-request', plugins_url( 'js/ajax.js', __FILE__ ), array( 'jquery' ), 1.0,true );
        wp_localize_script( $this->plugin_slug .'-ajax-request', 'MyAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    }

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		
		add_options_page(
			__( 'CREA Listings Settings', $this->plugin_slug ),
			__( 'CREA Listings', $this->plugin_slug ),
		    'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);		
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		
		include_once( 'views/admin.php' );
	}

	/**
	 * NOTE: Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.	 
	 *
	 * @since    1.0.0
	 */
	public function action_method_name() {

            if(isset($_POST['action']) && $_POST['action'] == 'delete-option'){
                $options = get_option($this->option_name);
                $users = get_option($this->option_name_users);
                unset($users[$_POST['username']]);
                if(isset($_POST['username']) && ($options['username'] == $_POST['username'])){
                    update_option($this->option_name,array(
                        'fullname' => ' ',
                        'username' => ' ',
                        'password' => ' ',
                        'number_of_listing' => 5
                    ));
                }
                update_option($this->option_name_users, $users);
                header('Location: http:'.$_POST['_wp_http_referer']);
                exit;
            }           
            else{
                register_setting('crea_options', $this->option_name, array($this, 'validate'));
                $options = get_option($this->option_name);
                $users = get_option($this->option_name_users);
                if(isset($options['username']) && $options['username'] && $options['username']!= ' '){
                $users = ($users)?$users:array();
                $users[$options['username']] = $options;
                update_option($this->option_name_users, $users);}
            }         
		}
        
        /**
         * Validate the input
         */
        public function validate($input) {

            $valid = array();
            $valid['fullname'] = sanitize_text_field($input['fullname']);
            $valid['username'] = sanitize_text_field($input['username']);
            $valid['password'] = sanitize_text_field($input['password']);
            $valid['number_of_listing'] = sanitize_text_field($input['number_of_listing']);

            if (strlen($valid['username']) == 0) {
                add_settings_error(
                        'username',                     // Setting title
                        'username_texterror',            // Error ID
                        'Please enter a valid username',     // Error message
                        'error'                         // Type of message
                );

                // Set it to the default value
                $valid['username'] = $this->data['username'];
            }
            if (strlen($valid['password']) == 0) {
                add_settings_error(
                        'password',
                        'password_texterror',
                        'Please enter a password',
                        'error'
                );

                $valid['password'] = $this->data['password'];
            }
            
            if (strlen($valid['number_of_listing']) == 0) {
                add_settings_error(
                        'number_of_listing',
                        'number_of_listing_texterror',
                        'Please enter a number',
                        'error'
                );

                $valid['number_of_listing'] = $this->data['number_of_listing'];
            }
            return $valid;
        }
        
       /**
         * Log server info
         */
        public function LogServerInfo(){

			$this->DisplayHeader('Server Info');
			$this->DisplayLog('Server Details: ' . implode($this->rets->GetServerInformation()));
			$this->DisplayLog('RETS version: ' . $this->rets->GetServerVersion());
		}
	
        /**
         * Log infor
         */
		public function LogTypeInfo(){	

			$this->DisplayHeader('RETS Type Info');
			$this->DisplayLog(var_export($this->rets->GetMetadataTypes(), true));
			$this->DisplayLog(var_export($this->rets->GetMetadataResources(), true));
		}
        
        /**
         * Connect to Rets server
         */
        public function Connect( $username, $password ) { 
		
			$connect = $this->rets->Connect($this->loginURL, $username, $password);		
			if ($connect === true) { 			
			}
			else{				
				if ($error = $this->rets->Error()) {				
				}				
				return false;
			}
			return true;
		}        
       
        /**
         * Search residential property by its id
         */
        public function SearchResidentialProperty($crit, $urlEncode = true, $user, $pass){	
            
			global $wpdb;
	        if(!isset($user)){ return; }
	        $table_name = $wpdb->prefix ."crea_properties";
			$myrow = $wpdb->get_row( "SELECT idproperty FROM $table_name where username = '$user'" );
	                
			if($urlEncode){
				$results = $this->rets->SearchQuery("Property","Property",urlencode($crit));
			}
			else{
				$results = $this->rets->SearchQuery("Property","Property",$crit);
			}

            $first_check = str_replace('xmlns="CREA.Search.Property"','',$this->rets->GetLastServerResponse());
			$data = simplexml_load_string(str_replace('RETS-RESPONSE','rets',$first_check));
            $i=0;
                if( $crit === "ID=*" ){
	                    $properties = (array)$data->rets;
	                    $property_ids = '';
	                    if(!isset($myrow->data)){
	                        foreach($properties['Property'] as $property){
		                        $row_inserted = $wpdb->insert( $table_name, array( 
		                             			'idproperty' => $properties['Property'][$i]->attributes()->ID, 
		                             			'lastupdated' => date("Y-m-d H:i:s"),
		                             			'username' => $user,
		                             			'post_status' => 'publish'
		                             			) );
		                        $property_ids .= $properties['Property'][$i]->attributes()->ID.',';
		                        $i++;
	                        }
	                    }                    
                }
                else{                    
                    $properties = (array)$data->rets;$i=0;
                    if(is_object($properties['PropertyDetails'])){
	                        $temp = $properties['PropertyDetails'];
	                        $properties['PropertyDetails'] = array();
	                        $properties['PropertyDetails'][0] = $temp ;
                    }

                    foreach($properties['PropertyDetails'] as $property){
                        $details = array();
                        $details['property_id'] = (array)$properties['PropertyDetails'][$i]->attributes()->ID;
                        $details['listing_id'] = (array)$property->ListingID;
                        $details['agent_id'] = (array)$property->AgentDetails->attributes()->ID;
                        $details['agent_name'] = (array)$property->AgentDetails->Name;
                        $details['agent_email'] = (array)$property->AgentDetails->Emails->Email;
                        $details['agent_phone'] = (array)$property->AgentDetails->Phones->Phone;
                        $details['agent_address1'] = (array)$property->AgentDetails->Office->Address->AddressLine1;
                        $details['agent_city'] = (array)$property->AgentDetails->Office->Address->City;
                        $details['agent_postcode'] = (array)$property->AgentDetails->Office->Address->PostalCode;
                        $details['agent_office_phone'] = (array)$property->AgentDetails->Office->Address->Phones->Phone;
                        $details['agent_position'] = (array)$property->AgentDetails->Position;
                        $details['office_id'] = (array)$property->AgentDetails->Office->attributes()->ID;
                        $details['office_name'] = (array)$property->AgentDetails->Office->Name;
                        $details['office_city'] = (array)$property->AgentDetails->Office->Address->City;
                        $details['office_board'] = (array)$property->Board;
                        $details['office_franchise'] = (array)$property->Business->Franchise;
                        $details['building_bathroom'] = (array)$property->Building->BathroomTotal;
                        $details['building_bedroom'] = (array)$property->Building->BedroomsTotal;
                        $details['building_cooling'] = (array)$property->Building->CoolingType;
                        $details['building_display_years'] = (array)$property->Building->DisplayAsYears;
                        $details['building_fireplace'] = (array)$property->Building->FireplacePresent;
                        $details['building_fuel'] = (array)$property->Building->HeatingFuel;
                        $details['building_heating_type'] = (array)$property->Building->HeatingType;
                        $details['building_size'] = (array)$property->Building->SizeInterior;
                        $details['building_type'] = (array)$property->Building->Type;
                        $details['land_size'] = (array)$property->Land->SizeTotal;
                        $details['land_acre'] = (array)$property->Land->Acreage;
                        $details['land_street'] = (array)$property->Address->StreetAddress;
                        $details['land_address'] = (array)$property->Address->AddressLine1;
                        $details['land_city'] = (array)$property->Address->City;
                        $details['land_province'] = (array)$property->Address->Province;
                        $details['land_postcode'] = (array)$property->Address->PostalCode;
                        $details['land_province'] = (array)$property->Address->Country;
                        $details['land_country'] = (array)$property->Address->Province;
                        $details['features'] = (array)$property->Features;
                        $details['farmtype'] = (array)$property->FarmType;
                        $details['ownership'] = (array)$property->OwnershipType;
                        $details['price'] = (array)$property->Price;
                        $details['property_type'] = (array)$property->PropertyType;
                        $details['public_remark'] = (array)$property->PublicRemarks;
                        $details['viewtype'] = (array)$property->ViewType;
                        $details['waterfront'] = (array)$property->WaterFrontType;
                        $i++;
                        $ser_data = addslashes(serialize($details));
                        $date = date("Y-m-d H:i:s");
                        $id = $details['property_id'][0];
                        $result = $wpdb->query("UPDATE $table_name SET data='{$ser_data}', lastupdated='$date' where idproperty = $id and username = '$user'");
                    }
                }
		}

		/**
         * Get the property
         */
        public function GetPropertyObject($user, $pass){
                if(!isset($user)){
                	return;
                }
                global $wpdb;
                $table_name = $wpdb->prefix ."crea_properties";
                $myrows = $wpdb->get_results( "SELECT * FROM $table_name where photo_flag=0 and username = '$user' LIMIT 0,20" );
                $upload_dir = wp_upload_dir();
                foreach($myrows as $row){
                    if($row->photo_flag == 1){
                    	continue;
                    }
                    $image_data = array();
                    $photos = $this->rets->GetObject("Property", "Photo", $row->idproperty);
                    foreach ($photos as $photo){
                            $listing = $photo['Content-ID'];
                            $number = $photo['Object-ID'];
                            if ($photo['Success'] == true) {
                                    file_put_contents("{$upload_dir['path']}/image-{$listing}-{$number}.jpg", $photo['Data']);
                                    $image_data[] = $upload_dir['url'].'/image-'.$listing.'-'.$number.'.jpg';
                            }
                            else {
                                    echo "({$listing}-{$number}): {$photo['ReplyCode']} = {$photo['ReplyText']}\n";
                            }
                    }                   
                    $ser_data = addslashes(serialize($image_data));
                    $result = $wpdb->query("UPDATE $table_name SET photo_data='{$ser_data}',photo_flag=1 where username = '$user' and idproperty = $row->idproperty");
                }
		}
        
        /**
         * Daily update of listing
         */
        public function SearchResidentialPropertiesUpdatedSince($days, $user, $pass){

			date_default_timezone_set('UTC');
			$date = new DateTime();
			$date->sub(new DateInterval('P' . $days . 'D'));		
			return $this->SearchResidentialProperty("(LastUpdated=" . $date->format('Y-m-d') . ")", false, $user, $pass);		
		}
        
        /**
         * Close the connnection
         */
        public function Disconnect(){ 

			 $this->rets->Disconnect();		
		}

        /**
         * Log all request made in log file
         */
        public function LogLastRequest($logResponse = true){
		
			if ($last_request = $this->rets->LastRequest()){		
			}
			if($logResponse){
				$this->DisplayLog($this->rets->GetLastServerResponse());
			}
		}	
        
        /**
         * write log in file
         */
        private function DisplayLog($text){
			echo $text . "\n";
			$this->log->lwrite($text.PHP_EOL);
		}	
        
        /**
         * Display header information
         */
		function DisplayHeader($text){
			echo "\n\n";
			echo PHP_EOL.str_pad('## '.trim($text).' ', 80, '#').PHP_EOL;
			
			$this->log->lwrite("");
			$this->log->lwrite("");
			$this->log->lwrite(str_pad('## '.trim($text).' ', 80, '#').PHP_EOL);
		}
        
        /**
         * List all properties
         */
        public function getpropertylist(){
                $new_listings = '';
                
                $options = get_option($this->option_name);
                
                 if(isset($options['username']) && $options['username'] != ' ' && $options['password'] != ' ' ){
                    global $wpdb;
                    $table_name = $wpdb->prefix ."crea_properties";
                    $myrow = $wpdb->get_row( "SELECT idproperty FROM $table_name where username = '{$options['username']}'" );
                      if(!isset($myrow->idproperty)){
                        if( $this->Connect($options['username'], $options['password']) ){
                            $this->SearchResidentialProperty("ID=*", true, $options['username'],  $options['password']);
                            $property_id = '';
                            $myrows = $wpdb->get_results( "SELECT idproperty FROM $table_name where username = '{$options['username']}' and idproperty is not null Limit 0,100" );
                            $i = 0;
                            foreach($myrows as $row){
                                if($i==0){
                                    $property_id = $row->idproperty;
                                }else{
                                    $property_id = $row->idproperty.','.$property_id;
                                }
                                $i++;
                            }
                            $this->SearchResidentialProperty("ID=$property_id", true, $options['username'],  $options['password']);
                            $this->GetPropertyObject($options['username'],  $options['password']);
                            $this->Disconnect();
                        }
                      }
                }
        }
        
        /**
         * Display property lists
         */
        public function display_properties($attr){
            if(!isset($attr['user'])){
            	return;
            }
            global $wpdb;
            $html = '';
            $pagination_count = 0;
            $options = get_option('crea-listing-values');
            $table_name = $wpdb->prefix ."crea_properties";
            $myrows = $wpdb->get_results( "SELECT data,photo_data,url FROM $table_name where username = '{$attr['user']}' and post_status = 'publish' and data is not null LIMIT 0,{$options['number_of_listing']}" );
            $count = $wpdb->get_var( "SELECT count(*) FROM $table_name where username = '{$attr['user']}' and post_status = 'publish' and data is not null " );
            
            if(!isset($options['number_of_listing'])){$options['number_of_listing'] = 5;}
            $pagination_count = ceil($count/$options['number_of_listing']);
            foreach($myrows as $row){
               $data = unserialize(stripslashes($row->data));
               $photo_data = unserialize(stripslashes($row->photo_data));
               if(isset($data['listing_id'][0])){$mls = $data['listing_id'][0];}else{$mls = '<b> ';}
               if(isset($data['building_heating_type'][0])){$heat = 'Heating/AC: <b> '.$data['building_heating_type'][0]; }else{$heat = ' ';}
               if(isset($data['building_type'][0])){$btype = 'Style: <b> '.$data['building_type'][0]; }else{$btype = ' ';}
               if(isset($data['features'][0])){$feature = 'Out Door: <b> '.$data['features'][0]; }else{$feature = ' ';}
               if(isset($data['building_bedroom'][0])){$bedroom = $data['building_bedroom'][0]; }else{$bedroom = '0';}
               if(isset($data['building_bathroom'][0])){$bathroom = $data['building_bathroom'][0]; }else{$bathroom = '0';}
               if(isset($data['land_address'][0])){$laddress = $data['land_address'][0]; }else{$laddress = ' ';}
               if(isset($data['land_city'][0])){$city = $data['land_city'][0]; }else{$city = ' ';}
               if(isset($data['land_country'][0])){$country = $data['land_country'][0]; }else{$country = ' ';}
               if(isset($data['land_postcode'][0])){$postcode = $data['land_postcode'][0]; }else{$postcode = ' ';}
               if(isset($data['land_size'][0])){$lsize= 'Property Size:   <b> '.$data['land_size'][0]; }else{$lsize = ' ';}
               if(isset($data['building_size'][0])){$bsize = 'Building Size:   <b> '.$data['building_size'][0]; }else{$bsize = ' ';}
               if(isset($photo_data[0])){$image = $photo_data[0];}else{$image = ' ';}
               if(isset($data['price'][0])){$price =$data['price'][0]; }else{$price = '0.00';}
               if(isset($data['property_type'][0])){$type= $data['property_type'][0]; }else{$type = ' ';}
               if(isset($data['public_remark'][0])){$public_remark = $data['public_remark'][0]; }else{$public_remark = ' ';}
               if(isset($data['agent_name'][0])){$agent = $data['agent_name'][0]; }else{$agent = ' ';}
               if(isset($data['agent_email'][0])){$agent_email = $data['agent_email'][0]; }else{$agent_email = ' ';}
               $words = preg_split('/\s+/', $public_remark);
               $words = array_slice($words, 0, 35);
               $words2 = implode(' ', $words);             
               $html .='<table border="1" cellspacing="0" cellpadding="0" class="table">';
               $html .='<tbody>';
               $html .='<tr>';
               $html .='<td colspan="2"><div class="add-city"><h4>'.$laddress.', <span>' .$city.'</h4></span>';
               $html .='<ul class="fr qnty"><li><img src="'.plugins_url( 'css/bedroom_icon.png', __FILE__ ).'" alt="" /><span>'.$bedroom.'</span> </li><li><img src="'.plugins_url( 'css/wash_icon.png', __FILE__ ).'" alt="" /> <span class="qnty-count">'.$bathroom.'</span></li></ul></div></td>';
               $html .='</tr>';
               $html .='<tr>';
               $html .='<td><p><img src="'.$image.'" alt="" /></p> <p>MLS#: <span class="tb">'.$mls.'</span></p><p>Contact: <a href="mailto:'.$agent_email.'">'.$agent.'</a></p></td>';
               $html .='<td> <p class="tb">$'.$price.'<span class="fr">'.$type.'</span></p><p>'.$words2.'… <a id="id_'.$data['property_id'][0].'" href="'.$row->url.'" class="details">more</a></p> <p class="fr btn-read-more"><a href="'.$row->url.'" id="id_'.$data['property_id'][0].'" class="more details">More Details</a></p></td>';
               $html .='</tr>';
               $html .='</tbody>';
               $html .='</table>';    
              }
                
            return '<div id="results">'.$html."<div class='pagination'><span id='".$attr['user']."_2'  class='clickable'>Next</span></div></div>";
        }
        
        /**
         * Pagination data
         */
        function crea_pagination() {

            $ids = explode("_",$_POST['id']);
            global $wpdb;
            $html = '';
            $pagination_count = 0;
            $users = get_option($this->option_name_users);
            $options = $users[$ids[0]];
            $table_name = $wpdb->prefix ."crea_properties";
            
            $myrows = $wpdb->get_results( "SELECT data,photo_data,url FROM $table_name where username = '{$ids[0]}' and post_status = 'publish' and data is not null LIMIT ".(($options['number_of_listing']*($ids[1]-1))).",".($options['number_of_listing'])."" );
            $count = $wpdb->get_var( "SELECT count(*) FROM $table_name where username = '{$ids[0]}' and post_status = 'publish' and data is not null" );
            
            if(!isset($options['number_of_listing'])){
            	$options['number_of_listing'] = 5;
            }
            $pagination_count = ceil($count/$options['number_of_listing']);

            if($ids[1] >= $pagination_count){
                $next = '';
            }
            else{
                $next = "<span id='".$ids[0]."_".($ids[1]+1)."'  class='clickable'>Next</span>";
            }
            if($ids[1] <= 1){
                $previous = '';
            }
            else{
                $previous = "<span id='".$ids[0]."_".($ids[1]-1)."' class='clickable'>Previous</span>";
            }
            
            foreach($myrows as $row){
               $data = unserialize(stripslashes($row->data));
               $photo_data = unserialize(stripslashes($row->photo_data));
               if(isset($data['listing_id'][0])){$mls = $data['listing_id'][0];}else{$mls = '<b> ';}
               if(isset($data['building_heating_type'][0])){$heat = 'Heating/AC: <b> '.$data['building_heating_type'][0]; }else{$heat = ' ';}
               if(isset($data['building_type'][0])){$btype = 'Style: <b> '.$data['building_type'][0]; }else{$btype = ' ';}
               if(isset($data['features'][0])){$feature = 'Out Door: <b> '.$data['features'][0]; }else{$feature = ' ';}
               if(isset($data['building_bedroom'][0])){$bedroom = $data['building_bedroom'][0]; }else{$bedroom = ' ';}
               if(isset($data['building_bathroom'][0])){$bathroom = $data['building_bathroom'][0]; }else{$bathroom = ' ';}
               if(isset($data['land_address'][0])){$laddress = $data['land_address'][0]; }else{$laddress = ' ';}
               if(isset($data['land_city'][0])){$city = $data['land_city'][0]; }else{$city = ' ';}
               if(isset($data['land_country'][0])){$country = $data['land_country'][0]; }else{$country = ' ';}
               if(isset($data['land_postcode'][0])){$postcode = $data['land_postcode'][0]; }else{$postcode = ' ';}
               if(isset($data['land_size'][0])){$lsize= 'Property Size:   <b> '.$data['land_size'][0]; }else{$lsize = ' ';}
               if(isset($data['building_size'][0])){$bsize = 'Building Size:   <b> '.$data['building_size'][0]; }else{$bsize = ' ';}
               if(isset($photo_data[0])){$image = $photo_data[0];}else{$image = ' ';}
               if(isset($data['price'][0])){$price =$data['price'][0]; }else{$price = '0.00';}
               if(isset($data['property_type'][0])){$type= $data['property_type'][0]; }else{$type = ' ';}
               if(isset($data['public_remark'][0])){$public_remark = $data['public_remark'][0]; }else{$public_remark = ' ';}
               if(isset($data['agent_name'][0])){$agent = $data['agent_name'][0]; }else{$agent = ' ';}
               if(isset($data['agent_email'][0])){$agent_email = $data['agent_email'][0]; }else{$agent_email = ' ';}
               $words = preg_split('/\s+/', $public_remark);
               $words = array_slice($words, 0, 35);
               $words2 = implode(' ', $words);             
               
               $html .='<table border="1" cellspacing="0" cellpadding="0" class="table">';
               $html .='<tbody>';
               $html .='<tr>';
               $html .='<td colspan="2"><div class="add-city"><h4>'.$laddress.', <span>' .$city.'</h4></span>';
               $html .='<ul class="fr qnty"><li><img src="'.plugins_url( 'css/bedroom_icon.png', __FILE__ ).'" alt="" /><span>'.$bedroom.'</span> </li><li><img src="'.plugins_url( 'css/wash_icon.png', __FILE__ ).'" alt="" /> <span>'.$bathroom.'</span></li></ul></div></td>';
               $html .='</tr>';
               $html .='<tr>';
               $html .='<td><p><img src="'.$image.'" alt="" /></p> <p>MLS#: <span class="tb">'.$mls.'</span></p><p>Contact: <a href="mailto:'.$agent_email.'">'.$agent.'</a></p></td>';
               $html .='<td> <p class="tb">$'.$price.'<span class="fr">'.$type.'</span></p><p>'.$words2.'… <a id="id_'.$data['property_id'][0].'" href="'.$row->url.'" class="details">more</a></p> <p class="fr btn-read-more"><a href="'.$row->url.'" id="id_'.$data['property_id'][0].'" class="more details">More Details</a></p></td>';
               $html .='</tr>';
               $html .='</tbody>';
               $html .='</table>';
            }

            echo $html."<div class='pagination'>".$previous." ".$next."</div>";
            die();
        }
        
        /**
         * get property details
         */
        public function property_details(){

           global $wpdb;
           $html2 = ' ';
           $ids = explode("_",$_POST['id']);
           $table_name = $wpdb->prefix ."crea_properties";
           $row = $wpdb->get_row( "SELECT data,photo_data FROM $table_name where data is not null and idproperty = {$ids[1]}" );
           
           $data = unserialize(stripslashes($row->data));
           $photo_data = unserialize(stripslashes($row->photo_data));
           if(isset($data['listing_id'][0])){$mls = $data['listing_id'][0];}else{$mls = ' ';}
           if(isset($data['building_heating_type'][0])){$heat = $data['building_heating_type'][0]; }else{$heat = ' ';}
           if(isset($data['building_type'][0])){$btype = $data['building_type'][0]; }else{$btype = ' ';}
           if(isset($data['features'][0])){$feature = $data['features'][0]; }else{$feature = ' ';}
           if(isset($data['building_bedroom'][0])){$bedroom = $data['building_bedroom'][0]; }else{$bedroom = ' ';}
           if(isset($data['building_bathroom'][0])){$bathroom = $data['building_bathroom'][0]; }else{$bathroom = ' ';}
           if(isset($data['land_address'][0])){$laddress = $data['land_address'][0]; }else{$laddress = ' ';}
           if(isset($data['land_city'][0])){$city = $data['land_city'][0]; }else{$city = ' ';}
           if(isset($data['land_country'][0])){$country = $data['land_country'][0]; }else{$country = ' ';}
           if(isset($data['land_province'][0])){$province = $data['land_province'][0]; }else{$province = ' ';}
           if(isset($data['land_postcode'][0])){$postcode = $data['land_postcode'][0]; }else{$postcode = ' ';}
           if(isset($data['land_size'][0])){$lsize= $data['land_size'][0]; }else{$lsize = ' ';}
           if(isset($data['property_type'][0])){$type= $data['property_type'][0]; }else{$type = ' ';}
           if(isset($data['building_size'][0])){$bsize = $data['building_size'][0]; }else{$bsize = ' ';}
           if(isset($data['price'][0])){$price = $data['price'][0]; }else{$price = ' ';}
           if(isset($data['land_street'][0])){$street = $data['land_street'][0]; }else{$street = ' ';}
           if(isset($data['building_cooling'][0])){$bcooling = $data['building_cooling'][0]; }else{$bcooling = ' ';}
           if(isset($data['building_heating_type'][0])){$bheating = $data['building_heating_type'][0]; }else{$bheating = ' ';}
           if(isset($data['farmtype'][0])){$farmtype = $data['farmtype'][0]; }else{$farmtype  = ' ';}
           if(isset($data['viewtype'][0])){$viewtype = $data['viewtype'][0]; }else{$viewtype = ' ';}
           if(isset($data['waterfront'][0])){$waterfront = $data['waterfront'][0]; }else{$waterfront = ' ';}
           if(isset($data['public_remark'][0])){$public_remark = $data['public_remark'][0]; }else{$public_remark = ' ';}
           if(isset($data['agent_name'][0])){$agent = $data['agent_name'][0]; }else{$agent = ' ';}
           if(isset($data['agent_email'][0])){$agent_email = $data['agent_email'][0]; }else{$agent_email = ' ';}
           if(isset($data['agent_phone'][0])){$agent_phone = $data['agent_phone'][0]; }else{$agent_phone = ' ';}
           if(isset($data['agent_address1'][0])){$agent_address = $data['agent_address1'][0]; }else{$agent_address = ' ';}
           if(isset($data['agent_city'][0])){$agent_city = $data['agent_city'][0]; }else{$agent_city = ' ';}
           if(isset($data['office_name'][0])){$office = $data['office_name'][0]; }else{$office = ' ';}
           
           $photo_html = '<ul class="slides">';
           if(isset($photo_data) && !empty($photo_data)){
               foreach($photo_data as $pho){
                   $photo_html .= ' <li><img src="'.$pho.'" class="thumbimages" alt="" /></li>';
               }
           }
           $photo_html .= ' </ul>';
           if(isset($photo_data[0])){$image = $photo_data[0];}else{$image = ' ';}

               $html2 .='<table border="1" cellspacing="0" cellpadding="0" class="table">';
               $html2 .='<tbody>'; 
               $html2 .='<tr>';
               $html2 .='<td colspan="2"><h2>'.$laddress.','.$city.'</h2><p class="tb">$'.$price.'</p></td>';              
               $html2 .='</tr>';        
               $html2 .='<tr>';
               $html2 .='<td colspan="2"><div class="slider"> <div id="slider" class="flexslider">'.$photo_html.'</div>  <div id="carousel" class="flexslider">'.$photo_html.'</div></div></td>';
               $html2 .='</tr>';
               $html2 .='<tr>';
               $html2 .='<td><h3>At A Glance</h3><ul><li>MLS#:<span class="tb">'.$mls.'</span></li><li>'.$type.'</li><li>'.$bedroom.' Bedrooms</li><li>'.$bathroom.' Baths</li><li>'.$bsize.'</li></ul></td>';
               $html2 .='<td><h3>Arrange a viewing</h3><ul class="soffer-view"><li>'.$agent.'</li><li>'.$office.'</li><li>'.$agent_phone.'</li><li>705.441.0950</li><li><a href="mailto:'.$agent_email.'">Email</a></li></ul></td>';
               $html2 .='</tr>';
               $html2 .='<tr>';
               $html2 .='<td colspan="2"><h3>Description</h3><p>'.$public_remark.'</p></td>';              
               $html2 .='</tr>';
               $html2 .='</tbody>';
               $html2 .='</table>';

            return $html2;

        }
        
        /**
         * Featured list widget
         */
        public function featured_properties($attr){
            if(!isset($attr['user']) || !isset($attr['mlsid'])){return;}
            global $wpdb;
            $html = '';
            $pagination_count = 0;
            $options = get_option('crea-listing-values');
            $table_name = $wpdb->prefix ."crea_properties";
            $myrows = $wpdb->get_results( "SELECT data,photo_data,url,post_status FROM $table_name where username = '{$attr['user']}' and post_status = 'publish' and data is not null LIMIT 0,{$options['number_of_listing']}" );
           
            foreach($myrows as $row){

                   
                   $url=$row->url;
                   $data = unserialize(stripslashes($row->data));
                   if($data['listing_id'][0] != $attr['mlsid'] ){
                       continue;
                   }
                   $photo_data = unserialize(stripslashes($row->photo_data));
                   $images = ' ';
                   if(isset($data['listing_id'][0])){$mls = $data['listing_id'][0];}else{$mls = '<b> ';}
                   if(isset($data['building_heating_type'][0])){$heat = 'Heating/AC: <b> '.$data['building_heating_type'][0]; }else{$heat = ' ';}
                   if(isset($data['building_type'][0])){$btype = 'Style: <b> '.$data['building_type'][0]; }else{$btype = ' ';}
                   if(isset($data['features'][0])){$feature = 'Out Door: <b> '.$data['features'][0]; }else{$feature = ' ';}
                   if(isset($data['building_bedroom'][0])){$bedroom = $data['building_bedroom'][0]; }else{$bedroom = ' ';}
                   if(isset($data['building_bathroom'][0])){$bathroom = $data['building_bathroom'][0]; }else{$bathroom = ' ';}
                   if(isset($data['land_address'][0])){$laddress = $data['land_address'][0]; }else{$laddress = ' ';}
                   if(isset($data['land_city'][0])){$city = $data['land_city'][0]; }else{$city = ' ';}
                   if(isset($data['land_country'][0])){$country = $data['land_country'][0]; }else{$country = ' ';}
                   if(isset($data['land_postcode'][0])){$postcode = $data['land_postcode'][0]; }else{$postcode = ' ';}
                   if(isset($data['land_size'][0])){$lsize= 'Property Size:   <b> '.$data['land_size'][0]; }else{$lsize = ' ';}
                   if(isset($data['building_size'][0])){$bsize = ''.$data['building_size'][0]; }else{$bsize = ' ';}
                   if(isset($photo_data[0])){$image = $photo_data[0];}else{$image = ' ';}
                   if(isset($data['price'][0])){$price =$data['price'][0]; }else{$price = ' ';}
                   if(isset($data['property_type'][0])){$type= $data['property_type'][0]; }else{$type = ' ';}
                   if(isset($data['public_remark'][0])){$public_remark = $data['public_remark'][0]; }else{$public_remark = ' ';}
                   if(isset($data['agent_name'][0])){$agent = $data['agent_name'][0]; }else{$agent = ' ';}
                   if(isset($data['agent_email'][0])){$agent_email = $data['agent_email'][0]; }else{$agent_email = ' ';}
                   if(isset($data['agent_phone'][0])){$agent_phone = $data['agent_phone'][0]; }else{$agent_phone = ' ';}
           		   if(isset($data['agent_address1'][0])){$agent_address = $data['agent_address1'][0]; }else{$agent_address = ' ';}
            	   if(isset($data['agent_city'][0])){$agent_city = $data['agent_city'][0]; }else{$agent_city = ' ';}
                   if(isset($data['office_name'][0])){$office = $data['office_name'][0]; }else{$office = ' ';}

                   $status=$row->post_status;

                  $photo_html = '<ul class="slides">';
                 if(isset($photo_data) && !empty($photo_data)){
                 foreach($photo_data as $pho){
                   $photo_html .= ' <li><img src="'.$pho.'" class="thumbimages" alt="" /></li>';
               }
               }
           $photo_html .= ' </ul>';
                 
            }       
            if($status == 'publish'){      
              
               $html2 .='<table border="1" cellspacing="0" cellpadding="0" class="table">';
               $html2 .='<tbody>'; 
               $html2 .='<tr>';
               $html2 .='<td colspan="2"><div class="add-city"><h3>'.$laddress.', <span>' .$city.'</h3></span>';
               $html2 .='<ul class="fr qnty"><li><img src="'.plugins_url( 'css/bedroom_icon.png', __FILE__ ).'" alt="" /><span>'.$bedroom.'</span> </li><li><img src="'.plugins_url( 'css/wash_icon.png', __FILE__ ).'" alt="" /> <span>'.$bathroom.'</span></li></ul></div></td>';
               $html2 .='</tr>';       
               $html2 .='<tr>';
               $html2 .='<td colspan="2"><div class="slider"> <div id="slider" class="flexslider">'.$photo_html.'</div>  <div id="carousel" class="flexslider">'.$photo_html.'</div></div></td>';
               $html2 .='</tr>';
               $html2 .='<tr>';
               $html2 .='<td><h3>At A Glance</h3><ul><li>Price:<span class="tb">$'.$price.'</span></li><li>MLS#:<span class="tb">'.$mls.'</span></li><li>'.$type.'</li><li>'.$bsize.'</li></ul></td>';
               $html2 .='<td><h3>Arrange a viewing</h3><ul class="soffer-view"><li>'.$agent.'</li><li>'.$office.'</li><li>'.$agent_phone.'</li><li>705.441.0950</li><li><a href="mailto:'.$agent_email.'">Email</a></li></ul></td>';
               $html2 .='</tr>';
               $html2 .='<tr>';
               $html2 .='<td colspan="2"><h3>Description</h3><p>'.$public_remark.'</p></td>';              
               $html2 .='</tr>';
               $html2 .='</tbody>';
               $html2 .='</table>';
            return $html2;
            }
            
           
        }
        
        /**
         * hourly cron
         */
        public function do_this_hourly(){
            $options = get_option($option_name_users);
            foreach($options as $option){
                 if( $option['username'] != ' ' && $option['password'] != ' ' ){
                    global $wpdb;
                    $table_name = $wpdb->prefix ."crea_properties";
                    $myrow = $wpdb->get_row( "SELECT idproperty FROM $table_name where username = '{$option['username']}' and data is null" );
                    if(isset($myrow->idproperty)){
                        if( $this->Connect($option['username'], $option['password']) ){
                            $property_id = '';
                            $myrows = $wpdb->get_results( "SELECT idproperty FROM $table_name where username='{$option['username']}' and idproperty is not null Limit 0,20" );
                            $i = 0;
                            foreach($myrows as $row){
                                if($i==0){
                                    $property_id = $row->idproperty;
                                }
                                else{
                                    $property_id = $row->idproperty.','.$property_id;
                                }
                                $i++;
                            }
                            $this->SearchResidentialProperty("ID=$property_id", true, $option['username'], $option['password']);
                            $this->Disconnect();
                        }
                    }
                    $myrow = $wpdb->get_row( "SELECT idproperty FROM $table_name where username = '{$option['username']}' and photodata is null" );
                    if(isset($myrow->idproperty)){
                        if( $this->Connect($option['username'], $option['password']) ){
                                 $this->GetPropertyObject($options['username'],  $options['password']);
                                 $this->Disconnect();
                         }
                    }
                }
            } 
        }
        
        /**
         * daily cron
         */
        public function do_this_daily(){
            $options = get_option($option_name_users);
            foreach($options as $option){
                 if( $option['username'] != ' ' && $option['password'] != ' ' ){
                    global $wpdb;
                    $table_name = $wpdb->prefix ."crea_properties";
                    $myrow = $wpdb->get_row( "SELECT idproperty FROM $table_name where username = '{$option['username']}'" );
                      if(isset($myrow->idproperty)){
                        if( $this->Connect($option['username'], $option['password']) ){
                            $this->SearchResidentialPropertiesUpdatedSince(1,$option['username'], $option['password']);
                            $this->Disconnect();
                         }
                      }
                }
            }
            $this->crea_create_posts();
        }
        
        /**
         * Run daily cron for creating posts from data 
         */
        public function crea_create_posts(){
            global $wpdb;
            $table_name = $wpdb->prefix ."crea_properties";
            $myrows = $wpdb->get_results( "SELECT idproperty FROM $table_name where data is not null" );
            $cat = get_cat_ID( trim($this->crea_properties) );
            
            foreach($myrows as $myrow){
                $get_property_data = $this->crea_property_details($myrow->idproperty);
                $crea_post = query_posts( array(
                            'meta_query'=> array(
                                                    array(
                                                      'key' => 'property_id',
                                                      'compare' => '=',
                                                      'value' => $myrow->idproperty,
                                                      'type' => 'numeric',
                                                    ),
                                                 ),
                              'cat' => $cat                   
                ) );
                if(!empty($crea_post)){
                        $cpost = array(
                            'ID' => $crea_post[0]->ID,
                            'post_content' => $get_property_data['post_content'],
                        );
                        $post_id = wp_update_post( $cpost );
                }else{
                        $content = $get_property_data['post_content'];
                        $cpost = array(
                            'post_title' => wp_strip_all_tags($get_property_data['post_title']),
                            'post_status' => 'publish',
                            'post_content' =>  "$content",
                            'post_author' => 1,
                            'post_category' => array( $cat ),
                            'post_date' => date('Y-m-d H:i:s')
                        );
                        
                        $post_id = wp_insert_post( $cpost );
                        if($post_id != 0)
                        update_post_meta($post_id, 'property_id', $myrow->idproperty);
                }
                $url = get_permalink($post_id);
                $result = $wpdb->query("UPDATE $table_name SET url='{$url}' where idproperty = $myrow->idproperty");
            }
        }
        
        /**
         * get all property data and return html
         */
        public function crea_property_details($id){
           global $wpdb;
           $html2 = ' ';
           $table_name = $wpdb->prefix ."crea_properties";
           $row = $wpdb->get_row( "SELECT data,photo_data FROM $table_name where data is not null and idproperty = {$id}" );
           
           $data = unserialize(stripslashes($row->data));
           $photo_data = unserialize(stripslashes($row->photo_data));
           if(isset($data['listing_id'][0])){$mls = $data['listing_id'][0];}else{$mls = ' ';}
           if(isset($data['building_heating_type'][0])){$heat = $data['building_heating_type'][0]; }else{$heat = ' ';}
           if(isset($data['building_type'][0])){$btype = $data['building_type'][0]; }else{$btype = ' ';}
           if(isset($data['features'][0])){$feature = $data['features'][0]; }else{$feature = ' ';}
           if(isset($data['building_bedroom'][0])){$bedroom = $data['building_bedroom'][0]; }else{$bedroom = ' ';}
           if(isset($data['building_bathroom'][0])){$bathroom = $data['building_bathroom'][0]; }else{$bathroom = ' ';}
           if(isset($data['land_address'][0])){$laddress = $data['land_address'][0]; }else{$laddress = ' ';}
           if(isset($data['land_city'][0])){$city = $data['land_city'][0]; }else{$city = ' ';}
           if(isset($data['land_country'][0])){$country = $data['land_country'][0]; }else{$country = ' ';}
           if(isset($data['land_province'][0])){$province = $data['land_province'][0]; }else{$province = ' ';}
           if(isset($data['land_postcode'][0])){$postcode = $data['land_postcode'][0]; }else{$postcode = ' ';}
           if(isset($data['land_size'][0])){$lsize= $data['land_size'][0]; }else{$lsize = ' ';}
           if(isset($data['property_type'][0])){$type= $data['property_type'][0]; }else{$type = ' ';}
           if(isset($data['building_size'][0])){$bsize = $data['building_size'][0]; }else{$bsize = ' ';}
           if(isset($data['price'][0])){$price = $data['price'][0]; }else{$price = ' ';}
           if(isset($data['land_street'][0])){$street = $data['land_street'][0]; }else{$street = ' ';}
           if(isset($data['building_cooling'][0])){$bcooling = $data['building_cooling'][0]; }else{$bcooling = ' ';}
           if(isset($data['building_heating_type'][0])){$bheating = $data['building_heating_type'][0]; }else{$bheating = ' ';}
           if(isset($data['farmtype'][0])){$farmtype = $data['farmtype'][0]; }else{$farmtype  = ' ';}
           if(isset($data['viewtype'][0])){$viewtype = $data['viewtype'][0]; }else{$viewtype = ' ';}
           if(isset($data['waterfront'][0])){$waterfront = $data['waterfront'][0]; }else{$waterfront = ' ';}
           if(isset($data['public_remark'][0])){$public_remark = $data['public_remark'][0]; }else{$public_remark = ' ';}
           if(isset($data['agent_name'][0])){$agent = $data['agent_name'][0]; }else{$agent = ' ';}
           if(isset($data['agent_email'][0])){$agent_email = $data['agent_email'][0]; }else{$agent_email = ' ';}
           if(isset($data['agent_phone'][0])){$agent_phone = $data['agent_phone'][0]; }else{$agent_phone = ' ';}
           if(isset($data['agent_address1'][0])){$agent_address = $data['agent_address1'][0]; }else{$agent_address = ' ';}
           if(isset($data['agent_city'][0])){$agent_city = $data['agent_city'][0]; }else{$agent_city = ' ';}
           if(isset($data['office_name'][0])){$office = $data['office_name'][0]; }else{$office = ' ';}
           
           $photo_html = '<ul class="slides">';
           if(isset($photo_data) && !empty($photo_data)){
               foreach($photo_data as $pho){
                   $photo_html .= ' <li><img src="'.$pho.'" class="thumbimages" alt="" /></li>';
               }
           }
           $photo_html .= ' </ul>';
           if(isset($photo_data[0])){
           	$image = $photo_data[0];
           }
           else{
           	$image = ' ';
           } 
               $html2 .='<table border="1" cellspacing="0" cellpadding="0" class="table">';
               $html2 .='<tbody>';         
               $html2 .='<tr>';
               $html2 .='<td colspan="2"><div class="slider"> <div id="slider" class="flexslider">'.$photo_html.'</div>  <div id="carousel" class="flexslider">'.$photo_html.'</div></div></td>';
               $html2 .='</tr>';
               $html2 .='<tr>';
               $html2 .='<td><h3>At A Glance</h3><ul><li>Price:<span class="tb">$'.$price.'</span></li><li>MLS#:<span class="tb">'.$mls.'</span></li><li>'.$type.'</li><li>'.$bedroom.' Bedrooms</li><li>'.$bathroom.' Baths</li><li>'.$bsize.'</li></ul></td>';
               $html2 .='<td><h3>Arrange a viewing</h3><ul class="soffer-view"><li>'.$agent.'</li><li>'.$office.'</li><li>'.$agent_phone.'</li><li>705.441.0950</li><li><a href="mailto:'.$agent_email.'">Email</a></li></ul></td>';
               $html2 .='</tr>';
               $html2 .='<tr>';
               $html2 .='<td colspan="2"><h3>Description</h3><p>'.$public_remark.'</p></td>';              
               $html2 .='</tr>';
               $html2 .='</tbody>';
               $html2 .='</table>';

               $html['post_content'] = $html2;
               $html['post_title'] = $laddress.','.$city;
             
           return $html;
        }
        
        /**
         * adds crea properties category 
         */
        function crea_insert_category() {
            $term = term_exists($this->crea_properties, 'category');
            if ($term == 0 ) {
              wp_insert_term(
                    $this->crea_properties,
                    'category',
                    array(
                      'description'	=> 'This category comprises all properties fetched from CREA Listings',
                      'slug' 		=> 'crea-properties'
                    )
               );
            }
        }
        
        /**
         * Remove posts from this category to get display
         */
        function exclude_category($query) {
            $cat = get_cat_ID( trim($this->crea_properties) );
            $query->set('cat', '-'.$cat);
            return $query;
        }

        /**
         * Ajax function to view user property list on backend
         */
        function view_list_callback(){

	       	$name=$_POST['content'];
	        $users = get_option($this->option_name_users);  
	        $user=  $users[$name];	

		    $html .= '';	
		    $html .= '<h3>View "'.$user['fullname'].'" Property List';
		    $html .= '<a href="">Back to Crea Listing</a></h3>';
		    $html .='<table class="table table-striped widefat" id="testre">';
		    $html .='<thead>';
			$html .= '<tr>';
			$html .='<th>User Name</th>';
			$html .='<th>MLS(Listing Id)</th>';
			$html .='<th>Address</th>';
			$html .='<th>City</th>';
			$html .='<th>Price</th>';
			$html .='<th>Status</th>';
			$html .= '</tr>';
			$html .='</thead>';
			$html .= '<tbody> ';
			  
			global $wpdb;
			$table_name = $wpdb->prefix ."crea_properties";
		    $myrows = $wpdb->get_results( "SELECT * FROM $table_name where username ='".$name."'" );
			foreach($myrows as $row) {

			    $data = unserialize(stripslashes($row->data));
				$photo_data = unserialize(stripslashes($row->photo_data));
				if(isset($data['listing_id'][0])){$mls = $data['listing_id'][0];}else{$mls = '<b> ';}
				if(isset($data['building_heating_type'][0])){$heat = 'Heating/AC: <b> '.$data['building_heating_type'][0]; }else{$heat = ' ';}
				if(isset($data['building_type'][0])){$btype = 'Style: <b> '.$data['building_type'][0]; }else{$btype = ' ';}
				if(isset($data['features'][0])){$feature = 'Out Door: <b> '.$data['features'][0]; }else{$feature = ' ';}
				if(isset($data['building_bedroom'][0])){$bedroom = $data['building_bedroom'][0]; }else{$bedroom = '0';}
				if(isset($data['building_bathroom'][0])){$bathroom = $data['building_bathroom'][0]; }else{$bathroom = '0';}
				if(isset($data['land_address'][0])){$laddress = $data['land_address'][0]; }else{$laddress = ' ';}
				if(isset($data['land_city'][0])){$city = $data['land_city'][0]; }else{$city = ' ';}
				if(isset($data['land_country'][0])){$country = $data['land_country'][0]; }else{$country = ' ';}
				if(isset($data['land_postcode'][0])){$postcode = $data['land_postcode'][0]; }else{$postcode = ' ';}
				if(isset($data['land_size'][0])){$lsize= 'Property Size:   <b> '.$data['land_size'][0]; }else{$lsize = ' ';}
				if(isset($data['building_size'][0])){$bsize = 'Building Size:   <b> '.$data['building_size'][0]; }else{$bsize = ' ';}
				if(isset($photo_data[0])){$image = $photo_data[0];}else{$image = ' ';}
				if(isset($data['price'][0])){$price =$data['price'][0]; }else{$price = '0.00';}
				if(isset($data['property_type'][0])){$type= $data['property_type'][0]; }else{$type = ' ';}
				if(isset($data['public_remark'][0])){$public_remark = $data['public_remark'][0]; }else{$public_remark = ' ';}
				if(isset($data['agent_name'][0])){$agent = $data['agent_name'][0]; }else{$agent = ' ';}
				if(isset($data['agent_email'][0])){$agent_email = $data['agent_email'][0]; }else{$agent_email = ' ';}
				$words = preg_split('/\s+/', $public_remark);
				$words = array_slice($words, 0, 35);
				$words2 = implode(' ', $words);             
	            $status=$row->post_status;

				$html .= '<tr>';
				$html .='<td>'.$user['fullname'].' </td>';
				$html .='<td>'.$mls .'</td>';
				$html .='<td>'. $laddress .'</td>';
				$html .='<td>'. $city .'</td>';
				$html .='<td>$'.$price.'</td>';
	            if($status == 'publish'){
					$html .='<td style="float:left;"><a href="javascript:void(0);" id="'.$name.'_'.$row->idproperty.'" class="hide-data button-primary">Hide</a></td>';
				}
				else{
					$html .='<td style="float:left;"><a href="javascript:void(0);" id="'.$name.'_'.$row->idproperty.'" class="show-data button-primary">Show</a></td>';
				}
				$html .='</tr>';
			} 

			$html .='<tfoot>';
			$html .= '<tr>';
			$html .='<th>User Name</th>';
			$html .='<th>MLS(Listing Id)</th>';
			$html .='<th>Address</th>';
			$html .='<th>City</th>';
			$html .='<th>Price</th>';
			$html .='<th>Status</th>';
			$html .= '</tr>';
			$html .='</tfoot>';
			$html .= '</tbody> ';
            $html .= '</table>';

			echo $html;
			die(0); 
 		}

 		/**
         * Ajax function to hide user property from frontend
         */
		function hide_list_callback(){

	       	$splitcontent=$_POST['content']; 
	       	$split = explode('_', $splitcontent);
	       	$name1= $split[1]; 

	        global $wpdb;
			$table_name = $wpdb->prefix ."crea_properties";      	
	        $result = $wpdb->query("UPDATE $table_name SET post_status='draft' where idproperty = '".$name1."'");
	   	    $name=$split[0];;
	        $users = get_option($this->option_name_users);  
	        $user=  $users[$name];

		    $html .= '';	
		    $html .= '<h3>View "'.$user['fullname'].'" Property List';
		    $html .= '<a href="">Back to Crea Listing</a></h3>';
		    $html .='<table class="table table-striped widefat" id="testre">';
		    $html .='<thead>';
			$html .= '<tr>';
			$html .='<th>User Name</th>';
			$html .='<th>MLS(Listing Id)</th>';
			$html .='<th>Address</th>';
			$html .='<th>City</th>';
			$html .='<th>Price</th>';
			$html .='<th>Status</th>';
			$html .= '</tr>';
			$html .='</thead>';
			$html .= '<tbody> ';
		 
			$table_name = $wpdb->prefix ."crea_properties";
			$myrows = $wpdb->get_results( "SELECT * FROM $table_name where username ='".$name."'" );
			foreach($myrows as $row) {
			    $data = unserialize(stripslashes($row->data));
				$photo_data = unserialize(stripslashes($row->photo_data));
				if(isset($data['listing_id'][0])){$mls = $data['listing_id'][0];}else{$mls = '<b> ';}
				if(isset($data['building_heating_type'][0])){$heat = 'Heating/AC: <b> '.$data['building_heating_type'][0]; }else{$heat = ' ';}
				if(isset($data['building_type'][0])){$btype = 'Style: <b> '.$data['building_type'][0]; }else{$btype = ' ';}
				if(isset($data['features'][0])){$feature = 'Out Door: <b> '.$data['features'][0]; }else{$feature = ' ';}
				if(isset($data['building_bedroom'][0])){$bedroom = $data['building_bedroom'][0]; }else{$bedroom = '0';}
				if(isset($data['building_bathroom'][0])){$bathroom = $data['building_bathroom'][0]; }else{$bathroom = '0';}
				if(isset($data['land_address'][0])){$laddress = $data['land_address'][0]; }else{$laddress = ' ';}
				if(isset($data['land_city'][0])){$city = $data['land_city'][0]; }else{$city = ' ';}
				if(isset($data['land_country'][0])){$country = $data['land_country'][0]; }else{$country = ' ';}
				if(isset($data['land_postcode'][0])){$postcode = $data['land_postcode'][0]; }else{$postcode = ' ';}
				if(isset($data['land_size'][0])){$lsize= 'Property Size:   <b> '.$data['land_size'][0]; }else{$lsize = ' ';}
				if(isset($data['building_size'][0])){$bsize = 'Building Size:   <b> '.$data['building_size'][0]; }else{$bsize = ' ';}
				if(isset($photo_data[0])){$image = $photo_data[0];}else{$image = ' ';}
				if(isset($data['price'][0])){$price =$data['price'][0]; }else{$price = '0.00';}
				if(isset($data['property_type'][0])){$type= $data['property_type'][0]; }else{$type = ' ';}
				if(isset($data['public_remark'][0])){$public_remark = $data['public_remark'][0]; }else{$public_remark = ' ';}
				if(isset($data['agent_name'][0])){$agent = $data['agent_name'][0]; }else{$agent = ' ';}
				if(isset($data['agent_email'][0])){$agent_email = $data['agent_email'][0]; }else{$agent_email = ' ';}
				$words = preg_split('/\s+/', $public_remark);
				$words = array_slice($words, 0, 35);
				$words2 = implode(' ', $words);             
	            $status=$row->post_status;

				$html .= '<tr>';
				$html .='<td>'.$user['fullname'].' </td>';
				$html .='<td>'.$mls .'</td>';
				$html .='<td>'. $laddress .'</td>';
				$html .='<td>'. $city .'</td>';
				$html .='<td>$'.$price.'</td>';
	            if($status == 'publish'){
					$html .='<td style="float:left;"><a href="javascript:void(0);" id="'.$name.'_'.$row->idproperty.'" class="hide-data button-primary">Hide</a></td>';
				}
				else{
					$html .='<td style="float:left;"><a href="javascript:void(0);" id="'.$name.'_'.$row->idproperty.'" class="show-data button-primary">Show</a></td>';
				}
				$html .='</tr>';
			}  

			$html .='<tfoot>';
			$html .= '<tr>';
			$html .='<th>User Name</th>';
			$html .='<th>MLS(Listing Id)</th>';
			$html .='<th>Address</th>';
			$html .='<th>City</th>';
			$html .='<th>Price</th>';
			$html .='<th>Status</th>';
			$html .= '</tr>';
			$html .='</tfoot>';
			$html .= '</tbody> ';
            $html .= '</table>';

			echo $html;
			 die(0); 	          

    	}  

    	/**
         * Ajax function to show user hide-property from frontend
         */
		function show_list_callback(){

	       	$splitcontent=$_POST['content']; 
	       	$split = explode('_', $splitcontent);
	       	$name1= $split[1]; 

	        global $wpdb;
			$table_name = $wpdb->prefix ."crea_properties";      	
	        $result = $wpdb->query("UPDATE $table_name SET post_status='publish' where idproperty = '".$name1."'");

	        $name=$split[0]; 
	        $users = get_option($this->option_name_users);  
	        $user=  $users[$name];
		      
		    $html .= '';	
		    $html .= '<h3>View "'.$user['fullname'].'" Property List';
		    $html .= '<a href="">Back to Crea Listing</a></h3>';
		    $html .='<table class="table table-striped widefat" id="testre">';
		    $html .='<thead>';
			$html .= '<tr>';
			$html .='<th>User Name</th>';
			$html .='<th>MLS(Listing Id)</th>';
			$html .='<th>Address</th>';
			$html .='<th>City</th>';
			$html .='<th>Price</th>';
			$html .='<th>Status</th>';
			$html .= '</tr>';
			$html .='</thead>';
			$html .= '<tbody> ';  
			 
			$table_name = $wpdb->prefix ."crea_properties";
			$myrows = $wpdb->get_results( "SELECT * FROM $table_name where username ='".$name."'" );
			foreach($myrows as $row) {
			    $data = unserialize(stripslashes($row->data));
				$photo_data = unserialize(stripslashes($row->photo_data));
				if(isset($data['listing_id'][0])){$mls = $data['listing_id'][0];}else{$mls = '<b> ';}
				if(isset($data['building_heating_type'][0])){$heat = 'Heating/AC: <b> '.$data['building_heating_type'][0]; }else{$heat = ' ';}
				if(isset($data['building_type'][0])){$btype = 'Style: <b> '.$data['building_type'][0]; }else{$btype = ' ';}
				if(isset($data['features'][0])){$feature = 'Out Door: <b> '.$data['features'][0]; }else{$feature = ' ';}
				if(isset($data['building_bedroom'][0])){$bedroom = $data['building_bedroom'][0]; }else{$bedroom = '0';}
				if(isset($data['building_bathroom'][0])){$bathroom = $data['building_bathroom'][0]; }else{$bathroom = '0';}
				if(isset($data['land_address'][0])){$laddress = $data['land_address'][0]; }else{$laddress = ' ';}
				if(isset($data['land_city'][0])){$city = $data['land_city'][0]; }else{$city = ' ';}
				if(isset($data['land_country'][0])){$country = $data['land_country'][0]; }else{$country = ' ';}
				if(isset($data['land_postcode'][0])){$postcode = $data['land_postcode'][0]; }else{$postcode = ' ';}
				if(isset($data['land_size'][0])){$lsize= 'Property Size:   <b> '.$data['land_size'][0]; }else{$lsize = ' ';}
				if(isset($data['building_size'][0])){$bsize = 'Building Size:   <b> '.$data['building_size'][0]; }else{$bsize = ' ';}
				if(isset($photo_data[0])){$image = $photo_data[0];}else{$image = ' ';}
				if(isset($data['price'][0])){$price =$data['price'][0]; }else{$price = '0.00';}
				if(isset($data['property_type'][0])){$type= $data['property_type'][0]; }else{$type = ' ';}
				if(isset($data['public_remark'][0])){$public_remark = $data['public_remark'][0]; }else{$public_remark = ' ';}
				if(isset($data['agent_name'][0])){$agent = $data['agent_name'][0]; }else{$agent = ' ';}
				if(isset($data['agent_email'][0])){$agent_email = $data['agent_email'][0]; }else{$agent_email = ' ';}
				$words = preg_split('/\s+/', $public_remark);
				$words = array_slice($words, 0, 35);
				$words2 = implode(' ', $words);             
	            $status=$row->post_status;

				$html .= '<tr>';
				$html .='<td>'.$user['fullname'].' </td>';
				$html .='<td>'.$mls .'</td>';
				$html .='<td>'. $laddress .'</td>';
				$html .='<td>'. $city .'</td>';
				$html .='<td>$'.$price.'</td>';
	            if($status == 'publish'){
					$html .='<td style="float:left;"><a href="javascript:void(0);" id="'.$name.'_'.$row->idproperty.'" class="hide-data button-primary">Hide</a></td>';
				}
				else{
					$html .='<td style="float:left;"><a href="javascript:void(0);" id="'.$name.'_'.$row->idproperty.'" class="show-data button-primary">Show</a></td>';
				}
				$html .='</tr>';
			}  

			$html .='<tfoot>';
			$html .= '<tr>';
			$html .='<th>User Name</th>';
			$html .='<th>MLS(Listing Id)</th>';
			$html .='<th>Address</th>';
			$html .='<th>City</th>';
			$html .='<th>Price</th>';
			$html .='<th>Status</th>';
			$html .= '</tr>';
			$html .='</tfoot>';
			$html .= '</tbody> ';
            $html .= '</table>';

			echo $html;
			 die(0); 	          
   		}

        /**
         * update database table whenever necessary
         */
        public function table_install(){
            global $wpdb;
            $ver = get_option( "crea_db_version" );
            $installed_ver = (isset($ver))?$ver:'1.0.0';
            
            if( $installed_ver != $this->version ) {
                
	            $crea_tablename = $wpdb->prefix . "crea_properties";
	            $crea_sql = "CREATE TABLE $crea_tablename (
							id int(11) NOT NULL AUTO_INCREMENT,
							idproperty int(16) DEFAULT NULL,
							data text,
							lastupdated datetime DEFAULT NULL,
	               			photo_flag INT( 4 ) NOT NULL DEFAULT  '0',
	               			photo_data TEXT NULL,
	                		username VARCHAR( 64 ) NULL,
	                		url VARCHAR( 256 ) NULL,
	                		post_status TEXT NULL,
							UNIQUE KEY id (id)
	                		)ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4806;";
	              
	             /* include dbdelta stuff */
	             require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	             
	             /* build the table using the variables above */
	             dbDelta( $crea_sql );
	            
	             update_option( "crea_db_version", $this->version );
            }
        }
        
        /**
         * check for database updates
         */
        function crea_db_check() {
            if (get_option( 'crea_db_version' ) != $this->version) {
                $this->table_install();
            }
        }

        /**
         * check for post updates
         */
        function crea_post_check() {
           global $wpdb;
           $table_name = $wpdb->prefix ."crea_properties";
           $row = $wpdb->get_row( "SELECT * FROM $table_name where url is null" );
           if( isset($row->idproperty) && $row->idproperty){
               $this->crea_create_posts();
           }
        }
}
        