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
        $values['oc_api_user'] = $this->configObject->getOpencastAPIUser();
        $values['oc_api_password'] = $this->configObject->getOpencastAPIPassword();
        $values['xsendfile_header'] = $this->configObject->getXSendfileHeader();
        $values['distribution_directory'] = $this->configObject->getDistributionDirectory();
        $values['uploadworkflow'] = $this->configObject->getUploadWorkflow();
        $values['trimworkflow'] = $this->configObject->getTrimWorkflow();
        $values['publisher'] = $this->configObject->getPublisher();
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

        // upload workflow
        $uploadworkflow = new ilSelectInputGUI($pl->txt('upload_workflow'), 'uploadworkflow');
        $uploadworkflow->setRequired(true);
        $workflow = $this->configObject->getUploadWorkflowOptions();
        if ($workflow === false) {
            $workflow = array(
                "oc_api_setup_required" => $pl->txt("oc_api_setup_required")
            );
        }
        $uploadworkflow->setOptions($workflow);
        $form->addItem($uploadworkflow);

        // trim workflow
        $trimworkflow = new ilSelectInputGUI($pl->txt('trim_workflow'), 'trimworkflow');
        $trimworkflow->setRequired(true);
        $trimworkflows = $this->configObject->getTrimWorkflowOptions();
        if ($trimworkflows === false) {
            $trimworkflows = array(
                "oc_api_setup_required" => $pl->txt("oc_api_setup_required")
            );
        }
        $trimworkflow->setOptions($trimworkflows);
        $form->addItem($trimworkflow);

        // publisher
        $publisher = new ilTextInputGUI($pl->txt('oc_publisher'), 'publisher');
        $publisher->setRequired(false);
        $publisher->setMaxLength(100);
        $publisher->setSize(100);
        $form->addItem($publisher);

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
            $oc_api_user = $form->getInput('oc_api_user');
            $oc_api_password = $form->getInput('oc_api_password');
            $xsendfile_header = $form->getInput('xsendfile_header');
            $distribution_directory = $form->getInput('distribution_directory');
            $uploadworkflow = $form->getInput('uploadworkflow');
            $trimworkflow = $form->getInput('trimworkflow');
            $publisher = $form->getInput('publisher');

            $this->configObject->setMatterhornServer($mh_server);
            $this->configObject->setOpencastAPIUser($oc_api_user);
            $this->configObject->setOpencastAPIPassword($oc_api_password);
            $this->configObject->setXSendfileHeader($xsendfile_header);
            $this->configObject->setDistributionDirectory($distribution_directory);
            $this->configObject->setUploadWorkflow($uploadworkflow);
            $this->configObject->setTrimWorkflow($trimworkflow);
            $this->configObject->setPublisher($publisher);

            ilUtil::sendSuccess($pl->txt('saving_invoked'), true);
            $ilCtrl->redirect($this, 'configure');
        } else {
            $form->setValuesByPost();
            $tpl->setContent($form->getHtml());
        }
    }
}
