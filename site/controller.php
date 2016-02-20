<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				Vast Development Method 
/-------------------------------------------------------------------------------------------------------/

	@version		1.0.5
	@build			20th February, 2016
	@created		5th August, 2015
	@package		Demo
	@subpackage		controller.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>	
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
  ____  _____  _____  __  __  __      __       ___  _____  __  __  ____  _____  _  _  ____  _  _  ____ 
 (_  _)(  _  )(  _  )(  \/  )(  )    /__\     / __)(  _  )(  \/  )(  _ \(  _  )( \( )( ___)( \( )(_  _)
.-_)(   )(_)(  )(_)(  )    (  )(__  /(__)\   ( (__  )(_)(  )    (  )___/ )(_)(  )  (  )__)  )  (   )(  
\____) (_____)(_____)(_/\/\_)(____)(__)(__)   \___)(_____)(_/\/\_)(__)  (_____)(_)\_)(____)(_)\_) (__) 

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla controller library
jimport('joomla.application.component.controller');

/**
 * Demo Component Controller
 */
class DemoController extends JControllerLegacy
{
	/**
	 * display task
	 *
	 * @return void
	 */
        function display($cachable = false, $urlparams = false)
	{
		// set default view if not set
		$view		= $this->input->getCmd('view', '###SITE_DEFAULT_VIEW###');
		$isEdit		= $this->checkEditView($view);
		$layout		= $this->input->get('layout', null, 'WORD');
		$id		= $this->input->getInt('id');
		$cachable	= true;
		
		// Check for edit form.
                if($isEdit)
                {
			if ($layout == 'edit' && !$this->checkEditId('com_demo.edit.'.$view, $id))
			{
				// Somehow the person just went to the form - we don't allow that.
				$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
				$this->setMessage($this->getError(), 'error');
				// check if item was opend from other then its own list view
				$ref 	= $this->input->getCmd('ref', 0);
				$refid 	= $this->input->getInt('refid', 0);
				// set redirect
				if ($refid > 0 && DemoHelper::checkString($ref))
				{
					// redirect to item of ref
					$this->setRedirect(JRoute::_('index.php?option=com_demo&view='.(string)$ref.'&layout=edit&id='.(int)$refid, false));
				}
				elseif (DemoHelper::checkString($ref))
				{

					// redirect to ref
					 $this->setRedirect(JRoute::_('index.php?option=com_demo&view='.(string)$ref, false));
				}
				else
				{
					// normal redirect back to the list default site view
					$this->setRedirect(JRoute::_('index.php?option=com_demo&view=###SITE_DEFAULT_VIEW###', false));
				}
				return false;
			}
                }

		return parent::display($cachable, $urlparams);
	}

	protected function checkEditView($view)
	{
                if (DemoHelper::checkString($view))
                {
                        $views = array(

                                );
                        // check if this is a edit view
                        if (in_array($view,$views))
                        {
                                return true;
                        }
                }
		return false;
	}
}
