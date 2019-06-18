<?php
	$start = microtime(true);

	require_once '../../../../wp-load.php';
	echo number_format( microtime(true)-$start, 4, ',', '.' )." s => WORDPRESS LOADED<br/>";

	new Oft_Mdm_Microsoft_Graph();

	class Oft_Mdm_Microsoft_Graph {
		static $global_graph;

		function __construct() {
			require_once 'microsoft-graph/autoload.php';
			echo number_format( microtime(true)-$start, 4, ',', '.' )." s => MS GRAPH API LOADED<br/>";

			$logger = wc_get_logger();
			$context = array( 'source' => 'Microsoft Graph API' );
			
			$user_name = 'klantendienst@oft.be';
			$user_id = '3a4ec597-1540-4a6a-81d9-2d9ea893edb0';

			$guzzle = new GuzzleHttp\Client();
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
			echo number_format( microtime(true)-$start, 4, ',', '.' )." s => ACCESS TOKEN RECEIVED<br/>";

			$graph = new Microsoft\Graph\Graph();
			$graph->setBaseUrl('https://graph.microsoft.com')->setApiVersion('v1.0')->setAccessToken( $token->access_token );

			$oww_scheme_id = 'AAMkADNlZGNlY2Q3LTU4NDctNDZlMi1hMjgyLWNjMTdhN2NiZTk0ZgBGAAAAAABuULMO0-qUSYSIyb2KIl9LBwAoJ3qpsWFSSpypVo542E_lAAAAAAEGAAAoJ3qpsWFSSpypVo542E_lAAAcuXwlAAA=';
			$mdm_scheme_id = 'AAMkADNlZGNlY2Q3LTU4NDctNDZlMi1hMjgyLWNjMTdhN2NiZTk0ZgBGAAAAAABuULMO0-qUSYSIyb2KIl9LBwAoJ3qpsWFSSpypVo542E_lAAAAAAEGAAAoJ3qpsWFSSpypVo542E_lAAAcuXwmAAA=';
			$shuttle_scheme_id = 'AAMkADNlZGNlY2Q3LTU4NDctNDZlMi1hMjgyLWNjMTdhN2NiZTk0ZgBGAAAAAABuULMO0-qUSYSIyb2KIl9LBwAoJ3qpsWFSSpypVo542E_lAAAAAAEGAAAoJ3qpsWFSSpypVo542E_lAAAcuXwnAAA=';

			// Kalender-ID's veranderen per gebruiker!
			// $user_name_app = 'oxfamappuser@oww.be';
			// $user_id_app = 'b4de7e02-104b-42b4-967d-484b7fcde90d';
			// $calendars = $graph
			// 	->createRequest( 'GET', '/users/'.$user_name_app.'/calendars' )
			// 	->addHeaders( array( 'Content-Type' => 'application/json', 'Prefer' => 'outlook.timezone="Europe/Paris"' ) )
			// 	->setReturnType(Microsoft\Graph\Model\Event::class)
			// 	->setTimeout(10000)
			// 	->execute();
			// var_dump_pre($calendars);
			// $oww_scheme_id_app = '';
			// $mdm_scheme_id_app = '';
			// $shuttle_scheme_id_app = '';

			try {
				// Let op het verschil tussen events en de instances van terugkerende events!
				// $event_a1a = $this->get_calendar_event_by_routecode();
				// echo number_format( microtime(true)-$start, 4, ',', '.' )." s => EVENT REQUEST EXECUTED<br/>";

				// $instances = $this->get_instances_for_calendar_event( $event_a1a->getId() );
				// echo number_format( microtime(true)-$start, 4, ',', '.' )." s => INSTANCE REQUEST EXECUTED<br/>";
				// echo "<br/>".count($instances)." INSTANCES<br/>";

				$instances = $this->get_calendar_view_by_subject( NULL, 50 );
				echo "<br/>".count($instances)." INSTANCES<br/>";

				foreach ( $instances as $event ) {
					echo '<br/><b>'.$event->getSubject().'</b><br/>';
					// Bevat respectievelijk Microsoft\Graph\Model\DateTimeTimeZone / array van strings / Microsoft\Graph\Model\Recipient
					echo $event->getStart()->getDateTime().' &mdash; '.str_replace( 'Z', '', implode( ', ', $event->getCategories() ) ).' &mdash; '.$event->getOrganizer()->getEmailAddress()->getAddress().'<br/>';
				}
				
			} catch ( Exception $e ) {
				exit( $e->getMessage() );
			}
		}

		function get_calendar_event_by_routecode( $routecode = 'A1A', $type = 'deadline' ) {
			global $graph, $user_name, $oww_scheme_id;

			// Single quotes gebruiken zodat dollartekens niet als variabelen geïnterpreteerd worden
			$events = $graph->createRequest( 'GET', '/users/'.$user_name.'/calendars/'.$oww_scheme_id.'/events?$orderby=start/dateTime asc&$filter=categories/any(a:a eq \'Z'.$routecode.'\') and startswith(subject,\''.$type.'\')' )
				->addHeaders( array( 'Content-Type' => 'application/json', 'Prefer' => 'outlook.timezone="Europe/Paris"' ) )
				->setReturnType(Microsoft\Graph\Model\Event::class)
				->setTimeout(10000)
				->execute();

			if ( is_array( $events ) ) {
				if ( count( $events ) === 1 ) {
					return $events[0];
				} elseif ( count( $events ) > 1 ) {
					return 'MULTIPLE EVENTS FOUND';
				}
			}

			return false;
		}

		function get_instances_for_calendar_event( $event_id, $limit = 20 ) {
			global $graph, $user_name, $oww_scheme_id;
			$start_date = date_i18n( 'Y-m-d\TH:i:s' );
			$end_date = date_i18n( 'Y-m-d\TH:i:s', strtotime('+2 months') );

			$instances = $graph->createRequest( 'GET', '/users/'.$user_name.'/calendars/'.$oww_scheme_id.'/events/'.$event_id.'/instances?startDateTime='.$start_date.'&endDateTime='.$end_date.'&$orderby=start/dateTime asc&$top='.$limit )
				->addHeaders( array( 'Content-Type' => 'application/json', 'Prefer' => 'outlook.timezone="Europe/Paris"' ) )
				->setReturnType(Microsoft\Graph\Model\Event::class)
				->setTimeout(10000)
				->execute();
			
			if ( is_array( $instances ) ) {
				if ( count( $instances ) > 0 ) {
					return $instances;
				} else {
					return 'NO INSTANCES FOUND';
				}
			}

			return false;
		}

		function get_calendar_view_by_subject( $type = '', $limit = 100 ) {
			global $graph, $user_name, $oww_scheme_id;
			$start_date = date_i18n( 'Y-m-d\TH:i:s' );
			// Dit houdt geen rekening met de lokale tijd ...
			$end_date = date_i18n( 'Y-m-d\TH:i:s', strtotime('+2 months') );
			var_dump_pre($start_date);
			var_dump_pre($end_date);

			// Opgelet: startswith() is hier case sensitive!
			// Filteren op categorie resulteert in Error 500
			$instances_in_view = $graph->createRequest( 'GET', '/users/'.$user_name.'/calendars/'.$oww_scheme_id.'/calendarView?startDateTime='.$start_date.'&endDateTime='.$end_date.'&$orderby=start/dateTime asc&$filter=startswith(subject,\''.$type.'\')&$top='.$limit )
				->addHeaders( array( 'Content-Type' => 'application/json', 'Prefer' => 'outlook.timezone="Europe/Paris"' ) )
				->setReturnType(Microsoft\Graph\Model\Event::class)
				->setTimeout(100000)
				->execute();
			
			if ( is_array( $instances_in_view ) ) {
				if ( count( $instances_in_view ) > 0 ) {
					return $instances_in_view;
				} else {
					return 'NO INSTANCES IN VIEW';
				}
			}

			return false;
		}
	}

?>