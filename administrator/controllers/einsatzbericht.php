<?php
/**
 * @version     3.0.0
 * @package     com_einsatzkomponente
 * @copyright   Copyright (C) 2013 by Ralf Meyer. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Ralf Meyer <webmaster@feuerwehr-veenhusen.de> - http://einsatzkomponente.de
 */
// No direct access
defined('_JEXEC') or die;
jimport('joomla.application.component.controllerform');
/**
 * Einsatzbericht controller class.
 */
class EinsatzkomponenteControllerEinsatzbericht extends JControllerForm
{
    function __construct() {
        $this->view_list = 'einsatzberichte';
        parent::__construct();
    }
     function swf()  
    {    
	
        $pview      = JFactory::getApplication()->input->get('view', 'einsatzbericht');
		$rep_id      = JFactory::getApplication()->input->get('id', '0');

		if (parent::save()) :
		if ($rep_id == '0') :
		$db = JFactory::getDBO();
		$query = "SELECT id FROM #__eiko_einsatzberichte ORDER BY id DESC LIMIT 1";
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		$rep_id      = $rows[0]->id;
		$msg    = JText::_( 'Neuer Einsatzbericht gespeichert ! Sie können jetzt die Einsatzbilder zu diesem Einsatz hinzufügen.' );
        $this->setRedirect('index.php?option=com_einsatzkomponente&view=swfupload&pview='.$pview.'&rep_id='.$rep_id.'', $msg); 
		endif;
		
		else:
        $this->setRedirect('index.php?option=com_einsatzkomponente&view=einsatzbericht&layout=edit', $msg); 
		endif;
		
		if (!$rep_id == '0') :
        //$msg    = JText::_( '' );  
        $this->setRedirect('index.php?option=com_einsatzkomponente&view=swfupload&pview='.$pview.'&rep_id='.$rep_id.'', $msg); 
		endif;
		
    }
	//function  

    function save($key = NULL, $urlVar = NULL) {

		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

		// Get items to remove from the request.
		$send = 'false';
		$cid = JFactory::getApplication()->input->get('id','0');
		$params = JComponentHelper::getParams('com_einsatzkomponente');
		
	if (parent::save()) :
		if ( $params->get('send_mail_auto', '0') ): 
		if (!$cid) :
		$db = JFactory::getDBO();
		$query = "SELECT id FROM #__eiko_einsatzberichte ORDER BY id DESC LIMIT 1";
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		$cid      = $rows[0]->id;
		$send = sendMail_auto($cid,'neuer Bericht: ');
		else:
		$send = sendMail_auto($cid,'Update: ');
		endif;
		endif;
	endif;
    //print_r ($send);break;
    }
	
}


	    function sendMail_auto($cid,$status) {

		

		//$model = $this->getModel();
		$params = JComponentHelper::getParams('com_einsatzkomponente');
		$query = 'SELECT * FROM `#__eiko_einsatzberichte` WHERE `id` = "'.$cid.'" LIMIT 1';
		$db = JFactory::getDBO();
		$db->setQuery($query);
		$result = $db->loadObjectList();
	
		$mailer = JFactory::getMailer();
		$config = JFactory::getConfig();
		$sender = array( 
    	$config->get( 'config.mailfrom' ),
    	$config->get( 'config.fromname' ) );
		$mailer->setSender($sender);
		
		$user = JFactory::getUser();
		//$recipient = $user->email;
		$recipient = $params->get('mail_empfaenger_auto',$user->email);
		
		$recipient 	 = explode( ',', $recipient);
		$orga		 = explode( ',', $result[0]->auswahlorga);
		$orgas 		 = str_replace(",", " +++ ", $result[0]->auswahlorga);
 
		$mailer->addRecipient($recipient);
		
		$mailer->setSubject($status.''.$orga[0].'  +++ '.$result[0]->summary.' +++');
		
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
					$query
						->select('*')
						->from('`#__eiko_tickerkat`')
						->where('id = "' .$result[0]->tickerkat.'"  AND state = "1" ');
					$db->setQuery($query);
					$kat = $db->loadObject();
		
		$link = JRoute::_( JURI::root() . 'index.php?option=com_einsatzkomponente&view=einsatzbericht&id='.$result[0]->id.'&Itemid='.$params->get('homelink','')); 
		
		$body   = ''
				. '<h2>+++ '.$result[0]->summary.' +++</h2>';
		if ($params->get('send_mail_kat','0')) :	
		$body   .= '<h4>'.JText::_($kat->title).'</h4>';
		endif;
		if ($params->get('send_mail_orga','0')) :	
		$body   .= '<span><b>Eingesetzte Kräfte:</b> '.$orgas.'</span>';
		endif;
		$body   .= '<div>';
		if ($params->get('send_mail_desc','0')) :	
		if ($result[0]->desc) :	
    	$body   .= '<p>'.$result[0]->desc.'</p>';
		else:
    	$body   .= '<p>Ein ausführlicher Bericht ist zur Zeit noch nicht vorhanden.</p>';
		endif;
		endif;
		if ($params->get('send_mail_link','0')) :	
    	$body   .= '<p><a href="'.$link.'" target="_blank">Link zur Homepage</a></p>';
		endif;
		if ($result[0]->image) :	
		if ($params->get('send_mail_image','0')) :	
		$body   .= '<img src="'.JURI::root().$result[0]->image.'" style="margin-left:10px;float:right;height:50%;" alt="Einsatzbild"/>';
		endif;
		endif;
		$body   .= '</div>';
		

		$mailer->isHTML(true);
		$mailer->Encoding = 'base64';
		$mailer->setBody($body);
		// Optionally add embedded image
		//$mailer->AddEmbeddedImage( JPATH_COMPONENT.'/assets/logo128.jpg', 'logo_id', 'logo.jpg', 'base64', 'image/jpeg' );
		
		$send = $mailer->Send();
        return 'gesendet'; 
    }
