<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				Vast Development Method 
/-------------------------------------------------------------------------------------------------------/

	@version		1.0.3 - 24th August, 2015
	@package		Demo
	@subpackage		view.html.php
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

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * Demo View class for the Help_documents
 */
class DemoViewHelp_documents extends JViewLegacy
{
	/**
	 * Help_documents view display method
	 * @return void
	 */
	function display($tpl = null) 
	{
		if ($this->getLayout() !== 'modal')
		{
			// Include helper submenu
			DemoHelper::addSubmenu('help_documents');
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
        {
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		};

		// Assign data to the view
		$this->items 		= $this->get('Items');
		$this->pagination 	= $this->get('Pagination');
		$this->state		= $this->get('State');
		$this->user 		= JFactory::getUser();
		$this->listOrder	= $this->escape($this->state->get('list.ordering'));
		$this->listDirn		= $this->escape($this->state->get('list.direction'));
		$this->saveOrder	= $this->listOrder == 'ordering';
        // get global action permissions
		$this->canDo		= DemoHelper::getActions('help_document');
		$this->canEdit		= $this->canDo->get('help_document.edit');
		$this->canState		= $this->canDo->get('help_document.edit.state');
		$this->canCreate	= $this->canDo->get('help_document.create');
		$this->canDelete	= $this->canDo->get('help_document.delete');
        
		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal')
		{
			$this->addToolbar();
			$this->sidebar = JHtmlSidebar::render();
            // load the batch html
            if ($this->canCreate && $this->canEdit && $this->canState)
            {
				$this->batchDisplay = JHtmlBatch_::render();
            }
		}

		// Display the template
		parent::display($tpl);

		// Set the document
		$this->setDocument();
	}

	/**
	 * Setting the toolbar
	 */
	protected function addToolBar() 
	{		
		JToolBarHelper::title(JText::_('COM_DEMO_HELP_DOCUMENTS'), 'joomla');
		JHtmlSidebar::setAction('index.php?option=com_demo&view=help_documents');
        JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');
		
		if ($this->canCreate)
        {
			JToolBarHelper::addNew('help_document.add');
		}
        
        // Only load if there are items
        if (DemoHelper::checkArray($this->items))
		{
            if ($this->canEdit)
            {
                JToolBarHelper::editList('help_document.edit');
            }
    
            if ($this->canState)
            {
                JToolBarHelper::divider();
    
                JToolBarHelper::publishList('help_documents.publish');
                JToolBarHelper::unpublishList('help_documents.unpublish');
        
                JToolBarHelper::divider();
                JToolBarHelper::archiveList('help_documents.archive');
                    
                if ($this->canDo->get('core.admin'))
                {
                    JToolBarHelper::checkin('help_documents.checkin');
                }
            }
    
            if ($this->state->get('filter.published') == -2 && ($this->canState && $this->canDelete))
            {
                JToolbarHelper::deleteList('', 'help_documents.delete', 'JTOOLBAR_EMPTY_TRASH');
            }
            elseif ($this->canState && $this->canDelete)
            {
                JToolbarHelper::trash('help_documents.trash');
			}
            
			// Add a batch button
			if ($this->canCreate && $this->canEdit && $this->canState)
			{
                // Get the toolbar object instance
                $bar = JToolBar::getInstance('toolbar');
                // set the batch button name
				$title = JText::_('JTOOLBAR_BATCH');	
				// Instantiate a new JLayoutFile instance and render the batch button
				$layout = new JLayoutFile('joomla.toolbar.batch');
				// add the button to the page
				$dhtml = $layout->render(array('title' => $title));
				$bar->appendButton('Custom', $dhtml, 'batch');
			}

			if ($this->canDo->get('core.export') && $this->canDo->get('help_document.export'))
			{
				JToolBarHelper::custom('help_documents.exportData', 'download', '', 'COM_DEMO_EXPORT_DATA', true);
			}
        }

		if ($this->canDo->get('core.import') && $this->canDo->get('help_document.import'))
		{
			JToolBarHelper::custom('help_documents.importData', 'upload', '', 'COM_DEMO_IMPORT_DATA', false);
		}
		
		JToolBarHelper::divider();
		
       	if ($this->canDo->get('core.admin') || $this->canDo->get('core.options'))
		{
			JToolBarHelper::preferences('com_demo');
		}

		// set help url for this view if found
        $help_url = DemoHelper::getHelpUrl('help_documents');
        if (DemoHelper::checkString($help_url))
        {
			JToolbarHelper::help('COM_DEMO_HELP_MANAGER', false, $help_url);
        }
		
		if ($this->canState)
        {
			JHtmlSidebar::addFilter(
				JText::_('JOPTION_SELECT_PUBLISHED'),
				'filter_published',
				JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
			);
            
           	JHtmlBatch_::addListSelection(
                JText::_('COM_DEMO_KEEP_ORIGINAL_STATE'),
                'batch[published]',
                JHtml::_('select.options', JHtml::_('jgrid.publishedOptions', array('all' => false)), 'value', 'text', '', true)
            );
		}

		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_ACCESS'),
			'filter_access',
			JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access'))
		);
        
		if ($this->canCreate && $this->canEdit)
		{
			JHtmlBatch_::addListSelection(
                JText::_('COM_DEMO_KEEP_ORIGINAL_ACCESS'),
                'batch[access]',
                JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text')
			);
        }  

		// Set Type Selection
		$this->typeOptions = $this->getTheTypeSelections();
		if ($this->typeOptions)
		{
			// Type Filter
			JHtmlSidebar::addFilter(
				'- Select '.JText::_('COM_DEMO_HELP_DOCUMENT_TYPE_LABEL').' -',
				'filter_type',
				JHtml::_('select.options', $this->typeOptions, 'value', 'text', $this->state->get('filter.type'))
			);

			if ($this->canCreate && $this->canEdit)
			{
				//  Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_DEMO_HELP_DOCUMENT_TYPE_LABEL').' -',
					'batch[type]',
					JHtml::_('select.options', $this->typeOptions, 'value', 'text')
				);
			}
		}

