<?php

namespace Sintattica\Atk\Attributes\NestedAttributes;

use Sintattica\Atk\Attributes\Attribute;

trait NestableField
{

    /**
     *
     * Construct the SQL query to get data from a Json field.
     *
     * @param Attribute $attr
     * @param string|null $table
     * @return string
     */
    public function getQueryForJsonField(Attribute $attr, ?string $table): string
    {
        if (empty($table)) {
            $table = $attr->getOwnerInstance()->getTable();
        }

        if (strpos($table, '.') !== false) {
            $identifiers = explode('.', $table);

            $tableName = '';
            foreach ($identifiers as $identifier) {
                $tableName .= $attr->getDb()->quoteIdentifier($identifier) . '.';
            }

        } else {
            $tableName = $attr->getDb()->quoteIdentifier($table);
        }

        $nestedAttributeFieldName = $attr->getDb()->quoteIdentifier($attr->m_ownerInstance->getNestedAttributeField());
        $nestedAttrName = $attr->fieldName();

        return "$tableName.$nestedAttributeFieldName->'$.$nestedAttrName'";
    }


    protected function buildSQLSearchValue($table): string
    {
        return "JSON_UNQUOTE(" . $this->getQueryForJsonField($this, $table) . ")";
    }

}