<?php

namespace Sintattica\Atk\Attributes\NestedAttributes;

use Sintattica\Atk\Core\Tools;
use Sintattica\Atk\Db\Query;

/**
 * Converts the queries to work with Json fields.
 * To be used only with NestedAttribute or its derivatives.
 */
trait NestedSearchable
{

    /**
     * Overload the base function to work with Json fields.
     *
     * @param Query $query
     * @param string $table
     * @param mixed $value
     * @param string $searchMode
     * @param string $fieldname
     * @return string
     */
    public function getSearchCondition(Query $query, $table, $value, $searchMode, $fieldname = ''): string
    {
        if (is_array($value)) {
            $value = $value[$this->fieldName()];
        }

        if ($this->m_searchmode) {
            $searchMode = $this->m_searchmode;
        }

        if (strpos($value, '*') !== false && Tools::atk_strlen($value) > 1) {
            // auto wildcard detection
            $searchMode = 'wildcard';
        }

        $fields_sql = $this->getQueryForJsonField($this, $table);

        $func = $searchMode . 'Condition';
        if (method_exists($query, $func) && ($value || ($value == 0))) {
            return $query->$func($fields_sql, $this->escapeSQL($value), $this->dbFieldType());
        } elseif (!method_exists($query, $func)) {
            Tools::atkdebug("Database doesn't support searchmode '$searchMode' for " . $this->fieldName() . ', ignoring condition.');
        }

        return '';
    }

}