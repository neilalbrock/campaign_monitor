<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(EXTENSIONS . '/campaign_monitor/lib/cmapi/CMBase.php');
	
	Class contentExtensionCampaign_MonitorCmadmin extends AdministrationPage{
		
		public function view(){				
			//Page options
			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Campaign Monitor Subscribers'))));
			$this->appendSubheading(__('Campaign Monitor Subscribers'));
			
			//Form action
			$this->Form->setAttribute('action', $this->_Parent->getCurrentPageURL());
			
			//Get Campaign Monitor preferences
			$api_key = $this->_Parent->Configuration->get('api_key', 'campaign_monitor');
			$list_id = $this->_Parent->Configuration->get('list_id', 'campaign_monitor');
			//New Campaign Monitor instance
			$cm = new CampaignMonitor($api_key);
			
			//Get subscriber list
			$result = $cm->subscribersGetActive(0,$list_id);
			$subscribers = $result['anyType']['Subscriber'];
			
			//Subscriber table headers
			$aTableHead = array(
				array(__('Name'), 'col'),
				array(__('Email Address'), 'col'),
				array(__('Date Subscribed'), 'col'),
				array(__('Status'), 'col'),
			);
			
			$aTableBody = array();
			
			if (!is_array($subscribers) || empty($subscribers)){

				$aTableBody = array(
									Widget::TableRow(array(Widget::TableData(__('You currently have no subscribers.'), 'inactive', NULL, count($aTableHead))), 'odd')
								);
								
			} else {
				
				if (is_array($subscribers[0])) { //Check if the subscriber list is longer than one, in which case it's an array of arrays
				
					foreach($subscribers as $subscriber){
					
						$td1 = Widget::TableData($subscriber["Name"]);
						$td2 = Widget::TableData($subscriber["EmailAddress"]);
						$td2->appendChild(Widget::Input('items['.$subscriber["EmailAddress"].']', 'on', 'checkbox'));
						$td3 = Widget::TableData(date("d F Y H:i", strtotime($subscriber["Date"])));
						$td4 = Widget::TableData($subscriber["State"]);

						//Add table data to row and body
						$aTableBody[] = Widget::TableRow(array($td1, $td2, $td3, $td4));		

					}
				
				} else { //Single subscriber
					
					$td1 = Widget::TableData($subscribers["Name"]);
					$td2 = Widget::TableData($subscribers["EmailAddress"]);
					$td2->appendChild(Widget::Input('items['.$subscribers["EmailAddress"].']', 'on', 'checkbox'));
					$td3 = Widget::TableData(date("d F Y H:i", strtotime($subscribers["Date"])));
					$td4 = Widget::TableData($subscribers["State"]);

					//Add table data to row and body
					$aTableBody[] = Widget::TableRow(array($td1, $td2, $td3, $td4));
					
				}
				
			}
			
			$table = Widget::Table(
							Widget::TableHead($aTableHead), NULL, 
							Widget::TableBody($aTableBody), 'orderable'
					);
			
			//Append the subscriber table to the page
			$this->Form->appendChild($table);
			
			//Actions for this page
			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');
			
			$options = array(
				array(NULL, false, __('With Selected...')),
				array('unsubscribe', false, __('Unsubscribe')),
			);

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));
			
			//Append actions to the page
			$this->Form->appendChild($tableActions);
		}
		
		function __actionIndex(){
			$checked  = @array_keys($_POST['items']);
			
			if(is_array($checked) && !empty($checked)){

				//Get Campaign Monitor preferences
				$api_key = $this->_Parent->Configuration->get('api_key', 'campaign_monitor');
				$list_id = $this->_Parent->Configuration->get('list_id', 'campaign_monitor');
				//New Campaign Monitor instance
				$cm = new CampaignMonitor($api_key);

				switch($_POST['with-selected']){

					case 'unsubscribe':

						foreach($checked as $subscriber){
							$result = $cm->subscriberUnsubscribe($subscriber,$list_id,false);
						}
						redirect($this->_Parent->getCurrentPageURL());
						break;
						
				}
			}			
		}
		
	}
	
?>