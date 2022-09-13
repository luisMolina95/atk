<?php


namespace Sintattica\Atk\Attributes\NestedAttributes;


use Sintattica\Atk\Attributes\Attribute;
use Sintattica\Atk\Attributes\NumberAttribute;
use Sintattica\Atk\Core\Tools;
use Sintattica\Atk\Db\Query;

class NestedNumberAttribute extends NumberAttribute
{
    use NestableField;

    use NestedSearchable;

    use NestedOrderable;

    public function __construct($name, $flags = 0, $decimals = null)
    {
        $this->setIsNestedAttribute(true);
        parent::__construct($name, $flags, $decimals);
    }

}
