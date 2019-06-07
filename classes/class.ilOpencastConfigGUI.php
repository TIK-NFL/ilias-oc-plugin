<?php
include_once './Services/Component/classes/class.ilPluginConfigGUI.php';

/**
 * Opencast configuration user interface class.
 *
 * @author Per Pascal Seeland <pascal.seeland@tik.uni-stuttgart.de>
 */
class ilOpencastConfigGUI extends ilPluginConfigGUI
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
        $values['mh_server'] = $this->configObject->getOpencastServer();
        $values['oc_api_user'] = $this->configObject->getOpencastAPIUser();
        $values['oc_api_password'] = $this->configObject->getOpencastAPIPassword();
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

        $pl->includeClass("class.ilOpencastConfig.php");

        $this->configObject = new ilOpencastConfig();

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
            $uploadworkflow = $form->getInput('uploadworkflow');
            $trimworkflow = $form->getInput('trimworkflow');
            $publisher = $form->getInput('publisher');

            $this->configObject->setOpencastServer($mh_server);
            $this->configObject->setOpencastAPIUser($oc_api_user);
            $this->configObject->setOpencastAPIPassword($oc_api_password);
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
