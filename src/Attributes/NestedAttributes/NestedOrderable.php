<?php

namespace Sintattica\Atk\Attributes\NestedAttributes;

trait NestedOrderable
{

    /**
     * Parent is Attribute
     * Override the basic order by to work with Json fields
     */
    public function getOrderByStatement($extra = [], $table = '', $direction = 'ASC'): string
    {
        if ($this->getOwnerInstance()->isNestedAttribute($this->fieldName())) {
            $json_query = $this->getQueryForJsonField($this, $table);

            if ($this->dbFieldType() == 'string' && $this->getDb()->getForceCaseInsensitive()) {
                return "LOWER($json_query) " . ($direction ? " {$direction}" : '');
            }

            return "$json_query " . ($direction ? " {$direction}" : '');
        }

        return parent::getOrderByStatement($extra, $table, $direction);
    }

}