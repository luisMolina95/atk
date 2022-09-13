<?php


namespace Sintattica\Atk\Attributes\NestedAttributes;


use Sintattica\Atk\Attributes\MultiListAttribute;
use Sintattica\Atk\Core\Tools;
use Sintattica\Atk\Db\Query;

class NestedMultiListAttribute extends MultiListAttribute
{

    use NestableField;
    use NestedOrderable;

    function __construct($name, $flags, $optionArray, $valueArray = null)
    {
        $this->setIsNestedAttribute(true);
        parent::__construct($name, $flags | self::AF_NO_SORT, $optionArray, $valueArray);
    }

    /**
     * Overload funzione padre per permettere ricerca tramite campo JSON
     *
     * @param Query $query
     * @param string $table
     * @param mixed $value
     * @param string $searchmode
     * @param string $fieldname
     * @return string
     */
    public function getSearchCondition(Query $query, $table, $value, $searchmode, $fieldname = ''): string
    {
        if (!$this->getOwnerInstance()->isNestedAttribute($this->fieldName())) {
            return parent::getSearchCondition($query, $table, $value, $searchmode, $fieldname);
        }

        // Multiselect attribute has only 1 searchmode, and that is substring.

        $searchconditions = array();
        $field_sql = $this->getQueryForJsonField($this, $table);

        if (is_array($value) && $value[0] != "" && count($value) > 0) {
            // include i separatori nel valore da ricercare, così da rendere sicura la ricerca (posto che il separatore NON sia usato nei valori)
            if (in_array('__NONE__', $value)) {
                return $query->nullCondition($field_sql, true);
            }
            if (count($value) == 1) {
                $searchconditions[] = $query->substringCondition($field_sql, Tools::escapeSQL($this->m_fieldSeparator.$value[0].$this->m_fieldSeparator));
            } else {
                foreach ($value as $str) {
                    $searchconditions[] = $query->substringCondition($field_sql, Tools::escapeSQL($this->m_fieldSeparator.$str.$this->m_fieldSeparator));
                }
            }
        }

        if (count($searchconditions)) {
            return '(' . implode(' OR ', $searchconditions) . ')';
        } else {
            return '';
        }
    }

}
