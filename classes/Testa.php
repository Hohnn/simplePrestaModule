<?php


class Testa extends ObjectModel
{
    public $id;
    public $user_id;
    public $message;
    public $active;
    public Customer $customer;
    public $created_at;

    public static $definition = [
        'table' => 'jbs_testamonial',
        'primary' => 'id_jbs_testamonial',
        'fields' => [
            'user_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'message' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true],
            'active' => ['type' => self::TYPE_BOOL],
            'created_at' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => false],
        ],
    ];

    public function __construct($id_jbs_testamonial = null, $id_lang = null, $id_shop = Null)
    {
        parent::__construct($id_jbs_testamonial, $id_lang, $id_shop);
    }

    public static function getAll($onlyActive = false): array
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'jbs_testamonial' . ($onlyActive ? ' WHERE active = 1' : '');
        $result = Db::getInstance()->executeS($sql);
        $testimonials = [];
        foreach ($result as $row) {
            $testimonial = new self($row['id_jbs_testamonial']);
            $testimonial->customer = new Customer($row['user_id']);
            $testimonials[] = $testimonial;
        }
        return $testimonials;
    }

    public function getFullName(): string
    {
        return $this->customer->firstname . ' ' . $this->customer->lastname;
    }

    public function getDateFormatted(): string
    {
        return date('d/m/Y H:i', strtotime($this->created_at));
    }
}