		// Set Location Selection
		$this->locationOptions = $this->getTheLocationSelections();
		if ($this->locationOptions)
		{
			// Location Filter
			JHtmlSidebar::addFilter(
				'- Select '.JText::_('COM_DEMO_HELP_DOCUMENT_LOCATION_LABEL').' -',
				'filter_location',
				JHtml::_('select.options', $this->locationOptions, 'value', 'text', $this->state->get('filter.location'))
			);

			if ($this->canCreate && $this->canEdit)
			{
				//  Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_DEMO_HELP_DOCUMENT_LOCATION_LABEL').' -',
					'batch[location]',
					JHtml::_('select.options', $this->locationOptions, 'value', 'text')
				);
			}
		}

		// Set Admin View Selection
		$this->admin_viewOptions = $this->getTheAdmin_viewSelections();
		if ($this->admin_viewOptions)
		{
			// Admin View Filter
			JHtmlSidebar::addFilter(
				'- Select '.JText::_('COM_DEMO_HELP_DOCUMENT_ADMIN_VIEW_LABEL').' -',
				'filter_admin_view',
				JHtml::_('select.options', $this->admin_viewOptions, 'value', 'text', $this->state->get('filter.admin_view'))
			);

			if ($this->canCreate && $this->canEdit)
			{
				//  Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_DEMO_HELP_DOCUMENT_ADMIN_VIEW_LABEL').' -',
					'batch[admin_view]',
					JHtml::_('select.options', $this->admin_viewOptions, 'value', 'text')
				);
			}
		}

		// Set Site View Selection
		$this->site_viewOptions = $this->getTheSite_viewSelections();
		if ($this->site_viewOptions)
		{
			// Site View Filter
			JHtmlSidebar::addFilter(
				'- Select '.JText::_('COM_DEMO_HELP_DOCUMENT_SITE_VIEW_LABEL').' -',
				'filter_site_view',
				JHtml::_('select.options', $this->site_viewOptions, 'value', 'text', $this->state->get('filter.site_view'))
			);

			if ($this->canCreate && $this->canEdit)
			{
				//  Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_DEMO_HELP_DOCUMENT_SITE_VIEW_LABEL').' -',
					'batch[site_view]',
					JHtml::_('select.options', $this->site_viewOptions, 'value', 'text')
				);
			}
		}
	}

	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 */
	protected function setDocument() 
	{
		$document = JFactory::getDocument();
		$document->setTitle(JText::_('COM_DEMO_HELP_DOCUMENTS'));
		$document->addStyleSheet(JURI::root() . "administrator/components/com_demo/assets/css/help_documents.css");
	}
    
    /**
	 * Escapes a value for output in a view script.
	 *
	 * @param   mixed  $var  The output to escape.
	 *
	 * @return  mixed  The escaped value.
	 */
	public function escape($var)
	{
		if(strlen($var) > 50)
		{
    		// use the helper htmlEscape method instead and shorten the string
			return DemoHelper::htmlEscape($var, $this->_charset, true);
		}
    	// use the helper htmlEscape method instead.
		return DemoHelper::htmlEscape($var, $this->_charset);
	}
	
	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 */
	protected function getSortFields()
	{
		return array(
			'a.sorting' => JText::_('JGRID_HEADING_ORDERING'),
			'a.published' => JText::_('JSTATUS'),
			'a.title' => JText::_('COM_DEMO_HELP_DOCUMENT_TITLE_LABEL'),
			'a.type' => JText::_('COM_DEMO_HELP_DOCUMENT_TYPE_LABEL'),
			'a.location' => JText::_('COM_DEMO_HELP_DOCUMENT_LOCATION_LABEL'),
			'a.admin_view' => JText::_('COM_DEMO_HELP_DOCUMENT_ADMIN_VIEW_LABEL'),
			'a.site_view' => JText::_('COM_DEMO_HELP_DOCUMENT_SITE_VIEW_LABEL'),
			'a.id' => JText::_('JGRID_HEADING_ID')
		);
	} 

	protected function getTheTypeSelections()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName('type'));
		$query->from($db->quoteName('#__demo_help_document'));
		$query->order($db->quoteName('type') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadColumn();

		if ($results)
		{
			// get model
			$model = &$this->getModel();
			$results = array_unique($results);
			$filter = array();
			foreach ($results as $type)
			{
				// Translate the type selection
				$text = $model->selectionTranslation($type,'type');
				// Now add the type and its text to the options array
				$filter[] = JHtml::_('select.option', $type, JText::_($text));
			}
			return $filter;
		}
		return false;
	}

	protected function getTheLocationSelections()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName('location'));
		$query->from($db->quoteName('#__demo_help_document'));
		$query->order($db->quoteName('location') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadColumn();

		if ($results)
		{
			// get model
			$model = &$this->getModel();
			$results = array_unique($results);
			$filter = array();
			foreach ($results as $location)
			{
				// Translate the location selection
				$text = $model->selectionTranslation($location,'location');
				// Now add the location and its text to the options array
				$filter[] = JHtml::_('select.option', $location, JText::_($text));
			}
			return $filter;
		}
		return false;
	}

	protected function getTheAdmin_viewSelections()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName('admin_view'));
		$query->from($db->quoteName('#__demo_help_document'));
		$query->order($db->quoteName('admin_view') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadColumn();

		if ($results)
		{
			$results = array_unique($results);
			$filter = array();
			foreach ($results as $admin_view)
			{
				// Now add the admin_view and its text to the options array
				$filter[] = JHtml::_('select.option', $admin_view, $admin_view);
			}
			return $filter;
		}
		return false;
	}

	protected function getTheSite_viewSelections()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName('site_view'));
		$query->from($db->quoteName('#__demo_help_document'));
		$query->order($db->quoteName('site_view') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadColumn();

		if ($results)
		{
			$results = array_unique($results);
			$filter = array();
			foreach ($results as $site_view)
			{
				// Now add the site_view and its text to the options array
				$filter[] = JHtml::_('select.option', $site_view, $site_view);
			}
			return $filter;
		}
		return false;
	}
}
