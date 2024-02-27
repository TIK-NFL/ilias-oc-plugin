<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use TIK_NFL\ilias_oc_plugin\ilOpencastConfig;

/**
 * Opencast configuration user interface class.
 *
 * @author Per Pascal Seeland <pascal.seeland@tik.uni-stuttgart.de>
 *
 * @ilCtrl_Calls ilOpencastConfigGUI: ilCommonActionDispatcherGUI
 * @ilCtrl_IsCalledBy ilOpencastConfigGUI: ilObjComponentSettingsGUI
 */
class ilOpencastConfigGUI extends ilPluginConfigGUI
{

    public const FIELD_SERIES_SIGNING_KEY = 'series_signing_key';
    public const FIELD_SHOW_QRCODE = 'show_qrcode';

    private ?ilOpencastConfig $configObject = null;
    private ilGlobalTemplateInterface $tpl;
    private ilCtrlInterface $ctrl;

    public function __construct() {
        global $DIC;
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
    }

    /**
     * Handles all commmands, default is "configure".
     */
    public function performCommand(string $cmd) : void
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
    public function configure() : void
    {
        $form = $this->initConfigurationForm();
        $values = array();
        $values['oc_server'] = $this->configObject->getOpencastServer();
        $values['oc_api_user'] = $this->configObject->getOpencastAPIUser();
        $values['oc_api_password'] = $this->configObject->getOpencastAPIPassword();
        $values['uploadworkflow'] = $this->configObject->getUploadWorkflow();
        $values['trimworkflow'] = $this->configObject->getTrimWorkflow();
        $values['publisher'] = $this->configObject->getPublisher();
        $values['delivery_method'] = $this->configObject->getDeliveryMethod();
        $values['urlsigningkey'] = $this->configObject->getUrlSigningKey();
        $values['distributionserver'] = $this->configObject->getDistributionServer();
        $values['stripurl'] = $this->configObject->getStripUrl();
        $values['tokenvalidity'] = $this->configObject->getTokenValidity();
        $values[self::FIELD_SHOW_QRCODE] = $this->configObject->getShowQRCode();
        $values[self::FIELD_SERIES_SIGNING_KEY] = $this->configObject->getSeriesSigningKey();
        $form->setValuesByArray($values);
        $this->tpl->setContent($form->getHTML());
    }

    /**
     * Init configuration form.
     *
     * @return ilPropertyFormGUI form object
     */
    public function initConfigurationForm() :  ilPropertyFormGUI
    {
        global $lng;

        $pl = $this->getPluginObject();

        $this->configObject = new ilOpencastConfig();

        $form = new ilPropertyFormGUI();

        // oc server
        $oc_server = new ilTextInputGUI($pl->txt('oc_server'), 'oc_server');
        $oc_server->setRequired(true);
        $oc_server->setMaxLength(100);
        $oc_server->setSize(100);
        $form->addItem($oc_server);

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

        $radg = new ilRadioGroupInputGUI($pl->txt('delivery_method'), 'delivery_method');
        $op1 = new ilRadioOption(
            $pl->txt('use_opencast_api_url'),
            'api'
            );
        $radg->addOption($op1);

        $op2 = new ilRadioOption(
            $pl->txt('use_external_distribution_server'),
            "external"
            );
        $radg->addOption($op2);
        // urlsigningkey
        $signingkey = new ilTextInputGUI($pl->txt('urlsigningkey'), 'urlsigningkey');
        $signingkey->setRequired(true);
        $signingkey->setMaxLength(100);
        $signingkey->setSize(100);
        $op2->addSubItem($signingkey);

        // distributionserver
        $distributionserver = new ilTextInputGUI($pl->txt('distributionserver'), 'distributionserver');
        $distributionserver->setRequired(true);
        $distributionserver->setMaxLength(100);
        $distributionserver->setSize(100);
        $op2->addSubItem($distributionserver);

        // stripurl
        $stripurl = new ilTextInputGUI($pl->txt('stripurl'), 'stripurl');
        $stripurl->setRequired(true);
        $stripurl->setMaxLength(200);
        $stripurl->setSize(200);
        $op2->addSubItem($stripurl);

        // token validity
        $tokenvalidity = new ilTextInputGUI($pl->txt('tokenvalidity'), 'tokenvalidity');
        $tokenvalidity->setRequired(true);
        $tokenvalidity->setMaxLength(200);
        $tokenvalidity->setSize(200);
        $op2->addSubItem($tokenvalidity);

        $form->addItem($radg);

        // show qrcode
        $show_qrcode = new ilCheckboxInputGUI($pl->txt('showqrcode'), self::FIELD_SHOW_QRCODE);
        $seriessigningkey = new ilTextInputGUI($pl->txt('seriessigningkey'), self::FIELD_SERIES_SIGNING_KEY);
        $seriessigningkey->setRequired(true);
        $seriessigningkey->setMaxLength(100);
        $seriessigningkey->setSize(100);
        $show_qrcode->addSubItem($seriessigningkey);
        $form->addItem($show_qrcode);

        $form->addCommandButton('save', $lng->txt('save'));
        $form->setTitle($pl->txt('opencast_plugin_configuration'));
        $form->setFormAction($this->ctrl->getFormAction($this));

        return $form;
    }

    public function save(): void
    {
        $pl = $this->getPluginObject();
        $form = $this->initConfigurationForm();
        if ($form->checkInput()) {
            $oc_server = $form->getInput('oc_server');
            $oc_api_user = $form->getInput('oc_api_user');
            $oc_api_password = $form->getInput('oc_api_password');
            $uploadworkflow = $form->getInput('uploadworkflow');
            $trimworkflow = $form->getInput('trimworkflow');
            $publisher = $form->getInput('publisher');
            $delivery_method = $form->getInput('delivery_method');
            $urlsigningkey = $form->getInput('urlsigningkey');
            $distributionserver = $form->getInput('distributionserver');
            $stripurl = $form->getInput('stripurl');
            $tokenvalidity = (int) $form->getInput('tokenvalidity');
            $showqrcode = $form->getInput(self::FIELD_SHOW_QRCODE);
            $seriessigningkey = $form->getInput(self::FIELD_SERIES_SIGNING_KEY);

            $this->configObject->setOpencastServer($oc_server);
            $this->configObject->setOpencastAPIUser($oc_api_user);
            $this->configObject->setOpencastAPIPassword($oc_api_password);
            $this->configObject->setUploadWorkflow($uploadworkflow);
            $this->configObject->setTrimWorkflow($trimworkflow);
            $this->configObject->setPublisher($publisher);
            $this->configObject->setDeliveryMethod($delivery_method);
            $this->configObject->setUrlSigningKey($urlsigningkey);
            $this->configObject->setDistributionServer($distributionserver);
            $this->configObject->setStripUrl($stripurl);
            $this->configObject->setTokenValidity($tokenvalidity);
            $this->configObject->setShowQRCode((bool)$showqrcode);
            $this->configObject->setSeriesSigningKey($seriessigningkey);

            $this->tpl->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS, $pl->txt('saving_invoked'), true);
            $this->ctrl->redirect($this, 'configure');
        } else {
            $form->setValuesByPost();
            $this->tpl->setContent($form->getHtml());
        }
    }
}
