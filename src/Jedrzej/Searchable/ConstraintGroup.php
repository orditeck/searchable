<?php namespace Jedrzej\Searchable;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class ConstraintGroup extends Constraint
{
    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Creates constraint object for given filter.
     *
     * @param array $group group of query values
     *
     * @return ConstraintGroup
     */
    public static function make($values)
    {
        return new static($values);
    }

    /**
     * Applies constraint to query.
     *
     * @param Builder $builder query builder
     * @param string $field field name
     * @param string $mode determines how constraint is added to existing query ("or" or "and")
     */
    public function apply(Builder $builder, $field, $mode = Constraint::MODE_AND)
    {
        if ($this->isRelation($field)) {
            list($relation, $field) = $this->splitRelationField($field);
            if (static::parseIsNegation($relation)) {
                $builder->whereDoesntHave($relation, function (Builder $builder) use ($field, $mode) {
                    foreach($this->values as $value){
                        $constraint = Constraint::make($value);
                        $constraint->doApply($builder, $field, $mode);
                    }
                });
            } else {
                $builder->whereHas($relation, function (Builder $builder) use ($field, $mode) {
                    foreach($this->values as $value){
                        $constraint = Constraint::make($value);
                        $constraint->doApply($builder, $field, $mode);
                    }
                });
            }
        } else {
            $this->doApply($builder, $field, $mode);
        }
    }

    /**
     * @param array $values values
     */
    protected function __construct($values)
    {
        $this->values = $values;
    }
}