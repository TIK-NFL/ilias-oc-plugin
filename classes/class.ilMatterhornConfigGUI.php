<?php
include_once './Services/Component/classes/class.ilPluginConfigGUI.php';

/**
 * Matterhorn configuration user interface class.
 *
 * @author Per Pascal Grube <pascal.grube@tik.uni-stuttgart.de>
 */
class ilMatterhornConfigGUI extends ilPluginConfigGUI
{

    /**
     * Handles all commmands, default is "configure".
     */
    public function performCommand($cmd)
    {
        switch ($cmd) {
            case 'configure':
            case 'save':
                $this->$cmd();
                break;
        }
    }

    /**
     * Configure screen.
     */
    public function configure()
    {
        global $tpl;
        $form = $this->initConfigurationForm();
        $values = array();
        $values['mh_server'] = $this->configObject->getMatterhornServer();
        $values['mh_server_engage'] = $this->configObject->getMatterhornEngageServer();
        $values['mh_digest_user'] = $this->configObject->getMatterhornUser();
        $values['mh_digest_password'] = $this->configObject->getMatterhornPassword();
        $values['oc_api_user'] = $this->configObject->getOpencastAPIUser();
        $values['oc_api_password'] = $this->configObject->getOpencastAPIPassword();
        $values['mh_directory'] = $this->configObject->getMatterhornDirectory();
        $values['xsendfile_header'] = $this->configObject->getXSendfileHeader();
        $values['distribution_directory'] = $this->configObject->getDistributionDirectory();
        $values['opencast_version'] = $this->configObject->getMatterhornVersion();
        $values['uploadworkflow'] = $this->configObject->getUploadWorkflow();
        $values['signingkey'] = $this->configObject->getSigningKey();
        $values['distributionserver'] = $this->configObject->getDistributionServer();
        $values['stripurl'] = $this->configObject->getStripUrl();
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
        
        $pl = $this->getPluginObject();
        
        $pl->includeClass("class.ilMatterhornConfig.php");
        
        $this->configObject = new ilMatterhornConfig();
        
        include_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
        $form = new ilPropertyFormGUI();
        
        // mh server
        $mh_server = new ilTextInputGUI($pl->txt('mh_server'), 'mh_server');
        $mh_server->setRequired(true);
        $mh_server->setMaxLength(100);
        $mh_server->setSize(100);
        $form->addItem($mh_server);
        
        // mh engage server
        $mh_server_engage = new ilTextInputGUI($pl->txt('mh_server_engage'), 'mh_server_engage');
        $mh_server_engage->setRequired(true);
        $mh_server_engage->setMaxLength(100);
        $mh_server_engage->setSize(100);
        $form->addItem($mh_server_engage);
        
        // mh digest user
        $mh_digest_user = new ilTextInputGUI($pl->txt('mh_digest_user'), 'mh_digest_user');
        $mh_digest_user->setRequired(true);
        $mh_digest_user->setMaxLength(100);
        $mh_digest_user->setSize(100);
        $form->addItem($mh_digest_user);
        
        // mh digest password
        $mh_digest_password = new ilTextInputGUI($pl->txt('mh_digest_password'), 'mh_digest_password');
        $mh_digest_password->setRequired(true);
        $mh_digest_password->setMaxLength(100);
        $mh_digest_password->setSize(100);
        $form->addItem($mh_digest_password);
        
        // oc api user
        $oc_api_user = new ilTextInputGUI($pl->txt('oc_api_user'), 'oc_api_user');
        $oc_api_user->setRequired(true);
        $oc_api_user->setMaxLength(100);
        $oc_api_user->setSize(100);
        $form->addItem($oc_api_user);
        
        // oc api password
        $oc_api_password = new ilTextInputGUI($pl->txt('oc_api_password'), 'oc_api_password');
        $oc_api_password->setRequired(true);
        $oc_api_password->setMaxLength(100);
        $oc_api_password->setSize(100);
        $form->addItem($oc_api_password);
        
        // mh directory
        $mh_directory = new ilTextInputGUI($pl->txt('mh_directory'), 'mh_directory');
        $mh_directory->setRequired(true);
        $mh_directory->setMaxLength(100);
        $mh_directory->setSize(100);
        $form->addItem($mh_directory);
        
        // xsendfile header
        $xsendfile_header = new ilSelectInputGUI($pl->txt('xsendfile_header'), 'xsendfile_header');
        $xsendfile_header->setRequired(true);
        $xsendfile_header->setOptions($this->configObject->getXSendfileHeaderOptions());
        $form->addItem($xsendfile_header);
        
        // distribution directory
        $distribution_directory = new ilTextInputGUI($pl->txt('distribution_directory'), 'distribution_directory');
        $distribution_directory->setRequired(true);
        $distribution_directory->setMaxLength(100);
        $distribution_directory->setSize(100);
        $form->addItem($distribution_directory);
        
        // matterhorn version
        $matterhorn_version = new ilSelectInputGUI($pl->txt('opencast_version'), 'opencast_version');
        $matterhorn_version->setRequired(true);
        $matterhorn_version->setOptions($this->configObject->getMatterhornVersionOptions());
        
        // upload workflow
        $uploadworkflow = new ilTextInputGUI($pl->txt('upload_workflow'), 'uploadworkflow');
        $uploadworkflow->setRequired(true);
        $uploadworkflow->setMaxLength(100);
        $uploadworkflow->setSize(100);
        $form->addItem($uploadworkflow);

        // signingkey
        $signingkey = new ilTextInputGUI($pl->txt('signingkey'), 'signingkey');
        $signingkey->setRequired(true);
        $signingkey->setMaxLength(100);
        $signingkey->setSize(100);
        $form->addItem($signingkey);

        // distributionserver
        $distributionserver = new ilTextInputGUI($pl->txt('distributionserver'), 'distributionserver');
        $distributionserver->setRequired(true);
        $distributionserver->setMaxLength(100);
        $distributionserver->setSize(100);
        $form->addItem($distributionserver);

        // stripurl
        $stripurl = new ilTextInputGUI($pl->txt('stripurl'), 'stripurl');
        $stripurl->setRequired(true);
        $stripurl->setMaxLength(200);
        $stripurl->setSize(200);
        $form->addItem($stripurl);

        
        $form->addItem($matterhorn_version);
        $form->addCommandButton('save', $lng->txt('save'));
        $form->setTitle($pl->txt('opencast_plugin_configuration'));
        $form->setFormAction($ilCtrl->getFormAction($this));
        
        return $form;
    }

