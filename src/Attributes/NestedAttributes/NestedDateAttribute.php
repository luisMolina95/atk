<?php


namespace Sintattica\Atk\Attributes\NestedAttributes;


use Sintattica\Atk\Attributes\DateAttribute;
use Sintattica\Atk\Db\Query;

class NestedDateAttribute extends DateAttribute
{

    use NestableField;

    use NestedSearchable;

    use NestedOrderable;


    public function __construct($name, $flags = 0, $format_edit = '', $format_view = '', $min = 0, $max = 0)
    {
        $this->setIsNestedAttribute(true);
        parent::__construct($name, $flags, $format_edit, $format_view, $min, $max);
    }

    public function getSearchCondition(Query $query, $table, $value, $searchmode, $fieldname = ''): string
    {
        if (!$this->getOwnerInstance()->isNestedAttribute($this->fieldName())) {
            return parent::getSearchCondition($query, $table, $value, $searchmode, $fieldname);
        }
        return parent::getSearchCondition($query, $table, $value, $searchmode, $this->buildSQLSearchValue($table));
    }


}
