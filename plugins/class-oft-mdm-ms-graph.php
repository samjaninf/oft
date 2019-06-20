<?php
	use Microsoft\Graph\Graph;
	use Microsoft\Graph\Model;

	if ( isset( $_GET['routecode'] ) and ! empty( $_GET['routecode'] ) ) {
		$start = microtime(true);
		$routecode = $_GET['routecode'];

		$graph_api = new Oft_Mdm_Microsoft_Graph();

		$events = $graph_api->get_events_by_routecode( $routecode );
		echo number_format( microtime(true)-$start, 4, ',', '.' )." s => EVENT REQUEST EXECUTED<br/>";
		var_dump_pre($events);

		$instances = $graph_api->get_first_instances_of_event( $events[0]->getId() );
		echo number_format( microtime(true)-$start, 4, ',', '.' )." s => INSTANCE REQUEST EXECUTED<br/>";
		
		echo "<ol>";
		foreach ( $instances as $event ) {
			echo '<li><b>'.$event->getSubject().'</b><br/>';
			// Bevat respectievelijk Microsoft\Graph\Model\DateTimeTimeZone / array van strings / Microsoft\Graph\Model\Recipient
			echo $event->getStart()->getDateTime().' &mdash; '.str_replace( 'Z', '', implode( ', ', $event->getCategories() ) ).' &mdash; '.$event->getOrganizer()->getEmailAddress()->getAddress().'</li>';
		}
		echo "</ol>";

		if ( isset( $_GET['subject'] ) and ! empty( $_GET['subject'] ) ) {
			$subject = $_GET['subject'];
		} else {
			$subject = '';
		}
		
		$instances = $graph_api->get_calendar_view_by_subject( $subject, 50 );
		echo number_format( microtime(true)-$start, 4, ',', '.' )." s => CALENDER VIEW REQUEST EXECUTED<br/>";
		
		echo "<ol>";
		foreach ( $instances as $event ) {
			echo '<li><b>'.$event->getSubject().'</b><br/>';
			// Bevat respectievelijk Microsoft\Graph\Model\DateTimeTimeZone / array van strings / Microsoft\Graph\Model\Recipient
			echo $event->getStart()->getDateTime().' &mdash; '.str_replace( 'Z', '', implode( ', ', $event->getCategories() ) ).' &mdash; '.$event->getOrganizer()->getEmailAddress()->getAddress().'</li>';
		}
		echo "</ol>";
	}

	class Oft_Mdm_Microsoft_Graph {
		protected $graph_api, $context, $timer;
		
		const USER_NAME = 'klantendienst@oft.be';
		const USER_ID = '3a4ec597-1540-4a6a-81d9-2d9ea893edb0';
		const OWW_SCHEME_ID = 'AAMkADNlZGNlY2Q3LTU4NDctNDZlMi1hMjgyLWNjMTdhN2NiZTk0ZgBGAAAAAABuULMO0-qUSYSIyb2KIl9LBwAoJ3qpsWFSSpypVo542E_lAAAAAAEGAAAoJ3qpsWFSSpypVo542E_lAAAcuXwlAAA=';
		const MDM_SCHEME_ID = 'AAMkADNlZGNlY2Q3LTU4NDctNDZlMi1hMjgyLWNjMTdhN2NiZTk0ZgBGAAAAAABuULMO0-qUSYSIyb2KIl9LBwAoJ3qpsWFSSpypVo542E_lAAAAAAEGAAAoJ3qpsWFSSpypVo542E_lAAAcuXwmAAA=';
		const SHUTTLE_SCHEME_ID = 'AAMkADNlZGNlY2Q3LTU4NDctNDZlMi1hMjgyLWNjMTdhN2NiZTk0ZgBGAAAAAABuULMO0-qUSYSIyb2KIl9LBwAoJ3qpsWFSSpypVo542E_lAAAAAAEGAAAoJ3qpsWFSSpypVo542E_lAAAcuXwnAAA=';

		function __construct() {
			$this->timer = microtime(true);
			require_once WP_PLUGIN_DIR.'/microsoft-graph/autoload.php';
			echo number_format( microtime(true) - $this->timer, 4, ',', '.' )." s => MICROSOFT GRAPH API LOADED<br/>";
			
			$guzzle = new \GuzzleHttp\Client();
			$token = json_decode( $guzzle->post(
				'https://login.microsoftonline.com/'.MS_TENANT_ID.'/oauth2/token?api-version=1.0',
				array(
					'form_params' => array(
						'client_id' => MS_CLIENT_ID,
						'client_secret' => MS_CLIENT_SECRET,
						'resource' => 'https://graph.microsoft.com',
						'grant_type' => 'client_credentials',
					)
				)
			)->getBody()->getContents() );
			echo number_format( microtime(true) - $this->timer, 4, ',', '.' )." s => ACCESS TOKEN RECEIVED<br/>";

			$this->graph_api = new Graph();
			$this->graph_api->setBaseUrl('https://graph.microsoft.com')->setApiVersion('v1.0')->setAccessToken( $token->access_token );
			$this->context = array( 'source' => 'Microsoft Graph API' );

			// Kalender-ID's veranderen per gebruiker!
			// $user_name_app = 'oxfamappuser@oww.be';
			// $user_id_app = 'b4de7e02-104b-42b4-967d-484b7fcde90d';
			// $calendars = $this->graph_api
			// 	->createRequest( 'GET', '/users/'.$user_name_app.'/calendars' )
			// 	->addHeaders( array( 'Content-Type' => 'application/json', 'Prefer' => 'outlook.timezone="Europe/Paris"' ) )
			// 	->setReturnType(Model\Event::class)
			// 	->setTimeout(10000)
			// 	->execute();
			// var_dump_pre($calendars);
		}

		function get_events_by_routecode( $routecode, $type = 'deadline' ) {
			// Single quotes gebruiken zodat dollartekens niet als variabelen geïnterpreteerd worden
			$events = $this->graph_api->createRequest( 'GET', '/users/'.self::USER_NAME.'/calendars/'.self::OWW_SCHEME_ID.'/events?$orderby=start/dateTime asc&$filter=categories/any(a:a eq \'Z'.$routecode.'\') and startswith(subject,\''.$type.'\')' )
				->addHeaders( array( 'Content-Type' => 'application/json', 'Prefer' => 'outlook.timezone="Europe/Paris"' ) )
				->setReturnType(Model\Event::class)
				->setTimeout(10000)
				->execute();

			return $this->check_api_response( $events, 'NO EVENTS FOUND' );
		}

		function get_first_instances_of_event( $event_id, $limit = 20 ) {
			$start_date = date_i18n( 'Y-m-d\TH:i:s' );
			$end_date = date_i18n( 'Y-m-d\TH:i:s', strtotime('+2 months') );
			// Dit houdt geen rekening met de lokale tijd ...
			// var_dump_pre($end_date);

			$instances = $this->graph_api->createRequest( 'GET', '/users/'.self::USER_NAME.'/calendars/'.self::OWW_SCHEME_ID.'/events/'.$event_id.'/instances?startDateTime='.$start_date.'&endDateTime='.$end_date.'&$orderby=start/dateTime asc&$top='.$limit )
				->addHeaders( array( 'Content-Type' => 'application/json', 'Prefer' => 'outlook.timezone="Europe/Paris"' ) )
				->setReturnType(Model\Event::class)
				->setTimeout(10000)
				->execute();
			
			return $this->check_api_response( $instances, 'NO INSTANCES FOUND' );
		}

		function get_calendar_view_by_subject( $type = '', $limit = 100 ) {
			$start_date = date_i18n( 'Y-m-d\TH:i:s' );
			$end_date = date_i18n( 'Y-m-d\TH:i:s', strtotime('+2 months') );
			// Dit houdt geen rekening met de lokale tijd ...
			// var_dump_pre($end_date);

			// Opgelet: startswith() is hier case sensitive!
			// Filteren op categorie resulteert in Error 500
			$instances_in_view = $this->graph_api->createRequest( 'GET', '/users/'.self::USER_NAME.'/calendars/'.self::OWW_SCHEME_ID.'/calendarView?startDateTime='.$start_date.'&endDateTime='.$end_date.'&$orderby=start/dateTime asc&$filter=startswith(subject,\''.$type.'\')&$top='.$limit )
				->addHeaders( array( 'Content-Type' => 'application/json', 'Prefer' => 'outlook.timezone="Europe/Paris"' ) )
				->setReturnType(Model\Event::class)
				->setTimeout(10000)
				->execute();
			
			return $this->check_api_response( $instances_in_view, 'NO INSTANCES IN VIEW' );
		}

		function check_api_response( $response, $error_message ) {
			$logger = wc_get_logger();
			
			// Foutmelding retourneert één object met lege values i.p.v. een (lege) array van objecten
			if ( $response instanceof Model\Event ) {
				$logger->warning( 'Fatal error', $this->context );
			} elseif ( is_array( $response ) ) {
				if ( count( $response ) > 0 ) {
					return $response;
				} else {
					$logger->warning( $error_message, $this->context );
				}
			}

			throw new Exception( $error_message );
		}
	}

?>