    public function save()
    {
        global $tpl, $ilCtrl;
        
        $pl = $this->getPluginObject();
        $form = $this->initConfigurationForm();
        if ($form->checkInput()) {
            $mh_server = $form->getInput('mh_server');
            $mh_server_engage = $form->getInput('mh_server_engage');
            $mh_digest_user = $form->getInput('mh_digest_user');
            $mh_digest_password = $form->getInput('mh_digest_password');
            $oc_api_user = $form->getInput('oc_api_user');
            $oc_api_password = $form->getInput('oc_api_password');
            $mh_directory = $form->getInput('mh_directory');
            $xsendfile_header = $form->getInput('xsendfile_header');
            $distribution_directory = $form->getInput('distribution_directory');
            $opencast_version = $form->getInput('opencast_version');
            $uploadworkflow = $form->getInput('uploadworkflow');
            $signingkey = $form->getInput('signingkey');
            $distributionserver = $form->getInput('distributionserver');
            $stripurl = $form->getInput('stripurl');
            
            $this->configObject->setMatterhornServer($mh_server);
            $this->configObject->setMatterhornEngageServer($mh_server_engage);
            $this->configObject->setMatterhornUser($mh_digest_user);
            $this->configObject->setMatterhornPassword($mh_digest_password);
            $this->configObject->setOpencastAPIUser($oc_api_user);
            $this->configObject->setOpencastAPIPassword($oc_api_password);
            $this->configObject->setMatterhornDirectory($mh_directory);
            $this->configObject->setXSendfileHeader($xsendfile_header);
            $this->configObject->setDistributionDirectory($distribution_directory);
            $this->configObject->setMatterhornVersion($opencast_version);
            $this->configObject->setUploadWorkflow($uploadworkflow);
            $this->configObject->setSigningKey($signingkey);
            $this->configObject->setDistributionServer($distributionserver);
            $this->configObject->setStripUrl($stripurl);
            
            ilUtil::sendSuccess($pl->txt('saving_invoked'), true);
            $ilCtrl->redirect($this, 'configure');
        } else {
            $form->setValuesByPost();
            $tpl->setContent($form->getHtml());
        }
    }
}
