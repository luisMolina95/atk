<?php


namespace Sintattica\Atk\Attributes\NestedAttributes;


use Sintattica\Atk\Attributes\DateTimeAttribute;
use Sintattica\Atk\Db\Query;

class NestedDateTimeAttribute extends DateTimeAttribute
{
    use NestableField;

    use NestedOrderable;

    use NestedSearchable;

    public function __construct($name, $flags = 0, $format_edit = '', $format_view = '', $min = 0, $max = 0)
    {
        $this->setIsNestedAttribute(true);
        parent::__construct($name, $flags, $format_edit, $format_view, $min, $max);
    }

}
