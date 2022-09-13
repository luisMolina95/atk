<?php

namespace Sintattica\Atk\Attributes\NestedAttributes;

use Sintattica\Atk\Attributes\Attribute;

class NestedAttribute extends Attribute
{

    use NestableField;

    use NestedSearchable;

    use NestedOrderable;

    
    public function __construct($name, $flags = 0)
    {
        $this->setIsNestedAttribute(true);
        parent::__construct($name, $flags);
    }

    public function setForceUpdate($value)
    {
        parent::setForceUpdate($value);
        $this->m_ownerInstance->getAttribute($this->m_ownerInstance->getNestedAttributeField())->setForceUpdate($value);
    }
}
