<?php

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");
 
/**
 * Matterhorn configuration user interface class
 *
 * @author Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
 * @version $Id$
 *
 */
class ilMatterhornConfigGUI extends ilPluginConfigGUI
{
	/**
	* Handles all commmands, default is "configure"
	*/
	function performCommand($cmd)
	{

		switch ($cmd)
		{
			case "configure":
			case "save":
				$this->$cmd();
				break;

		}
	}

	/**
	 * Configure screen
	 */
	function configure()
	{
		global $tpl;
		$form = $this->initConfigurationForm();
		$values = array();
		$values['mh_server'] = $this->configObject->getMatterhornServer();
		$values['mh_server_engage'] = $this->configObject->getMatterhornEngageServer();
		$values['mh_digest_user'] = $this->configObject->getMatterhornUser();
		$values['mh_digest_password'] = $this->configObject->getMatterhornPassword();
		$values['xsendfile_basedir'] = $this->configObject->getXSendfileBasedir();
		$form->setValuesByArray($values);
		$tpl->setContent($form->getHTML());
	}
		
	/**
	 * Init configuration form.
	 *
	 * @return object form object
	 */
	public function initConfigurationForm()
	{
		global $lng, $ilCtrl;

		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Matterhorn/classes/class.ilMatterhornConfig.php");

		$this->configObject = new ilMatterhornConfig();
		
		$pl = $this->getPluginObject();
	
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
			
		// mh server
		$mh_server = new ilTextInputGUI($pl->txt("mh_server"), "mh_server");
		$mh_server->setRequired(true);
		$mh_server->setMaxLength(100);
		$mh_server->setSize(100);
		$form->addItem($mh_server);

		// mh engage server
		$mh_server_engage = new ilTextInputGUI($pl->txt("mh_server_engage"), "mh_server_engage");
		$mh_server_engage->setRequired(true);
		$mh_server_engage->setMaxLength(100);
		$mh_server_engage->setSize(100);
		$form->addItem($mh_server_engage);
		
		// mh digest user
		$mh_digest_user = new ilTextInputGUI($pl->txt("mh_digest_user"), "mh_digest_user");
		$mh_digest_user->setRequired(true);
		$mh_digest_user->setMaxLength(100);
		$mh_digest_user->setSize(100);
		$form->addItem($mh_digest_user);

		// mh digest password
		$mh_digest_password = new ilTextInputGUI($pl->txt("mh_digest_password"), "mh_digest_password");
		$mh_digest_password->setRequired(true);
		$mh_digest_password->setMaxLength(100);
		$mh_digest_password->setSize(100);
		$form->addItem($mh_digest_password);

		// xsendfile basedir
		$xsendfile_basedir = new ilTextInputGUI($pl->txt("xsendfile_basedir"), "xsendfile_basedir");
		$xsendfile_basedir->setRequired(true);
		$xsendfile_basedir->setMaxLength(100);
		$xsendfile_basedir->setSize(100);
		$form->addItem($xsendfile_basedir);
		
		
		$form->addCommandButton("save", $lng->txt("save"));
	                
		$form->setTitle($pl->txt("matterhorn_plugin_configuration"));
		$form->setFormAction($ilCtrl->getFormAction($this));
		
		return $form;
	}
	
	public function save()
	{
		global $tpl,$ilCtrl;
	
		$pl = $this->getPluginObject();		
		$form = $this->initConfigurationForm();
		if ($form->checkInput())
		{
			$mh_server = $form->getInput("mh_server");
			$mh_server_engage = $form->getInput("mh_server_engage");
			$mh_digest_user = $form->getInput("mh_digest_user");
			$mh_digest_password = $form->getInput("mh_digest_password");
			$xsendfile_basedir = $form->getInput("xsendfile_basedir");
			
			$this->configObject->setMatterhornServer($mh_server);
			$this->configObject->setMatterhornEngageServer($mh_server_engage);			
			$this->configObject->setMatterhornUser($mh_digest_user);
			$this->configObject->setMatterhornPassword($mh_digest_password);
			$this->configObject->setXSendfileBasedir($xsendfile_basedir);
			
			ilUtil::sendSuccess($pl->txt("saving_invoked"), true);
			$ilCtrl->redirect($this, "configure");
		}
		else
		{
			$form->setValuesByPost();
			$tpl->setContent($form->getHtml());
		}
	}

}
?>
