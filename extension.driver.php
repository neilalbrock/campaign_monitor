<?php

	Class extension_campaign_monitor extends Extension{

		public function about(){
			return array(
				'name' => 'Campaign Monitor',
				 'version' => '0.1',
				 'release-date' => '2010-07-05',
				 'author' => array(
					'name' => 'Neil Albrock',
					'website' => 'http://atomised.coop',
					'email' => 'neil@atomised.coop')
		 	);
		}
		
		public function fetchNavigation() {
			return array(
				array(
					'location' => 'System',
					'name'	=> 'Campaign Monitor',
					'link'	=> '/cmadmin/'
				)
			);
		}
		
		public function getSubscribedDelegates(){
			return array(
						array(
							'page' => '/system/preferences/',
							'delegate' => 'AddCustomPreferenceFieldsets',
							'callback' => 'appendPreferences'
						),
						array(
							'page' => '/blueprints/events/new/',
							'delegate' => 'AppendEventFilter',
							'callback' => 'addFilterToEventEditor'
						),
						array(
							'page' => '/blueprints/events/edit/',
							'delegate' => 'AppendEventFilter',
							'callback' => 'addFilterToEventEditor'
						),
						array(
							'page' => '/blueprints/events/new/',
							'delegate' => 'AppendEventFilterDocumentation',
							'callback' => 'appendEventFilterDocumentation'
						),
						array(
							'page' => '/blueprints/events/edit/',
							'delegate' => 'AppendEventFilterDocumentation',
							'callback' => 'appendEventFilterDocumentation'
						),
						array(
							'page' => '/frontend/',
							'delegate' => 'EventPostSaveFilter',
							'callback' => 'createNewCampaign'
						)
					);
		}
		
		public function appendPreferences($context){

			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Campaign Monitor')));
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');
			
			$label = Widget::Label(__('API Key'));
			$label->appendChild(Widget::Input('settings[campaign_monitor][api_key]', Symphony::Configuration()->get('api_key', 'campaign_monitor')));
			$div->appendChild($label);
			
			$label = Widget::Label(__('Client ID'));
			$label->appendChild(Widget::Input('settings[campaign_monitor][client_id]', Symphony::Configuration()->get('client_id', 'campaign_monitor')));
			$div->appendChild($label);
			
			$group->appendChild($div);
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');
			
			$label = Widget::Label(__('List ID'));
			$label->appendChild(Widget::Input('settings[campaign_monitor][list_id]', Symphony::Configuration()->get('list_id', 'campaign_monitor')));
			$div->appendChild($label);
			
			$group->appendChild($div);
										
			$context['wrapper']->appendChild($group);
						
		}
		
		public function addFilterToEventEditor($context){
			$context['options'][] = array(
				'campaign_monitor-create-campaign-filter', @in_array('campaign_monitor-create-campaign-filter', $context['selected']), 'Campaign Monitor: Create campaign'
			);			
			
		}
		
		public function appendEventFilterDocumentation(array $context=array()){
			if(!@in_array('campaign_monitor-create-campaign-filter', $context['selected'])) return;
			
			$context['documentation'][] = new XMLElement('h3', __('Campaign Monitor: Create campaign'));
			$context['documentation'][] = new XMLElement('p', __('The Create campaign filter, upon the event successfully saving the entry, creates a new campaign via the Campaign Monitor API'));

		}
		
		public function createNewCampaign(array $context=array()){
			
			if(!@in_array('campaign_monitor-create-campaign-filter', $context['event']->eParamFILTERS)) return;
			
			require_once(EXTENSIONS . '/campaign_monitor/lib/cmapi/CMBase.php');
			
			$api_key = $context['parent']->Configuration->get('api_key', 'campaign_monitor');
			$client_id = $context['parent']->Configuration->get('client_id', 'campaign_monitor');
			$list_id = array($context['parent']->Configuration->get('list_id', 'campaign_monitor'));
			$subscriber_segments = '';
			$campaign_name = 'Newsletter';
			$subject = 'Newsletter';
			$from_name = 'Neil Albrock';
			$from_email = 'neil@atomised.coop';
			$reply_email = 'neil@atomised.coop';
			$html_content = '';
			$text_content = '';
			
			$cm = new CampaignMonitor($api_key);
			
			//Create the new campaign
			$campaignid = $cm->campaignCreate($client_id,$campaign_name,$subject,$from_name,$from_email,$reply_email,$html_content,$text_content,$list_id,'');
			
			$result = $cm->campaignSend($campaignid,$from_email,date("Y-m-d H:i:s"));
			
			/*$fields = $_POST['send-email'];
			
			$fields['recipient'] = $this->__sendEmailFindFormValue($fields['recipient'], $_POST['fields'], true);
			$fields['recipient'] = preg_split('/\,/i', $fields['recipient'], -1, PREG_SPLIT_NO_EMPTY);
			$fields['recipient'] = array_map('trim', $fields['recipient']);

			$fields['recipient'] = Symphony::Database()->fetch("SELECT `email`, CONCAT(`first_name`, ' ', `last_name`) AS `name` FROM `tbl_authors` WHERE `username` IN ('".@implode("', '", $fields['recipient'])."') ");

			$fields['subject'] = $this->__sendEmailFindFormValue($fields['subject'], $context['fields'], true, __('[Symphony] A new entry was created on %s', array(Symphony::Configuration()->get('sitename', 'general'))));
			$fields['body'] = $this->__sendEmailFindFormValue($fields['body'], $context['fields'], false, NULL, false);
			$fields['sender-email'] = $this->__sendEmailFindFormValue($fields['sender-email'], $context['fields'], true, 'noreply@' . parse_url(URL, PHP_URL_HOST));
			$fields['sender-name'] = $this->__sendEmailFindFormValue($fields['sender-name'], $context['fields'], true, 'Symphony');
			$fields['from'] = $this->__sendEmailFindFormValue($fields['from'], $context['fields'], true, $fields['sender-email']);		
						
			$section = Symphony::Database()->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `id` = ".$context['event']->getSource()." LIMIT 1");
			
			$edit_link = URL.'/symphony/publish/'.$section['handle'].'/edit/'.$context['entry_id'].'/';

			$body = __('Dear <!-- RECIPIENT NAME -->,') . General::CRLF . General::CRLF . __('This is a courtesy email to notify you that an entry was created on the %1$s section. You can edit the entry by going to: %2$s', array($section['name'], $edit_link)). General::CRLF . General::CRLF;

			if(is_array($fields['body'])){
				foreach($fields['body'] as $field_handle => $value){
					$body .= "=== $field_handle ===" . General::CRLF . General::CRLF . $value . General::CRLF . General::CRLF;
				}
			}

			else $body .= $fields['body'];

			$errors = array();

			if(!is_array($fields['recipient']) || empty($fields['recipient'])){
				$context['messages'][] = array('smtp-email-library-send-email-filter', false, __('No valid recipients found. Check send-email[recipient] field.'));
			}

			else{
				
				foreach($fields['recipient'] as $r){
					
					$email = new LibraryEmail;

					$email->to = vsprintf('%2$s <%1$s>', array_values($r));
					$email->from = sprintf('%s <%s>', $fields['sender-name'], $fields['sender-email']);
					$email->subject = $fields['subject'];
					$email->message = str_replace('<!-- RECIPIENT NAME -->', $r['name'], $body);
					$email->setHeader('Reply-To', $fields['from']);

					try{
						$email->send();
					}
					catch(Exception $e){
						$errors[] = $email;
					}

				}

				if(!empty($errors)){
					$context['messages'][] = array('smtp-email-library-send-email-filter', false, 'The following email addresses were problematic: ' . General::sanitize(implode(', ', $errors)));
				}

				else $context['messages'][] = array('smtp-email-library-send-email-filter', true);
			}*/
		}
				
	}
?>