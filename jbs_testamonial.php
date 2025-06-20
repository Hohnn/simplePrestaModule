<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'jbs_testamonial/classes/Testa.php';

class Jbs_testamonial extends Module
{
    public function __construct()
    {
        $this->name = 'jbs_testamonial';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'JBS';
        $this->bootstrap = true;


        parent::__construct();

        $this->displayName = $this->l('JBS Testimonial');
        $this->description = $this->l('Displays testimonials on the home page.');
    }

    public function install()
    {
        return parent::install() && $this->registerHook('displayHome') &&
            $this->installDb() &&
            Configuration::updateValue('TESTAMONIAL_LIMIT', 5);
    }

    /**
     * Get the module configuration form.
     *
     * @return string
     */
    public function getContent()
    {
        if (Tools::isSubmit('submit_testamonial_config')) {
            $limit = Tools::getValue('testamonial_limit', 5);
            Configuration::updateValue('TESTAMONIAL_LIMIT', (int)$limit);

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&conf=6');
        }

        if (Tools::isSubmit('statusjbs_testamonial')) {
            $id = (int)Tools::getValue('id_jbs_testamonial');
            $testa = new Testa($id);
            $testa->active = !$testa->active;
            $testa->update();
            Tools::redirectAdmin(AdminController::$currentIndex . '&configure=' . $this->name . '&conf=6' . '&token=' . Tools::getAdminTokenLite('AdminModules'));
        }

        if (Tools::isSubmit('deletejbs_testamonial')) {
            $id = (int)Tools::getValue('id_jbs_testamonial');
            $testa = new Testa($id);
            $testa->delete();
            Tools::redirectAdmin(AdminController::$currentIndex . '&configure=' . $this->name . '&conf=1&token=' . Tools::getAdminTokenLite('AdminModules'));
        }

        //generate the form with helper
        $helper = new HelperForm();
        $helper->show_cancel_button = false;
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submit_testamonial_config';
        $helper->fields_value['testamonial_limit'] = Configuration::get('TESTAMONIAL_LIMIT', 5);
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');
        $helper->toolbar_btn = [
            'save' => [
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Save'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list'),
            ],
        ];
        $helper->tpl_vars = [
            'fields_value' => $helper->fields_value,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        $htmlLimit = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuration'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Number of testimonials to display'),
                        'name' => 'testamonial_limit',
                        'required' => true,
                        'default_value' => Configuration::get('TESTAMONIAL_LIMIT', 5),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Enregistrer'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $html = $helper->generateForm([$htmlLimit]);
        $html .= $this->getTestimonialsListHtml();

        return $html;
    }
    /**
     * Hook to display testimonials on the home page.
     *
     * @param array $params
     * @return string
     */
    public function hookDisplayHome($params)
    {

        $errors = [];
        $success = false;

        if (Tools::isSubmit('submit_testamonial_home')) {
            $content = Tools::getValue('content');

            if (empty($content)) {
                $errors[] = 'Tous les champs sont obligatoires.';
            } else {
                [
                    'errors' => $errors,
                    'success' => $success,
                ] = $this->addtestamonial($content);
            }
        }

        $this->context->smarty->assign([
            'form_action' => Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']),
            'errors' => $errors,
            'success' => $success,
            'testamonials' => Testa::getAll(true)
        ]);

        return $this->display(__FILE__, 'views/templates/hook/home.tpl');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    private function installDb()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'jbs_testamonial` (
            `id_jbs_testamonial` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id_jbs_testamonial`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    private function addtestamonial(String $content): array
    {
        $errors = [];
        $success = false;

        $user = $this->context->customer;

        if (!$user->isLogged()) {
            $errors[] = $this->l('You must be logged in to submit a testimonial.');
        }

        $testimonial = new Testa();
        $testimonial->user_id = (int)$user->id;
        $testimonial->message = pSQL($content);
        $testimonial->active = 1; // Set active by default
        $testimonial->created_at = date('Y-m-d H:i:s');

        // Here you would typically save the testimonial to the database
        try {
            $testimonial->save();
            $success = true;
        } catch (\Exception $e) {
            $errors[] = $this->l('An error occurred while saving your testimonial: ') . $e->getMessage();
        }

        return [
            'errors' => $errors,
            'success' => $success,
        ];
    }

    public function getTestimonialsListHtml()
    {
        $testimonials = Testa::getAll();
        // Préparer les données pour HelperList
        $rows = [];
        foreach ($testimonials as $testimonial) {
            $rows[] = [
                'id_jbs_testamonial' => $testimonial->id,
                'user' => $testimonial->customer->id,
                'title' => $testimonial->getFullName(),
                'message' => $testimonial->message,
                'active' => $testimonial->active,
                'created_at' => $testimonial->getDateFormatted(),
            ];
        }

        // Définir les champs à afficher
        $fields_list = [
            'id_jbs_testamonial' => [
                'title' => $this->l('ID'),
                'type' => 'text',
            ],
            'user' => [
                'title' => $this->l('User'),
                'type' => 'text',
            ],
            'title' => [
                'title' => $this->l('Title'),
                'type' => 'text',
            ],
            'message' => [
                'title' => $this->l('Message'),
                'type' => 'text',
            ],
            'active' => [
                'title' => $this->l('Active'),
                'type' => 'bool',
                'active' => 'status', // Ajoute cette ligne pour activer le toggle
            ],
            'created_at' => [
                'title' => $this->l('Date'),
                'type' => 'text',
            ],
        ];

        // Configurer le HelperList
        $helper = new HelperList();
        $helper->module = $this;
        $helper->title = $this->l('Testimonials');
        $helper->title_icon = 'icon-list';
        $helper->table = 'jbs_testamonial';
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->identifier = 'id_jbs_testamonial';
        $helper->show_toolbar = false;
        $helper->no_link = true;
        $helper->actions = ['status', 'delete']; // Ajoute 'status'
        $helper->listTotal = count($rows);
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        return $helper->generateList($rows, $fields_list);
    }
}
