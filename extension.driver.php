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
					'name'	=> __('Campaign Monitor'),
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
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');
			
			$label = Widget::Label(__('Campaign Name Prefix'));
			$label->appendChild(Widget::Input('settings[campaign_monitor][campaign_name]', Symphony::Configuration()->get('campaign_name', 'campaign_monitor')));
			$div->appendChild($label);
			
			$label = Widget::Label(__('Sender'));
			$label->appendChild(Widget::Input('settings[campaign_monitor][sender_name]', Symphony::Configuration()->get('sender_name', 'campaign_monitor')));
			$div->appendChild($label);
			
			$group->appendChild($div);
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');
			
			$label = Widget::Label(__('Sender Email'));
			$label->appendChild(Widget::Input('settings[campaign_monitor][sender_email]', Symphony::Configuration()->get('sender_email', 'campaign_monitor')));
			$div->appendChild($label);
			
			$label = Widget::Label(__('Reply To Email'));
			$label->appendChild(Widget::Input('settings[campaign_monitor][reply_email]', Symphony::Configuration()->get('reply_email', 'campaign_monitor')));
			$div->appendChild($label);
			
			$group->appendChild($div);
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');
			
			$label = Widget::Label(__('HTML Content'));
			$label->appendChild(Widget::Input('settings[campaign_monitor][html_content]', Symphony::Configuration()->get('html_content', 'campaign_monitor')));
			$div->appendChild($label);
			
			$label = Widget::Label(__('Plain Text Content'));
			$label->appendChild(Widget::Input('settings[campaign_monitor][text_content]', Symphony::Configuration()->get('text_content', 'campaign_monitor')));
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
		
		private function __sendEmailFindFormValue($needle, $haystack, $discard_field_name=true, $default=NULL, $collapse=true){

			if(preg_match('/^(fields\[[^\]]+\],?)+$/i', $needle)){
				$parts = preg_split('/\,/i', $needle, -1, PREG_SPLIT_NO_EMPTY);
				$parts = array_map('trim', $parts);

				$stack = array();
				foreach($parts as $p){ 
					$field = str_replace(array('fields[', ']'), '', $p);
					($discard_field_name ? $stack[] = $haystack[$field] : $stack[$field] = $haystack[$field]);
				}

				if(is_array($stack) && !empty($stack)) return ($collapse ? implode(' ', $stack) : $stack);
				else $needle = NULL;
			}

			$needle = trim($needle);
			if(empty($needle)) return $default;

			return $needle;

		}
		
		public function createNewCampaign(array $context=array()){
			
			if(!@in_array('campaign_monitor-create-campaign-filter', $context['event']->eParamFILTERS)) return;
			
			require_once(EXTENSIONS . '/campaign_monitor/lib/cmapi/CMBase.php');
			
			//Options from Preferences
			$api_key = $context['parent']->Configuration->get('api_key', 'campaign_monitor');
			$client_id = $context['parent']->Configuration->get('client_id', 'campaign_monitor');
			$list_id = array($context['parent']->Configuration->get('list_id', 'campaign_monitor'));
			$from_name = $context['parent']->Configuration->get('sender_name', 'campaign_monitor');
			$from_email = $context['parent']->Configuration->get('sender_email', 'campaign_monitor');
			$reply_email = $context['parent']->Configuration->get('reply_email', 'campaign_monitor');
			//Options from Prefs + Event
			$campaign_name = $context['parent']->Configuration->get('campaign_name', 'campaign_monitor') . $_POST['fields']['issue'];
			$subject = $context['parent']->Configuration->get('campaign_name', 'campaign_monitor') . $_POST['fields']['issue'];
			$html_content = $context['parent']->Configuration->get('html_content', 'campaign_monitor') . $_POST['fields']['issue'];
			$text_content = $context['parent']->Configuration->get('text_content', 'campaign_monitor'). $_POST['fields']['issue'];
			
			$cm = new CampaignMonitor($api_key);
			
			//Create the new campaign
			$campaignid = $cm->campaignCreate($client_id,$campaign_name,$subject,$from_name,$from_email,$reply_email,$html_content,$text_content,$list_id,'');
			
			$result = $cm->campaignSend($campaignid,$from_email,date("Y-m-d H:i:s"));			
			
		}
				
	}
?>