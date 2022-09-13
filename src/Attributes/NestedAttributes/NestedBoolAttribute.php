<?php


namespace Sintattica\Atk\Attributes\NestedAttributes;

use Sintattica\Atk\Attributes\BoolAttribute;


class NestedBoolAttribute extends BoolAttribute
{

    /**
     * Make the field nested
     */
    use NestableField;

    /**
     * Use the nested filed search methods
     */
    use NestedSearchable;

    /**
     * Use the nested field 'order by' methods
     */
    use NestedOrderable;


    public function __construct($name, $flags = 0)
    {
        $this->setIsNestedAttribute(true);
        parent::__construct($name, $flags);
    }
}
